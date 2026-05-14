<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'api' => 'API Arludent',
        'version' => '1.0.0',
        'descripcion' => 'Sistema de Gestión Odontológica',
        'documentacion' => url('/api/documentacion'),
        'estado' => 'operativo',
        'endpoints' => [
            'autenticacion' => url('/api/auth'),
            'usuarios' => url('/api/usuarios'),
            'clinica' => url('/api/clinica'),
            'sistema' => url('/api/sistema'),
        ],
        'contacto' => [
            'email' => 'soporte@arludent.com',
            'documentacion' => url('/api/documentacion'),
        ],
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

// ============ SEGUIMIENTO POST-TRATAMIENTO (PÚBLICO) ============
// Rutas públicas para seguimiento post-tratamiento
Route::prefix('seguimiento')->group(function () {
    // Obtener información del seguimiento por token
    Route::get('/{token}', [\App\Http\Controllers\GestionClinica\SeguimientoPostTratamientoController::class, 'obtenerPorToken']);
    Route::post('/{token}/responder', [\App\Http\Controllers\GestionClinica\SeguimientoPostTratamientoController::class, 'responderPaciente']);
});

// Webhook público para el Agente IA (seguimientos post-tratamiento)
Route::post('/api/seguimiento/webhook-ia', [\App\Http\Controllers\GestionClinica\SeguimientoPostTratamientoController::class, 'webhookIA']);

// Ruta de prueba temporal para verificar pacientes
Route::get('/test-pacientes', function () {
    $pacientes = \App\Models\Paciente::with('usuario')->get();
    return response()->json([
        'total' => $pacientes->count(),
        'pacientes' => $pacientes->map(function($p) {
            return [
                'id_paciente' => $p->id_paciente,
                'nombres' => $p->nombres,
                'apellidos' => $p->apellidos,
                'dni' => $p->dni,
                'fecha_nacimiento' => $p->fecha_nacimiento,
                'sexo' => $p->sexo,
                'telefono' => $p->usuario->telefono ?? $p->telefono_responsable,
                'email' => $p->usuario->correo ?? null,
                'domicilio' => $p->domicilio,
                'estado' => $p->estado,
            ];
        })
    ]);
});
