<?php
namespace App\Jobs;

use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalcularCostoPromedioProducto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public int $productoId) {}

    public function handle(): void
    {
        $producto = Producto::find($this->productoId);
        if (!$producto) return;

        // Promedio ponderado del costo a través de todos los almacenes
        $promedio = Inventario::whereHas('variante', function ($q) {
            $q->where('producto_id', $this->productoId);
        })
        ->where('cantidad_disponible', '>', 0)
        ->select(DB::raw('SUM(cantidad_disponible * costo_promedio) / NULLIF(SUM(cantidad_disponible), 0) as promedio'))
        ->value('promedio');

        $producto->costo_promedio = $promedio ? round((float) $promedio, 6) : 0;
        $producto->saveQuietly(); // no dispara eventos/triggers de timestamps

        Log::debug('RecalcularCostoPromedioProducto OK', [
            'producto_id' => $this->productoId,
            'nuevo_costo' => $producto->costo_promedio,
        ]);
    }
}
