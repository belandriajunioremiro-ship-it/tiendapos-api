<?php
namespace App\Services\Inventory\DTO;

use App\Models\Unidad;

readonly class VentaItemData
{
    public function __construct(
        public int $varianteId,
        public int $almacenId,
        public float $cantidadVenta,      // en unidad_venta (ej. 1 Caja x24)
        public int $unidadVentaId,        // unidad en que se vende
        public int $userId,
        public int $referenciaId,         // venta_id
        public ?string $notas = null,
    ) {}

    public function factor(Unidad $unidad): float
    {
        return (float) $unidad->factor_conversion;
    }

    public function cantidadBase(Unidad $unidad): float
    {
        return $this->cantidadVenta * $this->factor($unidad);
    }
}
