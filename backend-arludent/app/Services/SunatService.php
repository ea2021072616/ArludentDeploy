<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio de Integración con API SUNAT
 *
 * Integra con apisunat.com para emitir comprobantes electrónicos
 * Documentación: https://apisunat.com/
 */
class SunatService
{
    private string $personaId;
    private string $personaToken;
    private string $baseUrl;
    private bool $modoProduccion;

    public function __construct()
    {
        // Credenciales de desarrollo proporcionadas
        $this->personaId = config('services.sunat.persona_id', '685effa1250d3a0015f27672');
        $this->personaToken = config('services.sunat.persona_token', 'DEV_4tkV0zZAS3p0BTrSiEMDCx3URjeFtwbgu0VHQ2OIvVbEiHeNbDfU313BZdSEeCnL');
        $this->baseUrl = config('services.sunat.base_url', 'https://apisunat.com/api/v1');
        $this->modoProduccion = config('services.sunat.produccion', false);
    }

    /**
     * Emitir Boleta Electrónica (RUC 10 o DNI)
     *
     * @param array $datos Datos de la boleta
     * @return array Respuesta de SUNAT
     */
    public function emitirBoleta(array $datos): array
    {
        try {
            $payload = [
                'personaId' => $this->personaId,
                'personaToken' => $this->personaToken,
                'tipoComprobante' => '03', // 03 = Boleta
                'serie' => $datos['serie'] ?? 'B001',
                'numero' => $datos['numero'],
                'fechaEmision' => $datos['fecha_emision'] ?? now()->format('Y-m-d'),
                'horaEmision' => $datos['hora_emision'] ?? now()->format('H:i:s'),

                // Datos del emisor (clínica)
                'rucEmisor' => $datos['ruc_emisor'],
                'razonSocialEmisor' => $datos['razon_social_emisor'],
                'direccionEmisor' => $datos['direccion_emisor'] ?? 'Lima, Perú',

                // Datos del cliente
                'tipoDocumentoCliente' => $datos['tipo_documento_cliente'], // 1=DNI, 6=RUC
                'numeroDocumentoCliente' => $datos['numero_documento_cliente'],
                'nombreCliente' => $datos['nombre_cliente'],
                'direccionCliente' => $datos['direccion_cliente'] ?? '',

                // Items/Servicios
                'items' => $datos['items'], // Array de items

                // Montos
                'subtotal' => $datos['subtotal'],
                'igv' => $datos['igv'],
                'total' => $datos['total'],

                // Opcionales
                'moneda' => $datos['moneda'] ?? 'PEN',
                'observaciones' => $datos['observaciones'] ?? '',
            ];

            // En modo desarrollo, simular respuesta exitosa
            if (!$this->modoProduccion) {
                return $this->simularRespuestaBoleta($payload);
            }

            // Llamada real a la API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/boleta/emitir", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception("Error en API SUNAT: {$response->body()}");

        } catch (Exception $e) {
            Log::error('Error al emitir boleta SUNAT', [
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);

            throw $e;
        }
    }

    /**
     * Emitir Factura Electrónica (RUC 20)
     *
     * @param array $datos Datos de la factura
     * @return array Respuesta de SUNAT
     */
    public function emitirFactura(array $datos): array
    {
        try {
            $payload = [
                'personaId' => $this->personaId,
                'personaToken' => $this->personaToken,
                'tipoComprobante' => '01', // 01 = Factura
                'serie' => $datos['serie'] ?? 'F001',
                'numero' => $datos['numero'],
                'fechaEmision' => $datos['fecha_emision'] ?? now()->format('Y-m-d'),
                'horaEmision' => $datos['hora_emision'] ?? now()->format('H:i:s'),

                // Datos del emisor (clínica)
                'rucEmisor' => $datos['ruc_emisor'],
                'razonSocialEmisor' => $datos['razon_social_emisor'],
                'direccionEmisor' => $datos['direccion_emisor'] ?? 'Lima, Perú',

                // Datos del cliente (debe ser RUC para factura)
                'tipoDocumentoCliente' => '6', // 6 = RUC
                'numeroDocumentoCliente' => $datos['numero_documento_cliente'],
                'nombreCliente' => $datos['nombre_cliente'],
                'direccionCliente' => $datos['direccion_cliente'] ?? '',

                // Items/Servicios
                'items' => $datos['items'],

                // Montos
                'subtotal' => $datos['subtotal'],
                'igv' => $datos['igv'],
                'total' => $datos['total'],

                // Opcionales
                'moneda' => $datos['moneda'] ?? 'PEN',
                'observaciones' => $datos['observaciones'] ?? '',
            ];

            // En modo desarrollo, simular respuesta exitosa
            if (!$this->modoProduccion) {
                return $this->simularRespuestaFactura($payload);
            }

            // Llamada real a la API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/factura/emitir", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception("Error en API SUNAT: {$response->body()}");

        } catch (Exception $e) {
            Log::error('Error al emitir factura SUNAT', [
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);

            throw $e;
        }
    }

    /**
     * Consultar estado de un comprobante
     *
     * @param string $serie Serie del comprobante
     * @param string $numero Número del comprobante
     * @param string $tipo Tipo: 'boleta' o 'factura'
     * @return array Estado del comprobante
     */
    public function consultarComprobante(string $serie, string $numero, string $tipo = 'boleta'): array
    {
        try {
            if (!$this->modoProduccion) {
                return [
                    'success' => true,
                    'estado' => 'aceptado',
                    'mensaje' => 'Comprobante aceptado por SUNAT (simulado)',
                    'fecha_consulta' => now()->toDateTimeString(),
                ];
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/comprobante/consultar", [
                    'personaId' => $this->personaId,
                    'personaToken' => $this->personaToken,
                    'serie' => $serie,
                    'numero' => $numero,
                    'tipo' => $tipo === 'factura' ? '01' : '03',
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception("Error al consultar comprobante: {$response->body()}");

        } catch (Exception $e) {
            Log::error('Error al consultar comprobante SUNAT', [
                'error' => $e->getMessage(),
                'serie' => $serie,
                'numero' => $numero,
                'tipo' => $tipo
            ]);

            throw $e;
        }
    }

    /**
     * Simula respuesta de emisión de boleta (modo desarrollo)
     */
    private function simularRespuestaBoleta(array $payload): array
    {
        $serie = $payload['serie'];
        $numero = $payload['numero'];
        $hash = md5($serie . $numero . time());

        return [
            'success' => true,
            'mensaje' => 'Boleta emitida correctamente (SIMULADO - DESARROLLO)',
            'data' => [
                'tipo_comprobante' => 'boleta',
                'serie' => $serie,
                'numero' => $numero,
                'comprobante_completo' => "{$serie}-{$numero}",
                'hash' => $hash,
                'codigo_qr' => "https://apisunat.com/qr/{$serie}-{$numero}",
                'enlace_pdf' => "https://apisunat.com/pdf/{$serie}-{$numero}.pdf",
                'enlace_xml' => "https://apisunat.com/xml/{$serie}-{$numero}.xml",
                'estado_sunat' => 'aceptado',
                'fecha_emision' => $payload['fechaEmision'],
                'hora_emision' => $payload['horaEmision'],
                'total' => $payload['total'],
                'mensaje_sunat' => 'La Boleta número ' . $serie . '-' . $numero . ' ha sido aceptada',
                'codigo_sunat' => '0',
            ]
        ];
    }

    /**
     * Simula respuesta de emisión de factura (modo desarrollo)
     */
    private function simularRespuestaFactura(array $payload): array
    {
        $serie = $payload['serie'];
        $numero = $payload['numero'];
        $hash = md5($serie . $numero . time());

        return [
            'success' => true,
            'mensaje' => 'Factura emitida correctamente (SIMULADO - DESARROLLO)',
            'data' => [
                'tipo_comprobante' => 'factura',
                'serie' => $serie,
                'numero' => $numero,
                'comprobante_completo' => "{$serie}-{$numero}",
                'hash' => $hash,
                'codigo_qr' => "https://apisunat.com/qr/{$serie}-{$numero}",
                'enlace_pdf' => "https://apisunat.com/pdf/{$serie}-{$numero}.pdf",
                'enlace_xml' => "https://apisunat.com/xml/{$serie}-{$numero}.xml",
                'estado_sunat' => 'aceptado',
                'fecha_emision' => $payload['fechaEmision'],
                'hora_emision' => $payload['horaEmision'],
                'total' => $payload['total'],
                'mensaje_sunat' => 'La Factura número ' . $serie . '-' . $numero . ' ha sido aceptada',
                'codigo_sunat' => '0',
            ]
        ];
    }
}
