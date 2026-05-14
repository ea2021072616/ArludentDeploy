<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Recuperación y Cambio de Contraseña.
 *
 * Gestiona la solicitud de recuperación de contraseña por correo,
 * el restablecimiento mediante token, y el cambio de contraseña
 * para usuarios autenticados.
 */
class PasswordResetController extends Controller
{
    private PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Solicita recuperación de contraseña enviando un correo con token.
     *
     * @OA\Post(
     *     path="/api/auth/recuperar-password",
     *     tags={"Autenticación"},
     *     summary="Solicitar recuperación de contraseña",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"correo"},
     *             @OA\Property(property="correo", type="string", format="email", example="juan@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Correo de recuperación enviado")
     * )
     */
    public function requestReset(Request $request): JsonResponse
    {
        $request->validate([
            'correo' => 'required|email',
        ]);

        $resultado = $this->passwordResetService->iniciarRecuperacion($request->correo);

        return $this->successResponse(null, $resultado['message']);
    }

    /**
     * Restablece la contraseña usando el token de recuperación.
     *
     * @OA\Post(
     *     path="/api/auth/restablecer-password",
     *     tags={"Autenticación"},
     *     summary="Restablecer contraseña",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"correo","token","password","password_confirmation"},
     *             @OA\Property(property="correo", type="string", format="email"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Contraseña restablecida exitosamente")
     * )
     */
    public function reset(PasswordResetRequest $request): JsonResponse
    {
        $resultado = $this->passwordResetService->restablecerPassword(
            $request->correo,
            $request->token,
            $request->password
        );

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message'], Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse(null, $resultado['message']);
    }

    /**
     * Cambia la contraseña del usuario autenticado validando la contraseña actual.
     *
     * @OA\Post(
     *     path="/api/auth/cambiar-password",
     *     tags={"Autenticación"},
     *     summary="Cambiar contraseña",
     *     security={{"bearer":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password_actual","password","password_confirmation"},
     *             @OA\Property(property="password_actual", type="string", format="password"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Contraseña cambiada exitosamente")
     * )
     */
    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $resultado = $this->passwordResetService->cambiarPassword(
            $usuario,
            $request->password_actual,
            $request->password
        );

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message'], Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse(null, $resultado['message']);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene el usuario actualmente autenticado con type-hint seguro.
     */
    private function obtenerUsuarioAutenticado(): User
    {
        /** @var User */
        return Auth::user();
    }
}
