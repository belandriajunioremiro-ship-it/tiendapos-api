<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Impuesto extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'impuestos';
    protected $fillable = [
        'tienda_id', 'nombre', 'porcentaje', 'tipo', 'aplica_a', 'es_defecto', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje' => 'float',
            'es_defecto' => 'boolean',
            'activo'     => 'boolean',
        ];
    }
}

