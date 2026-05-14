<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para autenticar solicitudes internas del microservicio de IA
 *
 * Este middleware verifica que las solicitudes vengan del microservicio
 * usando una API Key compartida
 */
class InternalApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener la API Key del header
        $apiKey = $request->header('X-Internal-API-Key');

        // Obtener la API Key configurada en .env
        $expectedApiKey = env('INTERNAL_API_KEY', 'arludent-internal-2024-secure-key-xyz789');

        // Verificar que coincidan
        if ($apiKey !== $expectedApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. API Key inválida o ausente.',
                'error' => 'Unauthorized'
            ], 401);
        }

        // Continuar con la solicitud
        return $next($request);
    }
}
