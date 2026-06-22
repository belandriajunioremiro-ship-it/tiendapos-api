<?php
namespace App\Services\Inventory\Exceptions;

use RuntimeException;

class CoherenciaDimensionalException extends RuntimeException
{
    public static function tiposNoCoinciden(string $tipoOrigen, string $tipoBase, int $varianteId): self
    {
        return new self(
            "Incoherencia dimensional al operar variante_id={$varianteId}. " .
            "Unidad origen es tipo '{$tipoOrigen}' pero la unidad base del producto es tipo '{$tipoBase}'. " .
            "No se puede convertir entre tipos físicos distintos."
        );
    }
}
