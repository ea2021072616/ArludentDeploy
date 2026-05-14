<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use App\Models\SeguimientoTratamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SeguimientoTratamientoController extends Controller
{
    /**
     * Agregar un nuevo seguimiento a un tratamiento
     */
    public function store(Request $request, $idHistorial, $idTratamientoHistorial)
    {
        $validator = Validator::make($request->all(), [
            'fecha_registro' => 'required|date',
            'descripcion' => 'required|string',
            'duracion_restante' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar que el usuario sea médico
        $user = Auth::user();
        $medico = $user->medico;

        if (!$medico) {
            return response()->json(['message' => 'Solo los médicos pueden agregar seguimientos'], 403);
        }

        $seguimiento = SeguimientoTratamiento::create([
            'id_historial' => $idHistorial,
            'id_tratamiento_historial' => $idTratamientoHistorial,
            'fecha_registro' => $request->fecha_registro,
            'descripcion' => $request->descripcion,
            'duracion_restante' => $request->duracion_restante,
            'registrado_por' => $medico->id_medico,
        ]);

        return response()->json([
            'message' => 'Seguimiento agregado correctamente',
            'seguimiento' => $seguimiento->load('registradoPor')
        ], 201);
    }

    /**
     * Actualizar un seguimiento existente
     */
    public function update(Request $request, $idHistorial, $idTratamientoHistorial, $idSeguimiento)
    {
        $seguimiento = SeguimientoTratamiento::where('id_seguimiento', $idSeguimiento)
            ->where('id_historial', $idHistorial)
            ->where('id_tratamiento_historial', $idTratamientoHistorial)
            ->firstOrFail();

        // Verificar que el médico que actualiza sea el mismo que registró
        $user = Auth::user();
        $medico = $user->medico;

        if (!$medico || $seguimiento->registrado_por !== $medico->id_medico) {
            return response()->json(['message' => 'No tiene permiso para modificar este seguimiento'], 403);
        }

        $validator = Validator::make($request->all(), [
            'fecha_registro' => 'sometimes|date',
            'descripcion' => 'sometimes|string',
            'duracion_restante' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $seguimiento->update($request->only(['fecha_registro', 'descripcion', 'duracion_restante']));

        return response()->json([
            'message' => 'Seguimiento actualizado correctamente',
            'seguimiento' => $seguimiento->load('registradoPor')
        ]);
    }

    /**
     * Eliminar un seguimiento
     */
    public function destroy($idHistorial, $idTratamientoHistorial, $idSeguimiento)
    {
        $seguimiento = SeguimientoTratamiento::where('id_seguimiento', $idSeguimiento)
            ->where('id_historial', $idHistorial)
            ->where('id_tratamiento_historial', $idTratamientoHistorial)
            ->firstOrFail();

        // Verificar que el médico que elimina sea el mismo que registró
        $user = Auth::user();
        $medico = $user->medico;

        if (!$medico || $seguimiento->registrado_por !== $medico->id_medico) {
            return response()->json(['message' => 'No tiene permiso para eliminar este seguimiento'], 403);
        }

        $seguimiento->delete();

        return response()->json(['message' => 'Seguimiento eliminado correctamente']);
    }
}
