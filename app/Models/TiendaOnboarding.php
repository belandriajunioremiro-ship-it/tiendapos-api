<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiendaOnboarding extends TiendaPosModel
{
    public $timestamps = true;

    protected $table = 'tienda_onboarding';

    protected $fillable = [
        'tienda_id',
        'paso_actual',
        'completado',
        'fecha_completado',
        'metadata',
    ];

    protected $casts = [
        'completado'       => 'boolean',
        'fecha_completado' => 'datetime',
        'metadata'         => 'array',
        'paso_actual'      => 'integer',
    ];

    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class);
    }
}
