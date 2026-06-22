<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tienda extends TiendaPosModel
{

    public $timestamps = false;
    use HasFactory;

    protected $table = 'tienda';
    protected $fillable = [
        'rif',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'telefono',
        'email',
        'logo_url',
        'moneda_base',
        'moneda_pivot_api',
        'zona_horaria',
        'prefijo_factura',
        'siguiente_numero',
        'decimales_precio',
        'es_agente_igtf',
        'alicuota_igtf',
        'activo',
        'pais',                 // ← NECESARIO para OnboardingService y onboarding:show
        'regimen_fiscal',
        'actividad_economica',
        'codigo_postal',
        'sitio_web',
    ];

    protected function casts(): array
    {
        return [
            'es_agente_igtf' => 'boolean',
            'activo'         => 'boolean',
            'alicuota_igtf'  => 'float',
        ];
    }

    // ════════════════════════════════════════════════════════════════
    //  RELACIONES
    // ════════════════════════════════════════════════════════════════

    public function suscripcion()
    {
        return $this->hasOne(Suscripcion::class, 'tienda_id');
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'tienda_id');
    }

    public function almacenes()
    {
        return $this->hasMany(Almacen::class);
    }

    public function cajas()
    {
        return $this->hasMany(Caja::class);
    }
}
