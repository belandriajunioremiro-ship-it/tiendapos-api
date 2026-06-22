<?php
namespace App\Services\Inventory\DTO;

use App\Models\Unidad;

readonly class TrasladoItemData
{
    public function __construct(
        public int $varianteId,
        public int $almacenOrigenId,
        public int $almacenDestinoId,
        public float $cantidad,           // en unidad_origen (la de traslado)
        public int $unidadId,
        public int $userId,
        public int $referenciaId,         // traslado_id
        public ?int $loteOrigenId = null,
        public ?string $notas = null,
    ) {}

    public function cantidadBase(Unidad $unidad): float
    {
        return $this->cantidad * (float) $unidad->factor_conversion;
    }
}
