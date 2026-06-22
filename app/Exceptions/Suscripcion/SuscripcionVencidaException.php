<?php

namespace App\Exceptions\Suscripcion;

use RuntimeException;

class SuscripcionVencidaException extends RuntimeException
{
    public static function trialVencido(string $finTrial): self
    {
        return new self("Tu período de prueba finalizó el {$finTrial}. " .
            "Suscríbete a un plan para continuar usando el sistema.");
    }

    public static function suspendada(string $motivo = ''): self
    {
        return new self("La suscripción está suspendida. {$motivo}");
    }
}
