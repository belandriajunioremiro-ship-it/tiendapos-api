<?php

namespace App\Jobs;

use App\Models\Inventario;
use App\Models\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarStockBajo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function handle(): void
    {
        $itemsBajoStock = Inventario::withoutTiendaScope()
            ->whereColumn('cantidad_disponible', '<=', 'stock_minimo')
            ->where('stock_minimo', '>', 0)
            ->with(['variante.producto', 'almacen'])
            ->get();

        $notificados = 0;

        foreach ($itemsBajoStock as $item) {
            $variante = $item->variante;
            $producto = $variante?->producto;

            if (!$producto) {
                continue;
            }

            $existe = Notificacion::where('tipo', 'stock_bajo')
                ->where('referencia_tipo', 'inventario')
                ->where('referencia_id', $item->id)
                ->where('leida', false)
                ->exists();

            if ($existe) {
                continue;
            }

            Notificacion::create([
                'tienda_id'       => $item->tienda_id,
                'tipo'            => 'stock_bajo',
                'titulo'          => 'Stock bajo: ' . $producto->nombre,
                'mensaje'         => "Quedan {$item->cantidad_disponible} unidades en {$item->almacen?->nombre} (mínimo: {$item->stock_minimo})",
                'referencia_tipo' => 'inventario',
                'referencia_id'   => $item->id,
                'leida'           => false,
            ]);

            $notificados++;
        }

        if ($notificados > 0) {
            Log::info("Notificaciones de stock bajo creadas: {$notificados}");
        }
    }
}
