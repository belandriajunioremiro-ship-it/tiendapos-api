<?php
namespace App\Services\Inventory\Results;

readonly class RecepcionResult
{
    public function __construct(
        public int $inventarioId,
        public float $cantidadBase,
        public float $costoUnitarioBase,
        public ?int $loteId,
        public int $movimientoId,
    ) {}
}
