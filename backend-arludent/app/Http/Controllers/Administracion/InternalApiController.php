<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Cita;
use App\Models\Medico;
use App\Models\HistorialClinico;
use App\Models\InteraccionIA;
use App\Models\User;
use App\Models\Odontograma;
use App\Models\Pago;
use App\Models\TratamientoHistorial;
use App\Models\LogActividad;
use App\Services\VerificacionPacienteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controlador para endpoints internos del microservicio de IA
 *
 * Estos endpoints NO requieren autenticación JWT ya que son llamados
 * internamente por el microservicio de IA usando API Key
 */
class InternalApiController extends Controller
{
    // ========================================
    // PACIENTES
    // ========================================

    /**
     * Obtiene información de un paciente por ID
     */
    public function getPaciente($id): JsonResponse
    {
        try {
            $paciente = Paciente::with(['usuario'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_paciente' => $paciente->id_paciente,
                    'nombres' => $paciente->nombres,
                    'apellidos' => $paciente->apellidos,
                    'dni' => $paciente->dni,
                    'fecha_nacimiento' => $paciente->fecha_nacimiento?->format('Y-m-d'),
                    'edad' => $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->age : null,
                    'sexo' => $paciente->sexo,
                    'telefono' => $paciente->telefono,
                    'correo' => $paciente->correo,
                    'domicilio' => $paciente->domicilio,
                    'alergias' => $paciente->alergias,
                    'grupo_sanguineo' => $paciente->grupo_sanguineo,
                    'estado' => $paciente->estado
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Busca pacientes con filtros
     */
    public function getPacientes(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $search = $request->input('search');

            $query = Paciente::with(['usuario']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                      ->orWhere('apellidos', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%");
                });
            }

            $pacientes = $query->limit($limit)->get()->map(function ($paciente) {
                return [
                    'id_paciente' => $paciente->id_paciente,
                    'nombres' => $paciente->nombres,
                    'apellidos' => $paciente->apellidos,
                    'dni' => $paciente->dni,
                    'edad' => $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->age : null,
                    'telefono' => $paciente->telefono,
                    'correo' => $paciente->correo
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $pacientes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar pacientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca un paciente por DNI
     */
    public function getPacientePorDni($dni): JsonResponse
    {
        try {
            $paciente = Paciente::with(['usuario'])
                ->where('dni', $dni)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'id_paciente' => $paciente->id_paciente,
                    'nombres' => $paciente->nombres,
                    'apellidos' => $paciente->apellidos,
                    'dni' => $paciente->dni,
                    'fecha_nacimiento' => $paciente->fecha_nacimiento?->format('Y-m-d'),
                    'edad' => $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->age : null,
                    'sexo' => $paciente->sexo,
                    'telefono' => $paciente->telefono,
                    'correo' => $paciente->correo,
                    'alergias' => $paciente->alergias,
                    'grupo_sanguineo' => $paciente->grupo_sanguineo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // ========================================
    // CITAS
    // ========================================

    /**
     * Obtiene información de una cita por ID
     */
    public function getCita($id): JsonResponse
    {
        try {
            $cita = Cita::with(['paciente', 'medico.usuario'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_cita' => $cita->id_cita,
                    'fecha_hora_inicio' => $cita->fecha_hora_inicio->format('Y-m-d H:i:s'),
                    'fecha_hora_fin' => $cita->fecha_hora_fin->format('Y-m-d H:i:s'),
                    'motivo' => $cita->motivo,
                    'estado' => $cita->estado,
                    'notas' => $cita->notas,
                    'paciente' => [
                        'id_paciente' => $cita->paciente->id_paciente,
                        'nombres' => $cita->paciente->nombres,
                        'apellidos' => $cita->paciente->apellidos
                    ],
                    'medico' => [
                        'id_medico' => $cita->medico->id_medico,
                        'nombres' => $cita->medico->nombres,
                        'apellidos' => $cita->medico->apellidos,
                        'especialidad' => $cita->medico->especialidad
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Obtiene las citas de un paciente
     */
    public function getCitasPaciente($id, Request $request): JsonResponse
    {
        try {
            $estado = $request->input('estado');

            $query = Cita::with(['medico.usuario'])
                ->where('id_paciente', $id);

            if ($estado) {
                $query->where('estado', $estado);
            }

            $citas = $query->orderBy('fecha_hora_inicio', 'desc')->get()->map(function ($cita) {
                return [
                    'id_cita' => $cita->id_cita,
                    'fecha_hora_inicio' => $cita->fecha_hora_inicio->format('Y-m-d H:i:s'),
                    'fecha_hora_fin' => $cita->fecha_hora_fin->format('Y-m-d H:i:s'),
                    'motivo' => $cita->motivo,
                    'estado' => $cita->estado,
                    'medico' => [
                        'id_medico' => $cita->medico->id_medico,
                        'nombres' => $cita->medico->nombres,
                        'apellidos' => $cita->medico->apellidos,
                        'especialidad' => $cita->medico->especialidad
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $citas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener citas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene las citas de un médico
     */
    public function getCitasMedico($id, Request $request): JsonResponse
    {
        try {
            $fecha = $request->input('fecha');

            $query = Cita::with(['paciente'])
                ->where('id_medico', $id);

            if ($fecha) {
                $query->whereDate('fecha_hora_inicio', $fecha);
            }

            $citas = $query->orderBy('fecha_hora_inicio', 'asc')->get()->map(function ($cita) {
                return [
                    'id_cita' => $cita->id_cita,
                    'fecha_hora_inicio' => $cita->fecha_hora_inicio->format('Y-m-d H:i:s'),
                    'fecha_hora_fin' => $cita->fecha_hora_fin->format('Y-m-d H:i:s'),
                    'motivo' => $cita->motivo,
                    'estado' => $cita->estado,
                    'paciente' => [
                        'id_paciente' => $cita->paciente->id_paciente,
                        'nombres' => $cita->paciente->nombres,
                        'apellidos' => $cita->paciente->apellidos
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $citas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener citas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // HISTORIAL CLÍNICO
    // ========================================

    /**
     * Obtiene el historial clínico de un paciente
     */
    public function getHistorialPaciente($id): JsonResponse
    {
        try {
            $historiales = HistorialClinico::with(['medico.usuario', 'detalles'])
                ->where('id_paciente', $id)
                ->orderBy('fecha_atencion', 'desc')
                ->get()
                ->map(function ($historial) {
                    return [
                        'id_historial' => $historial->id_historial,
                        'fecha_atencion' => $historial->fecha_atencion->format('Y-m-d'),
                        'diagnostico' => $historial->diagnostico,
                        'tratamiento_realizado' => $historial->tratamiento_realizado,
                        'observaciones' => $historial->observaciones,
                        'medico' => [
                            'nombres' => $historial->medico->nombres,
                            'apellidos' => $historial->medico->apellidos,
                            'especialidad' => $historial->medico->especialidad
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $historiales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene un resumen del historial clínico
     */
    public function getHistorialResumen($id): JsonResponse
    {
        try {
            $paciente = Paciente::findOrFail($id);

            $totalConsultas = HistorialClinico::where('id_paciente', $id)->count();
            $ultimaConsulta = HistorialClinico::where('id_paciente', $id)
                ->orderBy('fecha_atencion', 'desc')
                ->first();

            $tratamientosActivos = DB::table('seguimiento_tratamiento')
                ->join('historiales_clinico', 'seguimiento_tratamiento.id_historial', '=', 'historiales_clinico.id_historial')
                ->where('historiales_clinico.id_paciente', $id)
                ->where('seguimiento_tratamiento.estado', 'en_proceso')
                ->count();

            $diagnosticosRecientes = HistorialClinico::where('id_paciente', $id)
                ->whereNotNull('diagnostico')
                ->orderBy('fecha_atencion', 'desc')
                ->limit(3)
                ->pluck('diagnostico')
                ->implode(', ');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_consultas' => $totalConsultas,
                    'ultima_consulta' => $ultimaConsulta ? $ultimaConsulta->fecha_atencion->format('Y-m-d') : null,
                    'tratamientos_activos' => $tratamientosActivos,
                    'alergias' => $paciente->alergias ?: 'Ninguna',
                    'diagnosticos_recientes' => $diagnosticosRecientes ?: 'No hay diagnósticos recientes',
                    'notas_importantes' => $ultimaConsulta ? $ultimaConsulta->observaciones : 'Sin notas especiales'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // MÉDICOS
    // ========================================

    /**
     * Obtiene información de un médico
     */
    public function getMedico($id): JsonResponse
    {
        try {
            $medico = Medico::with(['usuario'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_medico' => $medico->id_medico,
                    'nombres' => $medico->nombres,
                    'apellidos' => $medico->apellidos,
                    'especialidad' => $medico->especialidad,
                    'colegiatura' => $medico->colegiatura,
                    'telefono' => $medico->usuario->telefono,
                    'correo' => $medico->usuario->correo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Médico no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Lista todos los médicos
     */
    public function getMedicos(Request $request): JsonResponse
    {
        try {
            $especialidad = $request->input('especialidad');

            $query = Medico::with(['usuario'])
                ->where('estado_disponibilidad', 'disponible');

            if ($especialidad) {
                $query->where('especialidad', 'like', "%{$especialidad}%");
            }

            $medicos = $query->get()->map(function ($medico) {
                return [
                    'id_medico' => $medico->id_medico,
                    'nombres' => $medico->nombres,
                    'apellidos' => $medico->apellidos,
                    'especialidad' => $medico->especialidad,
                    'colegiatura' => $medico->colegiatura
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $medicos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener médicos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene disponibilidad de un médico
     */
    public function getDisponibilidad($id, Request $request): JsonResponse
    {
        try {
            $fecha = $request->input('fecha', now()->format('Y-m-d'));

            // Obtener citas del médico en esa fecha
            $citasOcupadas = Cita::where('id_medico', $id)
                ->whereDate('fecha_hora_inicio', $fecha)
                ->whereIn('estado', ['pendiente', 'confirmado'])
                ->orderBy('fecha_hora_inicio')
                ->get(['fecha_hora_inicio', 'fecha_hora_fin']);

            // Horarios de trabajo típicos (8:00 AM - 6:00 PM)
            $horariosDisponibles = [];
            $horaInicio = 8;
            $horaFin = 18;

            for ($hora = $horaInicio; $hora < $horaFin; $hora++) {
                $horario = sprintf("%02d:00", $hora);
                $horarioCompleto = "{$fecha} {$horario}:00";

                $slotInicio = $horarioCompleto;
                $slotFin = date('Y-m-d H:i:s', strtotime("$horarioCompleto + 1 hour"));

                // Verificar si está ocupado
                $ocupado = $citasOcupadas->contains(function ($cita) use ($slotInicio, $slotFin) {
                    $citaInicio = $cita->fecha_hora_inicio->format('Y-m-d H:i:s');
                    $citaFin = $cita->fecha_hora_fin->format('Y-m-d H:i:s');
                    
                    // Slot overlaps with Cita if: SlotInicio < CitaFin AND SlotFin > CitaInicio
                    return $slotInicio < $citaFin && $slotFin > $citaInicio;
                });

                if (!$ocupado) {
                    $horariosDisponibles[] = $horario;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'fecha' => $fecha,
                    'horarios_disponibles' => $horariosDisponibles
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener disponibilidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // HEALTH CHECK
    // ========================================

    /**
     * Health check para el microservicio
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 'ok',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    // ========================================
    // AGENDAMIENTO DE CITAS
    // ========================================

    /**
     * Determina el tipo de usuario y retorna información relevante
     */
    public function determinarTipoUsuario($id_usuario): JsonResponse
    {
        try {
            $usuario = User::with(['paciente', 'roles'])->findOrFail($id_usuario);
            $paciente = $usuario->paciente;

            $esPacienteActivo = $paciente && $paciente->estado === 'activo';
            $esExterno = !$esPacienteActivo;

            // Obtener último médico si es paciente activo
            $ultimoMedico = null;
            if ($esPacienteActivo) {
                $ultimaCita = Cita::where('id_paciente', $paciente->id_paciente)
                    ->whereIn('estado', ['completado', 'confirmado'])
                    ->orderBy('fecha_hora_inicio', 'desc')
                    ->first();

                if ($ultimaCita) {
                    $ultimoMedico = [
                        'id_medico' => $ultimaCita->id_medico,
                        'nombres' => $ultimaCita->medico->nombres,
                        'apellidos' => $ultimaCita->medico->apellidos,
                        'especialidad' => $ultimaCita->medico->especialidad
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'es_paciente_activo' => $esPacienteActivo,
                    'es_externo' => $esExterno,
                    'id_paciente' => $paciente ? $paciente->id_paciente : null,
                    'dni' => $paciente ? $paciente->dni : null,
                    'nombre_completo' => $paciente
                        ? "{$paciente->nombres} {$paciente->apellidos}"
                        : $usuario->nombre_completo,
                    'ultimo_medico' => $ultimoMedico
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al determinar tipo de usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sugiere horarios alternativos para un médico
     */
    public function sugerirHorarios(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_medico' => 'required|exists:medicos,id_medico',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'duracion_minutos' => 'nullable|integer|min:30|max:120',
                'limite' => 'nullable|integer|min:1|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $idMedico = $request->id_medico;
            $fechaInicio = $request->fecha_inicio;
            $fechaFin = $request->input('fecha_fin', date('Y-m-d', strtotime($fechaInicio . ' +7 days')));
            $duracion = $request->input('duracion_minutos', 60);
            $limite = $request->input('limite', 3);

            $horariosDisponibles = [];
            $fechaActual = new \DateTime($fechaInicio);
            $fechaLimite = new \DateTime($fechaFin);

            while ($fechaActual <= $fechaLimite && count($horariosDisponibles) < $limite) {
                $fecha = $fechaActual->format('Y-m-d');

                // Saltar fines de semana
                if ($fechaActual->format('N') >= 6) {
                    $fechaActual->modify('+1 day');
                    continue;
                }

                // Obtener citas ocupadas del día
                $citasOcupadas = Cita::where('id_medico', $idMedico)
                    ->whereDate('fecha_hora_inicio', $fecha)
                    ->whereIn('estado', ['pendiente', 'confirmado'])
                    ->orderBy('fecha_hora_inicio')
                    ->get(['fecha_hora_inicio', 'fecha_hora_fin']);

                // Horario de trabajo: 8:00 AM - 6:00 PM
                for ($hora = 8; $hora < 18; $hora++) {
                    if (count($horariosDisponibles) >= $limite) break;

                    $horarioInicio = sprintf("%s %02d:00:00", $fecha, $hora);
                    $horarioFin = date('Y-m-d H:i:s', strtotime($horarioInicio . " +{$duracion} minutes"));

                    // Verificar si está libre
                    $ocupado = false;
                    foreach ($citasOcupadas as $cita) {
                        $citaInicio = $cita->fecha_hora_inicio->format('Y-m-d H:i:s');
                        $citaFin = $cita->fecha_hora_fin->format('Y-m-d H:i:s');

                        if (
                            ($horarioInicio >= $citaInicio && $horarioInicio < $citaFin) ||
                            ($horarioFin > $citaInicio && $horarioFin <= $citaFin) ||
                            ($horarioInicio <= $citaInicio && $horarioFin >= $citaFin)
                        ) {
                            $ocupado = true;
                            break;
                        }
                    }

                    if (!$ocupado) {
                        $horariosDisponibles[] = [
                            'fecha_hora_inicio' => $horarioInicio,
                            'fecha_hora_fin' => $horarioFin,
                            'fecha' => $fecha,
                            'hora' => sprintf("%02d:00", $hora),
                            'dia_semana' => $this->getNombreDia($fechaActual->format('N'))
                        ];
                    }
                }

                $fechaActual->modify('+1 day');
            }

            return response()->json([
                'success' => true,
                'data' => $horariosDisponibles,
                'total' => count($horariosDisponibles)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sugerir horarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra una nueva cita
     */
    public function registrarCita(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_usuario' => 'required|exists:usuarios,id_usuario',
                'id_medico' => 'required|exists:medicos,id_medico',
                'fecha_hora_inicio' => 'required|date|after:now',
                'fecha_hora_fin' => 'required|date|after:fecha_hora_inicio',
                'motivo' => 'nullable|string|max:500',
                'tipo_cita' => 'nullable|in:primera_vez,seguimiento,especialidad',
                'notas' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = User::with('paciente')->findOrFail($request->id_usuario);
            $idPaciente = $usuario->paciente ? $usuario->paciente->id_paciente : null;
            $idUsuarioExterno = $idPaciente ? null : $usuario->id_usuario;

            // Verificar disponibilidad del horario
            $conflicto = Cita::where('id_medico', $request->id_medico)
                ->whereIn('estado', ['pendiente', 'confirmado'])
                ->where(function ($query) use ($request) {
                    $query->whereBetween('fecha_hora_inicio', [$request->fecha_hora_inicio, $request->fecha_hora_fin])
                        ->orWhereBetween('fecha_hora_fin', [$request->fecha_hora_inicio, $request->fecha_hora_fin])
                        ->orWhere(function ($q) use ($request) {
                            $q->where('fecha_hora_inicio', '<=', $request->fecha_hora_inicio)
                              ->where('fecha_hora_fin', '>=', $request->fecha_hora_fin);
                        });
                })
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'El horario solicitado no está disponible'
                ], 409);
            }

            // Crear la cita
            $cita = Cita::create([
                'id_paciente' => $idPaciente,
                'id_usuario_externo' => $idUsuarioExterno,
                'id_medico' => $request->id_medico,
                'fecha_hora_inicio' => $request->fecha_hora_inicio,
                'fecha_hora_fin' => $request->fecha_hora_fin,
                'motivo' => $request->motivo ?? 'Consulta general',
                'estado' => 'pendiente',
                'creado_por' => $request->id_usuario,
                'notas' => $request->notas
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cita registrada exitosamente',
                'data' => [
                    'id_cita' => $cita->id_cita,
                    'fecha_hora_inicio' => $cita->fecha_hora_inicio->format('Y-m-d H:i:s'),
                    'fecha_hora_fin' => $cita->fecha_hora_fin->format('Y-m-d H:i:s'),
                    'estado' => $cita->estado,
                    'motivo' => $cita->motivo
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirma una cita (cambia estado de pendiente a confirmada)
     */
    public function confirmarCita($id): JsonResponse
    {
        try {
            $cita = Cita::findOrFail($id);

            if ($cita->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden confirmar citas en estado pendiente'
                ], 400);
            }

            $cita->update(['estado' => 'confirmado']);

            return response()->json([
                'success' => true,
                'message' => 'Cita confirmada exitosamente',
                'data' => [
                    'id_cita' => $cita->id_cita,
                    'estado' => $cita->estado,
                    'fecha_hora_inicio' => $cita->fecha_hora_inicio->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra una interacción con la IA
     */
    public function registrarInteraccion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_usuario' => 'required|exists:usuarios,id_usuario',
                'tipo_intencion' => 'nullable|string|max:50',
                'entrada_usuario' => 'nullable|string',
                'respuesta_ia' => 'nullable|string',
                'estado_resultado' => 'nullable|in:exitosa,fallida,requiere_revision',
                'contexto' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $interaccion = InteraccionIA::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Interacción registrada',
                'data' => ['id_interaccion' => $interaccion->id_interaccion]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar interacción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Obtiene el nombre del día en español
     */
    private function getNombreDia($numeroDia): string
    {
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];

        return $dias[$numeroDia] ?? '';
    }

    // ========================================
    // NUEVAS FUNCIONALIDADES: GESTIÓN DE CITAS CON VERIFICACIÓN DNI
    // ========================================

    /**
     * Cancela una cita con verificación de identidad del paciente
     * Requiere DNI y nombre parcial para validar identidad
     */
    public function verificarPacienteYCancelarCita(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dni' => 'required|string|size:8',
                'nombre_parcial' => 'required|string|min:2',
                'id_cita' => 'required|integer|exists:citas,id_cita',
                'motivo_cancelacion' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $verificacionService = new VerificacionPacienteService();

            // PASO 1: Verificar identidad del paciente
            $verificacion = $verificacionService->verificarIdentidadPaciente(
                $request->dni,
                $request->nombre_parcial
            );

            if (!$verificacion['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $verificacion['message']
                ], 403);
            }

            $paciente = $verificacion['paciente'];

            // PASO 2: Buscar y validar que la cita pertenece al paciente
            $resultadoCita = $verificacionService->buscarCitaYValidarPaciente(
                $request->id_cita,
                $paciente->id_paciente
            );

            if (!$resultadoCita['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultadoCita['message']
                ], 404);
            }

            $cita = $resultadoCita['cita'];

            // PASO 3: Validar que la cita puede ser cancelada
            $validacionEstado = $verificacionService->validarEstadoParaModificacion($cita);

            if (!$validacionEstado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validacionEstado['message']
                ], 400);
            }

            // PASO 4: Cancelar la cita
            $motivoCancelacion = $request->motivo_cancelacion ?? 'Cancelada por el paciente mediante asistente IA';
            $notasActualizadas = $cita->notas
                ? $cita->notas . "\n[Cancelada por paciente - IA]: " . $motivoCancelacion
                : "[Cancelada por paciente - IA]: " . $motivoCancelacion;

            $cita->update([
                'estado' => 'cancelado',
                'notas' => $notasActualizadas
            ]);

            // PASO 5: Registrar en log de actividad
            LogActividad::create([
                'id_usuario' => $cita->paciente->id_usuario,
                'accion' => 'cancelar_cita_ia',
                'descripcion' => "Cita cancelada mediante asistente IA - DNI verificado: {$request->dni}",
                'tabla_afectada' => 'citas',
                'id_registro_afectado' => $cita->id_cita
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cita cancelada exitosamente',
                'data' => [
                    'cita' => $verificacionService->formatearInfoCita($cita),
                    'motivo_cancelacion' => $motivoCancelacion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la cita: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reprograma una cita con verificación de identidad del paciente
     */
    public function verificarPacienteYReprogramarCita(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dni' => 'required|string|size:8',
                'nombre_parcial' => 'required|string|min:2',
                'id_cita' => 'required|integer|exists:citas,id_cita',
                'nueva_fecha' => 'required|date|after:today',
                'nueva_hora_inicio' => 'required|date_format:H:i',
                'nueva_hora_fin' => 'required|date_format:H:i|after:nueva_hora_inicio',
                'motivo_reprogramacion' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $verificacionService = new VerificacionPacienteService();

            // PASO 1: Verificar identidad
            $verificacion = $verificacionService->verificarIdentidadPaciente(
                $request->dni,
                $request->nombre_parcial
            );

            if (!$verificacion['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $verificacion['message']
                ], 403);
            }

            $paciente = $verificacion['paciente'];

            // PASO 2: Validar cita
            $resultadoCita = $verificacionService->buscarCitaYValidarPaciente(
                $request->id_cita,
                $paciente->id_paciente
            );

            if (!$resultadoCita['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultadoCita['message']
                ], 404);
            }

            $cita = $resultadoCita['cita'];

            // PASO 3: Validar estado
            $validacionEstado = $verificacionService->validarEstadoParaModificacion($cita);

            if (!$validacionEstado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validacionEstado['message']
                ], 400);
            }

            // PASO 4: Validar disponibilidad del médico en nuevo horario
            $nuevaFechaHoraInicio = Carbon::parse($request->nueva_fecha . ' ' . $request->nueva_hora_inicio);
            $nuevaFechaHoraFin = Carbon::parse($request->nueva_fecha . ' ' . $request->nueva_hora_fin);

            $conflicto = Cita::where('id_medico', $cita->id_medico)
                ->where('id_cita', '!=', $cita->id_cita)
                ->whereIn('estado', ['pendiente', 'confirmado', 'en_espera'])
                ->where(function ($query) use ($nuevaFechaHoraInicio, $nuevaFechaHoraFin) {
                    $query->whereBetween('fecha_hora_inicio', [$nuevaFechaHoraInicio, $nuevaFechaHoraFin])
                          ->orWhereBetween('fecha_hora_fin', [$nuevaFechaHoraInicio, $nuevaFechaHoraFin])
                          ->orWhere(function ($q) use ($nuevaFechaHoraInicio, $nuevaFechaHoraFin) {
                              $q->where('fecha_hora_inicio', '<=', $nuevaFechaHoraInicio)
                                ->where('fecha_hora_fin', '>=', $nuevaFechaHoraFin);
                          });
                })
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'El médico no está disponible en el nuevo horario solicitado. Por favor elige otro horario.'
                ], 400);
            }

            // PASO 5: Reprogramar la cita
            $motivoReprogramacion = $request->motivo_reprogramacion ?? 'Reprogramada por el paciente mediante asistente IA';
            $notasActualizadas = $cita->notas
                ? $cita->notas . "\n[Reprogramada - IA]: De {$cita->fecha_hora_inicio->format('Y-m-d H:i')} a {$nuevaFechaHoraInicio->format('Y-m-d H:i')}. Motivo: {$motivoReprogramacion}"
                : "[Reprogramada - IA]: {$motivoReprogramacion}";

            $cita->update([
                'fecha_hora_inicio' => $nuevaFechaHoraInicio,
                'fecha_hora_fin' => $nuevaFechaHoraFin,
                'estado' => 'pendiente', // Volver a pendiente para que el médico confirme
                'notas' => $notasActualizadas
            ]);

            // PASO 6: Registrar en log
            LogActividad::create([
                'id_usuario' => $cita->paciente->id_usuario,
                'accion' => 'reprogramar_cita_ia',
                'descripcion' => "Cita reprogramada mediante asistente IA - Nuevo horario: {$nuevaFechaHoraInicio->format('Y-m-d H:i')}",
                'tabla_afectada' => 'citas',
                'id_registro_afectado' => $cita->id_cita
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cita reprogramada exitosamente',
                'data' => [
                    'cita' => $verificacionService->formatearInfoCita($cita),
                    'nuevo_horario' => [
                        'fecha' => $nuevaFechaHoraInicio->format('Y-m-d'),
                        'hora_inicio' => $nuevaFechaHoraInicio->format('H:i'),
                        'hora_fin' => $nuevaFechaHoraFin->format('H:i')
                    ],
                    'motivo_reprogramacion' => $motivoReprogramacion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reprogramar la cita: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambia el médico de una cita con verificación de identidad
     */
    public function verificarPacienteYCambiarMedico(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dni' => 'required|string|size:8',
                'nombre_parcial' => 'required|string|min:2',
                'id_cita' => 'required|integer|exists:citas,id_cita',
                'id_nuevo_medico' => 'required|integer|exists:medicos,id_medico',
                'motivo_cambio' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $verificacionService = new VerificacionPacienteService();

            // PASO 1: Verificar identidad
            $verificacion = $verificacionService->verificarIdentidadPaciente(
                $request->dni,
                $request->nombre_parcial
            );

            if (!$verificacion['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $verificacion['message']
                ], 403);
            }

            $paciente = $verificacion['paciente'];

            // PASO 2: Validar cita
            $resultadoCita = $verificacionService->buscarCitaYValidarPaciente(
                $request->id_cita,
                $paciente->id_paciente
            );

            if (!$resultadoCita['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultadoCita['message']
                ], 404);
            }

            $cita = $resultadoCita['cita'];

            // PASO 3: Validar estado
            $validacionEstado = $verificacionService->validarEstadoParaModificacion($cita);

            if (!$validacionEstado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validacionEstado['message']
                ], 400);
            }

            // PASO 4: Validar que el nuevo médico existe y está activo
            $nuevoMedico = Medico::with('usuario')->find($request->id_nuevo_medico);

            if (!$nuevoMedico || $nuevoMedico->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'El médico seleccionado no está disponible.'
                ], 400);
            }

            // PASO 5: Verificar que no es el mismo médico
            if ($cita->id_medico == $request->id_nuevo_medico) {
                return response()->json([
                    'success' => false,
                    'message' => 'El médico seleccionado ya está asignado a esta cita.'
                ], 400);
            }

            // PASO 6: Validar disponibilidad del nuevo médico en ese horario
            $conflicto = Cita::where('id_medico', $request->id_nuevo_medico)
                ->whereIn('estado', ['pendiente', 'confirmado', 'en_espera'])
                ->where(function ($query) use ($cita) {
                    $query->whereBetween('fecha_hora_inicio', [$cita->fecha_hora_inicio, $cita->fecha_hora_fin])
                          ->orWhereBetween('fecha_hora_fin', [$cita->fecha_hora_inicio, $cita->fecha_hora_fin])
                          ->orWhere(function ($q) use ($cita) {
                              $q->where('fecha_hora_inicio', '<=', $cita->fecha_hora_inicio)
                                ->where('fecha_hora_fin', '>=', $cita->fecha_hora_fin);
                          });
                })
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nuevo médico no está disponible en ese horario. Por favor elige otro médico o reprograma la cita.'
                ], 400);
            }

            // PASO 7: Cambiar médico
            $medicoAnterior = $cita->medico;
            $motivoCambio = $request->motivo_cambio ?? 'Cambio de médico solicitado por el paciente mediante asistente IA';

            $notasActualizadas = $cita->notas
                ? $cita->notas . "\n[Cambio de médico - IA]: De Dr(a). {$medicoAnterior->usuario->nombres} {$medicoAnterior->usuario->apellidos} a Dr(a). {$nuevoMedico->usuario->nombres} {$nuevoMedico->usuario->apellidos}. Motivo: {$motivoCambio}"
                : "[Cambio de médico - IA]: {$motivoCambio}";

            $cita->update([
                'id_medico' => $request->id_nuevo_medico,
                'estado' => 'pendiente', // Volver a pendiente para que el nuevo médico confirme
                'notas' => $notasActualizadas
            ]);

            // Recargar relación
            $cita->load('medico.usuario');

            // PASO 8: Registrar en log
            LogActividad::create([
                'id_usuario' => $cita->paciente->id_usuario,
                'accion' => 'cambiar_medico_cita_ia',
                'descripcion' => "Cambio de médico mediante asistente IA - Nuevo médico: Dr(a). {$nuevoMedico->usuario->nombres} {$nuevoMedico->usuario->apellidos}",
                'tabla_afectada' => 'citas',
                'id_registro_afectado' => $cita->id_cita
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Médico cambiado exitosamente',
                'data' => [
                    'cita' => $verificacionService->formatearInfoCita($cita),
                    'medico_anterior' => [
                        'nombre' => $medicoAnterior->usuario->nombres . ' ' . $medicoAnterior->usuario->apellidos,
                        'especialidad' => $medicoAnterior->especialidad
                    ],
                    'medico_nuevo' => [
                        'nombre' => $nuevoMedico->usuario->nombres . ' ' . $nuevoMedico->usuario->apellidos,
                        'especialidad' => $nuevoMedico->especialidad
                    ],
                    'motivo_cambio' => $motivoCambio
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el médico: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // NUEVAS FUNCIONALIDADES: GESTIÓN SIMPLIFICADA CON SOLO DNI
    // ========================================

    /**
     * Busca citas de un paciente usando solo su DNI (sin verificación de nombre)
     * Retorna lista de citas para que el paciente elija cuál modificar
     */
    public function buscarCitasPorDni(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dni' => 'required|string|size:8',
                'user_id' => 'nullable|integer' // Opcional: ID del usuario logueado para validación
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $verificacionService = new VerificacionPacienteService();

            // Buscar paciente solo por DNI
            $resultado = $verificacionService->buscarPacientePorDni($request->dni);

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message']
                ], 404);
            }

            $paciente = $resultado['paciente'];

            // VALIDACIÓN DE SEGURIDAD: Si se proporciona user_id, verificar que el DNI pertenezca al usuario logueado
            if ($request->has('user_id') && $request->user_id) {
                // Buscar el usuario logueado
                $usuario = User::find($request->user_id);

                if (!$usuario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuario no encontrado.'
                    ], 404);
                }

                // Buscar si el usuario tiene un perfil de paciente asociado
                $pacienteAsociado = Paciente::where('id_usuario', $usuario->id_usuario)->first();

                if (!$pacienteAsociado) {
                    // El usuario logueado NO es un paciente (es admin, médico, secretaria)
                    // En este caso, permitimos la búsqueda sin validación
                    Log::info("Usuario {$usuario->id_usuario} ({$usuario->correo}) no es paciente, permitiendo búsqueda de DNI {$request->dni}");
                } else {
                    // El usuario SÍ es un paciente, validar que el DNI coincida
                    if ($pacienteAsociado->dni !== $request->dni) {
                        return response()->json([
                            'success' => false,
                            'message' => '❌ El DNI proporcionado no coincide con tu cuenta. Por seguridad, solo puedes consultar tus propias citas.'
                        ], 403);
                    }
                    Log::info("Validación exitosa: DNI {$request->dni} pertenece al usuario {$usuario->id_usuario}");
                }
            }

            // Buscar citas del paciente (solo futuras y modificables)
            $citas = Cita::with(['medico.usuario'])
                ->where('id_paciente', $paciente->id_paciente)
                ->whereIn('estado', ['pendiente', 'confirmado', 'en_espera'])
                ->where('fecha_hora_inicio', '>', now())
                ->orderBy('fecha_hora_inicio', 'asc')
                ->get();

            if ($citas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes citas programadas que se puedan modificar.'
                ], 404);
            }

            // Formatear citas
            $citasFormateadas = $citas->map(function ($cita) use ($verificacionService) {
                return $verificacionService->formatearInfoCita($cita);
            });

            return response()->json([
                'success' => true,
                'message' => 'Citas encontradas',
                'data' => [
                    'paciente' => [
                        'id_paciente' => $paciente->id_paciente,
                        'nombre_completo' => "{$paciente->nombres} {$paciente->apellidos}",
                        'dni' => $paciente->dni
                    ],
                    'citas' => $citasFormateadas,
                    'total_citas' => $citasFormateadas->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar citas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancela una cita usando solo DNI (sin verificación de nombre)
     */
    public function cancelarCitaConDni(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dni' => 'required|string|size:8',
                'id_cita' => 'required|integer|exists:citas,id_cita',
                'motivo_cancelacion' => 'nullable|string|max:500',
                'user_id' => 'nullable|integer' // Opcional: ID del usuario logueado para validación
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $verificacionService = new VerificacionPacienteService();

            // Buscar paciente por DNI
            $resultado = $verificacionService->buscarPacientePorDni($request->dni);

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message']
                ], 404);
            }

            $paciente = $resultado['paciente'];

            // VALIDACIÓN DE SEGURIDAD: Si se proporciona user_id, verificar que el DNI pertenezca al usuario logueado
            if ($request->has('user_id') && $request->user_id) {
                $usuario = User::find($request->user_id);

                if (!$usuario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuario no encontrado.'
                    ], 404);
                }

                $pacienteAsociado = Paciente::where('id_usuario', $usuario->id_usuario)->first();

                if ($pacienteAsociado && $pacienteAsociado->dni !== $request->dni) {
                    return response()->json([
                        'success' => false,
                        'message' => '❌ El DNI proporcionado no coincide con tu cuenta. Por seguridad, solo puedes cancelar tus propias citas.'
                    ], 403);
                }

                Log::info("Validación exitosa para cancelación: DNI {$request->dni} pertenece al usuario {$usuario->id_usuario}");
            }

            // Validar que la cita pertenece al paciente
            $resultadoCita = $verificacionService->buscarCitaYValidarPaciente(
                $request->id_cita,
                $paciente->id_paciente
            );

            if (!$resultadoCita['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultadoCita['message']
                ], 404);
            }

            $cita = $resultadoCita['cita'];

            // Validar estado
            $validacionEstado = $verificacionService->validarEstadoParaModificacion($cita);

            if (!$validacionEstado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validacionEstado['message']
                ], 400);
            }

            // Cancelar cita
            $motivoCancelacion = $request->motivo_cancelacion ?? 'Cancelada por el paciente mediante asistente IA';
            $notasActualizadas = $cita->notas
                ? $cita->notas . "\n[Cancelada - IA con DNI]: " . $motivoCancelacion
                : "[Cancelada - IA con DNI]: " . $motivoCancelacion;

            $cita->update([
                'estado' => 'cancelado',
                'notas' => $notasActualizadas
            ]);

            // Log
            LogActividad::create([
                'id_usuario' => $cita->paciente->id_usuario,
                'accion' => 'cancelar_cita_ia_dni',
                'descripcion' => "Cita cancelada con DNI: {$request->dni}",
                'tabla_afectada' => 'citas',
                'id_registro_afectado' => $cita->id_cita
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cita cancelada exitosamente',
                'data' => [
                    'cita' => $verificacionService->formatearInfoCita($cita),
                    'motivo_cancelacion' => $motivoCancelacion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la cita: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reprograma una cita médica usando solo DNI del paciente
     * Valida disponibilidad del médico en la nueva fecha/hora
     */
    public function reprogramarCitaConDni(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dni' => 'required|string|size:8',
                'id_cita' => 'required|integer|exists:citas,id_cita',
                'nueva_fecha' => 'required|date|after_or_equal:today',
                'nueva_hora_inicio' => 'required|date_format:H:i',
                'nueva_hora_fin' => 'required|date_format:H:i|after:nueva_hora_inicio',
                'motivo_reprogramacion' => 'nullable|string|max:500',
                'user_id' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $verificacionService = new VerificacionPacienteService();

            // Buscar paciente por DNI
            $resultado = $verificacionService->buscarPacientePorDni($request->dni);

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message']
                ], 404);
            }

            $paciente = $resultado['paciente'];

            // VALIDACIÓN DE SEGURIDAD: Si se proporciona user_id, verificar que el DNI pertenezca al usuario logueado
            if ($request->has('user_id') && $request->user_id) {
                $usuario = User::find($request->user_id);

                if (!$usuario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuario no encontrado.'
                    ], 404);
                }

                $pacienteAsociado = Paciente::where('id_usuario', $usuario->id_usuario)->first();

                if ($pacienteAsociado && $pacienteAsociado->dni !== $request->dni) {
                    return response()->json([
                        'success' => false,
                        'message' => '❌ El DNI proporcionado no coincide con tu cuenta. Por seguridad, solo puedes reprogramar tus propias citas.'
                    ], 403);
                }
            }

            // Validar que la cita pertenece al paciente
            $resultadoCita = $verificacionService->buscarCitaYValidarPaciente(
                $request->id_cita,
                $paciente->id_paciente
            );

            if (!$resultadoCita['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultadoCita['message']
                ], 404);
            }

            $cita = $resultadoCita['cita'];

            // Validar estado de la cita
            if (!in_array($cita->estado, ['pendiente', 'confirmado'])) {
                return response()->json([
                    'success' => false,
                    'message' => "No puedes reprogramar una cita con estado '{$cita->estado}'. Solo se pueden reprogramar citas pendientes o confirmadas."
                ], 400);
            }

            // Construir fecha/hora completa
            $nuevaFechaHoraInicio = "{$request->nueva_fecha} {$request->nueva_hora_inicio}:00";
            $nuevaFechaHoraFin = "{$request->nueva_fecha} {$request->nueva_hora_fin}:00";

            // VALIDAR DISPONIBILIDAD DEL MÉDICO
            $citasConflictivas = Cita::where('id_medico', $cita->id_medico)
                ->where('id_cita', '!=', $cita->id_cita)
                ->whereIn('estado', ['pendiente', 'confirmado'])
                ->where(function ($query) use ($nuevaFechaHoraInicio, $nuevaFechaHoraFin) {
                    $query->whereBetween('fecha_hora_inicio', [$nuevaFechaHoraInicio, $nuevaFechaHoraFin])
                        ->orWhereBetween('fecha_hora_fin', [$nuevaFechaHoraInicio, $nuevaFechaHoraFin])
                        ->orWhere(function ($q) use ($nuevaFechaHoraInicio, $nuevaFechaHoraFin) {
                            $q->where('fecha_hora_inicio', '<=', $nuevaFechaHoraInicio)
                              ->where('fecha_hora_fin', '>=', $nuevaFechaHoraFin);
                        });
                })
                ->exists();

            if ($citasConflictivas) {
                return response()->json([
                    'success' => false,
                    'message' => "❌ El médico NO está disponible en ese horario. Ya tiene una cita programada. Por favor elige otra fecha/hora."
                ], 400);
            }

            // Reprogramar cita
            $motivoReprogramacion = $request->motivo_reprogramacion ?? 'Reprogramada por el paciente mediante asistente IA';
            $notasActualizadas = $cita->notas
                ? $cita->notas . "\n[Reprogramada - IA con DNI]: " . $motivoReprogramacion
                : "[Reprogramada - IA con DNI]: " . $motivoReprogramacion;

            $cita->update([
                'fecha_hora_inicio' => $nuevaFechaHoraInicio,
                'fecha_hora_fin' => $nuevaFechaHoraFin,
                'notas' => $notasActualizadas
            ]);

            // Log
            LogActividad::create([
                'id_usuario' => $cita->paciente->id_usuario,
                'accion' => 'reprogramar_cita_ia_dni',
                'descripcion' => "Cita reprogramada con DNI: {$request->dni} a {$nuevaFechaHoraInicio}",
                'tabla_afectada' => 'citas',
                'id_registro_afectado' => $cita->id_cita
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cita reprogramada exitosamente',
                'data' => [
                    'cita' => $verificacionService->formatearInfoCita($cita),
                    'motivo_reprogramacion' => $motivoReprogramacion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reprogramar la cita: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // NUEVAS FUNCIONALIDADES: CONSULTAS SIN VERIFICACIÓN DNI
    // ========================================

    /**
     * Consulta el odontograma de un paciente
     */
    public function consultarOdontogramaPaciente($idPaciente): JsonResponse
    {
        try {
            \Log::info("Consultando odontograma - Paciente ID recibido: {$idPaciente}");

            $paciente = Paciente::find($idPaciente);

            if (!$paciente) {
                // Listar pacientes existentes para debug
                $pacientesExistentes = Paciente::select('id_paciente', 'nombres', 'apellidos')->limit(5)->get();
                \Log::warning("Paciente {$idPaciente} no encontrado. Pacientes existentes: " . $pacientesExistentes->pluck('id_paciente')->implode(', '));

                return response()->json([
                    'success' => false,
                    'message' => "Paciente con ID {$idPaciente} no encontrado. IDs disponibles: " . $pacientesExistentes->pluck('id_paciente')->implode(', ')
                ], 404);
            }

            // Buscar el historial clínico más reciente
            $historial = HistorialClinico::where('id_paciente', $idPaciente)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$historial) {
                return response()->json([
                    'success' => true,
                    'message' => 'El paciente aún no tiene un historial clínico registrado',
                    'data' => [
                        'tiene_odontograma' => false,
                        'piezas' => []
                    ]
                ]);
            }

            // Obtener odontograma
            $odontograma = Odontograma::where('id_historial', $historial->id_historial)->get();

            if ($odontograma->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'El paciente no tiene odontograma registrado aún',
                    'data' => [
                        'tiene_odontograma' => false,
                        'piezas' => []
                    ]
                ]);
            }

            // Formatear respuesta con resumen
            $resumen = [
                'total_piezas' => $odontograma->count(),
                'sanas' => $odontograma->where('estado_pieza', 'sano')->count(),
                'cariadas' => $odontograma->where('estado_pieza', 'cariado')->count(),
                'restauradas' => $odontograma->where('estado_pieza', 'restaurado')->count(),
                'ausentes' => $odontograma->where('estado_pieza', 'ausente')->count(),
                'con_protesis' => $odontograma->where('estado_pieza', 'protesis')->count(),
                'otros' => $odontograma->where('estado_pieza', 'otros')->count()
            ];

            $piezasDetalle = $odontograma->map(function ($pieza) {
                return [
                    'pieza' => $pieza->pieza,
                    'estado' => $pieza->estado_pieza,
                    'tratamiento_asociado' => $pieza->tratamiento_asociado,
                    'comentario' => $pieza->comentario
                ];
            });

            // URL del frontend para visualización completa
            $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
            $urlVisualizacion = $frontendUrl . "/paciente/odontograma/{$historial->id_historial}";

            return response()->json([
                'success' => true,
                'message' => 'Odontograma obtenido exitosamente',
                'data' => [
                    'tiene_odontograma' => true,
                    'resumen' => $resumen,
                    'piezas' => $piezasDetalle,
                    'url_visualizacion_completa' => $urlVisualizacion,
                    'fecha_historial' => $historial->created_at->format('Y-m-d')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar odontograma: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta el historial de pagos de un paciente
     */
    public function consultarHistorialPagos(Request $request, $idPaciente): JsonResponse
    {
        try {
            $paciente = Paciente::find($idPaciente);

            if (!$paciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado'
                ], 404);
            }

            // Parámetros de consulta
            $tipo = $request->query('tipo', 'todos'); // ultimo, año_actual, todos
            $limite = (int) $request->query('limite', 10);

            $query = Pago::where('id_paciente', $idPaciente)
                ->where('estado_pago', 'pagado') // Solo pagos completados
                ->orderBy('fecha_pago', 'desc');

            // Filtros según tipo
            if ($tipo === 'ultimo') {
                $query->limit(1);
            } elseif ($tipo === 'año_actual') {
                $query->whereYear('fecha_pago', date('Y'));
            } else {
                $query->limit($limite);
            }

            $pagos = $query->get();

            if ($pagos->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron pagos registrados',
                    'data' => [
                        'tiene_pagos' => false,
                        'pagos' => [],
                        'resumen' => [
                            'total_pagado' => 0,
                            'cantidad_pagos' => 0
                        ]
                    ]
                ]);
            }

            // Formatear pagos
            $pagosFormateados = $pagos->map(function ($pago) {
                return [
                    'id_pago' => $pago->id_pago,
                    'fecha_pago' => $pago->fecha_pago->format('Y-m-d'),
                    'concepto' => $pago->concepto,
                    'monto' => (float) $pago->monto,
                    'metodo_pago' => $pago->metodo_pago,
                    'tipo_comprobante' => $pago->tipo_comprobante ?? 'ninguno',
                    'comprobante' => $pago->tiene_comprobante
                        ? $pago->comprobante_completo
                        : null,
                    'notas' => $pago->notas
                ];
            });

            // Resumen
            $totalPagado = $pagos->sum('monto');
            $cantidadPagos = $pagos->count();

            // Resumen por método de pago
            $porMetodo = $pagos->groupBy('metodo_pago')->map(function ($items, $metodo) {
                return [
                    'metodo' => $metodo,
                    'cantidad' => $items->count(),
                    'total' => (float) $items->sum('monto')
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Historial de pagos obtenido exitosamente',
                'data' => [
                    'tiene_pagos' => true,
                    'pagos' => $pagosFormateados,
                    'resumen' => [
                        'total_pagado' => (float) $totalPagado,
                        'cantidad_pagos' => $cantidadPagos,
                        'por_metodo_pago' => $porMetodo,
                        'tipo_consulta' => $tipo
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar pagos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el estado de tratamientos del paciente
     */
    public function getEstadoTratamientos(int $pacienteId): JsonResponse
    {
        try {
            $paciente = Paciente::find($pacienteId);

            if (!$paciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado'
                ], 404);
            }

            // Obtener historial clínico
            $historial = HistorialClinico::with(['tratamientos'])
            ->where('id_paciente', $pacienteId)
            ->first();

            if (!$historial || $historial->tratamientos->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tiene_tratamientos' => false,
                        'message' => 'No se encontraron tratamientos registrados'
                    ]
                ]);
            }

            $tratamientos = $historial->tratamientos;

            // Clasificar tratamientos
            $activos = $tratamientos->whereIn('estado_tratamiento', ['iniciado', 'en_curso', 'pausado'])->values();
            $completados = $tratamientos->where('estado_tratamiento', 'completado')
                ->sortByDesc('fecha_finalizacion')
                ->take(5)
                ->values();
            $pendientes = $tratamientos->where('estado_tratamiento', 'pendiente')->values();

            // Resumen
            $resumen = [
                'total_tratamientos' => $tratamientos->count(),
                'activos' => $activos->count(),
                'completados' => $tratamientos->where('estado_tratamiento', 'completado')->count(),
                'pendientes' => $pendientes->count(),
                'monto_total_tratamientos' => (float) $tratamientos->sum('precio')
            ];

            // Formatear activos
            $activosFormateados = $activos->map(function ($trat) {
                return [
                    'id_tratamiento' => $trat->id_tratamiento,
                    'nombre_tratamiento' => $trat->nombre_tratamiento,
                    'descripcion' => $trat->descripcion,
                    'estado' => $trat->estado_tratamiento,
                    'fecha_inicio' => $trat->fecha_inicio ? $trat->fecha_inicio->format('Y-m-d') : null,
                    'fecha_fin_estimada' => $trat->fecha_finalizacion ? $trat->fecha_finalizacion->format('Y-m-d') : null,
                    'precio' => (float) $trat->precio,
                    'medico_responsable' => 'No asignado'
                ];
            });

            // Formatear completados
            $completadosFormateados = $completados->map(function ($trat) {
                return [
                    'id_tratamiento' => $trat->id_tratamiento,
                    'nombre_tratamiento' => $trat->nombre_tratamiento,
                    'fecha_inicio' => $trat->fecha_inicio ? $trat->fecha_inicio->format('Y-m-d') : null,
                    'fecha_fin' => $trat->fecha_finalizacion ? $trat->fecha_finalizacion->format('Y-m-d') : null,
                    'precio' => (float) $trat->precio
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Estado de tratamientos obtenido exitosamente',
                'data' => [
                    'tiene_tratamientos' => true,
                    'resumen' => $resumen,
                    'tratamientos_activos' => $activosFormateados,
                    'tratamientos_completados_recientes' => $completadosFormateados
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar tratamientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta el estado de tratamientos de un paciente
     */
    public function consultarEstadoTratamientos($idPaciente): JsonResponse
    {
        try {
            $paciente = Paciente::find($idPaciente);

            if (!$paciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado'
                ], 404);
            }

            // Buscar tratamientos del paciente a través de historial clínico
            $tratamientos = TratamientoHistorial::whereHas('historial', function ($query) use ($idPaciente) {
                $query->where('id_paciente', $idPaciente);
            })
            ->with(['tratamiento', 'realizadoPor.usuario', 'seguimientos'])
            ->orderBy('fecha_inicio', 'desc')
            ->get();

            if ($tratamientos->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron tratamientos registrados',
                    'data' => [
                        'tiene_tratamientos' => false,
                        'tratamientos_activos' => [],
                        'tratamientos_completados' => []
                    ]
                ]);
            }

            // Separar por estado
            $activos = $tratamientos->whereIn('estado', ['pendiente', 'en_curso']);
            $completados = $tratamientos->where('estado', 'completado')->take(5); // Últimos 5 completados

            // Formatear tratamientos activos
            $activosFormateados = $activos->map(function ($tratamiento) {
                return [
                    'id' => $tratamiento->id,
                    'nombre_tratamiento' => $tratamiento->tratamiento->nombre ?? 'Tratamiento personalizado',
                    'descripcion' => $tratamiento->descripcion,
                    'estado' => $tratamiento->estado,
                    'fecha_inicio' => $tratamiento->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin_estimada' => $tratamiento->fecha_fin?->format('Y-m-d'),
                    'precio' => (float) $tratamiento->precio,
                    'medico_responsable' => $tratamiento->realizadoPor
                        ? $tratamiento->realizadoPor->usuario->nombres . ' ' . $tratamiento->realizadoPor->usuario->apellidos
                        : 'No asignado',
                    'cantidad_seguimientos' => $tratamiento->seguimientos->count(),
                    'ultimo_seguimiento' => $tratamiento->seguimientos->first()
                        ? [
                            'fecha' => $tratamiento->seguimientos->first()->fecha_registro->format('Y-m-d'),
                            'observaciones' => $tratamiento->seguimientos->first()->observaciones
                        ]
                        : null
                ];
            });

            // Formatear tratamientos completados
            $completadosFormateados = $completados->map(function ($tratamiento) {
                return [
                    'id' => $tratamiento->id,
                    'nombre_tratamiento' => $tratamiento->tratamiento->nombre ?? 'Tratamiento personalizado',
                    'fecha_inicio' => $tratamiento->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin' => $tratamiento->fecha_fin?->format('Y-m-d'),
                    'precio' => (float) $tratamiento->precio,
                    'medico_responsable' => $tratamiento->realizadoPor
                        ? $tratamiento->realizadoPor->usuario->nombres . ' ' . $tratamiento->realizadoPor->usuario->apellidos
                        : 'No asignado'
                ];
            });

            // Resumen
            $resumen = [
                'total_tratamientos' => $tratamientos->count(),
                'activos' => $activos->count(),
                'completados' => $tratamientos->where('estado', 'completado')->count(),
                'pendientes' => $tratamientos->where('estado', 'pendiente')->count(),
                'monto_total_tratamientos' => (float) $tratamientos->sum('precio')
            ];

            return response()->json([
                'success' => true,
                'message' => 'Estado de tratamientos obtenido exitosamente',
                'data' => [
                    'tiene_tratamientos' => true,
                    'resumen' => $resumen,
                    'tratamientos_activos' => $activosFormateados,
                    'tratamientos_completados_recientes' => $completadosFormateados
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar tratamientos: ' . $e->getMessage()
            ], 500);
        }
    }
}
