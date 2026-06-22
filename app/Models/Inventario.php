<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventario extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'inventario';
    protected $fillable = [
        'tienda_id', 'variante_id', 'almacen_id', 'cantidad_disponible', 'cantidad_reservada',
        'cantidad_en_transito', 'stock_minimo', 'stock_maximo', 'costo_promedio',
        'ultima_entrada', 'ultima_salida'
    ];

    protected function casts(): array
    {
        return [
            'cantidad_disponible' => 'float',
            'cantidad_reservada' => 'float',
            'cantidad_en_transito' => 'float',
            'stock_minimo' => 'float',
            'stock_maximo' => 'float',
            'costo_promedio' => 'float',
            'ultima_entrada' => 'datetime',
            'ultima_salida' => 'datetime',
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

    public function lotes()
    {
        return $this->hasMany(InventarioLote::class, 'inventario_id');
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('cantidad_disponible', '<=', 'stock_minimo')
                     ->where('stock_minimo', '>', 0);
    }

    public function scopeConStock($query)
    {
        return $query->where('cantidad_disponible', '>', 0);
    }
}
