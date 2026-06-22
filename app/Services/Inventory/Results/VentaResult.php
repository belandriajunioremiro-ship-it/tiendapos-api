<?php
namespace App\Services\Inventory\Results;

readonly class VentaResult
{
    /**
     * @param array<int, array{lote_id: int, lote: string, cantidad: float, costo_unitario: float}> $lotesConsumidos
     */
    public function __construct(
        public int $movimientoId,
        public float $cantidadBase,
        public float $factorSnapshot,
        public float $costoUnitarioBase,   // snapshot para items_venta.costo_unitario
        public array $lotesConsumidos,     // vacío si el producto no controla lotes
    ) {}
}
