<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

/**
 * Servicio de Correo
 *
 * Centraliza el envío de correos electrónicos con plantillas en español
 */
class MailService
{
    /**
     * Envía correo de bienvenida al usuario
     *
     * @param object $usuario Datos del usuario (correo, username/nombres)
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarBienvenida($usuario): array
    {
        try {
            Mail::send('emails.bienvenida', [
                'usuario' => $usuario,
            ], function ($message) use ($usuario) {
                $message->to($usuario->correo)
                       ->subject('¡Bienvenido a Arludent!');
            });

            return [
                'success' => true,
                'message' => 'Correo de bienvenida enviado.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar correo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Envía correo de recuperación de contraseña
     *
     * @param object $usuario
     * @param string $token Token de recuperación
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarRecuperacionPassword($usuario, string $token): array
    {
        try {
            // URL apunta al frontend (incluye email para auto-fill)
            $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
            $url = $frontendUrl . '/reset-password/' . $token . '?email=' . urlencode($usuario->correo);

            Mail::send('emails.recuperacion', [
                'usuario' => $usuario,
                'url' => $url,
                'token' => $token,
            ], function ($message) use ($usuario) {
                $message->to($usuario->correo)
                       ->subject('Recuperación de Contraseña - Arludent');
            });

            return [
                'success' => true,
                'message' => 'Correo de recuperación enviado.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar correo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Envía notificación de cita próxima
     *
     * @param object $usuario
     * @param object $cita Datos de la cita
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarRecordatorioCita($usuario, $cita): array
    {
        try {
            Mail::send('emails.recordatorio-cita', [
                'usuario' => $usuario,
                'cita' => $cita,
            ], function ($message) use ($usuario) {
                $message->to($usuario->correo)
                       ->subject('Recordatorio de Cita - Arludent');
            });

            return [
                'success' => true,
                'message' => 'Recordatorio enviado.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar recordatorio: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Envía notificación de cambio de contraseña
     *
     * @param object $usuario
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarCambioPassword($usuario): array
    {
        try {
            Mail::send('emails.cambio-password', [
                'usuario' => $usuario,
            ], function ($message) use ($usuario) {
                $message->to($usuario->correo)
                       ->subject('Contraseña Actualizada - Arludent');
            });

            return [
                'success' => true,
                'message' => 'Notificación de cambio de contraseña enviada.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar notificación: ' . $e->getMessage(),
            ];
        }
    }
}
