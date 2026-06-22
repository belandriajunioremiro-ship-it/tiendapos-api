<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductoProveedor extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'producto_proveedor';
protected $fillable = [ 'tienda_id',
        'producto_id', 'proveedor_id', 'referencia_proveedor', 'costo_referencial',
        'moneda', 'plazo_entrega', 'es_preferido', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'costo_referencial' => 'float',
            'es_preferido'      => 'boolean',
            'activo'            => 'boolean',
        ];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}

