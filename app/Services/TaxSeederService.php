<?php

namespace App\Services;

use App\Models\Impuesto;

class TaxSeederService
{
    public function sembrar(string $pais, int $tiendaId): void
    {
        Impuesto::where('es_defecto', true)
            ->where('tienda_id', $tiendaId)
            ->update(['es_defecto' => false]);

        $impuestos = match ($pais) {
            'VE' => [
                ['IVA 16%', 16, 'iva', true],
                ['IVA 8%', 8, 'iva', false],
                ['Exento', 0, 'exento', false],
            ],
            'CO' => [
                ['IVA 19%', 19, 'iva', true],
                ['IVA 5%', 5, 'iva', false],
                ['Exento', 0, 'exento', false],
            ],
            'MX' => [
                ['IVA 16%', 16, 'iva', true],
                ['IVA 0%', 0, 'iva', false],
                ['Exento', 0, 'exento', false],
            ],
            'EC' => [
                ['IVA 15%', 15, 'iva', true],
                ['IVA 0%', 0, 'iva', false],
                ['Exento', 0, 'exento', false],
            ],
            'AR' => [
                ['IVA 21%', 21, 'iva', true],
                ['IVA 10.5%', 10.5, 'iva', false],
                ['Exento', 0, 'exento', false],
            ],
            'PE' => [
                ['IGV 18%', 18, 'iva', true],
                ['Exento', 0, 'exento', false],
            ],
            'CL' => [
                ['IVA 19%', 19, 'iva', true],
                ['Exento', 0, 'exento', false],
            ],
            'BO' => [
                ['IVA 13%', 13, 'iva', true],
                ['Exento', 0, 'exento', false],
            ],
            'UY' => [
                ['IVA 22%', 22, 'iva', true],
                ['IVA 10%', 10, 'iva', false],
                ['Exento', 0, 'exento', false],
            ],
            default => [
                ['IVA 16%', 16, 'iva', true],
                ['Exento', 0, 'exento', false],
            ],
        };

        foreach ($impuestos as $imp) {
            Impuesto::firstOrCreate(
                ['nombre' => $imp[0], 'tienda_id' => $tiendaId],
                [
                    'porcentaje' => $imp[1],
                    'tipo'       => $imp[2],
                    'aplica_a'   => 'ambos',
                    'es_defecto' => false,
                    'activo'     => true,
                ]
            );
        }

        $default = Impuesto::where('nombre', $impuestos[0][0])
            ->where('tienda_id', $tiendaId)
            ->first();
        if ($default) {
            $default->update(['es_defecto' => true]);
        }
    }
}
