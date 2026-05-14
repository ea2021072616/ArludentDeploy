<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use App\Models\HistorialClinico;
use App\Models\TratamientoHistorial;
use App\Models\Tratamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TratamientoHistorialController extends Controller
{
    /**
     * Agregar un tratamiento al historial
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
                'message' => 'Solo médicos pueden agregar tratamientos'
            ], 403);
        }

        $medico = $user->medico;

        // Validar datos
        $validated = $request->validate([
            'id_tratamiento' => 'required|exists:tratamientos,id_tratamiento',
            'descripcion' => 'nullable|string|max:1000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:sugerido,en_curso,completado,cancelado',
            'precio' => 'nullable|numeric|min:0',
            'id_detalle_historial' => 'nullable|exists:detalle_historial,id_detalle'
        ]);

        // Crear tratamiento
        $tratamiento = new TratamientoHistorial();
        $tratamiento->id_historial = $historial->id_historial;
        $tratamiento->id_tratamiento = $validated['id_tratamiento'];
        $tratamiento->descripcion = $validated['descripcion'] ?? null;
        $tratamiento->fecha_inicio = $validated['fecha_inicio'] ?? null;
        $tratamiento->fecha_fin = $validated['fecha_fin'] ?? null;
        $tratamiento->estado = $validated['estado'];
        $tratamiento->precio = $validated['precio'] ?? null;
        $tratamiento->realizado_por = $medico->id_medico;
        $tratamiento->id_detalle_historial = $validated['id_detalle_historial'] ?? null;
        $tratamiento->save();

        // Cargar relación del tratamiento
        $tratamiento->load('tratamiento');

        return response()->json([
            'success' => true,
            'message' => 'Tratamiento agregado correctamente',
            'data' => [
                'tratamiento' => $tratamiento
            ]
        ], 201);
    }

    /**
     * Actualizar un tratamiento existente
     */
    public function update(Request $request, $idTratamiento)
    {
        $tratamiento = TratamientoHistorial::findOrFail($idTratamiento);

        // Verificar permisos
        $user = Auth::user();
        if (!$user->medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo médicos pueden editar tratamientos'
            ], 403);
        }

        $medico = $user->medico;

        // Solo el médico que creó el tratamiento puede editarlo
        if ($tratamiento->realizado_por !== $medico->id_medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puede editar sus propios tratamientos'
            ], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'descripcion' => 'nullable|string|max:1000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'estado' => 'sometimes|in:sugerido,en_curso,completado,cancelado',
            'precio' => 'nullable|numeric|min:0'
        ]);

        // Actualizar
        $tratamiento->update($validated);
        $tratamiento->load('tratamiento');

        return response()->json([
            'success' => true,
            'message' => 'Tratamiento actualizado correctamente',
            'data' => [
                'tratamiento' => $tratamiento
            ]
        ]);
    }

    /**
     * Eliminar un tratamiento
     */
    public function destroy($idTratamiento)
    {
        $tratamiento = TratamientoHistorial::findOrFail($idTratamiento);

        // Verificar permisos
        $user = Auth::user();
        if (!$user->medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo médicos pueden eliminar tratamientos'
            ], 403);
        }

        $medico = $user->medico;

        // Solo el médico que creó el tratamiento puede eliminarlo
        if ($tratamiento->realizado_por !== $medico->id_medico) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puede eliminar sus propios tratamientos'
            ], 403);
        }

        $tratamiento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tratamiento eliminado correctamente'
        ]);
    }

    /**
     * Obtener catálogo de tratamientos disponibles
     */
    public function catalogoTratamientos()
    {
        $tratamientos = Tratamiento::where('estado', 'activo')
            ->select('id_tratamiento', 'nombre', 'descripcion', 'precio_actual as precio', 'categoria')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'tratamientos' => $tratamientos
            ]
        ]);
    }
}
