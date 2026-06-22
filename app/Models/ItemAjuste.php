<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemAjuste extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'items_ajuste';
protected $fillable = [ 'tienda_id',
        'ajuste_id', 'variante_id', 'cantidad_sistema',
        'cantidad_fisica', 'costo_unitario', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_sistema' => 'float',
            'cantidad_fisica'  => 'float',
            'costo_unitario'   => 'float',
        ];
    }

    public function ajuste()
    {
        return $this->belongsTo(AjusteInventario::class, 'ajuste_id');
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id');
    }
}

