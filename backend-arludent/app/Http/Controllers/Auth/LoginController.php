<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Controlador de Login.
 *
 * Gestiona la autenticación de usuarios mediante JWT,
 * incluyendo login, logout, refresh de tokens y consulta del usuario actual.
 */
class LoginController extends Controller
{
    /** Factor de conversión de minutos (TTL de JWT) a segundos. */
    private const SECONDS_PER_MINUTE = 60;

    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Inicia sesión con credenciales.
     *
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Autenticación"},
     *     summary="Iniciar sesión",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"correo","password"},
     *             @OA\Property(property="correo", type="string", example="juan@example.com", description="Correo electrónico o nombre de usuario"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciales inválidas")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->authService->login(
            $request->correo,
            $request->password
        );

        if (!$resultado['success']) {
            return $this->unauthorizedResponse($resultado['message']);
        }

        if ($resultado['requires_mfa']) {
            return $this->successResponse(
                [
                    'requires_mfa' => true,
                    'user_id' => $resultado['user']->id_usuario,
                ],
                $resultado['message']
            );
        }

        return $this->successResponse(
            $this->buildTokenResponse($resultado['token'], $resultado['user']),
            $resultado['message']
        );
    }

    /**
     * Cierra sesión invalidando el token JWT actual.
     *
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Autenticación"},
     *     summary="Cerrar sesión",
     *     security={{"bearer":{}}},
     *     @OA\Response(response=200, description="Sesión cerrada exitosamente")
     * )
     */
    public function logout(): JsonResponse
    {
        $resultado = $this->authService->logout();

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message']);
        }

        return $this->successResponse(null, $resultado['message']);
    }

    /**
     * Refresca el token JWT actual por uno nuevo.
     *
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Autenticación"},
     *     summary="Refrescar token",
     *     security={{"bearer":{}}},
     *     @OA\Response(response=200, description="Token refrescado")
     * )
     */
    public function refresh(): JsonResponse
    {
        $resultado = $this->authService->refreshToken();

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message']);
        }

        return $this->successResponse(
            $this->buildTokenResponse($resultado['token']),
            $resultado['message']
        );
    }

    /**
     * Obtiene los datos del usuario autenticado con sus roles.
     *
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Autenticación"},
     *     summary="Obtener usuario actual",
     *     security={{"bearer":{}}},
     *     @OA\Response(response=200, description="Usuario actual")
     * )
     */
    public function me(): JsonResponse
    {
        $usuario = $this->authService->usuarioAutenticado();

        if (!$usuario) {
            return $this->unauthorizedResponse('No autenticado');
        }

        return $this->successResponse($usuario->load('roles'));
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Construye la estructura estándar de respuesta para tokens JWT.
     *
     * @param string $token Token JWT generado.
     * @param mixed $usuario Datos del usuario (opcional, para login completo).
     */
    private function buildTokenResponse(string $token, mixed $usuario = null): array
    {
        $respuesta = [
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * self::SECONDS_PER_MINUTE,
        ];

        if ($usuario !== null) {
            $respuesta['user'] = $usuario;
        }

        return $respuesta;
    }
}
