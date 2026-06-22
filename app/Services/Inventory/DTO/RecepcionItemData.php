<?php
namespace App\Services\Inventory\DTO;

use App\Models\Unidad;

readonly class RecepcionItemData
{
    public function __construct(
        public int $varianteId,
        public int $almacenId,
        public float $cantidad,           // en unidad_origen (ej. 1 Caja x24)
        public int $unidadOrigenId,       // id de la unidad en que se recibió
        public float $costoUnitario,      // costo por unidad_origen (ej. $120 por caja)
        public int $userId,
        public string $referenciaTipo,    // 'recepciones_compra'
        public int $referenciaId,
        public ?string $lote = null,
        public ?string $fechaVencimiento = null,
        public ?string $notas = null,
    ) {}

    public function factor(Unidad $unidad): float
    {
        return (float) $unidad->factor_conversion;
    }

    public function cantidadBase(Unidad $unidad): float
    {
        return $this->cantidad * $this->factor($unidad);
    }

    public function costoPorUnidadBase(Unidad $unidad): float
    {
        $factor = $this->factor($unidad);
        if ($factor == 0) {
            throw new \DomainException("Factor de conversión cero en unidad_id={$unidad->id}");
        }
        return $this->costoUnitario / $factor;
    }
}
