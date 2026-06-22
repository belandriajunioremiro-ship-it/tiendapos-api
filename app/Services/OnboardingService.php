<?php

namespace App\Services;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\ConfiguracionImpresora;
use App\Models\Impuesto;
use App\Models\Inventario;
use App\Models\ListaPrecio;
use App\Models\MargenGanancia;
use App\Models\OnboardingPaso;
use App\Models\Plane;
use App\Models\PlantillasImpresion;
use App\Models\Producto;
use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\TiendaOnboarding;
use App\Models\Unidad;
use App\Models\User;
use App\Models\VarianteProducto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OnboardingService
{
    public function __construct(
        private TaxSeederService $taxSeeder,
        private CurrencySeederService $currencySeeder,
        private CatalogSeederService $catalogSeeder,
    ) {}

    public function crearCuenta(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tienda = Tienda::create([
                'pais'            => $data['pais'],
                'rif'             => 'TEMP-' . Str::random(8),
                'razon_social'    => 'Tienda en configuración',
                'moneda_base'     => $this->currencySeeder->monedaBase($data['pais']),
                'moneda_pivot_api'=> 'USD',
                'zona_horaria'    => $this->zonaHorariaPais($data['pais']),
                'es_agente_igtf'  => $data['pais'] === 'VE',
                'activo'          => true,
            ]);

            $user = User::create([
                'tienda_id' => $tienda->id,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'activo'    => true,
            ]);

            if (method_exists($user, 'assignRole')) {
                $user->assignRole('admin');
            }

            TiendaOnboarding::create([
                'tienda_id'    => $tienda->id,
                'paso_actual'  => 1,
                'completado'   => false,
                'metadata'     => '{}',
            ]);

            $plan = $this->obtenerPlanPorPais($data['pais']);
            $this->crearSuscripcionSegunPlan($tienda->id, $plan, $data['pais']);

            $token = $user->createToken('onboarding')->plainTextToken;
            $tienda->load('suscripcion.plan');

            return [
                'user'        => $user,
                'tienda'      => $tienda,
                'token'       => $token,
                'paso_actual' => 1,
            ];
        });
    }

    public function guardarDatosFiscales(int $tiendaId, array $data): Tienda
    {
        return DB::transaction(function () use ($tiendaId, $data) {
            $tienda = Tienda::findOrFail($tiendaId);

            $tienda->update([
                'rif'                 => $data['identificacion_fiscal'],
                'razon_social'        => $data['razon_social'],
                'nombre_comercial'    => $data['nombre_comercial'] ?? $data['razon_social'],
                'direccion'           => $data['direccion'],
                'telefono'            => $data['telefono'] ?? null,
                'email'               => $data['email'] ?? null,
                'regimen_fiscal'      => $data['regimen_fiscal'] ?? null,
                'actividad_economica' => $data['actividad_economica'] ?? null,
                'codigo_postal'       => $data['codigo_postal'] ?? null,
                'logo_url'            => $data['logo_url'] ?? null,
            ]);

            $this->taxSeeder->sembrar($tienda->pais, $tiendaId);
            $this->currencySeeder->sembrarMonedas($tienda->pais, $tiendaId);

            if ($tienda->pais === 'VE') {
                $this->currencySeeder->sembrarTasaInicialVES($tiendaId);
            }

            $this->avanzarPaso($tiendaId, 2);

            return $tienda->fresh();
        });
    }

    public function configurarNegocio(int $tiendaId, array $data): void
    {
        DB::transaction(function () use ($tiendaId, $data) {
            $tienda = Tienda::findOrFail($tiendaId);

            Almacen::firstOrCreate(
                ['nombre' => $data['nombre_almacen'] ?? 'Depósito Principal', 'tienda_id' => $tiendaId],
                ['tipo' => 'deposito', 'activo' => true]
            );

            Caja::firstOrCreate(
                ['nombre' => $data['nombre_caja'] ?? 'Caja 1', 'tienda_id' => $tiendaId],
                ['activo' => true]
            );

            $this->catalogSeeder->sembrarCategorias($data['tipo_negocio'] ?? 'general', $tiendaId);
            $this->catalogSeeder->sembrarMetodosPago($tienda->pais, $tiendaId);

            Cliente::firstOrCreate(
                ['numero_documento' => '00000000', 'tienda_id' => $tiendaId],
                ['nombre' => 'CONSUMIDOR FINAL', 'tipo_cliente' => 'natural', 'activo' => true]
            );

            MargenGanancia::firstOrCreate(
                ['nombre' => 'Margen estándar 20%', 'tienda_id' => $tiendaId],
                ['porcentaje' => 20, 'tipo' => 'sobre_costo', 'es_defecto' => true, 'activo' => true]
            );

            ListaPrecio::firstOrCreate(
                ['nombre' => 'Precio detal', 'tienda_id' => $tiendaId],
                ['tipo' => 'porcentaje_precio_base', 'valor' => 0, 'activo' => true]
            );

            $tipoPlantilla = $data['tipo_impresora'] ?? 'termica_80mm';
            PlantillasImpresion::firstOrCreate(
                ['tipo' => $tipoPlantilla, 'es_defecto' => true, 'tienda_id' => $tiendaId],
                [
                    'nombre'          => 'Plantilla ' . $tipoPlantilla,
                    'contenido_html'  => '<html><body><!-- template --></body></html>',
                    'activo'          => true,
                ]
            );

            ConfiguracionImpresora::create([
                'tienda_id'     => $tienda->id,
                'caja_id'       => Caja::where('tienda_id', $tiendaId)->first()->id,
                'nombre'        => 'Impresora principal',
                'tipo'          => $tipoPlantilla,
                'conexion'      => 'usb',
                'copias'        => 1,
                'imprime_logo'  => true,
                'activa'        => true,
            ]);

            $this->avanzarPaso($tiendaId, 3);
        });
    }

    public function crearPrimerProducto(int $tiendaId, array $data): Producto
    {
        return DB::transaction(function () use ($tiendaId, $data) {
            $tienda  = Tienda::findOrFail($tiendaId);
            $unidad  = Unidad::where('abreviatura', 'und')->firstOrFail();
            $impuesto = ($data['aplica_iva'] ?? true)
                ? Impuesto::where('es_defecto', true)->where('tienda_id', $tiendaId)->firstOrFail()
                : Impuesto::where('nombre', 'Exento')->where('tienda_id', $tiendaId)->firstOrFail();

            $categoriaId = $data['categoria_id']
                ?? CategoriaProducto::where('tienda_id', $tiendaId)->first()->id;
            $almacen     = Almacen::where('tienda_id', $tiendaId)->first();

            $producto = Producto::create([
                'tienda_id'      => $tiendaId,
                'categoria_id'   => $categoriaId,
                'unidad_id'      => $unidad->id,
                'impuesto_id'    => $impuesto->id,
                'moneda_precio'  => $tienda->moneda_base,
                'codigo_sku'     => $data['sku'] ?? 'PROD-' . Str::random(8),
                'nombre'         => $data['nombre'],
                'descripcion'    => $data['descripcion'] ?? null,
                'costo_promedio' => $data['costo'] ?? 0,
                'margen_pct'     => 20,
                'activo'         => true,
            ]);

            VarianteProducto::create([
                'tienda_id'    => $tiendaId,
                'producto_id'  => $producto->id,
                'codigo_barra' => $data['codigo_barra'] ?? 'PROD-' . Str::random(12),
                'descripcion'  => $producto->nombre,
                'factor_unidad'=> 1,
                'atributos'    => '{}',
                'activo'       => true,
            ]);

            Inventario::create([
                'tienda_id'            => $tiendaId,
                'variante_id'          => VarianteProducto::where('producto_id', $producto->id)->first()->id,
                'almacen_id'           => $almacen->id,
                'cantidad_disponible'  => $data['stock_inicial'] ?? 0,
                'cantidad_reservada'   => 0,
                'stock_minimo'         => 5,
                'costo_promedio'       => $data['costo'] ?? 0,
            ]);

            $this->completarOnboarding($tiendaId);

            return $producto;
        });
    }

    public function saltarPrimerProducto(int $tiendaId): void
    {
        $this->completarOnboarding($tiendaId);
    }

    public function obtenerEstado(int $tiendaId): array
    {
        $onboarding = TiendaOnboarding::where('tienda_id', $tiendaId)->firstOrFail();
        $pasos      = OnboardingPaso::orderBy('orden')->where('activo', true)->get();

        return [
            'paso_actual'      => $onboarding->paso_actual,
            'completado'       => $onboarding->completado,
            'fecha_completado' => $onboarding->fecha_completado,
            'pasos'            => $pasos,
        ];
    }

    private function avanzarPaso(int $tiendaId, int $paso): void
    {
        TiendaOnboarding::where('tienda_id', $tiendaId)->update([
            'paso_actual'    => $paso,
            'actualizado_en' => now(),
        ]);
    }

    private function completarOnboarding(int $tiendaId): void
    {
        TiendaOnboarding::where('tienda_id', $tiendaId)->update([
            'paso_actual'      => 4,
            'completado'       => true,
            'fecha_completado' => now(),
            'actualizado_en'   => now(),
        ]);
    }

    private function zonaHorariaPais(string $pais): string
    {
        return match ($pais) {
            'VE' => 'America/Caracas',
            'CO' => 'America/Bogota',
            'MX' => 'America/Mexico_City',
            'EC' => 'America/Guayaquil',
            'AR' => 'America/Argentina/Buenos_Aires',
            'PE' => 'America/Lima',
            'CL' => 'America/Santiago',
            'BO' => 'America/La_Paz',
            'UY' => 'America/Montevideo',
            default => 'America/Caracas',
        };
    }

    private function obtenerPlanPorPais(string $pais): Plane
    {
        $planNombre = match ($pais) {
            'VE', 'CO' => 'Trial',
            'MX', 'EC' => 'Básico',
            'AR', 'PE' => 'Pro',
            'CL', 'BO', 'UY' => 'Premium',
            default => 'Trial',
        };

        return Plane::where('nombre', $planNombre)->first() ?? Plane::first();
    }

    private function crearSuscripcionSegunPlan(int $tiendaId, Plane $plan, string $pais): void
    {
        if (in_array($pais, ['VE', 'CO'])) {
            Suscripcion::create([
                'tienda_id'    => $tiendaId,
                'plan_id'      => $plan->id,
                'estado'       => 'trial',
                'inicio_trial' => now(),
                'fin_trial'    => now()->addDays($plan->dias_trial ?? 14),
                'auto_renovar' => true,
            ]);
            return;
        }

        $inicio = now()->subDays(5);
        $fin = now()->addDays(25);

        Suscripcion::create([
            'tienda_id'     => $tiendaId,
            'plan_id'       => $plan->id,
            'estado'        => 'activa',
            'inicio_pago'   => $inicio,
            'fin_periodo'   => $fin->toDateString(),
            'proximo_cobro' => $fin->copy()->addMonth()->toDateString(),
            'auto_renovar'  => true,
        ]);
    }
}
