<?php

namespace Tests\Feature;

use App\Models\CuentaCredito;
use App\Models\Cliente;
use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CreditoControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Tienda $tienda;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tienda = Tienda::create([
            'rif' => 'J-77777777-7',
            'razon_social' => 'Credito Test Store',
            'nombre_comercial' => 'Credito Test Store',
            'moneda_base' => 'USD',
            'prefijo_factura' => 'CRD',
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

    public function test_store_crea_cuenta_credito(): void
    {
        $cliente = Cliente::create([
            'tienda_id' => $this->tienda->id,
            'tipo_documento' => 'V',
            'numero_documento' => '33333333',
            'nombre' => 'Cliente Credito',
            'tipo_cliente' => 'natural',
        ]);

        $payload = [
            'cliente_id' => $cliente->id,
            'moneda' => 'USD',
            'limite' => 5000.00,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/creditos', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.limite', 5000);
    }

    public function test_store_rechaza_cliente_duplicado(): void
    {
        $cliente = Cliente::create([
            'tienda_id' => $this->tienda->id,
            'tipo_documento' => 'V',
            'numero_documento' => '44444444',
            'nombre' => 'Cliente Dup',
            'tipo_cliente' => 'natural',
        ]);

        CuentaCredito::create([
            'tienda_id' => $this->tienda->id,
            'cliente_id' => $cliente->id,
            'moneda' => 'USD',
            'limite' => 1000,
            'saldo_usado' => 0,
            'estado' => 'activa',
        ]);

        $payload = [
            'cliente_id' => $cliente->id,
            'moneda' => 'USD',
            'limite' => 3000.00,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/creditos', $payload);

        $response->assertStatus(422);
    }

    public function test_update_limite_no_menor_a_saldo(): void
    {
        $cliente = Cliente::create([
            'tienda_id' => $this->tienda->id,
            'tipo_documento' => 'V',
            'numero_documento' => '55555555',
            'nombre' => 'Cliente Update',
            'tipo_cliente' => 'natural',
        ]);

        $cuenta = CuentaCredito::create([
            'tienda_id' => $this->tienda->id,
            'cliente_id' => $cliente->id,
            'moneda' => 'USD',
            'limite' => 5000,
            'saldo_usado' => 3000,
            'estado' => 'activa',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/creditos/{$cuenta->id}", [
                'limite' => 1000,
            ]);

        $response->assertStatus(422);
    }

    public function test_destroy_rechaza_con_saldo_pendiente(): void
    {
        $cliente = Cliente::create([
            'tienda_id' => $this->tienda->id,
            'tipo_documento' => 'V',
            'numero_documento' => '66666666',
            'nombre' => 'Cliente Del',
            'tipo_cliente' => 'natural',
        ]);

        $cuenta = CuentaCredito::create([
            'tienda_id' => $this->tienda->id,
            'cliente_id' => $cliente->id,
            'moneda' => 'USD',
            'limite' => 5000,
            'saldo_usado' => 1500,
            'estado' => 'activa',
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/creditos/{$cuenta->id}");

        $response->assertStatus(422);
    }
}
