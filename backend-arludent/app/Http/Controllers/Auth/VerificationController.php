<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Verificación de Correo Electrónico.
 *
 * Gestiona la verificación de correo mediante token
 * y el reenvío del correo de verificación.
 */
class VerificationController extends Controller
{
    private VerificationService $verificationService;

    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Verifica el correo electrónico con el token proporcionado por query string.
     *
     * @OA\Get(
     *     path="/api/auth/verificar-correo",
     *     tags={"Autenticación"},
     *     summary="Verificar correo electrónico",
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Correo verificado exitosamente")
     * )
     */
    public function verify(Request $request): JsonResponse
    {
        $token = $request->query('token');

        if (!$token) {
            return $this->errorResponse('Token no proporcionado', Response::HTTP_BAD_REQUEST);
        }

        $resultado = $this->verificationService->verificarToken($token);

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message'], Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse(
            ['user' => $resultado['user']],
            $resultado['message']
        );
    }

    /**
     * Reenvía el correo de verificación a la dirección proporcionada.
     *
     * @OA\Post(
     *     path="/api/auth/reenviar-verificacion",
     *     tags={"Autenticación"},
     *     summary="Reenviar correo de verificación",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"correo"},
     *             @OA\Property(property="correo", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Correo reenviado")
     * )
     */
    public function resend(Request $request): JsonResponse
    {
        $request->validate([
            'correo' => 'required|email',
        ]);

        $resultado = $this->verificationService->reenviarVerificacion($request->correo);

        if (!$resultado['success']) {
            return $this->errorResponse($resultado['message'], Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse(null, $resultado['message']);
    }
}
