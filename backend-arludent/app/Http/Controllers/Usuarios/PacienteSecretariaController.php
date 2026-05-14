<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\HistorialClinico;
use App\Models\SeguimientoTratamiento;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Gestión Pacientes Secretaria",
 *     description="Gestión completa de pacientes desde el rol de secretaria"
 * )
 */
class PacienteSecretariaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/secretaria/pacientes",
     *     summary="Búsqueda avanzada de pacientes",
     *     description="Permite buscar pacientes con múltiples criterios y filtros",
     *     tags={"Gestión Pacientes Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="buscar",
     *         in="query",
     *         description="Buscar por nombre, apellido, documento o teléfono",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado del paciente",
     *         required=false,
     *         @OA\Schema(type="string", enum={"activo", "inactivo"})
     *     ),
     *     @OA\Parameter(
     *         name="edad_min",
     *         in="query",
     *         description="Edad mínima",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="edad_max",
     *         in="query",
     *         description="Edad máxima",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="tiene_deuda",
     *         in="query",
     *         description="Filtrar pacientes con deuda pendiente",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="ultima_cita_desde",
     *         in="query",
     *         description="Filtrar por fecha de última cita desde",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="ordenar_por",
     *         in="query",
     *         description="Campo para ordenar",
     *         required=false,
     *         @OA\Schema(type="string", enum={"nombre", "fecha_registro", "ultima_cita", "deuda"})
     *     )
     * )
     */
    public function busquedaAvanzada(Request $request): JsonResponse
    {
        try {
            $query = Paciente::with(['usuario.roles']);

            // Filtro de búsqueda general
            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombres', 'like', '%' . $buscar . '%')
                      ->orWhere('apellidos', 'like', '%' . $buscar . '%')
                      ->orWhere('numero_documento', 'like', '%' . $buscar . '%')
                      ->orWhere('telefono', 'like', '%' . $buscar . '%')
                      ->orWhere('email', 'like', '%' . $buscar . '%');
                });
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por rango de edad
            if ($request->filled('edad_min') || $request->filled('edad_max')) {
                $fechaMax = $request->edad_min ? now()->subYears($request->edad_min) : null;
                $fechaMin = $request->edad_max ? now()->subYears($request->edad_max + 1) : null;
                
                if ($fechaMax) {
                    $query->where('fecha_nacimiento', '<=', $fechaMax);
                }
                if ($fechaMin) {
                    $query->where('fecha_nacimiento', '>=', $fechaMin);
                }
            }

            // Filtro por deuda pendiente
            if ($request->filled('tiene_deuda') && $request->tiene_deuda) {
                $query->whereHas('pagos', function ($q) {
                    $q->where('estado_pago', 'pendiente');
                });
            }

            // Filtro por fecha de última cita
            if ($request->filled('ultima_cita_desde')) {
                $query->whereHas('citas', function ($q) use ($request) {
                    $q->where('fecha_hora_inicio', '>=', $request->ultima_cita_desde);
                });
            }

            // Ordenamiento
            $ordenarPor = $request->ordenar_por ?? 'nombre';
            switch ($ordenarPor) {
                case 'nombre':
                    $query->orderBy('nombres')->orderBy('apellidos');
                    break;
                case 'fecha_registro':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'ultima_cita':
                    $query->withMax('citas', 'fecha_hora_inicio')
                          ->orderByDesc('citas_max_fecha_hora_inicio');
                    break;
                case 'deuda':
                    $query->withSum(['pagos' => function ($q) {
                        $q->where('estado_pago', 'pendiente');
                    }], 'monto')
                    ->orderByDesc('pagos_sum_monto');
                    break;
                default:
                    $query->orderBy('nombres')->orderBy('apellidos');
            }

            $pacientes = $query->paginate($request->per_page ?? 20);

            // Formatear respuesta con información adicional
            $pacientesFormateados = $pacientes->getCollection()->map(function ($paciente) {
                return $this->formatearPacienteCompleto($paciente);
            });

            return $this->successResponse([
                'pacientes' => $pacientesFormateados,
                'paginacion' => [
                    'total' => $pacientes->total(),
                    'por_pagina' => $pacientes->perPage(),
                    'pagina_actual' => $pacientes->currentPage(),
                    'total_paginas' => $pacientes->lastPage()
                ],
                'filtros_aplicados' => $request->only([
                    'buscar', 'estado', 'edad_min', 'edad_max', 'tiene_deuda', 
                    'ultima_cita_desde', 'ordenar_por'
                ])
            ]);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda avanzada de pacientes: ' . $e->getMessage());
            return $this->errorResponse('Error al buscar pacientes: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/pacientes/{id}/ficha-completa",
     *     summary="Obtener ficha completa del paciente",
     *     description="Obtiene toda la información del paciente incluyendo historial médico, citas y pagos",
     *     tags={"Gestión Pacientes Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del paciente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function fichaCompleta($id): JsonResponse
    {
        try {
            $paciente = Paciente::with([
                'usuario.roles',
                'citas.medico',
                'citas.calificacion',
                'pagos',
                'historialClinico.seguimientosTratamiento'
            ])->findOrFail($id);

            // Información básica del paciente
            $informacionBasica = [
                'id' => $paciente->id_paciente,
                'nombres' => $paciente->nombres,
                'apellidos' => $paciente->apellidos,
                'nombre_completo' => $paciente->nombres . ' ' . $paciente->apellidos,
                'numero_documento' => $paciente->numero_documento,
                'tipo_documento' => $paciente->tipo_documento,
                'fecha_nacimiento' => $paciente->fecha_nacimiento,
                'edad' => $paciente->fecha_nacimiento ? 
                    Carbon::parse($paciente->fecha_nacimiento)->age : null,
                'sexo' => $paciente->sexo,
                'telefono' => $paciente->telefono,
                'email' => $paciente->email,
                'direccion' => $paciente->direccion,
                'ocupacion' => $paciente->ocupacion,
                'estado_civil' => $paciente->estado_civil,
                'contacto_emergencia' => $paciente->contacto_emergencia,
                'telefono_emergencia' => $paciente->telefono_emergencia,
                'estado' => $paciente->estado,
                'fecha_registro' => $paciente->created_at,
                'foto_url' => $paciente->foto_url
            ];

            // Historial de citas
            $historialCitas = $paciente->citas->map(function ($cita) {
                return [
                    'id' => $cita->id_cita,
                    'fecha_hora' => $cita->fecha_hora_inicio,
                    'medico' => $cita->medico->nombres . ' ' . $cita->medico->apellidos,
                    'motivo' => $cita->motivo,
                    'estado' => $cita->estado,
                    'notas' => $cita->notas,
                    'calificacion' => $cita->calificacion ? [
                        'puntuacion' => $cita->calificacion->puntuacion,
                        'comentario' => $cita->calificacion->comentario
                    ] : null
                ];
            })->sortByDesc('fecha_hora')->values();

            // Historial de pagos
            $historialPagos = $paciente->pagos->map(function ($pago) {
                return [
                    'id' => $pago->id_pago,
                    'fecha_pago' => $pago->fecha_pago,
                    'monto' => $pago->monto,
                    'concepto' => $pago->concepto,
                    'estado_pago' => $pago->estado_pago,
                    'metodo_pago' => $pago->metodo_pago,
                    'numero_comprobante' => $pago->numero_comprobante
                ];
            })->sortByDesc('fecha_pago')->values();

            // Seguimientos post-tratamiento
            $seguimientos = [];
            if ($paciente->historialClinico) {
                $seguimientos = $paciente->historialClinico->seguimientosTratamiento->map(function ($seguimiento) {
                    return [
                        'id' => $seguimiento->id_seguimiento,
                        'fecha_registro' => $seguimiento->fecha_registro,
                        'descripcion' => $seguimiento->descripcion,
                        'duracion_restante' => $seguimiento->duracion_restante
                    ];
                })->sortByDesc('fecha_registro')->values();
            }

            // Estadísticas del paciente
            $estadisticas = [
                'total_citas' => $paciente->citas->count(),
                'citas_completadas' => $paciente->citas->where('estado', 'completado')->count(),
                'citas_canceladas' => $paciente->citas->where('estado', 'cancelado')->count(),
                'total_pagado' => $paciente->pagos->where('estado_pago', 'pagado')->sum('monto'),
                'deuda_pendiente' => $paciente->pagos->where('estado_pago', 'pendiente')->sum('monto'),
                'ultima_cita' => $paciente->citas->where('estado', 'completado')->max('fecha_hora_inicio'),
                'proxima_cita' => $paciente->citas->whereIn('estado', ['pendiente', 'confirmado'])
                    ->where('fecha_hora_inicio', '>=', now())->min('fecha_hora_inicio'),
                'promedio_calificacion' => $paciente->citas->whereNotNull('calificacion')
                    ->avg('calificacion.puntuacion'),
                'es_paciente_frecuente' => $paciente->citas->count() >= 5,
                'requiere_seguimiento' => $this->evaluarSeguimiento($paciente)
            ];

            // Alertas importantes
            $alertas = $this->generarAlertasPaciente($paciente);

            return $this->successResponse([
                'informacion_basica' => $informacionBasica,
                'historial_citas' => $historialCitas,
                'historial_pagos' => $historialPagos,
                'seguimientos' => $seguimientos,
                'estadisticas' => $estadisticas,
                'alertas' => $alertas
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener ficha completa del paciente: ' . $e->getMessage());
            return $this->errorResponse('Error al obtener ficha del paciente: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/secretaria/pacientes",
     *     summary="Crear nuevo paciente",
     *     description="Crea un nuevo paciente con toda su información",
     *     tags={"Gestión Pacientes Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombres", type="string", example="Juan Carlos"),
     *             @OA\Property(property="apellidos", type="string", example="Pérez González"),
     *             @OA\Property(property="numero_documento", type="string", example="12345678"),
     *             @OA\Property(property="tipo_documento", type="string", example="DNI"),
     *             @OA\Property(property="fecha_nacimiento", type="string", format="date", example="1990-05-15"),
     *             @OA\Property(property="sexo", type="string", enum={"M", "F"}, example="M"),
     *             @OA\Property(property="telefono", type="string", example="987654321"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@email.com"),
     *             @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *             @OA\Property(property="ocupacion", type="string", example="Ingeniero"),
     *             @OA\Property(property="estado_civil", type="string", example="Soltero"),
     *             @OA\Property(property="contacto_emergencia", type="string", example="María Pérez"),
     *             @OA\Property(property="telefono_emergencia", type="string", example="987654322"),
     *             @OA\Property(property="crear_usuario", type="boolean", example=true),
     *             @OA\Property(property="username", type="string", example="juan.perez"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'numero_documento' => 'required|string|max:20|unique:pacientes,numero_documento',
                'tipo_documento' => 'required|string|max:10',
                'fecha_nacimiento' => 'required|date|before:today',
                'sexo' => 'required|in:M,F',
                'telefono' => 'required|string|max:20',
                'email' => 'nullable|email|max:100|unique:pacientes,email',
                'direccion' => 'nullable|string|max:255',
                'ocupacion' => 'nullable|string|max:100',
                'estado_civil' => 'nullable|string|max:20',
                'contacto_emergencia' => 'nullable|string|max:100',
                'telefono_emergencia' => 'nullable|string|max:20',
                'crear_usuario' => 'boolean',
                'username' => 'required_if:crear_usuario,true|string|max:50|unique:usuarios,username',
                'password' => 'required_if:crear_usuario,true|string|min:8'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            DB::beginTransaction();

            $usuario = null;
            
            // Crear usuario si se solicita
            if ($request->crear_usuario) {
                $usuario = User::create([
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'telefono' => $request->telefono,
                    'estado' => 'activo'
                ]);

                // Asignar rol de paciente
                $rolPaciente = Rol::where('nombre', 'paciente')->first();
                if ($rolPaciente) {
                    $usuario->roles()->attach($rolPaciente->id_rol);
                }
            }

            // Crear paciente
            $paciente = Paciente::create([
                'id_usuario' => $usuario ? $usuario->id_usuario : null,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'numero_documento' => $request->numero_documento,
                'tipo_documento' => $request->tipo_documento,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'sexo' => $request->sexo,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'direccion' => $request->direccion,
                'ocupacion' => $request->ocupacion,
                'estado_civil' => $request->estado_civil,
                'contacto_emergencia' => $request->contacto_emergencia,
                'telefono_emergencia' => $request->telefono_emergencia,
                'estado' => 'activo'
            ]);

            // Crear historial clínico básico
            HistorialClinico::create([
                'id_paciente' => $paciente->id_paciente,
                'fecha_creacion' => now(),
                'creado_por' => Auth::id()
            ]);

            DB::commit();

            return $this->successResponse([
                'paciente' => $this->formatearPacienteCompleto($paciente->fresh()),
                'usuario_creado' => $request->crear_usuario,
                'credenciales' => $request->crear_usuario ? [
                    'username' => $request->username,
                    'password_temporal' => $request->password
                ] : null
            ], 'Paciente creado exitosamente', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear paciente: ' . $e->getMessage());
            return $this->errorResponse('Error al crear paciente: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/secretaria/pacientes/{id}",
     *     summary="Actualizar información del paciente",
     *     description="Actualiza la información completa del paciente",
     *     tags={"Gestión Pacientes Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del paciente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $paciente = Paciente::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nombres' => 'sometimes|string|max:100',
                'apellidos' => 'sometimes|string|max:100',
                'numero_documento' => 'sometimes|string|max:20|unique:pacientes,numero_documento,' . $id . ',id_paciente',
                'tipo_documento' => 'sometimes|string|max:10',
                'fecha_nacimiento' => 'sometimes|date|before:today',
                'sexo' => 'sometimes|in:M,F',
                'telefono' => 'sometimes|string|max:20',
                'email' => 'nullable|email|max:100|unique:pacientes,email,' . $id . ',id_paciente',
                'direccion' => 'nullable|string|max:255',
                'ocupacion' => 'nullable|string|max:100',
                'estado_civil' => 'nullable|string|max:20',
                'contacto_emergencia' => 'nullable|string|max:100',
                'telefono_emergencia' => 'nullable|string|max:20',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $paciente->update($request->only([
                'nombres', 'apellidos', 'numero_documento', 'tipo_documento',
                'fecha_nacimiento', 'sexo', 'telefono', 'email', 'direccion',
                'ocupacion', 'estado_civil', 'contacto_emergencia', 
                'telefono_emergencia', 'estado'
            ]));

            return $this->successResponse([
                'paciente' => $this->formatearPacienteCompleto($paciente->fresh())
            ], 'Paciente actualizado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al actualizar paciente: ' . $e->getMessage());
            return $this->errorResponse('Error al actualizar paciente: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/pacientes/estadisticas-generales",
     *     summary="Estadísticas generales de pacientes",
     *     description="Obtiene estadísticas generales de todos los pacientes",
     *     tags={"Gestión Pacientes Secretaria"},
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function estadisticasGenerales(): JsonResponse
    {
        try {
            $estadisticas = [
                'total_pacientes' => Paciente::count(),
                'pacientes_activos' => Paciente::where('estado', 'activo')->count(),
                'pacientes_nuevos_mes' => Paciente::where('created_at', '>=', now()->subMonth())->count(),
                'pacientes_con_citas_pendientes' => Paciente::whereHas('citas', function ($q) {
                    $q->whereIn('estado', ['pendiente', 'confirmado'])
                      ->where('fecha_hora_inicio', '>=', now());
                })->count(),
                'pacientes_con_deuda' => Paciente::whereHas('pagos', function ($q) {
                    $q->where('estado_pago', 'pendiente');
                })->count(),
                'promedio_edad' => Paciente::whereNotNull('fecha_nacimiento')
                    ->selectRaw('AVG(YEAR(CURDATE()) - YEAR(fecha_nacimiento)) as promedio')
                    ->value('promedio'),
                'distribucion_sexo' => [
                    'masculino' => Paciente::where('sexo', 'M')->count(),
                    'femenino' => Paciente::where('sexo', 'F')->count()
                ],
                'pacientes_frecuentes' => Paciente::withCount('citas')
                    ->having('citas_count', '>=', 5)->count(),
                'requieren_seguimiento' => Paciente::whereHas('citas', function ($q) {
                    $q->where('estado', 'completado')
                      ->where('fecha_hora_fin', '<=', now()->subDays(7))
                      ->where('fecha_hora_fin', '>=', now()->subDays(30));
                })->whereDoesntHave('historialClinico.seguimientosTratamiento', function ($q) {
                    $q->where('fecha_registro', '>=', now()->subDays(30));
                })->count()
            ];

            return $this->successResponse($estadisticas);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de pacientes: ' . $e->getMessage());
            return $this->errorResponse('Error al obtener estadísticas: ' . $e->getMessage());
        }
    }

    // ============================================================
    // MÉTODOS AUXILIARES
    // ============================================================

    /**
     * Formatear información completa del paciente
     */
    private function formatearPacienteCompleto($paciente)
    {
        return [
            'id' => $paciente->id_paciente,
            'nombres' => $paciente->nombres,
            'apellidos' => $paciente->apellidos,
            'nombre_completo' => $paciente->nombres . ' ' . $paciente->apellidos,
            'numero_documento' => $paciente->numero_documento,
            'tipo_documento' => $paciente->tipo_documento,
            'fecha_nacimiento' => $paciente->fecha_nacimiento,
            'edad' => $paciente->fecha_nacimiento ? 
                Carbon::parse($paciente->fecha_nacimiento)->age : null,
            'sexo' => $paciente->sexo,
            'telefono' => $paciente->telefono,
            'email' => $paciente->email,
            'direccion' => $paciente->direccion,
            'estado' => $paciente->estado,
            'fecha_registro' => $paciente->created_at,
            
            // Información adicional calculada
            'estadisticas_basicas' => [
                'total_citas' => $paciente->citas->count() ?? 0,
                'ultima_cita' => $paciente->citas->where('estado', 'completado')->max('fecha_hora_inicio'),
                'proxima_cita' => $paciente->citas->whereIn('estado', ['pendiente', 'confirmado'])
                    ->where('fecha_hora_inicio', '>=', now())->min('fecha_hora_inicio'),
                'deuda_pendiente' => $paciente->pagos->where('estado_pago', 'pendiente')->sum('monto') ?? 0,
                'es_paciente_frecuente' => ($paciente->citas->count() ?? 0) >= 5
            ],
            
            'acciones_disponibles' => [
                'puede_agendar_cita' => $paciente->estado === 'activo',
                'puede_editar' => true,
                'puede_desactivar' => $paciente->estado === 'activo',
                'requiere_seguimiento' => $this->evaluarSeguimiento($paciente)
            ]
        ];
    }

    /**
     * Evaluar si el paciente requiere seguimiento
     */
    private function evaluarSeguimiento($paciente)
    {
        // Verificar si tiene citas completadas recientes sin seguimiento
        $citasRecientesSinSeguimiento = $paciente->citas
            ->where('estado', 'completado')
            ->where('fecha_hora_fin', '<=', now()->subDays(7))
            ->where('fecha_hora_fin', '>=', now()->subDays(30))
            ->count();

        $seguimientosRecientes = 0;
        if ($paciente->historialClinico) {
            $seguimientosRecientes = $paciente->historialClinico->seguimientosTratamiento
                ->where('fecha_registro', '>=', now()->subDays(30))
                ->count();
        }

        return $citasRecientesSinSeguimiento > 0 && $seguimientosRecientes === 0;
    }

    /**
     * Generar alertas importantes del paciente
     */
    private function generarAlertasPaciente($paciente)
    {
        $alertas = [];

        // Deuda pendiente
        $deudaPendiente = $paciente->pagos->where('estado_pago', 'pendiente')->sum('monto');
        if ($deudaPendiente > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => "Deuda pendiente: S/. " . number_format($deudaPendiente, 2),
                'icono' => 'exclamation-triangle'
            ];
        }

        // Seguimiento requerido
        if ($this->evaluarSeguimiento($paciente)) {
            $alertas[] = [
                'tipo' => 'info',
                'mensaje' => "Requiere seguimiento post-tratamiento",
                'icono' => 'user-clock'
            ];
        }

        // Cita próxima
        $proximaCita = $paciente->citas->whereIn('estado', ['pendiente', 'confirmado'])
            ->where('fecha_hora_inicio', '>=', now())
            ->where('fecha_hora_inicio', '<=', now()->addDays(3))
            ->first();
            
        if ($proximaCita) {
            $alertas[] = [
                'tipo' => 'success',
                'mensaje' => "Cita próxima: " . Carbon::parse($proximaCita->fecha_hora_inicio)->format('d/m/Y H:i'),
                'icono' => 'calendar'
            ];
        }

        return $alertas;
    }
}