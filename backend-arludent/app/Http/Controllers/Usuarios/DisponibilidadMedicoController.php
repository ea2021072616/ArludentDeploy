<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\DisponibilidadMedico;
use App\Models\LogActividad;
use App\Models\Medico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Disponibilidad Médica.
 *
 * Gestiona los horarios semanales, horarios específicos por fecha,
 * y bloqueos de disponibilidad de los médicos.
 * Los médicos de cabecera reciben horarios predefinidos automáticamente.
 */
class DisponibilidadMedicoController extends Controller
{
    /** Duración por defecto de una cita en minutos. */
    private const DEFAULT_APPOINTMENT_DURATION_MINUTES = 60;

    /** Primer día laboral de la semana (Lunes). */
    private const FIRST_WORKDAY = 1;

    /** Último día laboral de la semana (Sábado). */
    private const LAST_WORKDAY = 6;

    /** Tipos válidos de médico de cabecera. */
    private const TIPOS_CABECERA = ['cabecera_manana', 'cabecera_tarde'];

    /** Horarios predefinidos por tipo de médico de cabecera. */
    private const HORARIOS_PREDEFINIDOS = [
        'cabecera_manana' => ['hora_inicio' => '09:00', 'hora_fin' => '13:00'],
        'cabecera_tarde'  => ['hora_inicio' => '13:00', 'hora_fin' => '20:00'],
    ];

    /** Nombres de los días de la semana en español. */
    private const DIAS_SEMANA = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    /** Reglas de validación comunes para disponibilidad. */
    private const DISPONIBILIDAD_VALIDATION_RULES = [
        'tipo'         => 'required|in:horario,bloqueo',
        'dia_semana'   => 'nullable|integer|min:0|max:6',
        'fecha_inicio' => 'nullable|date',
        'fecha_fin'    => 'nullable|date',
        'hora_inicio'  => 'nullable|date_format:H:i',
        'hora_fin'     => 'nullable|date_format:H:i|after:hora_inicio',
        'motivo'       => 'nullable|string|max:500',
    ];

    /**
     * Obtener disponibilidad del médico autenticado con filtros opcionales.
     */
    public function index(Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        if ($this->esMedicoCabecera($medico)) {
            $this->verificarYCrearHorariosCabecera($medico);
        }

        $query = DisponibilidadMedico::where('id_medico', $medico->id_medico)
            ->orderBy('tipo', 'asc')
            ->orderBy('dia_semana', 'asc')
            ->orderBy('fecha_inicio', 'asc');

        $this->aplicarFiltrosConsulta($query, $request);

        $disponibilidades = $query->get();

        $disponibilidadesFormateadas = $disponibilidades->map(
            fn ($disponibilidad) => $this->formatearDisponibilidad($disponibilidad, $medico)
        );

        return $this->successResponse([
            'disponibilidades'     => $disponibilidadesFormateadas,
            'tipo_medico'          => $medico->tipo_medico,
            'horarios_predefinidos' => $this->getHorariosPredefinidos($medico->tipo_medico),
            'es_cabecera'          => $this->esMedicoCabecera($medico),
            'total'                => $disponibilidadesFormateadas->count(),
        ]);
    }

    /**
     * Crear nueva disponibilidad (horario o bloqueo).
     */
    public function store(Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $validator = Validator::make($request->all(), self::DISPONIBILIDAD_VALIDATION_RULES);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $errorFechas = $this->validarFechasHorario($request->all());
        if ($errorFechas) {
            return $this->errorResponse($errorFechas, Response::HTTP_BAD_REQUEST);
        }

        $horaInicio = $request->hora_inicio;
        $horaFin = $request->hora_fin;

        if ($this->esMedicoCabecera($medico) && $request->tipo === 'horario') {
            if ($request->dia_semana === null) {
                return $this->errorResponse(
                    'Los médicos de cabecera deben especificar el día de la semana para los horarios.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $horariosPredefinidos = $this->getHorariosPredefinidos($medico->tipo_medico);
            if ($horariosPredefinidos) {
                $horaInicio = $horariosPredefinidos['hora_inicio'];
                $horaFin = $horariosPredefinidos['hora_fin'];
            }
        }

        $datosValidacion = array_merge($request->all(), [
            'hora_inicio' => $horaInicio,
            'hora_fin'    => $horaFin,
        ]);

        if (!$this->validarSinConflictos($medico->id_medico, $datosValidacion)) {
            return $this->errorResponse(
                'El horario especificado entra en conflicto con otra disponibilidad existente.',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $disponibilidad = DisponibilidadMedico::create([
                'id_medico'   => $medico->id_medico,
                'tipo'        => $request->tipo,
                'dia_semana'  => $request->dia_semana,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'   => $request->fecha_fin,
                'hora_inicio' => $horaInicio,
                'hora_fin'    => $horaFin,
                'motivo'      => $request->motivo,
            ]);

            $this->registrarActividad(
                'crear_disponibilidad',
                "Disponibilidad {$request->tipo} creada",
                $request->ip(),
                $disponibilidad->id_disp
            );

            return $this->successResponse(
                ['disponibilidad' => $disponibilidad],
                'Disponibilidad creada exitosamente.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear la disponibilidad.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Actualizar una disponibilidad existente.
     */
    public function update($id, Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $disponibilidad = $this->buscarDisponibilidadDelMedico($id, $medico->id_medico);

        if (!$disponibilidad) {
            return $this->errorResponse('Disponibilidad no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if ($this->esHorarioCabeceraProtegido($disponibilidad, $medico)) {
            return $this->errorResponse(
                'No tiene permisos para modificar esta disponibilidad.',
                Response::HTTP_FORBIDDEN
            );
        }

        $reglasUpdate = self::DISPONIBILIDAD_VALIDATION_RULES;
        $reglasUpdate['tipo'] = 'sometimes|required|in:horario,bloqueo';

        $validator = Validator::make($request->all(), $reglasUpdate);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $datosCombinados = array_merge($disponibilidad->toArray(), $request->all());

        $errorFechas = $this->validarFechasHorario($datosCombinados);
        if ($errorFechas) {
            return $this->errorResponse($errorFechas, Response::HTTP_BAD_REQUEST);
        }

        if (!$this->validarSinConflictos($medico->id_medico, $datosCombinados, $id)) {
            return $this->errorResponse(
                'El horario especificado entra en conflicto con otra disponibilidad existente.',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $disponibilidad->update($request->only([
                'tipo', 'dia_semana', 'fecha_inicio', 'fecha_fin',
                'hora_inicio', 'hora_fin', 'motivo',
            ]));

            $this->registrarActividad(
                'actualizar_disponibilidad',
                "Disponibilidad {$disponibilidad->tipo} actualizada",
                $request->ip(),
                $disponibilidad->id_disp
            );

            return $this->successResponse(
                ['disponibilidad' => $disponibilidad->fresh()],
                'Disponibilidad actualizada exitosamente.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar la disponibilidad.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Eliminar una disponibilidad.
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        $disponibilidad = $this->buscarDisponibilidadDelMedico($id, $medico->id_medico);

        if (!$disponibilidad) {
            return $this->errorResponse('Disponibilidad no encontrada.', Response::HTTP_NOT_FOUND);
        }

        if ($this->esHorarioCabeceraProtegido($disponibilidad, $medico)) {
            return $this->errorResponse(
                'No tiene permisos para eliminar esta disponibilidad.',
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $tipoDisponibilidad = $disponibilidad->tipo;
            $disponibilidad->delete();

            $this->registrarActividad(
                'eliminar_disponibilidad',
                "Disponibilidad {$tipoDisponibilidad} eliminada",
                $request->ip(),
                $id
            );

            return $this->successResponse(null, 'Disponibilidad eliminada exitosamente.');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar la disponibilidad.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Obtener horarios disponibles para agendar citas con un médico en una fecha.
     */
    public function horariosDisponibles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_medico'        => 'required|exists:medicos,id_medico',
            'fecha'            => 'required|date|after_or_equal:today',
            'duracion_minutos' => 'nullable|integer|min:15|max:480',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $fecha = Carbon::parse($request->fecha);
        $diaSemana = $fecha->dayOfWeek;
        $duracionMinutos = $request->duracion_minutos ?? self::DEFAULT_APPOINTMENT_DURATION_MINUTES;

        $disponibilidades = DisponibilidadMedico::where('id_medico', $request->id_medico)
            ->where(function ($queryBuilder) use ($fecha, $diaSemana) {
                $queryBuilder->where(function ($subQuery) use ($fecha) {
                    $subQuery->where('fecha_inicio', '<=', $fecha)
                        ->where('fecha_fin', '>=', $fecha);
                })->orWhere(function ($subQuery) use ($diaSemana) {
                    $subQuery->where('dia_semana', $diaSemana)
                        ->whereNull('fecha_inicio')
                        ->whereNull('fecha_fin');
                });
            })
            ->where('tipo', 'horario')
            ->get();

        $slotsDisponibles = $this->generarSlotsHorarios($disponibilidades, $duracionMinutos);

        return $this->successResponse([
            'fecha'            => $fecha->toDateString(),
            'dia_semana'       => $diaSemana,
            'dia_semana_texto' => self::DIAS_SEMANA[$diaSemana] ?? null,
            'horarios'         => collect($slotsDisponibles)->unique('hora_inicio')->values(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene el perfil de médico del usuario autenticado.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
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
     * Registra una entrada en el log de actividad del módulo de disponibilidad.
     */
    private function registrarActividad(
        string $accion,
        string $descripcion,
        ?string $ipUsuario,
        mixed $registroAfectado = null
    ): void {
        /** @var User $usuario */
        $usuario = Auth::user();

        LogActividad::create([
            'id_usuario'        => $usuario->id_usuario,
            'accion'            => $accion,
            'modulo_afectado'   => 'disponibilidad_medico',
            'registro_afectado' => $registroAfectado,
            'descripcion'       => $descripcion,
            'ip_usuario'        => $ipUsuario,
        ]);
    }

    /**
     * Busca una disponibilidad que pertenezca al médico dado.
     */
    private function buscarDisponibilidadDelMedico(int $idDisp, int $idMedico): ?DisponibilidadMedico
    {
        return DisponibilidadMedico::where('id_disp', $idDisp)
            ->where('id_medico', $idMedico)
            ->first();
    }

    /**
     * Valida las reglas de negocio sobre fechas y días de semana.
     *
     * @return string|null Mensaje de error si hay violación, null si es válido.
     */
    private function validarFechasHorario(array $datos): ?string
    {
        $diaSemana = $datos['dia_semana'] ?? null;
        $fechaInicio = $datos['fecha_inicio'] ?? null;
        $fechaFin = $datos['fecha_fin'] ?? null;
        $tipo = $datos['tipo'] ?? null;

        if ($diaSemana !== null) {
            if ($fechaInicio || $fechaFin) {
                return 'Para horarios semanales, no especifiques fechas. Solo selecciona el día de la semana.';
            }
            return null;
        }

        if ($tipo === 'horario' && (!$fechaInicio || !$fechaFin)) {
            return 'Para horarios específicos, debes especificar fecha de inicio y fin.';
        }

        if ($fechaInicio && Carbon::parse($fechaInicio)->isPast()) {
            return 'La fecha de inicio debe ser futura.';
        }

        if ($fechaInicio && $fechaFin && Carbon::parse($fechaFin)->lessThan(Carbon::parse($fechaInicio))) {
            return 'La fecha de fin no puede ser anterior a la fecha de inicio.';
        }

        return null;
    }

    /**
     * Aplica filtros opcionales de tipo, día, y rango de fechas a la consulta.
     */
    private function aplicarFiltrosConsulta($query, Request $request): void
    {
        if ($request->query('tipo')) {
            $query->where('tipo', $request->query('tipo'));
        }

        if ($request->query('dia_semana') !== null) {
            $query->where('dia_semana', $request->query('dia_semana'));
        }

        if ($request->query('desde')) {
            $fechaDesde = $request->query('desde');
            $query->where(function ($queryBuilder) use ($fechaDesde) {
                $queryBuilder->where('fecha_inicio', '>=', $fechaDesde)
                    ->orWhereNull('fecha_inicio');
            });
        }

        if ($request->query('hasta')) {
            $fechaHasta = $request->query('hasta');
            $query->where(function ($queryBuilder) use ($fechaHasta) {
                $queryBuilder->where('fecha_fin', '<=', $fechaHasta)
                    ->orWhereNull('fecha_fin');
            });
        }
    }

    /**
     * Formatea una entrada de disponibilidad para la respuesta JSON.
     */
    private function formatearDisponibilidad(DisponibilidadMedico $disponibilidad, Medico $medico): array
    {
        $esProtegido = $this->esHorarioCabeceraProtegido($disponibilidad, $medico);

        return [
            'id_disp'              => $disponibilidad->id_disp,
            'tipo'                 => $disponibilidad->tipo,
            'dia_semana'           => $disponibilidad->dia_semana,
            'dia_semana_texto'     => self::DIAS_SEMANA[$disponibilidad->dia_semana] ?? null,
            'fecha_inicio'         => $disponibilidad->fecha_inicio?->format('Y-m-d'),
            'fecha_fin'            => $disponibilidad->fecha_fin?->format('Y-m-d'),
            'hora_inicio'          => $disponibilidad->hora_inicio,
            'hora_fin'             => $disponibilidad->hora_fin,
            'motivo'               => $disponibilidad->motivo,
            'es_horario_cabecera'  => $esProtegido,
            'puede_eliminar'       => !$esProtegido,
            'created_at'           => $disponibilidad->created_at,
            'updated_at'           => $disponibilidad->updated_at,
        ];
    }

    /**
     * Genera slots de tiempo disponibles a partir de las disponibilidades.
     */
    private function generarSlotsHorarios($disponibilidades, int $duracionMinutos): array
    {
        $slots = [];

        foreach ($disponibilidades as $disponibilidad) {
            if (!$disponibilidad->hora_inicio || !$disponibilidad->hora_fin) {
                continue;
            }

            $horaInicio = Carbon::createFromTimeString($disponibilidad->hora_inicio);
            $horaFin = Carbon::createFromTimeString($disponibilidad->hora_fin);

            $slotActual = clone $horaInicio;
            while ($slotActual->copy()->addMinutes($duracionMinutos) <= $horaFin) {
                $slots[] = [
                    'hora_inicio' => $slotActual->format('H:i'),
                    'hora_fin'    => $slotActual->copy()->addMinutes($duracionMinutos)->format('H:i'),
                    'disponible'  => true,
                ];
                $slotActual->addMinutes($duracionMinutos);
            }
        }

        return $slots;
    }

    /**
     * Determina si un médico es de tipo cabecera (mañana o tarde).
     */
    private function esMedicoCabecera(Medico $medico): bool
    {
        return in_array($medico->tipo_medico, self::TIPOS_CABECERA);
    }

    /**
     * Determina si una disponibilidad es un horario semanal protegido de cabecera.
     *
     * Los horarios semanales predefinidos de médicos de cabecera no pueden
     * ser modificados ni eliminados por el médico.
     */
    private function esHorarioCabeceraProtegido(DisponibilidadMedico $disponibilidad, Medico $medico): bool
    {
        return $this->esMedicoCabecera($medico)
            && $disponibilidad->tipo === 'horario'
            && $disponibilidad->dia_semana !== null;
    }

    /**
     * Obtiene los horarios predefinidos según el tipo de médico de cabecera.
     */
    private function getHorariosPredefinidos(string $tipoMedico): ?array
    {
        return self::HORARIOS_PREDEFINIDOS[$tipoMedico] ?? null;
    }

    /**
     * Valida que no existan conflictos de horario con disponibilidades existentes.
     */
    private function validarSinConflictos(int $idMedico, array $datos, ?int $excludeId = null): bool
    {
        $query = DisponibilidadMedico::where('id_medico', $idMedico);

        if ($excludeId) {
            $query->where('id_disp', '!=', $excludeId);
        }

        if (isset($datos['dia_semana']) && $datos['dia_semana'] !== null) {
            $query->where('dia_semana', $datos['dia_semana']);

            if (isset($datos['hora_inicio']) && isset($datos['hora_fin']) && $datos['tipo'] === 'horario') {
                $query->where('tipo', 'horario');
                $this->aplicarFiltroSolapamientoHoras($query, $datos);
            } elseif ($datos['tipo'] === 'bloqueo') {
                $query->where('tipo', 'bloqueo');
            }
        }

        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin'])) {
            $query->where(function ($queryBuilder) use ($datos) {
                $queryBuilder->whereBetween('fecha_inicio', [$datos['fecha_inicio'], $datos['fecha_fin']])
                    ->orWhereBetween('fecha_fin', [$datos['fecha_inicio'], $datos['fecha_fin']])
                    ->orWhere(function ($subQuery) use ($datos) {
                        $subQuery->where('fecha_inicio', '<=', $datos['fecha_inicio'])
                            ->where('fecha_fin', '>=', $datos['fecha_fin']);
                    });
            });

            if (isset($datos['hora_inicio']) && isset($datos['hora_fin']) && $datos['tipo'] === 'horario') {
                $query->where('tipo', 'horario');
                $this->aplicarFiltroSolapamientoHoras($query, $datos);
            }
        }

        return $query->count() === 0;
    }

    /**
     * Aplica un filtro de solapamiento de horas al query builder.
     */
    private function aplicarFiltroSolapamientoHoras($query, array $datos): void
    {
        $query->where(function ($queryBuilder) use ($datos) {
            $queryBuilder->whereBetween('hora_inicio', [$datos['hora_inicio'], $datos['hora_fin']])
                ->orWhereBetween('hora_fin', [$datos['hora_inicio'], $datos['hora_fin']])
                ->orWhere(function ($subQuery) use ($datos) {
                    $subQuery->where('hora_inicio', '<=', $datos['hora_inicio'])
                        ->where('hora_fin', '>=', $datos['hora_fin']);
                });
        });
    }

    /**
     * Crea horarios semanales predefinidos (Lun-Sáb) si el médico de cabecera aún no tiene.
     */
    private function verificarYCrearHorariosCabecera(Medico $medico): void
    {
        $tieneHorarios = DisponibilidadMedico::where('id_medico', $medico->id_medico)
            ->where('tipo', 'horario')
            ->whereNotNull('dia_semana')
            ->exists();

        if ($tieneHorarios) {
            return;
        }

        $horarios = $this->getHorariosPredefinidos($medico->tipo_medico);

        if (!$horarios) {
            return;
        }

        $turnoDescripcion = $medico->tipo_medico === 'cabecera_manana' ? 'mañana' : 'tarde';

        for ($dia = self::FIRST_WORKDAY; $dia <= self::LAST_WORKDAY; $dia++) {
            DisponibilidadMedico::create([
                'id_medico'   => $medico->id_medico,
                'tipo'        => 'horario',
                'dia_semana'  => $dia,
                'hora_inicio' => $horarios['hora_inicio'],
                'hora_fin'    => $horarios['hora_fin'],
                'fecha_inicio' => null,
                'fecha_fin'   => null,
                'motivo'      => "Horario predefinido de {$turnoDescripcion}",
            ]);
        }
    }
}
