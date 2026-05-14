<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\HistorialClinico;
use App\Models\SeguimientoTratamiento;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(
 *     name="Seguimiento Post-Tratamiento",
 *     description="Gestión de seguimientos posteriores a tratamientos dentales"
 * )
 */
class SeguimientoController extends Controller
{
    /** Días hacia atrás para consultar citas completadas. */
    private const DEFAULT_LOOKBACK_DAYS = 30;

    /** Días tras los que un tratamiento urgente requiere seguimiento. */
    private const URGENT_FOLLOWUP_DAYS = 3;

    /** Días tras los que un tratamiento normal requiere seguimiento. */
    private const NORMAL_FOLLOWUP_DAYS = 7;

    /** Días sin seguimiento para prioridad alta/vencimiento crítico. */
    private const HIGH_PRIORITY_DAYS = 14;

    /** Días sin seguimiento para considerar vencimiento como alta prioridad. */
    private const OVERDUE_HIGH_DAYS = 10;

    /** Paginación por defecto. */
    private const DEFAULT_PER_PAGE = 15;

    /** Motivos de tratamiento que requieren seguimiento urgente. */
    private const MOTIVOS_URGENTES = ['extracción', 'cirugía', 'implante', 'endodoncia'];

    /**
     * @OA\Get(
     *     path="/api/secretaria/seguimiento",
     *     summary="Listar seguimientos post-tratamiento",
     *     description="Obtiene lista de seguimientos con filtros",
     *     tags={"Seguimiento Post-Tratamiento"},
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Cita::with(['paciente', 'medico'])
                ->where('estado', 'completado')
                ->whereDate('fecha_hora_fin', '>=', now()->subDays(self::DEFAULT_LOOKBACK_DAYS));

            $this->aplicarFiltrosIndex($query, $request);

            $citas = $query->orderBy('fecha_hora_fin', 'desc')->paginate(self::DEFAULT_PER_PAGE);

            $seguimientosFormateados = $citas->getCollection()->map(
                fn ($cita) => $this->formatearSeguimiento($cita)
            );

            return $this->successResponse([
                'seguimientos' => $seguimientosFormateados,
                'paginacion'   => $this->formatearPaginacion($citas),
                'resumen'      => [
                    'pendientes'        => $citas->getCollection()->where('estado_seguimiento', 'pendiente')->count(),
                    'registrados'       => $citas->getCollection()->where('estado_seguimiento', 'registrado')->count(),
                    'requieren_atencion' => $citas->getCollection()->where('requiere_atencion', true)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener seguimientos: ' . $e->getMessage());
            return $this->errorResponse(
                'Error interno del servidor',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/secretaria/seguimiento",
     *     summary="Crear registro de seguimiento",
     *     description="Registra un nuevo seguimiento para una cita completada",
     *     tags={"Seguimiento Post-Tratamiento"},
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cita_id'               => 'required|exists:citas,id_cita',
                'descripcion'           => 'required|string|max:1000',
                'estado_paciente'       => 'required|in:excelente,bueno,regular,malo',
                'requiere_nueva_cita'   => 'boolean',
                'recomendaciones'       => 'nullable|string|max:500',
                'fecha_proximo_control' => 'nullable|date|after:today',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $cita = Cita::with(['paciente', 'medico'])->findOrFail($request->cita_id);

            if ($cita->estado !== 'completado') {
                return $this->errorResponse(
                    'Solo se pueden crear seguimientos para citas completadas',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $historial = HistorialClinico::firstOrCreate(
                ['id_paciente' => $cita->id_paciente],
                ['fecha_creacion' => now(), 'creado_por' => Auth::id()]
            );

            $seguimiento = SeguimientoTratamiento::create([
                'id_historial'   => $historial->id_historial,
                'fecha_registro' => now(),
                'descripcion'    => $request->descripcion,
                'registrado_por' => Auth::id(),
            ]);

            return $this->successResponse([
                'seguimiento_id'  => $seguimiento->id_seguimiento,
                'cita_id'         => $cita->id_cita,
                'paciente'        => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
                'fecha_registro'  => $seguimiento->fecha_registro->format('Y-m-d H:i'),
                'estado_paciente' => $request->estado_paciente,
            ], 'Seguimiento registrado exitosamente', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error al crear seguimiento: ' . $e->getMessage());
            return $this->errorResponse(
                'Error interno del servidor',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/seguimiento/{citaId}",
     *     summary="Obtener seguimientos de una cita",
     *     description="Obtiene todos los seguimientos registrados para una cita específica",
     *     tags={"Seguimiento Post-Tratamiento"},
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function show($citaId): JsonResponse
    {
        try {
            $cita = Cita::with(['paciente', 'medico'])->findOrFail($citaId);

            $historial = HistorialClinico::where('id_paciente', $cita->id_paciente)->first();

            $infoCita = [
                'id'                => $cita->id_cita,
                'fecha_tratamiento' => Carbon::parse($cita->fecha_hora_fin)->format('Y-m-d H:i'),
                'motivo'            => $cita->motivo,
            ];

            $infoPaciente = [
                'id'              => $cita->paciente->id_paciente,
                'nombre_completo' => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
                'telefono'        => $cita->paciente->telefono,
                'email'           => $cita->paciente->email,
            ];

            if (!$historial) {
                return $this->successResponse([
                    'cita'          => $infoCita,
                    'paciente'      => $infoPaciente,
                    'seguimientos'  => [],
                ]);
            }

            $seguimientos = SeguimientoTratamiento::where('id_historial', $historial->id_historial)
                ->where('fecha_registro', '>=', $cita->fecha_hora_fin)
                ->with(['registradoPor'])
                ->orderBy('fecha_registro', 'desc')
                ->get();

            $seguimientosFormateados = $seguimientos->map(fn ($seguimiento) => [
                'id'              => $seguimiento->id_seguimiento,
                'fecha_registro'  => $seguimiento->fecha_registro->format('Y-m-d H:i'),
                'descripcion'     => $seguimiento->descripcion,
                'registrado_por'  => $seguimiento->registradoPor
                    ? $seguimiento->registradoPor->nombres . ' ' . $seguimiento->registradoPor->apellidos
                    : 'Usuario eliminado',
                'created_at'      => $seguimiento->created_at->format('Y-m-d H:i'),
            ]);

            return $this->successResponse([
                'cita'                => $infoCita,
                'paciente'            => $infoPaciente,
                'medico_tratante'     => [
                    'nombre_completo' => $cita->medico->nombres . ' ' . $cita->medico->apellidos,
                ],
                'seguimientos'        => $seguimientosFormateados,
                'total_seguimientos'  => $seguimientos->count(),
                'ultimo_seguimiento'  => $seguimientos->first()?->fecha_registro->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener seguimientos de la cita: ' . $e->getMessage());
            return $this->errorResponse(
                'Error interno del servidor',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/seguimiento/vencidos",
     *     summary="Obtener seguimientos que requieren atención",
     *     description="Lista citas que necesitan seguimiento urgente",
     *     tags={"Seguimiento Post-Tratamiento"},
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function seguimientosVencidos(): JsonResponse
    {
        try {
            $citasSinSeguimiento = Cita::with(['paciente', 'medico'])
                ->where('estado', 'completado')
                ->where('fecha_hora_fin', '<=', now()->subDays(self::NORMAL_FOLLOWUP_DAYS))
                ->where('fecha_hora_fin', '>=', now()->subDays(self::DEFAULT_LOOKBACK_DAYS))
                ->whereDoesntHave('paciente.historialClinico.seguimientosTratamiento', function ($queryBuilder) {
                    $queryBuilder->where('fecha_registro', '>=', now()->subDays(self::DEFAULT_LOOKBACK_DAYS));
                })
                ->orderBy('fecha_hora_fin')
                ->get();

            $citasFormateadas = $citasSinSeguimiento->map(function ($cita) {
                $diasTranscurridos = Carbon::parse($cita->fecha_hora_fin)->diffInDays(now());

                return [
                    'cita_id'               => $cita->id_cita,
                    'paciente'              => [
                        'nombre_completo' => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
                        'telefono'        => $cita->paciente->telefono,
                    ],
                    'medico'                => $cita->medico->nombres . ' ' . $cita->medico->apellidos,
                    'fecha_tratamiento'     => Carbon::parse($cita->fecha_hora_fin)->format('Y-m-d'),
                    'motivo'                => $cita->motivo,
                    'dias_sin_seguimiento'  => $diasTranscurridos,
                    'prioridad'             => $this->calcularPrioridadVencida($diasTranscurridos, $cita->motivo),
                ];
            });

            return $this->successResponse([
                'total_pendientes'       => $citasFormateadas->count(),
                'citas_sin_seguimiento'  => $citasFormateadas,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener seguimientos vencidos: ' . $e->getMessage());
            return $this->errorResponse(
                'Error interno del servidor',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Aplica filtros opcionales a la consulta del índice de seguimientos.
     */
    private function aplicarFiltrosIndex($query, Request $request): void
    {
        if ($request->filled('medico_id')) {
            $query->where('id_medico', $request->medico_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_hora_fin', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_hora_fin', '<=', $request->fecha_hasta);
        }

        if ($request->filled('buscar_paciente')) {
            $termino = $request->buscar_paciente;
            $query->whereHas('paciente', function ($queryBuilder) use ($termino) {
                $queryBuilder->where('nombres', 'like', "%{$termino}%")
                    ->orWhere('apellidos', 'like', "%{$termino}%");
            });
        }
    }

    /**
     * Formatea una cita completada con información de seguimiento.
     */
    private function formatearSeguimiento(Cita $cita): array
    {
        $seguimientoExistente = SeguimientoTratamiento::whereHas('historial', function ($queryBuilder) use ($cita) {
            $queryBuilder->where('id_paciente', $cita->id_paciente);
        })->where('fecha_registro', '>=', $cita->fecha_hora_fin)->first();

        $diasTranscurridos = Carbon::parse($cita->fecha_hora_fin)->diffInDays(now());

        return [
            'cita_id'             => $cita->id_cita,
            'paciente'            => [
                'id'              => $cita->paciente->id_paciente,
                'nombre_completo' => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
                'telefono'        => $cita->paciente->telefono,
                'email'           => $cita->paciente->email,
            ],
            'medico'              => [
                'id'              => $cita->medico->id_medico,
                'nombre_completo' => $cita->medico->nombres . ' ' . $cita->medico->apellidos,
            ],
            'fecha_tratamiento'   => Carbon::parse($cita->fecha_hora_fin)->format('Y-m-d'),
            'motivo_cita'         => $cita->motivo,
            'dias_transcurridos'  => $diasTranscurridos,
            'estado_seguimiento'  => $seguimientoExistente ? 'registrado' : 'pendiente',
            'prioridad'           => $this->calcularPrioridad($diasTranscurridos, $cita->motivo),
            'requiere_atencion'   => $this->requiereSeguimiento($diasTranscurridos, $cita->motivo),
            'ultimo_seguimiento'  => $seguimientoExistente
                ? Carbon::parse($seguimientoExistente->fecha_registro)->format('Y-m-d')
                : null,
            'observaciones'       => $cita->notas,
        ];
    }

    /**
     * Formatea datos de paginación para la respuesta.
     */
    private function formatearPaginacion($paginator): array
    {
        return [
            'total'         => $paginator->total(),
            'por_pagina'    => $paginator->perPage(),
            'pagina_actual' => $paginator->currentPage(),
            'total_paginas' => $paginator->lastPage(),
        ];
    }

    /**
     * Determina si el motivo del tratamiento es considerado urgente.
     */
    private function esTratamientoUrgente(string $motivo): bool
    {
        foreach (self::MOTIVOS_URGENTES as $motivoUrgente) {
            if (stripos($motivo, $motivoUrgente) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determina si una cita requiere seguimiento según días transcurridos y tipo de tratamiento.
     */
    private function requiereSeguimiento(int $diasTranscurridos, string $motivo): bool
    {
        $diasLimite = $this->esTratamientoUrgente($motivo)
            ? self::URGENT_FOLLOWUP_DAYS
            : self::NORMAL_FOLLOWUP_DAYS;

        return $diasTranscurridos >= $diasLimite;
    }

    /**
     * Calcula la prioridad del seguimiento (baja, media, alta).
     */
    private function calcularPrioridad(int $diasTranscurridos, string $motivo): string
    {
        if ($this->esTratamientoUrgente($motivo) && $diasTranscurridos >= self::URGENT_FOLLOWUP_DAYS) {
            return 'alta';
        }

        if ($diasTranscurridos >= self::HIGH_PRIORITY_DAYS) {
            return 'alta';
        }

        if ($diasTranscurridos >= self::NORMAL_FOLLOWUP_DAYS) {
            return 'media';
        }

        return 'baja';
    }

    /**
     * Calcula la prioridad para seguimientos ya vencidos (media, alta, critica).
     */
    private function calcularPrioridadVencida(int $diasTranscurridos, string $motivo): string
    {
        if ($this->esTratamientoUrgente($motivo) && $diasTranscurridos > self::NORMAL_FOLLOWUP_DAYS) {
            return 'critica';
        }

        if ($diasTranscurridos > self::HIGH_PRIORITY_DAYS) {
            return 'critica';
        }

        if ($diasTranscurridos > self::OVERDUE_HIGH_DAYS) {
            return 'alta';
        }

        return 'media';
    }
}