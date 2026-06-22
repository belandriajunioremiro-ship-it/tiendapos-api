
-- =============================================================================
--  TIENDAPOS v2.1 - MULTIMONEDA DINÁMICA + CORRECCIONES FISCALES IGTF
--  PostgreSQL 15+  |  Laravel 11 + Spatie  |  ExchangeRate API
--
--  CAMBIOS vs v2.0:
--    1) IGTF separado: ventas.impuesto_iva / impuesto_igtf
--       pagos_venta.monto_igtf + tasa_igtf_pct
--       metodos_pago.grava_igtf (configurable por admin)
--    2) ventas: tipo_documento (FAC/NE/NC/ND/COT) + tipo_pago (contado/credito)
--       + estado limpio (borrador/pendiente/pagada/parcial/anulada)
--    3) items_venta.impuesto_monto (monto absoluto para evitar descuadre)
--    4) devoluciones_venta.numero_nota_credito + trazabilidad multimoneda
--       en items_devolucion
--
--  NORMATIVA IGTF VENEZUELA (vigente 2024-2025):
--    - Alícuota: 3% sobre pagos en DIVISAS y CRIPTOMONEDAS
--    - 0% sobre pagos en Bolívares (Decreto N° 4.972, Gaceta 6.821)
--    - Aplica cuando el receptor es Sujeto Pasivo Especial (SPE)
--    - Se calcula sobre el MEDIO DE PAGO, no sobre el producto
--    - Se muestra SEPARADO en la factura (línea aparte, no sumado al total)
--    - En pagos mixtos: solo grava la porción en divisas/cripto
--    - En ventas a crédito: el IGTF se documenta al momento del pago efectivo
--    - Métodos gravados: efectivo USD/EUR/COP, Zelle, cripto (USDT, BTC)
--    - Métodos NO gravados: efectivo VES, transferencia bancaria VES,
--      pago móvil VES, tarjeta débito/crédito VES, cheque VES
--
--  ARQUITECTURA MULTIMONEDA - REGLA DE ORO:
--  Todo importe monetario guarda SIEMPRE tres valores:
--    (1) monto_original  -> en la moneda en que ocurrió el hecho
--    (2) tasa_usada      -> snapshot de la tasa al momento exacto
--    (3) monto_en_base   -> convertido a tienda.moneda_base
--  Esto permite reportes en cualquier moneda, históricos inmutables
--  y cuadre de caja multicurrency.
--
--  FLUJO DE TASAS:
--    ExchangeRate API  ->  tasas_cambio (activa=TRUE, fuente='api')
--    BCV / manual      ->  tasas_cambio (activa=TRUE, fuente='BCV'/'manual')
--    fn_tasa_entre(A,B)->  resuelve directa, inversa o cruzada via pivot
--
--  CIERRE DE CAJA:
--    sesiones_caja       -> totales en moneda_base (resumen rápido)
--    sesion_caja_monedas -> desglose por moneda (conteo físico vs calculado)
--
--  SIN HARDCODE:
--    Ningún DEFAULT 'USD' ni 'VES' en columnas de schema.
--    El usuario define la moneda_base en tienda y las monedas aceptadas
--    en tienda_monedas. Toda la lógica de conversión es dinámica.
--
--  ROLES:  admin | supervisor | cajero -> Laravel Spatie (fuera de esta DB)
--  user_id en cada tabla   -> users.id de Laravel
-- =============================================================================

CREATE EXTENSION IF NOT EXISTS "ltree";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- =============================================================================
-- MÓDULO 0 - MONEDAS Y CONFIGURACIÓN DE TIENDA
-- =============================================================================

-- Catálogo ISO 4217 + criptomonedas comunes
-- El usuario puede agregar más desde el panel admin
CREATE TABLE monedas (
    codigo        CHAR(3)      PRIMARY KEY,          -- ISO 4217: USD, VES, COP, EUR.
    nombre        VARCHAR(60)  NOT NULL,
    simbolo       VARCHAR(8)   NOT NULL DEFAULT '$',
    decimales     SMALLINT     NOT NULL DEFAULT 2,   -- 0 para CLP, PYG; 2 para casi todo
    es_cripto     BOOLEAN      NOT NULL DEFAULT FALSE,
    activa        BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en     TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE monedas IS 'Catálogo de monedas ISO 4217 + criptos. El usuario habilita las que usa.';

INSERT INTO monedas (codigo, nombre, simbolo, decimales, es_cripto) VALUES
    -- América Latina (las más usadas en el contexto venezolano/colombiano)
    ('USD', 'Dólar estadounidense',  '$',   2, FALSE),
    ('VES', 'Bolívar venezolano',    'Bs.', 2, FALSE),
    ('COP', 'Peso colombiano',       '$',   2, FALSE),
    ('EUR', 'Euro',                  '€',   2, FALSE),
    ('BRL', 'Real brasileño',        'R$',  2, FALSE),
    ('ARS', 'Peso argentino',        '$',   2, FALSE),
    ('MXN', 'Peso mexicano',         '$',   2, FALSE),
    ('PEN', 'Sol peruano',           'S/',  2, FALSE),
    ('CLP', 'Peso chileno',          '$',   0, FALSE),
    ('BOB', 'Boliviano',             'Bs.', 2, FALSE),
    ('PYG', 'Guaraní paraguayo',     '₲',   0, FALSE),
    ('UYU', 'Peso uruguayo',         '$',   2, FALSE),
    ('GBP', 'Libra esterlina',       '£',   2, FALSE),
    -- Criptomonedas comunes en Venezuela
    ('UST', 'USDT (Tether)',         '₮',   2, TRUE),
    ('BTC', 'Bitcoin',               '₿',   8, TRUE);

-- -----------------------------------------------------------------------------
-- Configuración única de la tienda (1 sola fila, id = 1)
-- moneda_base: la moneda en que se llevan las cuentas y los reportes
-- moneda_pivot_api: la moneda que usa ExchangeRate API como base
--   (normalmente USD en el plan gratuito; cambiar si usas plan de pago)
-- -----------------------------------------------------------------------------
CREATE TABLE tienda (
    id               BIGSERIAL    PRIMARY KEY,
    rif              VARCHAR(20)  NOT NULL,
    razon_social     VARCHAR(200) NOT NULL,
    nombre_comercial VARCHAR(200),
    direccion        TEXT,
    telefono         VARCHAR(20),
    email            VARCHAR(150),
    logo_url         VARCHAR(500),
    moneda_base      CHAR(3)      NOT NULL REFERENCES monedas(codigo),
    -- ↑ SIN DEFAULT: el usuario DEBE elegirla al configurar la tienda
    moneda_pivot_api CHAR(3)      NOT NULL DEFAULT 'USD' REFERENCES monedas(codigo),
    -- ↑ Pivot de ExchangeRate API. Libre plan = USD. Cambiar si usas plan pago.
    zona_horaria     VARCHAR(50)  NOT NULL DEFAULT 'America/Caracas',
    prefijo_factura  VARCHAR(10)  NOT NULL DEFAULT 'FAC',
    siguiente_numero INTEGER      NOT NULL DEFAULT 1,
    decimales_precio SMALLINT     NOT NULL DEFAULT 2,
    -- v2.1: Configuración IGTF a nivel de tienda
    es_agente_igtf   BOOLEAN      NOT NULL DEFAULT FALSE,
    -- TRUE si la tienda es Sujeto Pasivo Especial (SPE) ante el SENIAT.
    -- Solo los SPE están obligados a percibir el IGTF.
    alicuota_igtf    NUMERIC(6,4) NOT NULL DEFAULT 3.0000,
    -- Alícuota vigente del IGTF (3% según Decreto N° 4.972).
    -- Se guarda aquí para poder cambiarla si el gobierno la modifica,
    -- sin necesidad de tocar código.
    activo           BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    actualizado_en   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE  tienda IS 'Config de la tienda. Una sola fila (id=1). moneda_base es la moneda contable.';
COMMENT ON COLUMN tienda.moneda_base IS 'Moneda de referencia para reportes y cierres. El usuario la define.';
COMMENT ON COLUMN tienda.moneda_pivot_api IS 'Base de ExchangeRate API. Plan gratis=USD. fn_tasa_entre usa esto como pivot.';
COMMENT ON COLUMN tienda.es_agente_igtf IS 'TRUE = la tienda es SPE y DEBE percibir IGTF en pagos con divisas/cripto. FALSE = no aplica.';
COMMENT ON COLUMN tienda.alicuota_igtf IS 'Alícuota IGTF vigente (%). Se usa como snapshot al momento de cobrar. Cambiar aquí si el gobierno modifica la alícuota.';

INSERT INTO tienda (rif, razon_social, nombre_comercial, moneda_base, zona_horaria, es_agente_igtf) VALUES
    ('J-12345678-9', 'Mi Empresa C.A.', 'Mi Tienda POS', 'USD', 'America/Caracas', TRUE);
    -- ↑ Cambiar moneda_base a lo que necesites: 'VES', 'COP', etc.
    -- ↑ es_agente_igtf=TRUE porque la mayoría de comercios formales en VE son SPE

-- -----------------------------------------------------------------------------
-- Monedas que la tienda acepta para cobrar y/o pagar
-- El admin habilita/deshabilita desde el panel sin tocar código
-- -----------------------------------------------------------------------------
CREATE TABLE tienda_monedas (
    id                 BIGSERIAL  PRIMARY KEY,
    moneda             CHAR(3)    NOT NULL UNIQUE REFERENCES monedas(codigo),
    acepta_ventas      BOOLEAN    NOT NULL DEFAULT TRUE,   -- cobro al cliente
    acepta_compras     BOOLEAN    NOT NULL DEFAULT FALSE,  -- pago a proveedor
    acepta_creditos    BOOLEAN    NOT NULL DEFAULT FALSE,  -- abonos de crédito
    orden_display      SMALLINT   NOT NULL DEFAULT 0,      -- orden en el POS
    activa             BOOLEAN    NOT NULL DEFAULT TRUE,
    creado_en          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE tienda_monedas IS 'Monedas habilitadas. El POS solo muestra monedas con acepta_ventas=TRUE.';

INSERT INTO tienda_monedas (moneda, acepta_ventas, acepta_compras, acepta_creditos, orden_display) VALUES
    ('USD', TRUE,  TRUE,  TRUE,  1),
    ('VES', TRUE,  FALSE, TRUE,  2),
    ('COP', TRUE,  FALSE, FALSE, 3),
    ('UST', TRUE,  FALSE, FALSE, 4),
    ('EUR', FALSE, FALSE, FALSE, 5);

-- =============================================================================
-- MÓDULO 1 - CATÁLOGOS BASE
-- =============================================================================

-- Unidades de medida con árbol de conversión (base_id=NULL -> es la unidad raíz)
CREATE TABLE unidades (
    id                BIGSERIAL     PRIMARY KEY,
    base_id           BIGINT        REFERENCES unidades(id),
    nombre            VARCHAR(50)   NOT NULL,
    abreviatura       VARCHAR(10)   NOT NULL,
    tipo              VARCHAR(20)   NOT NULL DEFAULT 'cantidad',
    -- cantidad | peso | volumen | longitud
    factor_conversion NUMERIC(14,6) NOT NULL DEFAULT 1.000000,
    activo            BOOLEAN       NOT NULL DEFAULT TRUE
);
COMMENT ON TABLE unidades IS 'Unidades con conversión. factor: caja=24->24, ml->0.001';
CREATE UNIQUE INDEX unidades_tienda_abreviatura_unique ON unidades(tienda_id, abreviatura);

INSERT INTO unidades (nombre, abreviatura, tipo, factor_conversion) VALUES
    ('Unidad',      'und', 'cantidad', 1),
    ('Par',         'par', 'cantidad', 2),
    ('Docena',      'doc', 'cantidad', 12),
    ('Caja x24',    'cja', 'cantidad', 24),
    ('Blister x10', 'bls', 'cantidad', 10),
    ('Kilogramo',   'kg',  'peso',     1),
    ('Gramo',       'g',   'peso',     0.001),
    ('Libra',       'lb',  'peso',     0.453592),
    ('Litro',       'lt',  'volumen',  1),
    ('Mililitro',   'ml',  'volumen',  0.001),
    ('Metro',       'm',   'longitud', 1),
    ('Centímetro',  'cm',  'longitud', 0.01);

UPDATE unidades SET base_id = (SELECT id FROM unidades WHERE abreviatura='und') WHERE abreviatura IN ('par','doc','cja','bls');
UPDATE unidades SET base_id = (SELECT id FROM unidades WHERE abreviatura='kg')  WHERE abreviatura IN ('g','lb');
UPDATE unidades SET base_id = (SELECT id FROM unidades WHERE abreviatura='lt')  WHERE abreviatura = 'ml';
UPDATE unidades SET base_id = (SELECT id FROM unidades WHERE abreviatura='m')   WHERE abreviatura = 'cm';

-- -----------------------------------------------------------------------------
-- Impuestos globales (sin sucursal_id - una sola tienda)
-- es_defecto=TRUE: lo heredan los productos con impuesto_id=NULL
-- -----------------------------------------------------------------------------
CREATE TABLE impuestos (
    id         BIGSERIAL    PRIMARY KEY,
    nombre     VARCHAR(60)  NOT NULL,
    porcentaje NUMERIC(6,4) NOT NULL,
    tipo       VARCHAR(20)  NOT NULL DEFAULT 'iva',
    -- iva | igtf | especifico | exento
    aplica_a   VARCHAR(10)  NOT NULL DEFAULT 'ambos',
    -- venta | compra | ambos
    es_defecto BOOLEAN      NOT NULL DEFAULT FALSE,
    activo     BOOLEAN      NOT NULL DEFAULT TRUE
);
CREATE UNIQUE INDEX idx_impuesto_defecto ON impuestos(tienda_id) WHERE es_defecto = TRUE;
COMMENT ON TABLE impuestos IS 'es_defecto=TRUE es el que heredan productos sin impuesto_id explícito. Único por tienda.';

INSERT INTO impuestos (nombre, porcentaje, tipo, es_defecto) VALUES
    ('IVA 16%', 16.0000, 'iva',    TRUE),
    ('IVA 8%',   8.0000, 'iva',    FALSE),
    ('IGTF 3%',  3.0000, 'igtf',   FALSE),
    ('Exento',   0.0000, 'exento', FALSE);

-- -----------------------------------------------------------------------------
-- TASAS DE CAMBIO - Multimoneda
--
-- Estructura:
--   moneda_base    -> la moneda "desde" (ej: USD)
--   moneda_destino -> la moneda "hasta" (ej: VES)
--   tasa           -> cuántas unidades de destino = 1 unidad de base
--                    Ej: USD/VES = 36.45 -> 1 USD = 36.45 VES
--   activa = TRUE  -> es la tasa vigente para ese par (índice único parcial)
--
-- fn_tasa_entre(A, B) resuelve:
--   1) tasa directa A->B
--   2) inversa de B->A
--   3) cross-rate via tienda.moneda_pivot_api
--
-- Actualización desde ExchangeRate API:
--   La app hace UPDATE activa=FALSE del par anterior
--   y luego INSERT del nuevo con activa=TRUE
-- -----------------------------------------------------------------------------
CREATE TABLE tasas_cambio (
    id             BIGSERIAL     PRIMARY KEY,
    moneda_base    CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    moneda_destino CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    tasa           NUMERIC(20,8) NOT NULL CHECK (tasa > 0),
    fuente         VARCHAR(30)   NOT NULL DEFAULT 'manual',
    -- manual | BCV | paralelo | api_automatica | BCE
    fecha          DATE          NOT NULL DEFAULT CURRENT_DATE,
    activa         BOOLEAN       NOT NULL DEFAULT TRUE,
    notas          TEXT,
    creado_en      TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    CHECK (moneda_base <> moneda_destino)
);
-- Solo UNA tasa activa por par de monedas, fuente y tienda
CREATE UNIQUE INDEX idx_tasa_activa
    ON tasas_cambio (tienda_id, moneda_base, moneda_destino, fuente) WHERE activa = TRUE;
CREATE INDEX idx_tasas_fecha
    ON tasas_cambio (moneda_base, moneda_destino, fecha DESC);

COMMENT ON TABLE  tasas_cambio IS '1 tasa activa por par/fuente (índice único parcial). La app desactiva la vieja antes de insertar la nueva.';
COMMENT ON COLUMN tasas_cambio.tasa IS '1 unidad de moneda_base = N unidades de moneda_destino. Ej: USD/VES=36.45';

-- Tasas de ejemplo (actualizar desde ExchangeRate API en producción)
INSERT INTO tasas_cambio (moneda_base, moneda_destino, tasa, fuente, fecha) VALUES
    ('USD', 'VES', 36.45000000,  'BCV',           CURRENT_DATE),
    ('USD', 'VES', 38.10000000,  'paralelo',       CURRENT_DATE),
    ('USD', 'COP', 4200.00000000,'api_automatica', CURRENT_DATE),
    ('USD', 'EUR',    0.92000000,'api_automatica', CURRENT_DATE),
    ('USD', 'BRL',    5.10000000,'api_automatica', CURRENT_DATE),
    ('USD', 'UST',    1.00000000,'manual',          CURRENT_DATE),
    ('USD', 'ARS',  900.00000000,'api_automatica', CURRENT_DATE);

-- -----------------------------------------------------------------------------

CREATE TABLE categorias_productos (
    id        BIGSERIAL    PRIMARY KEY,
    padre_id  BIGINT       REFERENCES categorias_productos(id),
    nombre    VARCHAR(100) NOT NULL,
    slug      VARCHAR(120) NOT NULL,
    nivel     SMALLINT     NOT NULL DEFAULT 1,
    ruta      LTREE,
    icono     VARCHAR(60),
    activo    BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_cat_ruta  ON categorias_productos USING GIST (ruta);
CREATE INDEX idx_cat_padre ON categorias_productos(padre_id);
-- Único por tienda: cada tienda puede tener su propia jerarquía
CREATE UNIQUE INDEX categorias_productos_tienda_slug_unique ON categorias_productos(tienda_id, slug);

INSERT INTO categorias_productos (nombre, slug, nivel, ruta) VALUES
    ('Farmacia',   'farmacia',   1, 'farmacia'),
    ('Ropa',       'ropa',       1, 'ropa'),
    ('Ferretería', 'ferreteria', 1, 'ferreteria'),
    ('Licorería',  'licoreria',  1, 'licoreria'),
    ('Bodega',     'bodega',     1, 'bodega');

INSERT INTO categorias_productos (padre_id, nombre, slug, nivel, ruta) VALUES
    (1, 'Antibióticos',     'farmacia-antibioticos', 2, 'farmacia.antibioticos'),
    (1, 'Analgésicos',      'farmacia-analgesicos',  2, 'farmacia.analgesicos'),
    (1, 'Vitaminas',        'farmacia-vitaminas',    2, 'farmacia.vitaminas'),
    (2, 'Ropa dama',        'ropa-dama',             2, 'ropa.dama'),
    (2, 'Ropa caballero',   'ropa-caballero',        2, 'ropa.caballero'),
    (3, 'Tornillería',      'ferreteria-tornilleria',2, 'ferreteria.tornilleria'),
    (3, 'Herramientas',     'ferreteria-herramientas',2,'ferreteria.herramientas'),
    (4, 'Ron',              'licoreria-ron',         2, 'licoreria.ron'),
    (4, 'Whisky',           'licoreria-whisky',      2, 'licoreria.whisky'),
    (5, 'Granos y harinas', 'bodega-granos',         2, 'bodega.granos');

-- Atributos dinámicos por categoría (para variantes)
CREATE TABLE definicion_atributos (
    id            BIGSERIAL    PRIMARY KEY,
    categoria_id  BIGINT       NOT NULL REFERENCES categorias_productos(id),
    clave         VARCHAR(60)  NOT NULL,
    etiqueta      VARCHAR(80)  NOT NULL,
    tipo_dato     VARCHAR(20)  NOT NULL DEFAULT 'text',
    -- text | number | select | boolean | color | date
    opciones      JSONB,
    obligatorio   BOOLEAN      NOT NULL DEFAULT FALSE,
    filtrable     BOOLEAN      NOT NULL DEFAULT FALSE,
    en_listado    BOOLEAN      NOT NULL DEFAULT FALSE,
    orden        SMALLINT     NOT NULL DEFAULT 0,
    activo       BOOLEAN      NOT NULL DEFAULT TRUE,
    UNIQUE (categoria_id, clave)
);

INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, opciones, obligatorio, filtrable, en_listado, orden)
SELECT id,'concentracion','Concentración','select','["100mg","250mg","500mg","1g"]'::jsonb,TRUE,TRUE,TRUE,1 FROM categorias_productos WHERE slug='farmacia';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, obligatorio, filtrable, en_listado, orden)
SELECT id,'requiere_receta','Requiere receta','boolean',TRUE,TRUE,TRUE,2 FROM categorias_productos WHERE slug='farmacia';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, obligatorio, orden)
SELECT id,'laboratorio','Laboratorio','text',FALSE,3 FROM categorias_productos WHERE slug='farmacia';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, opciones, obligatorio, filtrable, en_listado, orden)
SELECT id,'talla','Talla','select','["XS","S","M","L","XL","XXL"]'::jsonb,TRUE,TRUE,TRUE,1 FROM categorias_productos WHERE slug='ropa';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, opciones, obligatorio, filtrable, orden)
SELECT id,'genero','Género','select','["masculino","femenino","unisex","niño","niña"]'::jsonb,TRUE,TRUE,2 FROM categorias_productos WHERE slug='ropa';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, obligatorio, filtrable, orden)
SELECT id,'color','Color','color',FALSE,TRUE,3 FROM categorias_productos WHERE slug='ropa';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, obligatorio, filtrable, en_listado, orden)
SELECT id,'calibre_mm','Calibre (mm)','number',FALSE,TRUE,TRUE,1 FROM categorias_productos WHERE slug='ferreteria';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, opciones, obligatorio, orden)
SELECT id,'material','Material','select','["acero","aluminio","hierro","plástico","cobre"]'::jsonb,FALSE,2 FROM categorias_productos WHERE slug='ferreteria';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, obligatorio, filtrable, en_listado, orden)
SELECT id,'grado_alcoholico','Grado alcohólico (%)','number',TRUE,TRUE,TRUE,1 FROM categorias_productos WHERE slug='licoreria';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, opciones, obligatorio, orden)
SELECT id,'tipo_bebida','Tipo de bebida','select','["ron","whisky","vodka","vino","cerveza","brandy","tequila","gin"]'::jsonb,TRUE,2 FROM categorias_productos WHERE slug='licoreria';
INSERT INTO definicion_atributos (categoria_id, clave, etiqueta, tipo_dato, obligatorio, orden)
SELECT id,'volumen_ml','Volumen (ml)','number',TRUE,3 FROM categorias_productos WHERE slug='licoreria';

-- -----------------------------------------------------------------------------

CREATE TABLE margenes_ganancia (
    id           BIGSERIAL    PRIMARY KEY,
    categoria_id BIGINT       REFERENCES categorias_productos(id),
    nombre       VARCHAR(80)  NOT NULL,
    porcentaje   NUMERIC(7,4) NOT NULL,
    tipo         VARCHAR(25)  NOT NULL DEFAULT 'sobre_costo',
    descripcion  TEXT,
    es_defecto   BOOLEAN      NOT NULL DEFAULT FALSE,
    activo       BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX idx_margen_defecto ON margenes_ganancia(tienda_id) WHERE es_defecto = TRUE;

INSERT INTO margenes_ganancia (nombre, porcentaje, tipo, descripcion, es_defecto) VALUES
    ('Margen estándar 20%',  20.0000, 'sobre_costo', 'Margen general',                          TRUE),
    ('Margen bajo 12%',      12.0000, 'sobre_costo', 'Para mayoristas',                         FALSE),
    ('Margen medio 25%',     25.0000, 'sobre_costo', 'Rotación media',                          FALSE),
    ('Margen alto 30%',      30.0000, 'sobre_costo', 'Baja rotación o alto valor',              FALSE),
    ('Margen farmacia 35%',  35.0000, 'sobre_costo', 'Medicamentos',                            FALSE),
    ('Margen ropa 40%',      40.0000, 'sobre_costo', 'Prendas de vestir',                       FALSE),
    ('Margen licorería 18%', 18.0000, 'sobre_costo', 'Licores',                                 FALSE);

-- -----------------------------------------------------------------------------
-- Listas de precio por segmento
-- tipo y valor NO tienen moneda: son ajustes % sobre precio_base del producto
-- La moneda la define el producto (moneda_precio)
-- -----------------------------------------------------------------------------
CREATE TABLE listas_precio (
    id          BIGSERIAL    PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL,
    tipo        VARCHAR(30)  NOT NULL DEFAULT 'porcentaje_precio_base',
    -- porcentaje_precio_base | porcentaje_costo | precio_fijo
    valor       NUMERIC(8,4) NOT NULL DEFAULT 0,
    -- -10 = 10% descuento, 0 = sin cambio, +5 = 5% incremento
    descripcion TEXT,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE listas_precio IS 'Ajustes % sobre precio_base. Sin moneda: aplica en la moneda del producto.';

INSERT INTO listas_precio (nombre, tipo, valor, descripcion) VALUES
    ('Precio detal',     'porcentaje_precio_base',  0.0000, 'Precio público normal'),
    ('Precio mayorista', 'porcentaje_precio_base', -10.0000,'10% descuento al mayor'),
    ('Precio empleado',  'porcentaje_precio_base', -15.0000,'15% descuento empleados'),
    ('Precio especial',  'porcentaje_precio_base',  -5.0000,'5% descuento clientes frecuentes');

-- =============================================================================
-- MÓDULO 2 - PRODUCTOS
-- =============================================================================
-- moneda_precio: la moneda en que está definido el precio de este producto.
-- precio_base (GENERADA): costo_promedio × (1 + margen_pct/100) - en moneda_precio
-- Al vender, el POS convierte precio_base -> moneda_factura usando fn_tasa_entre()
-- =============================================================================

CREATE TABLE productos (
    id                    BIGSERIAL     PRIMARY KEY,
    categoria_id          BIGINT        NOT NULL REFERENCES categorias_productos(id),
    unidad_id             BIGINT        NOT NULL REFERENCES unidades(id),
    impuesto_id           BIGINT        REFERENCES impuestos(id),
    -- NULL = hereda es_defecto de impuestos
    margen_id             BIGINT        REFERENCES margenes_ganancia(id),
    moneda_precio         CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    -- SIN DEFAULT: la app debe pasarla explícitamente
    codigo_sku            VARCHAR(50)   NOT NULL,
    nombre                VARCHAR(200)  NOT NULL,
    descripcion           TEXT,
    foto_url              VARCHAR(500),
    referencia_interna    VARCHAR(80),
    costo_promedio        NUMERIC(14,6) NOT NULL DEFAULT 0,
    -- en moneda_precio
    margen_pct            NUMERIC(7,4)  NOT NULL DEFAULT 20.0000,
    precio_base           NUMERIC(14,6) GENERATED ALWAYS AS
                          ( ROUND(costo_promedio * (1.0 + margen_pct / 100.0), 6) ) STORED,
    -- GENERADA en moneda_precio. No modificar directamente.
    precio_minimo         NUMERIC(14,6),
    -- en moneda_precio
    permite_precio_manual BOOLEAN       NOT NULL DEFAULT FALSE,
    aplica_descuento      BOOLEAN       NOT NULL DEFAULT TRUE,
    atributos             JSONB         NOT NULL DEFAULT '{}',
    activo                BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en             TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    actualizado_en        TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE  productos IS 'Catálogo maestro. precio_base=GENERADA en moneda_precio. El POS convierte a moneda_factura al vender.';
COMMENT ON COLUMN productos.moneda_precio IS 'Moneda en que están costo y precio de este producto. Puede diferir de moneda_base de la tienda.';

CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_sku       ON productos(codigo_sku);
CREATE INDEX idx_productos_nombre    ON productos USING GIN (nombre gin_trgm_ops);
-- SKU único por tienda (multi-tenancy)
CREATE UNIQUE INDEX productos_tienda_sku_unique ON productos(tienda_id, codigo_sku);

INSERT INTO productos (categoria_id, unidad_id, impuesto_id, margen_id, moneda_precio, codigo_sku, nombre, costo_promedio, margen_pct, atributos) VALUES
(
    (SELECT id FROM categorias_productos WHERE slug='farmacia-antibioticos'),
    (SELECT id FROM unidades WHERE abreviatura='und'),
    (SELECT id FROM impuestos WHERE nombre='IVA 16%'),
    (SELECT id FROM margenes_ganancia WHERE nombre='Margen farmacia 35%'),
    'USD', 'FARM-001', 'Amoxicilina 500mg',
    2.8000, 35.0000,
    '{"concentracion":"500mg","laboratorio":"Genérico","requiere_receta":false}'
),
(
    (SELECT id FROM categorias_productos WHERE slug='ropa-caballero'),
    (SELECT id FROM unidades WHERE abreviatura='und'),
    (SELECT id FROM impuestos WHERE nombre='IVA 16%'),
    (SELECT id FROM margenes_ganancia WHERE nombre='Margen ropa 40%'),
    'USD', 'ROPA-001', 'Camisa casual manga corta',
    11.5000, 40.0000,
    '{"genero":"masculino"}'
),
(
    (SELECT id FROM categorias_productos WHERE slug='ferreteria-tornilleria'),
    (SELECT id FROM unidades WHERE abreviatura='und'),
    (SELECT id FROM impuestos WHERE nombre='IVA 16%'),
    (SELECT id FROM margenes_ganancia WHERE nombre='Margen estándar 20%'),
    'USD', 'FERR-001', 'Tornillo 3/8" acero inoxidable',
    0.1400, 20.0000,
    '{"calibre_mm":9.525,"material":"acero"}'
),
(
    (SELECT id FROM categorias_productos WHERE slug='licoreria-ron'),
    (SELECT id FROM unidades WHERE abreviatura='und'),
    (SELECT id FROM impuestos WHERE nombre='IVA 16%'),
    (SELECT id FROM margenes_ganancia WHERE nombre='Margen licorería 18%'),
    'USD', 'LIC-001', 'Ron Santa Teresa 1796',
    17.5000, 18.0000,
    '{"grado_alcoholico":40,"tipo_bebida":"ron","volumen_ml":750}'
),
(
    (SELECT id FROM categorias_productos WHERE slug='bodega-granos'),
    (SELECT id FROM unidades WHERE abreviatura='und'),
    (SELECT id FROM impuestos WHERE nombre='IVA 16%'),
    (SELECT id FROM margenes_ganancia WHERE nombre='Margen estándar 20%'),
    'USD', 'BOD-001', 'Harina P.A.N. 1kg',
    0.9500, 20.0000,
    '{"marca":"P.A.N.","peso_kg":1}'
);

-- Variantes: presentaciones, lotes, tallas. El stock se lleva a este nivel.
-- costo_variante / precio_venta NULL -> hereda del producto padre (en moneda_precio del padre)
CREATE TABLE variantes_producto (
    id             BIGSERIAL     PRIMARY KEY,
    producto_id    BIGINT        NOT NULL REFERENCES productos(id),
    codigo_barra   VARCHAR(60),
    descripcion    VARCHAR(200),
    costo_variante NUMERIC(14,6),
    -- NULL = hereda costo_promedio del producto (en misma moneda_precio)
    precio_venta   NUMERIC(14,6),
    -- NULL = hereda precio_base del producto (en misma moneda_precio)
    factor_unidad  NUMERIC(10,4) NOT NULL DEFAULT 1.0000,
    peso_kg        NUMERIC(8,4),
    atributos      JSONB         NOT NULL DEFAULT '{}',
    activo         BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en      TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_variantes_producto ON variantes_producto(producto_id);
CREATE INDEX idx_variantes_barra    ON variantes_producto(codigo_barra);
-- Código de barra único por tienda (multi-tenancy)
CREATE UNIQUE INDEX variantes_tienda_barra_unique ON variantes_producto(tienda_id, codigo_barra) WHERE codigo_barra IS NOT NULL;

INSERT INTO variantes_producto (producto_id, codigo_barra, descripcion, factor_unidad, atributos) VALUES
    (1, '7591234560011', 'Amoxicilina - Caja x24',        24,  '{"lote":"L2025A","fecha_venc":"2026-06"}'),
    (1, '7591234560028', 'Amoxicilina - Blister x10',     10,  '{"lote":"L2025A","fecha_venc":"2026-06"}'),
    (2, '7595000100011', 'Camisa - Talla S / Azul',        1,  '{"talla":"S","color":"#1E3A5F"}'),
    (2, '7595000100028', 'Camisa - Talla M / Azul',        1,  '{"talla":"M","color":"#1E3A5F"}'),
    (2, '7595000100035', 'Camisa - Talla L / Azul',        1,  '{"talla":"L","color":"#1E3A5F"}'),
    (3, '7590000200011', 'Tornillo 3/8" - Unidad',         1,  '{}'),
    (3, '7590000200028', 'Tornillo 3/8" - Bolsa x100',   100,  '{"presentacion":"bolsa"}'),
    (4, '7591000300011', 'Ron Santa Teresa - 750ml',        1,  '{"lote":"ST2024","fecha_venc":"2028-12"}'),
    (5, '7593000400011', 'Harina P.A.N. - 1kg',            1,  '{"lote":"HP2025"}');

-- =============================================================================
-- MÓDULO 3 - CLIENTES Y PROVEEDORES
-- =============================================================================

CREATE TABLE clientes (
    id               BIGSERIAL     PRIMARY KEY,
    lista_precio_id  BIGINT        REFERENCES listas_precio(id),
    moneda_credito   CHAR(3)       REFERENCES monedas(codigo),
    -- La moneda en que se lleva su cuenta de crédito. NULL = usa moneda_base de tienda.
    tipo_documento   VARCHAR(5)    NOT NULL DEFAULT 'V',
    numero_documento VARCHAR(20),
    nombre           VARCHAR(200)  NOT NULL,
    nombre_comercial VARCHAR(200),
    telefono         VARCHAR(20),
    telefono2        VARCHAR(20),
    email            VARCHAR(150),
    direccion        TEXT,
    fecha_nacimiento DATE,
    tipo_cliente     VARCHAR(20)   NOT NULL DEFAULT 'natural',
    -- natural | juridico | mayorista | empleado | vip
    limite_credito   NUMERIC(14,2) NOT NULL DEFAULT 0,
    dias_credito     SMALLINT      NOT NULL DEFAULT 0,
    notas            TEXT,
    activo           BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en        TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    actualizado_en   TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
COMMENT ON COLUMN clientes.moneda_credito IS 'Moneda de la cuenta corriente. NULL = hereda moneda_base de tienda.';

CREATE INDEX idx_clientes_documento ON clientes(tipo_documento, numero_documento);
CREATE INDEX idx_clientes_nombre    ON clientes USING GIN (nombre gin_trgm_ops);

INSERT INTO clientes (tipo_documento, numero_documento, nombre, tipo_cliente) VALUES
    ('V', '00000000', 'CONSUMIDOR FINAL', 'natural');
INSERT INTO clientes (lista_precio_id, moneda_credito, tipo_documento, numero_documento, nombre, telefono, limite_credito, dias_credito, tipo_cliente) VALUES
    (2, 'USD', 'J', '30456789-1', 'Distribuidora Los Andes C.A.', '0212-5551234', 2000.00, 30, 'mayorista'),
    (1, 'USD', 'V', '14552310',   'María González',               '0414-1234567',  500.00, 15, 'natural');

-- -----------------------------------------------------------------------------

CREATE TABLE proveedores (
    id               BIGSERIAL     PRIMARY KEY,
    tipo_documento   VARCHAR(5)    NOT NULL DEFAULT 'J',
    numero_documento VARCHAR(20),
    razon_social     VARCHAR(200)  NOT NULL,
    nombre_comercial VARCHAR(200),
    contacto         VARCHAR(100),
    telefono         VARCHAR(20),
    telefono2        VARCHAR(20),
    email            VARCHAR(150),
    direccion        TEXT,
    pais             VARCHAR(60)   NOT NULL DEFAULT 'Venezuela',
    moneda_compra    CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    -- SIN DEFAULT: definir explícitamente la moneda de facturación
    dias_entrega     SMALLINT      NOT NULL DEFAULT 1,
    credito_dias     SMALLINT      NOT NULL DEFAULT 0,
    limite_credito   NUMERIC(14,2) NOT NULL DEFAULT 0,
    notas            TEXT,
    activo           BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en        TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    actualizado_en   TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

INSERT INTO proveedores (razon_social, contacto, telefono, moneda_compra, dias_entrega, credito_dias) VALUES
    ('Distribuidora Farma C.A.',  'Carlos Méndez', '0212-5551234', 'USD', 3, 30),
    ('Textiles del Sur S.A.',     'Ana Torres',    '0241-6667788', 'USD', 5, 15),
    ('Materiales Henríquez C.A.', 'José Henríquez','0261-4443322', 'USD', 2,  0),
    ('Licores Premium C.A.',      'Pedro Ramos',   '0212-3334455', 'USD', 4, 30);

CREATE TABLE producto_proveedor (
    id                   BIGSERIAL     PRIMARY KEY,
    producto_id          BIGINT        NOT NULL REFERENCES productos(id),
    proveedor_id         BIGINT        NOT NULL REFERENCES proveedores(id),
    referencia_proveedor VARCHAR(80),
    costo_referencial    NUMERIC(14,6),
    moneda               CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    plazo_entrega        SMALLINT,
    es_preferido         BOOLEAN       NOT NULL DEFAULT FALSE,
    activo               BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en            TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    UNIQUE (producto_id, proveedor_id)
);
CREATE UNIQUE INDEX idx_pp_preferido ON producto_proveedor(producto_id) WHERE es_preferido = TRUE;

INSERT INTO producto_proveedor (producto_id, proveedor_id, referencia_proveedor, costo_referencial, moneda, es_preferido) VALUES
    (1, 1, 'FARM-AMOX-500',  2.80, 'USD', TRUE),
    (2, 2, 'TEXT-CAM-001',  11.50, 'USD', TRUE),
    (3, 3, 'HEN-TORN-3/8',   0.14, 'USD', TRUE),
    (4, 4, 'LIC-ST1796',    17.50, 'USD', TRUE),
    (5, 1, 'FARM-HPAN-1K',   0.92, 'USD', TRUE);

-- =============================================================================
-- MÓDULO 4 - INVENTARIO
-- =============================================================================

CREATE TABLE almacenes (
    id          BIGSERIAL    PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    tipo        VARCHAR(30)  NOT NULL DEFAULT 'deposito',
    -- deposito | exhibicion | consignacion | virtual
    direccion   TEXT,
    responsable VARCHAR(100),
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE almacenes IS 'Almacenes de la tienda. Sin sucursal_id. Stock en inventario.';

INSERT INTO almacenes (nombre, tipo) VALUES
    ('Depósito principal',   'deposito'),
    ('Mostrador / Exhibición','exhibicion');

-- Stock en tiempo real por variante y almacén.
-- costo_promedio acá: en la misma moneda_precio del producto padre.
CREATE TABLE inventario (
    id                   BIGSERIAL     PRIMARY KEY,
    variante_id          BIGINT        NOT NULL REFERENCES variantes_producto(id),
    almacen_id           BIGINT        NOT NULL REFERENCES almacenes(id),
    cantidad_disponible  NUMERIC(14,4) NOT NULL DEFAULT 0,
    cantidad_reservada   NUMERIC(14,4) NOT NULL DEFAULT 0,
    cantidad_en_transito NUMERIC(14,4) NOT NULL DEFAULT 0,
    stock_minimo         NUMERIC(14,4) NOT NULL DEFAULT 5,
    stock_maximo         NUMERIC(14,4),
    costo_promedio       NUMERIC(14,6) NOT NULL DEFAULT 0,
    -- en moneda_precio del producto padre
    ultima_entrada       TIMESTAMPTZ,
    ultima_salida        TIMESTAMPTZ,
    actualizado_en       TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    UNIQUE (variante_id, almacen_id)
);
CREATE INDEX idx_inventario_variante ON inventario(variante_id);
CREATE INDEX idx_inventario_almacen  ON inventario(almacen_id);

-- Bitácora inmutable particionada por año
CREATE TABLE movimientos_inventario (
    id              BIGSERIAL     NOT NULL,
    variante_id     BIGINT        NOT NULL REFERENCES variantes_producto(id),
    almacen_id      BIGINT        NOT NULL REFERENCES almacenes(id),
    user_id         BIGINT        NOT NULL,
    tipo            VARCHAR(40)   NOT NULL,
    -- entrada_compra|salida_venta|ajuste_positivo|ajuste_negativo
    -- traslado_salida|traslado_entrada|devolucion_cliente|devolucion_proveedor
    cantidad        NUMERIC(14,4) NOT NULL,
    stock_anterior  NUMERIC(14,4) NOT NULL DEFAULT 0,
    stock_nuevo     NUMERIC(14,4) NOT NULL DEFAULT 0,
    costo_unitario  NUMERIC(14,6),
    -- en moneda_precio del producto
    moneda_costo    CHAR(3)       REFERENCES monedas(codigo),
    referencia_tipo VARCHAR(40)   CHECK (referencia_tipo IS NULL OR referencia_tipo IN (
                        'ventas','recepciones_compra','ajustes_inventario',
                        'traslados_stock','devoluciones_venta')),
    referencia_id   BIGINT,
    notas           TEXT,
    creado_en       TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    PRIMARY KEY (id, creado_en)
) PARTITION BY RANGE (creado_en);

CREATE TABLE movimientos_inventario_2025 PARTITION OF movimientos_inventario FOR VALUES FROM ('2025-01-01') TO ('2026-01-01');
CREATE TABLE movimientos_inventario_2026 PARTITION OF movimientos_inventario FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');
CREATE TABLE movimientos_inventario_2027 PARTITION OF movimientos_inventario FOR VALUES FROM ('2027-01-01') TO ('2028-01-01');
CREATE TABLE movimientos_inventario_2028 PARTITION OF movimientos_inventario FOR VALUES FROM ('2028-01-01') TO ('2029-01-01');

CREATE INDEX idx_movinv_variante   ON movimientos_inventario(variante_id);
CREATE INDEX idx_movinv_almacen    ON movimientos_inventario(almacen_id);
CREATE INDEX idx_movinv_referencia ON movimientos_inventario(referencia_tipo, referencia_id);
CREATE INDEX idx_movinv_tipo       ON movimientos_inventario(tipo);

CREATE TABLE ajustes_inventario (
    id           BIGSERIAL    PRIMARY KEY,
    almacen_id   BIGINT       NOT NULL REFERENCES almacenes(id),
    user_id      BIGINT       NOT NULL,
    aprobado_por BIGINT,
    numero       VARCHAR(20)  NOT NULL UNIQUE,
    motivo       VARCHAR(100) NOT NULL,
    -- conteo_fisico|merma|caducidad|robo|daño|donacion|correccion
    estado       VARCHAR(20)  NOT NULL DEFAULT 'borrador',
    -- borrador|en_revision|aprobado|rechazado
    notas        TEXT,
    aprobado_en  TIMESTAMPTZ,
    creado_en    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE items_ajuste (
    id               BIGSERIAL     PRIMARY KEY,
    ajuste_id        BIGINT        NOT NULL REFERENCES ajustes_inventario(id),
    variante_id      BIGINT        NOT NULL REFERENCES variantes_producto(id),
    cantidad_sistema NUMERIC(14,4) NOT NULL,
    cantidad_fisica  NUMERIC(14,4) NOT NULL,
    diferencia       NUMERIC(14,4) GENERATED ALWAYS AS (cantidad_fisica - cantidad_sistema) STORED,
    costo_unitario   NUMERIC(14,6),
    notas            TEXT
);

CREATE TABLE traslados_stock (
    id                 BIGSERIAL    PRIMARY KEY,
    almacen_origen_id  BIGINT       NOT NULL REFERENCES almacenes(id),
    almacen_destino_id BIGINT       NOT NULL REFERENCES almacenes(id),
    user_id            BIGINT       NOT NULL,
    recibido_por       BIGINT,
    numero             VARCHAR(20)  NOT NULL UNIQUE,
    estado             VARCHAR(20)  NOT NULL DEFAULT 'borrador',
    -- borrador|aprobado|en_transito|recibido|cancelado
    notas              TEXT,
    enviado_en         TIMESTAMPTZ,
    recibido_en        TIMESTAMPTZ,
    creado_en          TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CHECK (almacen_origen_id <> almacen_destino_id)
);

CREATE TABLE items_traslado (
    id               BIGSERIAL     PRIMARY KEY,
    traslado_id      BIGINT        NOT NULL REFERENCES traslados_stock(id),
    variante_id      BIGINT        NOT NULL REFERENCES variantes_producto(id),
    cantidad_enviada NUMERIC(14,4) NOT NULL,
    cantidad_recibida NUMERIC(14,4),
    costo_unitario   NUMERIC(14,6),
    notas            TEXT
);

-- =============================================================================
-- MÓDULO 5 - COMPRAS
-- =============================================================================

CREATE TABLE ordenes_compra (
    id               BIGSERIAL     PRIMARY KEY,
    proveedor_id     BIGINT        NOT NULL REFERENCES proveedores(id),
    almacen_id       BIGINT        NOT NULL REFERENCES almacenes(id),
    user_id          BIGINT        NOT NULL,
    aprobado_por     BIGINT,
    numero           VARCHAR(20)   NOT NULL UNIQUE,
    moneda           CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    subtotal         NUMERIC(16,4) NOT NULL DEFAULT 0,
    impuesto         NUMERIC(16,4) NOT NULL DEFAULT 0,
    total            NUMERIC(16,4) NOT NULL DEFAULT 0,
    tasa_base        NUMERIC(20,8),
    total_en_base    NUMERIC(16,4),
    estado           VARCHAR(20)   NOT NULL DEFAULT 'borrador',
    -- borrador|aprobada|enviada|parcial|recibida|cancelada
    fecha_estimada   DATE,
    notas            TEXT,
    aprobado_en      TIMESTAMPTZ,
    creado_en        TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    actualizado_en   TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

CREATE TABLE items_orden_compra (
    id              BIGSERIAL     PRIMARY KEY,
    orden_id        BIGINT        NOT NULL REFERENCES ordenes_compra(id),
    producto_id     BIGINT        NOT NULL REFERENCES productos(id),
    variante_id     BIGINT        REFERENCES variantes_producto(id),
    cantidad        NUMERIC(14,4) NOT NULL,
    cantidad_recibida NUMERIC(14,4) NOT NULL DEFAULT 0,
    costo_unitario  NUMERIC(14,6) NOT NULL,
    impuesto_pct    NUMERIC(6,4)  NOT NULL DEFAULT 0,
    total_linea     NUMERIC(16,4) NOT NULL,
    notas           TEXT
);

CREATE TABLE recepciones_compra (
    id            BIGSERIAL    PRIMARY KEY,
    orden_id      BIGINT       NOT NULL REFERENCES ordenes_compra(id),
    almacen_id    BIGINT       NOT NULL REFERENCES almacenes(id),
    user_id       BIGINT       NOT NULL,
    numero        VARCHAR(20)  NOT NULL UNIQUE,
    estado        VARCHAR(20)  NOT NULL DEFAULT 'pendiente',
    -- pendiente|procesada|con_diferencia
    notas         TEXT,
    recibido_en   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE items_recepcion (
    id                BIGSERIAL     PRIMARY KEY,
    recepcion_id      BIGINT        NOT NULL REFERENCES recepciones_compra(id),
    item_orden_id     BIGINT        NOT NULL REFERENCES items_orden_compra(id),
    variante_id       BIGINT        NOT NULL REFERENCES variantes_producto(id),
    cantidad_esperada NUMERIC(14,4) NOT NULL,
    cantidad_recibida NUMERIC(14,4) NOT NULL,
    cantidad_rechazada NUMERIC(14,4) NOT NULL DEFAULT 0,
    costo_unitario    NUMERIC(14,6) NOT NULL,
    lote              VARCHAR(50),
    fecha_vencimiento DATE,
    notas             TEXT
);

CREATE TABLE facturas_proveedor (
    id               BIGSERIAL     PRIMARY KEY,
    orden_id         BIGINT        REFERENCES ordenes_compra(id),
    proveedor_id     BIGINT        NOT NULL REFERENCES proveedores(id),
    numero_factura   VARCHAR(50)   NOT NULL,
    moneda           CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    subtotal         NUMERIC(16,4) NOT NULL DEFAULT 0,
    impuesto         NUMERIC(16,4) NOT NULL DEFAULT 0,
    total            NUMERIC(16,4) NOT NULL DEFAULT 0,
    tasa_base        NUMERIC(20,8),
    total_en_base    NUMERIC(16,4),
    fecha_factura    DATE          NOT NULL DEFAULT CURRENT_DATE,
    fecha_vence      DATE,
    estado           VARCHAR(20)   NOT NULL DEFAULT 'pendiente',
    -- pendiente|parcial|pagada|vencida|anulada
    notas            TEXT,
    creado_en        TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    UNIQUE (proveedor_id, numero_factura)
);

-- =============================================================================
-- MÓDULO 6 - CAJA Y TESORERÍA  (MULTIMONEDA)
-- =============================================================================
-- DISEÑO:
--   metodos_pago       -> cada método tiene SU moneda
--   cajas              -> puntos físicos de cobro
--   sesiones_caja      -> turno del cajero; totales en moneda_base (resumen)
--   sesion_caja_monedas-> desglose por moneda: apertura, ventas, retiros, cierre
--   movimientos_caja   -> retiros / gastos / ingresos con snapshot de moneda y tasa
-- =============================================================================

-- *** MODIFICACIÓN 1 (parcial): grava_igtf en metodos_pago ***
CREATE TABLE metodos_pago (
    id                  BIGSERIAL    PRIMARY KEY,
    nombre              VARCHAR(60)  NOT NULL,
    tipo                VARCHAR(30)  NOT NULL,
    -- efectivo|transferencia|tarjeta_debito|tarjeta_credito
    -- pago_movil|zelle|criptomoneda|cheque|nota_credito
    moneda              CHAR(3)      NOT NULL REFERENCES monedas(codigo),
    -- La moneda de operación de ESTE método. Sin DEFAULT.
    requiere_referencia BOOLEAN      NOT NULL DEFAULT FALSE,
    requiere_banco      BOOLEAN      NOT NULL DEFAULT FALSE,
    grava_igtf          BOOLEAN      NOT NULL DEFAULT FALSE,
    -- v2.1: TRUE si este método de pago está sujeto al IGTF (3%).
    -- Según normativa VE vigente (2024-2025):
    --   TRUE  -> Efectivo USD/EUR/COP, Zelle, criptomonedas (USDT, BTC)
    --   FALSE -> Efectivo VES, transferencia VES, pago móvil VES,
    --           tarjeta débito/crédito VES, cheque VES
    -- El admin puede cambiar esto desde el panel si la normativa cambia.
    activo              BOOLEAN      NOT NULL DEFAULT TRUE
);
COMMENT ON TABLE metodos_pago IS 'Cada método tiene su moneda. grava_igtf=TRUE -> el POS calcula el 3% IGTF automáticamente al usar este método.';
COMMENT ON COLUMN metodos_pago.grava_igtf IS 'TRUE = este medio de pago está gravado con IGTF (divisas/cripto). El admin lo configura según normativa SENIAT vigente.';

INSERT INTO metodos_pago (nombre, tipo, moneda, requiere_referencia, requiere_banco, grava_igtf) VALUES
    ('Efectivo USD',           'efectivo',        'USD', FALSE, FALSE, TRUE),
    ('Efectivo VES',           'efectivo',        'VES', FALSE, FALSE, FALSE),
    ('Efectivo COP',           'efectivo',        'COP', FALSE, FALSE, TRUE),
    ('Transferencia bancaria', 'transferencia',   'VES', TRUE,  TRUE,  FALSE),
    ('Pago móvil',             'pago_movil',      'VES', TRUE,  TRUE,  FALSE),
    ('Tarjeta débito',         'tarjeta_debito',  'VES', TRUE,  FALSE, FALSE),
    ('Tarjeta crédito',        'tarjeta_credito', 'VES', TRUE,  FALSE, FALSE),
    ('Zelle',                  'zelle',           'USD', TRUE,  FALSE, TRUE),
    ('USDT / Binance',         'criptomoneda',    'UST', TRUE,  FALSE, TRUE),
    ('Cheque',                 'cheque',          'VES', TRUE,  TRUE,  FALSE),
    ('Nota de crédito',        'nota_credito',    'USD', TRUE,  FALSE, FALSE);
    -- Nota de crédito: no grava IGTF porque no es un pago en divisas,
    -- es una compensación interna.

-- -----------------------------------------------------------------------------

CREATE TABLE cajas (
    id              BIGSERIAL    PRIMARY KEY,
    nombre          VARCHAR(80)  NOT NULL,
    descripcion     VARCHAR(150),
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE cajas IS 'Puntos físicos de cobro. El límite de efectivo es por moneda (en sesion_caja_monedas).';

INSERT INTO cajas (nombre) VALUES ('Caja 1 - Principal'), ('Caja 2 - Secundaria');

-- -----------------------------------------------------------------------------
-- sesiones_caja: turno del cajero
-- Los campos *_base guardan totales en tienda.moneda_base para el dashboard.
-- El detalle por moneda está en sesion_caja_monedas.
-- -----------------------------------------------------------------------------
CREATE TABLE sesiones_caja (
    id                     BIGSERIAL     PRIMARY KEY,
    caja_id                BIGINT        NOT NULL REFERENCES cajas(id),
    user_id                BIGINT        NOT NULL,
    estado                 VARCHAR(20)   NOT NULL DEFAULT 'abierta',
    -- abierta | cerrada | con_diferencia
    observaciones          TEXT,
    apertura_en            TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    cierre_en              TIMESTAMPTZ,
    -- -> Totales en moneda_base (se llenan al cerrar; para reportes rápidos) ->
    total_ventas_base       NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_devoluciones_base NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_retiros_base      NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_ingresos_base     NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_gastos_base       NUMERIC(18,4) NOT NULL DEFAULT 0,
    diferencia_base         NUMERIC(18,4)
    -- diferencia_base: calculada al cerrar; negativo=faltante, positivo=sobrante
);
CREATE UNIQUE INDEX idx_sesion_abierta ON sesiones_caja(caja_id) WHERE estado = 'abierta';
COMMENT ON TABLE sesiones_caja IS 'Turno de cajero. 1 abierta por caja (índice único parcial). Detalle multimoneda en sesion_caja_monedas.';

-- -----------------------------------------------------------------------------
-- sesion_caja_monedas - CORAZÓN DEL CIERRE MULTIMONEDA
--
-- Por cada moneda que maneje la sesión:
--   monto_apertura       -> cuánto declaró el cajero al abrir (fondo de caja)
--   total_ventas         -> suma de pagos_venta en esta moneda durante la sesión
--   total_devoluciones   -> reintegros en esta moneda
--   total_retiros        -> retiros / gastos en esta moneda
--   total_ingresos       -> abonos de crédito cobrados en esta moneda
--   monto_calculado (GEN)-> lo que DEBERÍA haber en caja
--   monto_declarado      -> lo que el cajero cuenta físicamente al cerrar
--   diferencia (GEN)     -> declarado - calculado (neg=faltante, pos=sobrante)
--
-- Al cierre se guarda también la tasa vigente para convertir a base.
-- -----------------------------------------------------------------------------
CREATE TABLE sesion_caja_monedas (
    id                      BIGSERIAL     PRIMARY KEY,
    sesion_id               BIGINT        NOT NULL REFERENCES sesiones_caja(id),
    moneda                  CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    limite_efectivo         NUMERIC(18,4) NOT NULL DEFAULT 0,
    -- alerta si efectivo supera este monto en esta moneda
    monto_apertura          NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_ventas            NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_devoluciones      NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_retiros           NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_ingresos          NUMERIC(18,4) NOT NULL DEFAULT 0,
    total_gastos            NUMERIC(18,4) NOT NULL DEFAULT 0,
    monto_calculado         NUMERIC(18,4) GENERATED ALWAYS AS
                            ( monto_apertura
                              + total_ventas
                              + total_ingresos
                              - total_devoluciones
                              - total_retiros
                              - total_gastos ) STORED,
    monto_declarado         NUMERIC(18,4),
    -- lo que el cajero cuenta al cerrar; NULL = sesión aún abierta
    diferencia              NUMERIC(18,4) GENERATED ALWAYS AS
                            ( COALESCE(monto_declarado, 0) -
                              ( monto_apertura
                                + total_ventas
                                + total_ingresos
                                - total_devoluciones
                                - total_retiros
                                - total_gastos )
                            ) STORED,
    -- -> Conversión a moneda_base al cierre ->
    tasa_al_cierre          NUMERIC(20,8),
    monto_calculado_en_base NUMERIC(18,4),
    monto_declarado_en_base NUMERIC(18,4),
    diferencia_en_base      NUMERIC(18,4),
    UNIQUE (sesion_id, moneda)
);
COMMENT ON TABLE sesion_caja_monedas IS 'Desglose por moneda de cada sesión. monto_calculado y diferencia son GENERADAS.';

-- -----------------------------------------------------------------------------

CREATE TABLE movimientos_caja (
    id              BIGSERIAL     PRIMARY KEY,
    sesion_id       BIGINT        NOT NULL REFERENCES sesiones_caja(id),
    user_id         BIGINT        NOT NULL,
    tipo            VARCHAR(20)   NOT NULL,
    -- retiro | ingreso | gasto | fondo
    moneda          CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    monto           NUMERIC(18,4) NOT NULL,
    tasa_base       NUMERIC(20,8),
    monto_en_base   NUMERIC(18,4),
    concepto        VARCHAR(150)  NOT NULL,
    referencia      VARCHAR(100),
    creado_en       TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- =============================================================================
-- MÓDULO 7 - VENTAS (* MODIFICACIONES v2.1 *)
-- =============================================================================

CREATE TABLE descuentos (
    id              BIGSERIAL     PRIMARY KEY,
    nombre          VARCHAR(100)  NOT NULL,
    tipo_aplicacion VARCHAR(20)   NOT NULL DEFAULT 'producto',
    -- producto | categoria | cliente | global
    producto_id     BIGINT        REFERENCES productos(id),
    categoria_id    BIGINT        REFERENCES categorias_productos(id),
    cliente_id      BIGINT        REFERENCES clientes(id),
    valor_pct       NUMERIC(6,4)  NOT NULL,
    maximo_pct      NUMERIC(6,4),
    fecha_inicio    DATE,
    fecha_fin       DATE,
    activo          BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- *** CABECERA DE VENTA - v2.1 con IGTF separado + tipo_documento + estado limpio ***
CREATE TABLE ventas (
    id               BIGSERIAL     PRIMARY KEY,
    cliente_id       BIGINT        NOT NULL REFERENCES clientes(id),
    caja_id          BIGINT        NOT NULL REFERENCES cajas(id),
    sesion_caja_id   BIGINT        REFERENCES sesiones_caja(id),
    almacen_id       BIGINT        NOT NULL REFERENCES almacenes(id),
    user_id          BIGINT        NOT NULL,
    numero_factura   VARCHAR(30)   NOT NULL UNIQUE,

    -- * MODIFICACIÓN 2: tipo_documento + tipo_pago + estado limpio *
    tipo_documento   VARCHAR(5)    NOT NULL DEFAULT 'FAC'
        CHECK (tipo_documento IN ('FAC','NE','NC','ND','COT')),
    -- FAC = Factura fiscal (documento principal del SENIAT)
    -- NE  = Nota de Entrega (despacho sin cobro inmediato)
    -- NC  = Nota de Crédito (devolución / anulación parcial)
    -- ND  = Nota de Débito (cargo adicional / recargo)
    -- COT = Cotización (presupuesto sin compromiso fiscal)

    tipo_pago        VARCHAR(15)   NOT NULL DEFAULT 'contado'
        CHECK (tipo_pago IN ('contado','credito')),
    -- contado = el cliente paga al momento (uno o varios pagos)
    -- credito = se genera factura_credito y se cobra después

    moneda_factura   CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    -- Moneda en que está denominada la factura (sin DEFAULT)
    fuente_tasa      VARCHAR(30),
    -- BCV | paralelo | api_automatica | manual (qué tasa se usó)
    subtotal         NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- en moneda_factura: SUM de (precio_en_factura × cantidad × (1 - descuento_pct/100))
    descuento        NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- en moneda_factura: descuento global (adicional al de líneas)

    -- * MODIFICACIÓN 1: IVA e IGTF separados *
    impuesto_iva     NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- IVA total = SUM(items_venta.impuesto_monto) de esta venta.
    -- Es el impuesto sobre la MERCANCÍA. Se suma al total.
    impuesto_igtf    NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- IGTF total = SUM(pagos_venta.monto_igtf convertido a moneda_factura).
    -- Es el impuesto sobre el MEDIO DE PAGO. Se muestra en línea SEPARADA.
    -- Se DEBE sumar al gran total a pagar por el cliente.

    total            NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- en moneda_factura: subtotal - descuento + impuesto_iva + impuesto_igtf
    -- ¡SÍ incluye IGTF! Para cuadrar con los pagos recibidos (pagos_venta).

    tasa_base_usada  NUMERIC(20,8),
    -- snapshot: cuántas moneda_base = 1 moneda_factura al emitir
    total_en_base    NUMERIC(16,4),
    -- total × tasa_base_usada -> en moneda_base (inmutable)

    -- * MODIFICACIÓN 2 (continuación): estado limpio *
    estado           VARCHAR(20)   NOT NULL DEFAULT 'borrador'
        CHECK (estado IN ('borrador','pendiente','pagada','parcial','anulada')),
    -- FLUJO DE ESTADOS:
    --   borrador  -> el cajero está armando la venta (puede editar)
    --   pendiente -> emitida pero aún no cobrada (ej: NE, COT, crédito por confirmar)
    --   pagada    -> cobro completo registrado
    --   parcial   -> pago parcial recibido (crédito con abono)
    --   anulada   -> venta cancelada (no eliminar, solo marcar)
    --
    -- FLUJO TÍPICO COT->FAC:
    --   1) Se crea como tipo_documento='COT', estado='pendiente'
    --   2) Cliente aprueba -> UPDATE tipo_documento='FAC', estado='borrador'
    --   3) Se cobra -> estado='pagada'
    --   NOTA: Una COT nunca puede estar en estado 'pagada' directamente.
    --   Para cobrar, primero debe mutar a FAC.

    notas            TEXT,
    creado_en        TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    actualizado_en   TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  ventas IS 'v2.1: tipo_documento (SENIAT) + tipo_pago (contado/credito) + estado operativo limpio. impuesto_iva/impuesto_igtf separados. total SI incluye IGTF.';
COMMENT ON COLUMN ventas.tipo_documento IS 'Tipo de documento fiscal SENIAT: FAC=Factura, NE=Nota Entrega, NC=Nota Crédito, ND=Nota Débito, COT=Cotización.';
COMMENT ON COLUMN ventas.tipo_pago IS 'Forma de pago: contado (cobra ya) o credito (genera cuenta por cobrar). Independiente del tipo_documento.';
COMMENT ON COLUMN ventas.moneda_factura IS 'La moneda en que se emite la factura. El cliente puede pagar en otras monedas (ver pagos_venta).';
COMMENT ON COLUMN ventas.impuesto_iva IS 'IVA total de esta factura = SUM(items_venta.impuesto_monto). Se suma al total.';
COMMENT ON COLUMN ventas.impuesto_igtf IS 'IGTF total = SUM(pagos_venta.monto_igtf convertido a moneda_factura). Se muestra aparte en la factura pero SE SUMA al total.';
COMMENT ON COLUMN ventas.total IS 'subtotal - descuento + impuesto_iva + impuesto_igtf. ¡SÍ incluye IGTF! Para cuadrar caja perfectamente con pagos_venta.';
COMMENT ON COLUMN ventas.total_en_base  IS 'Snapshot al emitir. No cambia aunque las tasas cambien después.';
COMMENT ON COLUMN ventas.estado IS 'borrador->pendiente->pagada|parcial|anulada. Una COT en "pendiente" muta a FAC para poder pasar a "pagada".';

CREATE INDEX idx_ventas_cliente    ON ventas(cliente_id);
CREATE INDEX idx_ventas_caja       ON ventas(caja_id);
CREATE INDEX idx_ventas_fecha      ON ventas(creado_en);
CREATE INDEX idx_ventas_numero     ON ventas(numero_factura);
CREATE INDEX idx_ventas_estado     ON ventas(estado);
CREATE INDEX idx_ventas_moneda     ON ventas(moneda_factura);
CREATE INDEX idx_ventas_tipo_doc   ON ventas(tipo_documento);

-- -----------------------------------------------------------------------------
-- *** items_venta - TRIPLE SNAPSHOT MULTIMONEDA + impuesto_monto (v2.1) ***
--   moneda_precio    -> la moneda del producto
--   precio_unitario  -> precio en moneda_precio (snapshot)
--   costo_unitario   -> costo en moneda_precio (snapshot)
--   tasa_conversion  -> snapshot de moneda_precio -> moneda_factura
--   precio_en_factura-> precio_unitario × tasa_conversion (en moneda_factura)
--   costo_en_factura -> costo_unitario × tasa_conversion (en moneda_factura)
--   impuesto_monto   -> monto absoluto del IVA de esta línea (v2.1)
--   ganancia_linea   -> GENERADA: ambos en moneda_factura -> cálculo correcto
-- -----------------------------------------------------------------------------
CREATE TABLE items_venta (
    id                BIGSERIAL     PRIMARY KEY,
    venta_id          BIGINT        NOT NULL REFERENCES ventas(id),
    variante_id       BIGINT        NOT NULL REFERENCES variantes_producto(id),
    cantidad          NUMERIC(14,4) NOT NULL,
    moneda_precio     CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    -- moneda del producto al momento de vender
    precio_unitario   NUMERIC(14,6) NOT NULL,
    -- snapshot en moneda_precio
    costo_unitario    NUMERIC(14,6) NOT NULL,
    -- snapshot en moneda_precio
    tasa_conversion   NUMERIC(20,8) NOT NULL DEFAULT 1.0,
    -- moneda_precio -> moneda_factura; 1.0 si son la misma
    precio_en_factura NUMERIC(14,6) NOT NULL,
    -- precio_unitario × tasa_conversion (en moneda_factura)
    costo_en_factura  NUMERIC(14,6) NOT NULL,
    -- costo_unitario × tasa_conversion (en moneda_factura)
    margen_aplicado   NUMERIC(7,4)  NOT NULL DEFAULT 0,
    descuento_pct     NUMERIC(6,4)  NOT NULL DEFAULT 0,
    impuesto_pct      NUMERIC(6,4)  NOT NULL DEFAULT 0,

    -- * MODIFICACIÓN 3: impuesto_monto absoluto *
    impuesto_monto    NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- Monto absoluto del IVA calculado para esta línea, en moneda_factura.
    -- Fórmula: ROUND(precio_en_factura × cantidad × (1 - descuento_pct/100) × impuesto_pct/100, 4)
    -- Se guarda precalculado para EVITAR el problema de descuadre por redondeo:
    --   SUM(impuesto_monto) de todas las líneas = ventas.impuesto_iva (exacto).
    -- Si solo se guardara impuesto_pct, la suma de redondeos por línea podría
    -- diferir en ±1 céntimo del total calculado en la cabecera.

    total_linea       NUMERIC(16,4) NOT NULL,
    -- en moneda_factura: (precio_en_factura × cantidad × (1 - desc/100)) + impuesto_monto
    ganancia_linea    NUMERIC(16,4) GENERATED ALWAYS AS
                      ( ROUND(
                          (precio_en_factura * (1.0 - descuento_pct / 100.0) - costo_en_factura)
                          * cantidad, 4
                        ) ) STORED
    -- GENERADA en moneda_factura. Usa precio/costo ya convertidos.
    -- Nota: la ganancia NO se ve afectada por impuesto_monto porque el
    -- impuesto no es ganancia; es un pasivo que se debe al fisco.
);
COMMENT ON TABLE  items_venta IS 'v2.1: Snapshot triple + impuesto_monto absoluto. SUM(impuesto_monto) = ventas.impuesto_iva.';
COMMENT ON COLUMN items_venta.tasa_conversion IS '1.0 cuando producto y factura están en la misma moneda.';
COMMENT ON COLUMN items_venta.impuesto_monto IS 'IVA absoluto de la línea en moneda_factura. Evita descuadre de céntimos. SUM(impuesto_monto) = ventas.impuesto_iva.';
COMMENT ON COLUMN items_venta.ganancia_linea  IS 'GENERADA en moneda_factura. Correcto porque precio y costo están en la misma moneda. No incluye impuesto.';

CREATE INDEX idx_items_venta_venta    ON items_venta(venta_id);
CREATE INDEX idx_items_venta_variante ON items_venta(variante_id);

-- -----------------------------------------------------------------------------
-- *** pagos_venta - PAGO MIXTO MULTIMONEDA + IGTF (v2.1) ***
--   El cliente puede pagar con varios métodos y varias monedas en una sola venta.
--   Ejemplo: $5 USD efectivo + 18,000 VES pago móvil -> total = $10 USD (si tasa=3600)
--
--   monto_pago        -> cuánto el cliente entregó en SU moneda
--   moneda_pago       -> la moneda que el cliente usó
--   tasa_aplicada     -> snapshot moneda_pago -> moneda_factura al momento del cobro
--   monto_en_factura  -> monto_pago / tasa_aplicada (o × si inversa)
--   monto_en_base     -> convertido a moneda_base de tienda
--
--   * v2.1: IGTF por pago *
--   monto_igtf        -> 3% sobre monto_pago si el método grava IGTF (en moneda_pago)
--   tasa_igtf_pct     -> snapshot de la alícuota usada (normalmente 3.0000)
-- -----------------------------------------------------------------------------
CREATE TABLE pagos_venta (
    id               BIGSERIAL     PRIMARY KEY,
    venta_id         BIGINT        NOT NULL REFERENCES ventas(id),
    metodo_pago_id   BIGINT        NOT NULL REFERENCES metodos_pago(id),
    sesion_caja_id   BIGINT        REFERENCES sesiones_caja(id),
    moneda_pago      CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    -- la moneda que el cliente realmente pagó
    monto_pago       NUMERIC(18,4) NOT NULL,
    -- cuánto entregó en moneda_pago (sin incluir IGTF)
    tasa_aplicada    NUMERIC(20,8),
    -- snapshot: 1 moneda_pago = N moneda_factura (NULL si es la misma moneda)
    monto_en_factura NUMERIC(18,4) NOT NULL,
    -- monto que abona a la factura (en moneda_factura)
    tasa_base        NUMERIC(20,8),
    -- snapshot moneda_pago -> moneda_base
    monto_en_base    NUMERIC(18,4),
    -- para cuadre de caja en moneda_base

    -- * MODIFICACIÓN 1: campos IGTF por pago *
    monto_igtf       NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- IGTF cobrado en ESTE pago, en moneda_pago.
    -- = monto_pago × (tasa_igtf_pct / 100) si metodos_pago.grava_igtf = TRUE
    -- = 0 si el método NO grava IGTF (ej: pago móvil VES, transferencia VES)
    -- EJEMPLO: Pago de $100 USD en efectivo -> monto_igtf = $3.00 USD
    tasa_igtf_pct    NUMERIC(6,4)  NOT NULL DEFAULT 0,
    -- Snapshot de la alícuota IGTF al momento del cobro.
    -- Normalmente 3.0000 (3%), pero se guarda como snapshot por si
    -- el gobierno cambia la alícuota en el futuro.
    -- Se toma de tienda.alicuota_igtf al momento de registrar el pago.
    -- Si el método no grava IGTF, se guarda 0.
    
    monto_igtf_en_factura NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- IGTF calculado y convertido explícitamente a moneda_factura
    -- para evitar descuadres cruzados (ej. factura VES, pago USD)

    referencia       VARCHAR(100),
    banco            VARCHAR(60),
    creado_en        TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE  pagos_venta IS 'v2.1: Pago mixto multimoneda + IGTF. monto_igtf se calcula solo si metodos_pago.grava_igtf=TRUE.';
COMMENT ON COLUMN pagos_venta.monto_en_factura IS 'Lo que abona a ventas.total. SUM de todos los monto_en_factura debe igualar ventas.total.';
COMMENT ON COLUMN pagos_venta.monto_igtf IS 'IGTF percibido en moneda_pago.';
COMMENT ON COLUMN pagos_venta.monto_igtf_en_factura IS 'IGTF convertido explícitamente a moneda_factura.';
COMMENT ON COLUMN pagos_venta.tasa_igtf_pct IS 'Snapshot de la alícuota IGTF (%). Viene de tienda.alicuota_igtf al cobrar. 0 si el método no grava.';

-- *** RELACIÓN IGTF: CÓMO CUADRA ***
-- ventas.impuesto_igtf = SUM(pagos_venta.monto_igtf_en_factura)
-- para todos los pagos de esa venta.
--
-- Es decir: cada pago tiene su monto_igtf en moneda_pago.
-- Para subirlo a la cabecera (ventas.impuesto_igtf, que está en moneda_factura),
-- se convierte usando la misma tasa_aplicada del pago.
--
-- EJEMPLO con pago mixto:
--   Factura en USD, total = $100.00
--   Pago 1: $50 USD efectivo -> monto_igtf = $1.50 USD, tasa_aplicada = NULL (misma moneda)
--   Pago 2: 180,000 VES pago móvil -> monto_igtf = 0 VES (no grava)
--   ventas.impuesto_igtf = 1.50 × 1 + 0 = $1.50 USD

-- -----------------------------------------------------------------------------

-- *** devoluciones_venta - v2.1: con número de nota de crédito fiscal ***
CREATE TABLE devoluciones_venta (
    id              BIGSERIAL     PRIMARY KEY,
    venta_id        BIGINT        NOT NULL REFERENCES ventas(id),

    -- * MODIFICACIÓN 4: número fiscal de nota de crédito *
    numero_nota_credito VARCHAR(30) UNIQUE,
    -- Número fiscal de la Nota de Crédito emitida por esta devolución.
    -- Formato sugerido: NC-000001 (usando prefijo de tienda + secuencia).
    -- UNIQUE porque cada devolución genera un documento fiscal independiente.
    -- Puede ser NULL si la devolución es un cambio de producto sin NC formal.

    user_id         BIGINT        NOT NULL,
    motivo          VARCHAR(100)  NOT NULL,
    descripcion     TEXT,
    moneda_devolucion CHAR(3)     NOT NULL REFERENCES monedas(codigo),
    -- en qué moneda se reintegra al cliente
    monto_devuelto  NUMERIC(16,4) NOT NULL,
    -- en moneda_devolucion
    tasa_usada      NUMERIC(20,8),
    monto_en_base   NUMERIC(16,4),
    tipo_reintegro  VARCHAR(20)   NOT NULL DEFAULT 'efectivo',
    -- efectivo | nota_credito | cambio_producto | abono_cuenta
    estado          VARCHAR(20)   NOT NULL DEFAULT 'procesada',
    -- procesada | anulada
    creado_en       TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE  devoluciones_venta IS 'v2.1: Incluye numero_nota_credito para trazabilidad fiscal SENIAT. Cada devolución genera un documento NC.';
COMMENT ON COLUMN devoluciones_venta.numero_nota_credito IS 'Número fiscal único de la Nota de Crédito. Formato: NC-000001. NULL si es cambio sin documento formal.';

-- *** items_devolucion - v2.1: con trazabilidad multimoneda ***
CREATE TABLE items_devolucion (
    id              BIGSERIAL     PRIMARY KEY,
    devolucion_id   BIGINT        NOT NULL REFERENCES devoluciones_venta(id),
    item_venta_id   BIGINT        NOT NULL REFERENCES items_venta(id),
    variante_id     BIGINT        NOT NULL REFERENCES variantes_producto(id),
    cantidad        NUMERIC(14,4) NOT NULL,
    precio_unitario NUMERIC(14,6) NOT NULL,
    -- snapshot del precio original (en moneda_precio del item)

    -- * MODIFICACIÓN 4: trazabilidad multimoneda *
    monto_devuelto_en_factura NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- Monto devuelto en la moneda de la FACTURA ORIGINAL.
    -- = cantidad × precio_unitario × tasa_conversion_original × (1 - descuento_pct/100)
    -- Sirve para descontar del total de la factura original.
    tasa_usada_devolucion     NUMERIC(20,8),
    -- Snapshot de la tasa moneda_factura -> moneda_base AL MOMENTO de la devolución.
    -- Puede diferir de la tasa original si pasó tiempo entre la venta y la devolución.
    -- Se usa para registrar el monto en base al tipo de cambio del día de la NC.
    monto_devuelto_en_base    NUMERIC(16,4) NOT NULL DEFAULT 0,
    -- = monto_devuelto_en_factura × tasa_usada_devolucion
    -- Inmutable. Para cuadre contable y reportes en moneda_base.

    motivo_item     VARCHAR(100)
);
COMMENT ON TABLE  items_devolucion IS 'v2.1: Cada ítem devuelto con trazabilidad multimoneda completa. monto_devuelto_en_base es inmutable para cuadre.';
COMMENT ON COLUMN items_devolucion.monto_devuelto_en_factura IS 'Monto en moneda_factura original. Para descontar de la factura de venta.';
COMMENT ON COLUMN items_devolucion.tasa_usada_devolucion IS 'Tasa al momento de la NC (puede diferir de la tasa de la venta original).';
COMMENT ON COLUMN items_devolucion.monto_devuelto_en_base IS 'Inmutable. Para cuadre contable en moneda_base de la tienda.';

-- =============================================================================
-- MÓDULO 8 - CRÉDITOS Y CUENTAS POR COBRAR  (MULTIMONEDA)
-- =============================================================================
-- El crédito de cada cliente está en UNA moneda (su moneda_credito).
-- Si el cliente paga en otra moneda, el abono se convierte a moneda_credito.
-- =============================================================================

CREATE TABLE cuentas_credito (
    id               BIGSERIAL     PRIMARY KEY,
    cliente_id       BIGINT        NOT NULL UNIQUE REFERENCES clientes(id),
    moneda           CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    -- la moneda en que se lleva la cuenta (viene de clientes.moneda_credito)
    limite           NUMERIC(14,2) NOT NULL DEFAULT 0,
    saldo_usado      NUMERIC(14,2) NOT NULL DEFAULT 0,
    -- actualizado por trigger en abonos_credito
    saldo_disponible NUMERIC(14,2) GENERATED ALWAYS AS (limite - saldo_usado) STORED,
    estado           VARCHAR(20)   NOT NULL DEFAULT 'activa',
    -- activa | suspendida | bloqueada | al_dia | vencida
    actualizado_en   TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE  cuentas_credito IS 'Cuenta en una moneda específica. saldo_disponible=limite-saldo_usado (generada).';
COMMENT ON COLUMN cuentas_credito.moneda IS 'Toda la cuenta se lleva en esta moneda. Los abonos en otra moneda se convierten.';

CREATE TABLE facturas_credito (
    id                BIGSERIAL     PRIMARY KEY,
    venta_id          BIGINT        NOT NULL REFERENCES ventas(id),
    cliente_id        BIGINT        NOT NULL REFERENCES clientes(id),
    cuenta_credito_id BIGINT        NOT NULL REFERENCES cuentas_credito(id),
    moneda            CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    -- = cuentas_credito.moneda del cliente
    monto_total       NUMERIC(14,2) NOT NULL,
    -- en moneda de la cuenta
    saldo_pendiente   NUMERIC(14,2) NOT NULL,
    dias_plazo        SMALLINT      NOT NULL DEFAULT 30,
    fecha_emision     DATE          NOT NULL DEFAULT CURRENT_DATE,
    fecha_vence       DATE          NOT NULL,
    estado            VARCHAR(20)   NOT NULL DEFAULT 'pendiente',
    -- pendiente | parcial | pagada | vencida | anulada
    creado_en         TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_factcred_cliente ON facturas_credito(cliente_id);
CREATE INDEX idx_factcred_vence   ON facturas_credito(fecha_vence);
CREATE INDEX idx_factcred_estado  ON facturas_credito(estado);

-- -----------------------------------------------------------------------------
-- abonos_credito - MULTIMONEDA
-- El cliente puede abonar en cualquier moneda; se convierte a moneda de la cuenta
-- -----------------------------------------------------------------------------
CREATE TABLE abonos_credito (
    id                    BIGSERIAL     PRIMARY KEY,
    factura_credito_id    BIGINT        NOT NULL REFERENCES facturas_credito(id),
    metodo_pago_id        BIGINT        NOT NULL REFERENCES metodos_pago(id),
    sesion_caja_id        BIGINT        REFERENCES sesiones_caja(id),
    user_id               BIGINT        NOT NULL,
    moneda_pago           CHAR(3)       NOT NULL REFERENCES monedas(codigo),
    -- moneda en que el cliente pagó
    monto_pago            NUMERIC(14,2) NOT NULL,
    -- cuánto entregó en moneda_pago
    tasa_usada            NUMERIC(20,8),
    -- snapshot moneda_pago -> moneda de la cuenta
    monto_en_moneda_cta   NUMERIC(14,2) NOT NULL,
    -- lo que se abona al saldo (en moneda de la cuenta)
    tasa_base             NUMERIC(20,8),
    monto_en_base         NUMERIC(14,2),
    -- para reportes en moneda_base de tienda
    referencia            VARCHAR(100),
    notas                 TEXT,
    creado_en             TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
COMMENT ON TABLE  abonos_credito IS 'Pagos a crédito multimoneda. Trigger recalcula saldo_pendiente en facturas y saldo_usado en cuentas.';
COMMENT ON COLUMN abonos_credito.monto_en_moneda_cta IS 'El monto que se descuenta del saldo (en moneda de la cuenta de crédito).';

-- =============================================================================
-- MÓDULO 9 - AUDITORÍA Y CONFIGURACIÓN
-- =============================================================================

CREATE TABLE auditoria (
    id            BIGSERIAL    NOT NULL,
    user_id       BIGINT,
    tabla         VARCHAR(60)  NOT NULL,
    accion        VARCHAR(10)  NOT NULL,
    registro_id   BIGINT       NOT NULL,
    datos_antes   JSONB,
    datos_despues JSONB,
    ip            INET,
    user_agent    VARCHAR(300),
    creado_en     TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    PRIMARY KEY (id, creado_en)
) PARTITION BY RANGE (creado_en);
COMMENT ON TABLE auditoria IS 'Bitácora inmutable particionada. No se edita ni borra.';

CREATE TABLE auditoria_2025 PARTITION OF auditoria FOR VALUES FROM ('2025-01-01') TO ('2026-01-01');
CREATE TABLE auditoria_2026 PARTITION OF auditoria FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');
CREATE TABLE auditoria_2027 PARTITION OF auditoria FOR VALUES FROM ('2027-01-01') TO ('2028-01-01');
CREATE TABLE auditoria_2028 PARTITION OF auditoria FOR VALUES FROM ('2028-01-01') TO ('2029-01-01');
CREATE INDEX idx_auditoria_tabla   ON auditoria(tabla, registro_id);
CREATE INDEX idx_auditoria_usuario ON auditoria(user_id);

CREATE TABLE notificaciones (
    id              BIGSERIAL    PRIMARY KEY,
    user_id         BIGINT,
    tipo            VARCHAR(40)  NOT NULL,
    -- stock_minimo | credito_vencido | lote_por_vencer | caja_sin_cuadre |
    -- compra_recibida | devolucion_pendiente | limite_credito_alcanzado |
    -- diferencia_caja | tasa_no_actualizada
    titulo          VARCHAR(150) NOT NULL,
    mensaje         TEXT         NOT NULL,
    referencia_tipo VARCHAR(40),
    referencia_id   BIGINT,
    leida           BOOLEAN      NOT NULL DEFAULT FALSE,
    creado_en       TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE plantillas_impresion (
    id             BIGSERIAL    PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    tipo           VARCHAR(30)  NOT NULL,
    -- ticket_80mm | factura_a4 | etiqueta_precio | nota_entrega |
    -- orden_compra | reporte_caja | abono_credito | cotizacion | nota_credito
    contenido_html TEXT         NOT NULL,
    es_defecto     BOOLEAN      NOT NULL DEFAULT FALSE,
    activo         BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- =============================================================================
-- FUNCIONES
-- =============================================================================

-- fn_actualizar_timestamp: trigger estándar para updated_at
CREATE OR REPLACE FUNCTION fn_actualizar_timestamp()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN NEW.actualizado_en = NOW(); RETURN NEW; END;
$$;

CREATE TRIGGER trg_ts_tienda          BEFORE UPDATE ON tienda          FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();
CREATE TRIGGER trg_ts_clientes        BEFORE UPDATE ON clientes        FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();
CREATE TRIGGER trg_ts_proveedores     BEFORE UPDATE ON proveedores     FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();
CREATE TRIGGER trg_ts_productos       BEFORE UPDATE ON productos       FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();
CREATE TRIGGER trg_ts_ventas          BEFORE UPDATE ON ventas          FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();
CREATE TRIGGER trg_ts_ordenes_compra  BEFORE UPDATE ON ordenes_compra  FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();
CREATE TRIGGER trg_ts_cuentas_credito BEFORE UPDATE ON cuentas_credito FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();

-- ---------------------------------------------------------------------------
-- fn_tasa_entre: resuelve la tasa de cambio entre cualquier par de monedas.
--
-- Estrategia:
--   1) Busca tasa directa A->B
--   2) Busca tasa inversa B->A y calcula 1/tasa
--   3) Busca cross-rate via tienda.moneda_pivot_api (normalmente USD)
--   4) Si el origen ES el pivot, usa la tasa directa pivot->destino
--
-- Parámetros:
--   p_origen  -> moneda desde
--   p_destino -> moneda hasta
--   p_fuente  -> NULL=cualquiera, 'BCV', 'paralelo', etc.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tasa_entre(
    p_origen  CHAR(3),
    p_destino CHAR(3),
    p_fuente  VARCHAR(30) DEFAULT NULL
) RETURNS NUMERIC(20,8) LANGUAGE plpgsql STABLE AS $$
DECLARE
    v_tasa    NUMERIC(20,8);
    v_pivot   CHAR(3);
    v_a_pivot NUMERIC(20,8);
    v_b_pivot NUMERIC(20,8);
BEGIN
    IF p_origen = p_destino THEN RETURN 1.0; END IF;

    -- 1) Tasa directa A->B
    SELECT tasa INTO v_tasa
    FROM tasas_cambio
    WHERE moneda_base    = p_origen
      AND moneda_destino = p_destino
      AND activa         = TRUE
      AND (p_fuente IS NULL OR fuente = p_fuente)
    ORDER BY creado_en DESC
    LIMIT 1;
    IF v_tasa IS NOT NULL THEN RETURN v_tasa; END IF;

    -- 2) Tasa inversa (B->A existe; calcular A->B = 1 / tasa(B->A))
    SELECT 1.0 / tasa INTO v_tasa
    FROM tasas_cambio
    WHERE moneda_base    = p_destino
      AND moneda_destino = p_origen
      AND activa         = TRUE
      AND (p_fuente IS NULL OR fuente = p_fuente)
    ORDER BY creado_en DESC
    LIMIT 1;
    IF v_tasa IS NOT NULL THEN RETURN ROUND(v_tasa, 8); END IF;

    -- 3) Cross-rate via pivot (moneda_pivot_api de la tienda)
    SELECT moneda_pivot_api INTO v_pivot FROM tienda LIMIT 1;
    IF v_pivot IS NULL THEN RETURN NULL; END IF;

    -- Pivot->origen y Pivot->destino
    SELECT tasa INTO v_a_pivot FROM tasas_cambio
    WHERE moneda_base=v_pivot AND moneda_destino=p_origen AND activa=TRUE
    ORDER BY creado_en DESC LIMIT 1;

    SELECT tasa INTO v_b_pivot FROM tasas_cambio
    WHERE moneda_base=v_pivot AND moneda_destino=p_destino AND activa=TRUE
    ORDER BY creado_en DESC LIMIT 1;

    IF v_a_pivot IS NOT NULL AND v_b_pivot IS NOT NULL AND v_a_pivot > 0 THEN
        RETURN ROUND(v_b_pivot / v_a_pivot, 8);
        -- Ej: USD/VES=36.45 y USD/COP=4200 -> VES/COP = 4200/36.45 = 115.23
    END IF;

    -- 4) Origen es el pivot: pivot->destino directamente
    IF p_origen = v_pivot AND v_b_pivot IS NOT NULL THEN
        RETURN v_b_pivot;
    END IF;

    RETURN NULL; -- Sin tasa disponible; la app debe alertar
END;
$$;
COMMENT ON FUNCTION fn_tasa_entre IS 'Resuelve tasa entre cualquier par: directo -> inverso -> cross-rate vía pivot. Retorna NULL si no hay tasa.';

-- ---------------------------------------------------------------------------
-- fn_recalcular_saldo_credito:
-- Recalcula saldo_pendiente en facturas y saldo_usado en cuentas
-- ante INSERT, UPDATE o DELETE en abonos_credito.
-- Usa monto_en_moneda_cta (ya convertido a la moneda de la cuenta).
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_recalcular_saldo_credito()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
DECLARE
    v_factura_id BIGINT;
    v_abono      NUMERIC(14,2);
BEGIN
    v_factura_id := CASE WHEN TG_OP = 'DELETE' THEN OLD.factura_credito_id
                         ELSE NEW.factura_credito_id END;
    v_abono      := CASE WHEN TG_OP = 'DELETE' THEN -OLD.monto_en_moneda_cta
                         WHEN TG_OP = 'UPDATE' THEN NEW.monto_en_moneda_cta - OLD.monto_en_moneda_cta
                         ELSE NEW.monto_en_moneda_cta END;

    -- Actualizar saldo_pendiente de la factura
    UPDATE facturas_credito
    SET saldo_pendiente = GREATEST(0, saldo_pendiente - v_abono),
        estado = CASE
            WHEN GREATEST(0, saldo_pendiente - v_abono) <= 0 THEN 'pagada'
            WHEN GREATEST(0, saldo_pendiente - v_abono) < monto_total THEN 'parcial'
            ELSE 'pendiente'
        END
    WHERE id = v_factura_id;

    -- Recalcular saldo_usado desde la fuente de verdad (suma de saldos pendientes)
    UPDATE cuentas_credito cc
    SET saldo_usado    = COALESCE(
                            (SELECT SUM(fc.saldo_pendiente)
                             FROM facturas_credito fc
                             WHERE fc.cuenta_credito_id = cc.id
                               AND fc.estado NOT IN ('pagada','anulada')),
                            0),
        actualizado_en = NOW()
    WHERE cc.id = (SELECT cuenta_credito_id FROM facturas_credito WHERE id = v_factura_id);

    RETURN CASE WHEN TG_OP = 'DELETE' THEN OLD ELSE NEW END;
END;
$$;

CREATE TRIGGER trg_abono_credito
    AFTER INSERT OR UPDATE OR DELETE ON abonos_credito
    FOR EACH ROW EXECUTE FUNCTION fn_recalcular_saldo_credito();

-- =============================================================================
-- VISTAS (actualizadas para v2.1)
-- =============================================================================

-- * Stock en tiempo real *
CREATE VIEW vista_stock AS
SELECT
    a.nombre                                             AS almacen,
    p.codigo_sku,
    p.nombre                                             AS producto,
    p.moneda_precio,
    vp.descripcion                                       AS variante,
    vp.codigo_barra,
    u.abreviatura                                        AS unidad,
    i.cantidad_disponible,
    i.cantidad_reservada,
    i.cantidad_en_transito,
    i.stock_minimo,
    p.costo_promedio,
    p.moneda_precio                                      AS moneda_costo,
    p.margen_pct,
    p.precio_base                                        AS precio_venta_calculado,
    p.precio_minimo,
    CASE
        WHEN i.cantidad_disponible <= 0              THEN 'sin_stock'
        WHEN i.cantidad_disponible <= i.stock_minimo THEN 'stock_minimo'
        ELSE 'normal'
    END                                                  AS estado_stock,
    i.ultima_entrada,
    i.ultima_salida,
    i.actualizado_en
FROM inventario i
JOIN variantes_producto vp ON vp.id = i.variante_id
JOIN productos p           ON p.id  = vp.producto_id
JOIN almacenes a           ON a.id  = i.almacen_id
JOIN unidades u            ON u.id  = p.unidad_id
WHERE p.activo = TRUE AND vp.activo = TRUE AND a.activo = TRUE;
COMMENT ON VIEW vista_stock IS 'Stock en tiempo real con moneda del producto. Filtrar por estado_stock para alertas.';

-- * Rentabilidad de ventas (v2.1: con impuesto_monto + tipo_documento) *
CREATE VIEW vista_rentabilidad_ventas AS
SELECT
    v.numero_factura,
    v.tipo_documento,
    v.tipo_pago,
    v.creado_en::DATE                                        AS fecha,
    v.moneda_factura,
    v.fuente_tasa,
    COALESCE(c.nombre, 'Cliente eliminado')                  AS cliente,
    p.codigo_sku,
    p.nombre                                                 AS producto,
    p.moneda_precio,
    vp.descripcion                                           AS variante,
    iv.cantidad,
    iv.moneda_precio                                         AS moneda_item,
    iv.precio_unitario,
    iv.tasa_conversion,
    iv.precio_en_factura,
    iv.costo_en_factura,
    iv.descuento_pct,
    iv.impuesto_pct,
    iv.impuesto_monto,
    iv.total_linea,
    iv.ganancia_linea,
    ROUND(iv.ganancia_linea / NULLIF(iv.total_linea, 0) * 100, 2)  AS pct_ganancia_real,
    v.impuesto_iva                                           AS iva_factura,
    v.impuesto_igtf                                          AS igtf_factura,
    v.total_en_base,
    v.tasa_base_usada
FROM items_venta iv
JOIN ventas v              ON v.id  = iv.venta_id
LEFT JOIN clientes c       ON c.id  = v.cliente_id
JOIN variantes_producto vp ON vp.id = iv.variante_id
JOIN productos p           ON p.id  = vp.producto_id
WHERE v.estado NOT IN ('anulada')
  AND v.tipo_documento NOT IN ('COT');
COMMENT ON VIEW vista_rentabilidad_ventas IS 'v2.1: Ganancia real por línea. Excluye COT y anuladas. Incluye impuesto_monto, IVA e IGTF de cabecera.';

-- * Cartera activa de créditos *
CREATE VIEW vista_cartera_creditos AS
SELECT
    c.nombre                              AS cliente,
    c.telefono,
    fc.moneda,
    v.numero_factura                      AS factura_venta,
    v.tipo_documento,
    fc.monto_total,
    fc.saldo_pendiente,
    fc.fecha_emision,
    fc.fecha_vence,
    fc.estado,
    CURRENT_DATE - fc.fecha_vence         AS dias_mora,
    cc.limite                             AS limite_credito,
    cc.saldo_usado,
    cc.saldo_disponible
FROM facturas_credito fc
JOIN clientes c         ON c.id  = fc.cliente_id
JOIN cuentas_credito cc ON cc.id = fc.cuenta_credito_id
JOIN ventas v           ON v.id  = fc.venta_id
WHERE fc.estado NOT IN ('pagada', 'anulada');
COMMENT ON VIEW vista_cartera_creditos IS 'Cartera activa con moneda de cada crédito. Ordenar al consultar (no ORDER BY en vista).';

-- * Cierre de caja multimoneda *
-- Muestra el desglose por moneda de cada sesión y el cuadre en moneda_base
CREATE VIEW vista_cierre_caja AS
SELECT
    sc.id                                                   AS sesion_id,
    ca.nombre                                               AS caja,
    sc.estado,
    sc.apertura_en,
    sc.cierre_en,
    -- -> Por moneda ->
    scm.moneda,
    m.simbolo                                               AS simbolo_moneda,
    scm.monto_apertura,
    scm.total_ventas,
    scm.total_devoluciones,
    scm.total_retiros,
    scm.total_ingresos,
    scm.total_gastos,
    scm.monto_calculado,
    scm.monto_declarado,
    scm.diferencia,
    -- -> En moneda base ->
    scm.tasa_al_cierre,
    scm.monto_calculado_en_base,
    scm.monto_declarado_en_base,
    scm.diferencia_en_base,
    -- -> Totales del turno en moneda base (de sesiones_caja) ->
    sc.total_ventas_base,
    sc.total_retiros_base,
    sc.diferencia_base
FROM sesiones_caja sc
JOIN cajas ca           ON ca.id  = sc.caja_id
JOIN sesion_caja_monedas scm ON scm.sesion_id = sc.id
JOIN monedas m          ON m.codigo = scm.moneda;
COMMENT ON VIEW vista_cierre_caja IS 'Desglose de cierre por moneda + totales en moneda_base. Base para el reporte de cuadre de caja.';

-- * Pagos por sesión agrupados por moneda (v2.1: con IGTF) *
CREATE VIEW vista_pagos_por_moneda AS
SELECT
    pv.sesion_caja_id,
    pv.moneda_pago                             AS moneda,
    mp.tipo                                    AS tipo_metodo,
    mp.nombre                                  AS metodo_pago,
    mp.grava_igtf,
    COUNT(*)                                   AS cant_operaciones,
    SUM(pv.monto_pago)                         AS total_recibido,
    -- en moneda_pago
    SUM(pv.monto_igtf)                         AS total_igtf,
    -- IGTF percibido en moneda_pago
    SUM(pv.monto_en_factura)                   AS total_en_factura,
    SUM(pv.monto_en_base)                      AS total_en_base
FROM pagos_venta pv
JOIN metodos_pago mp ON mp.id = pv.metodo_pago_id
WHERE pv.sesion_caja_id IS NOT NULL
GROUP BY pv.sesion_caja_id, pv.moneda_pago, mp.tipo, mp.nombre, mp.grava_igtf;
COMMENT ON VIEW vista_pagos_por_moneda IS 'v2.1: Totales de cobro por moneda y método en cada sesión de caja. Incluye total_igtf percibido.';

-- * Resumen de tasas vigentes *
CREATE VIEW vista_tasas_vigentes AS
SELECT
    tc.moneda_base,
    mb.nombre   AS nombre_base,
    mb.simbolo  AS simbolo_base,
    tc.moneda_destino,
    md.nombre   AS nombre_destino,
    md.simbolo  AS simbolo_destino,
    tc.tasa,
    tc.fuente,
    tc.fecha,
    tc.creado_en AS actualizado_en,
    -- Tasa inversa (cuánto de base = 1 destino)
    ROUND(1.0 / tc.tasa, 8) AS tasa_inversa
FROM tasas_cambio tc
JOIN monedas mb ON mb.codigo = tc.moneda_base
JOIN monedas md ON md.codigo = tc.moneda_destino
WHERE tc.activa = TRUE
ORDER BY tc.moneda_base, tc.fuente, tc.moneda_destino;
COMMENT ON VIEW vista_tasas_vigentes IS 'Tasas activas con sus inversas. La app usa esta vista en el POS para mostrar equivalencias.';

-- =============================================================================
-- FIN DEL SCRIPT v2.1
--
-- RESUMEN DE CAMBIOS vs v2.0:
--   * tienda: +es_agente_igtf, +alicuota_igtf
--   * metodos_pago: +grava_igtf (configurable por admin)
--   * ventas: impuesto -> impuesto_iva + impuesto_igtf
--             tipo -> tipo_documento (FAC/NE/NC/ND/COT) + tipo_pago (contado/credito)
--             estado limpio con CHECK constraint
--   * items_venta: +impuesto_monto (monto absoluto IVA)
--   * pagos_venta: +monto_igtf, +tasa_igtf_pct
--   * devoluciones_venta: +numero_nota_credito UNIQUE
--   * items_devolucion: +monto_devuelto_en_factura, +tasa_usada_devolucion,
--                        +monto_devuelto_en_base
--   * Vistas actualizadas: vista_rentabilidad_ventas, vista_pagos_por_moneda,
--                           vista_cartera_creditos
--
-- Tablas de negocio: 42  |  Vistas: 6  |  Funciones: 3  |  Triggers: 8+1
-- Particionadas: auditoria (2025-2028), movimientos_inventario (2025-2028)
--
-- CHECKLIST DE INTEGRACIÓN CON LARAVEL / EXCHANGERATE API:
--   ✓ Al hacer login el POS llama GET /api/tasas-vigentes y cachea en Zustand
--   ✓ ExchangeRate API: GET /latest?base=USD -> INSERT tasas_cambio (activa=TRUE)
--     antes de insertar: UPDATE tasas_cambio SET activa=FALSE WHERE moneda_base='USD'
--   ✓ Al abrir caja: INSERT sesion_caja_monedas por cada moneda de tienda_monedas
--   ✓ Al cobrar:
--     - INSERT pagos_venta con tasa_aplicada = fn_tasa_entre(moneda_pago, moneda_factura)
--     - Si metodos_pago.grava_igtf = TRUE Y tienda.es_agente_igtf = TRUE:
--         monto_igtf = monto_pago × (tienda.alicuota_igtf / 100)
--         tasa_igtf_pct = tienda.alicuota_igtf
--     - UPDATE sesion_caja_monedas SET total_ventas += monto_pago WHERE moneda = moneda_pago
--   ✓ Al cerrar caja: UPDATE sesion_caja_monedas SET monto_declarado = X (cajero cuenta)
--     + UPDATE sesion_caja_monedas SET tasa_al_cierre = fn_tasa_entre(moneda,'USD') etc.
--     + UPDATE sesiones_caja SET total_ventas_base = SUM(monto_calculado_en_base)
-- =============================================================================
