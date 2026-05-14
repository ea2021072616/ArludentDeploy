<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administracion\InternalApiController;

/*
|--------------------------------------------------------------------------
| Internal API Routes
|--------------------------------------------------------------------------
|
| Rutas internas para el microservicio de IA
| Requieren autenticación mediante X-Internal-API-Key header
| NO usan autenticación JWT
|
*/

Route::prefix('internal')->middleware('internal.api')->group(function () {

    // Health Check
    Route::get('/health', [InternalApiController::class, 'health']);

    // ========================================
    // Pacientes
    // ========================================
    Route::prefix('pacientes')->group(function () {
        Route::get('/', [InternalApiController::class, 'getPacientes']);
        Route::get('/{id}', [InternalApiController::class, 'getPaciente']);
        Route::get('/dni/{dni}', [InternalApiController::class, 'getPacientePorDni']);

        // Citas de un paciente
        Route::get('/{id}/citas', [InternalApiController::class, 'getCitasPaciente']);

        // Historial de un paciente
        Route::get('/{id}/historial', [InternalApiController::class, 'getHistorialPaciente']);
        Route::get('/{id}/historial-resumen', [InternalApiController::class, 'getHistorialResumen']);
    });

    // ========================================
    // Citas
    // ========================================
    Route::prefix('citas')->group(function () {
        Route::get('/{id}', [InternalApiController::class, 'getCita']);
    });

    // ========================================
    // Médicos
    // ========================================
    Route::prefix('medicos')->group(function () {
        Route::get('/', [InternalApiController::class, 'getMedicos']);
        Route::get('/{id}', [InternalApiController::class, 'getMedico']);

        // Citas de un médico
        Route::get('/{id}/citas', [InternalApiController::class, 'getCitasMedico']);

        // Disponibilidad de un médico
        Route::get('/{id}/disponibilidad', [InternalApiController::class, 'getDisponibilidad']);
    });

    // ========================================
    // Agendamiento de Citas (IA)
    // ========================================
    Route::prefix('agendamiento')->group(function () {
        // Determinar tipo de usuario
        Route::get('/tipo-usuario/{id_usuario}', [InternalApiController::class, 'determinarTipoUsuario']);

        // Sugerir horarios disponibles
        Route::post('/sugerir-horarios', [InternalApiController::class, 'sugerirHorarios']);

        // Registrar nueva cita
        Route::post('/registrar-cita', [InternalApiController::class, 'registrarCita']);

        // Confirmar cita existente
        Route::patch('/confirmar-cita/{id}', [InternalApiController::class, 'confirmarCita']);
    });

    // ========================================
    // Gestión de Citas con Verificación DNI (IA) - NUEVO
    // ========================================
    Route::prefix('citas-verificadas')->group(function () {
        // Cancelar cita con verificación de identidad (DNI + nombre)
        Route::post('/cancelar', [InternalApiController::class, 'verificarPacienteYCancelarCita']);

        // Reprogramar cita con verificación de identidad
        Route::post('/reprogramar', [InternalApiController::class, 'verificarPacienteYReprogramarCita']);

        // Cambiar médico de cita con verificación de identidad
        Route::post('/cambiar-medico', [InternalApiController::class, 'verificarPacienteYCambiarMedico']);
    });

    // ========================================
    // Gestión Simplificada con SOLO DNI (IA) - NUEVO
    // ========================================
    Route::prefix('citas-dni')->group(function () {
        // Buscar citas del paciente usando solo DNI
        Route::post('/buscar', [InternalApiController::class, 'buscarCitasPorDni']);

        // Cancelar cita usando solo DNI (sin verificar nombre)
        Route::post('/cancelar', [InternalApiController::class, 'cancelarCitaConDni']);

        // Reprogramar cita usando solo DNI
        Route::post('/reprogramar', [InternalApiController::class, 'reprogramarCitaConDni']);
    });

    // ========================================
    // Consultas de Información del Paciente (IA) - NUEVO
    // ========================================
    Route::prefix('consultas')->group(function () {
        // Consultar odontograma de un paciente
        Route::get('/paciente/{id}/odontograma', [InternalApiController::class, 'consultarOdontogramaPaciente']);

        // Consultar historial de pagos de un paciente
        Route::get('/paciente/{id}/pagos', [InternalApiController::class, 'consultarHistorialPagos']);

        // Consultar estado de tratamientos de un paciente
        Route::get('/paciente/{id}/tratamientos', [InternalApiController::class, 'getEstadoTratamientos']);
    });

    // ========================================
    // Interacciones IA
    // ========================================
    Route::post('/interacciones', [InternalApiController::class, 'registrarInteraccion']);

});
