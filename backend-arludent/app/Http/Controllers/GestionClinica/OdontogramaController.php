<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Odontograma;
use App\Models\HistorialClinico;
use App\Models\Medico;
use App\Models\Cita;

/**
 * Controlador de Odontograma
 *
 * Gestiona el diagrama dental de los pacientes
 */
class OdontogramaController extends Controller
{
    /**
     * Obtener odontograma por historial
     *
     * @param int $idHistorial
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByHistorial($idHistorial)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $medico = Medico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$medico) {
            return $this->errorResponse('No se encontró el perfil de médico.', 404);
        }

        $historial = HistorialClinico::find($idHistorial);

        if (!$historial) {
            return $this->errorResponse('Historial clínico no encontrado.', 404);
        }

        // Verificar permisos
        $esResponsable = $historial->id_medico_responsable === $medico->id_medico;
        $haAtendido = Cita::where('id_medico', $medico->id_medico)
            ->where('id_paciente', $historial->id_paciente)
            ->exists();

        if (!$esResponsable && !$haAtendido) {
            return $this->errorResponse('No tiene permisos para ver este odontograma.', 403);
        }

        $odontograma = Odontograma::where('id_historial', $idHistorial)->get();

        return $this->successResponse([
            'odontograma' => $odontograma,
            'total_piezas' => $odontograma->count(),
        ]);
    }

    /**
     * Registrar o actualizar pieza dental
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

        // Validación
        $validator = Validator::make($request->all(), [
            'id_historial' => 'required|exists:historial_clinico,id_historial',
            'pieza' => 'required|string|max:10',
            'estado_pieza' => 'required|in:sano,cariado,restaurado,ausente,protesis,otros',
            'tratamiento_asociado' => 'nullable|string|max:255',
            'comentario' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $historial = HistorialClinico::find($request->id_historial);

        // Verificar permisos
        $esResponsable = $historial->id_medico_responsable === $medico->id_medico;
        if (!$esResponsable) {
            return $this->errorResponse('No tiene permisos para modificar este odontograma.', 403);
        }

        // Buscar si ya existe la pieza
        $odontograma = Odontograma::where('id_historial', $request->id_historial)
            ->where('pieza', $request->pieza)
            ->first();

        if ($odontograma) {
            // Actualizar pieza existente
            $odontograma->update([
                'estado_pieza' => $request->estado_pieza,
                'tratamiento_asociado' => $request->tratamiento_asociado,
                'comentario' => $request->comentario,
            ]);
            $mensaje = 'Pieza dental actualizada exitosamente.';
        } else {
            // Crear nueva pieza
            $odontograma = Odontograma::create([
                'id_historial' => $request->id_historial,
                'pieza' => $request->pieza,
                'estado_pieza' => $request->estado_pieza,
                'tratamiento_asociado' => $request->tratamiento_asociado,
                'comentario' => $request->comentario,
            ]);
            $mensaje = 'Pieza dental registrada exitosamente.';
        }

        return $this->successResponse([
            'odontograma' => $odontograma
        ], $mensaje, 201);
    }

    /**
     * Actualizar pieza dental
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

        $odontograma = Odontograma::find($id);

        if (!$odontograma) {
            return $this->errorResponse('Registro de odontograma no encontrado.', 404);
        }

        $historial = HistorialClinico::find($odontograma->id_historial);

        // Verificar permisos
        $esResponsable = $historial->id_medico_responsable === $medico->id_medico;
        if (!$esResponsable) {
            return $this->errorResponse('No tiene permisos para modificar este odontograma.', 403);
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'estado_pieza' => 'sometimes|in:sano,cariado,restaurado,ausente,protesis,otros',
            'tratamiento_asociado' => 'nullable|string|max:255',
            'comentario' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $odontograma->update($request->all());

        return $this->successResponse([
            'odontograma' => $odontograma
        ], 'Pieza dental actualizada exitosamente.');
    }

    /**
     * Eliminar registro de pieza dental
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $medico = Medico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$medico) {
            return $this->errorResponse('No se encontró el perfil de médico.', 404);
        }

        $odontograma = Odontograma::find($id);

        if (!$odontograma) {
            return $this->errorResponse('Registro de odontograma no encontrado.', 404);
        }

        $historial = HistorialClinico::find($odontograma->id_historial);

        // Verificar permisos
        $esResponsable = $historial->id_medico_responsable === $medico->id_medico;
        if (!$esResponsable) {
            return $this->errorResponse('No tiene permisos para eliminar este registro.', 403);
        }

        $odontograma->delete();

        return $this->successResponse([], 'Registro de pieza dental eliminado exitosamente.');
    }
}
