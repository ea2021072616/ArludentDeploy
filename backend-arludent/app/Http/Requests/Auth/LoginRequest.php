<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\RecaptchaService;

/**
 * Request de Login
 */
class LoginRequest extends FormRequest
{
    private RecaptchaService $recaptchaService;

    public function __construct(RecaptchaService $recaptchaService)
    {
        parent::__construct();
        $this->recaptchaService = $recaptchaService;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'correo' => 'required|string', // Cambiado de 'email' a 'string' para permitir username también
            'password' => 'required|string',
        ];

        // Agregar validación de reCAPTCHA solo si está habilitado
        if ($this->recaptchaService->isEnabled()) {
            $rules['recaptcha_token'] = 'required|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo electrónico o usuario es obligatorio.',
            'correo.string' => 'Debe proporcionar un correo electrónico o nombre de usuario válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'recaptcha_token.required' => 'Por favor, completa la verificación de seguridad.',
            'recaptcha_token.string' => 'Token de verificación inválido.',
        ];
    }

    /**
     * Configurar el validador con validación personalizada de reCAPTCHA
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar reCAPTCHA solo si está habilitado
            if ($this->recaptchaService->isEnabled()) {
                $token = $this->input('recaptcha_token');
                $remoteIp = $this->ip();

                if (!$this->recaptchaService->verify($token, $remoteIp)) {
                    $validator->errors()->add(
                        'recaptcha_token',
                        'La verificación de seguridad falló. Por favor, inténtalo de nuevo.'
                    );
                }
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación',
            'errors' => $validator->errors(),
        ], 422));
    }
}
