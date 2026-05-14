<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MFAVerifyRequest;
use App\Models\User;
use App\Services\MFAService;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Controlador de MFA (Autenticación de Dos Factores).
 *
 * Gestiona la generación, activación, verificación durante login
 * y desactivación de la autenticación multifactor basada en TOTP.
 */
class MFAController extends Controller
{
    /** Factor de conversión de minutos (TTL de JWT) a segundos. */
    private const SECONDS_PER_MINUTE = 60;

    private MFAService $mfaService;
    private AuthService $authService;

    public function __construct(MFAService $mfaService, AuthService $authService)
    {
        $this->middleware('auth:api')->except(['verifyLogin']);
        $this->mfaService = $mfaService;
        $this->authService = $authService;
    }

    /**
     * Genera el secreto y código QR para configurar MFA.
     *
     * @OA\Post(
     *     path="/api/auth/mfa/generar",
     *     tags={"Autenticación"},
     *     summary="Generar código QR para MFA",
     *     security={{"bearer":{}}},
     *     @OA\Response(response=200, description="QR generado")
     * )
     */
    public function generate(): JsonResponse
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $resultado = $this->mfaService->generarSecretoMFA($usuario);

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message']);
        }

        return $this->successResponse([
            'secret'  => $resultado['secret'],
            'qr_code' => $resultado['qr_code'],
        ], $resultado['message']);
    }

    /**
     * Activa MFA verificando el código TOTP inicial proporcionado por el usuario.
     *
     * @OA\Post(
     *     path="/api/auth/mfa/activar",
     *     tags={"Autenticación"},
     *     summary="Activar MFA",
     *     security={{"bearer":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"codigo"},
     *             @OA\Property(property="codigo", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="MFA activado")
     * )
     */
    public function enable(MFAVerifyRequest $request): JsonResponse
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $resultado = $this->mfaService->activarMFA($usuario, $request->codigo);

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message'], Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse([
            'backup_codes' => null, // Reservado para futura generación de códigos de respaldo
        ], $resultado['message']);
    }

    /**
     * Verifica el código MFA durante el flujo de login y emite un token JWT.
     *
     * @OA\Post(
     *     path="/api/auth/mfa/verificar-login",
     *     tags={"Autenticación"},
     *     summary="Verificar código MFA en login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","codigo"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="codigo", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="MFA verificado, token emitido")
     * )
     */
    public function verifyLogin(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'codigo'  => 'required|string|size:6',
        ]);

        $usuario = User::find($request->user_id);

        if (!$usuario) {
            return $this->notFoundResponse('Usuario no encontrado');
        }

        $resultado = $this->mfaService->verificarCodigoMFA($usuario, $request->codigo);

        if (!$resultado['success']) {
            return $this->unauthorizedResponse($resultado['message']);
        }

        $token = JWTAuth::fromUser($usuario);
        $usuario->update(['last_login' => now()]);

        return $this->successResponse(
            $this->buildTokenResponse($token, $usuario->load('roles')),
            'Login completado exitosamente'
        );
    }

    /**
     * Desactiva MFA previa verificación del código TOTP actual.
     *
     * @OA\Post(
     *     path="/api/auth/mfa/desactivar",
     *     tags={"Autenticación"},
     *     summary="Desactivar MFA",
     *     security={{"bearer":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"codigo"},
     *             @OA\Property(property="codigo", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="MFA desactivado")
     * )
     */
    public function disable(MFAVerifyRequest $request): JsonResponse
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $resultado = $this->mfaService->desactivarMFA($usuario, $request->codigo);

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

    /**
     * Construye la estructura estándar de respuesta para tokens JWT.
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
