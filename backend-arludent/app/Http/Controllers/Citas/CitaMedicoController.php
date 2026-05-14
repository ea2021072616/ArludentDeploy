<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Models\Cita;
use App\Models\LogActividad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Citas para Médicos.
 *
 * Permite al médico gestionar sus citas: listar, cancelar, agregar notas y marcar como completadas.
 * También expone endpoints de secretaría para gestión administrativa de citas.
 */
class CitaMedicoController extends Controller
{
    /** Meses hacia atrás por defecto para vista de calendario. */
    private const CALENDAR_PAST_MONTHS = 3;

    /** Meses hacia adelante por defecto para vista de calendario. */
    private const CALENDAR_FUTURE_MONTHS = 6;

    /** Duración por defecto de una cita en minutos (secretaría). */
    private const DEFAULT_APPOINTMENT_DURATION = 30;

    /** Resultados por página por defecto. */
    private const DEFAULT_PER_PAGE = 25;

    /** Límite de resultados para búsquedas de autocompletado. */
    private const AUTOCOMPLETE_LIMIT = 10;

    /** Límite de resultados para búsquedas generales. */
    private const SEARCH_LIMIT = 50;

    /** Días para estadísticas de citas próximas. */
    private const UPCOMING_DAYS = 7;

    /** Estados que permiten cancelación. */
    private const ESTADOS_CANCELABLES = ['pendiente', 'confirmado', 'en_espera'];

    /** Estados que permiten marcar como completado. */
    private const ESTADOS_COMPLETABLES = ['confirmado', 'siendo_atendido'];

    /** Estados que permiten agregar notas. */
    private const ESTADOS_CON_NOTAS = ['confirmado', 'siendo_atendido', 'completado'];

    /** Mapa de colores por estado de cita para calendario del médico. */
    private const COLORES_ESTADO_MEDICO = [
        'pendiente'       => '#FFA500',
        'confirmado'      => '#4CAF50',
        'en_espera'       => '#2196F3',
        'siendo_atendido' => '#9C27B0',
        'completado'      => '#757575',
        'cancelado'       => '#F44336',
        'no_asistio'      => '#FF5722',
    ];

    /** Color por defecto si el estado no está en el mapa. */
    private const COLOR_DEFAULT = '#9E9E9E';

    /**
     * Obtener todas las citas del médico autenticado.
     */
    public function misCitas(Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $query = Cita::where('id_medico', $medico->id_medico)
            ->with(['paciente.usuario', 'calificacion'])
            ->orderBy('fecha_hora_inicio', 'desc');

        $this->aplicarFiltrosFecha($query, $request);

        $citas = $query->get();

        $citasFormateadas = $citas->map(fn ($cita) => $this->formatearCitaMedico($cita));

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
        $medico = $this->obtenerMedicoAutenticado();

        $desde = $request->query('desde', Carbon::now()->subMonths(self::CALENDAR_PAST_MONTHS)->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', Carbon::now()->addMonths(self::CALENDAR_FUTURE_MONTHS)->endOfMonth()->toDateString());

        $citas = Cita::where('id_medico', $medico->id_medico)
            ->with(['paciente'])
            ->whereBetween('fecha_hora_inicio', [$desde, $hasta])
            ->orderBy('fecha_hora_inicio', 'asc')
            ->get();

        $eventos = $citas->map(function ($cita) {
            return [
                'id'            => $cita->id_cita,
                'title'         => $cita->paciente
                    ? $cita->paciente->nombres . ' ' . $cita->paciente->apellidos
                    : 'Paciente',
                'start'         => $cita->fecha_hora_inicio->toIso8601String(),
                'end'           => $cita->fecha_hora_fin?->toIso8601String(),
                'color'         => self::COLORES_ESTADO_MEDICO[$cita->estado] ?? self::COLOR_DEFAULT,
                'extendedProps' => [
                    'id_cita' => $cita->id_cita,
                    'estado'  => $cita->estado,
                    'paciente' => $cita->paciente
                        ? $cita->paciente->nombres . ' ' . $cita->paciente->apellidos
                        : 'Sin paciente',
                    'motivo'  => $cita->motivo,
                    'notas'   => $cita->notas,
                ],
            ];
        });

        return $this->successResponse(['eventos' => $eventos]);
    }

    /**
     * Obtener detalle de una cita específica del médico.
     */
    public function detalleCita($id): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $cita = $this->buscarCitaDelMedico($id, $medico->id_medico, ['paciente.usuario', 'usuarioExterno', 'calificacion']);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse([
            'cita' => [
                'id_cita'           => $cita->id_cita,
                'fecha_hora_inicio' => $cita->fecha_hora_inicio,
                'fecha_hora_fin'    => $cita->fecha_hora_fin,
                'motivo'            => $cita->motivo,
                'estado'            => $cita->estado,
                'notas'             => $cita->notas,
                'paciente'          => $this->formatearInfoPaciente($cita, true),
                'puede_cancelar'    => in_array($cita->estado, self::ESTADOS_CANCELABLES),
                'puede_completar'   => in_array($cita->estado, self::ESTADOS_COMPLETABLES),
                'puede_agregar_notas' => in_array($cita->estado, self::ESTADOS_CON_NOTAS),
                'calificacion'      => $cita->calificacion,
            ],
        ]);
    }

    /**
     * Cancelar una cita del médico.
     */
    public function cancelarCita($id, Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $cita = $this->buscarCitaDelMedico($id, $medico->id_medico);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if (!in_array($cita->estado, self::ESTADOS_CANCELABLES)) {
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
            'notas'  => $this->anexarNota($cita->notas, '[Cancelada por médico] ' .
                ($request->motivo_cancelacion ?? 'Sin motivo especificado')),
        ]);

        $this->registrarActividad(
            'cancelar_cita_medico',
            "Cita #{$cita->id_cita} cancelada por médico",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()->load('paciente')],
            'Cita cancelada exitosamente.'
        );
    }

    /**
     * Marcar cita como completada.
     */
    public function completarCita($id, Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $cita = $this->buscarCitaDelMedico($id, $medico->id_medico);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if (!in_array($cita->estado, self::ESTADOS_COMPLETABLES)) {
            return $this->errorResponse('Esta cita no puede ser marcada como completada.', Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'notas' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $cita->update([
            'estado' => 'completado',
            'notas'  => $request->notas
                ? $this->anexarNota($cita->notas, $request->notas)
                : $cita->notas,
        ]);

        $this->registrarActividad(
            'completar_cita',
            "Cita #{$cita->id_cita} marcada como completada",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()->load('paciente')],
            'Cita marcada como completada exitosamente.'
        );
    }

    /**
     * Agregar o actualizar notas de una cita.
     */
    public function agregarNotas($id, Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $cita = $this->buscarCitaDelMedico($id, $medico->id_medico);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if (!in_array($cita->estado, self::ESTADOS_CON_NOTAS)) {
            return $this->errorResponse('No se pueden agregar notas a esta cita.', Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'notas' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $cita->update(['notas' => $request->notas]);

        $this->registrarActividad(
            'agregar_notas_cita',
            "Notas agregadas a cita #{$cita->id_cita}",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()],
            'Notas agregadas exitosamente.'
        );
    }

    /**
     * Obtener estadísticas de citas del médico.
     */
    public function estadisticasCitas(): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $queryBase = Cita::where('id_medico', $medico->id_medico);

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
                ->with('paciente')
                ->first(),
        ];

        return $this->successResponse(['estadisticas' => $estadisticas]);
    }

    /**
     * Obtener TODAS las citas (para secretaría).
     */
    public function todasLasCitas(Request $request): JsonResponse
    {
        $query = Cita::with(['paciente', 'usuarioExterno', 'medico', 'calificacion'])
            ->orderBy('fecha_hora_inicio', 'desc');

        $this->aplicarFiltrosSecretaria($query, $request);

        $citas = $query->get();

        $citasFormateadas = $citas->map(fn ($cita) => $this->formatearCitaSecretaria($cita));

        return $this->successResponse(['data' => $citasFormateadas]);
    }

    /**
     * Obtener TODAS las citas para calendario (para secretaría).
     */
    public function todasLasCitasCalendario(Request $request): JsonResponse
    {
        $query = Cita::with(['paciente', 'usuarioExterno', 'medico']);

        if ($request->query('mes')) {
            $mes = $request->query('mes');
            $query->whereYear('fecha_hora_inicio', '=', substr($mes, 0, 4))
                ->whereMonth('fecha_hora_inicio', '=', substr($mes, 5, 2));
        }

        if ($request->query('medico_id')) {
            $query->where('id_medico', $request->query('medico_id'));
        }

        $citas = $query->orderBy('fecha_hora_inicio', 'asc')->get();

        $eventos = $citas->map(function ($cita) {
            $pacienteNombre = $cita->paciente
                ? "{$cita->paciente->nombres} {$cita->paciente->apellidos}"
                : ($cita->usuarioExterno ? $cita->usuarioExterno->username : 'Sin paciente');

            $medicoNombre = $cita->medico
                ? "Dr(a). {$cita->medico->nombres} {$cita->medico->apellidos}"
                : 'Sin médico';

            return [
                'id'            => $cita->id_cita,
                'title'         => "{$pacienteNombre} - {$medicoNombre}",
                'start'         => $cita->fecha_hora_inicio->toIso8601String(),
                'end'           => $cita->fecha_hora_fin?->toIso8601String(),
                'color'         => self::COLORES_ESTADO_MEDICO[$cita->estado] ?? self::COLOR_DEFAULT,
                'extendedProps' => [
                    'id_cita'  => $cita->id_cita,
                    'estado'   => $cita->estado,
                    'paciente' => $pacienteNombre,
                    'medico'   => $medicoNombre,
                    'motivo'   => $cita->motivo,
                    'notas'    => $cita->notas,
                ],
            ];
        });

        return $this->successResponse(['eventos' => $eventos]);
    }

    /**
     * Confirmar una cita (para secretaría).
     */
    public function confirmarCita(Request $request, $id): JsonResponse
    {
        $cita = Cita::find($id);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if (!in_array($cita->estado, ['pendiente', 'confirmado'])) {
            return $this->errorResponse(
                'No se puede confirmar esta cita en su estado actual.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $cita->update(['estado' => 'confirmado']);

        $this->registrarActividad(
            'confirmar_cita',
            "Cita #{$cita->id_cita} confirmada desde secretaría",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()],
            'Cita confirmada exitosamente.'
        );
    }

    /**
     * Obtener detalle de una cita específica (para secretaría).
     */
    public function show($id): JsonResponse
    {
        $cita = Cita::with(['paciente', 'usuarioExterno', 'medico', 'calificacion'])->find($id);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse([
            'data' => $this->formatearCitaSecretaria($cita),
        ]);
    }

    /**
     * Cambiar estado de una cita (para secretaría).
     */
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        $cita = Cita::find($id);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|in:pendiente,confirmado,en_espera,siendo_atendido,completado,cancelado,no_asistio',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Estado inválido.', Response::HTTP_BAD_REQUEST, $validator->errors());
        }

        $nuevoEstado = $request->input('estado');
        $estadoAnterior = $cita->estado;

        $cita->estado = $nuevoEstado;
        $cita->save();

        $this->registrarActividad(
            'cambiar_estado_cita',
            "Estado de cita #{$cita->id_cita} cambiado de '{$estadoAnterior}' a '{$nuevoEstado}'",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()],
            'Estado de la cita actualizado exitosamente.'
        );
    }

    /**
     * Cancelar una cita (para secretaría). No requiere verificación de médico.
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        $cita = Cita::find($id);

        if (!$cita) {
            return $this->errorResponse('Cita no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if (!in_array($cita->estado, self::ESTADOS_CANCELABLES)) {
            return $this->errorResponse(
                'Esta cita no puede ser cancelada en su estado actual.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $validator = Validator::make($request->all(), [
            'notas' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos inválidos.', Response::HTTP_BAD_REQUEST, $validator->errors());
        }

        $nuevasNotas = $request->notas ?? 'Cancelada por secretaría';

        $cita->estado = 'cancelado';
        $cita->notas = $this->anexarNota($cita->notas, "[Cancelada] {$nuevasNotas}");
        $cita->save();

        $this->registrarActividad(
            'cancelar_cita',
            "Cita #{$cita->id_cita} cancelada desde secretaría: {$nuevasNotas}",
            $request->ip(),
            $cita->id_cita
        );

        return $this->successResponse(
            ['cita' => $cita->fresh()],
            'Cita cancelada exitosamente.'
        );
    }

    /**
     * Listar todos los médicos.
     */
    public function listarMedicos(): JsonResponse
    {
        $medicos = Medico::with('usuario:id_usuario,username')
            ->get()
            ->map(fn ($medico) => [
                'id_medico'     => $medico->id_medico,
                'nombres'       => $medico->nombres,
                'apellidos'     => $medico->apellidos,
                'especialidad'  => $medico->especialidad,
                'nro_colegiado' => $medico->nro_colegiado,
                'tipo_medico'   => $medico->tipo_medico,
            ]);

        return $this->successResponse(['medicos' => $medicos]);
    }

    /**
     * Listar todos los pacientes (para secretaría).
     */
    public function listarPacientes(Request $request): JsonResponse
    {
        $query = Paciente::with('usuario');

        $this->aplicarFiltrosPacientes($query, $request);

        if ($request->has('search') && !empty($request->search)) {
            $terminoBusqueda = $request->search;
            $query->where(function ($queryBuilder) use ($terminoBusqueda) {
                $queryBuilder->where('nombres', 'LIKE', "%{$terminoBusqueda}%")
                    ->orWhere('apellidos', 'LIKE', "%{$terminoBusqueda}%")
                    ->orWhere('dni', 'LIKE', "%{$terminoBusqueda}%");
            });

            $pacientes = $query
                ->orderBy('created_at', 'DESC')
                ->limit(self::SEARCH_LIMIT)
                ->get()
                ->map(fn ($paciente) => $this->formatearDatosPaciente($paciente));

            return $this->successResponse(['pacientes' => $pacientes]);
        }

        $total = $query->count();
        $perPage = $request->input('per_page', self::DEFAULT_PER_PAGE);
        $page = $request->input('page', 1);

        $pacientes = $query
            ->orderBy('created_at', 'DESC')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn ($paciente) => $this->formatearDatosPaciente($paciente));

        return $this->successResponse([
            'pacientes'  => $pacientes,
            'pagination' => [
                'current_page' => (int) $page,
                'per_page'     => (int) $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Crear una nueva cita (para secretaría).
     */
    public function crearCita(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_paciente'      => 'required|exists:pacientes,id_paciente',
            'id_medico'        => 'required|exists:medicos,id_medico',
            'fecha_hora_inicio' => 'required|date|after:now',
            'duracion'         => 'nullable|integer|min:15|max:240',
            'motivo'           => 'nullable|string|max:500',
            'notas'            => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos inválidos.', Response::HTTP_BAD_REQUEST, $validator->errors());
        }

        $fechaInicio = Carbon::parse($request->fecha_hora_inicio);
        $duracion = $request->duracion ?? self::DEFAULT_APPOINTMENT_DURATION;
        $fechaFin = $fechaInicio->copy()->addMinutes($duracion);

        if ($this->existeConflictoCita($request->id_medico, $fechaInicio, $fechaFin)) {
            return $this->errorResponse(
                'El médico ya tiene una cita programada en ese horario.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $cita = Cita::create([
            'id_paciente'      => $request->id_paciente,
            'id_medico'        => $request->id_medico,
            'fecha_hora_inicio' => $fechaInicio,
            'fecha_hora_fin'   => $fechaFin,
            'motivo'           => $request->motivo,
            'estado'           => 'confirmado',
            'notas'            => $request->notas,
            'creado_por'       => Auth::id(),
        ]);

        $this->registrarActividad(
            'crear_cita',
            "Cita #{$cita->id_cita} creada desde secretaría",
            $request->ip(),
            $cita->id_cita
        );

        $cita->load(['paciente', 'medico']);

        return $this->successResponse(
            ['cita' => $cita],
            'Cita creada exitosamente.',
            Response::HTTP_CREATED
        );
    }

    /**
     * Buscar pacientes en tiempo real (autocompletado).
     */
    public function buscarPacientes(Request $request): JsonResponse
    {
        $busqueda = $request->query('q', '');

        if (strlen($busqueda) < 2) {
            return $this->successResponse(['pacientes' => []], 'Mínimo 2 caracteres para buscar');
        }

        $pacientes = Paciente::with('usuario')
            ->where(function ($queryBuilder) use ($busqueda) {
                $queryBuilder->where('nombres', 'like', "%{$busqueda}%")
                    ->orWhere('apellidos', 'like', "%{$busqueda}%")
                    ->orWhere('dni', 'like', "%{$busqueda}%");
            })
            ->where('estado', 'activo')
            ->limit(self::AUTOCOMPLETE_LIMIT)
            ->get()
            ->map(fn ($paciente) => [
                'id_paciente'    => $paciente->id_paciente,
                'nombres'        => $paciente->nombres,
                'apellidos'      => $paciente->apellidos,
                'dni'            => $paciente->dni,
                'telefono'       => $paciente->telefono,
                'correo'         => $paciente->correo,
                'nombre_completo' => "{$paciente->nombres} {$paciente->apellidos}",
                'dni_nombre'     => "{$paciente->dni} - {$paciente->nombres} {$paciente->apellidos}",
            ]);

        return $this->successResponse(['pacientes' => $pacientes]);
    }

    /**
     * Estadísticas generales de pacientes para secretaría.
     */
    public function estadisticasGeneralesPacientes(): JsonResponse
    {
        $totalPacientes = Paciente::where('estado', 'activo')->count();
        $totalInactivos = Paciente::where('estado', 'inactivo')->count();

        $citasPendientes = Cita::whereIn('estado', ['pendiente', 'confirmado'])
            ->whereDate('fecha_hora_inicio', '>=', now())
            ->count();

        $citasHoy = Cita::whereDate('fecha_hora_inicio', today())
            ->whereIn('estado', ['pendiente', 'confirmado', 'en_espera', 'siendo_atendido'])
            ->count();

        $pacientesConCitasProximas = Cita::whereBetween('fecha_hora_inicio', [now(), now()->addDays(self::UPCOMING_DAYS)])
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->distinct('id_paciente')
            ->count('id_paciente');

        return $this->successResponse([
            'total_pacientes'          => $totalPacientes,
            'total_inactivos'          => $totalInactivos,
            'citas_pendientes'         => $citasPendientes,
            'citas_hoy'               => $citasHoy,
            'pacientes_citas_proximas' => $pacientesConCitasProximas,
        ]);
    }

    /**
     * Ver detalle completo de un paciente.
     */
    public function verPaciente($id): JsonResponse
    {
        $paciente = Paciente::with([
            'usuario',
            'citas' => fn ($queryBuilder) => $queryBuilder->with('medico.usuario')
                ->orderBy('fecha_hora_inicio', 'desc')
                ->limit(10),
            'historiales' => fn ($queryBuilder) => $queryBuilder->with('medico.usuario')
                ->orderBy('fecha_atencion', 'desc')
                ->limit(5),
        ])->find($id);

        if (!$paciente) {
            return $this->errorResponse('Paciente no encontrado', Response::HTTP_NOT_FOUND);
        }

        $queryBase = Cita::where('id_paciente', $id);
        $totalCitas = (clone $queryBase)->count();
        $citasCompletadas = (clone $queryBase)->where('estado', 'completado')->count();
        $citasCanceladas = (clone $queryBase)->where('estado', 'cancelado')->count();

        $proximaCita = Cita::where('id_paciente', $id)
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->whereDate('fecha_hora_inicio', '>=', now())
            ->with('medico.usuario')
            ->orderBy('fecha_hora_inicio', 'asc')
            ->first();

        $pacienteData = [
            'id_paciente'       => $paciente->id_paciente,
            'nombres'           => $paciente->nombres,
            'apellidos'         => $paciente->apellidos,
            'tipo_documento'    => 'CC',
            'numero_documento'  => $paciente->dni,
            'fecha_nacimiento'  => $paciente->fecha_nacimiento,
            'edad'              => $paciente->fecha_nacimiento?->age,
            'genero'            => $paciente->sexo,
            'telefono'          => $paciente->telefono_responsable,
            'email'             => $paciente->correo,
            'direccion'         => $paciente->domicilio,
            'alergias'          => $paciente->alergias,
            'grupo_sanguineo'   => $paciente->grupo_sanguineo,
            'estado'            => $paciente->estado ?? 'activo',
            'estadisticas'      => [
                'total_citas'        => $totalCitas,
                'citas_completadas'  => $citasCompletadas,
                'citas_canceladas'   => $citasCanceladas,
            ],
            'proxima_cita'      => $proximaCita ? [
                'id_cita'           => $proximaCita->id_cita,
                'fecha_hora_inicio' => $proximaCita->fecha_hora_inicio,
                'medico'            => [
                    'nombres'       => $proximaCita->medico->nombres,
                    'apellidos'     => $proximaCita->medico->apellidos,
                    'especialidad'  => $proximaCita->medico->especialidad,
                ],
                'motivo'            => $proximaCita->motivo,
            ] : null,
            'ultimas_citas'     => $paciente->citas->map(fn ($cita) => [
                'id_cita'           => $cita->id_cita,
                'fecha_hora_inicio' => $cita->fecha_hora_inicio,
                'estado'            => $cita->estado,
                'motivo'            => $cita->motivo,
                'medico'            => [
                    'nombres'  => $cita->medico->nombres,
                    'apellidos' => $cita->medico->apellidos,
                ],
            ]),
            'historiales'       => $paciente->historiales->map(fn ($historial) => [
                'id_historial'          => $historial->id_historial,
                'fecha_atencion'        => $historial->fecha_atencion,
                'diagnostico'           => $historial->diagnostico,
                'tratamiento_realizado' => $historial->tratamiento_realizado,
                'medico'                => [
                    'nombres'  => $historial->medico->nombres,
                    'apellidos' => $historial->medico->apellidos,
                ],
            ]),
        ];

        return $this->successResponse(['paciente' => $pacienteData]);
    }

    /**
     * Listar seguimientos post-tratamiento.
     */
    public function listarSeguimientos(Request $request): JsonResponse
    {
        $query = \App\Models\SeguimientoPostTratamiento::with([
            'paciente', 'cita', 'realizadoPor',
        ]);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_seguimiento')) {
            $query->where('tipo_seguimiento', $request->tipo_seguimiento);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_seguimiento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_seguimiento', '<=', $request->fecha_hasta);
        }

        if ($request->filled('buscar_paciente')) {
            $busqueda = $request->buscar_paciente;
            $query->whereHas('paciente', function ($queryBuilder) use ($busqueda) {
                $queryBuilder->where('nombres', 'like', "%{$busqueda}%")
                    ->orWhere('apellidos', 'like', "%{$busqueda}%")
                    ->orWhere('dni', 'like', "%{$busqueda}%");
            });
        }

        if ($request->boolean('solo_vencidos')) {
            $query->vencidos();
        }

        $query->orderBy('prioridad', 'desc')
            ->orderBy('fecha_seguimiento', 'asc');

        $perPage = $request->input('per_page', self::DEFAULT_PER_PAGE);
        $seguimientos = $query->paginate($perPage);

        return $this->successResponse([
            'seguimientos' => $seguimientos->items(),
            'pagination'   => [
                'current_page' => $seguimientos->currentPage(),
                'per_page'     => $seguimientos->perPage(),
                'total'        => $seguimientos->total(),
                'last_page'    => $seguimientos->lastPage(),
            ],
        ]);
    }

    /**
     * Estadísticas de seguimientos.
     */
    public function estadisticasSeguimientos(): JsonResponse
    {
        $modelo = \App\Models\SeguimientoPostTratamiento::class;

        $porTipo = $modelo::selectRaw('tipo_seguimiento, count(*) as total')
            ->groupBy('tipo_seguimiento')
            ->get()
            ->pluck('total', 'tipo_seguimiento');

        return $this->successResponse([
            'total_seguimientos' => $modelo::count(),
            'pendientes'         => $modelo::where('estado', 'pendiente')->count(),
            'completados'        => $modelo::where('estado', 'realizado')->count(),
            'vencidos'           => $modelo::vencidos()->count(),
            'con_problemas'      => $modelo::conProblemas()->count(),
            'urgentes'           => $modelo::urgentes()->count(),
            'por_tipo'           => $porTipo,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene el perfil de médico del usuario autenticado.
     */
    private function obtenerMedicoAutenticado(): Medico
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        $medico = Medico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$medico) {
            abort(Response::HTTP_NOT_FOUND, 'No se encontró el perfil de médico.');
        }

        return $medico;
    }

    /**
     * Registra una entrada en el log de actividad del módulo de citas.
     */
    private function registrarActividad(string $accion, string $descripcion, ?string $ip, mixed $registroAfectado = null): void
    {
        LogActividad::create([
            'id_usuario'        => Auth::id(),
            'accion'            => $accion,
            'modulo_afectado'   => 'citas',
            'registro_afectado' => $registroAfectado,
            'descripcion'       => $descripcion,
            'ip_usuario'        => $ip,
        ]);
    }

    /**
     * Busca una cita que pertenezca al médico autenticado.
     */
    private function buscarCitaDelMedico(int $idCita, int $idMedico, array $relaciones = []): ?Cita
    {
        $query = Cita::where('id_cita', $idCita)->where('id_medico', $idMedico);

        if (!empty($relaciones)) {
            $query->with($relaciones);
        }

        return $query->first();
    }

    /**
     * Formatea la información del paciente o usuario externo para una cita.
     */
    private function formatearInfoPaciente(Cita $cita, bool $incluirDatosExtendidos = false): ?array
    {
        if ($cita->paciente) {
            $info = [
                'id_paciente' => $cita->paciente->id_paciente,
                'nombres'     => $cita->paciente->nombres,
                'apellidos'   => $cita->paciente->apellidos,
                'dni'         => $cita->paciente->dni,
                'telefono'    => $cita->paciente->telefono_responsable,
            ];

            if ($incluirDatosExtendidos) {
                $info['fecha_nacimiento'] = $cita->paciente->fecha_nacimiento;
                $info['domicilio'] = $cita->paciente->domicilio;
            }

            return $info;
        }

        if ($cita->usuarioExterno) {
            $info = [
                'id_paciente' => null,
                'nombres'     => $cita->usuarioExterno->username ?? 'Usuario',
                'apellidos'   => 'Externo',
                'dni'         => null,
                'telefono'    => $cita->usuarioExterno->telefono,
            ];

            if ($incluirDatosExtendidos) {
                $info['fecha_nacimiento'] = null;
                $info['domicilio'] = null;
            }

            return $info;
        }

        return null;
    }

    /**
     * Formatea una cita para la vista del médico.
     */
    private function formatearCitaMedico(Cita $cita): array
    {
        return [
            'id_cita'              => $cita->id_cita,
            'fecha_hora_inicio'    => $cita->fecha_hora_inicio,
            'fecha_hora_fin'       => $cita->fecha_hora_fin,
            'motivo'               => $cita->motivo,
            'estado'               => $cita->estado,
            'notas'                => $cita->notas,
            'paciente'             => $this->formatearInfoPaciente($cita),
            'puede_cancelar'       => in_array($cita->estado, self::ESTADOS_CANCELABLES),
            'puede_completar'      => in_array($cita->estado, self::ESTADOS_COMPLETABLES),
            'puede_agregar_notas'  => in_array($cita->estado, self::ESTADOS_CON_NOTAS),
            'calificacion'         => $cita->calificacion ? [
                'id_calificacion' => $cita->calificacion->id_calificacion,
                'puntuacion'      => $cita->calificacion->puntuacion,
                'comentario'      => $cita->calificacion->comentario,
                'fecha'           => $cita->calificacion->fecha,
            ] : null,
            'created_at'           => $cita->created_at,
            'updated_at'           => $cita->updated_at,
        ];
    }

    /**
     * Formatea una cita para la vista de secretaría.
     */
    private function formatearCitaSecretaria(Cita $cita): array
    {
        $medicoInfo = $cita->medico ? [
            'id_medico'    => $cita->medico->id_medico,
            'nombres'      => $cita->medico->nombres,
            'apellidos'    => $cita->medico->apellidos,
            'especialidad' => $cita->medico->especialidad,
        ] : null;

        return [
            'id_cita'           => $cita->id_cita,
            'fecha_hora_inicio' => $cita->fecha_hora_inicio,
            'fecha_hora_fin'    => $cita->fecha_hora_fin,
            'motivo'            => $cita->motivo,
            'estado'            => $cita->estado,
            'notas'             => $cita->notas,
            'paciente'          => $this->formatearInfoPaciente($cita),
            'usuario_externo'   => $cita->usuarioExterno ? [
                'id_usuario' => $cita->usuarioExterno->id_usuario,
                'username'   => $cita->usuarioExterno->username,
            ] : null,
            'medico'            => $medicoInfo,
            'calificacion'      => $cita->calificacion,
            'created_at'        => $cita->created_at,
            'updated_at'        => $cita->updated_at,
        ];
    }

    /**
     * Formatea datos de un paciente para listados.
     */
    private function formatearDatosPaciente(Paciente $paciente): array
    {
        $ultimaCita = Cita::where('id_paciente', $paciente->id_paciente)
            ->orderBy('fecha_hora_inicio', 'DESC')
            ->first();

        $edad = null;
        if ($paciente->fecha_nacimiento) {
            try {
                $edad = Carbon::parse($paciente->fecha_nacimiento)->age;
            } catch (\Exception $e) {
                // Fecha inválida — mantener null
            }
        }

        return [
            'id_paciente'      => $paciente->id_paciente,
            'nombres'          => $paciente->nombres,
            'apellidos'        => $paciente->apellidos,
            'dni'              => $paciente->dni,
            'sexo'             => $paciente->sexo,
            'fecha_nacimiento' => $paciente->fecha_nacimiento,
            'edad'             => $edad,
            'telefono'         => $paciente->usuario->telefono ?? $paciente->telefono_responsable,
            'email'            => $paciente->usuario->correo ?? null,
            'domicilio'        => $paciente->domicilio,
            'estado'           => $paciente->estado ?? 'activo',
            'created_at'       => $paciente->created_at,
            'updated_at'       => $paciente->updated_at,
            'ultima_cita'      => $ultimaCita ? [
                'fecha'  => $ultimaCita->fecha_hora_inicio,
                'motivo' => $ultimaCita->motivo,
            ] : null,
        ];
    }

    /**
     * Aplica filtros comunes de fecha y mes a un query de citas.
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
     * Aplica filtros para la vista de secretaría (estado, fechas, médico).
     */
    private function aplicarFiltrosSecretaria($query, Request $request): void
    {
        if ($request->query('estado')) {
            $query->where('estado', $request->query('estado'));
        }

        if ($request->query('fecha_desde')) {
            $query->whereDate('fecha_hora_inicio', '>=', $request->query('fecha_desde'));
        }

        if ($request->query('fecha_hasta')) {
            $query->whereDate('fecha_hora_inicio', '<=', $request->query('fecha_hasta'));
        }

        if ($request->query('medico_id')) {
            $query->where('id_medico', $request->query('medico_id'));
        }
    }

    /**
     * Aplica filtros de búsqueda de pacientes (nombre, documento, teléfono, email, estado, fechas).
     */
    private function aplicarFiltrosPacientes($query, Request $request): void
    {
        if ($request->filled('nombre')) {
            $nombre = $request->nombre;
            $query->where(function ($queryBuilder) use ($nombre) {
                $queryBuilder->where('nombres', 'LIKE', "%{$nombre}%")
                    ->orWhere('apellidos', 'LIKE', "%{$nombre}%");
            });
        }

        if ($request->filled('documento')) {
            $query->where('dni', 'LIKE', "%{$request->documento}%");
        }

        if ($request->filled('telefono')) {
            $telefono = $request->telefono;
            $query->where(function ($queryBuilder) use ($telefono) {
                $queryBuilder->where('telefono_responsable', 'LIKE', "%{$telefono}%")
                    ->orWhereHas('usuario', function ($subQuery) use ($telefono) {
                        $subQuery->where('telefono', 'LIKE', "%{$telefono}%");
                    });
            });
        }

        if ($request->filled('email')) {
            $query->whereHas('usuario', function ($queryBuilder) use ($request) {
                $queryBuilder->where('correo', 'LIKE', "%{$request->email}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
    }

    /**
     * Verifica si existe un conflicto de horario para un médico.
     */
    private function existeConflictoCita(int $idMedico, Carbon $fechaInicio, Carbon $fechaFin, ?int $excluirCitaId = null): bool
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
     * Anexa una nota a las notas existentes con separador de línea.
     */
    private function anexarNota(?string $notasExistentes, string $nuevaNota): string
    {
        return ($notasExistentes ? $notasExistentes . "\n" : '') . $nuevaNota;
    }
}
