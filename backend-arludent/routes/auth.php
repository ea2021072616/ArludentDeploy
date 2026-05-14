<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\MFAController;
use App\Http\Controllers\Auth\PasswordResetController;

/**
 * Rutas de Autenticación
 *
 * Agrupa todos los endpoints relacionados con autenticación
 * Incluye: registro, login, verificación, MFA, recuperación de contraseña
 */

Route::prefix('auth')->group(function () {

    // Registro y verificación
    Route::post('/registro', [RegisterController::class, 'register'])
        ->middleware('throttle:' . config('throttle.register', '3,1'));

    Route::get('/verificar-correo', [VerificationController::class, 'verify']);
    Route::post('/reenviar-verificacion', [VerificationController::class, 'resend']);

    // Login y sesión
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:' . config('throttle.login', '5,1'));

    Route::post('/logout', [LoginController::class, 'logout'])
        ->middleware('auth:api');

    Route::post('/refresh', [LoginController::class, 'refresh'])
        ->middleware('auth:api');

    Route::get('/me', [LoginController::class, 'me'])
        ->middleware('auth:api');

    // MFA (Autenticación de dos factores)
    Route::prefix('mfa')->group(function () {
        // Sin autenticación (para verificar en login)
        Route::post('/verificar-login', [MFAController::class, 'verifyLogin']);

        // Con autenticación
        Route::middleware('auth:api')->group(function () {
            Route::post('/generar', [MFAController::class, 'generate']);
            Route::post('/activar', [MFAController::class, 'enable']);
            Route::post('/desactivar', [MFAController::class, 'disable']);
        });
    });

    // Recuperación de contraseña
    Route::post('/recuperar-password', [PasswordResetController::class, 'requestReset']);
    Route::post('/restablecer-password', [PasswordResetController::class, 'reset']);
    Route::post('/cambiar-password', [PasswordResetController::class, 'change'])
        ->middleware('auth:api');
});
