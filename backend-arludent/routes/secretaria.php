<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administracion\IndicadoresController;
use App\Http\Controllers\Citas\CitaMedicoController;
use App\Http\Controllers\Facturacion\CajaController;
use App\Http\Controllers\GestionClinica\SeguimientoPostTratamientoController;

/**
 * Rutas de Secretaría
 *
 * Dashboard y funciones específicas para el rol de secretaría
 */

Route::prefix('secretaria')->middleware(['auth:api', 'role:secretaria'])->group(function () {

    // Dashboard principal de secretaría
    Route::get('/dashboard', [IndicadoresController::class, 'dashboardSecretaria']);

    // ============ MÉDICOS ============
    Route::get('/medicos', [CitaMedicoController::class, 'listarMedicos']);

    // ============ PACIENTES ============
    Route::get('/pacientes', [CitaMedicoController::class, 'listarPacientes']);
    Route::get('/pacientes/buscar', [CitaMedicoController::class, 'buscarPacientes']);
    Route::get('/pacientes/estadisticas-generales', [CitaMedicoController::class, 'estadisticasGeneralesPacientes']);
    Route::get('/pacientes/{id}', [CitaMedicoController::class, 'verPaciente']);

    // ============ SEGUIMIENTO POST-TRATAMIENTO ============
    Route::prefix('seguimiento')->group(function () {
        Route::get('/', [CitaMedicoController::class, 'listarSeguimientos']);
        Route::get('/estadisticas', [CitaMedicoController::class, 'estadisticasSeguimientos']);

        // CRUD Completo
        Route::post('/', [SeguimientoPostTratamientoController::class, 'store']);
        Route::put('/{id}', [SeguimientoPostTratamientoController::class, 'update']);
        Route::delete('/{id}', [SeguimientoPostTratamientoController::class, 'destroy']);

        // Registrar contacto manual
        Route::post('/{id}/registrar-contacto', [SeguimientoPostTratamientoController::class, 'registrarContacto']);
    });

    // ============ AGENDA / CITAS ============
    Route::prefix('citas')->group(function () {
        // Obtener todas las citas (de todos los médicos)
        Route::get('/', [CitaMedicoController::class, 'todasLasCitas']);

        // Crear nueva cita
        Route::post('/', [CitaMedicoController::class, 'crearCita']);

        // Obtener citas para calendario
        Route::get('/calendario', [CitaMedicoController::class, 'todasLasCitasCalendario']);

        // Obtener detalle de una cita
        Route::get('/{id}', [CitaMedicoController::class, 'show']);

        // Confirmar cita
        Route::patch('/{id}/confirmar', [CitaMedicoController::class, 'confirmarCita']);

        // Cambiar estado de una cita
        Route::patch('/{id}/estado', [CitaMedicoController::class, 'cambiarEstado']);

        // Cancelar cita
        Route::patch('/{id}/cancelar', [CitaMedicoController::class, 'cancelar']);
    });

    // ============ CAJA / PAGOS ============
    Route::prefix('caja')->group(function () {
        // Estadísticas de caja
        Route::get('/estadisticas', [CajaController::class, 'estadisticasCaja']);

        // Listar pagos/transacciones
        Route::get('/pagos', [CajaController::class, 'listarPagos']);

        // Ver detalle de un pago
        Route::get('/pagos/{id}', [CajaController::class, 'verPago']);

        // Registrar nuevo pago
        Route::post('/pagos', [CajaController::class, 'registrarPago']);

        // Emitir comprobante para un pago
        Route::post('/pagos/{id}/comprobante', [CajaController::class, 'emitirComprobante']);

        // Buscar pacientes (para asignar pago)
        Route::get('/pacientes/buscar', [CajaController::class, 'buscarPacientes']);

        // Descargar PDF generado directamente (stream desde storage)
        Route::get('/pagos/{id}/pdf/download', [CajaController::class, 'descargarPDFPago']);
    });

});

// ============ RUTAS DE CAJA ACCESIBLES PARA TODOS LOS USUARIOS AUTENTICADOS ============
Route::prefix('secretaria/caja')->middleware(['auth:api'])->group(function () {
    // Generar PDF simple de un pago (accesible para todos los usuarios autenticados)
    Route::get('/pagos/{id}/pdf', [CajaController::class, 'generarPDFPago']);
});
