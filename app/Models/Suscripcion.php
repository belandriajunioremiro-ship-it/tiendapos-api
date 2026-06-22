<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suscripcion extends TiendaPosModel
{
    public $timestamps = true;

    protected $table = 'suscripciones';

    protected $fillable = [
        'tienda_id',
        'plan_id',
        'estado',
        'inicio_trial',
        'fin_trial',
        'inicio_pago',
        'fin_periodo',
        'proximo_cobro',
        'metodo_pago',
        'referencia_pago',
        'auto_renovar',
        'cancelado_en',
        'cancelado_por',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'inicio_trial'  => 'datetime',
        'fin_trial'     => 'datetime',
        'inicio_pago'   => 'datetime',
        'fin_periodo'   => 'date',
        'proximo_cobro' => 'date',
        'auto_renovar'  => 'boolean',
        'cancelado_en'  => 'datetime',
    ];

    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plane::class, 'plan_id');
    }
}
