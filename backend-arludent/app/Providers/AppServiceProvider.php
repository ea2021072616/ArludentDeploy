<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Medico;
use App\Observers\ActivityObserver;
use App\Observers\MedicoObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observer para auditoría
        User::observe(ActivityObserver::class);
        
        // Registrar observer para médicos (inicializar horarios de cabecera)
        Medico::observe(MedicoObserver::class);
    }
}
