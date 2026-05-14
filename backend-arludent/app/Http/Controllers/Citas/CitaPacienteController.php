<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Models\Cita;
use App\Models\LogActividad;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Citas para Pacientes.
 *
 * Permite al paciente gestionar sus citas: listar, confirmar, reprogramar, cancelar y calificar.
 * También expone endpoints de secretaría para gestión de agenda.
 */
class CitaPacienteController extends Controller
{
    /** Meses hacia atrás por defecto para vista de calendario. */
    private const CALENDAR_PAST_MONTHS = 3;

    /** Meses hacia adelante por defecto para vista de calendario. */
    private const CALENDAR_FUTURE_MONTHS = 6;

    /** Duración por defecto de una cita en minutos. */
    private const DEFAULT_APPOINTMENT_DURATION = 60;

    /** Paginación por defecto. */
    private const DEFAULT_PER_PAGE = 20;

    /** Estados que permiten reprogramación o cancelación. */
    private const ESTADOS_MODIFICABLES = ['pendiente', 'confirmado'];

    /** Estados que permiten edición en calendario de secretaría. */
    private const ESTADOS_EDITABLES = ['pendiente', 'confirmado'];

    /** Mapa de colores por estado de cita. */
    private const COLORES_ESTADO = [
        'pendiente'       => '#ffc107',
        'confirmado'      => '#17a2b8',
        'en_espera'       => '#fd7e14',
        'siendo_atendido' => '#007bff',
        'completado'      => '#28a745',
        'no_asistio'      => '#6c757d',
        'cancelado'       => '#dc3545',
    ];

    /** Color por defecto si el estado no está en el mapa. */
    private const COLOR_DEFAULT = '#6c757d';

    /**
     * Obtener todas las citas del paciente autenticado.
     */
    public function misCitas(Request $request): JsonResponse
    {
        $paciente = $this->obtenerPacienteOpcional();

        if (!$paciente) {
            return $this->successResponse(['citas' => [], 'total' => 0]);
        }

        $query = Cita::where('id_paciente', $paciente->id_paciente)
            ->with(['medico.usuario', 'calificacion'])
            ->orderBy('fecha_hora_inicio', 'desc');

        $this->aplicarFiltrosFecha($query, $request);

        $citas = $query->get();
        $citasFormateadas = $citas->map(fn ($cita) => $this->formatearCitaPaciente($cita));

        return $this->successResponse([
            'citas' => $citasFormateadas,
            'total' => $citasFormateadas->count(),
        ]);
    }

    /**
     * Obtener citas para vista de calendario (agrupadas por día).
     */
    public function misCitasCalendario(Request $request): JsonResponse
    {
        $paciente = $this->obtenerPacienteAutenticado();

        if (!$paciente) {
            return $this->errorResponse('No se encontró el perfil de paciente.', Response::HTTP_NOT_FOUND);
        }

        $desde = $request->query('desde', Carbon::now()->subMonths(self::CALENDAR_PAST_MONTHS)->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', Carbon::now()->addMonths(self::CALENDAR_FUTURE_MONTHS)->endOfMonth()->toDateString());

        $citas = Cita::where('id_paciente', $paciente->id_paciente)
            ->with(['medico'])
            ->whereBetween('fecha_hora_inicio', [$desde, $hasta])
            ->orderBy('fecha_hora_inicio', 'asc')
            ->get();

        $eventos = $citas->map(function ($cita) {
            return [
                'id'            => $cita->id_cita,
                'title'         => $cita->motivo ?: 'Consulta médica',
                'start'         => $cita->fecha_hora_inicio->toIso8601String(),
                'end'           => $cita->fecha_hora_fin?->toIso8601String(),
                'color'         => self::COLORES_ESTADO[$cita->estado] ?? self::COLOR_DEFAULT,
                'extendedProps' => [
                    'id_cita'      => $cita->id_cita,
                    'estado'       => $cita->estado,
                    'medico'       => $cita->medico->nombres . ' ' . $cita->medico->apellidos,
                    'especialidad' => $cita->medico->especialidad,
                    'notas'        => $cita->notas,
                ],
            ];
        });

        return $this->successResponse(['eventos' => $eventos]);
    }

    /**
     * Obtener detalle de una cita específica.
     */
    public function detalleCita($id): JsonResponse
    {
        $paciente = $this->obtenerPacienteAutenticado();

        if (!$paciente) {
            return $this->errorResponse('No se encontró el perfil de paciente.', Response::HTTP_NOT_FOUND);
        }

        $cita = $this->buscarCitaDelPaciente($id, $paciente->id_paciente, ['medico.usuario', 'calificacion']);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse([
            'cita' => [
                'id_cita'              => $cita->id_cita,
                'fecha_hora_inicio'    => $cita->fecha_hora_inicio,
                'fecha_hora_fin'       => $cita->fecha_hora_fin,
                'motivo'               => $cita->motivo,
                'estado'               => $cita->estado,
                'notas'                => $cita->notas,
                'medico'               => $this->formatearInfoMedico($cita->medico),
                'puede_confirmar'      => $cita->estado === 'pendiente',
                'puede_reprogramar'    => in_array($cita->estado, self::ESTADOS_MODIFICABLES),
                'puede_cancelar'       => in_array($cita->estado, self::ESTADOS_MODIFICABLES),
                'puede_calificar'      => $cita->estado === 'completado' && !$cita->calificacion,
                'calificacion'         => $cita->calificacion,
            ],
        ]);
    }

    /**
     * Confirmar una cita pendiente.
     */
    public function confirmarCita($id, Request $request): JsonResponse
    {
        $paciente = $this->obtenerPacienteAutenticado();

        if (!$paciente) {
            return $this->errorResponse('No se encontró el perfil de paciente.', Response::HTTP_NOT_FOUND);
        }

        $cita = $this->buscarCitaDelPaciente($id, $paciente->id_paciente);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if ($cita->estado !== 'pendiente') {
            return $this->errorResponse('Solo se pueden confirmar citas pendientes.', Response::HTTP_BAD_REQUEST);
        }

        $cita->update(['estado' => 'confirmado']);

        $this->registrarActividad(
            Auth::id(),
            'confirmar_cita',
            "Cita #{$cita->id_cita} confirmada por paciente",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()->load('medico')],
            'Cita confirmada exitosamente.'
        );
    }

    /**
     * Reprogramar una cita.
     */
    public function reprogramarCita($id, Request $request): JsonResponse
    {
        $paciente = $this->obtenerPacienteAutenticado();

        if (!$paciente) {
            return $this->errorResponse('No se encontró el perfil de paciente.', Response::HTTP_NOT_FOUND);
        }

        $cita = $this->buscarCitaDelPaciente($id, $paciente->id_paciente);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if (!in_array($cita->estado, self::ESTADOS_MODIFICABLES)) {
            return $this->errorResponse('Esta cita no puede ser reprogramada.', Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'fecha_hora_inicio'      => 'required|date|after:now',
            'fecha_hora_fin'         => 'nullable|date|after:fecha_hora_inicio',
            'motivo_reprogramacion'  => 'nullable|string|max:500',
        ], [
            'fecha_hora_inicio.required' => 'La nueva fecha y hora es requerida.',
            'fecha_hora_inicio.date'     => 'Formato de fecha inválido.',
            'fecha_hora_inicio.after'    => 'La fecha debe ser futura.',
            'fecha_hora_fin.after'       => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $fechaInicio = Carbon::parse($request->fecha_hora_inicio);
        $fechaFin = $request->fecha_hora_fin
            ? Carbon::parse($request->fecha_hora_fin)
            : $fechaInicio->copy()->addMinutes(self::DEFAULT_APPOINTMENT_DURATION);

        if ($this->existeConflictoCitaPaciente($cita->id_medico, $fechaInicio, $fechaFin, $cita->id_cita)) {
            return $this->errorResponse('El horario seleccionado no está disponible.', Response::HTTP_BAD_REQUEST);
        }

        $cita->update([
            'fecha_hora_inicio' => $fechaInicio,
            'fecha_hora_fin'    => $fechaFin,
            'notas'             => $this->anexarNota(
                $cita->notas,
                '[Reprogramada por paciente] ' . ($request->motivo_reprogramacion ?? 'Sin motivo especificado')
            ),
        ]);

        $this->registrarActividad(
            Auth::id(),
            'reprogramar_cita',
            "Cita #{$cita->id_cita} reprogramada a {$fechaInicio->format('Y-m-d H:i')}",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()->load('medico')],
            'Cita reprogramada exitosamente.'
        );
    }

    /**
     * Cancelar una cita.
     */
    public function cancelarCita($id, Request $request): JsonResponse
    {
        $paciente = $this->obtenerPacienteAutenticado();

        if (!$paciente) {
            return $this->errorResponse('No se encontró el perfil de paciente.', Response::HTTP_NOT_FOUND);
        }

        $cita = $this->buscarCitaDelPaciente($id, $paciente->id_paciente);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if (!in_array($cita->estado, self::ESTADOS_MODIFICABLES)) {
            return $this->errorResponse('Esta cita no puede ser cancelada.', Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'motivo_cancelacion' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $cita->update([
            'estado' => 'cancelado',
            'notas'  => $this->anexarNota(
                $cita->notas,
                '[Cancelada por paciente] ' . ($request->motivo_cancelacion ?? 'Sin motivo especificado')
            ),
        ]);

        $this->registrarActividad(
            Auth::id(),
            'cancelar_cita',
            "Cita #{$cita->id_cita} cancelada por paciente",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()->load('medico')],
            'Cita cancelada exitosamente.'
        );
    }

    /**
     * Calificar una cita completada.
     */
    public function calificarCita($id, Request $request): JsonResponse
    {
        $paciente = $this->obtenerPacienteAutenticado();

        if (!$paciente) {
            return $this->errorResponse('No se encontró el perfil de paciente.', Response::HTTP_NOT_FOUND);
        }

        $cita = $this->buscarCitaDelPaciente($id, $paciente->id_paciente, ['calificacion']);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if ($cita->estado !== 'completado') {
            return $this->errorResponse('Solo se pueden calificar citas completadas.', Response::HTTP_BAD_REQUEST);
        }

        if ($cita->calificacion) {
            return $this->errorResponse('Esta cita ya ha sido calificada.', Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'puntuacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
        ], [
            'puntuacion.required' => 'La puntuación es requerida.',
            'puntuacion.integer'  => 'La puntuación debe ser un número.',
            'puntuacion.min'      => 'La puntuación mínima es 1.',
            'puntuacion.max'      => 'La puntuación máxima es 5.',
            'comentario.max'      => 'El comentario no puede exceder 1000 caracteres.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $calificacion = Calificacion::create([
            'id_cita'     => $cita->id_cita,
            'id_paciente' => $paciente->id_paciente,
            'id_medico'   => $cita->id_medico,
            'puntuacion'  => $request->puntuacion,
            'comentario'  => $request->comentario,
        ]);

        $this->registrarActividad(
            Auth::id(),
            'calificar_cita',
            "Cita #{$cita->id_cita} calificada con {$request->puntuacion} estrellas",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['calificacion' => $calificacion],
            'Calificación registrada exitosamente.'
        );
    }

    /**
     * Obtener estadísticas de citas del paciente.
     */
    public function estadisticasCitas(): JsonResponse
    {
        $paciente = $this->obtenerPacienteOpcional();

        if (!$paciente) {
            return $this->successResponse([
                'estadisticas' => [
                    'total' => 0, 'pendientes' => 0, 'confirmadas' => 0,
                    'completadas' => 0, 'canceladas' => 0, 'proxima_cita' => null,
                ],
            ]);
        }

        $queryBase = Cita::where('id_paciente', $paciente->id_paciente);

        $estadisticas = [
            'total'        => (clone $queryBase)->count(),
            'pendientes'   => (clone $queryBase)->where('estado', 'pendiente')->count(),
            'confirmadas'  => (clone $queryBase)->where('estado', 'confirmado')->count(),
            'completadas'  => (clone $queryBase)->where('estado', 'completado')->count(),
            'canceladas'   => (clone $queryBase)->where('estado', 'cancelado')->count(),
            'proxima_cita' => (clone $queryBase)
                ->whereIn('estado', ['pendiente', 'confirmado'])
                ->where('fecha_hora_inicio', '>=', now())
                ->orderBy('fecha_hora_inicio', 'asc')
                ->with('medico')
                ->first(),
        ];

        return $this->successResponse(['estadisticas' => $estadisticas]);
    }

    // ============================================================
    // MÉTODOS ESPECÍFICOS PARA SECRETARIA – GESTIÓN DE AGENDA
    // ============================================================

    /**
     * @OA\Get(
     *     path="/api/secretaria/agenda/calendario",
     *     summary="Vista de calendario completa para secretaria",
     *     description="Obtiene todas las citas en formato calendario con filtros avanzados",
     *     tags={"Agenda Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="vista", in="query", description="Tipo de vista", required=false,
     *         @OA\Schema(type="string", enum={"dia", "semana", "mes"}, default="semana")),
     *     @OA\Parameter(name="fecha", in="query", description="Fecha base (Y-m-d)", required=false,
     *         @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="medico_id", in="query", description="Filtrar por médico", required=false,
     *         @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sala", in="query", description="Filtrar por sala", required=false,
     *         @OA\Schema(type="string"))
     * )
     */
    public function calendarioSecretaria(Request $request): JsonResponse
    {
        try {
            $vista = $request->vista ?? 'semana';
            $fecha = $request->fecha ? Carbon::parse($request->fecha) : now();

            [$fechaInicio, $fechaFin] = $this->calcularRangoFechas($vista, $fecha);

            $query = Cita::with(['paciente', 'medico'])
                ->whereBetween('fecha_hora_inicio', [$fechaInicio, $fechaFin]);

            $this->aplicarFiltrosCalendarioSecretaria($query, $request);

            $citas = $query->orderBy('fecha_hora_inicio')->get();

            $citasFormateadas = $citas->map(fn ($cita) => $this->formatearCitaCalendarioSecretaria($cita));

            $estadisticas = [
                'total_citas'    => $citas->count(),
                'por_estado'     => $citas->groupBy('estado')->map->count(),
                'por_medico'     => $citas->groupBy('id_medico')->map(function ($citasMedico) {
                    $medico = $citasMedico->first()->medico;
                    return [
                        'medico' => $medico->nombres . ' ' . $medico->apellidos,
                        'total'  => $citasMedico->count(),
                    ];
                }),
                'ocupacion_horas' => $this->calcularOcupacionHoras($citas, $fechaInicio, $fechaFin),
            ];

            return $this->successResponse([
                'citas'        => $citasFormateadas,
                'periodo'      => [
                    'inicio' => $fechaInicio->format('Y-m-d'),
                    'fin'    => $fechaFin->format('Y-m-d'),
                    'vista'  => $vista,
                ],
                'estadisticas' => $estadisticas,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener calendario: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/secretaria/agenda/crear-cita",
     *     summary="Crear nueva cita desde secretaria",
     *     description="Permite a la secretaria crear citas para cualquier paciente",
     *     tags={"Agenda Secretaria"},
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function crearCitaSecretaria(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'paciente_id'      => 'required|exists:pacientes,id_paciente',
                'medico_id'        => 'required|exists:medicos,id_medico',
                'fecha_hora_inicio' => 'required|date|after:now',
                'duracion_minutos' => 'required|integer|min:15|max:180',
                'motivo'           => 'required|string|max:255',
                'notas'            => 'nullable|string|max:500',
                'estado_inicial'   => 'required|in:pendiente,confirmado',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $fechaInicio = Carbon::parse($request->fecha_hora_inicio);
            $fechaFin = $fechaInicio->copy()->addMinutes($request->duracion_minutos);

            if ($this->existeConflictoCitaSecretaria($request->medico_id, $fechaInicio, $fechaFin)) {
                return $this->errorResponse(
                    'El médico ya tiene una cita programada en ese horario',
                    Response::HTTP_CONFLICT
                );
            }

            $cita = Cita::create([
                'id_paciente'      => $request->paciente_id,
                'id_medico'        => $request->medico_id,
                'fecha_hora_inicio' => $fechaInicio,
                'fecha_hora_fin'   => $fechaFin,
                'motivo'           => $request->motivo,
                'notas'            => $request->notas,
                'estado'           => $request->estado_inicial,
                'creado_por'       => Auth::id(),
            ]);

            $this->registrarActividadSecretaria(
                'crear_cita_secretaria',
                $cita->id_cita,
                $cita->toArray(),
                $request
            );

            $cita->load(['paciente', 'medico']);

            return $this->successResponse([
                'cita' => $this->formatearCitaResumenSecretaria($cita),
            ], 'Cita creada exitosamente', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear cita: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/secretaria/agenda/actualizar-cita/{id}",
     *     summary="Actualizar cita existente (drag & drop)",
     *     description="Permite actualizar fecha, hora y médico de una cita",
     *     tags={"Agenda Secretaria"},
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function actualizarCitaSecretaria(Request $request, $id): JsonResponse
    {
        try {
            $cita = Cita::with(['paciente', 'medico'])->findOrFail($id);

            if (!in_array($cita->estado, self::ESTADOS_MODIFICABLES)) {
                return $this->errorResponse(
                    'No se puede modificar una cita en estado: ' . $cita->estado,
                    Response::HTTP_BAD_REQUEST
                );
            }

            $validator = Validator::make($request->all(), [
                'nueva_fecha_hora' => 'required|date|after:now',
                'nuevo_medico_id'  => 'nullable|exists:medicos,id_medico',
                'nueva_duracion'   => 'nullable|integer|min:15|max:180',
                'motivo_cambio'    => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $datosAnteriores = $cita->toArray();
            $nuevaFechaInicio = Carbon::parse($request->nueva_fecha_hora);
            $nuevaDuracion = $request->nueva_duracion ?? $cita->fecha_hora_inicio->diffInMinutes($cita->fecha_hora_fin);
            $nuevaFechaFin = $nuevaFechaInicio->copy()->addMinutes($nuevaDuracion);
            $nuevoMedicoId = $request->nuevo_medico_id ?? $cita->id_medico;

            if ($this->existeConflictoCitaSecretaria($nuevoMedicoId, $nuevaFechaInicio, $nuevaFechaFin, $id)) {
                return $this->errorResponse(
                    'El médico ya tiene una cita programada en ese horario',
                    Response::HTTP_CONFLICT
                );
            }

            $cita->update([
                'fecha_hora_inicio' => $nuevaFechaInicio,
                'fecha_hora_fin'    => $nuevaFechaFin,
                'id_medico'         => $nuevoMedicoId,
                'notas'             => $this->anexarNota(
                    $cita->notas,
                    '[' . now()->format('Y-m-d H:i') . '] ' . $request->motivo_cambio
                ),
            ]);

            $this->registrarActividadSecretaria(
                'actualizar_cita_secretaria',
                $cita->id_cita,
                $cita->fresh()->toArray(),
                $request,
                $datosAnteriores
            );

            $cita->load(['paciente', 'medico']);

            return $this->successResponse([
                'cita' => $this->formatearCitaResumenSecretaria($cita),
            ], 'Cita actualizada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar cita: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/agenda/filtros-avanzados",
     *     summary="Búsqueda avanzada de citas",
     *     description="Permite filtrar citas con múltiples criterios",
     *     tags={"Agenda Secretaria"},
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function filtrosAvanzadosSecretaria(Request $request): JsonResponse
    {
        try {
            $query = Cita::with(['paciente', 'medico']);

            $this->aplicarFiltrosAvanzados($query, $request);

            $orden = $request->orden ?? 'fecha_hora_inicio';
            $direccion = $request->direccion ?? 'asc';
            $query->orderBy($orden, $direccion);

            $citas = $query->paginate($request->per_page ?? self::DEFAULT_PER_PAGE);

            $citasFormateadas = $citas->getCollection()->map(
                fn ($cita) => $this->formatearCitaFiltroAvanzado($cita)
            );

            return $this->successResponse([
                'citas'              => $citasFormateadas,
                'paginacion'         => [
                    'total'          => $citas->total(),
                    'por_pagina'     => $citas->perPage(),
                    'pagina_actual'  => $citas->currentPage(),
                    'total_paginas'  => $citas->lastPage(),
                ],
                'filtros_aplicados'  => $request->only([
                    'fecha_desde', 'fecha_hasta', 'estados', 'medico_id',
                    'buscar_paciente', 'motivo', 'sala', 'hora_desde', 'hora_hasta',
                ]),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error en búsqueda avanzada: ' . $e->getMessage());
        }
    }

    // ============================================================
    // MÉTODOS AUXILIARES
    // ============================================================

    /**
     * Obtiene el paciente del usuario autenticado (devuelve null si no existe, sin error).
     */
    private function obtenerPacienteOpcional(): ?Paciente
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        return Paciente::where('id_usuario', $usuario->id_usuario)->first();
    }

    /**
     * Obtiene el paciente del usuario autenticado.
     */
    private function obtenerPacienteAutenticado(): ?Paciente
    {
        return $this->obtenerPacienteOpcional();
    }

    /**
     * Busca una cita que pertenezca al paciente autenticado.
     */
    private function buscarCitaDelPaciente(int $idCita, int $idPaciente, array $relaciones = []): ?Cita
    {
        $query = Cita::where('id_cita', $idCita)->where('id_paciente', $idPaciente);

        if (!empty($relaciones)) {
            $query->with($relaciones);
        }

        return $query->first();
    }

    /**
     * Registra una entrada en el log de actividad del módulo de citas (paciente).
     */
    private function registrarActividad(int $idUsuario, string $accion, string $descripcion, ?string $ip, mixed $registroAfectado = null): void
    {
        LogActividad::create([
            'id_usuario'        => $idUsuario,
            'accion'            => $accion,
            'modulo_afectado'   => 'citas',
            'registro_afectado' => $registroAfectado,
            'descripcion'       => $descripcion,
            'ip_usuario'        => $ip,
        ]);
    }

    /**
     * Registra una actividad de secretaría con datos extendidos.
     */
    private function registrarActividadSecretaria(
        string $accion,
        int $idRegistro,
        array $datosNuevos,
        Request $request,
        ?array $datosAnteriores = null
    ): void {
        LogActividad::create([
            'accion'               => $accion,
            'tabla_afectada'       => 'citas',
            'id_registro_afectado' => $idRegistro,
            'datos_anteriores'     => $datosAnteriores ? json_encode($datosAnteriores) : null,
            'datos_nuevos'         => json_encode($datosNuevos),
            'ip_address'           => $request->ip(),
            'user_agent'           => $request->userAgent(),
            'id_usuario'           => Auth::id(),
        ]);
    }

    /**
     * Formatea la información de un médico.
     */
    private function formatearInfoMedico($medico): array
    {
        return [
            'id_medico'    => $medico->id_medico,
            'nombres'      => $medico->nombres,
            'apellidos'    => $medico->apellidos,
            'especialidad' => $medico->especialidad,
            'foto_url'     => $medico->foto_url,
        ];
    }

    /**
     * Formatea una cita para la vista del paciente.
     */
    private function formatearCitaPaciente(Cita $cita): array
    {
        return [
            'id_cita'             => $cita->id_cita,
            'fecha_hora_inicio'   => $cita->fecha_hora_inicio,
            'fecha_hora_fin'      => $cita->fecha_hora_fin,
            'motivo'              => $cita->motivo,
            'estado'              => $cita->estado,
            'notas'               => $cita->notas,
            'medico'              => $this->formatearInfoMedico($cita->medico),
            'puede_confirmar'     => $cita->estado === 'pendiente',
            'puede_reprogramar'   => in_array($cita->estado, self::ESTADOS_MODIFICABLES),
            'puede_cancelar'      => in_array($cita->estado, self::ESTADOS_MODIFICABLES),
            'puede_calificar'     => $cita->estado === 'completado' && !$cita->calificacion,
            'calificacion'        => $cita->calificacion ? [
                'id_calificacion' => $cita->calificacion->id_calificacion,
                'puntuacion'      => $cita->calificacion->puntuacion,
                'comentario'      => $cita->calificacion->comentario,
                'fecha'           => $cita->calificacion->fecha,
            ] : null,
            'created_at'          => $cita->created_at,
            'updated_at'          => $cita->updated_at,
        ];
    }

    /**
     * Formatea una cita para el calendario de secretaría.
     */
    private function formatearCitaCalendarioSecretaria(Cita $cita): array
    {
        $pacienteNombre = $cita->paciente->nombres . ' ' . $cita->paciente->apellidos;
        $medicoNombre = $cita->medico->nombres . ' ' . $cita->medico->apellidos;

        return [
            'id'              => $cita->id_cita,
            'title'           => $pacienteNombre,
            'start'           => $cita->fecha_hora_inicio->toISOString(),
            'end'             => $cita->fecha_hora_fin->toISOString(),
            'estado'          => $cita->estado,
            'backgroundColor' => self::COLORES_ESTADO[$cita->estado] ?? self::COLOR_DEFAULT,
            'borderColor'     => self::COLORES_ESTADO[$cita->estado] ?? self::COLOR_DEFAULT,
            'extendedProps'   => [
                'paciente_id'       => $cita->id_paciente,
                'paciente_nombre'   => $pacienteNombre,
                'paciente_telefono' => $cita->paciente->telefono,
                'medico_id'         => $cita->id_medico,
                'medico_nombre'     => $medicoNombre,
                'motivo'            => $cita->motivo,
                'notas'             => $cita->notas,
                'sala'              => $cita->medico->consultorio ?? 'No asignado',
                'puede_editar'      => in_array($cita->estado, self::ESTADOS_EDITABLES),
                'puede_cancelar'    => in_array($cita->estado, self::ESTADOS_EDITABLES),
                'duracion_minutos'  => $cita->fecha_hora_inicio->diffInMinutes($cita->fecha_hora_fin),
            ],
        ];
    }

    /**
     * Formatea un resumen breve de cita (secretaría).
     */
    private function formatearCitaResumenSecretaria(Cita $cita): array
    {
        return [
            'id'                => $cita->id_cita,
            'paciente'          => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
            'medico'            => $cita->medico->nombres . ' ' . $cita->medico->apellidos,
            'fecha_hora_inicio' => $cita->fecha_hora_inicio,
            'fecha_hora_fin'    => $cita->fecha_hora_fin,
            'estado'            => $cita->estado,
            'motivo'            => $cita->motivo,
        ];
    }

    /**
     * Formatea una cita para la vista de filtros avanzados.
     */
    private function formatearCitaFiltroAvanzado(Cita $cita): array
    {
        return [
            'id'       => $cita->id_cita,
            'paciente' => [
                'id'              => $cita->paciente->id_paciente,
                'nombre_completo' => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
                'documento'       => $cita->paciente->numero_documento,
                'telefono'        => $cita->paciente->telefono,
                'email'           => $cita->paciente->email,
            ],
            'medico'   => [
                'id'              => $cita->medico->id_medico,
                'nombre_completo' => $cita->medico->nombres . ' ' . $cita->medico->apellidos,
                'especialidad'    => $cita->medico->especialidad,
                'consultorio'     => $cita->medico->consultorio,
            ],
            'fecha_hora_inicio'    => $cita->fecha_hora_inicio,
            'fecha_hora_fin'       => $cita->fecha_hora_fin,
            'motivo'               => $cita->motivo,
            'estado'               => $cita->estado,
            'notas'                => $cita->notas,
            'duracion_minutos'     => $cita->fecha_hora_inicio->diffInMinutes($cita->fecha_hora_fin),
            'acciones_disponibles' => [
                'puede_editar'    => in_array($cita->estado, self::ESTADOS_EDITABLES),
                'puede_cancelar'  => in_array($cita->estado, self::ESTADOS_EDITABLES),
                'puede_checkin'   => $cita->estado === 'confirmado',
                'puede_completar' => $cita->estado === 'siendo_atendido',
            ],
        ];
    }

    /**
     * Aplica filtros comunes de fecha/estado/mes a un query de citas.
     */
    private function aplicarFiltrosFecha($query, Request $request): void
    {
        if ($request->query('estado')) {
            $query->where('estado', $request->query('estado'));
        }

        if ($request->query('desde')) {
            $query->whereDate('fecha_hora_inicio', '>=', $request->query('desde'));
        }

        if ($request->query('hasta')) {
            $query->whereDate('fecha_hora_inicio', '<=', $request->query('hasta'));
        }

        if ($request->query('mes')) {
            $mes = $request->query('mes');
            $query->whereYear('fecha_hora_inicio', '=', substr($mes, 0, 4))
                ->whereMonth('fecha_hora_inicio', '=', substr($mes, 5, 2));
        }
    }

    /**
     * Aplica filtros de calendario de secretaría (medico, sala, estado).
     */
    private function aplicarFiltrosCalendarioSecretaria($query, Request $request): void
    {
        if ($request->filled('medico_id')) {
            $query->where('id_medico', $request->medico_id);
        }

        if ($request->filled('sala')) {
            $query->whereHas('medico', function ($queryBuilder) use ($request) {
                $queryBuilder->where('consultorio', $request->sala);
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
    }

    /**
     * Aplica filtros avanzados de búsqueda de citas (multi-criterio).
     */
    private function aplicarFiltrosAvanzados($query, Request $request): void
    {
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_hora_inicio', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_hora_inicio', '<=', $request->fecha_hasta);
        }

        if ($request->filled('estados')) {
            $estados = is_array($request->estados) ? $request->estados : explode(',', $request->estados);
            $query->whereIn('estado', $estados);
        }

        if ($request->filled('medico_id')) {
            $query->where('id_medico', $request->medico_id);
        }

        if ($request->filled('buscar_paciente')) {
            $termino = $request->buscar_paciente;
            $query->whereHas('paciente', function ($queryBuilder) use ($termino) {
                $queryBuilder->where('nombres', 'like', "%{$termino}%")
                    ->orWhere('apellidos', 'like', "%{$termino}%")
                    ->orWhere('numero_documento', 'like', "%{$termino}%");
            });
        }

        if ($request->filled('motivo')) {
            $query->where('motivo', 'like', '%' . $request->motivo . '%');
        }

        if ($request->filled('sala')) {
            $query->whereHas('medico', function ($queryBuilder) use ($request) {
                $queryBuilder->where('consultorio', $request->sala);
            });
        }

        if ($request->filled('hora_desde') && $request->filled('hora_hasta')) {
            $query->whereTime('fecha_hora_inicio', '>=', $request->hora_desde)
                ->whereTime('fecha_hora_inicio', '<=', $request->hora_hasta);
        }
    }

    /**
     * Verifica si existe un conflicto de horario (para citas del paciente).
     */
    private function existeConflictoCitaPaciente(int $idMedico, Carbon $fechaInicio, Carbon $fechaFin, ?int $excluirCitaId = null): bool
    {
        $query = Cita::where('id_medico', $idMedico)
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->where(function ($queryBuilder) use ($fechaInicio, $fechaFin) {
                $queryBuilder->whereBetween('fecha_hora_inicio', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_hora_fin', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($subQuery) use ($fechaInicio, $fechaFin) {
                        $subQuery->where('fecha_hora_inicio', '<=', $fechaInicio)
                            ->where('fecha_hora_fin', '>=', $fechaFin);
                    });
            });

        if ($excluirCitaId) {
            $query->where('id_cita', '!=', $excluirCitaId);
        }

        return $query->exists();
    }

    /**
     * Verifica si existe un conflicto de horario (para citas de secretaría).
     */
    private function existeConflictoCitaSecretaria(int $idMedico, Carbon $fechaInicio, Carbon $fechaFin, ?int $excluirCitaId = null): bool
    {
        $query = Cita::where('id_medico', $idMedico)
            ->where('estado', '!=', 'cancelado')
            ->where(function ($queryBuilder) use ($fechaInicio, $fechaFin) {
                $queryBuilder->whereBetween('fecha_hora_inicio', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_hora_fin', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($subQuery) use ($fechaInicio, $fechaFin) {
                        $subQuery->where('fecha_hora_inicio', '<=', $fechaInicio)
                            ->where('fecha_hora_fin', '>=', $fechaFin);
                    });
            });

        if ($excluirCitaId) {
            $query->where('id_cita', '!=', $excluirCitaId);
        }

        return $query->exists();
    }

    /**
     * Anexa una nota a las notas existentes.
     */
    private function anexarNota(?string $notasExistentes, string $nuevaNota): string
    {
        return ($notasExistentes ? $notasExistentes . "\n" : '') . $nuevaNota;
    }

    /**
     * Calcula el rango de fechas según la vista de calendario.
     */
    private function calcularRangoFechas(string $vista, Carbon $fecha): array
    {
        return match ($vista) {
            'dia'    => [$fecha->copy()->startOfDay(), $fecha->copy()->endOfDay()],
            'semana' => [$fecha->copy()->startOfWeek(), $fecha->copy()->endOfWeek()],
            'mes'    => [$fecha->copy()->startOfMonth(), $fecha->copy()->endOfMonth()],
            default  => [$fecha->copy()->startOfWeek(), $fecha->copy()->endOfWeek()],
        };
    }

    /**
     * Calcula la ocupación en horas del período.
     */
    private function calcularOcupacionHoras($citas, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $totalHorasDisponibles = $fechaInicio->diffInHours($fechaFin);
        $horasOcupadas = $citas->sum(
            fn ($cita) => $cita->fecha_hora_inicio->diffInHours($cita->fecha_hora_fin)
        );

        return [
            'total_horas_disponibles' => $totalHorasDisponibles,
            'horas_ocupadas'          => $horasOcupadas,
            'porcentaje_ocupacion'    => $totalHorasDisponibles > 0
                ? round(($horasOcupadas / $totalHorasDisponibles) * 100, 2)
                : 0,
        ];
    }
}
