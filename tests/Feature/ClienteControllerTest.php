<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClienteControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Tienda $tienda;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tienda = Tienda::create([
            'rif' => 'J-88888888-8',
            'razon_social' => 'Cliente Test Store',
            'nombre_comercial' => 'Cliente Test Store',
            'moneda_base' => 'USD',
            'prefijo_factura' => 'CLI',
            'siguiente_numero' => 1,
            'zona_horaria' => 'America/Caracas',
            'pais' => 'VE',
            'activo' => true,
        ]);
        Suscripcion::create([
            'tienda_id'   => $this->tienda->id,
            'plan_id'     => 1,
            'estado'      => 'trial',
            'inicio_trial'=> now()->subDays(5),
            'fin_trial'   => now()->addDays(25),
        ]);

        $this->user = User::factory()->create([
            'tienda_id' => $this->tienda->id,
            'activo' => true,
        ]);

        $this->user->assignRole('admin');
    }

    public function test_index_lista_clientes(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/clientes');
        if ($response->status() === 404) { echo '404 BODY: ' . $response->content() . PHP_EOL; }
        $response->assertOk();
    }

    public function test_store_crea_cliente(): void
    {
        $payload = [
            'tipo_documento' => 'V',
            'numero_documento' => '12345678',
            'nombre' => 'Juan Pérez',
            'tipo_cliente' => 'natural',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/clientes', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Juan Pérez');
    }

    public function test_store_valida_nombre_requerido(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/clientes', [
            'tipo_documento' => 'V',
            'tipo_cliente' => 'natural',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_show_retorna_cliente(): void
    {
        $cliente = Cliente::create([
            'tienda_id' => $this->tienda->id,
            'tipo_documento' => 'V',
            'numero_documento' => '87654321',
            'nombre' => 'Maria Lopez',
            'tipo_cliente' => 'natural',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/clientes/{$cliente->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $cliente->id);
    }

    public function test_update_actualiza_cliente(): void
    {
        $cliente = Cliente::create([
            'tienda_id' => $this->tienda->id,
            'tipo_documento' => 'V',
            'numero_documento' => '11111111',
            'nombre' => 'Old Name',
            'tipo_cliente' => 'natural',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/clientes/{$cliente->id}", [
                'nombre' => 'New Name',
            ]);

        $response->assertOk();
        $this->assertEquals('New Name', $cliente->fresh()->nombre);
    }

    public function test_destroy_desactiva_cliente(): void
    {
        $cliente = Cliente::create([
            'tienda_id' => $this->tienda->id,
            'tipo_documento' => 'V',
            'numero_documento' => '22222222',
            'nombre' => 'Delete Me',
            'tipo_cliente' => 'natural',
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/clientes/{$cliente->id}");

        $response->assertOk();
        $this->assertFalse($cliente->fresh()->activo);
    }
}
