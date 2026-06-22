<?php

namespace Tests\Feature;

use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $cajero;
    private User $desactivado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $tienda = Tienda::create([
            'rif' => 'J-66666666-6',
            'razon_social' => 'MW Test Store',
            'nombre_comercial' => 'MW Test Store',
            'moneda_base' => 'USD',
            'prefijo_factura' => 'MWT',
            'siguiente_numero' => 1,
            'zona_horaria' => 'America/Caracas',
            'pais' => 'VE',
            'activo' => true,
        ]);
        Suscripcion::create([
            'tienda_id'   => $tienda->id,
            'plan_id'     => 1,
            'estado'      => 'trial',
            'inicio_trial'=> now()->subDays(5),
            'fin_trial'   => now()->addDays(25),
        ]);

        $this->admin = User::factory()->create([
            'tienda_id' => $tienda->id,
            'activo' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->cajero = User::factory()->create([
            'tienda_id' => $tienda->id,
            'activo' => true,
        ]);
        $this->cajero->assignRole('cajero');

        $this->desactivado = User::factory()->create([
            'tienda_id' => $tienda->id,
            'activo' => false,
        ]);
        $this->desactivado->assignRole('cajero');
    }

    public function test_rutas_protegidas_requieren_auth(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_usuario_desactivado_bloqueado(): void
    {
        $response = $this->actingAs($this->desactivado)->getJson('/api/v1/auth/me');

        $response->assertForbidden();
    }

    public function test_usuario_activo_puede_acceder(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/auth/me');

        $response->assertOk();
    }

    public function test_rutas_admin_bloqueadas_para_cajero(): void
    {
        $response = $this->actingAs($this->cajero)->getJson('/api/v1/usuarios');

        $response->assertForbidden();
    }

    public function test_rutas_admin_accesibles_para_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/usuarios');

        $response->assertOk();
    }
}
