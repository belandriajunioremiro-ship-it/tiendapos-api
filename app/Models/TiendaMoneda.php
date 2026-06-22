<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiendaMoneda extends TiendaPosModel
{
    protected $table = 'tienda_monedas';

    protected $fillable = [
        'moneda',
        'acepta_ventas',
        'acepta_compras',
        'acepta_creditos',
        'orden_display',
        'activa',
    ];

    protected $casts = [
        'acepta_ventas'   => 'boolean',
        'acepta_compras'  => 'boolean',
        'acepta_creditos' => 'boolean',
        'activa'          => 'boolean',
        'orden_display'   => 'integer',
    ];

    public $timestamps = false;

    public function monedaRelacion(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda', 'codigo');
    }
}
