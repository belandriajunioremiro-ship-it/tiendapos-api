<?php

namespace App\Traits;

use App\Models\Tienda;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTienda
{
    protected static function bootBelongsToTienda(): void
    {
        static::addGlobalScope('tienda', function (Builder $builder) {
            $tiendaId = static::resolveTiendaId();
            if ($tiendaId) {
                $builder->where($builder->getModel()->getTable() . '.tienda_id', $tiendaId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tienda_id)) {
                $tiendaId = static::resolveTiendaId();
                if ($tiendaId) {
                    $model->tienda_id = $tiendaId;
                }
            }
        });
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    public static function resolveTiendaId(): ?int
    {
        if (auth()->check() && auth()->user()->tienda_id) {
            return auth()->user()->tienda_id;
        }
        return null;
    }

    // ✅ CORREGIDO: static:: en lugar de parent::
    public static function withoutTiendaScope()
    {
        return static::query()->withoutGlobalScope('tienda');
    }
}