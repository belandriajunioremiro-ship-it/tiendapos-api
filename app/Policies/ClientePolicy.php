<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cliente;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['ver_clientes']);
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->hasAnyPermission(['ver_clientes']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['crear_clientes']);
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->hasAnyPermission(['editar_clientes']);
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->hasAnyPermission(['eliminar_clientes']);
    }
}
