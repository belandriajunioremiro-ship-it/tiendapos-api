<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'clientes';
    protected $fillable = [
        'tienda_id', 'lista_precio_id', 'moneda_credito', 'tipo_documento', 'numero_documento',
        'nombre', 'nombre_comercial', 'telefono', 'telefono2', 'email', 'direccion',
        'fecha_nacimiento', 'tipo_cliente', 'limite_credito', 'dias_credito', 'notas', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'limite_credito' => 'float',
            'activo'         => 'boolean',
            'dias_credito'   => 'integer',
        ];
    }

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    public function cuentaCredito()
    {
        return $this->hasOne(CuentaCredito::class, 'cliente_id');
    }

    public function facturasCredito()
    {
        return $this->hasMany(FacturaCredito::class, 'cliente_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nombre', 'ilike', "%{$term}%")
              ->orWhere('numero_documento', 'ilike', "%{$term}%")
              ->orWhere('telefono', 'ilike', "%{$term}%");
        });
    }
}

