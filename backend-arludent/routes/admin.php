<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administracion\UsuarioAdminController;
use App\Http\Controllers\Administracion\TratamientoController;

/**
 * Rutas de Administración
 *
 * Gestión de usuarios, roles y configuraciones del sistema
 * Requieren autenticación y rol de admin
 */

Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {

    // Gestión de usuarios
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioAdminController::class, 'index']);
        Route::post('/', [UsuarioAdminController::class, 'store']);
        Route::get('/{id}', [UsuarioAdminController::class, 'show']);
        Route::put('/{id}', [UsuarioAdminController::class, 'update']);
        Route::delete('/{id}', [UsuarioAdminController::class, 'destroy']);
        Route::post('/{id}/cambiar-rol', [UsuarioAdminController::class, 'cambiarRol']);
    });

    // Gestión de tratamientos
    Route::prefix('tratamientos')->group(function () {
        Route::get('/', [TratamientoController::class, 'index']);
        Route::post('/', [TratamientoController::class, 'store']);
        Route::get('/categorias', [TratamientoController::class, 'categorias']);
        Route::get('/{id}', [TratamientoController::class, 'show']);
        Route::put('/{id}', [TratamientoController::class, 'update']);
        Route::post('/{id}/cambiar-estado', [TratamientoController::class, 'cambiarEstado']);
    });

});
