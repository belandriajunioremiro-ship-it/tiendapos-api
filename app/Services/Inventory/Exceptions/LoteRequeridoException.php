<?php
namespace App\Services\Inventory\Exceptions;

use RuntimeException;

class LoteRequeridoException extends RuntimeException
{
    public static function productoControlaLotes(int $productoId): self
    {
        return new self(
            "El producto_id={$productoId} tiene controla_lotes=TRUE. " .
            "Debe especificar lote y fecha_vencimiento en la recepción."
        );
    }

    public static function productoNoControlaLotes(int $productoId): self
    {
        return new self(
            "El producto_id={$productoId} tiene controla_lotes=FALSE. " .
            "No se puede especificar lote para este producto."
        );
    }
}
