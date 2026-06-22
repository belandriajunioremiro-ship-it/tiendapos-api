<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inventario;

class InventarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['ver_inventario']);
    }

    public function view(User $user, Inventario $inventario): bool
    {
        return $user->hasAnyPermission(['ver_inventario']);
    }

    public function ajustar(User $user): bool
    {
        return $user->hasAnyPermission(['ajustar_inventario']);
    }
}
