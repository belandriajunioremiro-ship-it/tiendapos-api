<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuentaCredito extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'cuentas_credito';
    protected $fillable = [ 'tienda_id',
        'cliente_id', 'moneda', 'limite', 'saldo_usado', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'limite'     => 'float',
            'saldo_usado' => 'float',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function facturas()
    {
        return $this->hasMany(FacturaCredito::class, 'cuenta_credito_id');
    }
}
