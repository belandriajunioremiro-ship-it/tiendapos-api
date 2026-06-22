<?php

namespace Tests\Unit;

use App\Exceptions\Suscripcion\LimitePlanExcedidoException;
use App\Exceptions\Suscripcion\SuscripcionVencidaException;
use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Plane;
use App\Models\Producto;
use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\User;
use App\Services\SuscripcionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SuscripcionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private SuscripcionService $service;
    private Tienda $tienda;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->service = new SuscripcionService();

        $this->tienda = Tienda::create([
            'rif' => 'J-SUBS-0001',
            'razon_social' => 'Subs Test',
            'nombre_comercial' => 'Subs Test',
            'moneda_base' => 'USD',
            'prefijo_factura' => 'SUB',
            'siguiente_numero' => 1,
            'zona_horaria' => 'America/Caracas',
            'pais' => 'VE',
            'activo' => true,
        ]);
    }

    private function crearSuscripcion(string $estado, array $overrides = []): Suscripcion
    {
        return Suscripcion::create(array_merge([
            'tienda_id'   => $this->tienda->id,
            'plan_id'     => 1,
            'estado'      => $estado,
            'inicio_trial'=> now()->subDays(5),
            'fin_trial'   => now()->addDays(25),
        ], $overrides));
    }

    public function test_obtenerActiva_retorna_suscripcion_trial(): void
    {
        $sub = $this->crearSuscripcion('trial');
        $result = $this->service->obtenerActiva($this->tienda->id);
        $this->assertEquals($sub->id, $result->id);
        $this->assertEquals('trial', $result->estado);
    }

    public function test_obtenerActiva_retorna_suscripcion_activa(): void
    {
        $sub = $this->crearSuscripcion('activa', [
            'inicio_pago' => now(),
            'fin_periodo' => now()->addMonth(),
        ]);
        $result = $this->service->obtenerActiva($this->tienda->id);
        $this->assertEquals('activa', $result->estado);
    }

    public function test_obtenerActiva_lanza_excepcion_si_no_existe(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->obtenerActiva(999999);
    }

    public function test_verificarAcceso_pasa_con_trial_vigente(): void
    {
        $this->crearSuscripcion('trial');
        $this->service->verificarAcceso($this->tienda->id);
        $this->assertTrue(true);
    }

    public function test_verificarAcceso_pasa_con_suscripcion_activa(): void
    {
        $this->crearSuscripcion('activa', [
            'inicio_pago' => now(),
            'fin_periodo' => now()->addMonth(),
        ]);
        $this->service->verificarAcceso($this->tienda->id);
        $this->assertTrue(true);
    }

    public function test_verificarAcceso_lanza_excepcion_si_suspendida(): void
    {
        $this->expectException(SuscripcionVencidaException::class);
        $this->crearSuscripcion('suspendida');
        $this->service->verificarAcceso($this->tienda->id);
    }

    public function test_verificarAcceso_lanza_excepcion_si_vencida(): void
    {
        $this->expectException(SuscripcionVencidaException::class);
        $this->crearSuscripcion('vencida');
        $this->service->verificarAcceso($this->tienda->id);
    }

    public function test_verificarAcceso_lanza_excepcion_si_trial_expirado(): void
    {
        $this->expectException(SuscripcionVencidaException::class);
        $this->crearSuscripcion('trial', [
            'fin_trial' => now()->subDays(5),
        ]);
        $this->service->verificarAcceso($this->tienda->id);
    }

    public function test_verificarAcceso_marca_trial_como_vencido_si_expiro(): void
    {
        $sub = $this->crearSuscripcion('trial', [
            'fin_trial' => now()->subDays(5),
        ]);

        try {
            $this->service->verificarAcceso($this->tienda->id);
        } catch (SuscripcionVencidaException $e) {
            // esperada
        }

        $this->assertEquals('vencida', $sub->fresh()->estado);
    }

    public function test_cancelarSuscripcion_cambia_estado_a_cancelada(): void
    {
        $sub = $this->crearSuscripcion('trial');
        $user = User::factory()->create(['tienda_id' => $this->tienda->id, 'activo' => true]);

        $this->service->cancelarSuscripcion($this->tienda->id, $user->id, 'No me gusta');

        $this->assertEquals('cancelada', $sub->fresh()->estado);
        $this->assertFalse($sub->fresh()->auto_renovar);
    }

    public function test_cancelarSuscripcion_no_afecta_suscripcion_de_otra_tienda(): void
    {
        $sub1 = $this->crearSuscripcion('trial');

        $tienda2 = Tienda::create([
            'rif' => 'J-SUBS-0002',
            'razon_social' => 'Otra Tienda',
            'nombre_comercial' => 'Otra Tienda',
            'moneda_base' => 'USD',
            'prefijo_factura' => 'OTR',
            'siguiente_numero' => 1,
            'zona_horaria' => 'America/Caracas',
            'pais' => 'VE',
            'activo' => true,
        ]);
        $sub2 = Suscripcion::create([
            'tienda_id'   => $tienda2->id,
            'plan_id'     => 1,
            'estado'      => 'trial',
            'inicio_trial'=> now()->subDays(5),
            'fin_trial'   => now()->addDays(25),
        ]);

        $user = User::factory()->create(['tienda_id' => $this->tienda->id, 'activo' => true]);
        $this->service->cancelarSuscripcion($this->tienda->id, $user->id);

        $this->assertEquals('cancelada', $sub1->fresh()->estado);
        $this->assertEquals('trial', $sub2->fresh()->estado);
    }

    public function test_activarSuscripcion_cancela_anterior_y_crea_nueva(): void
    {
        $subVieja = $this->crearSuscripcion('trial');

        $nueva = $this->service->activarSuscripcion($this->tienda->id, 1, 1);

        $this->assertEquals('cancelada', $subVieja->fresh()->estado);
        $this->assertEquals('activa', $nueva->estado);
        $this->assertTrue($nueva->auto_renovar);
    }

    public function test_marcarTrialsVencidos_actualiza_solo_los_expirados(): void
    {
        $subVigente = $this->crearSuscripcion('trial', ['fin_trial' => now()->addDays(10)]);
        $subExpirado = $this->crearSuscripcion('trial', ['fin_trial' => now()->subDays(3)]);

        $count = $this->service->marcarTrialsVencidos();

        $this->assertEquals(1, $count);
        $this->assertEquals('trial', $subVigente->fresh()->estado);
        $this->assertEquals('vencida', $subExpirado->fresh()->estado);
    }

    public function test_estadoParaFrontend_retorna_sin_suscripcion_si_no_hay(): void
    {
        $result = $this->service->estadoParaFrontend(999999);
        $this->assertEquals('sin_suscripcion', $result['estado']);
    }

    public function test_estadoParaFrontend_retorna_info_completa(): void
    {
        $this->crearSuscripcion('trial');
        $result = $this->service->estadoParaFrontend($this->tienda->id);

        $this->assertEquals('trial', $result['estado']);
        $this->assertTrue($result['es_trial']);
        $this->assertArrayHasKey('dias_restantes', $result);
        $this->assertArrayHasKey('limites', $result);
    }

    public function test_obtenerPlan_retorna_plan_de_suscripcion_activa(): void
    {
        $this->crearSuscripcion('trial');
        $plan = $this->service->obtenerPlan($this->tienda->id);
        $this->assertInstanceOf(Plane::class, $plan);
    }
}
