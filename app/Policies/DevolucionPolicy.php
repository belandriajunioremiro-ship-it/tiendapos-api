<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DevolucionVenta;

class DevolucionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['ver_devoluciones']);
    }

    public function view(User $user, DevolucionVenta $devolucion): bool
    {
        return $user->hasAnyPermission(['ver_devoluciones']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['crear_devolucion']);
    }
}
