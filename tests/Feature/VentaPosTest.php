<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VentaPosTest extends TestCase
{
    use DatabaseTransactions, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestData();
    }

    public function test_crear_venta_exitosa()
    {
        $data = $this->setUpVentaData();

        $response = $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'tipo_documento' => 'FAC',
            'tipo_pago'      => 'contado',
            'items' => [[
                'variante_id'     => $data['variante']->id,
                'cantidad'        => 2,
                'precio_unitario' => 100.00,
                'tasa_conversion' => 1,
                'descuento_pct'   => 0,
                'impuesto_pct'    => 16,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 232.00,
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers());

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['id', 'items', 'pagos']]);
    }

    public function test_crear_venta_stock_insuficiente()
    {
        $data = $this->setUpVentaData();
        \App\Models\Inventario::where('variante_id', $data['variante']->id)
            ->where('almacen_id', $data['almacen']->id)
            ->update(['cantidad_disponible' => 0]);

        $response = $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'tipo_documento' => 'FAC',
            'items' => [[
                'variante_id'     => $data['variante']->id,
                'cantidad'        => 10,
                'precio_unitario' => 100.00,
                'tasa_conversion' => 1,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 1000.00,
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers());

        $response->assertStatus(422);
    }

    public function test_crear_venta_sin_autenticacion()
    {
        $data = $this->setUpVentaData();

        $this->app['auth']->guard('web')->logout();

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
        ]);

        $response->assertStatus(401);
    }

    public function test_inventario_se_deduce_al_crear_venta()
    {
        $data = $this->setUpVentaData();

        $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'tipo_documento' => 'FAC',
            'items' => [[
                'variante_id'     => $data['variante']->id,
                'cantidad'        => 5,
                'precio_unitario' => 100.00,
                'tasa_conversion' => 1,
                'impuesto_pct'    => 16,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 580.00,
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers());

        $this->assertDatabaseHas('inventario', [
            'variante_id'         => $data['variante']->id,
            'almacen_id'          => $data['almacen']->id,
            'cantidad_disponible' => 45,
        ]);
    }

    public function test_anular_venta_pagada_devuelve_422()
    {
        $venta = $this->crearVentaHelper();
        $ventaId = $venta['id'];

        $response = $this->postJson("/api/v1/ventas/{$ventaId}/anular", $this->headers());

        $response->assertStatus(422);
    }

    public function test_anular_venta_inexistente()
    {
        $response = $this->postJson('/api/v1/ventas/99999/anular', $this->headers());
        $response->assertStatus(404);
    }

    public function test_listar_ventas()
    {
        $this->crearVentaHelper();

        $response = $this->getJson('/api/v1/ventas', $this->headers());

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_mostrar_venta()
    {
        $venta = $this->crearVentaHelper();
        $ventaId = $venta['id'];

        $response = $this->getJson("/api/v1/ventas/{$ventaId}", $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $ventaId);
    }

    public function test_venta_sin_items()
    {
        $data = $this->setUpVentaData();

        $response = $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'items'          => [],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 0,
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers());

        $response->assertStatus(422);
    }

    public function test_venta_sin_pagos()
    {
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
            'pagos' => [],
        ], $this->headers());

        $response->assertStatus(422);
    }

    private function crearVentaHelper(): array
    {
        $data = $this->setUpVentaData();

        $response = $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'tipo_documento' => 'FAC',
            'tipo_pago'      => 'contado',
            'items' => [[
                'variante_id'     => $data['variante']->id,
                'cantidad'        => 2,
                'precio_unitario' => 100.00,
                'tasa_conversion' => 1,
                'descuento_pct'   => 0,
                'impuesto_pct'    => 16,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 232.00,
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers());

        return $response->json('data');
    }
}
