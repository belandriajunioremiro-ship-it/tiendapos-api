<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unidad extends TiendaPosModel
{

    public $timestamps = false;
    use HasFactory;

    protected $table = 'unidades';
    protected $fillable = [
        'base_id', 'nombre', 'abreviatura', 'tipo', 'factor_conversion', 'es_vendible', 'es_logistica', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'factor_conversion' => 'float',
            'es_vendible'       => 'boolean',
            'es_logistica'      => 'boolean',
            'activo'            => 'boolean',
        ];
    }

    public function unidadBase()
    {
        return $this->belongsTo(Unidad::class, 'base_id');
    }

    public function subUnidades()
    {
        return $this->hasMany(Unidad::class, 'base_id');
    }
}
