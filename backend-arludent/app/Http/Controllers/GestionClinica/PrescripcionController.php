<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use App\Models\HistorialClinico;
use App\Models\Prescripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescripcionController extends Controller
{
    /**
     * Agregar una prescripción al historial
     */
    public function store(Request $request, $idHistorial)
    {
        // Verificar que el historial existe
        $historial = HistorialClinico::findOrFail($idHistorial);

        // Verificar permisos del médico
        $user = Auth::user();
        if (!$user->medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo médicos pueden prescribir medicamentos'
            ], 403);
        }

        $medico = $user->medico;

        // Validar datos
        $validated = $request->validate([
            'medicamento' => 'required|string|max:255',
            'dosis' => 'nullable|string|max:100',
            'frecuencia' => 'nullable|string|max:100',
            'duracion' => 'nullable|string|max:100',
            'indicaciones' => 'nullable|string|max:1000',
            'fecha_prescripcion' => 'required|date'
        ]);

        // Crear prescripción
        $prescripcion = new Prescripcion();
        $prescripcion->id_historial = $historial->id_historial;
        $prescripcion->medicamento = $validated['medicamento'];
        $prescripcion->dosis = $validated['dosis'] ?? null;
        $prescripcion->frecuencia = $validated['frecuencia'] ?? null;
        $prescripcion->duracion = $validated['duracion'] ?? null;
        $prescripcion->indicaciones = $validated['indicaciones'] ?? null;
        $prescripcion->fecha_prescripcion = $validated['fecha_prescripcion'];
        $prescripcion->prescrito_por = $medico->id_medico;
        $prescripcion->save();

        // Cargar relación del médico
        $prescripcion->load('prescritoPor');

        return response()->json([
            'success' => true,
            'message' => 'Prescripción agregada correctamente',
            'data' => [
                'prescripcion' => $prescripcion
            ]
        ], 201);
    }

    /**
     * Actualizar una prescripción existente
     */
    public function update(Request $request, $idPrescripcion)
    {
        $prescripcion = Prescripcion::findOrFail($idPrescripcion);

        // Verificar permisos
        $user = Auth::user();
        if (!$user->medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo médicos pueden editar prescripciones'
            ], 403);
        }

        $medico = $user->medico;

        // Solo el médico que creó la prescripción puede editarla
        if ($prescripcion->prescrito_por !== $medico->id_medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puede editar sus propias prescripciones'
            ], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'medicamento' => 'sometimes|string|max:255',
            'dosis' => 'nullable|string|max:100',
            'frecuencia' => 'nullable|string|max:100',
            'duracion' => 'nullable|string|max:100',
            'indicaciones' => 'nullable|string|max:1000',
            'fecha_prescripcion' => 'sometimes|date'
        ]);

        // Actualizar
        $prescripcion->update($validated);
        $prescripcion->load('prescritoPor');

        return response()->json([
            'success' => true,
            'message' => 'Prescripción actualizada correctamente',
            'data' => [
                'prescripcion' => $prescripcion
            ]
        ]);
    }

    /**
     * Eliminar una prescripción
     */
    public function destroy($idPrescripcion)
    {
        $prescripcion = Prescripcion::findOrFail($idPrescripcion);

        // Verificar permisos
        $user = Auth::user();
        if (!$user->medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo médicos pueden eliminar prescripciones'
            ], 403);
        }

        $medico = $user->medico;

        // Solo el médico que creó la prescripción puede eliminarla
        if ($prescripcion->prescrito_por !== $medico->id_medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puede eliminar sus propias prescripciones'
            ], 403);
        }

        $prescripcion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Prescripción eliminada correctamente'
        ]);
    }
}
