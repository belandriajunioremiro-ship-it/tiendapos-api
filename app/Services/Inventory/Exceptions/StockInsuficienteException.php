<?php
namespace App\Services\Inventory\Exceptions;

use RuntimeException;

class StockInsuficienteException extends RuntimeException
{
    public static function forVariante(int $varianteId, float $solicitada, float $disponible): self
    {
        return new self(
            "Stock insuficiente para variante_id={$varianteId}. " .
            "Solicitado: {$solicitada}, disponible: {$disponible}."
        );
    }

    public static function forLote(string $lote, float $solicitada, float $disponible): self
    {
        return new self(
            "Stock insuficiente en lote {$lote}. " .
            "Solicitado: {$solicitada}, disponible en el lote: {$disponible}."
        );
    }
}
