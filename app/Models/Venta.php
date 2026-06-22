<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Venta extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'ventas';
    protected $fillable = [
        'tienda_id',
        'cliente_id',
        'caja_id',
        'sesion_caja_id',
        'almacen_id',
        'user_id',
        'numero_factura',
        'tipo_documento',
        'tipo_pago',
        'moneda_factura',
        'fuente_tasa',
        'subtotal',
        'descuento',
        'impuesto_iva',
        'impuesto_igtf',
        'total',
        'tasa_base_usada',
        'total_en_base',
        'estado',
        'notas'
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'float',
            'descuento' => 'float',
            'impuesto_iva' => 'float',
            'impuesto_igtf' => 'float',
            'total' => 'float',
            'tasa_base_usada' => 'float',
            'total_en_base' => 'float',
        ];
    }

    public function items()
    {
        return $this->hasMany(ItemVenta::class, 'venta_id');
    }

    public function pagos()
    {
        return $this->hasMany(PagoVenta::class, 'venta_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sesionCaja()
    {
        return $this->belongsTo(SesionCaja::class, 'sesion_caja_id');
    }

    public function devoluciones()
    {
        return $this->hasMany(DevolucionVenta::class, 'venta_id');
    }

    public function facturaCredito()
    {
        return $this->hasOne(FacturaCredito::class, 'venta_id');
    }

    public function scopeByEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeByFecha($query, string $desde, string $hasta)
    {
        return $query->whereDate('creado_en', '>=', $desde)
                     ->whereDate('creado_en', '<=', $hasta);
    }

    public function scopePagada($query)
    {
        return $query->where('estado', 'pagada');
    }
}

