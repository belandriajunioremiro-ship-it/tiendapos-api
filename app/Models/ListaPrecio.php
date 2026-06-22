<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ListaPrecio extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'listas_precio';
protected $fillable = [ 'tienda_id',
        'nombre', 'tipo', 'valor', 'descripcion', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'valor'  => 'float',
            'activo' => 'boolean',
        ];
    }
}

