<?php

namespace App\Models;

use App\Traits\BelongsToTienda;

class PlantillasImpresion extends TiendaPosModel
{
    use BelongsToTienda;

    protected $table = 'plantillas_impresion';

    protected $fillable = [ 'tienda_id',
        'nombre',
        'tipo',
        'contenido_html',
        'es_defecto',
        'activo',
    ];

    protected $casts = [
        'es_defecto' => 'boolean',
        'activo'     => 'boolean',
    ];

    public $timestamps = false;
}
