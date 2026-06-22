<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacturaCredito extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'facturas_credito';

    protected $fillable = [ 'tienda_id',
        'venta_id', 'cliente_id', 'cuenta_credito_id', 'moneda',
        'monto_total', 'saldo_pendiente', 'dias_plazo',
        'fecha_emision', 'fecha_vence', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'monto_total'     => 'float',
            'saldo_pendiente' => 'float',
            'dias_plazo'      => 'integer',
        ];
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function cuenta()
    {
        return $this->belongsTo(CuentaCredito::class, 'cuenta_credito_id');
    }

    public function abonos()
    {
        return $this->hasMany(AbonoCredito::class, 'factura_credito_id');
    }
}

