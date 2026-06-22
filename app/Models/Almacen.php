<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Almacen extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'almacenes';
protected $fillable = [ 'tienda_id',
        'nombre', 'tipo', 'direccion', 'responsable', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'almacen_id');
    }

    public function movimientosInventario()
    {
        return $this->hasMany(MovimientoInventario::class, 'almacen_id');
    }
}

