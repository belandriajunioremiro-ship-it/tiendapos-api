<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrasladoStock extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'traslados_stock';

    protected $fillable = [ 'tienda_id',
        'almacen_origen_id', 'almacen_destino_id', 'user_id', 'recibido_por',
        'numero', 'estado', 'notas', 'enviado_en', 'recibido_en',
    ];

    public function origen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function destino()
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function items()
    {
        return $this->hasMany(ItemTraslado::class, 'traslado_id');
    }
}

