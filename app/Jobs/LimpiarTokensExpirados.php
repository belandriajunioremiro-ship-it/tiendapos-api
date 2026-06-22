<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class LimpiarTokensExpirados implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function handle(): void
    {
        $eliminados = PersonalAccessToken::where('expires_at', '<', now())->delete();

        if ($eliminados > 0) {
            Log::info("Tokens expirados eliminados: {$eliminados}");
        }
    }
}
