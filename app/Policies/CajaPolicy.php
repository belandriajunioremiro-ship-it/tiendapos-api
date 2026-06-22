<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Caja;

class CajaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['ver_caja']);
    }

    public function view(User $user, Caja $caja): bool
    {
        return $user->hasAnyPermission(['ver_caja']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Caja $caja): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Caja $caja): bool
    {
        return $user->hasRole('admin');
    }

    public function abrir(User $user): bool
    {
        return $user->hasAnyPermission(['abrir_caja']);
    }

    public function cerrar(User $user): bool
    {
        return $user->hasAnyPermission(['cerrar_caja']);
    }
}
