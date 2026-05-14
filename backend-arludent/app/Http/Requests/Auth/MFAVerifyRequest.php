<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de Verificación MFA
 */
class MFAVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required|string|size:6|regex:/^[0-9]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código MFA es obligatorio.',
            'codigo.size' => 'El código MFA debe tener exactamente 6 dígitos.',
            'codigo.regex' => 'El código MFA solo puede contener números.',
        ];
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
