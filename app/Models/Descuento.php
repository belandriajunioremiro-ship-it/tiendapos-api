<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Descuento extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'descuentos';

    protected $fillable = [ 'tienda_id',
        'nombre', 'tipo_aplicacion', 'producto_id', 'categoria_id', 'cliente_id',
        'valor_pct', 'maximo_pct', 'fecha_inicio', 'fecha_fin', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'valor_pct'  => 'float',
            'maximo_pct' => 'float',
            'activo'     => 'boolean',
        ];
    }
}

