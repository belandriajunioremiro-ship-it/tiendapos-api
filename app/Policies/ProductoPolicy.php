<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Producto;

class ProductoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['ver_productos']);
    }

    public function view(User $user, Producto $producto): bool
    {
        return $user->hasAnyPermission(['ver_productos']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['crear_productos']);
    }

    public function update(User $user, Producto $producto): bool
    {
        return $user->hasAnyPermission(['editar_productos']);
    }

    public function delete(User $user, Producto $producto): bool
    {
        return $user->hasAnyPermission(['eliminar_productos']);
    }
}
