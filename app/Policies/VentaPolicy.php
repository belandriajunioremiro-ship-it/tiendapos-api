<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venta;

class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['ver_ventas']);
    }

    public function view(User $user, Venta $venta): bool
    {
        return $user->hasAnyPermission(['ver_ventas']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['crear_venta']);
    }

    public function update(User $user, Venta $venta): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->id === $venta->user_id;
    }

    public function delete(User $user, Venta $venta): bool
    {
        return $user->hasAnyPermission(['anular_venta']);
    }
}
