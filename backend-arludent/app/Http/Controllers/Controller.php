<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

/**
 * Controlador Base
 *
 * Proporciona métodos de respuesta estándar JSON para toda la API
 */
abstract class Controller extends BaseController
{
    /**
     * Respuesta exitosa
     *
     * @param mixed $data Datos a devolver
     * @param string $message Mensaje descriptivo
     * @param int $code Código HTTP
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Operación exitosa', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Respuesta de error
     *
     * @param string $message Mensaje de error
     * @param int $code Código HTTP
     * @param mixed $errors Errores detallados (opcional)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message = 'Error en la operación', int $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Respuesta de validación fallida
     *
     * @param array $errors Errores de validación
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse(array $errors)
    {
        return $this->errorResponse(
            'Errores de validación',
            422,
            $errors
        );
    }

    /**
     * Respuesta no autorizada
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'No autorizado')
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Respuesta prohibida
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Acceso prohibido')
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Respuesta no encontrado
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado')
    {
        return $this->errorResponse($message, 404);
    }
}
