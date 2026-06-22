<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MovimientoInventario extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'movimientos_inventario';

    protected $fillable = [ 'tienda_id',
        'variante_id', 'almacen_id', 'user_id', 'tipo', 'cantidad',
        'stock_anterior', 'stock_nuevo', 'costo_unitario', 'moneda_costo',
        'referencia_tipo', 'referencia_id', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'      => 'float',
            'stock_anterior'=> 'float',
            'stock_nuevo'   => 'float',
            'costo_unitario'=> 'float',
        ];
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
}

