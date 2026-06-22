<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemTraslado extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'items_traslado';
protected $fillable = [ 'tienda_id',
        'traslado_id', 'variante_id', 'cantidad_enviada',
        'cantidad_recibida', 'costo_unitario', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_enviada'  => 'float',
            'cantidad_recibida' => 'float',
            'costo_unitario'    => 'float',
        ];
    }

    public function traslado()
    {
        return $this->belongsTo(TrasladoStock::class, 'traslado_id');
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id');
    }
}

