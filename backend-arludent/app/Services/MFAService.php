<?php

namespace App\Services;

use App\Models\User;
use App\Models\LogActividad;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Crypt;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Servicio MFA (Multi-Factor Authentication)
 *
 * Gestiona la autenticación de dos factores usando Google Authenticator
 * Utiliza el algoritmo TOTP (Time-based One-Time Password)
 */
class MFAService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Genera un secreto MFA para el usuario
     *
     * @param User $usuario
     * @return array ['success' => bool, 'secret' => string, 'qr_code' => string]
     */
    public function generarSecretoMFA(User $usuario): array
    {
        try {
            // Generar secreto único
            $secreto = $this->google2fa->generateSecretKey();

            // Cifrar secreto antes de guardarlo
            $secretoCifrado = Crypt::encryptString($secreto);

            // Guardar en base de datos (aún no activado)
            $usuario->update([
                'mfa_secret' => $secretoCifrado,
            ]);

            // Generar código QR
            $nombreApp = config('app.name');
            $correo = $usuario->correo;
            $qrCodeUrl = $this->google2fa->getQRCodeUrl(
                $nombreApp,
                $correo,
                $secreto
            );

            // Generar imagen QR en formato SVG
            $qrCode = $this->generarCodigoQR($qrCodeUrl);

            // Registrar actividad
            LogActividad::create([
                'id_usuario' => $usuario->id_usuario,
                'accion' => 'generar_mfa',
                'modulo_afectado' => 'autenticacion',
                'descripcion' => 'Secreto MFA generado',
                'ip_usuario' => request()->ip(),
            ]);

            return [
                'success' => true,
                'secret' => $secreto, // Devolver sin cifrar para mostrar al usuario
                'qr_code' => $qrCode,
                'message' => 'Escanea el código QR con Google Authenticator.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'secret' => null,
                'qr_code' => null,
                'message' => 'Error al generar secreto MFA: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Activa MFA para el usuario después de verificar el código
     *
     * @param User $usuario
     * @param string $codigoVerificacion Código de 6 dígitos de Google Authenticator
     * @return array ['success' => bool, 'message' => string]
     */
    public function activarMFA(User $usuario, string $codigoVerificacion): array
    {
        if (!$usuario->mfa_secret) {
            return [
                'success' => false,
                'message' => 'Primero debes generar un secreto MFA.',
            ];
        }

        // Descifrar secreto
        try {
            $secreto = Crypt::decryptString($usuario->mfa_secret);
        } catch (\Exception $e) {
            \Log::error('Error al descifrar MFA secret', [
                'user_id' => $usuario->id_usuario,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Error al descifrar el secreto MFA.',
            ];
        }

        // IMPORTANTE: Usar ventana muy amplia para activación inicial
        // porque puede haber desfase de tiempo entre dispositivos
        $ventana = 17; // ±8.5 minutos de tolerancia

        // Verificar código
        $esValido = $this->google2fa->verifyKey($secreto, $codigoVerificacion, $ventana);

        // Log para debugging
        \Log::info('Verificando código MFA para ACTIVACIÓN', [
            'user_id' => $usuario->id_usuario,
            'codigo_ingresado' => $codigoVerificacion,
            'codigo_valido' => $esValido ? 'SI' : 'NO',
            'ventana' => $ventana,
            'timestamp_servidor' => now()->toDateTimeString(),
            'timestamp_unix' => now()->timestamp
        ]);

        if (!$esValido) {
            return [
                'success' => false,
                'message' => 'Código de verificación inválido.',
            ];
        }

        // Activar MFA
        $usuario->update([
            'mfa_enabled' => true,
            'mfa_last_verified' => now(),
        ]);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'activar_mfa',
            'modulo_afectado' => 'autenticacion',
            'descripcion' => 'MFA activado exitosamente',
            'ip_usuario' => request()->ip(),
        ]);

        return [
            'success' => true,
            'message' => 'Autenticación de dos factores activada exitosamente.',
        ];
    }

    /**
     * Verifica un código MFA durante el login
     *
     * @param User $usuario
     * @param string $codigo Código de 6 dígitos
     * @return array ['success' => bool, 'message' => string]
     */
    public function verificarCodigoMFA(User $usuario, string $codigo): array
    {
        if (!$usuario->mfa_enabled || !$usuario->mfa_secret) {
            return [
                'success' => false,
                'message' => 'MFA no está activado para este usuario.',
            ];
        }

        // Descifrar secreto
        $secreto = Crypt::decryptString($usuario->mfa_secret);

        // Verificar código con ventana de tiempo configurada
        // Ventana de 8 = ±4 minutos de tolerancia
        $ventana = config('google2fa.window', 8);
        $esValido = $this->google2fa->verifyKey($secreto, $codigo, $ventana);

        if (!$esValido) {
            // Registrar intento fallido
            LogActividad::create([
                'id_usuario' => $usuario->id_usuario,
                'accion' => 'mfa_fallido',
                'modulo_afectado' => 'autenticacion',
                'descripcion' => 'Intento de verificación MFA fallido',
                'ip_usuario' => request()->ip(),
            ]);

            return [
                'success' => false,
                'message' => 'Código MFA inválido.',
            ];
        }

        // Actualizar última verificación
        $usuario->update([
            'mfa_last_verified' => now(),
        ]);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'mfa_exitoso',
            'modulo_afectado' => 'autenticacion',
            'descripcion' => 'Verificación MFA exitosa',
            'ip_usuario' => request()->ip(),
        ]);

        return [
            'success' => true,
            'message' => 'Código MFA verificado exitosamente.',
        ];
    }

    /**
     * Desactiva MFA para el usuario
     *
     * @param User $usuario
     * @param string $codigoActual Código de verificación actual
     * @return array ['success' => bool, 'message' => string]
     */
    public function desactivarMFA(User $usuario, string $codigoActual): array
    {
        if (!$usuario->mfa_enabled) {
            return [
                'success' => false,
                'message' => 'MFA ya está desactivado.',
            ];
        }

        // Verificar código actual antes de desactivar
        $verificacion = $this->verificarCodigoMFA($usuario, $codigoActual);

        if (!$verificacion['success']) {
            return $verificacion;
        }

        // Desactivar MFA y eliminar secreto
        $usuario->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_last_verified' => null,
        ]);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'desactivar_mfa',
            'modulo_afectado' => 'autenticacion',
            'descripcion' => 'MFA desactivado',
            'ip_usuario' => request()->ip(),
        ]);

        return [
            'success' => true,
            'message' => 'Autenticación de dos factores desactivada exitosamente.',
        ];
    }

    /**
     * Genera un código QR en formato SVG
     *
     * @param string $url URL para el código QR
     * @return string Código QR en formato SVG
     */
    private function generarCodigoQR(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($url);
    }
}
