<?php

namespace Tests\Unit;

use App\Services\Inventory\DTO\RecepcionItemData;
use App\Services\Inventory\DTO\VentaItemData;
use App\Services\Inventory\DTO\TrasladoItemData;
use App\Services\Inventory\Exceptions\StockInsuficienteException;
use App\Services\Inventory\Exceptions\LoteRequeridoException;
use App\Services\Inventory\Exceptions\CoherenciaDimensionalException;
use App\Services\Inventory\Exceptions\ConfiguracionInventarioException;
use App\Exceptions\Suscripcion\SuscripcionVencidaException;
use App\Exceptions\Suscripcion\LimitePlanExcedidoException;
use App\Models\Unidad;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DtoAndExceptionsTest extends TestCase
{
    // ── RecepcionItemData ──────────────────────────────────────────

    #[Test]
    public function recepcion_cantidadBase_multiplica_por_factor(): void
    {
        $dto = new RecepcionItemData(
            varianteId: 1,
            almacenId: 1,
            cantidad: 5.0,
            unidadOrigenId: 1,
            costoUnitario: 120.0,
            userId: 1,
            referenciaTipo: 'recepciones_compra',
            referenciaId: 1,
        );

        $unidad = new Unidad();
        $unidad->factor_conversion = 24;

        $this->assertEquals(120.0, $dto->cantidadBase($unidad));
    }

    #[Test]
    public function recepcion_costoPorUnidadBase_divide_por_factor(): void
    {
        $dto = new RecepcionItemData(
            varianteId: 1,
            almacenId: 1,
            cantidad: 1.0,
            unidadOrigenId: 1,
            costoUnitario: 120.0,
            userId: 1,
            referenciaTipo: 'recepciones_compra',
            referenciaId: 1,
        );

        $unidad = new Unidad();
        $unidad->factor_conversion = 24;

        $this->assertEquals(5.0, $dto->costoPorUnidadBase($unidad));
    }

    #[Test]
    public function recepcion_costoPorUnidadBase_lanza_domain_exception_si_factor_cero(): void
    {
        $dto = new RecepcionItemData(
            varianteId: 1,
            almacenId: 1,
            cantidad: 1.0,
            unidadOrigenId: 1,
            costoUnitario: 100.0,
            userId: 1,
            referenciaTipo: 'recepciones_compra',
            referenciaId: 1,
        );

        $unidad = new Unidad();
        $unidad->factor_conversion = 0;

        $this->expectException(\DomainException::class);
        $dto->costoPorUnidadBase($unidad);
    }

    // ── VentaItemData ──────────────────────────────────────────────

    #[Test]
    public function venta_cantidadBase_multiplica_por_factor(): void
    {
        $dto = new VentaItemData(
            varianteId: 1,
            almacenId: 1,
            cantidadVenta: 2.0,
            unidadVentaId: 1,
            userId: 1,
            referenciaId: 1,
        );

        $unidad = new Unidad();
        $unidad->factor_conversion = 12;

        $this->assertEquals(24.0, $dto->cantidadBase($unidad));
    }

    // ── TrasladoItemData ────────────────────────────────────────────

    #[Test]
    public function traslado_cantidadBase_multiplica_por_factor(): void
    {
        $dto = new TrasladoItemData(
            varianteId: 1,
            almacenOrigenId: 1,
            almacenDestinoId: 2,
            cantidad: 3.0,
            unidadId: 1,
            userId: 1,
            referenciaId: 1,
        );

        $unidad = new Unidad();
        $unidad->factor_conversion = 10;

        $this->assertEquals(30.0, $dto->cantidadBase($unidad));
    }

    // ── Exceptions - Inventario ────────────────────────────────────

    #[Test]
    public function stockInsuficiente_contiene_mensaje_con_ids(): void
    {
        $e = StockInsuficienteException::forVariante(42, 100.0, 50.0);
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertStringContainsString('100', $e->getMessage());
        $this->assertStringContainsString('50', $e->getMessage());
    }

    #[Test]
    public function loteRequerido_productoControlaLotes_contiene_id_producto(): void
    {
        $e = LoteRequeridoException::productoControlaLotes(7);
        $this->assertStringContainsString('7', $e->getMessage());
    }

    #[Test]
    public function loteRequerido_productoNoControlaLotes_contiene_id_producto(): void
    {
        $e = LoteRequeridoException::productoNoControlaLotes(7);
        $this->assertStringContainsString('7', $e->getMessage());
    }

    #[Test]
    public function coherenciaDimensional_contiene_ids(): void
    {
        $e = new CoherenciaDimensionalException('Unidad 1 no es coherente con Unidad 2 para variante 3');
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    #[Test]
    public function configuracionInventario_es_runtime_exception(): void
    {
        $e = new ConfiguracionInventarioException('Producto no controla lotes');
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    // ── Exceptions - Suscripcion ───────────────────────────────────

    #[Test]
    public function suscripcionVencida_trialVencido_contiene_fecha(): void
    {
        $e = SuscripcionVencidaException::trialVencido('15/06/2026');
        $this->assertStringContainsString('15/06/2026', $e->getMessage());
    }

    #[Test]
    public function suscripcionVencida_suspendada_contiene_mensaje(): void
    {
        $e = SuscripcionVencidaException::suspendada('Falta de pago');
        $this->assertStringContainsString('suspendida', $e->getMessage());
    }

    #[Test]
    public function limitePlanExcedido_productos_contiene_limite(): void
    {
        $e = LimitePlanExcedidoException::productos(50);
        $this->assertStringContainsString('50', $e->getMessage());
    }

    #[Test]
    public function limitePlanExcedido_usuarios_contiene_limite(): void
    {
        $e = LimitePlanExcedidoException::usuarios(5);
        $this->assertStringContainsString('5', $e->getMessage());
    }

    #[Test]
    public function limitePlanExcedido_almacenes_contiene_limite(): void
    {
        $e = LimitePlanExcedidoException::almacenes(3);
        $this->assertStringContainsString('3', $e->getMessage());
    }

    #[Test]
    public function limitePlanExcedido_cajas_contiene_limite(): void
    {
        $e = LimitePlanExcedidoException::cajas(2);
        $this->assertStringContainsString('2', $e->getMessage());
    }

    // ── RecepcionItemData - valores nulos ─────────────────────────

    #[Test]
    public function recepcion_campos_opcionales_son_null_por_defecto(): void
    {
        $dto = new RecepcionItemData(
            varianteId: 1,
            almacenId: 1,
            cantidad: 10.0,
            unidadOrigenId: 1,
            costoUnitario: 50.0,
            userId: 1,
            referenciaTipo: 'recepciones_compra',
            referenciaId: 1,
        );

        $this->assertNull($dto->lote);
        $this->assertNull($dto->fechaVencimiento);
        $this->assertNull($dto->notas);
    }

    #[Test]
    public function recepcion_campos_opcionales_se_pueden_setear(): void
    {
        $dto = new RecepcionItemData(
            varianteId: 1,
            almacenId: 1,
            cantidad: 10.0,
            unidadOrigenId: 1,
            costoUnitario: 50.0,
            userId: 1,
            referenciaTipo: 'recepciones_compra',
            referenciaId: 1,
            lote: 'LOT-001',
            fechaVencimiento: '2026-12-31',
            notas: 'Recepcion de prueba',
        );

        $this->assertEquals('LOT-001', $dto->lote);
        $this->assertEquals('2026-12-31', $dto->fechaVencimiento);
        $this->assertEquals('Recepcion de prueba', $dto->notas);
    }

    // ── VentaItemData - valores nulos ──────────────────────────────

    #[Test]
    public function venta_notas_es_null_por_defecto(): void
    {
        $dto = new VentaItemData(
            varianteId: 1,
            almacenId: 1,
            cantidadVenta: 5.0,
            unidadVentaId: 1,
            userId: 1,
            referenciaId: 1,
        );

        $this->assertNull($dto->notas);
    }

    // ── TrasladoItemData - valores nulos ───────────────────────────

    #[Test]
    public function traslado_campos_opcionales_son_null_por_defecto(): void
    {
        $dto = new TrasladoItemData(
            varianteId: 1,
            almacenOrigenId: 1,
            almacenDestinoId: 2,
            cantidad: 5.0,
            unidadId: 1,
            userId: 1,
            referenciaId: 1,
        );

        $this->assertNull($dto->loteOrigenId);
        $this->assertNull($dto->notas);
    }

    #[Test]
    public function traslado_loteOrigenId_se_puede_setear(): void
    {
        $dto = new TrasladoItemData(
            varianteId: 1,
            almacenOrigenId: 1,
            almacenDestinoId: 2,
            cantidad: 5.0,
            unidadId: 1,
            userId: 1,
            referenciaId: 1,
            loteOrigenId: 99,
        );

        $this->assertEquals(99, $dto->loteOrigenId);
    }

    // ── Factor de conversion con decimales ─────────────────────────

    #[Test]
    public function recepcion_cantidadBase_con_factor_decimal(): void
    {
        $dto = new RecepcionItemData(
            varianteId: 1,
            almacenId: 1,
            cantidad: 10.0,
            unidadOrigenId: 1,
            costoUnitario: 100.0,
            userId: 1,
            referenciaTipo: 'recepciones_compra',
            referenciaId: 1,
        );

        $unidad = new Unidad();
        $unidad->factor_conversion = 0.5;

        $this->assertEquals(5.0, $dto->cantidadBase($unidad));
    }

    #[Test]
    public function venta_cantidadBase_con_factor_decimal(): void
    {
        $dto = new VentaItemData(
            varianteId: 1,
            almacenId: 1,
            cantidadVenta: 10.0,
            unidadVentaId: 1,
            userId: 1,
            referenciaId: 1,
        );

        $unidad = new Unidad();
        $unidad->factor_conversion = 0.25;

        $this->assertEquals(2.5, $dto->cantidadBase($unidad));
    }
}
