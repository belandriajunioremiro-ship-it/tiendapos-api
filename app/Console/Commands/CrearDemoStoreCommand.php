<?php

namespace App\Console\Commands;

use App\Services\OnboardingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CrearDemoStoreCommand extends Command
{
    protected $signature = 'pos:demo-store {pais? : Código ISO del país (VE, CO, MX, EC, AR, PE, CL, BO, UY)} 
                            {--reset : Elimina tiendas demo anteriores del mismo país antes de crear}';

    protected $description = 'Crea una tienda demo completa con onboarding automático para el país especificado';

    private array $datosPaises;

    public function handle(OnboardingService $onboarding): int
    {
        $this->cargarDatosPaises();

        $pais = strtoupper($this->argument('pais') ?? '');

        if (!$pais) {
            $pais = $this->choice(
                '🌍 ¿Para qué país quieres crear la tienda demo?',
                array_keys($this->datosPaises),
                'VE'
            );
        }

        if (!isset($this->datosPaises[$pais])) {
            $this->error("❌ País '{$pais}' no soportado.");
            $this->line('   Países disponibles: ' . implode(', ', array_keys($this->datosPaises)));
            return 1;
        }

        $d = $this->datosPaises[$pais];

        // ═════════════════════════════════════════════════════════════════════
        // ENCABEZADO PRINCIPAL
        // ═════════════════════════════════════════════════════════════════════
        $this->line('');
        $this->line('<fg=bright-cyan;bg=black;options=bold>================================================================================</>');
        $this->line('<fg=bright-cyan;bg=black;options=bold>         PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TECH              </>');
        $this->line('<fg=bright-cyan;bg=black;options=bold>================================================================================</>');
        $this->line(" {$d['emoji']} <fg=bright-green;options=bold>CREANDO TIENDA DEMO — {$d['nombre_pais']}</>");
        $this->line(" <fg=gray>{$d['autoridad']} · {$d['documento']} · {$d['moneda_info']}</>");
        $this->line('<fg=bright-cyan>================================================================================</>');
        $this->line('');

        // ─── RESET: Limpiar tiendas demo anteriores ─────────────────────────────
        if ($this->option('reset')) {
            $this->line('<fg=yellow>🗑️  MODO RESET: Eliminando tiendas demo anteriores...</>');
            
            // Eliminar por email del admin (más confiable)
            $userToDelete = DB::table('users')->where('email', $d['admin_email'])->first();
            if ($userToDelete) {
                $tiendaId = $userToDelete->tienda_id;
                if ($tiendaId) {
                    DB::table('tienda_onboarding')->where('tienda_id', $tiendaId)->delete();
                    DB::table('suscripciones')->where('tienda_id', $tiendaId)->delete();
                    DB::table('configuracion_impresora')->where('tienda_id', $tiendaId)->delete();
                    DB::table('users')->where('tienda_id', $tiendaId)->delete();
                    DB::table('tienda')->where('id', $tiendaId)->delete();
                } else {
                    // Usuario sin tienda, eliminarlo directamente
                    DB::table('users')->where('id', $userToDelete->id)->delete();
                }
                $this->line("   <fg=green>✓</> Usuario y tienda demo eliminados");
            } else {
                // Fallback: buscar por nombre comercial
                $tiendasAnteriores = DB::table('tienda')
                    ->where('nombre_comercial', $d['nombre_comercio'])
                    ->pluck('id');

                foreach ($tiendasAnteriores as $tiendaId) {
                    DB::table('tienda_onboarding')->where('tienda_id', $tiendaId)->delete();
                    DB::table('suscripciones')->where('tienda_id', $tiendaId)->delete();
                    DB::table('configuracion_impresora')->where('tienda_id', $tiendaId)->delete();
                    DB::table('users')->where('tienda_id', $tiendaId)->delete();
                    DB::table('tienda')->where('id', $tiendaId)->delete();
                }
                $this->line("   <fg=green>✓</> {$tiendasAnteriores->count()} tienda(s) eliminada(s)");
            }
            $this->line('');
        }

        // ═════════════════════════════════════════════════════════════════════
        // PASO 1: CREAR CUENTA + SUSCRIPCIÓN
        // ═════════════════════════════════════════════════════════════════════
        $infoPlan = $this->obtenerInfoPlanDemo($pais);
        $this->line("<fg=yellow;options=bold>📝  PASO 1: CREAR CUENTA + {$infoPlan['titulo']}</>");
        $this->line('<fg=gray>────────────────────────────────────────────────────────────────────────────────</>');
        $this->line("   <fg=cyan>Creando:</> Usuario administrador + Tienda + {$infoPlan['descripcion']}");

        $cuenta = $onboarding->crearCuenta([
            'pais'     => $pais,
            'name'     => "Admin {$d['nombre_pais']}",
            'email'    => $d['admin_email'],
            'password' => $d['admin_password'],
        ]);

        $tiendaId = $cuenta['tienda']->id;
        $suscripcion = $cuenta['tienda']->suscripcion;
        $plan = $suscripcion?->plan;
        $esTrial = $suscripcion?->estado === 'trial';

        // Construir filas según tipo de suscripción
        $suscripcionRows = [
            ['Campo',                    'Valor'],
            ['ID Tienda',                "#" . $tiendaId],
            ['Nombre Comercial',         $d['nombre_comercio']],
            ['País',                     $d['emoji'] . ' ' . $d['nombre_pais']],
            ['Moneda Base',              $cuenta['tienda']->moneda_base],
            ['Zona Horaria',             $cuenta['tienda']->zona_horaria],
            ['Estado Tienda',            $cuenta['tienda']->activo ? '✅ Activa' : '❌ Inactiva'],
            ['Plan',                     $plan?->nombre ?? 'N/A'],
            ['Estado Suscripción',       $esTrial ? '🆓 Trial' : '✅ Activa'],
        ];

        if ($esTrial) {
            $suscripcionRows[] = ['Días de Prueba',     ($plan?->dias_trial ?? 14) . ' días'];
            $suscripcionRows[] = ['Fecha Fin Trial',    $suscripcion->fin_trial?->format('d/m/Y H:i') ?? 'N/A'];
        } else {
            $suscripcionRows[] = ['Precio Mensual',     $plan?->precio_mensual . ' ' . ($plan?->moneda ?? 'USD')];
            $suscripcionRows[] = ['Fin Período',        $suscripcion->fin_periodo ? \Carbon\Carbon::parse($suscripcion->fin_periodo)->format('d/m/Y') : 'N/A'];
            $suscripcionRows[] = ['Próximo Cobro',      $suscripcion->proximo_cobro ? \Carbon\Carbon::parse($suscripcion->proximo_cobro)->format('d/m/Y') : 'N/A'];
            $suscripcionRows[] = ['Auto Renovar',       $suscripcion->auto_renovar ? '✅ Sí' : '❌ No'];
        }

        $suscripcionRows[] = ['Límite Productos',         $plan?->limite_productos ?? '∞'];
        $suscripcionRows[] = ['Límite Usuarios',          $plan?->limite_usuarios ?? '∞'];
        $suscripcionRows[] = ['Límite Almacenes',         $plan?->limite_almacenes ?? '∞'];
        $suscripcionRows[] = ['Límite Cajas',             $plan?->limite_cajas ?? '∞'];

        $this->line('');
        $this->table([], $suscripcionRows);

        // ═════════════════════════════════════════════════════════════════════
        // PASO 2: DATOS FISCALES DEL PAÍS
        // ═════════════════════════════════════════════════════════════════════
        $this->line('');
        $this->line("<fg=yellow;options=bold>🏢  PASO 2: DATOS FISCALES — {$d['id_label']} ({$d['nombre_pais']})</>");
        $this->line('<fg=gray>────────────────────────────────────────────────────────────────────────────────</>');
        $this->line("   <fg=cyan>Configurando:</> {$d['id_label']} · Razón Social · Régimen Fiscal · Auto-sembrado de impuestos");

        $onboarding->guardarDatosFiscales($tiendaId, [
            'identificacion_fiscal' => $d['rif'],
            'razon_social'          => $d['razon_social'],
            'nombre_comercial'      => $d['nombre_comercio'],
            'direccion'             => $d['direccion'],
            'telefono'              => $d['telefono'],
            'email'                 => $d['email_tienda'],
            'regimen_fiscal'        => $d['regimen_fiscal'] ?? null,
            'codigo_postal'         => $d['codigo_postal'] ?? null,
        ]);

        $this->line('');
        $this->table([], [
            ['Campo',                    'Valor'],
            [$d['id_label'],             $d['rif']],
            ['Razón Social',             $d['razon_social']],
            ['Nombre Comercial',         $d['nombre_comercio']],
            ['Dirección',                $d['direccion']],
            ['Teléfono',                 $d['telefono']],
            ['Email Tienda',             $d['email_tienda']],
            ['Código Postal',            $d['codigo_postal'] ?? '—'],
            ['Régimen Fiscal',           $d['regimen_fiscal'] ?? '—'],
        ]);

        // Mostrar impuestos sembrados
        $impuestos = DB::table('impuestos')->where('activo', true)->get();
        $this->line('');
        $this->line('   <fg=cyan>Impuestos configurados automáticamente:</>');
        $impRows = $impuestos->map(fn($i) => [
            $i->id,
            $i->nombre,
            $i->porcentaje . '%',
            ucfirst($i->tipo),
            $i->es_defecto ? '⭐ DEFAULT' : '',
        ])->toArray();
        $this->table(['ID', 'Nombre', 'Porcentaje', 'Tipo', 'Default'], $impRows);

        // ═════════════════════════════════════════════════════════════════════
        // PASO 3: CONFIGURACIÓN DEL NEGOCIO + CATÁLOGOS
        // ═════════════════════════════════════════════════════════════════════
        $this->line('');
        $this->line("<fg=yellow;options=bold>🏪  PASO 3: CONFIGURACIÓN DEL NEGOCIO — {$d['tipo_negocio']}</>");
        $this->line('<fg=gray>────────────────────────────────────────────────────────────────────────────────</>');
        $this->line("   <fg=cyan>Creando:</> Almacén · Caja · Categorías · Métodos de Pago · Cliente Default");

        $onboarding->configurarNegocio($tiendaId, [
            'tipo_negocio'   => $d['tipo_negocio'],
            'nombre_almacen' => 'Depósito Principal',
            'nombre_caja'    => 'Caja 1',
            'tipo_impresora' => 'termica_80mm',
        ]);

        // Mostrar almacén y caja
        $almacen = DB::table('almacenes')->orderBy('id', 'desc')->first();
        $caja = DB::table('cajas')->orderBy('id', 'desc')->first();
        
        $this->line('');
        $this->table([], [
            ['Campo',                    'Valor'],
            ['Almacén',                  $almacen->nombre ?? 'Depósito Principal'],
            ['Tipo Almacén',             $almacen->tipo ?? 'deposito'],
            ['Caja',                     $caja->nombre ?? 'Caja 1'],
            ['Estado Caja',              $caja->activo ? '✅ Activa' : '❌ Inactiva'],
            ['Tipo Negocio',             $d['tipo_negocio']],
        ]);

        // Mostrar categorías sembradas
        $categorias = DB::table('categorias_productos')->pluck('nombre')->toArray();
        $this->line('');
        $this->line('   <fg=cyan>Categorías de productos sembradas:</>');
        $chunks = array_chunk($categorias, 4);
        foreach ($chunks as $chunk) {
            $this->line('   • ' . implode('   • ', $chunk));
        }

        // Mostrar métodos de pago
        $metodos = DB::table('metodos_pago')->where('activo', true)->get();
        $this->line('');
        $this->line('   <fg=cyan>Métodos de pago de ' . $d['nombre_pais'] . ':</>');
        $mPRows = $metodos->map(fn($m) => [
            $m->nombre,
            $m->tipo,
            $m->moneda,
            $m->grava_igtf ? '<fg=red>✅ IGTF</>' : '—',
            $m->requiere_referencia ? 'Sí' : 'No',
        ])->toArray();
        $this->table(['Nombre', 'Tipo', 'Moneda', 'IGTF', 'Ref.'], $mPRows);

        // ═════════════════════════════════════════════════════════════════════
        // PASO 4: PRIMER PRODUCTO DEMO
        // ═════════════════════════════════════════════════════════════════════
        $this->line('');
        $this->line('<fg=yellow;options=bold>📦  PASO 4: PRIMER PRODUCTO DEMO</>');
        $this->line('<fg=gray>────────────────────────────────────────────────────────────────────────────────</>');
        $this->line("   <fg=cyan>Creando:</> Producto + Variante + Inventario Inicial");

        $producto = $onboarding->crearPrimerProducto($tiendaId, [
            'nombre'        => $d['producto']['nombre'],
            'costo'         => $d['producto']['costo'],
            'aplica_iva'    => $d['producto']['aplica_iva'],
            'stock_inicial' => $d['producto']['stock'],
        ]);

        $this->line('');
        $this->table([], [
            ['Campo',                    'Valor'],
            ['Producto',                 $d['producto']['nombre']],
            ['SKU',                      $producto->codigo_sku],
            ['Costo Unitario',           $d['producto']['costo'] . ' ' . $cuenta['tienda']->moneda_base],
            ['Stock Inicial',            $d['producto']['stock'] . ' unidades'],
            ['Aplica IVA',               $d['producto']['aplica_iva'] ? '✅ Sí' : '❌ No'],
            ['Estado',                   $producto->activo ? '✅ Activo' : '❌ Inactivo'],
        ]);

        // ═════════════════════════════════════════════════════════════════════
        // CONFIGURACIÓN FISCAL DEL PAÍS
        // ═════════════════════════════════════════════════════════════════════
        $this->line('');
        $this->line('<fg=yellow;options=bold>📋  CONFIGURACIÓN FISCAL DEL PAÍS</>');
        $this->line('<fg=gray>────────────────────────────────────────────────────────────────────────────────</>');
        $this->table([], [
            ['País',              $d['emoji'] . ' ' . $d['nombre_pais']],
            ['Autoridad Fiscal',  $d['autoridad']],
            ['Documento Fiscal',  $d['documento']],
            ['IVA General',       $d['iva_general']],
            ['Exenciones',        $d['exenciones']],
            ['Impuesto Especial', $d['impuesto_especial']],
        ]);

        // ═════════════════════════════════════════════════════════════════════
        // MONEDAS Y TASAS DE CAMBIO
        // ═════════════════════════════════════════════════════════════════════
        $monedas = DB::table('tienda_monedas')->get();
        $this->line('');
        $this->line('<fg=yellow;options=bold>💱  MONEDAS HABILITADAS</>');
        $this->line('<fg=gray>────────────────────────────────────────────────────────────────────────────────</>');
        $mRows = $monedas->map(fn($m) => [
            $m->moneda,
            $m->acepta_ventas  ? '✅' : '❌',
            $m->acepta_compras ? '✅' : '❌',
            $m->activa         ? '✅' : '❌',
        ])->toArray();
        $this->table(['Moneda', 'Ventas', 'Compras', 'Activa'], $mRows);

        $tasa = DB::table('tasas_cambio')->where('activa', true)->first();
        if ($tasa) {
            $this->line("   <fg=cyan>💱 Tasa de cambio: 1 {$tasa->moneda_base} = {$tasa->tasa} {$tasa->moneda_destino} (Fuente: {$tasa->fuente})</>");
        }

        // ═════════════════════════════════════════════════════════════════════
        // CREDENCIALES DE ACCESO
        // ═════════════════════════════════════════════════════════════════════
        $this->line('');
        $this->line('<fg=yellow;options=bold>👤  CREDENCIALES DE ACCESO</>');
        $this->line('<fg=gray>────────────────────────────────────────────────────────────────────────────────</>');
        $this->table([], [
            ['Campo',                    'Valor'],
            ['Usuario Admin',            $d['admin_email']],
            ['Password',                 $d['admin_password']],
            ['Tienda ID',                $tiendaId],
            ['User ID',                  $cuenta['user']->id],
        ]);

        // ═════════════════════════════════════════════════════════════════════
        // RESUMEN FINAL
        // ═════════════════════════════════════════════════════════════════════
        $this->line('');
        $this->line('<fg=bright-cyan;bg=black;options=bold>================================================================================</>');
        $this->line('<fg=green;options=bold>                       ✅ TIENDA DEMO CREADA CON ÉXITO                       </>');
        $this->line('<fg=bright-cyan;bg=black;options=bold>================================================================================</>');
        $this->line('');

        $this->line('<fg=yellow>💡 Para hacer login desde Next.js:</>');
        $this->line('   <fg=white>POST http://localhost:8000/api/login</>');
        $this->line('   <fg=white>{ "email": "' . $d['admin_email'] . '", "password": "' . $d['admin_password'] . '" }</>');
        $this->line('');

        $this->line('<fg=yellow>📊 Estado de la suscripción:</>');
        $this->line('   <fg=white>GET http://localhost:8000/api/v1/suscripcion/estado</>');
        $this->line('   <fg=gray>Header: Authorization: Bearer {token}</>');
        $this->line('');

        $this->line('<fg=bright-cyan>================================================================================</>');
        $this->line(" <fg=green;options=bold>✅ Tienda #{$tiendaId} — {$pais} creada correctamente desde NEON TECH</>");
        $this->line('<fg=bright-cyan>================================================================================</>');
        $this->line('');

        return 0;
    }

    private function cargarDatosPaises(): void
    {
        $this->datosPaises = [
            'VE' => [
                'emoji'            => '🇻🇪',
                'nombre_pais'      => 'Venezuela',
                'id_label'         => 'RIF',
                'rif'              => 'J-12345678-9',
                'razon_social'     => 'Farmacia Salud y Vida C.A.',
                'nombre_comercio'  => 'Farmacia Salud y Vida',
                'direccion'        => 'Av. Principal, Centro Comercial Plaza, Local 5, Valencia, Carabobo',
                'telefono'         => '+58 241-1234567',
                'email_tienda'     => 'contacto@farmaciasalud.com',
                'codigo_postal'    => '2001',
                'regimen_fiscal'   => 'Ordinario',
                'admin_email'      => 'admin@farmacia-demo-ve.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'farmacia',
                'autoridad'        => 'SENIAT',
                'documento'        => 'Factura Fiscal SENIAT',
                'moneda_info'      => 'USD principal + VES secundario',
                'iva_general'      => '16% general | 8% reducido',
                'exenciones'       => 'Cesta básica (Decreto Ejecutivo)',
                'impuesto_especial'=> 'IGTF 3% sobre pagos en divisas',
                'producto'         => [
                    'nombre'     => 'Acetaminofén 500mg x 10 tabletas',
                    'costo'      => 2.50,
                    'aplica_iva' => true,
                    'stock'      => 100,
                ],
            ],
            'CO' => [
                'emoji'            => '🇨🇴',
                'nombre_pais'      => 'Colombia',
                'id_label'         => 'NIT',
                'rif'              => '900.123.456-7',
                'razon_social'     => 'Droguería El Buen Precio SAS',
                'nombre_comercio'  => 'Droguería El Buen Precio',
                'direccion'        => 'Carrera 10 # 20-30, Local 2, Bogotá, Cundinamarca',
                'telefono'         => '+57 1-2345678',
                'email_tienda'     => 'contacto@drogueriaelprecio.com',
                'codigo_postal'    => '110111',
                'regimen_fiscal'   => 'Común',
                'admin_email'      => 'admin@drogueria-demo-co.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'farmacia',
                'autoridad'        => 'DIAN',
                'documento'        => 'Factura Electrónica DIAN',
                'moneda_info'      => 'COP (Peso Colombiano)',
                'iva_general'      => '19% general | 5% diferencial',
                'exenciones'       => 'Canasta familiar (art. 477 ET)',
                'impuesto_especial'=> 'INC bebidas / Retefuente',
                'producto'         => [
                    'nombre'     => 'Acetaminofén 500mg x 20 tabletas',
                    'costo'      => 5000,
                    'aplica_iva' => true,
                    'stock'      => 150,
                ],
            ],
            'MX' => [
                'emoji'            => '🇲🇽',
                'nombre_pais'      => 'México',
                'id_label'         => 'RFC',
                'rif'              => 'ABC123456XYZ',
                'razon_social'     => 'Ferretería El Tornillo SA de CV',
                'nombre_comercio'  => 'Ferretería El Tornillo',
                'direccion'        => 'Av. Revolución 1234, Col. Centro, Ciudad de México, CDMX',
                'telefono'         => '+52 55-1234-5678',
                'email_tienda'     => 'contacto@ferreteriatornillo.mx',
                'codigo_postal'    => '06000',
                'regimen_fiscal'   => 'Persona Moral',
                'admin_email'      => 'admin@ferreteria-demo-mx.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'ferreteria',
                'autoridad'        => 'SAT',
                'documento'        => 'CFDI 4.0 (SAT)',
                'moneda_info'      => 'MXN (Peso Mexicano)',
                'iva_general'      => '16% general | 0% alimentos',
                'exenciones'       => 'Alimentos sin procesar / medicamentos (LIVA art. 2-A)',
                'impuesto_especial'=> 'IEPS (bebidas / tabaco / combustible)',
                'producto'         => [
                    'nombre'     => 'Martillo de carpintero 16oz',
                    'costo'      => 150,
                    'aplica_iva' => true,
                    'stock'      => 50,
                ],
            ],
            'EC' => [
                'emoji'            => '🇪🇨',
                'nombre_pais'      => 'Ecuador',
                'id_label'         => 'RUC',
                'rif'              => '1712345678001',
                'razon_social'     => 'Supermercado El Ahorro Cía. Ltda.',
                'nombre_comercio'  => 'Supermercado El Ahorro',
                'direccion'        => 'Av. Amazonas N34-123 y Naciones Unidas, Quito, Pichincha',
                'telefono'         => '+593 2-123-4567',
                'email_tienda'     => 'contacto@superahorro.ec',
                'codigo_postal'    => '170507',
                'regimen_fiscal'   => 'RIMPE',
                'admin_email'      => 'admin@supermercado-demo-ec.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'supermercado',
                'autoridad'        => 'SRI',
                'documento'        => 'Comprobante Electrónico SRI',
                'moneda_info'      => 'USD (dolarizado desde 2000)',
                'iva_general'      => '15% general | 0% medicamentos',
                'exenciones'       => 'Medicamentos, canasta básica (LRTI)',
                'impuesto_especial'=> 'ICE (consumos especiales)',
                'producto'         => [
                    'nombre'     => 'Arroz premium 1kg',
                    'costo'      => 1.50,
                    'aplica_iva' => true,
                    'stock'      => 200,
                ],
            ],
            'AR' => [
                'emoji'            => '🇦🇷',
                'nombre_pais'      => 'Argentina',
                'id_label'         => 'CUIT',
                'rif'              => '30-12345678-9',
                'razon_social'     => 'Licorería La Esquina SRL',
                'nombre_comercio'  => 'Licorería La Esquina',
                'direccion'        => 'Av. Corrientes 1234, CABA, Buenos Aires',
                'telefono'         => '+54 11-1234-5678',
                'email_tienda'     => 'contacto@licoreriaesquina.ar',
                'codigo_postal'    => '1043',
                'regimen_fiscal'   => 'Responsable Inscripto',
                'admin_email'      => 'admin@licoreria-demo-ar.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'licoreria',
                'autoridad'        => 'AFIP',
                'documento'        => 'Factura Tipo A / B / C',
                'moneda_info'      => 'ARS (Peso Argentino)',
                'iva_general'      => '21% general | 10.5% reducido',
                'exenciones'       => 'Productos básicos',
                'impuesto_especial'=> 'Impuesto Interno',
                'producto'         => [
                    'nombre'     => 'Vino Malbec 750ml',
                    'costo'      => 1200,
                    'aplica_iva' => true,
                    'stock'      => 80,
                ],
            ],
            'PE' => [
                'emoji'            => '🇵🇪',
                'nombre_pais'      => 'Perú',
                'id_label'         => 'RUC',
                'rif'              => '20123456789',
                'razon_social'     => 'Bodega San Juan EIRL',
                'nombre_comercio'  => 'Bodega San Juan',
                'direccion'        => 'Jr. Ayacucho 456, Lima, Lima',
                'telefono'         => '+51 1-123-4567',
                'email_tienda'     => 'contacto@bodegasanjuan.pe',
                'codigo_postal'    => '15001',
                'regimen_fiscal'   => 'Régimen General',
                'admin_email'      => 'admin@bodega-demo-pe.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'bodega',
                'autoridad'        => 'SUNAT',
                'documento'        => 'Factura Electrónica',
                'moneda_info'      => 'PEN (Sol Peruano)',
                'iva_general'      => '18% (IGV)',
                'exenciones'       => 'Productos de primera necesidad',
                'impuesto_especial'=> 'ISC (Impuesto Selectivo al Consumo)',
                'producto'         => [
                    'nombre'     => 'Azúcar rubia 1kg',
                    'costo'      => 4.50,
                    'aplica_iva' => true,
                    'stock'      => 120,
                ],
            ],
            'CL' => [
                'emoji'            => '🇨🇱',
                'nombre_pais'      => 'Chile',
                'id_label'         => 'RUT',
                'rif'              => '12.345.678-9',
                'razon_social'     => 'Moto Repuestos SpA',
                'nombre_comercio'  => 'Moto Repuestos',
                'direccion'        => "Av. Libertador Bernardo O'Higgins 789, Santiago",
                'telefono'         => '+56 2-1234-5678',
                'email_tienda'     => 'contacto@motorepuestos.cl',
                'codigo_postal'    => '8320000',
                'regimen_fiscal'   => 'Persona Jurídica',
                'admin_email'      => 'admin@moto-demo-cl.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'motos',
                'autoridad'        => 'SII',
                'documento'        => 'Factura Electrónica',
                'moneda_info'      => 'CLP (Peso Chileno)',
                'iva_general'      => '19%',
                'exenciones'       => 'Productos exentos',
                'impuesto_especial'=> '—',
                'producto'         => [
                    'nombre'     => 'Aceite motor 10W40 1L',
                    'costo'      => 8000,
                    'aplica_iva' => true,
                    'stock'      => 60,
                ],
            ],
            'BO' => [
                'emoji'            => '🇧🇴',
                'nombre_pais'      => 'Bolivia',
                'id_label'         => 'NIT',
                'rif'              => '123456789',
                'razon_social'     => 'Abarrotes El Puente Ltda.',
                'nombre_comercio'  => 'Abarrotes El Puente',
                'direccion'        => 'Av. 6 de Agosto, La Paz',
                'telefono'         => '+591 2-1234567',
                'email_tienda'     => 'contacto@abarrotespuente.bo',
                'codigo_postal'    => '00000',
                'regimen_fiscal'   => 'General',
                'admin_email'      => 'admin@abarrotes-demo-bo.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'abarrotes',
                'autoridad'        => 'Impuestos Nacionales',
                'documento'        => 'Factura Fiscal',
                'moneda_info'      => 'BOB (Boliviano)',
                'iva_general'      => '13%',
                'exenciones'       => 'Productos de primera necesidad',
                'impuesto_especial'=> '—',
                'producto'         => [
                    'nombre'     => 'Fideos tallarín 500g',
                    'costo'      => 8,
                    'aplica_iva' => true,
                    'stock'      => 90,
                ],
            ],
            'UY' => [
                'emoji'            => '🇺🇾',
                'nombre_pais'      => 'Uruguay',
                'id_label'         => 'RUT',
                'rif'              => '123456780019',
                'razon_social'     => 'Restaurante El Asador SRL',
                'nombre_comercio'  => 'Restaurante El Asador',
                'direccion'        => 'Av. 18 de Julio 1234, Montevideo',
                'telefono'         => '+598 2-123-4567',
                'email_tienda'     => 'contacto@elasador.uy',
                'codigo_postal'    => '11000',
                'regimen_fiscal'   => 'IVA General',
                'admin_email'      => 'admin@restaurante-demo-uy.com',
                'admin_password'   => 'demo1234',
                'tipo_negocio'     => 'restaurante',
                'autoridad'        => 'DGI',
                'documento'        => 'Factura Electrónica',
                'moneda_info'      => 'UYU (Peso Uruguayo)',
                'iva_general'      => '22% general | 10% básico',
                'exenciones'       => 'Productos básicos',
                'impuesto_especial'=> '—',
                'producto'         => [
                    'nombre'     => 'Asado de tira 1kg',
                    'costo'      => 450,
                    'aplica_iva' => true,
                    'stock'      => 40,
                ],
            ],
        ];
    }

    /**
     * Obtiene información del plan demo según el país.
     */
    private function obtenerInfoPlanDemo(string $pais): array
    {
        return match($pais) {
            'VE', 'CO' => [
                'titulo'      => 'SUSCRIPCIÓN TRIAL',
                'descripcion' => 'Suscripción Trial 14 días',
            ],
            'MX', 'EC' => [
                'titulo'      => 'SUSCRIPCIÓN BÁSICO',
                'descripcion' => 'Plan Básico $19 USD/mes',
            ],
            'AR', 'PE' => [
                'titulo'      => 'SUSCRIPCIÓN PRO',
                'descripcion' => 'Plan Pro $39 USD/mes',
            ],
            'CL', 'BO', 'UY' => [
                'titulo'      => 'SUSCRIPCIÓN PREMIUM',
                'descripcion' => 'Plan Premium $99 USD/mes',
            ],
            default => [
                'titulo'      => 'SUSCRIPCIÓN TRIAL',
                'descripcion' => 'Suscripción Trial 14 días',
            ],
        };
    }
}
