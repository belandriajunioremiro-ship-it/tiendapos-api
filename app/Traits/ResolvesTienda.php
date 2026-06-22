<?php

namespace App\Traits;

use App\Models\Tienda;

trait ResolvesTienda
{
    protected function resolveTienda(): Tienda
    {
        $tiendaId = auth()->user()?->tienda_id;

        if ($tiendaId) {
            return Tienda::findOrFail($tiendaId);
        }

        return Tienda::firstOrFail();
    }
}
