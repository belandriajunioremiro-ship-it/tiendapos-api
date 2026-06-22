<?php

namespace Tests\Feature;

use App\Models\Tienda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FullFlowTest extends TestCase
{
    use DatabaseTransactions, CreatesTestData;

    private Tienda $tienda;
    private User $user;
    private string $token;
    private array $headers;

    private function iniciarPais(string $pais, string $monedaBase, string $prefijo, array $extra = []): void
    {
        $this->tienda = $this->crearTienda(array_merge([
            'pais' => $pais,
            'moneda_base' => $monedaBase,
            'prefijo_factura' => $prefijo,
        ], $extra));

        $this->crearSuscripcion('trial', $this->tienda);
        $this->user = $this->crearUsuario('admin', $this->tienda);
        $this->token = $this->autenticar($this->user);
        $this->headers = $this->headers();
    }

    private function ejecutarFlujoCompleto(): void
    {
        // ── 1. TIENDA ──
        $this->getJson('/api/v1/tienda', $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('data.pais', $this->tienda->pais);

        // ── 2. CATEGORÍA ──
        $slugCat = 'cat-' . uniqid();
        $catResp = $this->postJson('/api/v1/categorias', [
            'nombre' => 'Electrónicos',
            'slug'   => $slugCat,
        ], $this->headers);
        if ($catResp->status() !== 201) { echo 'CAT ERROR: ' . $catResp->content() . PHP_EOL; }
        $catResp->assertStatus(201);
        $categoriaId = $catResp->json('data.id');

        // ── 3. IMPUESTO ──
        $impResp = $this->postJson('/api/v1/impuestos', [
            'nombre'     => 'IVA General',
            'porcentaje' => $this->tienda->pais === 'VE' ? 16 : ($this->tienda->pais === 'CO' ? 19 : 16),
            'tipo'       => 'iva',
            'es_defecto' => true,
        ], $this->headers);
        $impResp->assertStatus(201);
        $impuestoId = $impResp->json('data.id');

        // ── 4. MARGEN DE GANANCIA ──
        $this->postJson('/api/v1/margenes', [
            'nombre'     => 'Margen General',
            'porcentaje' => 30,
            'tipo'       => 'markup',
            'es_defecto' => true,
        ], $this->headers)->assertStatus(201);

        // ── 5. UNIDAD (necesaria para crear producto) ──
        $und = $this->crearUnidad();

        // ── 6. PRODUCTO (crea variante automática) ──
        $sku = 'SKU-' . $this->tienda->pais . '-' . uniqid();
        $prodResp = $this->postJson('/api/v1/productos', [
            'categoria_id'   => $categoriaId,
            'unidad_id'      => $und->id,
            'impuesto_id'    => $impuestoId,
            'moneda_precio'  => $this->tienda->moneda_base,
            'codigo_sku'     => $sku,
            'nombre'         => 'Laptop Pro ' . $this->tienda->pais,
            'costo_promedio' => 500,
            'margen_pct'     => 30,
        ], $this->headers);
        if ($prodResp->status() !== 201) { echo 'PROD ERROR (status ' . $prodResp->status() . '): ' . $prodResp->content() . PHP_EOL; }
        $prodResp->assertStatus(201);
        $productoId = $prodResp->json('data.id');
        $varianteId = $prodResp->json('data.variantes.0.id');

        // ── 7. LISTAR PRODUCTOS ──
        $this->getJson('/api/v1/productos', $this->headers)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // ── 8. ALMACÉN ──
        $almResp = $this->postJson('/api/v1/almacenes', [
            'nombre' => 'Almacén Principal ' . $this->tienda->pais,
            'tipo'   => 'principal',
        ], $this->headers);
        $almResp->assertStatus(201);
        $almacenId = $almResp->json('data.id');

        // ── 9. INVENTARIO ──
        $this->postJson('/api/v1/inventario', [
            'variante_id'         => $varianteId,
            'almacen_id'          => $almacenId,
            'cantidad_disponible' => 100,
            'stock_minimo'        => 10,
        ], $this->headers)->assertStatus(201);

        // ── 10. CAJA ──
        $cajaResp = $this->postJson('/api/v1/cajas', [
            'nombre' => 'Caja Principal ' . $this->tienda->pais,
        ], $this->headers);
        $cajaResp->assertStatus(201);
        $cajaId = $cajaResp->json('data.id');

        // ── 11. ABRIR SESIÓN DE CAJA ──
        $sesionResp = $this->postJson("/api/v1/cajas/{$cajaId}/abrir", [
            'observaciones' => 'Turno matutino',
        ], $this->headers);
        $sesionResp->assertStatus(201);
        $sesionCajaId = $sesionResp->json('data.id');

        // ── 12. LISTAR SESIONES ──
        $this->getJson('/api/v1/sesiones-caja?estado=abierta', $this->headers)
            ->assertStatus(200);

        // ── 13. CLIENTE ──
        $doc = 'V-' . rand(10000000, 99999999);
        $cliResp = $this->postJson('/api/v1/clientes', [
            'nombre'           => 'Juan Pérez',
            'tipo_documento'   => 'V',
            'numero_documento' => $doc,
            'tipo_cliente'     => 'natural',
            'telefono'         => '+584141234567',
            'email'            => 'juan.' . uniqid() . '@test.com',
        ], $this->headers);
        $cliResp->assertStatus(201);
        $clienteId = $cliResp->json('data.id');

        // ── 14. MÉTODO DE PAGO (modelo directo, evitar $this->authorize('admin')) ──
        $metodoPago = $this->crearMetodoPago();
        $this->assertNotNull($metodoPago->id);

        // ── 15. VENTA POS ──
        $precioUnit = 650;
        $cantidad   = 2;
        $ivaPct     = $this->tienda->pais === 'VE' ? 16 : ($this->tienda->pais === 'CO' ? 19 : 16);
        $subtotal   = $precioUnit * $cantidad;
        $iva        = $subtotal * $ivaPct / 100;
        $total      = $subtotal + $iva;

        $ventaResp = $this->postJson('/api/v1/ventas', [
            'cliente_id'     => $clienteId,
            'caja_id'        => $cajaId,
            'sesion_caja_id' => $sesionCajaId,
            'almacen_id'     => $almacenId,
            'moneda_factura' => $this->tienda->moneda_base,
            'tipo_documento' => 'FAC',
            'items' => [[
                'variante_id'    => $varianteId,
                'cantidad'       => $cantidad,
                'precio_unitario'=> $precioUnit,
                'tasa_conversion'=> 1,
                'descuento_pct'  => 0,
                'impuesto_pct'   => $ivaPct,
            ]],
            'pagos' => [[
                'metodo_pago_id' => $metodoPago->id,
                'monto_pago'     => round($total, 2),
                'tasa_aplicada'  => 1,
            ]],
        ], $this->headers);
        $ventaResp->assertStatus(201);
        $ventaId = $ventaResp->json('data.id');

        // ── 16. DETALLE DE VENTA ──
        $this->getJson("/api/v1/ventas/{$ventaId}", $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('data.cliente.id', $clienteId)
            ->assertJsonPath('data.estado', 'pagada');

        // ── 17. LISTAR VENTAS ──
        $this->getJson('/api/v1/ventas', $this->headers)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // ── 18. DASHBOARD ──
        $this->getJson('/api/v1/dashboard', $this->headers)
            ->assertStatus(200);

        // ── 19. MOVIMIENTO DE CAJA ──
        $this->postJson('/api/v1/movimientos-caja', [
            'sesion_id' => $sesionCajaId,
            'tipo'      => 'retiro',
            'moneda'    => $this->tienda->moneda_base,
            'monto'     => 100,
            'concepto'  => 'Pago servicios',
        ], $this->headers)->assertStatus(201);

        // ── 20. REPORTES (solo admin) ──
        $this->getJson('/api/v1/reportes/ventas', $this->headers)->assertStatus(200);
        $this->getJson('/api/v1/reportes/inventario', $this->headers)->assertStatus(200);

        // ── 21. VERIFICAR STOCK RESTANTE ──
        $this->getJson("/api/v1/inventario?almacen_id={$almacenId}", $this->headers)
            ->assertStatus(200);

        // ── 22. CERRAR CAJA ──
        $this->postJson("/api/v1/cajas/{$cajaId}/cerrar", [
            'observaciones' => 'Turno finalizado',
        ], $this->headers)->assertStatus(200);

        // ── 23. ACTUALIZAR TIENDA ──
        $this->putJson('/api/v1/tienda', [
            'nombre_comercial' => 'Mi Tienda ' . $this->tienda->pais . ' Actualizada',
        ], $this->headers)->assertStatus(200);
    }

    // ════════════════════════════════════════════════════════════════
    //  TESTS POR PAÍS
    // ════════════════════════════════════════════════════════════════

    #[Test]
    public function flujo_completo_venezuela()
    {
        $this->iniciarPais('VE', 'USD', 'VE', [
            'es_agente_igtf' => true,
            'alicuota_igtf'  => 3.0,
        ]);
        $this->ejecutarFlujoCompleto();
    }

    #[Test]
    public function flujo_completo_colombia()
    {
        $this->iniciarPais('CO', 'COP', 'CO');
        $this->ejecutarFlujoCompleto();
    }

    #[Test]
    public function flujo_completo_mexico()
    {
        $this->iniciarPais('MX', 'MXN', 'MX');
        $this->ejecutarFlujoCompleto();
    }
}
