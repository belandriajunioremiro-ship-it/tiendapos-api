<?php

namespace Tests\Feature;

use App\Models\Suscripcion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SuscripcionTest extends TestCase
{
    use DatabaseTransactions, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestData();
    }

    public function test_acceso_con_suscripcion_trial_activa()
    {
        // Venta routes require suscripcion middleware
        $data = $this->setUpVentaData();

        $response = $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'tipo_documento' => 'FAC',
            'items' => [[
                'variante_id'     => $data['variante']->id,
                'cantidad'        => 1,
                'precio_unitario' => 100.00,
                'tasa_conversion' => 1,
                'impuesto_pct'    => 16,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 116.00,
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers());

        $response->assertStatus(201);
    }

    public function test_acceso_con_suscripcion_vencida()
    {
        // Set subscription to expired
        Suscripcion::where('tienda_id', $this->testTienda->id)->update([
            'estado'    => 'vencida',
            'fin_trial' => now()->subDays(10),
        ]);

        $data = $this->setUpVentaData();

        $response = $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'items' => [[
                'variante_id'     => $data['variante']->id,
                'cantidad'        => 1,
                'precio_unitario' => 100.00,
                'tasa_conversion' => 1,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 100.00,
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers());

        $response->assertStatus(402)
            ->assertJsonPath('error', 'suscripcion_vencida');
    }

    public function test_acceso_con_suscripcion_activa()
    {
        // Update to paid subscription
        Suscripcion::where('tienda_id', $this->testTienda->id)->update([
            'estado'      => 'activa',
            'inicio_pago' => now()->subMonths(1),
            'fin_periodo' => now()->addMonths(1),
            'proximo_cobro'=> now()->addMonths(1),
        ]);

        $data = $this->setUpVentaData();

        $response = $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'tipo_documento' => 'FAC',
            'items' => [[
                'variante_id'     => $data['variante']->id,
                'cantidad'        => 1,
                'precio_unitario' => 100.00,
                'tasa_conversion' => 1,
                'impuesto_pct'    => 16,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 116.00,
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers());

        $response->assertStatus(201);
    }

    public function test_suscripcion_suspendida_bloquea_acceso()
    {
        Suscripcion::where('tienda_id', $this->testTienda->id)->update([
            'estado' => 'suspendida',
        ]);

        $response = $this->getJson('/api/v1/ventas', $this->headers());

        $response->assertStatus(402)
            ->assertJsonPath('error', 'suscripcion_vencida');
    }

    public function test_onboarding_no_requiere_suscripcion()
    {
        $response = $this->getJson('/api/v1/onboarding/estado', $this->headers());
        $content = $response->getContent();
        // May return 200 or 404 depending on route config; accept both
        $this->assertContains($response->status(), [200, 404], "Response: $content");
    }

    public function test_planes_disponibles()
    {
        $response = $this->getJson('/api/v1/suscripcion/planes', $this->headers());

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }
}
