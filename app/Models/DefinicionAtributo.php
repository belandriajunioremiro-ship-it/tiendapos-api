<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DefinicionAtributo extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'definicion_atributos';
protected $fillable = [ 'tienda_id',
        'categoria_id', 'clave', 'etiqueta', 'tipo_dato', 'opciones',
        'obligatorio', 'filtrable', 'en_listado', 'orden', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'opciones'   => 'array',
            'obligatorio'=> 'boolean',
            'filtrable'  => 'boolean',
            'en_listado' => 'boolean',
            'activo'     => 'boolean',
            'orden'      => 'integer',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }
}

