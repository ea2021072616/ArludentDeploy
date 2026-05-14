<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * Comandos Artisan
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Scheduler - Tareas programadas
 */
Schedule::command('seguimiento:enviar-notificaciones')
    ->dailyAt('09:00')
    ->timezone('America/Lima')
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.from.address'))
    ->appendOutputTo(storage_path('logs/seguimiento-notificaciones.log'));
