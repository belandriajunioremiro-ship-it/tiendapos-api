<?php

namespace App\Console\Commands;

use App\Models\Tienda;
use Illuminate\Console\Command;

class LimpiarDatosPrueba extends Command
{
    protected $signature = 'pos:limpiar
        {--force : Saltar confirmación}';
    protected $description = 'Elimina tiendas de prueba y sus datos en cascada. Mantiene tienda original + datos base.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('¿Eliminar todas las tiendas de prueba?') ) {
            return 0;
        }

        $tiendas = Tienda::where('rif', 'LIKE', 'TEST-%')
            ->orWhere('rif', 'LIKE', 'TEMP-%')
            ->orWhere('razon_social', 'LIKE', 'Tienda Test%')
            ->orWhere('razon_social', 'LIKE', 'Tienda en configuración%')
            ->get();

        if ($tiendas->isEmpty()) {
            $this->info('No hay tiendas de prueba para limpiar.');
            return 0;
        }

        $this->warn("Se eliminarán {$tiendas->count()} tiendas de prueba:");

        foreach ($tiendas as $t) {
            $this->line("  ID {$t->id} — {$t->razon_social} ({$t->pais})");
        }

        if (! $this->option('force')) {
            $this->line('');
            if (! $this->confirm('¿Continuar?')) {
                return 0;
            }
        }

        foreach ($tiendas as $t) {
            $this->line("  <fg=red>✗</> Eliminando tienda {$t->id} — {$t->razon_social}");
            $t->delete(); // FK cascade elimina todo: users, productos, ventas, etc.
        }

        $this->info("{$tiendas->count()} tiendas eliminadas.");
        return 0;
    }
}
