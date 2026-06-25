<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionClinica\HistorialClinicoController;
use App\Http\Controllers\GestionClinica\PacienteController;
use App\Http\Controllers\GestionClinica\OdontogramaController;
use App\Http\Controllers\GestionClinica\DetalleHistorialController;
use App\Http\Controllers\GestionClinica\TratamientoHistorialController;
use App\Http\Controllers\GestionClinica\PrescripcionController;
use App\Http\Controllers\GestionClinica\SeguimientoTratamientoController;

/**
 * Rutas del módulo Historial Clínico
 *
 * Agrupa todas las rutas relacionadas con historiales clínicos,
 * pacientes y odontogramas
 */

Route::prefix('clinica')->middleware(['auth:api'])->group(function () {

    // ============ HISTORIALES CLÍNICOS ============
    Route::prefix('historiales')->group(function () {
        Route::get('/', [HistorialClinicoController::class, 'index']);
        Route::post('/', [HistorialClinicoController::class, 'store']);
        Route::get('/{id}', [HistorialClinicoController::class, 'show']);
        Route::put('/{id}', [HistorialClinicoController::class, 'update']);
        Route::get('/{id}/pdf', [HistorialClinicoController::class, 'exportarPdf']);

        // Detalles de consultas
        Route::post('/{id}/detalles', [DetalleHistorialController::class, 'store']);
        Route::put('/detalles/{id}', [DetalleHistorialController::class, 'update']);
        Route::delete('/detalles/{id}', [DetalleHistorialController::class, 'destroy']);

        // Tratamientos del historial
        Route::post('/{id}/tratamientos', [TratamientoHistorialController::class, 'store']);
        Route::put('/tratamientos/{id}', [TratamientoHistorialController::class, 'update']);
        Route::delete('/tratamientos/{id}', [TratamientoHistorialController::class, 'destroy']);

        // Prescripciones
        Route::post('/{id}/prescripciones', [PrescripcionController::class, 'store']);
        Route::put('/prescripciones/{id}', [PrescripcionController::class, 'update']);
        Route::delete('/prescripciones/{id}', [PrescripcionController::class, 'destroy']);

        // Seguimientos de tratamientos
        Route::post('/{id}/tratamientos/{idTratamiento}/seguimientos', [SeguimientoTratamientoController::class, 'store']);
        Route::put('/{id}/tratamientos/{idTratamiento}/seguimientos/{idSeguimiento}', [SeguimientoTratamientoController::class, 'update']);
        Route::delete('/{id}/tratamientos/{idTratamiento}/seguimientos/{idSeguimiento}', [SeguimientoTratamientoController::class, 'destroy']);
    });

    // Catálogo de tratamientos disponibles
    Route::get('/catalogo-tratamientos', [TratamientoHistorialController::class, 'catalogoTratamientos']);

    // Historial del paciente autenticado
    Route::get('/mi-historial', [HistorialClinicoController::class, 'miHistorial']);

    // ============ GESTIÓN DE PACIENTES ============
    Route::prefix('pacientes')->group(function () {
        Route::get('/', [PacienteController::class, 'index']);
        Route::get('/{id}', [PacienteController::class, 'show']);
        Route::put('/{id}', [PacienteController::class, 'update']);
        Route::get('/{id}/historial-resumen', [PacienteController::class, 'getHistorialResumen']);
    });

    // ============ ODONTOGRAMA ============
    Route::prefix('odontograma')->group(function () {
        Route::get('/historial/{id}', [OdontogramaController::class, 'getByHistorial']);
        Route::post('/', [OdontogramaController::class, 'store']);
        Route::put('/{id}', [OdontogramaController::class, 'update']);
        Route::delete('/{id}', [OdontogramaController::class, 'destroy']);
    });

});
