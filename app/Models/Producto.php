<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'productos';
    protected $fillable = [
        'tienda_id', 'codigo_sku', 'nombre', 'categoria_id', 'unidad_id', 'impuesto_id', 'margen_id',
        'moneda_precio', 'costo_promedio', 'margen_pct', 'foto_url', 'referencia_interna',
        'descripcion', 'controla_lotes', 'activo'
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'controla_lotes' => 'boolean',
            'costo_promedio' => 'float',
            'precio_base' => 'float',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function variantes()
    {
        return $this->hasMany(VarianteProducto::class, 'producto_id');
    }

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class, 'impuesto_id');
    }

    public function margen()
    {
        return $this->belongsTo(MargenGanancia::class, 'margen_id');
    }

    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'producto_proveedor', 'producto_id', 'proveedor_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $term)
    {
        return $query->where('nombre', 'ilike', "%{$term}%")
                     ->orWhere('codigo_sku', 'ilike', "%{$term}%")
                     ->orWhere('codigo_barra', 'ilike', "%{$term}%");
    }
}
