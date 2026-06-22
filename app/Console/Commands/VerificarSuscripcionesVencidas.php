<?php

namespace App\Console\Commands;

use App\Services\SuscripcionService;
use Illuminate\Console\Command;

class VerificarSuscripcionesVencidas extends Command
{
    protected $signature = 'suscripciones:verificar-vencidas';

    protected $description = 'Marca como vencidas las suscripciones trial cuyo período de 14 días ha expirado.';

    public function handle(SuscripcionService $service): int
    {
        $this->info('Verificando suscripciones trial vencidas...');

        $cantidad = $service->marcarTrialsVencidos();

        if ($cantidad === 0) {
            $this->info('✓ No hay suscripciones trial vencidas.');
        } else {
            $this->warn("⚠ Se marcaron {$cantidad} suscripción(es) como vencidas.");
        }

        return self::SUCCESS;
    }
}
