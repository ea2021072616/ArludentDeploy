<?php

namespace App\Http\Controllers\Auditoria;

use App\Models\LogActividad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

/**
 * Controlador de Auditoría
 *
 * Gestiona la consulta de logs de actividad del sistema
 * Solo accesible por administradores
 */
class AuditoriaController extends Controller
{
    /**
     * Listar logs de actividad con filtros y paginación
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validar parámetros de entrada
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:5|max:100',
                'accion' => 'string|max:50',
                'modulo' => 'string|max:50',
                'id_usuario' => 'integer|exists:usuarios,id_usuario',
                'fecha_inicio' => 'date',
                'fecha_fin' => 'date|after_or_equal:fecha_inicio',
                'search' => 'string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de filtro inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Query base
            $query = LogActividad::with('usuario:id_usuario,username,correo')
                ->orderBy('fecha_hora', 'desc');

            // Aplicar filtros
            if ($request->has('accion')) {
                $query->where('accion', $request->accion);
            }

            if ($request->has('modulo')) {
                $query->where('modulo_afectado', $request->modulo);
            }

            if ($request->has('id_usuario')) {
                $query->where('id_usuario', $request->id_usuario);
            }

            if ($request->has('fecha_inicio')) {
                $query->whereDate('fecha_hora', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin')) {
                $query->whereDate('fecha_hora', '<=', $request->fecha_fin);
            }

            // Búsqueda general
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('descripcion', 'like', "%{$search}%")
                      ->orWhere('ip_usuario', 'like', "%{$search}%")
                      ->orWhere('registro_afectado', 'like', "%{$search}%");
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs->items(),
                    'pagination' => [
                        'total' => $logs->total(),
                        'per_page' => $logs->perPage(),
                        'current_page' => $logs->currentPage(),
                        'last_page' => $logs->lastPage(),
                        'from' => $logs->firstItem(),
                        'to' => $logs->lastItem(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs de auditoría',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener un log específico por ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $log = LogActividad::with('usuario:id_usuario,username,correo')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'log' => $log
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Log no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el log',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de auditoría
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            // Validar fechas
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'date',
                'fecha_fin' => 'date|after_or_equal:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fechas inválidas',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = LogActividad::query();

            // Aplicar filtro de fechas si existen
            if ($request->has('fecha_inicio')) {
                $query->whereDate('fecha_hora', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin')) {
                $query->whereDate('fecha_hora', '<=', $request->fecha_fin);
            }

            // Total de logs
            $totalLogs = $query->count();

            // Acciones más frecuentes (top 10)
            $accionesFrecuentes = (clone $query)
                ->selectRaw('accion, COUNT(*) as total')
                ->groupBy('accion')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            // Módulos más afectados
            $modulosAfectados = (clone $query)
                ->whereNotNull('modulo_afectado')
                ->selectRaw('modulo_afectado, COUNT(*) as total')
                ->groupBy('modulo_afectado')
                ->orderByDesc('total')
                ->get();

            // Usuarios más activos
            $usuariosActivos = (clone $query)
                ->whereNotNull('id_usuario')
                ->selectRaw('id_usuario, COUNT(*) as total')
                ->groupBy('id_usuario')
                ->orderByDesc('total')
                ->limit(10)
                ->with('usuario:id_usuario,username,correo')
                ->get();

            // Actividad por día (últimos 7 días por defecto)
            $fechaInicio = $request->get('fecha_inicio', now()->subDays(7)->format('Y-m-d'));
            $actividadDiaria = LogActividad::query()
                ->whereDate('fecha_hora', '>=', $fechaInicio)
                ->selectRaw('DATE(fecha_hora) as fecha, COUNT(*) as total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_logs' => $totalLogs,
                    'acciones_frecuentes' => $accionesFrecuentes,
                    'modulos_afectados' => $modulosAfectados,
                    'usuarios_activos' => $usuariosActivos,
                    'actividad_diaria' => $actividadDiaria,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener listado de acciones únicas (para filtros)
     *
     * @return JsonResponse
     */
    public function acciones(): JsonResponse
    {
        try {
            $acciones = LogActividad::select('accion')
                ->distinct()
                ->orderBy('accion')
                ->pluck('accion');

            return response()->json([
                'success' => true,
                'data' => [
                    'acciones' => $acciones
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener acciones',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener listado de módulos únicos (para filtros)
     *
     * @return JsonResponse
     */
    public function modulos(): JsonResponse
    {
        try {
            $modulos = LogActividad::select('modulo_afectado')
                ->whereNotNull('modulo_afectado')
                ->distinct()
                ->orderBy('modulo_afectado')
                ->pluck('modulo_afectado');

            return response()->json([
                'success' => true,
                'data' => [
                    'modulos' => $modulos
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener módulos',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
