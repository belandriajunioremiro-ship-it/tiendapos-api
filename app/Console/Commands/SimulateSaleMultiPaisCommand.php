<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PosBusinessRulesService;
use App\Models\Venta;
use App\Models\SesionCaja;
use App\Models\Tienda;
use App\Models\Caja;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\VarianteProducto;
use App\Models\MetodoPago;
use App\Models\TasaCambio;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Unidad;
use App\Models\Impuesto;
use Illuminate\Support\Facades\DB;

class SimulateSaleMultiPaisCommand extends Command
{
    protected $signature = 'pos:simulate-sale {pais?} {--list}';
    protected $description = 'Simula una venta real multi-país (VE, CO, MX, EC) con productos y precios 2026';

    private $posService;

    public function __construct(PosBusinessRulesService $posService)
    {
        parent::__construct();
        $this->posService = $posService;
    }

    public function handle()
    {
        if ($this->option('list')) {
            $this->mostrarPaisesDisponibles();
            return;
        }

        $pais = $this->argument('pais');
        if (!$pais) {
            $pais = $this->choice(
                '¿Qué país quieres simular?',
                ['VE' => '🇻🇪 Venezuela - Repuestos de Moto', 'CO' => '🇨🇴 Colombia - Ferretería', 'MX' => '🇲🇽 México - Miscelánea', 'EC' => '🇪🇨 Ecuador - Farmacia'],
                'VE'
            );
        }

        $pais = strtoupper($pais);
        if (!in_array($pais, ['VE', 'CO', 'MX', 'EC'])) {
            $this->error("País no soportado: {$pais}. Usa: VE, CO, MX o EC");
            return 1;
        }

        $this->simular($pais);
        return 0;
    }

    private function mostrarPaisesDisponibles(): void
    {
        $this->line('');
        $this->line('<fg=bright-cyan;options=bold>   PAÍSES DISPONIBLES PARA SIMULACIÓN   </>');
        $this->line('<fg=bright-cyan>========================================</>');
        $this->line('');
        $this->line('  <fg=green>VE</>  🇻🇪 Venezuela  — Repuestos de Moto (IVA 16% + IGTF 3%)');
        $this->line('  <fg=green>CO</>  🇨🇴 Colombia   — Ferretería Bogotá (IVA 19%)');
        $this->line('  <fg=green>MX</>  🇲🇽 México     — Miscelánea CDMX (IVA 16%)');
        $this->line('  <fg=green>EC</>  🇪🇨 Ecuador    — Farmacia Guayaquil (IVA 15%)');
        $this->line('');
        $this->line('  <fg=gray>Uso: php artisan pos:simulate-sale VE</>');
        $this->line('');
    }

    private function simular(string $pais): void
    {
        $config = $this->getConfigPais($pais);

        $this->line('');
        $this->line('<fg=bright-cyan;bg=black;options=bold>        PRUEBA DE CONEXIÓN TIENDA POS — MULTI-PAÍS LATAM        </>');
        $this->line('<fg=bright-cyan>===============================================================</>');
        $this->line(' ' . $config['emoji_bandera'] . ' ' . $config['nombre_comercio']);
        $this->line(' <fg=gray>' . $config['subtitulo'] . '</>');
        $this->line('<fg=bright-cyan>===============================================================');
        $this->line('');

        // 1) Configurar tienda para este país
        $this->configurarTienda($config);

        // 2) Crear productos del catálogo local
        $this->info('1️⃣ PREPARANDO CATÁLOGO Y INVENTARIO...');
        $variantes = $this->crearOCargarProductos($config);

        // 3) Configurar caja y sesión
        $this->info('2️⃣ CONFIGURANDO SESIÓN DE CAJA...');
        $caja = Caja::firstOrCreate(['nombre' => $config['nombre_caja']], ['activo' => true]);
        $usuario = User::first();
        $cliente = Cliente::firstOrCreate(
            ['numero_documento' => '00000000'],
            ['nombre' => 'CONSUMIDOR FINAL', 'tipo_cliente' => 'natural', 'activo' => true]
        );
        $almacen = Almacen::firstOrCreate(['nombre' => 'Depósito Principal'], ['tipo' => 'deposito', 'activo' => true]);

        $sesion = SesionCaja::firstOrCreate(
            ['caja_id' => $caja->id, 'estado' => 'abierta'],
            ['user_id' => $usuario->id, 'apertura_en' => now()]
        );

        $numeroTicket = $config['prefijo'] . '-' . time();
        $tasaLocal = $config['tasa_local'] ?? 1;
        $tasaRef = $pais === 'VE' ? $tasaLocal : 1;

        $venta = Venta::create([
            'numero_factura' => $numeroTicket,
            'caja_id' => $caja->id,
            'sesion_caja_id' => $sesion->id,
            'user_id' => $usuario->id,
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'moneda_factura' => $config['moneda_principal'],
            'estado' => 'borrador',
            'tipo_documento' => 'FAC',
            'tasa_referencia' => $tasaRef,
        ]);
        $this->line("   <fg=green>✓</> Ticket creado: {$numeroTicket}");

        // 4) Agregar items
        $this->info("\n3️⃣ ESCANEANDO PRODUCTOS...");
        $tasaConversionProd = ($config['moneda_principal'] === 'USD' && $pais === 'VE') ? 1.0 : 1.0;

        foreach ($config['productos'] as $prod) {
            $var = $variantes[$prod['sku']] ?? null;
            if (!$var) continue;

            $venta = $this->posService->agregarItemVenta(
                $venta, $var, $prod['cantidad'],
                $prod['precio'], $tasaConversionProd, 0, $prod['iva_pct']
            );

            $tipo = $prod['iva_pct'] > 0 ? '<fg=red>(G)</>' : '<fg=green>(E)</>';
            $precioFmt = $config['simbolo_moneda'] . number_format($prod['precio'], $config['decimales']);
            $this->line("   🛒  {$prod['cantidad']} {$prod['unidad']}  {$prod['nombre']}  @ {$precioFmt}  {$tipo}");
        }

        $venta->refresh();
        $this->line("   <fg=bright-yellow;options=bold>=> Subtotal + Impuestos: {$config['simbolo_moneda']}" . number_format($venta->total, $config['decimales']) . '</>');

        // 5) Procesar pago
        $this->info("\n4️⃣ PROCESANDO PAGO Y CIERRE FISCAL...");
        $this->procesarPago($venta, $config, $pais, $tasaLocal);

        $venta = $this->posService->procesarCobroFactura($venta);
        $venta->refresh();

        // 6) Imprimir ticket
        $this->imprimirTicket($venta, $config, $pais, $tasaLocal);
    }

    private function getConfigPais(string $pais): array
    {
        return match($pais) {
            'VE' => [
                'pais' => 'VE',
                'nombre_comercio' => 'INVERSIONES MOTO REPUESTOS 2026 C.A.',
                'subtitulo' => 'Repuestos de Motocicleta — Venezuela (IVA 16% + IGTF 3% sobre divisas)',
                'emoji_bandera' => '🏍️',
                'rif' => 'J-98765432-1',
                'direccion' => 'Av. Principal de El Valle, Caracas, Dtto. Capital',
                'telefono' => '+58 212-5551234',
                'nombre_caja' => 'Caja 1 - Taller',
                'prefijo' => 'MOTO',
                'moneda_principal' => 'USD',
                'moneda_secundaria' => 'VES',
                'simbolo_moneda' => '$',
                'simbolo_secundario' => 'Bs',
                'decimales' => 2,
                'iva_pct' => 16,
                'tiene_igtf' => true,
                'tasa_local' => TasaCambio::where('moneda_destino', 'VES')->where('activa', true)->value('tasa') ?? 592.51,
                'es_agente_igtf' => true,
                'categoria' => 'Repuestos Moto',
                'productos' => [
                    ['sku' => 'MOTO-CAUCHO-18', 'nombre' => 'Caucho Trasero 18" Bera SBR', 'precio' => 45.00, 'iva_pct' => 16, 'cantidad' => 1, 'unidad' => 'und'],
                    ['sku' => 'MOTO-TRIPA-18', 'nombre' => 'Tripa 18" Reforzada', 'precio' => 18.00, 'iva_pct' => 16, 'cantidad' => 1, 'unidad' => 'und'],
                    ['sku' => 'MOTO-BAT-12V', 'nombre' => 'Batería Seca 12V 7Ah', 'precio' => 25.00, 'iva_pct' => 16, 'cantidad' => 1, 'unidad' => 'und'],
                    ['sku' => 'MOTO-KIT-ARR', 'nombre' => 'Kit de Arrastre Bera/Horse', 'precio' => 35.00, 'iva_pct' => 16, 'cantidad' => 1, 'unidad' => 'kit'],
                    ['sku' => 'MOTO-ACEITE-MOT', 'nombre' => 'Aceite Motul 20W50 4T 1L', 'precio' => 8.00, 'iva_pct' => 16, 'cantidad' => 2, 'unidad' => 'lt'],
                    ['sku' => 'MOTO-BUJIA-NGK', 'nombre' => 'Bujía NGK Original', 'precio' => 4.00, 'iva_pct' => 16, 'cantidad' => 1, 'unidad' => 'und'],
                    ['sku' => 'MOTO-PASTILLAS', 'nombre' => 'Pastillas de Freno Delantero', 'precio' => 12.00, 'iva_pct' => 16, 'cantidad' => 1, 'unidad' => 'set'],
                ],
            ],
            'CO' => [
                'pais' => 'CO',
                'nombre_comercio' => 'FERRETERÍA EL TORNILLO S.A.S.',
                'subtitulo' => 'Materiales de Construcción — Bogotá, Colombia (IVA 19%)',
                'emoji_bandera' => '🏗️',
                'rif' => '900.123.456-7',
                'direccion' => 'Carrera 50 #25-30, Bogotá D.C.',
                'telefono' => '+57 601-5551234',
                'nombre_caja' => 'Caja 1 - Mostrador',
                'prefijo' => 'FERRE',
                'moneda_principal' => 'COP',
                'moneda_secundaria' => null,
                'simbolo_moneda' => '$',
                'simbolo_secundario' => null,
                'decimales' => 0,
                'iva_pct' => 19,
                'tiene_igtf' => false,
                'tasa_local' => 1,
                'es_agente_igtf' => false,
                'categoria' => 'Ferretería',
                'productos' => [
                    ['sku' => 'FER-CEM-50KG', 'nombre' => 'Saco Cemento Argos 50kg', 'precio' => 33500, 'iva_pct' => 19, 'cantidad' => 10, 'unidad' => 'sac'],
                    ['sku' => 'FER-CAB-12M', 'nombre' => 'Cabilla 1/2" x 6m', 'precio' => 28000, 'iva_pct' => 19, 'cantidad' => 12, 'unidad' => 'und'],
                    ['sku' => 'FER-PINT-BLAN', 'nombre' => 'Pintura Blanfix 4 Gal', 'precio' => 89000, 'iva_pct' => 19, 'cantidad' => 2, 'unidad' => 'gal'],
                    ['sku' => 'FER-MART-STAN', 'nombre' => 'Martillo Stanley 16oz', 'precio' => 35000, 'iva_pct' => 19, 'cantidad' => 1, 'unidad' => 'und'],
                    ['sku' => 'FER-CINTA-5M', 'nombre' => 'Cinta Métrica 5m Stanley', 'precio' => 18000, 'iva_pct' => 19, 'cantidad' => 1, 'unidad' => 'und'],
                ],
            ],
            'MX' => [
                'pais' => 'MX',
                'nombre_comercio' => 'MISCELÁNEA DON JUAN S.A. DE C.V.',
                'subtitulo' => 'Abarrotes y Víveres — Ciudad de México (IVA 16%, alimentos 0%)',
                'emoji_bandera' => '🛒',
                'rif' => 'ABCD123456XYZ',
                'direccion' => 'Av. Insurgentes Sur 1234, CDMX',
                'telefono' => '+52 55-5551234',
                'nombre_caja' => 'Caja 1 - Mostrador',
                'prefijo' => 'MISC',
                'moneda_principal' => 'MXN',
                'moneda_secundaria' => null,
                'simbolo_moneda' => '$',
                'simbolo_secundario' => null,
                'decimales' => 2,
                'iva_pct' => 16,
                'tiene_igtf' => false,
                'tasa_local' => 1,
                'es_agente_igtf' => false,
                'categoria' => 'Abarrotes',
                'productos' => [
                    ['sku' => 'MX-COCA-2L', 'nombre' => 'Coca-Cola 2L Original', 'precio' => 40.00, 'iva_pct' => 16, 'cantidad' => 2, 'unidad' => 'und'],
                    ['sku' => 'MX-TORT-1KG', 'nombre' => 'Tortillas de Maíz 1kg', 'precio' => 32.00, 'iva_pct' => 0, 'cantidad' => 3, 'unidad' => 'kg'],
                    ['sku' => 'MX-PAN-BIM', 'nombre' => 'Pan Bimbo Grande', 'precio' => 65.00, 'iva_pct' => 0, 'cantidad' => 1, 'unidad' => 'und'],
                    ['sku' => 'MX-LECHE-LALA', 'nombre' => 'Leche Lala Entera 1L', 'precio' => 28.00, 'iva_pct' => 0, 'cantidad' => 2, 'unidad' => 'lt'],
                    ['sku' => 'MX-HUEVOS-12', 'nombre' => 'Huevos Blancos 12pz', 'precio' => 52.00, 'iva_pct' => 0, 'cantidad' => 1, 'unidad' => 'doc'],
                    ['sku' => 'MX-SABRITAS', 'nombre' => 'Sabritas Original 45g', 'precio' => 18.00, 'iva_pct' => 16, 'cantidad' => 3, 'unidad' => 'und'],
                ],
            ],
            'EC' => [
                'pais' => 'EC',
                'nombre_comercio' => 'FARMACIA SALUD Y VIDA C.A.',
                'subtitulo' => 'Medicamentos y Salud — Guayaquil, Ecuador (IVA 15%, medicamentos 0%)',
                'emoji_bandera' => '💊',
                'rif' => '0912345678001',
                'direccion' => 'Av. 9 de Octubre 1234, Guayaquil',
                'telefono' => '+593 4-5551234',
                'nombre_caja' => 'Caja 1 - Mostrador',
                'prefijo' => 'FAR',
                'moneda_principal' => 'USD',
                'moneda_secundaria' => null,
                'simbolo_moneda' => '$',
                'simbolo_secundario' => null,
                'decimales' => 2,
                'iva_pct' => 15,
                'tiene_igtf' => false,
                'tasa_local' => 1,
                'es_agente_igtf' => false,
                'categoria' => 'Farmacia',
                'productos' => [
                    ['sku' => 'EC-ACETA-500', 'nombre' => 'Acetaminofén 500mg x10', 'precio' => 1.50, 'iva_pct' => 0, 'cantidad' => 2, 'unidad' => 'bls'],
                    ['sku' => 'EC-AMOX-500', 'nombre' => 'Amoxicilina 500mg x10', 'precio' => 3.20, 'iva_pct' => 0, 'cantidad' => 2, 'unidad' => 'bls'],
                    ['sku' => 'EC-IBU-400', 'nombre' => 'Ibuprofeno 400mg x10', 'precio' => 1.80, 'iva_pct' => 0, 'cantidad' => 2, 'unidad' => 'bls'],
                    ['sku' => 'EC-VITC', 'nombre' => 'Vitamina C 1g x10 eferv.', 'precio' => 4.50, 'iva_pct' => 15, 'cantidad' => 1, 'unidad' => 'tub'],
                    ['sku' => 'EC-SUERO-ORAL', 'nombre' => 'Suero Oral Litro', 'precio' => 2.30, 'iva_pct' => 15, 'cantidad' => 1, 'unidad' => 'lt'],
                    ['sku' => 'EC-ALCOHOL', 'nombre' => 'Alcohol 70% 500ml', 'precio' => 1.20, 'iva_pct' => 15, 'cantidad' => 2, 'unidad' => 'und'],
                ],
            ],
        };
    }

    private function configurarTienda(array $config): void
    {
        $tienda = Tienda::first();
        if (!$tienda) {
            $tienda = Tienda::create([
                'rif' => $config['rif'],
                'razon_social' => $config['nombre_comercio'],
                'nombre_comercial' => $config['nombre_comercio'],
                'direccion' => $config['direccion'],
                'telefono' => $config['telefono'],
                'moneda_base' => $config['moneda_principal'],
                'zona_horaria' => $config['pais'] === 'VE' ? 'America/Caracas' : ($config['pais'] === 'CO' ? 'America/Bogota' : ($config['pais'] === 'MX' ? 'America/Mexico_City' : 'America/Guayaquil')),
                'es_agente_igtf' => $config['es_agente_igtf'],
                'pais' => $config['pais'],
            ]);
        } else {
            $tienda->update([
                'rif' => $config['rif'],
                'razon_social' => $config['nombre_comercio'],
                'nombre_comercial' => $config['nombre_comercio'],
                'direccion' => $config['direccion'],
                'telefono' => $config['telefono'],
                'moneda_base' => $config['moneda_principal'],
                'es_agente_igtf' => $config['es_agente_igtf'],
                'pais' => $config['pais'],
            ]);
        }
    }

    private function crearOCargarProductos(array $config): array
    {
        $categoria = CategoriaProducto::firstOrCreate(
            ['slug' => strtolower(str_replace(' ', '-', $config['categoria']))],
            ['nombre' => $config['categoria'], 'nivel' => 1, 'ruta' => strtolower(str_replace(' ', '-', $config['categoria']))]
        );

        $unidad = Unidad::where('abreviatura', 'und')->first() ?? Unidad::first();

        // 🛟 FIX: Primero quitar es_defecto=true de TODOS los impuestos existentes
        // Esto evita la violación del índice único parcial idx_impuesto_defecto
        Impuesto::where('es_defecto', true)->update(['es_defecto' => false]);

        // Ahora sí crear/marcar el impuesto del país actual como default
        $impuestoGravado = Impuesto::firstOrCreate(
            ['nombre' => "IVA {$config['iva_pct']}%"],
            ['porcentaje' => $config['iva_pct'], 'tipo' => 'iva', 'aplica_a' => 'ambos', 'es_defecto' => false, 'activo' => true]
        );
        $impuestoGravado->update(['es_defecto' => true]);

        $impuestoExento = Impuesto::firstOrCreate(
            ['nombre' => 'Exento'],
            ['porcentaje' => 0, 'tipo' => 'exento', 'aplica_a' => 'ambos', 'es_defecto' => false, 'activo' => true]
        );

        $variantes = [];
        foreach ($config['productos'] as $prod) {
            $producto = Producto::updateOrCreate(
                ['codigo_sku' => $prod['sku']],
                [
                    'categoria_id' => $categoria->id,
                    'unidad_id' => $unidad->id,
                    'impuesto_id' => $prod['iva_pct'] > 0 ? $impuestoGravado->id : $impuestoExento->id,
                    'moneda_precio' => $config['moneda_principal'],
                    'nombre' => $prod['nombre'],
                    'costo_promedio' => $prod['precio'] * 0.7,
                    'margen_pct' => 30,
                    'atributos' => '{}',
                    'activo' => true,
                ]
            );
            $producto->update([
                'impuesto_id' => $prod['iva_pct'] > 0 ? $impuestoGravado->id : $impuestoExento->id,
                'moneda_precio' => $config['moneda_principal'],
            ]);

            $variante = VarianteProducto::updateOrCreate(
                ['codigo_barra' => $prod['sku'] . '-001'],
                [
                    'producto_id' => $producto->id,
                    'descripcion' => $prod['nombre'],
                    'factor_unidad' => 1,
                    'atributos' => '{}',
                    'activo' => true,
                ]
            );

            $variantes[$prod['sku']] = $variante;
        }
        return $variantes;
    }

    private function procesarPago(Venta $venta, array $config, string $pais, float $tasaLocal): void
    {
        $metodoDivisa = MetodoPago::firstOrCreate(
            ['nombre' => 'Efectivo ' . $config['moneda_principal']],
            ['tipo' => 'efectivo', 'moneda' => $config['moneda_principal'], 'grava_igtf' => $pais === 'VE', 'activo' => true]
        );

        // En Venezuela, pagar 50% en USD y 50% en VES (pago mixto con IGTF)
        // En otros países, pagar todo en la moneda principal
        if ($pais === 'VE') {
            $montoEfectivoUsd = 50.00;
            $this->posService->registrarPagoConIgtf($venta, $metodoDivisa, $montoEfectivoUsd, 1.0);
            $igtf = round($montoEfectivoUsd * 0.03, 2);
            $this->line("   💳 Efectivo USD: \${$montoEfectivoUsd} <fg=magenta>(+ IGTF 3% = \${$igtf})</>");

            $venta->refresh();
            $resto = $venta->total - $venta->pagos()->sum('monto_en_factura') - ($igtf * 1.0);
            $restoVES = round($resto * $tasaLocal, 2);

            $metodoLocal = MetodoPago::firstOrCreate(
                ['nombre' => 'Pago Móvil VES'],
                ['tipo' => 'pago_movil', 'moneda' => 'VES', 'grava_igtf' => false, 'requiere_referencia' => true, 'activo' => true]
            );
            $this->posService->registrarPagoConIgtf($venta, $metodoLocal, $restoVES, 1 / $tasaLocal);
            $this->line("   📱 Pago Móvil: Bs. " . number_format($restoVES, 2) . " <fg=blue>(Tasa: {$tasaLocal})</>");
        } else {
            // Pago único en moneda local
            $montoTotal = $venta->subtotal + $venta->impuesto_iva;
            $this->posService->registrarPagoConIgtf($venta, $metodoDivisa, $montoTotal, 1.0);
            $this->line("   💵 Efectivo: {$config['simbolo_moneda']}" . number_format($montoTotal, $config['decimales']));
        }
    }

    private function imprimirTicket(Venta $venta, array $config, string $pais, float $tasaLocal): void
    {
        $etiquetaId = $this->etiquetaIdPais($pais);
        $this->line("\n");
        $this->line('<fg=black;bg=white>                                                                </>');
        $this->line('<fg=black;bg=white;options=bold>              ' . str_pad(substr($config['nombre_comercio'], 0, 60), 60, ' ', STR_PAD_BOTH) . '              </>');
        $this->line('<fg=black;bg=white>                ' . str_pad("{$etiquetaId}: {$config['rif']}", 60, ' ', STR_PAD_BOTH) . '                </>');
        $this->line('<fg=black;bg=white>            ' . str_pad(substr($config['direccion'], 0, 60), 60, ' ', STR_PAD_BOTH) . '            </>');
        $this->line('<fg=black;bg=white>                                                                </>');
        $this->line('<fg=black;bg=white>  Factura: ' . str_pad($venta->numero_factura, 52) . '  </>');
        $this->line('<fg=black;bg=white>  Fecha:   ' . str_pad(now()->format('d/m/Y H:i'), 52) . '  </>');
        $this->line('<fg=black;bg=white>  Cajero:  ' . str_pad((User::first()->name ?? 'Admin POS'), 52) . '  </>');
        $this->line('<fg=black;bg=white>----------------------------------------------------------------</>');

        $itemsTable = [];
        foreach ($venta->items as $item) {
            $nombre = substr($item->variante->producto->nombre, 0, 30);
            $tipo = $item->impuesto_monto > 0 ? '(G)' : '(E)';
            $precio = $config['simbolo_moneda'] . number_format($item->precio_en_factura, $config['decimales']);
            $total = $config['simbolo_moneda'] . number_format($item->total_linea, $config['decimales']);
            $itemsTable[] = [$item->cantidad, $nombre, $precio, $tipo, $total];
        }
        $this->table(['Cant', 'Descripción', 'P.Unit', 'T', 'Total'], $itemsTable);

        // Cálculo de exento vs gravable
        $exento = 0;
        $gravable = 0;
        foreach ($venta->items as $item) {
            $baseLinea = ($item->precio_en_factura * $item->cantidad) - ($item->precio_en_factura * $item->cantidad * ($item->descuento_pct / 100));
            if ($item->impuesto_monto > 0) {
                $gravable += $baseLinea;
            } else {
                $exento += $baseLinea;
            }
        }

        $this->line('<fg=black;bg=white>----------------------------------------------------------------</>');
        if ($exento > 0) {
            $this->line(sprintf("<fg=black;bg=white>  EXENTO:                                      %s %7s   </>", $config['simbolo_moneda'], number_format($exento, $config['decimales'])));
        }
        $this->line(sprintf("<fg=black;bg=white>  BASE IMPONIBLE (GRAVABLE):                   %s %7s   </>", $config['simbolo_moneda'], number_format($gravable, $config['decimales'])));
        $this->line(sprintf("<fg=black;bg=white>  IVA (%d%%):                                    %s %7s   </>", $config['iva_pct'], $config['simbolo_moneda'], number_format($venta->impuesto_iva, $config['decimales'])));

        if ($pais === 'VE' && $venta->impuesto_igtf > 0) {
            $this->line(sprintf("<fg=black;bg=white>  IGTF (3%% sobre Divisa):                       %s %7s   </>", $config['simbolo_moneda'], number_format($venta->impuesto_igtf, $config['decimales'])));
        }

        $this->line('<fg=black;bg=white>----------------------------------------------------------------</>');
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL A PAGAR:                                %s %7s   </>", $config['simbolo_moneda'], number_format($venta->total, $config['decimales'])));

        // Línea de conversión a moneda secundaria (solo VE)
        if ($pais === 'VE') {
            $totalSec = $venta->total * $tasaLocal;
            $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL EN BS (Tasa %.2f):                  Bs %9s   </>", $tasaLocal, number_format($totalSec, 2)));
        }

        $this->line('<fg=black;bg=white>----------------------------------------------------------------</>');
        $this->line('<fg=black;bg=white>  MEDIOS DE PAGO:                                               </>');
        foreach ($venta->pagos as $pago) {
            $nombre = $pago->metodoPago->nombre;
            $monto = number_format($pago->monto_pago, $config['decimales']);
            $this->line(sprintf("<fg=black;bg=white>  * %-30s %14s %3s   </>", $nombre, $monto, $pago->moneda_pago));
        }
        $this->line('<fg=black;bg=white>                                                                </>');
        $this->line('');

        $this->info('✨ ¡Venta simulada completada! | Auditoría ' . $this->nombreEntidadFiscal($pais) . ': OK');
        $this->line('');
    }

    private function etiquetaIdPais(string $pais): string
    {
        return match($pais) {
            'VE' => 'RIF',
            'CO' => 'NIT',
            'MX' => 'RFC',
            'EC' => 'RUC',
            default => 'ID',
        };
    }

    private function nombreEntidadFiscal(string $pais): string
    {
        return match($pais) {
            'VE' => 'SENIAT',
            'CO' => 'DIAN',
            'MX' => 'SAT',
            'EC' => 'SRI',
            default => 'FISCAL',
        };
    }
}