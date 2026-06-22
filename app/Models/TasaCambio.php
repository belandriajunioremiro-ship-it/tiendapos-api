<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TasaCambio extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'tasas_cambio';

    protected $fillable = [ 'tienda_id',
        'moneda_base', 'moneda_destino', 'tasa', 'fuente', 'fecha', 'activa', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'tasa'   => 'float',
            'activa' => 'boolean',
        ];
    }
}

