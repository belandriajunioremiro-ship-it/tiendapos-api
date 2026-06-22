<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoriaProducto extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'categorias_productos';
protected $fillable = [ 'tienda_id',
        'padre_id', 'nombre', 'slug', 'nivel', 'ruta', 'icono', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'nivel'  => 'integer',
        ];
    }

    public function padre()
    {
        return $this->belongsTo(CategoriaProducto::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(CategoriaProducto::class, 'padre_id');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}

