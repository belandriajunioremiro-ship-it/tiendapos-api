<?php
namespace App\Services\Inventory\Results;

readonly class TrasladoResult
{
    public function __construct(
        public int $movimientoSalidaId,
        public int $movimientoEntradaId,
        public float $cantidadBase,
        public float $factorSnapshot,
        public ?int $loteOrigenId,
        public ?int $loteDestinoId,
    ) {}
}
