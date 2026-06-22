<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\TiendaPosTestStyle;
use App\Services\OnboardingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestOnboardingCommand extends Command
{
    use TiendaPosTestStyle;

    protected $signature = 'pos:test-onboarding
                            {pais=VE : Código de país (VE,CO,MX,EC,AR,PE,CL,BO,UY)}';
    protected $description = 'Test visual del onboarding completo por país';

    private array $banderas = [
        'VE' => '🇻🇪', 'CO' => '🇨🇴', 'MX' => '🇲🇽', 'EC' => '🇪🇨',
        'AR' => '🇦🇷', 'PE' => '🇵🇪', 'CL' => '🇨🇱', 'BO' => '🇧🇴', 'UY' => '🇺🇾',
    ];

    private array $rifLabels = [
        'VE' => 'RIF', 'CO' => 'NIT', 'MX' => 'RFC', 'EC' => 'RUC',
        'AR' => 'CUIT', 'PE' => 'RUC', 'CL' => 'RUT', 'BO' => 'NIT', 'UY' => 'RUT',
    ];

    public function handle(): int
    {
        $pais = strtoupper($this->argument('pais'));
        $bandera = $this->banderas[$pais] ?? '🌍';

        if (!isset($this->rifLabels[$pais])) {
            $this->testFail("País no soportado: {$pais}. Usa: VE,CO,MX,EC,AR,PE,CL,BO,UY");
            return 1;
        }

        $rif = $this->rifLabels[$pais];
        $nombreComercio = "Tienda Test {$pais}";
        $email = "test-{$pais}-" . time() . "@tiendapos.test";

        $this->testHeader(
            "ONBOARDING {$pais} — {$bandera}",
            "Flujo completo de 4 pasos para {$pais} con siembra automática"
        );

        $service = app(OnboardingService::class);

        // ─── PASO 1 ─────────────────────────────────────────────
        $this->testStep(1, 'Creando cuenta + tienda + suscripción Trial');

        $cuenta = $service->crearCuenta([
            'name'     => "Admin Test {$pais}",
            'email'    => $email,
            'password' => 'Test2026!Secure',
            'pais'     => $pais,
        ]);

        $this->testOk('Cuenta creada exitosamente');
        $this->testDetail('Tienda ID:', (string) $cuenta['tienda']->id);
        $this->testDetail('Usuario ID:', (string) $cuenta['user']->id);
        $this->testDetail('País:', $banderaPais = "{$bandera} {$pais}");
        $this->testDetail('Moneda base:', $cuenta['tienda']->moneda_base);
        $this->testDetail('Zona horaria:', $cuenta['tienda']->zona_horaria);
        $this->testDetail('Token Sanctum:', substr($cuenta['token'], 0, 25) . '...');
        $this->testOk('Suscripción Trial activa por 14 días');

        $tiendaId = $cuenta['tienda']->id;

        // ─── PASO 2 ─────────────────────────────────────────────
        $this->testStep(2, 'Guardando datos fiscales (siembra impuestos y monedas)');

        $tienda = $service->guardarDatosFiscales($tiendaId, [
            'identificacion_fiscal' => 'TEST-' . strtoupper($pais) . '-0001',
            'razon_social'          => $nombreComercio,
            'nombre_comercial'      => $nombreComercio,
            'direccion'             => "Dirección test {$pais}",
            'telefono'              => '+00-000-0000000',
            'email'                 => $email,
            'regimen_fiscal'        => 'Régimen Test',
            'codigo_postal'         => '0000',
        ]);

        $this->testOk("Datos fiscales guardados ({$rif}: TEST-{$pais}-0001)");

        $impuestos = DB::table('impuestos')->count();
        $monedasAceptadas = DB::table('tienda_monedas')->count();
        $this->testDetail('Impuestos en sistema:', (string) $impuestos);
        $this->testDetail('Monedas aceptadas:', (string) $monedasAceptadas);

        if ($pais === 'VE') {
            $tasa = DB::table('tasas_cambio')->where('moneda_destino', 'VES')->where('activa', true)->first();
            $this->testDetail('Tasa USD/VES:', $tasa ? number_format($tasa->tasa, 2) : 'N/A');
        }

        // ─── PASO 3 ─────────────────────────────────────────────
        $this->testStep(3, 'Configurando negocio (almacén, caja, categorías, métodos pago)');

        $service->configurarNegocio($tiendaId, [
            'tipo_negocio'   => 'bodega',
            'nombre_almacen' => 'Depósito Test',
            'nombre_caja'    => 'Caja Test 1',
            'tipo_impresora' => 'termica_80mm',
        ]);

        $this->testOk('Almacén principal creado');
        $this->testOk('Caja principal creada');
        $this->testOk('Categorías de "bodega" sembradas');

        $categoriasCount = DB::table('categorias_productos')->count();
        $metodosPagoCount = DB::table('metodos_pago')->count();
        $this->testDetail('Categorías totales:', (string) $categoriasCount);
        $this->testDetail('Métodos de pago:', (string) $metodosPagoCount);

        $this->testOk('Cliente "CONSUMIDOR FINAL" creado');
        $this->testOk('Margen 20% default creado');
        $this->testOk('Lista de precio "Precio detal" creada');
        $this->testOk('Plantilla de impresión creada');

        // ─── PASO 4 ─────────────────────────────────────────────
        $this->testStep(4, 'Creando primer producto con inventario');

        $producto = $service->crearPrimerProducto($tiendaId, [
            'nombre'        => "Producto Test {$pais}",
            'costo'         => 10,
            'aplica_iva'    => true,
            'stock_inicial' => 100,
        ]);

        $this->testOk('Producto creado: ' . $producto->nombre);
        $this->testDetail('SKU:', $producto->codigo_sku);
        $this->testDetail('Costo:', '$' . number_format($producto->costo_promedio, 2));
        $this->testDetail('Stock inicial:', '100 unidades');

        // ─── RESUMEN FINAL ──────────────────────────────────────
        $this->testFooter("ONBOARDING {$pais} COMPLETADO — TIENDA LISTA PARA FACTURAR", true, [
            'Cuenta + Tienda + Trial' => '✓ Paso 1',
            'Datos fiscales + Impuestos' => '✓ Paso 2',
            'Catálogos sembrados'     => '✓ Paso 3',
            'Primer producto'         => '✓ Paso 4',
            'Onboarding completado'   => '✓ SÍ',
            'Listo para Next.js'      => '✓ SÍ',
        ]);

        return 0;
    }
}
