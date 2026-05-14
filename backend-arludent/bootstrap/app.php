<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/auth.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Cargar rutas modulares de la API
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/auth.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/usuarios.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/admin.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/clinica.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/sistema.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/historialClinico.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/secretaria.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/internal.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware global para APIs
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Registrar middleware personalizados
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'internal.api' => \App\Http\Middleware\InternalApiKeyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
