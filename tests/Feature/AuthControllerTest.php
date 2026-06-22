<?php

namespace Tests\Feature;

use App\Models\Tienda;
use App\Models\User;
use App\Models\Suscripcion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $tienda = Tienda::create([
            'rif' => 'J-12345678-9',
            'razon_social' => 'Test Store',
            'nombre_comercial' => 'Test Store',
            'moneda_base' => 'USD',
            'prefijo_factura' => 'TST',
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

        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'tienda_id' => $tienda->id,
            'activo' => true,
        ]);

        $this->user->assignRole('admin');
    }

    public function test_login_exitoso(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => ['token', 'user']]);
    }

    public function test_login_credenciales_incorrectas(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_login_usuario_desactivado(): void
    {
        $this->user->update(['activo' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_me_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_me_retorna_usuario_autenticado(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.email', 'admin@test.com');
    }

    public function test_logout_elimina_token(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertEquals(0, $this->user->fresh()->tokens()->count());
    }
}
