<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemDevolucion extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'items_devolucion';
protected $fillable = [ 'tienda_id',
        'devolucion_id', 'item_venta_id', 'variante_id',
        'cantidad', 'precio_unitario', 'impuesto_monto', 'total_linea', 'motivo',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'       => 'float',
            'precio_unitario'=> 'float',
            'impuesto_monto' => 'float',
            'total_linea'    => 'float',
        ];
    }

    public function devolucion()
    {
        return $this->belongsTo(DevolucionVenta::class, 'devolucion_id');
    }

    public function itemVenta()
    {
        return $this->belongsTo(ItemVenta::class, 'item_venta_id');
    }
}

