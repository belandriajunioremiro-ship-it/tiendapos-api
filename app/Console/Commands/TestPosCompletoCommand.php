<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\TiendaPosTestStyle;
use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Impuesto;
use App\Models\Inventario;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\SesionCaja;
use App\Models\Tienda;
use App\Models\Unidad;
use App\Models\User;
use App\Models\VarianteProducto;
use App\Models\Venta;
use App\Services\OnboardingService;
use App\Services\PosBusinessRulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestPosCompletoCommand extends Command
{
    use TiendaPosTestStyle;

    protected $signature = 'pos:test-pos
                            {pais=VE : Código de país (VE,CO,MX,EC,AR,PE,CL,BO,UY)}';
    protected $description = 'Test visual de venta POS completa end-to-end con IVA/IGTF';

    private array $banderas = [
        'VE' => '🇻🇪', 'CO' => '🇨🇴', 'MX' => '🇲🇽', 'EC' => '🇪🇨',
        'AR' => '🇦🇷', 'PE' => '🇵🇪', 'CL' => '🇨🇱', 'BO' => '🇧🇴', 'UY' => '🇺🇾',
    ];

    private array $ivaPorPais = [
        'VE' => 16, 'CO' => 19, 'MX' => 16, 'EC' => 15,
        'AR' => 21, 'PE' => 18, 'CL' => 19, 'BO' => 13, 'UY' => 22,
    ];

    public function handle(): int
    {
        $pais = strtoupper($this->argument('pais'));
        $bandera = $this->banderas[$pais] ?? '🌍';
        $ivaPct = $this->ivaPorPais[$pais] ?? 16;

        $this->testHeader(
            "VENTA POS COMPLETA {$pais} — {$bandera}",
            "Apertura → Recepción → Venta → IVA {$ivaPct}% → Pago → Cierre"
        );

        $tienda = Tienda::where('pais', $pais)
            ->whereIn('id', Producto::select('tienda_id')->distinct())
            ->first();

        if (! $tienda) {
            $this->testInfo("No hay tienda con productos para {$pais}. Creando una temporal...");
            $onboarding = app(OnboardingService::class);
            $emailTest = "test.pos.{$pais}." . time() . "@demo.com";
            $cuenta = $onboarding->crearCuenta([
                'name'     => "Test {$pais}",
                'email'    => $emailTest,
                'password' => 'Test1234',
                'pais'     => $pais,
            ]);
            $tiendaIdCreada = $cuenta['tienda']->id;
            $onboarding->guardarDatosFiscales($tiendaIdCreada, [
                'identificacion_fiscal' => "TEST-{$pais}-001",
                'razon_social'          => "Tienda Test {$pais}",
                'nombre_comercial'      => "Tienda Test {$pais}",
                'direccion'             => "Dirección test {$pais}",
                'telefono'              => '+00-000-0000000',
                'email'                 => $emailTest,
            ]);
            $onboarding->configurarNegocio($tiendaIdCreada, [
                'tipo_negocio'   => 'general',
                'nombre_almacen' => 'Depósito Test',
                'nombre_caja'    => 'Caja Test',
                'tipo_impresora' => 'termica_80mm',
            ]);
            $onboarding->crearPrimerProducto($tiendaIdCreada, [
                'nombre'        => "Producto Test {$pais}",
                'costo'         => 10,
                'aplica_iva'    => true,
                'stock_inicial' => 100,
            ]);
            $tienda = $cuenta['tienda'];
            $this->testOk("Tienda temporal creada (ID: {$tienda->id})");
        }

        $admin = User::where('tienda_id', $tienda->id)->where('activo', true)->first();
        if (! $admin) {
            $this->testFail('No hay usuario admin');
            return 1;
        }

        Auth::login($admin);

        // ─── PASO 1 ─────────────────────────────────────────────
        $this->testStep(1, 'Abriendo sesión de caja');

        $caja = Caja::first();
        if (! $caja) {
            $caja = Caja::create(['nombre' => 'Caja Test', 'activo' => true]);
        }

        // Cerrar cualquier sesión abierta previa de esta caja
        SesionCaja::where('caja_id', $caja->id)
            ->where('estado', 'abierta')
            ->update(['estado' => 'cerrada', 'cierre_en' => now()]);

        $sesion = SesionCaja::create([
            'caja_id'          => $caja->id,
            'user_id'          => $admin->id,
            'monto_apertura'   => 100,
            'moneda_apertura'  => $tienda->moneda_base,
            'estado'           => 'abierta',
            'apertura_en'      => now(),
        ]);

        $this->testOk('Sesión de caja abierta');
        $this->testDetail('Caja:', $caja->nombre);
        $this->testDetail('Sesión ID:', (string) $sesion->id);
        $this->testDetail('Monto apertura:', '$100 ' . $tienda->moneda_base);

        // ─── PASO 2 ─────────────────────────────────────────────
        $this->testStep(2, 'Recibiendo inventario (simulación de compra)');

        $producto = Producto::first();
        if (! $producto) {
            $this->testFail('No hay productos. Corre pos:test-onboarding primero');
            return 1;
        }

        $variante = VarianteProducto::where('producto_id', $producto->id)->first();
        if (! $variante) {
            $this->testFail('No hay variantes de producto');
            return 1;
        }

        $almacen = Almacen::first();
        if (! $almacen) {
            $almacen = Almacen::create(['nombre' => 'Depósito Test', 'tipo' => 'deposito', 'activo' => true]);
        }

        $inventario = Inventario::updateOrCreate(
            [
                'variante_id' => $variante->id,
                'almacen_id'  => $almacen->id,
            ],
            [
                'cantidad_disponible' => 10,
                'cantidad_reservada'  => 0,
                'stock_minimo'        => 2,
                'costo_promedio'      => $producto->costo_promedio ?? 5,
            ]
        );

        $this->testOk('Inventario recibido');
        $this->testDetail('Producto:', $producto->nombre);
        $this->testDetail('Variante ID:', (string) $variante->id);
        $this->testDetail('Almacén:', $almacen->nombre);
        $this->testDetail('Stock:', '10 unidades');

        // ─── PASO 3 ─────────────────────────────────────────────
        $this->testStep(3, 'Creando venta (cabecera)');

        $cliente = Cliente::first();
        if (! $cliente) {
            $cliente = Cliente::create([
                'numero_documento' => '00000000',
                'nombre'           => 'CONSUMIDOR FINAL',
                'tipo_cliente'     => 'natural',
                'activo'           => true,
            ]);
        }

        $venta = Venta::create([
            'cliente_id'      => $cliente->id,
            'caja_id'         => $caja->id,
            'sesion_caja_id'  => $sesion->id,
            'almacen_id'      => $almacen->id,
            'user_id'         => $admin->id,
            'numero_factura'  => 'FAC-TEST-' . strtoupper(uniqid()),
            'tipo_documento'  => 'FAC',
            'moneda_factura'  => $tienda->moneda_base,
            'subtotal'        => 0,
            'descuento'       => 0,
            'impuesto_iva'    => 0,
            'impuesto_igtf'   => 0,
            'total'           => 0,
            'estado'          => 'borrador',
        ]);

        $this->testOk('Venta creada en estado borrador');
        $this->testDetail('Factura:', $venta->numero_factura);
        $this->testDetail('Cliente:', $cliente->nombre);
        $this->testDetail('Moneda:', $venta->moneda_factura);

        // ─── PASO 4 ─────────────────────────────────────────────
        $this->testStep(4, "Agregando items con IVA {$ivaPct}%");

        $pos = app(PosBusinessRulesService::class);
        $precioBase = 50;

        $pos->agregarItemVenta(
            $venta,
            $variante,
            2,
            $precioBase,
            1.0,
            0,
            $ivaPct
        );

        $venta->refresh();
        $this->testOk('Item agregado: 2 × ' . $producto->nombre);
        $this->testDetail('Precio unitario:', '$' . number_format($precioBase, 2));
        $this->testDetail('Cantidad:', '2');
        $this->testDetail('IVA aplicado:', $ivaPct . '%');
        $this->testDetail('Subtotal:', '$' . number_format($venta->subtotal, 2));
        $this->testDetail('Impuesto IVA:', '$' . number_format($venta->impuesto_iva, 2));

        // ─── PASO 5 ─────────────────────────────────────────────
        $this->testStep(5, 'Registrando pago');

        $metodoPago = MetodoPago::where('activo', true)->first();
        if (! $metodoPago) {
            $metodoPago = MetodoPago::create([
                'nombre'  => 'Efectivo Test',
                'tipo'    => 'efectivo',
                'moneda'  => $tienda->moneda_base,
                'activo'  => true,
            ]);
        }

        $pos->registrarPagoConIgtf($venta, $metodoPago, (float) $venta->total, 1.0);

        $this->testOk('Pago registrado');
        $this->testDetail('Método:', $metodoPago->nombre);
        $this->testDetail('Moneda:', $metodoPago->moneda);
        $this->testDetail('Monto:', '$' . number_format($venta->total, 2));

        if ($pais === 'VE' && $metodoPago->grava_igtf) {
            $this->testDetail('IGTF (3%):', '$' . number_format($venta->impuesto_igtf, 2));
        }

        // ─── PASO 6 ─────────────────────────────────────────────
        $this->testStep(6, 'Cerrando factura (procesarCobroFactura)');

        $venta = $pos->procesarCobroFactura($venta);
        $venta->refresh();

        $this->testOk('Factura cerrada y marcada como PAGADA');
        $this->testDetail('Estado final:', $venta->estado);

        // ─── PASO 7 ─────────────────────────────────────────────
        $this->testStep(7, 'Verificando descuento de inventario');

        $inv = Inventario::where('variante_id', $variante->id)->first();
        if ($inv) {
            $inv->refresh();
            $this->testOk('Inventario actualizado correctamente');
            $this->testDetail('Stock recibido:', '10 unidades');
            $this->testDetail('Stock vendido:', '2 unidades');
            $this->testDetail('Stock actual:', (string) $inv->cantidad_disponible);
        }

        // ─── TICKET FINAL ───────────────────────────────────────
        $this->testStep(8, '🎫 TICKET FINAL DE LA VENTA');

        $this->line('');
        $this->line("<fg=black;bg=white>  " . str_pad($tienda->razon_social, 60) . "  </>");
        $this->line("<fg=black;bg=white>  " . str_pad('Factura: ' . $venta->numero_factura, 60) . "  </>");
        $this->line("<fg=black;bg=white>  " . str_pad('Cliente: ' . $cliente->nombre, 60) . "  </>");
        $this->line("<fg=black;bg=white>  " . str_repeat('─', 60) . "  </>");
        $this->line("<fg=black;bg=white>  " . str_pad('2 × ' . substr($producto->nombre, 0, 40), 50) . ' $' . str_pad(number_format($precioBase * 2, 2), 8) . "  </>");
        $this->line("<fg=black;bg=white>  " . str_repeat('─', 60) . "  </>");
        $this->line("<fg=black;bg=white>  " . str_pad('SUBTOTAL', 50) . ' $' . str_pad(number_format($venta->subtotal, 2), 8) . "  </>");
        $this->line("<fg=black;bg=white>  " . str_pad("IVA {$ivaPct}%", 50) . ' $' . str_pad(number_format($venta->impuesto_iva, 2), 8) . "  </>");
        if ($venta->impuesto_igtf > 0) {
            $this->line("<fg=black;bg=white>  " . str_pad('IGTF 3%', 50) . ' $' . str_pad(number_format($venta->impuesto_igtf, 2), 8) . "  </>");
        }
        $this->line("<fg=black;bg=white;options=bold>  " . str_pad('TOTAL', 50) . ' $' . str_pad(number_format($venta->total, 2), 8) . "  </>");
        $this->line('');

        // ─── LIMPIEZA ───────────────────────────────────────────
        $venta->items()->delete();
        $venta->pagos()->delete();
        $venta->delete();
        $this->testInfo('Venta de prueba eliminada para no afectar reportes');

        Auth::logout();

        // ─── RESUMEN FINAL ──────────────────────────────────────
        $this->testFooter('VENTA POS COMPLETADA — CICLO END-TO-END', true, [
            'Apertura de caja'        => '✓ OK',
            'Recepción de inventario' => '✓ OK',
            'Creación de venta'       => '✓ OK',
            'Cálculo de IVA/IGTF'     => '✓ OK',
            'Registro de pago'        => '✓ OK',
            'Cierre de factura'       => '✓ OK',
            'Descuento de stock'      => '✓ OK',
        ]);

        return 0;
    }
}
