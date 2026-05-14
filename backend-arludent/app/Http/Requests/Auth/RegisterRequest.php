<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de Registro
 *
 * Valida los datos de registro con mensajes en español
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        return [
            'username' => 'nullable|string|max:50|unique:usuarios,username',
            'correo' => 'required|email|max:100|unique:usuarios,correo',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
            'telefono' => 'nullable|string|max:20',
        ];
    }

    /**
     * Mensajes de validación personalizados en español
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'username.max' => 'El nombre de usuario no puede tener más de 50 caracteres.',

            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Debe proporcionar un correo electrónico válido.',
            'correo.unique' => 'Este correo electrónico ya está registrado.',
            'correo.max' => 'El correo no puede tener más de 100 caracteres.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe incluir al menos una mayúscula, una minúscula, un número y un símbolo especial.',

            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
        ];
    }

    /**
     * Maneja errores de validación devolviendo JSON
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación',
            'errors' => $validator->errors(),
        ], 422));
    }
}
