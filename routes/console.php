<?php

use App\Jobs\LimpiarTokensExpirados;
use App\Jobs\MarcarTrialsVencidos;
use App\Jobs\NotificarStockBajo;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new MarcarTrialsVencidos)
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::job(new LimpiarTokensExpirados)
    ->dailyAt('04:00')
    ->withoutOverlapping();

Schedule::job(new NotificarStockBajo)
    ->dailyAt('06:00')
    ->withoutOverlapping();
