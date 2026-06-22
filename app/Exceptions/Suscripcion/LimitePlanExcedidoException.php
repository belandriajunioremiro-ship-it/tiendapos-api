<?php

namespace App\Exceptions\Suscripcion;

use RuntimeException;

class LimitePlanExcedidoException extends RuntimeException
{
    public static function productos(int $limite): self
    {
        return new self("Has alcanzado el límite de {$limite} productos de tu plan. Actualiza tu plan para agregar más.");
    }

    public static function usuarios(int $limite): self
    {
        return new self("Has alcanzado el límite de {$limite} usuarios de tu plan.");
    }

    public static function almacenes(int $limite): self
    {
        return new self("Has alcanzado el límite de {$limite} almacenes de tu plan.");
    }

    public static function cajas(int $limite): self
    {
        return new self("Has alcanzado el límite de {$limite} cajas de tu plan.");
    }
}
