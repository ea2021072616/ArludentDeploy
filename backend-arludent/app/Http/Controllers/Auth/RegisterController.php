<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Services\VerificationService;
use App\Services\MailService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Registro.
 *
 * Gestiona el registro de nuevos usuarios externos y el envío
 * automático del correo de verificación tras el registro.
 *
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para registro, login y gestión de sesiones"
 * )
 */
class RegisterController extends Controller
{
    /** Rol asignado por defecto a usuarios auto-registrados (hasta que se les cree historial clínico). */
    private const DEFAULT_REGISTRATION_ROLE = 'externo';

    private AuthService $authService;
    private VerificationService $verificationService;
    private MailService $mailService;

    public function __construct(
        AuthService $authService,
        VerificationService $verificationService,
        MailService $mailService
    ) {
        $this->authService = $authService;
        $this->verificationService = $verificationService;
        $this->mailService = $mailService;
    }

    /**
     * Registra un nuevo usuario y envía correo de verificación.
     *
     * @OA\Post(
     *     path="/api/auth/registro",
     *     tags={"Autenticación"},
     *     summary="Registrar nuevo usuario",
     *     description="Crea un nuevo usuario y envía correo de verificación",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"correo","password","password_confirmation"},
     *             @OA\Property(property="username", type="string", example="juanperez"),
     *             @OA\Property(property="correo", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!", description="Mínimo 8 caracteres, incluir mayúsculas, minúsculas, números y símbolos"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="telefono", type="string", example="+51987654321")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario registrado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuario registrado exitosamente. Por favor, verifica tu correo electrónico."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id_usuario", type="integer", example=1),
     *                     @OA\Property(property="username", type="string", example="juanperez"),
     *                     @OA\Property(property="correo", type="string", example="juan@example.com"),
     *                     @OA\Property(property="estado", type="string", example="pendiente")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Errores de validación"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $resultado = $this->authService->registrarUsuario(
            $request->validated(),
            self::DEFAULT_REGISTRATION_ROLE
        );

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message'], Response::HTTP_BAD_REQUEST);
        }

        // Enviar correo de verificación (no bloquea el registro si falla)
        try {
            $this->verificationService->enviarCorreoVerificacion(
                $resultado['user'],
                $resultado['verification_token']
            );
        } catch (\Exception $e) {
            \Log::warning('No se pudo enviar correo de verificación durante registro', [
                'user_id' => $resultado['user']->id_usuario,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->successResponse(
            [
                'user' => [
                    'id_usuario' => $resultado['user']->id_usuario,
                    'username'   => $resultado['user']->username,
                    'correo'     => $resultado['user']->correo,
                    'estado'     => $resultado['user']->estado,
                ],
            ],
            $resultado['message'],
            Response::HTTP_CREATED
        );
    }
}
