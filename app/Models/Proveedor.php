<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proveedor extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'proveedores';
    protected $fillable = [ 'tienda_id',
        'tipo_documento', 'numero_documento', 'razon_social', 'nombre_comercial',
        'contacto', 'telefono', 'telefono2', 'email', 'direccion', 'pais',
        'moneda_compra', 'dias_entrega', 'credito_dias', 'limite_credito', 'notas', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'limite_credito' => 'float',
            'activo'         => 'boolean',
            'dias_entrega'   => 'integer',
            'credito_dias'   => 'integer',
        ];
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }

    public function productos()
    {
        return $this->hasMany(ProductoProveedor::class, 'proveedor_id');
    }
}

