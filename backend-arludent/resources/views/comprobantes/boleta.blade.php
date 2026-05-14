<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Venta Electrónica</title>
    <style>
    /* Reduced page margins for DOMPDF */
    @page { margin: 6mm 8mm 6mm 8mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #263238; padding: 8px; }

    .container { max-width: 820px; margin: 4px auto; border: 1px solid #e6eefc; padding: 10px; border-radius: 6px; }

    /* Header: logo left, company center, comprobante right */
    .header { background: #ffffff; padding: 8px 10px; margin-bottom: 8px; }
    .header-row { display: flex; align-items: center; gap: 12px; }
    .logo-area img { max-width: 130px; max-height: 64px; object-fit: contain; }
    .company-info { flex: 1 1 auto; color: #0f172a; padding-left: 6px; }
    .company-info h1 { font-size: 15pt; font-weight: 700; color: #0f172a; }
    .company-info .ruc { font-size: 9.5pt; color: #475569; margin-top: 4px; }

    .comprobante-box { background: #f8fbff; border: 1px solid #2b6ee6; padding: 8px 12px; border-radius: 8px; text-align: center; min-width: 220px; }
    .comprobante-box h2 { font-size: 11pt; color: #2b6ee6; margin-bottom: 4px; font-weight: 700; }
    .comprobante-box .numero { font-size: 13pt; font-weight: 800; color: #0b254a; }

    .info-section { padding: 12px; background: #fbfdff; border-bottom: 1px solid #eef5ff; margin-top: 6px; }
    .info-row { display: table; width: 100%; margin-bottom: 6px; }
    .info-label { display: table-cell; width: 140px; font-weight: 700; color: #334155; }
    .info-value { display: table-cell; color: #0f172a; }

    /* Items table */
    .items-section { padding: 8px 6px 0 6px; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10pt; }
    .items-table thead { background: #2b6ee6; color: #fff; }
    .items-table th, .items-table td { padding: 10px 8px; border-bottom: 1px solid #e6eefc; }
    .items-table th { text-align: left; font-weight: 700; }
    .items-table td { color: #0f172a; }

    /* Totals - right column */
    .totals { float: right; width: 320px; margin-top: 6px; }
    .total-row { display: flex; justify-content: space-between; padding: 6px 0; color: #334155; }
    .total-label { font-weight: 700; }
    .total-value { font-weight: 700; }
    .total-final { background: #2b6ee6; color: #fff; padding: 10px; border-radius: 8px; margin-top: 8px; display: flex; justify-content: space-between; align-items: center; }
    .total-final .label { font-size: 11pt; font-weight: 800; }
    .total-final .value { font-size: 13pt; font-weight: 900; }

    .amount-text { background: #f6f9ff; padding: 10px; border-radius: 6px; margin: 12px 0; font-style: italic; color: #475569; }

    .footer { clear: both; margin-top: 20px; padding: 12px; text-align: center; border-top: 1px solid #e6eefc; color: #64748b; font-size: 9pt; }
    .footer .badge { display: inline-block; background: #e6f9ef; color: #047857; padding: 4px 10px; border-radius: 12px; font-weight: 700; margin-top: 6px; }
    .qr-section { margin-top: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabecera -->
        <div class="header">
            <div class="header-row">
                <div class="logo-area">
                    @php $logoPath = public_path('images/logo.png'); @endphp
                    @if(file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo">
                    @else
                        <div class="logo-fallback">{{ strtoupper(substr($empresa['razon_social'] ?? 'AR', 0, 2)) }}</div>
                    @endif
                </div>

                <div class="company-info">
                    <h1>{{ $empresa['razon_social'] }}</h1>
                    <div class="ruc">RUC: {{ $empresa['ruc'] }}</div>
                    <div style="font-size:9pt; margin-top:4px;">{{ $empresa['direccion'] }}</div>
                </div>

                <div class="comprobante-box">
                    <h2>BOLETA DE VENTA ELECTRÓNICA</h2>
                    <div class="numero">{{ $comprobante['serie'] }}-{{ $comprobante['numero'] }}</div>
                </div>
            </div>
        </div>

        <!-- Información Empresa -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Dirección:</div>
                <div class="info-value">{{ $empresa['direccion'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $empresa['telefono'] ?? '(01) 234-5678' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $empresa['email'] ?? 'contacto@arludent.com' }}</div>
            </div>
        </div>

        <!-- Información Cliente -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Cliente:</div>
                <div class="info-value">{{ $cliente['nombre'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ $cliente['tipo_documento'] == '1' ? 'DNI' : 'CE' }}:</div>
                <div class="info-value">{{ $cliente['numero_documento'] }}</div>
            </div>
            @if(!empty($cliente['direccion']))
            <div class="info-row">
                <div class="info-label">Dirección:</div>
                <div class="info-value">{{ $cliente['direccion'] }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Fecha de Emisión:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($comprobante['fecha_emision'])->format('d/m/Y H:i:s') }}</div>
            </div>
        </div>

        <!-- Items -->
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">Cant.</th>
                        <th>Descripción</th>
                        <th class="text-right" style="width: 100px;">P. Unit.</th>
                        <th class="text-right" style="width: 100px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td class="text-center">{{ number_format($item['cantidad'], 0) }}</td>
                        <td>{{ $item['descripcion'] }}</td>
                        <td class="text-right">S/ {{ number_format($item['precio_unitario'], 2) }}</td>
                        <td class="text-right">S/ {{ number_format($item['total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totales -->
            <div class="totals">
                <div class="total-row">
                    <div class="total-label">Subtotal:</div>
                    <div class="total-value">S/ {{ number_format($totales['subtotal'], 2) }}</div>
                </div>
                <div class="total-row">
                    <div class="total-label">IGV (18%):</div>
                    <div class="total-value">S/ {{ number_format($totales['igv'], 2) }}</div>
                </div>
                <div class="total-row total-final">
                    <div class="total-label">TOTAL:</div>
                    <div class="total-value">S/ {{ number_format($totales['total'], 2) }}</div>
                </div>
            </div>

            <div style="clear: both;"></div>

            <!-- Monto en letras -->
            <div class="amount-text">
                <strong>SON:</strong> {{ $totales['total_letras'] }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                <strong>REPRESENTACIÓN IMPRESA DE LA BOLETA DE VENTA ELECTRÓNICA</strong><br>
                <span class="badge">SISTEMA ARLUDENT</span><br><br>
                Consulta tu comprobante en: www.arludent.com<br>
                Este documento es generado electrónicamente y no requiere firma<br>
                <br>
                @if(!empty($comprobante['observaciones']))
                <strong>Observaciones:</strong> {{ $comprobante['observaciones'] }}<br>
                @endif
            </div>

            @if(!empty($comprobante['qr_code']))
            <div class="qr-section">
                <strong>Código QR para verificación</strong><br>
                <img src="{{ $comprobante['qr_code'] }}" alt="QR Code" style="width: 100px; height: 100px; margin-top: 10px;">
            </div>
            @endif
        </div>
    </div>
</body>
</html>
