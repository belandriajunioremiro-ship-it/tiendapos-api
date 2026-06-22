<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use DatabaseTransactions, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestData();
    }

    public function test_usuario_ve_solo_sus_ventas()
    {
        $this->crearVentaHelper();

        $ventas = Venta::withoutTiendaScope()->where('tienda_id', $this->testTienda->id)->get();
        $this->assertCount(1, $ventas);

        $tienda2 = $this->crearTienda([
            'rif'             => 'J-99999999-9',
            'email'           => 'storeb@test.com',
            'prefijo_factura' => 'TSB',
            'nombre_comercial'=> 'Store B',
            'pais'            => 'CO',
        ]);
        $this->crearSuscripcion('trial', $tienda2);
        $userB = $this->crearUsuario('admin', $tienda2);

        $this->actingAs($userB);
        $ventasB = Venta::get();
        $this->assertCount(0, $ventasB);
    }

    public function test_dos_tiendas_no_comparten_clientes()
    {
        Cliente::create([
            'tienda_id'     => $this->testTienda->id,
            'nombre'        => 'Cliente Tienda A',
            'documento'     => 'V-11111111',
            'tipo_documento'=> 'V',
            'activo'        => true,
        ]);

        $tienda2 = $this->crearTienda([
            'rif'             => 'J-88888888-8',
            'email'           => 'storec@test.com',
            'prefijo_factura' => 'TSC',
            'nombre_comercial'=> 'Store C',
            'pais'            => 'MX',
        ]);
        $this->crearSuscripcion('trial', $tienda2);
        $userC = $this->crearUsuario('admin', $tienda2);

        $this->actingAs($userC);
        $clientes = Cliente::get();
        $this->assertCount(0, $clientes);
    }

    public function test_dos_tiendas_no_comparten_almacenes()
    {
        Almacen::create([
            'tienda_id' => $this->testTienda->id,
            'nombre'    => 'Almacen Tienda A',
            'activo'    => true,
        ]);

        $tienda2 = $this->crearTienda([
            'rif'             => 'J-77777777-7',
            'email'           => 'stored@test.com',
            'prefijo_factura' => 'TSD',
            'nombre_comercial'=> 'Store D',
            'pais'            => 'AR',
        ]);
        $this->crearSuscripcion('trial', $tienda2);
        $userD = $this->crearUsuario('admin', $tienda2);

        $this->actingAs($userD);
        $almacenes = Almacen::get();
        $this->assertCount(0, $almacenes);
    }

    public function test_api_ventas_solo_devuelve_datos_de_la_tienda()
    {
        $this->crearVentaHelper();

        $tienda2 = $this->crearTienda([
            'rif'             => 'J-66666666-6',
            'email'           => 'storee@test.com',
            'prefijo_factura' => 'TSE',
            'nombre_comercial'=> 'Store E',
            'pais'            => 'PE',
        ]);
        $this->crearSuscripcion('trial', $tienda2);
        $userE = $this->crearUsuario('admin', $tienda2);
        $tokenE = $userE->createToken('test-token', ['*'])->plainTextToken;

        $data = $this->setUpVentaData();
        $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $data['cliente']->id,
            'caja_id'        => $data['caja']->id,
            'almacen_id'     => $data['almacen']->id,
            'moneda_factura' => 'USD',
            'tipo_documento' => 'FAC',
            'items' => [[
                'variante_id'     => $data['variante']->id,
                'cantidad'        => 1,
                'precio_unitario' => 50.00,
                'tasa_conversion' => 1,
                'impuesto_pct'    => 16,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $data['metodoPago']->id,
                'monto_pago'     => 58.00,
                'tasa_aplicada'  => 1,
            ]],
        ], ['Authorization' => "Bearer $tokenE", 'Accept' => 'application/json']);

        $response = $this->getJson('/api/v1/ventas', $this->headers());
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_belongs_to_tienda_scope_no_falla_sin_auth()
    {
        $tiendaId = $this->testTienda->id;

        $count = Venta::withoutTiendaScope()->where('tienda_id', $tiendaId)->count();

        $this->assertEquals(0, $count);
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
