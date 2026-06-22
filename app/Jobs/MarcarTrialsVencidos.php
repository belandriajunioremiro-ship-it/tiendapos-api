<?php

namespace App\Jobs;

use App\Services\SuscripcionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MarcarTrialsVencidos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function handle(SuscripcionService $suscripcionService): void
    {
        $afectadas = $suscripcionService->marcarTrialsVencidos();

        if ($afectadas > 0) {
            Log::info("Suscripciones trial vencidas marcadas: {$afectadas}");
        }
    }
}
