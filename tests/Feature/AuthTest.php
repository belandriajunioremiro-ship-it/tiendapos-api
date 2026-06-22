<?php

namespace Tests\Feature;

use App\Models\Tienda;
use App\Models\User;
use App\Models\Suscripcion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions, CreatesTestData;

    private User $user;
    private Tienda $tienda;
    private string $userEmail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tienda = $this->crearTienda();
        $this->crearSuscripcion('trial', $this->tienda);

        Role::where('name', 'admin')->first();
        $this->user = User::create([
            'tienda_id' => $this->tienda->id,
            'name'      => 'Auth Test User',
            'email'     => 'auth-test-' . uniqid() . '@test.com',
            'password'  => 'testpassword',
            'activo'    => true,
        ]);
        $role = Role::where('name', 'admin')->first();
        if ($role) {
            $this->user->assignRole($role);
        }

        $this->userEmail = $this->user->email;
    }

    public function test_login_con_credenciales_validas()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->userEmail,
            'password' => 'testpassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data' => ['token', 'token_type', 'user'],
            ]);
    }

    public function test_login_con_credenciales_invalidas()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->userEmail,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_usuario_inactivo()
    {
        $this->user->update(['activo' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->userEmail,
            'password' => 'testpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_me_devuelve_usuario_autenticado()
    {
        $token = $this->user->createToken('test-token', ['*'])->plainTextToken;
        $headers = ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];

        $response = $this->getJson('/api/v1/auth/me', $headers);

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $this->userEmail);
    }

    public function test_me_sin_token()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_refresh_token()
    {
        $token = $this->user->createToken('test-token', ['*'])->plainTextToken;
        $headers = ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];

        $response = $this->postJson('/api/v1/auth/refresh', [], $headers);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['token']]);
    }

    public function test_logout()
    {
        $token = $this->user->createToken('test-token', ['*'])->plainTextToken;
        $headers = ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];

        $response = $this->postJson('/api/v1/auth/logout', [], $headers);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_cambiar_password()
    {
        $token = $this->user->createToken('test-token', ['*'])->plainTextToken;
        $headers = ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];

        $response = $this->postJson('/api/v1/auth/cambiar-password', [
            'password_actual' => 'testpassword',
            'password_nueva'  => 'newpassword123',
            'password_nueva_confirmation' => 'newpassword123',
        ], $headers);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_cambiar_password_actual_incorrecta()
    {
        $token = $this->user->createToken('test-token', ['*'])->plainTextToken;
        $headers = ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];

        $response = $this->postJson('/api/v1/auth/cambiar-password', [
            'password_actual' => 'wrongpassword',
            'password_nueva'  => 'newpassword123',
            'password_nueva_confirmation' => 'newpassword123',
        ], $headers);

        $response->assertStatus(422);
    }

    public function test_forgot_password_envia_token()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $this->userEmail,
        ]);

        $response->assertStatus(200);
    }

    public function test_forgot_password_email_inexistente()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'noexiste-' . uniqid() . '@test.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_verify_token_requiere_campos()
    {
        $response = $this->postJson('/api/v1/auth/verify-token', []);

        $response->assertStatus(422);
    }

    public function test_rate_limit_login()
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email'    => 'ratelimit-' . uniqid() . '@test.com',
                'password' => 'wrong',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'ratelimit-final@test.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(429);
    }
}
