<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AjusteInventario extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'ajustes_inventario';

    protected $fillable = [ 'tienda_id',
        'almacen_id', 'user_id', 'aprobado_por', 'numero',
        'motivo', 'estado', 'notas', 'aprobado_en',
    ];

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function items()
    {
        return $this->hasMany(ItemAjuste::class, 'ajuste_id');
    }
}

