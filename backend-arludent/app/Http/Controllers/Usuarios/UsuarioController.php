<?php

namespace App\Http\Controllers\Usuarios;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\LogActividad;
use App\Http\Controllers\Controller;
use App\Services\HistorialClinicoService;

/**
 * Controlador de Usuario
 *
 * Gestiona el perfil y datos del usuario autenticado
 */
class UsuarioController extends Controller
{
    private HistorialClinicoService $historialService;

    public function __construct(HistorialClinicoService $historialService)
    {
        $this->historialService = $historialService;
    }
    /**
     * Obtener perfil del usuario autenticado
     */
    public function getPerfil()
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        return $this->successResponse([
            'user' => $usuario->load(['roles', 'paciente', 'medico'])
        ]);
    }

    /**
     * Actualizar perfil del usuario autenticado
     */
    public function updatePerfil(Request $request)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        // Validar datos
        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|max:50|unique:usuarios,username,' . $usuario->id_usuario . ',id_usuario',
            'telefono' => 'nullable|string|max:20',
        ], [
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'username.max' => 'El nombre de usuario no puede tener más de 50 caracteres.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Actualizar solo los campos permitidos
        $datosActualizar = [];

        if ($request->has('username')) {
            $datosActualizar['username'] = $request->username;
        }

        if ($request->has('telefono')) {
            $datosActualizar['telefono'] = $request->telefono;
        }

        // Si no hay nada que actualizar
        if (empty($datosActualizar)) {
            return $this->errorResponse('No hay datos para actualizar.', 400);
        }

        // Actualizar usuario
        $usuario->update($datosActualizar);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'actualizar_perfil',
            'modulo_afectado' => 'usuarios',
            'descripcion' => 'Perfil actualizado: ' . implode(', ', array_keys($datosActualizar)),
            'ip_usuario' => $request->ip(),
        ]);

        return $this->successResponse(
            [
                'user' => $usuario->fresh()->load(['roles', 'paciente', 'medico'])
            ],
            'Perfil actualizado exitosamente.'
        );
    }

    /**
     * Obtener estado de registro del usuario autenticado
     *
     * Indica si el usuario es externo/paciente y si tiene historial clínico
     */
    public function getEstadoRegistro()
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $estado = $this->historialService->obtenerEstadoRegistro($usuario);

        return $this->successResponse([
            'estado' => $estado
        ], 'Estado de registro obtenido exitosamente.');
    }
}
