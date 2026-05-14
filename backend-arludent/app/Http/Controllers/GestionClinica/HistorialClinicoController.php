<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\HistorialClinico;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Cita;
use App\Models\User;
use App\Models\Rol;
use App\Models\LogActividad;
use App\Services\HistorialClinicoService;

/**
 * Controlador de Historiales Clínicos
 *
 * Gestiona los historiales clínicos de los pacientes para médicos
 */
class HistorialClinicoController extends Controller
{
    private HistorialClinicoService $historialService;

    public function __construct(HistorialClinicoService $historialService)
    {
        $this->historialService = $historialService;
    }
    /**
     * Listar personas atendidas por el médico (pacientes y usuarios externos)
     *
     * Retorna una lista unificada de:
     * - Pacientes con registro completo (pueden o no tener historial)
     * - Usuarios externos sin registro de paciente (necesitan completar datos)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $medico = Medico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$medico) {
            return $this->errorResponse('No se encontró el perfil de médico.', 404);
        }

        $listaPersonas = collect();

        // Determinar qué pacientes puede ver según tipo de médico
        if (in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            // 1. Médico de cabecera: Obtener TODOS los PACIENTES (con o sin historial)
            $pacientes = Paciente::with(['usuario:id_usuario,correo,telefono'])->get();
        } else {
            // 2. Médico especialista: Solo pacientes con citas con él
            $pacientesIds = Cita::where('id_medico', $medico->id_medico)
                ->whereNotNull('id_paciente')
                ->distinct()
                ->pluck('id_paciente');

            $pacientes = Paciente::with(['usuario:id_usuario,correo,telefono'])
                ->whereIn('id_paciente', $pacientesIds)
                ->get();
        }

        foreach ($pacientes as $paciente) {
            $historial = HistorialClinico::where('id_paciente', $paciente->id_paciente)->first();

            $listaPersonas->push([
                'tipo' => 'paciente',
                'id_usuario' => $paciente->id_usuario,
                'id_paciente' => $paciente->id_paciente,
                'id_usuario_externo' => null,
                'nombres' => $paciente->nombres,
                'apellidos' => $paciente->apellidos,
                'nombre_completo' => trim($paciente->nombres . ' ' . $paciente->apellidos),
                'dni' => $paciente->dni,
                'telefono' => $paciente->usuario ? $paciente->usuario->telefono : null, // Teléfono principal del usuario
                'telefono_responsable' => $paciente->telefono_responsable, // Teléfono del responsable
                'correo' => $paciente->usuario ? $paciente->usuario->correo : null,
                'tiene_registro_paciente' => true,
                'tiene_historial' => $historial ? true : false,
                'id_historial' => $historial ? $historial->id_historial : null,
                'codigo_historial' => $historial ? $historial->codigo_historial : null,
                'fecha_creacion_historial' => $historial ? $historial->created_at : null,
                'estado' => 'completo', // Ya es paciente completo
            ]);
        }

        // 2. Obtener USUARIOS EXTERNOS (sin registro de paciente) - Solo para médicos de cabecera
        if (in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            $rolExterno = Rol::where('nombre_rol', 'externo')->first();

            if ($rolExterno) {
                $usuariosExternos = User::whereHas('roles', function ($query) use ($rolExterno) {
                    $query->where('roles.id_rol', $rolExterno->id_rol);
                })
                ->whereDoesntHave('paciente') // Que NO tengan registro de paciente
                ->get();

                foreach ($usuariosExternos as $usuarioExterno) {
                    $listaPersonas->push([
                        'tipo' => 'externo',
                        'id_usuario' => $usuarioExterno->id_usuario,
                        'id_paciente' => null,
                        'id_usuario_externo' => $usuarioExterno->id_usuario,
                        'nombres' => $usuarioExterno->username ?? 'Sin nombre',
                        'apellidos' => '',
                        'nombre_completo' => $usuarioExterno->username ?? $usuarioExterno->correo,
                        'dni' => null,
                        'telefono' => $usuarioExterno->telefono,
                        'correo' => $usuarioExterno->correo,
                        'tiene_registro_paciente' => false,
                        'tiene_historial' => false,
                        'id_historial' => null,
                        'codigo_historial' => null,
                        'fecha_creacion_historial' => null,
                        'estado' => 'pendiente_registro', // Necesita completar datos de paciente
                    ]);
                }
            }
        }

        // Filtro de búsqueda opcional
        $busqueda = $request->query('busqueda');
        if ($busqueda) {
            $listaPersonas = $listaPersonas->filter(function ($persona) use ($busqueda) {
                $textoBusqueda = strtolower($busqueda);
                return str_contains(strtolower($persona['nombre_completo']), $textoBusqueda) ||
                       str_contains(strtolower($persona['dni'] ?? ''), $textoBusqueda) ||
                       str_contains(strtolower($persona['correo'] ?? ''), $textoBusqueda);
            });
        }

        // Ordenar: primero externos sin registro, luego pacientes por apellido
        $listaPersonas = $listaPersonas->sortBy(function ($persona) {
            // Prioridad 1: Externos sin registro (estado pendiente)
            if ($persona['estado'] === 'pendiente_registro') {
                return '0_' . strtolower($persona['nombre_completo']);
            }
            // Prioridad 2: Pacientes sin historial
            if (!$persona['tiene_historial']) {
                return '1_' . strtolower($persona['apellidos']);
            }
            // Prioridad 3: Pacientes con historial
            return '2_' . strtolower($persona['apellidos']);
        })->values();

        return $this->successResponse([
            'personas' => $listaPersonas,
            'total' => $listaPersonas->count(),
            'estadisticas' => [
                'externos_sin_registro' => $listaPersonas->where('tipo', 'externo')->count(),
                'pacientes_sin_historial' => $listaPersonas->where('tipo', 'paciente')->where('tiene_historial', false)->count(),
                'pacientes_con_historial' => $listaPersonas->where('tipo', 'paciente')->where('tiene_historial', true)->count(),
            ],
            'medico' => [
                'id_medico' => $medico->id_medico,
                'nombres' => $medico->nombres,
                'apellidos' => $medico->apellidos,
            ]
        ], 'Lista de personas atendidas obtenida exitosamente.');
    }

    /**
     * Ver historial clínico específico
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $medico = Medico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$medico) {
            return $this->errorResponse('No se encontró el perfil de médico.', 404);
        }

        $historial = HistorialClinico::with([
            'paciente.usuario:id_usuario,telefono,correo',
            'medicoResponsable',
            'detalles.realizadoPor',
            'tratamientos.tratamiento',
            'tratamientos.seguimientos.registradoPor',
            'odontograma',
            'prescripciones'
        ])->find($id);

        if (!$historial) {
            return $this->errorResponse('Historial clínico no encontrado.', 404);
        }

        // Verificar permisos según tipo de médico
        if (!in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            // Si es especialista, verificar que haya atendido a este paciente
            $haAtendido = Cita::where('id_medico', $medico->id_medico)
                ->where('id_paciente', $historial->id_paciente)
                ->exists();

            if (!$haAtendido) {
                return $this->errorResponse('No tiene permisos para ver este historial clínico.', 403);
            }
        }
        // Si es médico de cabecera, puede ver cualquier historial
        return $this->successResponse([
            'historial' => $historial
        ]);
    }

    /**
     * Crear historial clínico para un paciente o usuario externo
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $medico = Medico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$medico) {
            return $this->errorResponse('No se encontró el perfil de médico.', 404);
        }

        // Validación inicial - ahora acepta id_paciente O id_usuario_externo
        $validator = Validator::make($request->all(), [
            'id_paciente' => 'nullable|exists:pacientes,id_paciente',
            'id_usuario_externo' => 'nullable|exists:usuarios,id_usuario',
            'motivo_consulta' => 'nullable|string|max:1000',
            'diagnostico_presuntivo' => 'nullable|string|max:1000',
            'diagnostico_principal' => 'nullable|string|max:1000',
            'higiene_bucal' => 'nullable|in:Bueno,Regular,Malo',
            // Campos de anamnesis
            'sintoma_principal' => 'nullable|string|max:500',
            'tiempo_inicio_sintomas' => 'nullable|string|max:100',
            'tratamiento_previo' => 'nullable|string|max:1000',
            'enfermedades_actuales' => 'nullable|string|max:1000',
            'bajo_tratamiento_medico' => 'nullable|boolean',
            'detalle_tratamiento_actual' => 'nullable|string|max:1000',
            'alergias_paciente' => 'nullable|string|max:1000',
            'intervenciones_quirurgicas_previas' => 'nullable|boolean',
            'detalle_intervenciones' => 'nullable|string|max:1000',
            'hemorragia_post_tratamiento' => 'nullable|boolean',
            'problema_anestesia' => 'nullable|boolean',
            'dificultad_abrir_masticar' => 'nullable|boolean',
            'sensibilidad_dental' => 'nullable|boolean',
            // Datos del paciente (requeridos si es usuario externo sin registro de paciente)
            'datos_paciente' => 'nullable|array',
            'datos_paciente.apellidos' => 'required_with:datos_paciente|string|max:100',
            'datos_paciente.nombres' => 'required_with:datos_paciente|string|max:100',
            'datos_paciente.dni' => 'nullable|string|max:20',
            'datos_paciente.fecha_nacimiento' => 'nullable|date',
            'datos_paciente.sexo' => 'nullable|in:M,F,Otro',
            'datos_paciente.domicilio' => 'nullable|string|max:200',
            'datos_paciente.persona_responsable' => 'nullable|string|max:100',
            'datos_paciente.telefono_responsable' => 'nullable|string|max:20',
            'datos_paciente.grupo_sanguineo' => 'nullable|string|max:5',
            'datos_paciente.alergias' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Validar que se envíe al menos uno: id_paciente o id_usuario_externo
        if (!$request->id_paciente && !$request->id_usuario_externo) {
            return $this->errorResponse('Debe proporcionar id_paciente o id_usuario_externo.', 400);
        }

        // Determinar el usuario objetivo
        $usuarioObjetivo = null;
        $idPaciente = null;

        if ($request->id_paciente) {
            // Caso 1: Se proporciona un id_paciente existente
            $paciente = Paciente::with('usuario:id_usuario,correo,telefono')->find($request->id_paciente);
            $usuarioObjetivo = $paciente->usuario;
            $idPaciente = $paciente->id_paciente;

        } elseif ($request->id_usuario_externo) {
            // Caso 2: Se proporciona un id_usuario_externo (usuario sin registro de paciente)
            $usuarioObjetivo = User::find($request->id_usuario_externo);

            if (!$usuarioObjetivo) {
                return $this->errorResponse('Usuario no encontrado.', 404);
            }

            // Si es usuario externo sin registro de paciente, requerir datos_paciente
            $pacienteExistente = Paciente::with('usuario:id_usuario,correo,telefono')->where('id_usuario', $usuarioObjetivo->id_usuario)->first();
            if (!$pacienteExistente && !$request->datos_paciente) {
                return $this->errorResponse('Se requieren los datos del paciente (datos_paciente) para crear el historial.', 400);
            }
        }

        // Preparar datos del historial
        $datosHistorial = [
            'motivo_consulta' => $request->motivo_consulta,
            'diagnostico_presuntivo' => $request->diagnostico_presuntivo,
            'diagnostico_principal' => $request->diagnostico_principal,
            'higiene_bucal' => $request->higiene_bucal,
            'sintoma_principal' => $request->sintoma_principal,
            'tiempo_inicio_sintomas' => $request->tiempo_inicio_sintomas,
            'tratamiento_previo' => $request->tratamiento_previo,
            'enfermedades_actuales' => $request->enfermedades_actuales,
            'bajo_tratamiento_medico' => $request->bajo_tratamiento_medico,
            'detalle_tratamiento_actual' => $request->detalle_tratamiento_actual,
            'alergias_paciente' => $request->alergias_paciente,
            'intervenciones_quirurgicas_previas' => $request->intervenciones_quirurgicas_previas,
            'detalle_intervenciones' => $request->detalle_intervenciones,
            'hemorragia_post_tratamiento' => $request->hemorragia_post_tratamiento,
            'problema_anestesia' => $request->problema_anestesia,
            'dificultad_abrir_masticar' => $request->dificultad_abrir_masticar,
            'sensibilidad_dental' => $request->sensibilidad_dental,
        ];

        // Usar el servicio para crear el historial
        $resultado = $this->historialService->crearHistorialYConvertirAPaciente(
            $usuarioObjetivo,
            $medico->id_medico,
            $datosHistorial,
            $request->datos_paciente
        );

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message'], 400);
        }

        return $this->successResponse(
            [
                'historial' => $resultado['historial'],
                'paciente' => $resultado['paciente'],
            ],
            $resultado['message'],
            201
        );
    }

    /**
     * Actualizar historial clínico
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $medico = Medico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$medico) {
            return $this->errorResponse('No se encontró el perfil de médico.', 404);
        }

        $historial = HistorialClinico::find($id);

        if (!$historial) {
            return $this->errorResponse('Historial clínico no encontrado.', 404);
        }

        // Verificar permisos según tipo de médico
        $esResponsable = $historial->id_medico_responsable === $medico->id_medico;
        $esCabecera = in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde']);
        
        if (!$esResponsable && !$esCabecera) {
            // Si no es el responsable ni médico de cabecera, verificar que tenga citas con el paciente
            $haAtendido = Cita::where('id_medico', $medico->id_medico)
                ->where('id_paciente', $historial->id_paciente)
                ->exists();
                
            if (!$haAtendido) {
                return $this->errorResponse('No tiene permisos para editar este historial.', 403);
            }
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'motivo_consulta' => 'nullable|string|max:1000',
            'diagnostico_presuntivo' => 'nullable|string|max:1000',
            'diagnostico_principal' => 'nullable|string|max:1000',
            'higiene_bucal' => 'nullable|in:Bueno,Regular,Malo',
            'sintoma_principal' => 'nullable|string|max:500',
            'tiempo_inicio_sintomas' => 'nullable|string|max:100',
            'tratamiento_previo' => 'nullable|string|max:1000',
            'enfermedades_actuales' => 'nullable|string|max:1000',
            'bajo_tratamiento_medico' => 'nullable|boolean',
            'detalle_tratamiento_actual' => 'nullable|string|max:1000',
            'alergias_paciente' => 'nullable|string|max:1000',
            'intervenciones_quirurgicas_previas' => 'nullable|boolean',
            'detalle_intervenciones' => 'nullable|string|max:1000',
            'hemorragia_post_tratamiento' => 'nullable|boolean',
            'problema_anestesia' => 'nullable|boolean',
            'dificultad_abrir_masticar' => 'nullable|boolean',
            'sensibilidad_dental' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $historial->update($request->all());

        return $this->successResponse([
            'historial' => $historial
        ], 'Historial actualizado exitosamente.');
    }

    /**
     * Obtener historial clínico del paciente autenticado
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function miHistorial()
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $paciente = Paciente::with('usuario:id_usuario,correo,telefono')->where('id_usuario', $usuario->id_usuario)->first();

        if (!$paciente) {
            return $this->successResponse([
                'tiene_historial' => false,
                'paciente' => null,
                'mensaje' => 'Aún no tienes un registro de paciente. Tu médico debe crear tu historial clínico.'
            ]);
        }

        $historial = HistorialClinico::with([
            'paciente.usuario:id_usuario,correo,telefono',
            'medicoResponsable',
            'detalles',
            'tratamientos.tratamiento',
            'odontograma',
            'prescripciones'
        ])->where('id_paciente', $paciente->id_paciente)->first();

        if (!$historial) {
            return $this->successResponse([
                'tiene_historial' => false,
                'paciente' => $paciente,
                'mensaje' => 'Aún no tienes un historial clínico registrado.'
            ]);
        }

        return $this->successResponse([
            'tiene_historial' => true,
            'historial' => $historial
        ]);
    }
}
