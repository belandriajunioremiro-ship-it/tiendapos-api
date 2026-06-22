<?php

namespace App\Models;

class OnboardingPaso extends TiendaPosModel
{
    protected $table = 'onboarding_pasos';

    protected $fillable = [
        'id',
        'clave',
        'nombre',
        'descripcion',
        'orden',
        'obligatorio',
        'activo',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'activo'      => 'boolean',
        'orden'       => 'integer',
    ];

    public $timestamps = false;

    public $incrementing = false;
    protected $keyType = 'int';
}
