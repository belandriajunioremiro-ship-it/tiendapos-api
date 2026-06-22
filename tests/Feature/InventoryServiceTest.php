<?php

namespace Tests\Feature;

use App\Jobs\RecalcularCostoPromedioProducto;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Inventario;
use App\Models\InventarioLote;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\Unidad;
use App\Models\VarianteProducto;
use App\Services\Inventory\DTO\RecepcionItemData;
use App\Services\Inventory\DTO\TrasladoItemData;
use App\Services\Inventory\DTO\VentaItemData;
use App\Services\Inventory\Exceptions\CoherenciaDimensionalException;
use App\Services\Inventory\Exceptions\LoteRequeridoException;
use App\Services\Inventory\Exceptions\StockInsuficienteException;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use DatabaseTransactions;

    private InventoryService $service;
    private Unidad $und;
    private Unidad $cja24;
    private Unidad $bls10;
    private Unidad $kg;
    private Unidad $lt;
    private Almacen $almacen1;
    private Almacen $almacen2;
    private CategoriaProducto $categoria;
    private Tienda $tienda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InventoryService::class);

        // Tienda (necesaria por multi-tenancy en almacenes, categorías, productos)
        $this->tienda = Tienda::create([
            'pais'            => 'VE',
            'rif'             => 'J-' . substr(uniqid(), -8),
            'razon_social'    => 'Test Inventory',
            'moneda_base'     => 'USD',
            'moneda_pivot_api'=> 'USD',
            'zona_horaria'    => 'America/Caracas',
            'activo'          => true,
        ]);

        // Sembrar unidades mínimas para los tests (firstOrCreate para no chocar con el seeder)
        $this->und   = Unidad::firstOrCreate(['abreviatura' => 'und'],   ['nombre' => 'Unidad',       'tipo' => 'cantidad', 'factor_conversion' => 1,  'es_vendible' => true,  'es_logistica' => true]);
        $this->cja24 = Unidad::firstOrCreate(['abreviatura' => 'cja24'], ['nombre' => 'Caja x24',    'tipo' => 'cantidad', 'factor_conversion' => 24, 'base_id' => $this->und->id, 'es_vendible' => false, 'es_logistica' => true]);
        $this->bls10 = Unidad::firstOrCreate(['abreviatura' => 'bls10'], ['nombre' => 'Blister x10', 'tipo' => 'cantidad', 'factor_conversion' => 10, 'base_id' => $this->und->id, 'es_vendible' => true,  'es_logistica' => false]);
        $this->kg    = Unidad::firstOrCreate(['abreviatura' => 'kg'],    ['nombre' => 'Kilogramo',   'tipo' => 'peso',     'factor_conversion' => 1,  'es_vendible' => true,  'es_logistica' => true]);
        $this->lt    = Unidad::firstOrCreate(['abreviatura' => 'lt'],    ['nombre' => 'Litro',       'tipo' => 'volumen',  'factor_conversion' => 1,  'es_vendible' => true,  'es_logistica' => true]);

        // Almacenes de prueba (uniqueId para evitar colisiones entre tests)
        $uid = uniqid();
        $this->almacen1 = Almacen::create(['nombre' => "Depósito-{$uid}",  'tipo' => 'deposito',  'tienda_id' => $this->tienda->id]);
        $this->almacen2 = Almacen::create(['nombre' => "Mostrador-{$uid}", 'tipo' => 'exhibicion', 'tienda_id' => $this->tienda->id]);

        // Categoría de prueba
        $this->categoria = CategoriaProducto::firstOrCreate(
            ['slug' => 'test-category-' . uniqid(), 'tienda_id' => $this->tienda->id],
            ['nombre' => 'Test Category', 'nivel' => 1, 'activo' => true]
        );

        // Prevenir que el job asíncrono interfiera con los asserts del test
        Queue::fake();
    }

    // ════════════════════════════════════════════════════════════════════
    //  TEST 1 — Recibir SIN lote (modo PPS)
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function test_recibir_sin_lote_aplica_pps_correctamente(): void
    {
        $producto = $this->crearProducto(controlaLotes: false);
        $variante = $this->crearVariante($producto);

        // Primera recepción: 1 Caja x24 a $120 → 24 unidades a $5.00 c/u
        $result1 = $this->service->recibir(new RecepcionItemData(
            varianteId:    $variante->id,
            almacenId:     $this->almacen1->id,
            cantidad:      1,
            unidadOrigenId: $this->cja24->id,
            costoUnitario: 120.0,
            userId:        1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:  1,
        ));

        $inv = Inventario::find($result1->inventarioId);
        $this->assertEquals(24.0,      $inv->cantidad_disponible, 'Stock: 1 caja x24 = 24 und');
        $this->assertEquals(5.000000,  $inv->costo_promedio,      'Costo PPS: $120/24 = $5.00');
        $this->assertNull($result1->loteId, 'Sin controla_lotes no debe crear lote');
        Queue::assertPushed(RecalcularCostoPromedioProducto::class);

        // Segunda recepción: 12 unidades a $6.00 c/u → PPS debe promediar
        // Nuevo PPS = (24*5 + 12*6) / 36 = (120+72)/36 = $5.333...
        $this->service->recibir(new RecepcionItemData(
            varianteId:    $variante->id,
            almacenId:     $this->almacen1->id,
            cantidad:      12,
            unidadOrigenId: $this->und->id,
            costoUnitario: 6.0,
            userId:        1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:  2,
        ));

        $inv->refresh();
        $this->assertEquals(36.0, $inv->cantidad_disponible, 'Stock: 24 + 12 = 36');
        $this->assertEqualsWithDelta(5.333333, $inv->costo_promedio, 0.000001, 'PPS: (24×5 + 12×6)/36 = 5.3333');
    }

    // ════════════════════════════════════════════════════════════════════
    //  TEST 2 — Vender con FEFO multi-lote (farmacia)
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function test_vender_fefo_consume_primero_el_lote_que_vence_antes(): void
    {
        $producto = $this->crearProducto(controlaLotes: true);
        $variante = $this->crearVariante($producto);

        // Lote A: vence más tarde (2027-12) → entra primero (PEPS lo vendería primero, FEFO no)
        $this->service->recibir(new RecepcionItemData(
            varianteId:     $variante->id,
            almacenId:      $this->almacen1->id,
            cantidad:       30,
            unidadOrigenId: $this->und->id,
            costoUnitario:  2.0,
            userId:         1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:   10,
            lote:           'LOTE-A',
            fechaVencimiento: '2027-12-31',
        ));

        // Lote B: vence antes (2026-08) → entra después (FEFO lo debe vender PRIMERO)
        $this->service->recibir(new RecepcionItemData(
            varianteId:     $variante->id,
            almacenId:      $this->almacen1->id,
            cantidad:       20,
            unidadOrigenId: $this->und->id,
            costoUnitario:  2.5,
            userId:         1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:   11,
            lote:           'LOTE-B',
            fechaVencimiento: '2026-08-31',
        ));

        // Vender 25 unidades → debe consumir 20 de LOTE-B (vence antes) y 5 de LOTE-A
        $result = $this->service->vender(new VentaItemData(
            varianteId:    $variante->id,
            almacenId:     $this->almacen1->id,
            cantidadVenta: 25,
            unidadVentaId: $this->und->id,
            userId:        1,
            referenciaId:  100,
        ));

        $this->assertCount(2, $result->lotesConsumidos, 'Deben consumirse 2 lotes');

        $primerLote = $result->lotesConsumidos[0];
        $this->assertEquals('LOTE-B', $primerLote['lote'],    'FEFO: LOTE-B debe venderse primero');
        $this->assertEquals(20.0,     $primerLote['cantidad'], 'LOTE-B se agota completo');

        $segundoLote = $result->lotesConsumidos[1];
        $this->assertEquals('LOTE-A', $segundoLote['lote'],   'El resto sale de LOTE-A');
        $this->assertEquals(5.0,      $segundoLote['cantidad'], 'Solo 5 und de LOTE-A');

        // Verificar stock restante
        $lotA = InventarioLote::where('lote', 'LOTE-A')->first();
        $lotB = InventarioLote::where('lote', 'LOTE-B')->first();
        $this->assertEquals(25.0, $lotA->cantidad, 'LOTE-A: 30 - 5 = 25');
        $this->assertEquals(0.0,  $lotB->cantidad, 'LOTE-B: 20 - 20 = 0 (historial fiscal)');
    }

    // ════════════════════════════════════════════════════════════════════
    //  TEST 3 — Recibir CON lote (crea inventario_lotes correctamente)
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function test_recibir_con_lote_crea_registro_en_inventario_lotes(): void
    {
        $producto = $this->crearProducto(controlaLotes: true);
        $variante = $this->crearVariante($producto);

        $result = $this->service->recibir(new RecepcionItemData(
            varianteId:     $variante->id,
            almacenId:      $this->almacen1->id,
            cantidad:       2,            // 2 Blister x10 = 20 unidades base
            unidadOrigenId: $this->bls10->id,
            costoUnitario:  15.0,         // $15 por blister → $1.50 por unidad base
            userId:         1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:   20,
            lote:           'FARMA-2026-06',
            fechaVencimiento: '2026-12-31',
        ));

        // El trigger debe haber sincronizado inventario.cantidad_disponible
        $inv = Inventario::find($result->inventarioId);
        $this->assertEquals(20.0, $inv->cantidad_disponible, '2 blisters x10 = 20 unidades base');

        // El lote debe existir con costo por unidad base
        $lote = InventarioLote::find($result->loteId);
        $this->assertNotNull($lote, 'El lote debe haber sido creado');
        $this->assertEquals('FARMA-2026-06', $lote->lote);
        $this->assertEquals('2026-12-31',    $lote->fecha_vencimiento->toDateString());
        $this->assertEquals(20.0,            $lote->cantidad);
        $this->assertEqualsWithDelta(1.5,    $lote->costo_unitario, 0.000001, '$15/10 = $1.50 por unidad base');
    }

    // ════════════════════════════════════════════════════════════════════
    //  TEST 4 — Traslado con lote (replica el lote en almacén destino)
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function test_traslado_con_lote_replica_en_almacen_destino(): void
    {
        $producto = $this->crearProducto(controlaLotes: true);
        $variante = $this->crearVariante($producto);

        // Primero recibir en almacen1
        $recepcion = $this->service->recibir(new RecepcionItemData(
            varianteId:     $variante->id,
            almacenId:      $this->almacen1->id,
            cantidad:       50,
            unidadOrigenId: $this->und->id,
            costoUnitario:  3.0,
            userId:         1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:   30,
            lote:           'TRASLADO-L1',
            fechaVencimiento: '2027-06-30',
        ));

        // Trasladar 20 unidades con el lote al almacen2
        $result = $this->service->trasladar(new TrasladoItemData(
            varianteId:      $variante->id,
            almacenOrigenId: $this->almacen1->id,
            almacenDestinoId: $this->almacen2->id,
            cantidad:        20,
            unidadId:        $this->und->id,
            userId:          1,
            referenciaId:    200,
            loteOrigenId:    $recepcion->loteId,
        ));

        // Verificar lote origen descontado
        $loteOrigen = InventarioLote::find($recepcion->loteId);
        $this->assertEquals(30.0, $loteOrigen->cantidad, 'Origen: 50 - 20 = 30');

        // Verificar que existe inventario en destino
        $invDestino = Inventario::where('variante_id', $variante->id)
            ->where('almacen_id', $this->almacen2->id)
            ->first();
        $this->assertNotNull($invDestino, 'Debe existir inventario en almacén destino');

        // Verificar que el lote existe en destino con el mismo código
        $loteDestino = InventarioLote::where('inventario_id', $invDestino->id)
            ->where('lote', 'TRASLADO-L1')
            ->first();
        $this->assertNotNull($loteDestino, 'El lote debe replicarse en el destino con el mismo código');
        $this->assertEquals(20.0,        $loteDestino->cantidad, 'Destino recibe 20 unidades');
        $this->assertEquals('2027-06-30', $loteDestino->fecha_vencimiento->toDateString(), 'Misma fecha de vencimiento');
    }

    // ════════════════════════════════════════════════════════════════════
    //  TEST 5 — Guard: no se puede vender más stock del disponible
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function test_vender_mas_del_stock_disponible_lanza_excepcion(): void
    {
        $this->expectException(StockInsuficienteException::class);

        $producto = $this->crearProducto(controlaLotes: false);
        $variante = $this->crearVariante($producto);

        // Solo hay 5 unidades
        $this->service->recibir(new RecepcionItemData(
            varianteId:     $variante->id,
            almacenId:      $this->almacen1->id,
            cantidad:       5,
            unidadOrigenId: $this->und->id,
            costoUnitario:  10.0,
            userId:         1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:   40,
        ));

        // Intentar vender 100 → debe explotar
        $this->service->vender(new VentaItemData(
            varianteId:    $variante->id,
            almacenId:     $this->almacen1->id,
            cantidadVenta: 100,
            unidadVentaId: $this->und->id,
            userId:        1,
            referenciaId:  400,
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  TEST 6 — Guard: coherencia dimensional (kg vs und)
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function test_recibir_con_unidad_de_tipo_diferente_lanza_excepcion(): void
    {
        $this->expectException(CoherenciaDimensionalException::class);

        // Producto con unidad base "und" (cantidad)
        $producto = $this->crearProducto(controlaLotes: false);
        $variante = $this->crearVariante($producto);

        // Intentar recibir en "kg" (peso) → incoherencia dimensional
        $this->service->recibir(new RecepcionItemData(
            varianteId:     $variante->id,
            almacenId:      $this->almacen1->id,
            cantidad:       10,
            unidadOrigenId: $this->kg->id, // ← tipo 'peso', el producto es 'cantidad'
            costoUnitario:  5.0,
            userId:         1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:   50,
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  TEST 7 — Guard: lote requerido si controla_lotes=true
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function test_recibir_sin_lote_en_producto_con_controla_lotes_lanza_excepcion(): void
    {
        $this->expectException(LoteRequeridoException::class);

        $producto = $this->crearProducto(controlaLotes: true);
        $variante = $this->crearVariante($producto);

        // Sin pasar lote ni fecha_vencimiento → debe explotar
        $this->service->recibir(new RecepcionItemData(
            varianteId:     $variante->id,
            almacenId:      $this->almacen1->id,
            cantidad:       10,
            unidadOrigenId: $this->und->id,
            costoUnitario:  5.0,
            userId:         1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:   60,
            lote:           null, // ← falta
            fechaVencimiento: null, // ← falta
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  TEST 8 — Guard: no se puede insertar lote en producto sin controla_lotes
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function test_recibir_con_lote_en_producto_sin_controla_lotes_lanza_excepcion(): void
    {
        $this->expectException(LoteRequeridoException::class);

        $producto = $this->crearProducto(controlaLotes: false);
        $variante = $this->crearVariante($producto);

        // El producto no controla lotes, pero le pasamos uno → debe explotar
        $this->service->recibir(new RecepcionItemData(
            varianteId:     $variante->id,
            almacenId:      $this->almacen1->id,
            cantidad:       10,
            unidadOrigenId: $this->und->id,
            costoUnitario:  5.0,
            userId:         1,
            referenciaTipo: 'recepciones_compra',
            referenciaId:   70,
            lote:           'LOTE-INVALIDO',
            fechaVencimiento: '2027-01-01',
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ════════════════════════════════════════════════════════════════════

    private function crearProducto(bool $controlaLotes = false): Producto
    {
        return Producto::create([
            'tienda_id'      => $this->tienda->id,
            'nombre'         => 'Producto Test ' . uniqid(),
            'codigo_sku'     => 'SKU-' . uniqid(),
            'categoria_id'   => $this->categoria->id,
            'unidad_id'      => $this->und->id,
            'moneda_precio'  => 'USD',
            'costo_promedio' => 0,
            'controla_lotes' => $controlaLotes,
            'activo'         => true,
        ]);
    }

    private function crearVariante(Producto $producto): VarianteProducto
    {
        return VarianteProducto::create([
            'tienda_id'    => $this->tienda->id,
            'producto_id'  => $producto->id,
            'codigo_barra' => 'COD-' . uniqid(),
            'factor_unidad'=> 1,
            'activo'       => true,
        ]);
    }
}
