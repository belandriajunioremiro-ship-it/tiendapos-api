<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemRecepcion extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'items_recepcion';
protected $fillable = [ 'tienda_id',
        'recepcion_id', 'item_orden_id', 'variante_id', 'cantidad_esperada',
        'cantidad_recibida', 'cantidad_rechazada', 'costo_unitario',
        'lote', 'fecha_vencimiento', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_esperada'  => 'float',
            'cantidad_recibida'  => 'float',
            'cantidad_rechazada' => 'float',
            'costo_unitario'     => 'float',
        ];
    }

    public function recepcion()
    {
        return $this->belongsTo(RecepcionCompra::class, 'recepcion_id');
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id');
    }
}

