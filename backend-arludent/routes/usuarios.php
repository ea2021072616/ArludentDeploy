<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Usuarios\UsuarioController;
use App\Http\Controllers\Usuarios\MedicoPerfilController;

/**
 * Rutas de Usuarios
 *
 * Gestión de perfiles y datos de usuario
 * Requieren autenticación
 */

Route::prefix('usuarios')->middleware('auth:api')->group(function () {

    // Perfil del usuario autenticado
    Route::get('/perfil', [UsuarioController::class, 'getPerfil']);
    Route::put('/perfil', [UsuarioController::class, 'updatePerfil']);

    // Estado de registro del usuario (externo/paciente, historial clínico)
    Route::get('/estado-registro', [UsuarioController::class, 'getEstadoRegistro']);

    // Perfil médico (solo para médicos)
    Route::prefix('medico')->middleware('role:medico')->group(function () {
        Route::get('/perfil', [MedicoPerfilController::class, 'getPerfil']);
        Route::put('/perfil', [MedicoPerfilController::class, 'updatePerfil']);
        Route::delete('/perfil/foto', [MedicoPerfilController::class, 'eliminarFoto']);
    });

    // Listar usuarios (solo admin) - TODO: mover a admin.php
    Route::get('/', function () {
        // TODO: Implementar listado de usuarios
        return response()->json([
            'success' => true,
            'message' => 'Endpoint en desarrollo',
        ]);
    })->middleware('role:admin');
});
