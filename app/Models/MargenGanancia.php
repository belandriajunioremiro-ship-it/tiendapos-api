<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MargenGanancia extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'margenes_ganancia';
protected $fillable = [ 'tienda_id',
        'categoria_id', 'nombre', 'porcentaje', 'tipo', 'descripcion', 'es_defecto', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje' => 'float',
            'es_defecto' => 'boolean',
            'activo'     => 'boolean',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }
}

