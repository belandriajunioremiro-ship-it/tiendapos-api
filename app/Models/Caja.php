<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Caja extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'cajas';

    protected $fillable = [ 'tienda_id',
        'nombre', 'descripcion', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function sesiones()
    {
        return $this->hasMany(SesionCaja::class, 'caja_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'caja_id');
    }

    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }
}

