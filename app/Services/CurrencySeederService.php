<?php

namespace App\Services;

use App\Models\TasaCambio;
use App\Models\TiendaMoneda;

class CurrencySeederService
{
    public function sembrarMonedas(string $pais, int $tiendaId): void
    {
        $monedas = match ($pais) {
            'VE' => [
                ['USD', true, true, true, 1],
                ['VES', true, false, true, 2],
            ],
            'CO' => [['COP', true, true, true, 1]],
            'MX' => [['MXN', true, true, true, 1]],
            'EC' => [['USD', true, true, true, 1]],
            'AR' => [
                ['ARS', true, true, true, 1],
                ['USD', true, false, false, 2],
            ],
            'PE' => [['PEN', true, true, true, 1]],
            'CL' => [['CLP', true, true, true, 1]],
            'BO' => [['BOB', true, true, true, 1]],
            'UY' => [['UYU', true, true, true, 1]],
            default => [['USD', true, true, true, 1]],
        };

        foreach ($monedas as $m) {
            TiendaMoneda::firstOrCreate(
                ['moneda' => $m[0]],
                [
                    'acepta_ventas'   => $m[1],
                    'acepta_compras'  => $m[2],
                    'acepta_creditos' => $m[3],
                    'orden_display'   => $m[4],
                    'activa'          => true,
                ]
            );
        }
    }

    public function sembrarTasaInicialVES(int $tiendaId): void
    {
        $existe = TasaCambio::where('moneda_base', 'USD')
            ->where('moneda_destino', 'VES')
            ->where('activa', true)
            ->where('tienda_id', $tiendaId)
            ->exists();

        if (!$existe) {
            TasaCambio::create([
                'tienda_id'      => $tiendaId,
                'moneda_base'    => 'USD',
                'moneda_destino' => 'VES',
                'tasa'           => 592.51,
                'fuente'         => 'BCV',
                'fecha'          => now()->toDateString(),
                'activa'         => true,
            ]);
        }
    }

    public function monedaBase(string $pais): string
    {
        return match ($pais) {
            'VE' => 'USD',
            'CO' => 'COP',
            'MX' => 'MXN',
            'EC' => 'USD',
            'AR' => 'ARS',
            'PE' => 'PEN',
            'CL' => 'CLP',
            'BO' => 'BOB',
            'UY' => 'UYU',
            default => 'USD',
        };
    }
}
