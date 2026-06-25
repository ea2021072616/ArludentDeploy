<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payload = [
    'seguimiento_id' => 7,
    'analisis' => [
        "nivel_urgencia" => "alto",
        "requiere_atencion" => true,
        "sentimiento_general" => "negativo",
        "sintomas_detectados" => ["dolor"],
        "recomendacion" => "Test",
        "resumen" => "Test",
        "probabilidad_complicacion" => 0.8,
        "necesita_cita_urgente" => true
    ],
    'timestamp' => now()->toIso8601String()
];

// Send with X-Internal-Key = 'your-secret-key-123' (from config)
$r = Illuminate\Support\Facades\Http::withHeaders([
    'X-Internal-Key' => env('INTERNAL_API_KEY', 'your-secret-key-123')
])->post('http://127.0.0.1:8000/api/seguimiento/webhook-ia', $payload);

echo "Status: " . $r->status() . "\n";
echo $r->body();
