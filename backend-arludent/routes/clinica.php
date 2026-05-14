<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Citas\CitaPacienteController;
use App\Http\Controllers\Citas\CitaMedicoController;
use App\Http\Controllers\Usuarios\DisponibilidadMedicoController;

/**
 * Rutas de Clínica
 *
 * Módulo clínico: pacientes, médicos, citas, historiales
 * Estructura base preparada para desarrollo futuro
 */

Route::prefix('clinica')->middleware('auth:api')->group(function () {

    // ============ CITAS DEL PACIENTE ============
    Route::prefix('mis-citas')->group(function () {
        // Listar todas las citas del paciente
        Route::get('/', [CitaPacienteController::class, 'misCitas']);

        // Obtener citas para calendario
        Route::get('/calendario', [CitaPacienteController::class, 'misCitasCalendario']);

        // Detalle de una cita específica
        Route::get('/{id}', [CitaPacienteController::class, 'detalleCita']);

        // Confirmar cita
        Route::patch('/{id}/confirmar', [CitaPacienteController::class, 'confirmarCita']);

        // Reprogramar cita
        Route::patch('/{id}/reprogramar', [CitaPacienteController::class, 'reprogramarCita']);

        // Cancelar cita
        Route::patch('/{id}/cancelar', [CitaPacienteController::class, 'cancelarCita']);

        // Calificar cita
        Route::post('/{id}/calificar', [CitaPacienteController::class, 'calificarCita']);

        // Estadísticas de citas
        Route::get('/estadisticas/general', [CitaPacienteController::class, 'estadisticasCitas']);
    });

    // ============ CITAS DEL MÉDICO ============
    Route::prefix('medico/citas')->group(function () {
        // Listar todas las citas del médico
        Route::get('/', [CitaMedicoController::class, 'misCitas']);

        // Obtener citas para calendario
        Route::get('/calendario', [CitaMedicoController::class, 'misCitasCalendario']);

        // Detalle de una cita específica
        Route::get('/{id}', [CitaMedicoController::class, 'detalleCita']);

        // Cancelar cita
        Route::patch('/{id}/cancelar', [CitaMedicoController::class, 'cancelarCita']);

        // Marcar cita como completada
        Route::patch('/{id}/completar', [CitaMedicoController::class, 'completarCita']);

        // Agregar notas a cita
        Route::patch('/{id}/notas', [CitaMedicoController::class, 'agregarNotas']);

        // Estadísticas de citas
        Route::get('/estadisticas/general', [CitaMedicoController::class, 'estadisticasCitas']);
    });

    // ============ DISPONIBILIDAD DEL MÉDICO ============
    Route::prefix('medico/disponibilidad')->group(function () {
        // Listar disponibilidad del médico
        Route::get('/', [DisponibilidadMedicoController::class, 'index']);

        // Crear nueva disponibilidad
        Route::post('/', [DisponibilidadMedicoController::class, 'store']);

        // Actualizar disponibilidad
        Route::put('/{id}', [DisponibilidadMedicoController::class, 'update']);

        // Eliminar disponibilidad
        Route::delete('/{id}', [DisponibilidadMedicoController::class, 'destroy']);

        // Obtener horarios disponibles para agendar citas
        Route::get('/horarios-disponibles', [DisponibilidadMedicoController::class, 'horariosDisponibles']);
    });

    // Pacientes
    Route::prefix('pacientes')->group(function () {
        Route::get('/', function () {
            return response()->json([
                'success' => true,
                'message' => 'Listado de pacientes - Endpoint en desarrollo',
            ]);
        });
    });

    // Pagos del paciente
    Route::prefix('mis-pagos')->group(function () {
        Route::get('/', [\App\Http\Controllers\Facturacion\PagoPacienteController::class, 'misPagos']);
        Route::get('/{id}/pdf', [\App\Http\Controllers\Facturacion\PagoPacienteController::class, 'descargarPDFPago']);
    });

    // Médicos
    Route::prefix('medicos')->group(function () {
        Route::get('/', function () {
            return response()->json([
                'success' => true,
                'message' => 'Listado de médicos - Endpoint en desarrollo',
            ]);
        });
    });

    // Citas
    Route::prefix('citas')->group(function () {
        Route::get('/', function () {
            return response()->json([
                'success' => true,
                'message' => 'Gestión de citas - Endpoint en desarrollo',
            ]);
        });
    });

    // ============ HISTORIALES CLÍNICOS ============
    // NOTA: Las rutas de historial clínico se movieron a routes/historialClinico.php
    // Este bloque se mantiene por compatibilidad, pero se recomienda usar las nuevas rutas

    // Tratamientos
    Route::prefix('tratamientos')->group(function () {
        Route::get('/', function () {
            return response()->json([
                'success' => true,
                'message' => 'Catálogo de tratamientos - Endpoint en desarrollo',
            ]);
        });
    });
});
