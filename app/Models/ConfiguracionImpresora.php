<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionImpresora extends TiendaPosModel
{
    use BelongsToTienda;

    public $timestamps = true;

    protected $table = 'configuracion_impresora';

    protected $fillable = [
        'tienda_id',
        'caja_id',
        'nombre',
        'tipo',
        'marca',
        'conexion',
        'ip',
        'puerto',
        'copias',
        'imprime_logo',
        'imprime_qr',
        'plantilla_id',
        'activa',
    ];

    protected $casts = [
        'copias'       => 'integer',
        'puerto'       => 'integer',
        'imprime_logo' => 'boolean',
        'imprime_qr'   => 'boolean',
        'activa'       => 'boolean',
    ];

    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillasImpresion::class, 'plantilla_id');
    }
}
