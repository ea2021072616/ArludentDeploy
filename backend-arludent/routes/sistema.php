<?php

use Illuminate\Support\Facades\Route;
use App\Models\Rol;
use App\Http\Controllers\Auditoria\AuditoriaController;
use App\Http\Controllers\Administracion\ReportesController;
use App\Http\Controllers\Administracion\IndicadoresController;

/**
 * Rutas de Sistema
 *
 * Logs de actividad, interacciones IA, auditoría
 * Acceso restringido a administradores
 */

Route::prefix('sistema')->middleware(['auth:api'])->group(function () {

    // Obtener todos los roles (necesario para el formulario de crear usuario)
    Route::get('/roles', function () {
        $roles = Rol::select('id_rol', 'nombre_rol', 'descripcion')
            ->orderBy('nombre_rol')
            ->get();

        return response()->json($roles);
    });

    // ============ AUDITORÍA (solo admin) ============
    Route::middleware('role:admin')->group(function () {
        // Logs de actividad
        Route::get('/auditoria', [AuditoriaController::class, 'index']);
        Route::get('/auditoria/{id}', [AuditoriaController::class, 'show']);
        Route::get('/auditoria-estadisticas', [AuditoriaController::class, 'estadisticas']);
        Route::get('/auditoria-acciones', [AuditoriaController::class, 'acciones']);
        Route::get('/auditoria-modulos', [AuditoriaController::class, 'modulos']);

        // ============ REPORTES ============
        Route::prefix('reportes')->group(function () {
            Route::get('/ingresos', [ReportesController::class, 'reporteIngresos']);
            Route::get('/flujo-clientes', [ReportesController::class, 'reporteFlujoClientes']);
            Route::get('/citas', [ReportesController::class, 'reporteCitas']);
        });

        // ============ INDICADORES / KPIs ============
        Route::prefix('indicadores')->group(function () {
            Route::get('/tratamientos-solicitados', [IndicadoresController::class, 'tratamientosSolicitados']);
            Route::get('/citas-por-medico', [IndicadoresController::class, 'citasPorMedico']);
            Route::get('/tendencias-ingresos', [IndicadoresController::class, 'tendenciasIngresos']);
            Route::get('/dashboard-kpis', [IndicadoresController::class, 'dashboardKPIs']);
            Route::get('/satisfaccion-pacientes', [IndicadoresController::class, 'satisfaccionPacientes']);
        });

        // Interacciones IA
        Route::get('/ia/interacciones', function () {
            return response()->json([
                'success' => true,
                'message' => 'Interacciones IA - Endpoint en desarrollo',
            ]);
        });

        // Estadísticas del sistema
        Route::get('/estadisticas', function () {
            return response()->json([
                'success' => true,
                'message' => 'Estadísticas del sistema - Endpoint en desarrollo',
            ]);
        });

        // Notificaciones
        Route::get('/notificaciones', function () {
            return response()->json([
                'success' => true,
                'message' => 'Gestión de notificaciones - Endpoint en desarrollo',
            ]);
        });
    });
});
