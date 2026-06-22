<?php

namespace Tests\Feature;

use App\Models\Tienda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use DatabaseTransactions, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_crear_cuenta_exitosamente()
    {
        $response = $this->postJson('/api/v1/onboarding/cuenta', [
            'name'                  => 'New User',
            'email'                 => 'newuser@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pais'                  => 'VE',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'data' => ['user', 'tienda', 'token', 'paso_actual'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com']);
        $this->assertDatabaseHas('tienda', ['pais' => 'VE']);
    }

    public function test_crear_cuenta_pais_invalido()
    {
        $response = $this->postJson('/api/v1/onboarding/cuenta', [
            'name'                  => 'New User',
            'email'                 => 'invalid@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pais'                  => 'XX',
        ]);

        $response->assertStatus(422);
    }

    public function test_crear_cuenta_email_duplicado()
    {
        $this->postJson('/api/v1/onboarding/cuenta', [
            'name'                  => 'First',
            'email'                 => 'dup@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pais'                  => 'CO',
        ]);

        $response = $this->postJson('/api/v1/onboarding/cuenta', [
            'name'                  => 'Second',
            'email'                 => 'dup@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pais'                  => 'CO',
        ]);

        $response->assertStatus(422);
    }

    public function test_onboarding_estado_requiere_autenticacion()
    {
        $response = $this->getJson('/api/v1/onboarding/estado');
        $response->assertStatus(401);
    }

    public function test_crear_cuenta_y_datos_fiscales_flujo_completo()
    {
        // Step 1: create account
        $cuenta = $this->postJson('/api/v1/onboarding/cuenta', [
            'name'                  => 'Flow User',
            'email'                 => 'flow@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pais'                  => 'CO',
        ]);

        $cuenta->assertStatus(201);
        $token = $cuenta->json('data.token');
        $tiendaId = $cuenta->json('data.tienda.id');
        $headers = ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];

        // Step 2: check estado
        $estado = $this->getJson('/api/v1/onboarding/estado', $headers);
        $estado->assertStatus(200);

        // Step 3: save fiscal data
        $fiscales = $this->postJson('/api/v1/onboarding/datos-fiscales', [
            'identificacion_fiscal' => 'J-12345678-9',
            'razon_social'          => 'Flow Store S.A.S.',
            'direccion'             => 'Calle Test 123',
            'telefono'              => '+584141234567',
        ], $headers);

        $fiscales->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify taxes and currencies were created
        $this->assertDatabaseHas('tienda', ['id' => $tiendaId]);
    }

    public function test_onboarding_tres_paises_distintos()
    {
        $paises = ['VE', 'CO', 'MX'];

        foreach ($paises as $pais) {
            $email = "user-$pais-" . uniqid() . '@test.com';
            $response = $this->postJson('/api/v1/onboarding/cuenta', [
                'name'                  => "User $pais",
                'email'                 => $email,
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'pais'                  => $pais,
            ]);
            $response->assertStatus(201);
            $this->assertDatabaseHas('tienda', ['pais' => $pais]);
        }
    }

    public function test_etiquetas_pais()
    {
        $response = $this->getJson('/api/v1/onboarding/etiquetas/VE');
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_etiquetas_pais_invalido()
    {
        $response = $this->getJson('/api/v1/onboarding/etiquetas/ZZ');
        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_configurar_negocio_completo()
    {
        $cuenta = $this->postJson('/api/v1/onboarding/cuenta', [
            'name'                  => 'Config User',
            'email'                 => 'config@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pais'                  => 'MX',
        ]);
        $token = $cuenta->json('data.token');
        $headers = ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];

        $fiscales = $this->postJson('/api/v1/onboarding/datos-fiscales', [
            'identificacion_fiscal' => 'RFC123456',
            'razon_social'          => 'Config Store S.A.',
            'direccion'             => 'Calle Test 123',
            'telefono'              => '+521234567890',
        ], $headers);
        $fiscales->assertStatus(200);

        $negocio = $this->postJson('/api/v1/onboarding/configurar-negocio', [
            'tipo_negocio'    => 'general',
            'nombre_almacen'  => 'Almacen Principal',
            'nombre_caja'     => 'Caja 1',
        ], $headers);
        $negocio->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
