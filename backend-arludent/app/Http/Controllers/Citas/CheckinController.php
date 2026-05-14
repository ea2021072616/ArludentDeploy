<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(
 *     name="Check-in Secretaria",
 *     description="Gestión de cola de atención y check-in de pacientes"
 * )
 */
class CheckinController extends Controller
{
    /** Minutos de tolerancia para considerar a un paciente como puntual. */
    private const PUNCTUALITY_TOLERANCE_MINUTES = 15;

    /** Días hacia atrás para consultas estadísticas por defecto. */
    private const DEFAULT_LOOKBACK_DAYS = 30;

    /** Estados que indican espera activa (para cálculo de tiempo de espera). */
    private const ESTADOS_ESPERA_ACTIVA = ['en_espera', 'siendo_atendido'];

    /** Transiciones de estado válidas en el flujo de atención. */
    private const TRANSICIONES_VALIDAS = [
        'confirmado'      => ['en_espera', 'no_asistio'],
        'en_espera'       => ['siendo_atendido', 'no_asistio'],
        'siendo_atendido' => ['completado'],
    ];

    /**
     * @OA\Get(
     *     path="/api/secretaria/checkin/cola",
     *     summary="Obtener cola de atención del día",
     *     description="Lista todos los pacientes del día organizados por estado de atención",
     *     tags={"Check-in Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="fecha",
     *         in="query",
     *         description="Fecha específica (Y-m-d). Si no se proporciona, usa fecha actual",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cola de atención obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Cola de atención obtenida exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="fecha",
     *                     type="string",
     *                     format="date",
     *                     example="2024-01-15"
     *                 ),
     *                 @OA\Property(
     *                     property="resumen",
     *                     type="object",
     *                     @OA\Property(property="total_citas", type="integer", example=15),
     *                     @OA\Property(property="confirmados", type="integer", example=3),
     *                     @OA\Property(property="en_espera", type="integer", example=2),
     *                     @OA\Property(property="siendo_atendido", type="integer", example=1),
     *                     @OA\Property(property="completados", type="integer", example=8),
     *                     @OA\Property(property="no_asistio", type="integer", example=1)
     *                 ),
     *                 @OA\Property(
     *                     property="cola",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="cita_id", type="integer", example=1),
     *                         @OA\Property(property="paciente_id", type="integer", example=1),
     *                         @OA\Property(property="paciente_nombre", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="paciente_telefono", type="string", example="987654321"),
     *                         @OA\Property(property="hora_cita", type="string", format="time", example="09:00:00"),
     *                         @OA\Property(property="medico_nombre", type="string", example="Dr. García"),
     *                         @OA\Property(property="estado_cita", type="string", example="confirmado"),
     *                         @OA\Property(property="hora_llegada", type="string", format="time", example="08:55:00"),
     *                         @OA\Property(property="tiempo_espera_minutos", type="integer", example=15),
     *                         @OA\Property(property="observaciones_checkin", type="string", example="Paciente llegó temprano")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getColaAtencion(Request $request): JsonResponse
    {
        try {
            $fecha = $request->fecha ?? now()->format('Y-m-d');

            $citas = Cita::with(['paciente', 'medico'])
                ->whereDate('fecha_hora_inicio', $fecha)
                ->orderBy('fecha_hora_inicio')
                ->get();

            $cola = $citas->map(fn ($cita) => $this->formatearCitaCola($cita));

            return $this->successResponse([
                'fecha'   => $fecha,
                'resumen' => $this->contarCitasPorEstado($citas),
                'cola'    => $cola,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener cola de atención: ' . $e->getMessage());
            return $this->errorResponse(
                'Error interno del servidor',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/secretaria/checkin/{citaId}/llegada",
     *     summary="Registrar llegada de paciente",
     *     description="Marca la llegada del paciente y cambia el estado a 'en_espera'",
     *     tags={"Check-in Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="citaId",
     *         in="path",
     *         description="ID de la cita",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="observaciones", type="string", example="Paciente llegó 10 minutos antes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Llegada registrada exitosamente"
     *     )
     * )
     */
    public function registrarLlegada(Request $request, $citaId): JsonResponse
    {
        try {
            $cita = Cita::with(['paciente', 'medico'])->findOrFail($citaId);

            if ($cita->estado_cita !== 'confirmado') {
                return $this->errorResponse(
                    'La cita debe estar en estado confirmado para registrar llegada',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $cita->update([
                'estado_cita'          => 'en_espera',
                'hora_llegada'         => now(),
                'observaciones_checkin' => $request->observaciones,
            ]);

            return $this->successResponse([
                'cita_id'        => $cita->id,
                'paciente'       => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
                'estado_anterior' => 'confirmado',
                'estado_actual'  => 'en_espera',
                'hora_llegada'   => $cita->hora_llegada->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al registrar llegada: ' . $e->getMessage());
            return $this->errorResponse(
                'Error interno del servidor',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/secretaria/checkin/{citaId}/estado",
     *     summary="Cambiar estado de cita en check-in",
     *     description="Permite cambiar el estado de la cita en el flujo de atención",
     *     tags={"Check-in Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="citaId",
     *         in="path",
     *         description="ID de la cita",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="nuevo_estado",
     *                 type="string",
     *                 enum={"en_espera", "siendo_atendido", "completado", "no_asistio"},
     *                 example="siendo_atendido"
     *             ),
     *             @OA\Property(property="observaciones", type="string", example="Paciente pasó a consulta")
     *         )
     *     )
     * )
     */
    public function cambiarEstado(Request $request, $citaId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nuevo_estado'  => 'required|in:en_espera,siendo_atendido,completado,no_asistio',
                'observaciones' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $cita = Cita::with(['paciente', 'medico'])->findOrFail($citaId);
            $estadoAnterior = $cita->estado_cita;

            if (!$this->esTransicionValida($estadoAnterior, $request->nuevo_estado)) {
                return $this->errorResponse(
                    "No se puede cambiar de '{$estadoAnterior}' a '{$request->nuevo_estado}'",
                    Response::HTTP_BAD_REQUEST
                );
            }

            $datosActualizacion = [
                'estado_cita'          => $request->nuevo_estado,
                'observaciones_checkin' => $request->observaciones,
            ];

            $this->agregarTimestampTransicion($datosActualizacion, $request->nuevo_estado, $cita);

            $cita->update($datosActualizacion);

            return $this->successResponse([
                'cita_id'         => $cita->id,
                'paciente'        => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
                'estado_anterior' => $estadoAnterior,
                'estado_actual'   => $request->nuevo_estado,
                'timestamp'       => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado: ' . $e->getMessage());
            return $this->errorResponse(
                'Error interno del servidor',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/checkin/estadisticas",
     *     summary="Estadísticas de check-in",
     *     description="Obtiene estadísticas de atención del día o período específico",
     *     tags={"Check-in Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         description="Fecha de inicio del período",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_fin",
     *         in="query",
     *         description="Fecha de fin del período",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     )
     * )
     */
    public function estadisticasCheckin(Request $request): JsonResponse
    {
        try {
            $fechaInicio = $request->fecha_inicio ?? now()->format('Y-m-d');
            $fechaFin = $request->fecha_fin ?? now()->format('Y-m-d');

            $citas = Cita::with(['paciente', 'medico'])
                ->whereBetween('fecha_hora_inicio', [
                    $fechaInicio . ' 00:00:00',
                    $fechaFin . ' 23:59:59',
                ])
                ->get();

            $estadisticas = [
                'total_citas'              => $citas->count(),
                'por_estado'               => $this->contarCitasPorEstado($citas),
                'tiempo_promedio_espera'    => $this->calcularTiempoPromedioEspera($citas),
                'puntualidad'              => $this->calcularPuntualidad($citas),
                'eficiencia_medicos'       => $this->calcularEficienciaMedicos($citas),
            ];

            return $this->successResponse([
                'periodo'      => ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
                'estadisticas' => $estadisticas,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas: ' . $e->getMessage());
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
     * Genera el conteo de citas agrupadas por estado.
     */
    private function contarCitasPorEstado($citas): array
    {
        return [
            'confirmados'     => $citas->where('estado_cita', 'confirmado')->count(),
            'en_espera'       => $citas->where('estado_cita', 'en_espera')->count(),
            'siendo_atendido' => $citas->where('estado_cita', 'siendo_atendido')->count(),
            'completados'     => $citas->where('estado_cita', 'completado')->count(),
            'no_asistio'      => $citas->where('estado_cita', 'no_asistio')->count(),
            'cancelados'      => $citas->where('estado_cita', 'cancelado')->count(),
        ];
    }

    /**
     * Formatea una cita para la vista de cola de atención.
     */
    private function formatearCitaCola(Cita $cita): array
    {
        $horaLlegada = $cita->hora_llegada ? Carbon::parse($cita->hora_llegada) : null;
        $horaCita = Carbon::parse($cita->fecha_hora_inicio);

        return [
            'cita_id'                => $cita->id,
            'paciente_id'            => $cita->paciente->id,
            'paciente_nombre'        => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
            'paciente_telefono'      => $cita->paciente->telefono,
            'paciente_dni'           => $cita->paciente->numero_documento,
            'hora_cita'              => $horaCita->format('H:i:s'),
            'medico_nombre'          => $cita->medico->nombres . ' ' . $cita->medico->apellidos,
            'estado_cita'            => $cita->estado_cita,
            'hora_llegada'           => $horaLlegada?->format('H:i:s'),
            'tiempo_espera_minutos'  => $this->calcularTiempoEsperaIndividual($horaLlegada, $cita->estado_cita),
            'observaciones_checkin'  => $cita->observaciones_checkin ?? null,
            'tratamiento'            => $cita->tratamiento->nombre ?? 'Consulta general',
        ];
    }

    /**
     * Calcula el tiempo de espera individual de una cita en minutos.
     */
    private function calcularTiempoEsperaIndividual(?Carbon $horaLlegada, string $estadoCita): ?int
    {
        if (!$horaLlegada || !in_array($estadoCita, self::ESTADOS_ESPERA_ACTIVA)) {
            return null;
        }

        return $horaLlegada->diffInMinutes(now());
    }

    /**
     * Determina si una transición de estado es válida según el flujo de atención.
     */
    private function esTransicionValida(string $estadoActual, string $nuevoEstado): bool
    {
        return isset(self::TRANSICIONES_VALIDAS[$estadoActual])
            && in_array($nuevoEstado, self::TRANSICIONES_VALIDAS[$estadoActual]);
    }

    /**
     * Agrega timestamps específicos según la transición de estado.
     */
    private function agregarTimestampTransicion(array &$datos, string $nuevoEstado, Cita $cita): void
    {
        match ($nuevoEstado) {
            'en_espera'       => $datos['hora_llegada'] = $cita->hora_llegada ?? now(),
            'siendo_atendido' => $datos['hora_inicio_atencion'] = now(),
            'completado'      => $datos['fecha_hora_fin'] = now(),
            default           => null,
        };
    }

    /**
     * Calcula el tiempo promedio de espera en minutos (llegada → inicio atención).
     */
    private function calcularTiempoPromedioEspera($citas): float
    {
        $citasConEspera = $citas->filter(
            fn ($cita) => $cita->hora_llegada && $cita->hora_inicio_atencion
        );

        if ($citasConEspera->isEmpty()) {
            return 0;
        }

        $tiempoTotalEspera = $citasConEspera->sum(
            fn ($cita) => Carbon::parse($cita->hora_llegada)
                ->diffInMinutes(Carbon::parse($cita->hora_inicio_atencion))
        );

        return round($tiempoTotalEspera / $citasConEspera->count(), 2);
    }

    /**
     * Calcula el porcentaje de pacientes que llegaron dentro de la tolerancia.
     */
    private function calcularPuntualidad($citas): float
    {
        $citasConLlegada = $citas->filter(fn ($cita) => $cita->hora_llegada);

        if ($citasConLlegada->isEmpty()) {
            return 0;
        }

        $citasPuntuales = $citasConLlegada->filter(function ($cita) {
            $horaLlegada = Carbon::parse($cita->hora_llegada);
            $horaCita = Carbon::parse($cita->fecha_hora_inicio);

            return $horaLlegada->diffInMinutes($horaCita, false) <= self::PUNCTUALITY_TOLERANCE_MINUTES;
        });

        return round(($citasPuntuales->count() / $citasConLlegada->count()) * 100, 2);
    }

    /**
     * Calcula la eficiencia de atención agrupada por médico.
     */
    private function calcularEficienciaMedicos($citas): array
    {
        return $citas->groupBy('medico_id')->map(function ($citasMedico, $medicoId) {
            $medico = $citasMedico->first()->medico;
            $completadas = $citasMedico->where('estado_cita', 'completado')->count();
            $total = $citasMedico->count();

            return [
                'medico_id'               => $medicoId,
                'medico_nombre'           => $medico->nombres . ' ' . $medico->apellidos,
                'total_citas'             => $total,
                'completadas'             => $completadas,
                'porcentaje_eficiencia'   => $total > 0 ? round(($completadas / $total) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }
}