<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VarianteProducto extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'variantes_producto';
    protected $fillable = [ 'tienda_id',
        'producto_id', 'codigo_barra', 'descripcion', 'factor_unidad', 'atributos', 'activo'
    ];

    protected function casts(): array
    {
        return [
            'atributos' => 'array',
            'activo' => 'boolean',
            'factor_unidad' => 'float',
        ];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'variante_id');
    }

    public function getControlaLotesAttribute(): bool
    {
        return (bool) $this->producto?->controla_lotes;
    }
}
