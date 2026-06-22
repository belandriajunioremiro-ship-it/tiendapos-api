<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CuentaCredito;

class CuentaCreditoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['ver_creditos']);
    }

    public function view(User $user, CuentaCredito $cuentaCredito): bool
    {
        return $user->hasAnyPermission(['ver_creditos']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['crear_credito']);
    }

    public function update(User $user, CuentaCredito $cuentaCredito): bool
    {
        return $user->hasAnyPermission(['crear_credito']);
    }

    public function delete(User $user, CuentaCredito $cuentaCredito): bool
    {
        return $user->hasRole('admin');
    }
}
