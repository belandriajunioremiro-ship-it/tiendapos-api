<?php

namespace App\Policies;

use App\Models\User;

class UsuarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['ver_usuarios']);
    }

    public function view(User $user): bool
    {
        return $user->hasAnyPermission(['ver_usuarios']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['crear_usuarios']);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyPermission(['editar_usuarios']);
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyPermission(['eliminar_usuarios']);
    }
}
