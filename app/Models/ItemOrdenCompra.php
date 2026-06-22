<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemOrdenCompra extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'items_orden_compra';
protected $fillable = [ 'tienda_id',
        'orden_id', 'producto_id', 'variante_id', 'cantidad', 'cantidad_recibida',
        'costo_unitario', 'impuesto_pct', 'total_linea', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'          => 'float',
            'cantidad_recibida' => 'float',
            'costo_unitario'    => 'float',
            'impuesto_pct'      => 'float',
            'total_linea'       => 'float',
        ];
    }

    public function orden()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id');
    }
}

