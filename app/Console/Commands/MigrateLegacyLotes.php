<?php

namespace App\Console\Commands;

use App\Models\Inventario;
use App\Models\InventarioLote;
use App\Models\Producto;
use App\Models\VarianteProducto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyLotes extends Command
{
    protected $signature = 'pos:migrate-legacy-lotes';
    protected $description = 'Migra lotes embebidos en variantes_producto.atributos a la tabla inventario_lotes (V3.2)';

    public function handle(): int
    {
        $this->info('Migrando lotes legacy (JSON → inventario_lotes)...');

        $variantesConLote = VarianteProducto::whereNotNull('atributos->lote')
            ->whereNotNull('atributos->fecha_venc')
            ->get();

        if ($variantesConLote->isEmpty()) {
            $this->info('No se encontraron variantes con lote embebido. Nada que migrar.');
            return self::SUCCESS;
        }

        $migrados = 0;
        $omitidos  = 0;

        foreach ($variantesConLote as $variante) {
            $atributos = $variante->atributos;
            $codigoLote = $atributos['lote'] ?? null;
            $fechaVenc  = $atributos['fecha_venc'] ?? null;

            if (!$codigoLote || !$fechaVenc) {
                $omitidos++;
                continue;
            }

            // Normalizar fecha (acepta '2026-06' o '2026-06-30')
            $fechaNormalizada = \Carbon\Carbon::parse($fechaVenc . '-01')->endOfMonth()->toDateString();

            // Marcar el producto como controla_lotes=TRUE
            $producto = Producto::find($variante->producto_id);
            if (!$producto) { $omitidos++; continue; }
            $producto->controla_lotes = true;
            $producto->save();

            // Por cada inventario donde esté la variante
            $inventarios = Inventario::where('variante_id', $variante->id)->get();
            foreach ($inventarios as $inv) {
                if ($inv->cantidad_disponible <= 0) continue;

                // Upsert con el mismo código de lote + inventario
                InventarioLote::updateOrCreate(
                    [
                        'inventario_id' => $inv->id,
                        'lote'          => $codigoLote,
                    ],
                    [
                        'fecha_vencimiento' => $fechaNormalizada,
                        'cantidad'          => $inv->cantidad_disponible,
                        'costo_unitario'    => $inv->costo_promedio,
                    ]
                );

                $migrados++;
                $this->line("  ✓ Variante {$variante->id} (lote {$codigoLote}) → inventario {$inv->id}");
            }
        }

        $this->info("Migración completa. Lotes migrados: {$migrados}. Omitidos: {$omitidos}.");
        return self::SUCCESS;
    }
}
