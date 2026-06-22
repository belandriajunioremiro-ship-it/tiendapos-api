<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DevolucionVenta extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'devoluciones_venta';

    protected $fillable = [ 'tienda_id',
        'venta_id', 'user_id', 'almacen_id', 'motivo', 'notas',
        'numero_nota_credito', 'estado', 'total_devuelto', 'moneda',
    ];

    protected function casts(): array
    {
        return [
            'total_devuelto' => 'float',
        ];
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function items()
    {
        return $this->hasMany(ItemDevolucion::class, 'devolucion_id');
    }
}

