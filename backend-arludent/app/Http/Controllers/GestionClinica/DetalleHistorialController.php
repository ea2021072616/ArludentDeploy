<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use App\Models\HistorialClinico;
use App\Models\DetalleHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DetalleHistorialController extends Controller
{
    /**
     * Agregar una nueva consulta/detalle al historial
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
                'message' => 'Solo médicos pueden agregar consultas'
            ], 403);
        }

        $medico = $user->medico;

        // Validar datos
        $validated = $request->validate([
            'fecha' => 'required|date',
            'diagnostico' => 'nullable|string|max:500',
            'procedimiento_realizado' => 'nullable|string|max:1000',
            'notas_medicas' => 'nullable|string|max:2000',
            'id_cita' => 'nullable|exists:citas,id_cita'
        ]);

        // Crear detalle
        $detalle = new DetalleHistorial();
        $detalle->id_historial = $historial->id_historial;
        $detalle->fecha = $validated['fecha'];
        $detalle->diagnostico = $validated['diagnostico'] ?? null;
        $detalle->procedimiento_realizado = $validated['procedimiento_realizado'] ?? null;
        $detalle->notas_medicas = $validated['notas_medicas'] ?? null;
        $detalle->realizado_por = $medico->id_medico;
        $detalle->id_cita = $validated['id_cita'] ?? null;
        $detalle->save();

        // Cargar relación del médico
        $detalle->load('realizadoPor');

        return response()->json([
            'success' => true,
            'message' => 'Consulta agregada correctamente',
            'data' => [
                'detalle' => $detalle
            ]
        ], 201);
    }

    /**
     * Actualizar un detalle existente
     */
    public function update(Request $request, $idDetalle)
    {
        $detalle = DetalleHistorial::findOrFail($idDetalle);

        // Verificar permisos
        $user = Auth::user();
        if (!$user->medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo médicos pueden editar consultas'
            ], 403);
        }

        $medico = $user->medico;

        // Solo el médico que creó la consulta puede editarla
        if ($detalle->realizado_por !== $medico->id_medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puede editar sus propias consultas'
            ], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'fecha' => 'sometimes|date',
            'diagnostico' => 'nullable|string|max:500',
            'procedimiento_realizado' => 'nullable|string|max:1000',
            'notas_medicas' => 'nullable|string|max:2000'
        ]);

        // Actualizar
        $detalle->update($validated);
        $detalle->load('realizadoPor');

        return response()->json([
            'success' => true,
            'message' => 'Consulta actualizada correctamente',
            'data' => [
                'detalle' => $detalle
            ]
        ]);
    }

    /**
     * Eliminar un detalle
     */
    public function destroy($idDetalle)
    {
        $detalle = DetalleHistorial::findOrFail($idDetalle);

        // Verificar permisos
        $user = Auth::user();
        if (!$user->medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo médicos pueden eliminar consultas'
            ], 403);
        }

        $medico = $user->medico;

        // Solo el médico que creó la consulta puede eliminarla
        if ($detalle->realizado_por !== $medico->id_medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puede eliminar sus propias consultas'
            ], 403);
        }

        $detalle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Consulta eliminada correctamente'
        ]);
    }
}
