<?php

namespace App\Services;

use App\Models\User;
use App\Models\LogActividad;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * Servicio de Verificación de Correo
 *
 * Gestiona la generación, envío y validación de tokens de verificación de correo electrónico
 */
class VerificationService
{
    /**
     * Envía un correo de verificación al usuario
     *
     * @param User $usuario
     * @param string $verificationToken
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarCorreoVerificacion(User $usuario, string $verificationToken): array
    {
        try {
            // Construir URL de verificación apuntando al frontend
            $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
            $url = $frontendUrl . '/email/verify?token=' . $verificationToken;

            // Enviar correo directamente
            Mail::send('emails.verificacion', [
                'usuario' => $usuario,
                'url' => $url,
            ], function ($message) use ($usuario) {
                $message->to($usuario->correo)
                       ->subject('Verifica tu correo electrónico - Arludent');
            });

            // Registrar actividad
            LogActividad::create([
                'id_usuario' => $usuario->id_usuario,
                'accion' => 'envio_verificacion',
                'modulo_afectado' => 'autenticacion',
                'descripcion' => 'Correo de verificación enviado',
                'ip_usuario' => request()->ip() ?? '127.0.0.1',
            ]);

            return [
                'success' => true,
                'message' => 'Correo de verificación enviado exitosamente.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar correo de verificación: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica el token de verificación de correo
     *
     * @param string $token
     * @return array ['success' => bool, 'user' => User|null, 'message' => string]
     */
    public function verificarToken(string $token): array
    {
        // Buscar usuario con el token
        $usuario = User::where('verification_token', $token)->first();

        if (!$usuario) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Token de verificación inválido.',
            ];
        }

        // Verificar si ya está verificado
        if ($usuario->hasVerifiedEmail()) {
            return [
                'success' => false,
                'user' => $usuario,
                'message' => 'El correo ya ha sido verificado anteriormente.',
            ];
        }

        // Marcar como verificado y activar usuario
        $usuario->markEmailAsVerified();
        $usuario->update(['estado' => 'activo']);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'verificacion_exitosa',
            'modulo_afectado' => 'autenticacion',
            'descripcion' => 'Correo verificado exitosamente',
            'ip_usuario' => request()->ip(),
        ]);

        return [
            'success' => true,
            'user' => $usuario,
            'message' => 'Correo verificado exitosamente. Ya puedes iniciar sesión.',
        ];
    }

    /**
     * Regenera y reenvía un nuevo token de verificación
     *
     * @param string $correo
     * @return array ['success' => bool, 'message' => string]
     */
    public function reenviarVerificacion(string $correo): array
    {
        $usuario = User::where('correo', $correo)->first();

        if (!$usuario) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ];
        }

        if ($usuario->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'El correo ya está verificado.',
            ];
        }

        // Generar nuevo token
        $nuevoToken = Str::random(60);
        $usuario->update(['verification_token' => $nuevoToken]);

        // Enviar correo
        return $this->enviarCorreoVerificacion($usuario, $nuevoToken);
    }
}
