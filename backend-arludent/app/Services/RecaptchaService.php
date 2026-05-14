<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para validación de Google reCAPTCHA v2
 */
class RecaptchaService
{
    private string $secretKey;
    private bool $enabled;
    private string $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct()
    {
        $this->secretKey = config('services.recaptcha.secret_key', '');
        $this->enabled = config('services.recaptcha.enabled', false);
    }

    /**
     * Verifica el token de reCAPTCHA con la API de Google
     *
     * @param string|null $token Token de respuesta de reCAPTCHA
     * @param string|null $remoteIp IP del cliente (opcional)
     * @return bool
     */
    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        // Si reCAPTCHA está deshabilitado, pasar la validación
        if (!$this->enabled) {
            Log::info('reCAPTCHA deshabilitado, omitiendo validación');
            return true;
        }

        // Si no hay token, fallar
        if (empty($token)) {
            Log::warning('Token de reCAPTCHA vacío');
            return false;
        }

        // Si no hay secret key configurada, fallar
        if (empty($this->secretKey)) {
            Log::error('Secret key de reCAPTCHA no configurada');
            return false;
        }

        try {
            // Preparar datos para la verificación
            $data = [
                'secret' => $this->secretKey,
                'response' => $token,
            ];

            // Agregar IP si está disponible
            if ($remoteIp) {
                $data['remoteip'] = $remoteIp;
            }

            // Hacer la petición a Google
            $response = Http::asForm()->post($this->verifyUrl, $data);

            // Verificar si la petición fue exitosa
            if (!$response->successful()) {
                Log::error('Error al verificar reCAPTCHA con Google', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $result = $response->json();

            // Verificar la respuesta
            if (!isset($result['success'])) {
                Log::error('Respuesta inválida de Google reCAPTCHA', ['result' => $result]);
                return false;
            }

            // Si no es exitoso, loguear los errores
            if (!$result['success']) {
                Log::warning('Verificación de reCAPTCHA fallida', [
                    'error_codes' => $result['error-codes'] ?? [],
                ]);
                return false;
            }

            // Verificación exitosa
            Log::info('reCAPTCHA verificado exitosamente', [
                'hostname' => $result['hostname'] ?? null,
                'score' => $result['score'] ?? null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Excepción al verificar reCAPTCHA', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Verifica si reCAPTCHA está habilitado
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
