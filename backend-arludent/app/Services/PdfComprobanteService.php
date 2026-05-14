<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfComprobanteService
{
    /**
     * Convertir número a letras en español
     */
    private function numeroALetras($numero)
    {
        $numero = number_format($numero, 2, '.', '');
        list($entero, $decimal) = explode('.', $numero);

        $letrasEntero = $this->convertirEntero($entero);

        return strtoupper($letrasEntero) . ' CON ' . $decimal . '/100 SOLES';
    }

    private function convertirEntero($numero)
    {
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $decenas2 = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $numero = intval($numero);

        if ($numero == 0) return 'CERO';
        if ($numero < 10) return $unidades[$numero];
        if ($numero < 20) return $decenas[$numero - 10];
        if ($numero < 100) {
            $dec = intval($numero / 10);
            $uni = $numero % 10;
            if ($uni == 0) return $decenas2[$dec];
            if ($dec == 2) return 'VEINTI' . $unidades[$uni];
            return $decenas2[$dec] . ' Y ' . $unidades[$uni];
        }
        if ($numero < 1000) {
            $cen = intval($numero / 100);
            $resto = $numero % 100;
            if ($numero == 100) return 'CIEN';
            if ($resto == 0) return $centenas[$cen];
            return $centenas[$cen] . ' ' . $this->convertirEntero($resto);
        }
        if ($numero < 1000000) {
            $miles = intval($numero / 1000);
            $resto = $numero % 1000;
            $textoMiles = ($miles == 1) ? 'MIL' : $this->convertirEntero($miles) . ' MIL';
            if ($resto == 0) return $textoMiles;
            return $textoMiles . ' ' . $this->convertirEntero($resto);
        }

        return number_format($numero);
    }

    /**
     * Generar PDF de Boleta
     */
    public function generarBoletaPDF($pago)
    {
        $data = [
            'empresa' => [
                'razon_social' => config('app.razon_social_clinica', 'CLINICA DENTAL ARLUDENT S.A.C.'),
                'ruc' => config('app.ruc_clinica', '20123456789'),
                'direccion' => config('app.direccion_clinica', 'Av. Principal 123, Lima, Perú'),
                'telefono' => config('app.telefono_clinica', '(01) 234-5678'),
                'email' => config('app.email_clinica', 'contacto@arludent.com'),
            ],
            'cliente' => [
                'nombre' => $pago->nombre_cliente,
                'tipo_documento' => $pago->tipo_documento_cliente,
                'numero_documento' => $pago->numero_documento_cliente,
                'direccion' => $pago->direccion_cliente,
            ],
            'comprobante' => [
                'serie' => $pago->serie_comprobante,
                'numero' => str_pad($pago->numero_comprobante, 8, '0', STR_PAD_LEFT),
                'fecha_emision' => $pago->fecha_pago,
                'observaciones' => $pago->notas,
                'forma_pago' => $pago->metodo_pago,
            ],
            'items' => [[
                'cantidad' => 1,
                'descripcion' => $pago->concepto,
                'precio_unitario' => $pago->subtotal,
                'total' => $pago->subtotal,
            ]],
            'totales' => [
                'subtotal' => $pago->subtotal,
                'igv' => $pago->igv,
                'total' => $pago->total,
                'total_letras' => $this->numeroALetras($pago->total),
            ],
        ];

        $pdf = Pdf::loadView('comprobantes.boleta', $data);
        $pdf->setPaper('A4', 'portrait');

        // Guardar en storage/app/public/comprobantes
        $filename = "B{$pago->serie_comprobante}-{$pago->numero_comprobante}.pdf";
        $path = "comprobantes/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'url' => asset('storage/' . $path),
        ];
    }

    /**
     * Generar PDF de Factura
     */
    public function generarFacturaPDF($pago)
    {
        $data = [
            'empresa' => [
                'razon_social' => config('app.razon_social_clinica', 'CLINICA DENTAL ARLUDENT S.A.C.'),
                'ruc' => config('app.ruc_clinica', '20123456789'),
                'direccion' => config('app.direccion_clinica', 'Av. Principal 123, Lima, Perú'),
                'telefono' => config('app.telefono_clinica', '(01) 234-5678'),
                'email' => config('app.email_clinica', 'facturacion@arludent.com'),
            ],
            'cliente' => [
                'razon_social' => $pago->nombre_cliente,
                'ruc' => $pago->numero_documento_cliente,
                'direccion' => $pago->direccion_cliente,
            ],
            'comprobante' => [
                'serie' => $pago->serie_comprobante,
                'numero' => str_pad($pago->numero_comprobante, 8, '0', STR_PAD_LEFT),
                'fecha_emision' => $pago->fecha_pago,
                'observaciones' => $pago->notas,
                'forma_pago' => $pago->metodo_pago,
            ],
            'items' => [[
                'cantidad' => 1,
                'descripcion' => $pago->concepto,
                'precio_unitario' => $pago->subtotal,
                'total' => $pago->subtotal,
            ]],
            'totales' => [
                'subtotal' => $pago->subtotal,
                'igv' => $pago->igv,
                'total' => $pago->total,
                'total_letras' => $this->numeroALetras($pago->total),
            ],
        ];

        $pdf = Pdf::loadView('comprobantes.factura', $data);
        $pdf->setPaper('A4', 'portrait');

        // Guardar en storage/app/public/comprobantes
        $filename = "F{$pago->serie_comprobante}-{$pago->numero_comprobante}.pdf";
        $path = "comprobantes/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'url' => asset('storage/' . $path),
        ];
    }

    /**
     * Eliminar PDF de comprobante
     */
    public function eliminarPDF($path)
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return true;
        }
        return false;
    }

    /**
     * Generar PDF simple de Boleta para un pago (sin SUNAT)
     */
    public function generarBoletaSimplePDF($pago, $paciente)
    {
        $data = [
            'empresa' => [
                'razon_social' => config('app.razon_social_clinica', 'CLINICA DENTAL ARLUDENT S.A.C.'),
                'ruc' => config('app.ruc_clinica', '20123456789'),
                'direccion' => config('app.direccion_clinica', 'Av. Principal 123, Lima, Perú'),
                'telefono' => config('app.telefono_clinica', '(01) 234-5678'),
                'email' => config('app.email_clinica', 'contacto@arludent.com'),
            ],
            'cliente' => [
                'nombre' => $paciente->nombres . ' ' . $paciente->apellidos,
                'tipo_documento' => $paciente->tipo_documento ?? '1',
                'numero_documento' => $paciente->numero_documento ?? $paciente->dni,
                'direccion' => $paciente->direccion ?? '',
            ],
            'comprobante' => [
                // Use serie/numero from pago if available, otherwise fallback
                'serie' => $pago->serie_comprobante ?? 'B001',
                'numero' => str_pad($pago->numero_comprobante ?? 1, 8, '0', STR_PAD_LEFT),
                'fecha_emision' => $pago->fecha_pago ?? $pago->created_at,
                'observaciones' => $pago->notas,
                'forma_pago' => $pago->metodo_pago,
            ],
            'items' => [[
                'cantidad' => 1,
                'descripcion' => $pago->concepto,
                'precio_unitario' => $pago->monto,
                'total' => $pago->monto,
            ]],
            'totales' => [
                'subtotal' => $pago->monto,
                'igv' => 0,
                'total' => $pago->monto,
                'total_letras' => $this->numeroALetras($pago->monto),
            ],
        ];

        $pdf = Pdf::loadView('comprobantes.boleta', $data);
        $pdf->setPaper('A4', 'portrait');

        // Guardar en storage/app/public/pagos
        $filename = "boleta-pago-{$pago->id_pago}.pdf";
        $path = "pagos/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'url' => asset('storage/' . $path),
        ];
    }

    /**
     * Generar PDF simple de Factura para un pago (sin SUNAT)
     */
    public function generarFacturaSimplePDF($pago, $paciente)
    {
        $data = [
            'empresa' => [
                'razon_social' => config('app.razon_social_clinica', 'CLINICA DENTAL ARLUDENT S.A.C.'),
                'ruc' => config('app.ruc_clinica', '20123456789'),
                'direccion' => config('app.direccion_clinica', 'Av. Principal 123, Lima, Perú'),
                'telefono' => config('app.telefono_clinica', '(01) 234-5678'),
                'email' => config('app.email_clinica', 'facturacion@arludent.com'),
            ],
            'cliente' => [
                'razon_social' => $paciente->nombres . ' ' . $paciente->apellidos,
                'ruc' => $paciente->numero_documento ?? $paciente->dni,
                'direccion' => $paciente->direccion ?? '',
            ],
            'comprobante' => [
                // Use serie/numero from pago if available, otherwise fallback
                'serie' => $pago->serie_comprobante ?? 'F001',
                'numero' => str_pad($pago->numero_comprobante ?? 1, 8, '0', STR_PAD_LEFT),
                'fecha_emision' => $pago->fecha_pago ?? $pago->created_at,
                'observaciones' => $pago->notas,
                'forma_pago' => $pago->metodo_pago,
            ],
            'items' => [[
                'cantidad' => 1,
                'descripcion' => $pago->concepto,
                'precio_unitario' => $pago->monto,
                'total' => $pago->monto,
            ]],
            'totales' => [
                'subtotal' => $pago->monto,
                'igv' => 0,
                'total' => $pago->monto,
                'total_letras' => $this->numeroALetras($pago->monto),
            ],
        ];

        $pdf = Pdf::loadView('comprobantes.factura', $data);
        $pdf->setPaper('A4', 'portrait');

        // Guardar en storage/app/public/pagos
        $filename = "factura-pago-{$pago->id_pago}.pdf";
        $path = "pagos/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'url' => asset('storage/' . $path),
        ];
    }
}
