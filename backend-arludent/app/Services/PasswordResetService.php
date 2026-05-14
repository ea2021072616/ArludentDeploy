<?php

namespace App\Services;

use App\Models\User;
use App\Models\LogActividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Servicio de Recuperación de Contraseña
 *
 * Gestiona el proceso de recuperación y restablecimiento de contraseña
 */
class PasswordResetService
{
    /**
     * Inicia el proceso de recuperación de contraseña
     *
     * @param string $correo
     * @return array ['success' => bool, 'message' => string]
     */
    public function iniciarRecuperacion(string $correo): array
    {
        $usuario = User::where('correo', $correo)->first();

        if (!$usuario) {
            // Por seguridad, no revelar si el correo existe
            return [
                'success' => true,
                'message' => 'Si el correo existe, recibirás instrucciones para recuperar tu contraseña.',
            ];
        }

        // Generar token único (hex = solo 0-9a-f, 100% URL-safe)
        $token = bin2hex(random_bytes(32));

        // Eliminar tokens anteriores para este email
        DB::table('password_reset_tokens')->where('email', $correo)->delete();

        // Guardar token hasheado en tabla password_reset_tokens
        DB::table('password_reset_tokens')->insert([
            'email' => $correo,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        Log::info('Token de recuperación generado', [
            'email' => $correo,
            'token_length' => strlen($token),
            'token_preview' => substr($token, 0, 8) . '...',
        ]);

        // Enviar correo con el token
        $mailService = new MailService();
        $mailService->enviarRecuperacionPassword($usuario, $token);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'solicitud_recuperacion',
            'modulo_afectado' => 'autenticacion',
            'descripcion' => 'Solicitud de recuperación de contraseña',
            'ip_usuario' => request()->ip(),
        ]);

        return [
            'success' => true,
            'message' => 'Si el correo existe, recibirás instrucciones para recuperar tu contraseña.',
        ];
    }

    /**
     * Verifica si un token de recuperación es válido
     *
     * @param string $token
     * @param string $correo
     * @return array ['success' => bool, 'valid' => bool]
     */
    public function verificarToken(string $token, string $correo): array
    {
        Log::info('Verificando token de recuperación', [
            'email' => $correo,
            'token_length' => strlen($token),
            'token_preview' => substr($token, 0, 8) . '...',
        ]);

        $registro = DB::table('password_reset_tokens')
            ->where('email', $correo)
            ->first();

        if (!$registro) {
            Log::warning('No se encontró registro de reset para el email', ['email' => $correo]);
            return [
                'success' => false,
                'valid' => false,
                'message' => 'No se encontró una solicitud de recuperación para este correo. Solicita una nueva.',
            ];
        }

        // Verificar si el token coincide
        if (!Hash::check($token, $registro->token)) {
            Log::warning('Token no coincide con el hash almacenado', [
                'email' => $correo,
                'token_preview' => substr($token, 0, 8) . '...',
            ]);
            return [
                'success' => false,
                'valid' => false,
                'message' => 'El token es inválido. Es posible que el enlace se haya copiado incorrectamente. Solicita uno nuevo.',
            ];
        }

        // Verificar si ha expirado (60 minutos por defecto)
        $expiracion = config('auth.passwords.users.expire', 60);
        $fechaCreacion = Carbon::parse($registro->created_at);

        if ($fechaCreacion->addMinutes($expiracion)->isPast()) {
            return [
                'success' => false,
                'valid' => false,
                'message' => 'El token ha expirado. Solicita uno nuevo.',
            ];
        }

        return [
            'success' => true,
            'valid' => true,
            'message' => 'Token válido.',
        ];
    }

    /**
     * Restablece la contraseña del usuario
     *
     * @param string $correo
     * @param string $token
     * @param string $nuevaPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public function restablecerPassword(string $correo, string $token, string $nuevaPassword): array
    {
        // Verificar token
        $verificacion = $this->verificarToken($token, $correo);

        if (!$verificacion['valid']) {
            return $verificacion;
        }

        // Buscar usuario
        $usuario = User::where('correo', $correo)->first();

        if (!$usuario) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ];
        }

        // Actualizar contraseña
        $usuario->update([
            'password_hash' => Hash::make($nuevaPassword),
        ]);

        // Eliminar el token usado
        DB::table('password_reset_tokens')
            ->where('email', $correo)
            ->delete();

        // Enviar notificación de cambio
        $mailService = new MailService();
        $mailService->enviarCambioPassword($usuario);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'password_restablecido',
            'modulo_afectado' => 'autenticacion',
            'descripcion' => 'Contraseña restablecida exitosamente',
            'ip_usuario' => request()->ip(),
        ]);

        return [
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.',
        ];
    }

    /**
     * Cambia la contraseña de un usuario autenticado
     *
     * @param User $usuario
     * @param string $passwordActual
     * @param string $nuevaPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public function cambiarPassword(User $usuario, string $passwordActual, string $nuevaPassword): array
    {
        // Verificar contraseña actual
        if (!Hash::check($passwordActual, $usuario->password_hash)) {
            return [
                'success' => false,
                'message' => 'La contraseña actual es incorrecta.',
            ];
        }

        // Verificar que la nueva contraseña sea diferente
        if (Hash::check($nuevaPassword, $usuario->password_hash)) {
            return [
                'success' => false,
                'message' => 'La nueva contraseña debe ser diferente a la actual.',
            ];
        }

        // Actualizar contraseña
        $usuario->update([
            'password_hash' => Hash::make($nuevaPassword),
        ]);

        // Enviar notificación
        $mailService = new MailService();
        $mailService->enviarCambioPassword($usuario);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'cambio_password',
            'modulo_afectado' => 'autenticacion',
            'descripcion' => 'Contraseña cambiada desde perfil',
            'ip_usuario' => request()->ip(),
        ]);

        return [
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente.',
        ];
    }
}
