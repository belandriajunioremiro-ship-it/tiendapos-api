<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migración maestra V4 — Multi-país + Onboarding + SaaS
     * -------------------------------------------------------
     * Convierte el POS venezolano en multi-país LATAM (visual + operativo),
     * añade tracking de onboarding y soporte de suscripciones SaaS.
     *
     * Segura para re-ejecutar (todos los cambios tienen IF NOT EXISTS).
     */
    public function up(): void
    {
        // ════════════════════════════════════════════════════════════════
        // PARTE 1 — LIMPIEZA DE DEFAULTS VENEZOLANOS
        // ════════════════════════════════════════════════════════════════

        // 1.1 Quitar el default 'America/Caracas' de zona_horaria
        DB::statement("
            ALTER TABLE tienda 
            ALTER COLUMN zona_horaria DROP DEFAULT;
        ");

        // 1.2 Quitar CHECK constraint de tipo_documento (era venezolano)
        DB::statement("
            ALTER TABLE ventas 
            DROP CONSTRAINT IF EXISTS ventas_tipo_documento_check;
        ");

        // 1.3 Hacer el enum de impuestos.tipo flexible (aceptar cualquier string)
        DB::statement("
            ALTER TABLE impuestos 
            DROP CONSTRAINT IF EXISTS impuestos_tipo_check;
        ");

        // ════════════════════════════════════════════════════════════════
        // PARTE 2 — MULTI-PAÍS EN TIENDA
        // ════════════════════════════════════════════════════════════════

        // 2.1 Añadir columnas de localización a tienda
        if (!Schema::hasColumn('tienda', 'pais')) {
            DB::statement("
                ALTER TABLE tienda 
                ADD COLUMN pais CHAR(2) NOT NULL DEFAULT 'VE';
            ");
            DB::statement("COMMENT ON COLUMN tienda.pais IS 'Código ISO 3166-1 alpha-2: VE, CO, MX, EC, etc.';");
        }

        if (!Schema::hasColumn('tienda', 'codigo_postal')) {
            DB::statement("
                ALTER TABLE tienda 
                ADD COLUMN codigo_postal VARCHAR(20);
            ");
        }

        if (!Schema::hasColumn('tienda', 'regimen_fiscal')) {
            DB::statement("
                ALTER TABLE tienda 
                ADD COLUMN regimen_fiscal VARCHAR(100);
            ");
            DB::statement("COMMENT ON COLUMN tienda.regimen_fiscal IS 'Régimen tributario. VE: Especial/Ordinario. CO: Régimen Común/Simplificado. MX: Persona Moral/Física.';");
        }

        if (!Schema::hasColumn('tienda', 'actividad_economica')) {
            DB::statement("
                ALTER TABLE tienda 
                ADD COLUMN actividad_economica VARCHAR(200);
            ");
        }

        if (!Schema::hasColumn('tienda', 'sitio_web')) {
            DB::statement("
                ALTER TABLE tienda 
                ADD COLUMN sitio_web VARCHAR(200);
            ");
        }

        // ════════════════════════════════════════════════════════════════
        // PARTE 3 — ETIQUETAS FISCALES POR PAÍS (Multi-LATAM visual)
        // ════════════════════════════════════════════════════════════════

        if (!Schema::hasTable('etiquetas_fiscales_pais')) {
            DB::statement("
                CREATE TABLE etiquetas_fiscales_pais (
                    pais         CHAR(2)      NOT NULL,
                    clave        VARCHAR(50)  NOT NULL,
                    etiqueta     VARCHAR(100) NOT NULL,
                    placeholder  VARCHAR(200),
                    PRIMARY KEY (pais, clave)
                );
            ");
            DB::statement("COMMENT ON TABLE etiquetas_fiscales_pais IS 'Traducciones visuales por país. El frontend consulta esta tabla para mostrar RIF/NIT/RFC/RUC según país.';");
        }

        // ════════════════════════════════════════════════════════════════
        // PARTE 4 — ONBOARDING (Tracking de wizard)
        // ════════════════════════════════════════════════════════════════

        // 4.1 Catálogo de pasos del wizard (definido por ti, no editable)
        if (!Schema::hasTable('onboarding_pasos')) {
            DB::statement("
                CREATE TABLE onboarding_pasos (
                    id           SMALLINT    PRIMARY KEY,
                    clave        VARCHAR(40) UNIQUE NOT NULL,
                    nombre       VARCHAR(100) NOT NULL,
                    descripcion  TEXT,
                    orden        SMALLINT    NOT NULL,
                    obligatorio  BOOLEAN     NOT NULL DEFAULT TRUE,
                    activo       BOOLEAN     NOT NULL DEFAULT TRUE,
                    creado_en    TIMESTAMPTZ NOT NULL DEFAULT NOW()
                );
            ");
            DB::statement("COMMENT ON TABLE onboarding_pasos IS 'Catálogo fijo de pasos del wizard de onboarding. El orden define la secuencia.';");
        }

        // 4.2 Estado del onboarding por tienda
        if (!Schema::hasTable('tienda_onboarding')) {
            DB::statement("
                CREATE TABLE tienda_onboarding (
                    id                BIGSERIAL    PRIMARY KEY,
                    tienda_id         BIGINT       NOT NULL REFERENCES tienda(id) ON DELETE CASCADE,
                    paso_actual       SMALLINT     NOT NULL DEFAULT 1,
                    completado        BOOLEAN      NOT NULL DEFAULT FALSE,
                    fecha_completado  TIMESTAMPTZ,
                    metadata          JSONB        NOT NULL DEFAULT '{}',
                    creado_en         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
                    actualizado_en    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
                    UNIQUE (tienda_id)
                );
            ");
            DB::statement("COMMENT ON TABLE tienda_onboarding IS 'Tracking del progreso del wizard. metadata guarda datos temporales entre pasos.';");
        }

        // ════════════════════════════════════════════════════════════════
        // PARTE 5 — SUSCRIPCIONES SaaS
        // ════════════════════════════════════════════════════════════════

        if (!Schema::hasTable('planes')) {
            DB::statement("
                CREATE TABLE planes (
                    id                SMALLINT     PRIMARY KEY,
                    nombre            VARCHAR(60)  NOT NULL,
                    descripcion       TEXT,
                    precio_mensual    NUMERIC(10,2) NOT NULL DEFAULT 0,
                    moneda            CHAR(3)      NOT NULL DEFAULT 'USD',
                    limite_productos  INTEGER,
                    limite_usuarios   INTEGER,
                    limite_almacenes  INTEGER,
                    limite_cajas      INTEGER,
                    dias_trial        SMALLINT     NOT NULL DEFAULT 14,
                    soporte           VARCHAR(30)  DEFAULT 'email',
                    caracteristicas   JSONB        NOT NULL DEFAULT '{}',
                    activo            BOOLEAN      NOT NULL DEFAULT TRUE,
                    creado_en         TIMESTAMPTZ  NOT NULL DEFAULT NOW()
                );
            ");
            DB::statement("COMMENT ON TABLE planes IS 'Planes de suscripción. límites NULL = ilimitado.';");
        }

        if (!Schema::hasTable('suscripciones')) {
            DB::statement("
                CREATE TABLE suscripciones (
                    id              BIGSERIAL    PRIMARY KEY,
                    tienda_id       BIGINT       NOT NULL REFERENCES tienda(id) ON DELETE CASCADE,
                    plan_id         SMALLINT     NOT NULL REFERENCES planes(id),
                    estado          VARCHAR(20)  NOT NULL DEFAULT 'trial',
                    -- trial | activa | suspendida | cancelada | vencida
                    inicio_trial    TIMESTAMPTZ,
                    fin_trial       TIMESTAMPTZ,
                    inicio_pago     TIMESTAMPTZ,
                    fin_periodo     DATE,
                    proximo_cobro   DATE,
                    metodo_pago     VARCHAR(30),
                    -- tarjeta | transferencia | cripto | manual
                    referencia_pago VARCHAR(200),
                    auto_renovar    BOOLEAN      NOT NULL DEFAULT TRUE,
                    cancelado_en    TIMESTAMPTZ,
                    cancelado_por   BIGINT,
                    motivo_cancelacion TEXT,
                    creado_en       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
                    actualizado_en  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
                );
            ");
            DB::statement("COMMENT ON TABLE suscripciones IS 'Suscripción activa de cada tienda. estado=trial los primeros 14 días.';");
            DB::statement("CREATE INDEX idx_suscripciones_tienda ON suscripciones(tienda_id);");
            DB::statement("CREATE INDEX idx_suscripciones_estado ON suscripciones(estado);");
        }

        // ════════════════════════════════════════════════════════════════
        // PARTE 6 — CONFIGURACIÓN DE IMPRESORA
        // ════════════════════════════════════════════════════════════════

        if (!Schema::hasTable('configuracion_impresora')) {
            DB::statement("
                CREATE TABLE configuracion_impresora (
                    id              BIGSERIAL    PRIMARY KEY,
                    tienda_id       BIGINT       NOT NULL REFERENCES tienda(id) ON DELETE CASCADE,
                    caja_id         BIGINT       REFERENCES cajas(id) ON DELETE CASCADE,
                    nombre          VARCHAR(60)  NOT NULL,
                    tipo            VARCHAR(30)  NOT NULL,
                    -- termica_58mm | termica_80mm | a4 | pdf | ninguno
                    marca           VARCHAR(60),
                    conexion        VARCHAR(20),
                    -- usb | red | bluetooth | serial
                    ip              INET,
                    puerto          INTEGER,
                    copias          SMALLINT     NOT NULL DEFAULT 1,
                    imprime_logo    BOOLEAN      NOT NULL DEFAULT TRUE,
                    imprime_qr      BOOLEAN      NOT NULL DEFAULT FALSE,
                    plantilla_id    BIGINT       REFERENCES plantillas_impresion(id),
                    activa          BOOLEAN      NOT NULL DEFAULT TRUE,
                    creado_en       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
                    actualizado_en  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
                );
            ");
            DB::statement("COMMENT ON TABLE configuracion_impresora IS 'Configuración de impresoras por caja. caja_id NULL = aplica a todas las cajas.';");
            DB::statement("CREATE INDEX idx_impresora_tienda ON configuracion_impresora(tienda_id);");
        }

        // ════════════════════════════════════════════════════════════════
        // PARTE 7 — TRIGGER PARA actualizado_en EN TABLAS NUEVAS
        // ════════════════════════════════════════════════════════════════

        DB::statement("
            CREATE OR REPLACE FUNCTION fn_actualizar_timestamp()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$             BEGIN 
                NEW.actualizado_en = NOW(); 
                RETURN NEW; 
            END;
            $$;
        ");

        DB::statement("DROP TRIGGER IF EXISTS trg_ts_tienda_onboarding ON tienda_onboarding;");
        DB::statement("CREATE TRIGGER trg_ts_tienda_onboarding BEFORE UPDATE ON tienda_onboarding FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();");

        DB::statement("DROP TRIGGER IF EXISTS trg_ts_suscripciones ON suscripciones;");
        DB::statement("CREATE TRIGGER trg_ts_suscripciones BEFORE UPDATE ON suscripciones FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();");

        DB::statement("DROP TRIGGER IF EXISTS trg_ts_config_impresora ON configuracion_impresora;");
        DB::statement("CREATE TRIGGER trg_ts_config_impresora BEFORE UPDATE ON configuracion_impresora FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();");

        // ════════════════════════════════════════════════════════════════
        // PARTE 8 — INSERTAR DATOS POR DEFECTO
        // ════════════════════════════════════════════════════════════════

        $this->sembrarCatalogos();
    }

    /**
     * Inserta los catálogos base: pasos del onboarding, etiquetas fiscales
     * por país y planes de suscripción.
     */
    private function sembrarCatalogos(): void
    {
        // ─── Pasos del wizard de onboarding ────────────────────────────
        $pasos = [
            [1, 'pais',              'País y régimen fiscal',     'Selecciona el país de operación para configurar etiquetas fiscales', 1, true],
            [2, 'datos_tienda',      'Datos de la empresa',       'RIF/NIT/RFC, razón social, dirección, logo',                          2, true],
            [3, 'monedas',           'Monedas y tasas',           'Moneda base y monedas aceptadas. Tasas de cambio si aplica',          3, true],
            [4, 'impuestos',         'Impuestos aplicables',      'IVA, IGTF, INC, IEPS, ICE según país',                                4, true],
            [5, 'almacen_caja',      'Almacén y caja',            'Tu depósito principal y punto de cobro',                              5, true],
            [6, 'configuracion_pos', 'Configuración del POS',     'Impresora, plantilla de ticket, datos de la caja',                    6, true],
            [7, 'primer_producto',   'Tu primer producto',        'Carga tu primer producto para probar el sistema',                     7, false],
        ];

        foreach ($pasos as $p) {
            DB::table('onboarding_pasos')->updateOrInsert(
                ['id' => $p[0]],
                [
                    'clave'       => $p[1],
                    'nombre'      => $p[2],
                    'descripcion' => $p[3],
                    'orden'       => $p[4],
                    'obligatorio' => $p[5],
                    'activo'      => true,
                ]
            );
        }

        // ─── Etiquetas fiscales por país ───────────────────────────────
        $etiquetas = [
            // Venezuela 🇻🇪
            ['VE', 'identificacion',     'RIF',           'Ej: J-12345678-9'],
            ['VE', 'impuesto_general',  'IVA',           'Impuesto al Valor Agregado'],
            ['VE', 'impuesto_adicional', 'IGTF',         'Impuesto a Grandes Transacciones Financieras'],
            ['VE', 'factura',            'Factura',       ''],
            ['VE', 'nota_credito',       'Nota de Crédito',''],
            ['VE', 'documento_cliente',  'Cédula/RIF',    ''],
            ['VE', 'regimen_fiscal',     'Régimen',       'Especial / Ordinario'],

            // Colombia 🇨🇴
            ['CO', 'identificacion',     'NIT',           'Ej: 900.123.456-7'],
            ['CO', 'impuesto_general',  'IVA',           'Impuesto al Valor Agregado'],
            ['CO', 'impuesto_adicional', 'INC',          'Impuesto Nacional al Consumo'],
            ['CO', 'factura',            'Factura de Venta',''],
            ['CO', 'nota_credito',       'Nota de Crédito',''],
            ['CO', 'documento_cliente',  'Cédula/CC',     ''],
            ['CO', 'regimen_fiscal',     'Régimen',       'Común / Simplificado'],

            // México 🇲🇽
            ['MX', 'identificacion',     'RFC',           'Ej: ABCD123456XYZ'],
            ['MX', 'impuesto_general',  'IVA',           'Impuesto al Valor Agregado'],
            ['MX', 'impuesto_adicional', 'IEPS',         'Impuesto Especial sobre Producción y Servicios'],
            ['MX', 'factura',            'Factura',       'CFDI'],
            ['MX', 'nota_credito',       'Nota de Crédito',''],
            ['MX', 'documento_cliente',  'CURP/RFC',      ''],
            ['MX', 'regimen_fiscal',     'Régimen',       'Persona Moral / Física'],

            // Ecuador 🇪🇨
            ['EC', 'identificacion',     'RUC',           'Ej: 1712345678001'],
            ['EC', 'impuesto_general',  'IVA',           'Impuesto al Valor Agregado'],
            ['EC', 'impuesto_adicional', 'ICE',          'Impuesto a Consumos Especiales'],
            ['EC', 'factura',            'Factura',       ''],
            ['EC', 'nota_credito',       'Nota de Crédito',''],
            ['EC', 'documento_cliente',  'Cédula/RUC',    ''],
            ['EC', 'regimen_fiscal',     'Régimen',       'RIMPE / General'],

            // Argentina 🇦🇷
            ['AR', 'identificacion',     'CUIT',          'Ej: 30-12345678-9'],
            ['AR', 'impuesto_general',  'IVA',           'Impuesto al Valor Agregado'],
            ['AR', 'impuesto_adicional', 'Impuesto Interno',''],
            ['AR', 'factura',            'Factura',       'Tipo A / B / C'],
            ['AR', 'nota_credito',       'Nota de Crédito',''],
            ['AR', 'documento_cliente',  'CUIT/DNI',      ''],
            ['AR', 'regimen_fiscal',     'Régimen',       'Responsable Inscripto / Monotributo'],

            // Perú 🇵🇪
            ['PE', 'identificacion',     'RUC',           'Ej: 20123456789'],
            ['PE', 'impuesto_general',  'IGV',           'Impuesto General a las Ventas'],
            ['PE', 'impuesto_adicional', 'ISC',          'Impuesto Selectivo al Consumo'],
            ['PE', 'factura',            'Factura',       ''],
            ['PE', 'nota_credito',       'Nota de Crédito',''],
            ['PE', 'documento_cliente',  'DNI/RUC',       ''],
            ['PE', 'regimen_fiscal',     'Régimen',       'Régimen General / MYPE'],
        ];

        foreach ($etiquetas as $e) {
            DB::table('etiquetas_fiscales_pais')->updateOrInsert(
                ['pais' => $e[0], 'clave' => $e[1]],
                ['etiqueta' => $e[2], 'placeholder' => $e[3]]
            );
        }

        // ─── Planes de suscripción ─────────────────────────────────────
        $planes = [
            [1, 'Trial',    'Plan de prueba con todas las funciones',           0.00,   'USD', 50,  3, 1, 1, 14, 'email',    '{"soporte_email": true}'],
            [2, 'Básico',   'Para pequeños comercios',                          19.00,  'USD', 500, 5, 2, 2, 14, 'email',    '{"soporte_email": true}'],
            [3, 'Pro',      'Para comercios en crecimiento',                    39.00,  'USD', 5000, 10, 5, 5, 14, 'chat',    '{"soporte_email": true, "soporte_chat": true}'],
            [4, 'Premium',  'Para cadenas y franquicias',                       99.00,  'USD', null, null, null, null, 14, 'telefono', '{"soporte_email": true, "soporte_chat": true, "soporte_telefono": true, "soporte_prioritario": true}'],
        ];

        foreach ($planes as $p) {
            DB::table('planes')->updateOrInsert(
                ['id' => $p[0]],
                [
                    'nombre'           => $p[1],
                    'descripcion'      => $p[2],
                    'precio_mensual'   => $p[3],
                    'moneda'           => $p[4],
                    'limite_productos' => $p[5],
                    'limite_usuarios'  => $p[6],
                    'limite_almacenes' => $p[7],
                    'limite_cajas'     => $p[8],
                    'dias_trial'       => $p[9],
                    'soporte'          => $p[10],
                    'caracteristicas'  => $p[11],
                    'activo'           => true,
                ]
            );
        }

        // ─── Marcar la tienda existente con VE si no tiene país ─────────
        DB::statement("
            UPDATE tienda 
            SET pais = 'VE' 
            WHERE pais IS NULL OR pais = '';
        ");

        // ─── Crear onboarding inicial para tiendas existentes ──────────
        DB::statement("
            INSERT INTO tienda_onboarding (tienda_id, paso_actual, completado, metadata)
            SELECT t.id, 7, TRUE, '{}'
            FROM tienda t
            WHERE NOT EXISTS (
                SELECT 1 FROM tienda_onboarding to2 WHERE to2.tienda_id = t.id
            );
        ");

        // ─── Crear suscripción Trial para tiendas existentes ───────────
        DB::statement("
            INSERT INTO suscripciones (tienda_id, plan_id, estado, inicio_trial, fin_trial, auto_renovar)
            SELECT t.id, 1, 'trial', NOW(), NOW() + INTERVAL '14 days', TRUE
            FROM tienda t
            WHERE NOT EXISTS (
                SELECT 1 FROM suscripciones s WHERE s.tienda_id = t.id
            );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ─── Drop triggers ─────────────────────────────────────────────
        DB::statement("DROP TRIGGER IF EXISTS trg_ts_config_impresora ON configuracion_impresora;");
        DB::statement("DROP TRIGGER IF EXISTS trg_ts_suscripciones ON suscripciones;");
        DB::statement("DROP TRIGGER IF EXISTS trg_ts_tienda_onboarding ON tienda_onboarding;");

        // ─── Drop tablas nuevas (orden inverso por FKs) ────────────────
        DB::statement("DROP TABLE IF EXISTS configuracion_impresora CASCADE;");
        DB::statement("DROP TABLE IF EXISTS suscripciones CASCADE;");
        DB::statement("DROP TABLE IF EXISTS planes CASCADE;");
        DB::statement("DROP TABLE IF EXISTS tienda_onboarding CASCADE;");
        DB::statement("DROP TABLE IF EXISTS onboarding_pasos CASCADE;");
        DB::statement("DROP TABLE IF EXISTS etiquetas_fiscales_pais CASCADE;");

        // ─── Quitar columnas de tienda ─────────────────────────────────
        $columnas = ['sitio_web', 'actividad_economica', 'regimen_fiscal', 'codigo_postal', 'pais'];
        foreach ($columnas as $col) {
            if (Schema::hasColumn('tienda', $col)) {
                DB::statement("ALTER TABLE tienda DROP COLUMN {$col};");
            }
        }

        // ─── Restaurar defaults venezolanos ────────────────────────────
        DB::statement("ALTER TABLE tienda ALTER COLUMN zona_horaria SET DEFAULT 'America/Caracas';");

        // ─── Recrear CHECK de tipo_documento ───────────────────────────
        DB::statement("
            ALTER TABLE ventas 
            ADD CONSTRAINT ventas_tipo_documento_check 
            CHECK (tipo_documento IN ('FAC','NE','NC','ND','COT'));
        ");
    }
};