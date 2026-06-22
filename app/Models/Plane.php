<?php

namespace App\Models;

class Plane extends TiendaPosModel
{
    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_mensual',
        'moneda',
        'limite_productos',
        'limite_usuarios',
        'limite_almacenes',
        'limite_cajas',
        'dias_trial',
        'soporte',
        'caracteristicas',
        'activo',
    ];

    protected $casts = [
        'precio_mensual'   => 'decimal:2',
        'limite_productos' => 'integer',
        'limite_usuarios'  => 'integer',
        'limite_almacenes' => 'integer',
        'limite_cajas'     => 'integer',
        'dias_trial'       => 'integer',
        'caracteristicas'  => 'array',
        'activo'           => 'boolean',
    ];

    public $timestamps = false;

    public $incrementing = false;
    protected $keyType = 'int';
}
