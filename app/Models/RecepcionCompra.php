<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecepcionCompra extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    const CREATED_AT = 'recibido_en';

    protected $table = 'recepciones_compra';
    protected $fillable = [ 'tienda_id',
        'orden_id', 'almacen_id', 'user_id', 'numero', 'estado', 'notas',
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function items()
    {
        return $this->hasMany(ItemRecepcion::class, 'recepcion_id');
    }
}

