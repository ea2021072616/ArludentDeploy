<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Cita;
use App\Models\HistorialClinico;

/**
 * Controlador de Pacientes
 *
 * Gestiona los datos de pacientes registrados
 */
class PacienteController extends Controller
{
    /**
     * Listar todos los pacientes del médico
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

        // Verificar el tipo de médico para determinar qué pacientes puede ver
        $query = Paciente::with(['usuario:id_usuario,correo']);

        // Si es médico de cabecera (mañana o tarde), puede ver TODOS los pacientes
        if (in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            // No aplicar filtro de pacientes, ver todos
        } else {
            // Si es médico especialista, solo ver pacientes que tienen citas con él
            $pacientesIds = Cita::where('id_medico', $medico->id_medico)
                ->whereNotNull('id_paciente')
                ->distinct()
                ->pluck('id_paciente');

            $query->whereIn('id_paciente', $pacientesIds);
        }

        // Filtro de búsqueda
        if ($request->busqueda) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->where('nombres', 'like', "%{$busqueda}%")
                  ->orWhere('apellidos', 'like', "%{$busqueda}%")
                  ->orWhere('dni', 'like', "%{$busqueda}%");
            });
        }

        // Filtro por estado
        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        $pacientes = $query->orderBy('apellidos', 'asc')
                           ->orderBy('nombres', 'asc')
                           ->get();

        // Agregar información adicional
        $pacientes = $pacientes->map(function($paciente) {
            $historial = HistorialClinico::where('id_paciente', $paciente->id_paciente)->first();
            $ultimaCita = Cita::where('id_paciente', $paciente->id_paciente)
                              ->orderBy('fecha_hora_inicio', 'desc')
                              ->first();

            return [
                'id_paciente' => $paciente->id_paciente,
                'nombres' => $paciente->nombres,
                'apellidos' => $paciente->apellidos,
                'nombre_completo' => trim($paciente->nombres . ' ' . $paciente->apellidos),
                'dni' => $paciente->dni,
                'fecha_nacimiento' => $paciente->fecha_nacimiento,
                'sexo' => $paciente->sexo,
                'telefono' => $paciente->telefono_responsable,
                'correo' => $paciente->usuario ? $paciente->usuario->correo : null,
                'estado' => $paciente->estado,
                'tiene_historial' => $historial ? true : false,
                'id_historial' => $historial ? $historial->id_historial : null,
                'ultima_cita' => $ultimaCita ? $ultimaCita->fecha_hora_inicio : null,
                'fecha_registro' => $paciente->fecha_registro,
            ];
        });

        return $this->successResponse([
            'pacientes' => $pacientes,
            'total' => $pacientes->count(),
        ]);
    }

    /**
     * Ver datos de un paciente específico
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

        $paciente = Paciente::with(['usuario:id_usuario,correo'])->find($id);

        if (!$paciente) {
            return $this->errorResponse('Paciente no encontrado.', 404);
        }

        // Verificar permisos según tipo de médico
        if (!in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            // Si es especialista, verificar que haya atendido a este paciente
            $haAtendido = Cita::where('id_medico', $medico->id_medico)
                ->where('id_paciente', $paciente->id_paciente)
                ->exists();

            if (!$haAtendido) {
                return $this->errorResponse('No tiene permisos para ver este paciente.', 403);
            }
        }
        // Si es médico de cabecera, puede ver cualquier paciente

        return $this->successResponse([
            'paciente' => $paciente
        ]);
    }

    /**
     * Actualizar datos del paciente
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
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el perfil de médico.',
            ], 404);
        }

        $paciente = Paciente::with('usuario')->find($id);

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado.',
            ], 404);
        }

        // Verificar permisos según tipo de médico
        if (!in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            // Si es especialista, verificar que haya atendido a este paciente
            $haAtendido = Cita::where('id_medico', $medico->id_medico)
                ->where('id_paciente', $paciente->id_paciente)
                ->exists();

            if (!$haAtendido) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para editar este paciente.',
                ], 403);
            }
        }
        // Si es médico de cabecera, puede editar cualquier paciente

        // Validación
        $validator = Validator::make($request->all(), [
            'apellidos' => 'sometimes|string|max:100',
            'nombres' => 'sometimes|string|max:100',
            'dni' => 'sometimes|nullable|string|max:20',
            'fecha_nacimiento' => 'sometimes|nullable|date',
            'sexo' => 'sometimes|nullable|in:M,F,Otro',
            'domicilio' => 'sometimes|nullable|string|max:200',
            'persona_responsable' => 'sometimes|nullable|string|max:100',
            'telefono' => 'sometimes|nullable|string|max:20', // Teléfono del usuario
            'telefono_responsable' => 'sometimes|nullable|string|max:20',
            'grupo_sanguineo' => 'sometimes|nullable|string|max:5',
            'alergias' => 'sometimes|nullable|string|max:1000',
            'estado' => 'sometimes|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Separar datos del paciente y del usuario
        $datosPaciente = $request->except(['telefono']);

        // Actualizar datos del paciente
        $paciente->update($datosPaciente);

        // Si se envió el teléfono del usuario, actualizarlo
        if ($request->has('telefono') && $paciente->usuario) {
            $paciente->usuario->update([
                'telefono' => $request->telefono
            ]);
        }

        // Recargar relación
        $paciente->load('usuario');

        return response()->json([
            'success' => true,
            'message' => 'Datos del paciente actualizados exitosamente.',
            'data' => [
                'paciente' => $paciente
            ]
        ], 200);
    }

    /**
     * Obtener resumen del historial del paciente
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHistorialResumen($id)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $medico = Medico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$medico) {
            return $this->errorResponse('No se encontró el perfil de médico.', 404);
        }

        $paciente = Paciente::find($id);

        if (!$paciente) {
            return $this->errorResponse('Paciente no encontrado.', 404);
        }

        // Verificar permisos según tipo de médico
        if (!in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            // Si es especialista, verificar que haya atendido a este paciente
            $haAtendido = Cita::where('id_medico', $medico->id_medico)
                ->where('id_paciente', $paciente->id_paciente)
                ->exists();

            if (!$haAtendido) {
                return $this->errorResponse('No tiene permisos para ver este paciente.', 403);
            }
        }
        // Si es médico de cabecera, puede ver cualquier paciente

        // Obtener resumen
        $historial = HistorialClinico::where('id_paciente', $paciente->id_paciente)->first();
        $totalCitas = Cita::where('id_paciente', $paciente->id_paciente)->count();
        $citasCompletadas = Cita::where('id_paciente', $paciente->id_paciente)
                                 ->where('estado', 'completado')
                                 ->count();

        return $this->successResponse([
            'paciente' => $paciente,
            'tiene_historial' => $historial ? true : false,
            'id_historial' => $historial ? $historial->id_historial : null,
            'total_citas' => $totalCitas,
            'citas_completadas' => $citasCompletadas,
        ]);
    }
}
