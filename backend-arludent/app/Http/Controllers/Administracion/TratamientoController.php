<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Tratamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\LogActividad;

/**
 * Controlador de Tratamientos
 *
 * Gestión del catálogo de tratamientos que ofrece la clínica
 * Solo accesible por administradores
 */
class TratamientoController extends Controller
{
    /**
     * Listar todos los tratamientos
     */
    public function index(Request $request)
    {
        $query = Tratamiento::query();

        // Filtrar por categoría
        if ($request->has('categoria') && $request->categoria) {
            $query->where('categoria', $request->categoria);
        }

        // Filtrar por estado
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        // Búsqueda por nombre o descripción
        if ($request->has('busqueda') && $request->busqueda) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%")
                  ->orWhere('categoria', 'like', "%{$busqueda}%");
            });
        }

        // Ordenar
        $query->orderBy('categoria', 'asc')
              ->orderBy('nombre', 'asc');

        // Paginación o todo
        if ($request->has('per_page') && $request->per_page === 'all') {
            $tratamientos = $query->get();
        } else {
            $perPage = $request->get('per_page', 15);
            $tratamientos = $query->paginate($perPage);
        }

        return $this->successResponse([
            'tratamientos' => $tratamientos
        ]);
    }

    /**
     * Obtener un tratamiento específico
     */
    public function show($id)
    {
        $tratamiento = Tratamiento::findOrFail($id);

        return $this->successResponse([
            'tratamiento' => $tratamiento
        ]);
    }

    /**
     * Crear nuevo tratamiento
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'categoria' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'precio_actual' => 'nullable|numeric|min:0|max:99999999.99',
            'estado' => 'sometimes|in:activo,inactivo',
        ], [
            'nombre.required' => 'El nombre del tratamiento es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'categoria.max' => 'La categoría no puede exceder 50 caracteres.',
            'precio_actual.numeric' => 'El precio debe ser un número válido.',
            'precio_actual.min' => 'El precio no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $tratamiento = Tratamiento::create([
                'nombre' => $request->nombre,
                'categoria' => $request->categoria,
                'descripcion' => $request->descripcion,
                'precio_actual' => $request->precio_actual,
                'estado' => $request->get('estado', 'activo'),
            ]);

            // Registrar actividad
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            LogActividad::create([
                'id_usuario' => $currentUser ? $currentUser->id_usuario : null,
                'accion' => 'crear_tratamiento',
                'modulo_afectado' => 'tratamientos',
                'descripcion' => "Tratamiento creado: {$tratamiento->nombre}",
                'ip_usuario' => $request->ip(),
            ]);

            return $this->successResponse(
                ['tratamiento' => $tratamiento],
                'Tratamiento creado exitosamente.',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear tratamiento: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar tratamiento
     */
    public function update(Request $request, $id)
    {
        $tratamiento = Tratamiento::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:100',
            'categoria' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'precio_actual' => 'nullable|numeric|min:0|max:99999999.99',
            'estado' => 'sometimes|in:activo,inactivo',
        ], [
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'categoria.max' => 'La categoría no puede exceder 50 caracteres.',
            'precio_actual.numeric' => 'El precio debe ser un número válido.',
            'precio_actual.min' => 'El precio no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $tratamiento->update($request->only([
                'nombre',
                'categoria',
                'descripcion',
                'precio_actual',
                'estado'
            ]));

            // Registrar actividad
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            LogActividad::create([
                'id_usuario' => $currentUser ? $currentUser->id_usuario : null,
                'accion' => 'actualizar_tratamiento',
                'modulo_afectado' => 'tratamientos',
                'descripcion' => "Tratamiento actualizado: {$tratamiento->nombre}",
                'ip_usuario' => $request->ip(),
            ]);

            return $this->successResponse(
                ['tratamiento' => $tratamiento->fresh()],
                'Tratamiento actualizado exitosamente.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar tratamiento: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cambiar estado del tratamiento (activar/inactivar)
     */
    public function cambiarEstado(Request $request, $id)
    {
        $tratamiento = Tratamiento::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $tratamiento->update(['estado' => $request->estado]);

            // Registrar actividad
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            LogActividad::create([
                'id_usuario' => $currentUser ? $currentUser->id_usuario : null,
                'accion' => 'cambiar_estado_tratamiento',
                'modulo_afectado' => 'tratamientos',
                'descripcion' => "Estado de tratamiento '{$tratamiento->nombre}' cambiado a: {$request->estado}",
                'ip_usuario' => $request->ip(),
            ]);

            return $this->successResponse(
                ['tratamiento' => $tratamiento->fresh()],
                "Tratamiento {$request->estado} correctamente."
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Error al cambiar estado: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener categorías únicas
     */
    public function categorias()
    {
        $categorias = Tratamiento::whereNotNull('categoria')
            ->distinct()
            ->pluck('categoria')
            ->filter()
            ->values();

        return $this->successResponse([
            'categorias' => $categorias
        ]);
    }
}
