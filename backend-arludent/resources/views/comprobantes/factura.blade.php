<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica</title>
    <style>
    @page { margin: 6mm 8mm 6mm 8mm; }
    *{ margin:0; padding:0; box-sizing:border-box; }
    body{ font-family: Arial, Helvetica, sans-serif; font-size:11pt; color:#263238; padding:8px; }
    .container{ max-width:820px; margin:4px auto; border:1px solid #fff0f0; padding:10px; border-radius:6px; }

    .header{ background:#fff; padding:8px 10px; margin-bottom:8px; }
    .header-row{ display:flex; align-items:center; gap:12px; }
    .logo-area img{ max-width:130px; max-height:64px; object-fit:contain; }
    .company-info{ flex:1 1 auto; color:#0f172a; padding-left:6px; }
    .company-info h1{ font-size:15pt; font-weight:700; }
    .company-info .ruc{ font-size:9.5pt; color:#6b1b1b; margin-top:4px; }

    .comprobante-box{ background:#fff8f8; border:1px solid #c53030; padding:8px 12px; border-radius:8px; min-width:220px; text-align:center; }
    .comprobante-box h2{ font-size:11pt; color:#b91c1c; margin-bottom:4px; font-weight:700; }
    .comprobante-box .numero{ font-size:13pt; font-weight:800; color:#3a2a2a; }

    .info-section{ padding:12px; background:#fff8f8; border-bottom:1px solid #fff0f0; margin-top:6px; }
    .info-row{ display:table; width:100%; margin-bottom:6px; }
    .info-label{ display:table-cell; width:140px; font-weight:700; color:#7f1d1d; }
    .info-value{ display:table-cell; color:#0f172a; }

    .items-section{ padding:8px 6px 0 6px; }
    .items-table{ width:100%; border-collapse:collapse; margin-bottom:14px; font-size:10pt; }
    .items-table thead{ background:#b91c1c; color:#fff; }
    .items-table th, .items-table td{ padding:10px 8px; border-bottom:1px solid #fff0f0; }
    .items-table th{ text-align:left; font-weight:700; }

    .totals{ float:right; width:320px; margin-top:6px; }
    .total-row{ display:flex; justify-content:space-between; padding:6px 0; color:#4a2a2a; }
    .total-label{ font-weight:700; }
    .total-value{ font-weight:700; }
    .total-final{ background:#b91c1c; color:#fff; padding:10px; border-radius:8px; margin-top:8px; display:flex; justify-content:space-between; align-items:center; }
    .total-final .label{ font-size:11pt; font-weight:800; }
    .total-final .value{ font-size:13pt; font-weight:900; }

    .amount-text{ background:#fff5f5; padding:10px; border-radius:6px; margin:12px 0; font-style:italic; color:#7f1d1d; }
    .condiciones-pago{ background:#fff7ed; border-left:4px solid #f97316; padding:12px; margin:12px 0; }

    .footer{ clear:both; margin-top:20px; padding:12px; text-align:center; border-top:1px solid #fff0f0; color:#64748b; font-size:9pt; }
    .footer .badge{ display:inline-block; background:#fff0f0; color:#b91c1c; padding:4px 10px; border-radius:12px; font-weight:700; margin-top:6px; }
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
                    <h2>FACTURA ELECTRÓNICA</h2>
                    <div class="numero">{{ $comprobante['serie'] }}-{{ $comprobante['numero'] }}</div>
                </div>
            </div>
        </div>

        <!-- Información Empresa -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Dirección Fiscal:</div>
                <div class="info-value">{{ $empresa['direccion'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $empresa['telefono'] ?? '(01) 234-5678' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $empresa['email'] ?? 'facturacion@arludent.com' }}</div>
            </div>
        </div>

        <!-- Información Cliente -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Señor(es):</div>
                <div class="info-value"><strong>{{ $cliente['razon_social'] }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">RUC:</div>
                <div class="info-value"><strong>{{ $cliente['ruc'] }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Dirección:</div>
                <div class="info-value">{{ $cliente['direccion'] ?? 'No especificada' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Emisión:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($comprobante['fecha_emision'])->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Vencimiento:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($comprobante['fecha_emision'])->addDays(30)->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Moneda:</div>
                <div class="info-value">SOLES (PEN)</div>
            </div>
        </div>

        <!-- Condiciones de Pago -->
        <div class="condiciones-pago">
            <strong>Condiciones de Pago:</strong> Contado | <strong>Forma de Pago:</strong> {{ ucfirst($comprobante['forma_pago'] ?? 'Efectivo') }}
        </div>

        <!-- Items -->
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">Cant.</th>
                        <th>Descripción del Servicio</th>
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
                    <div class="total-label">Op. Gravada:</div>
                    <div class="total-value">S/ {{ number_format($totales['subtotal'], 2) }}</div>
                </div>
                <div class="total-row">
                    <div class="total-label">Op. Exonerada:</div>
                    <div class="total-value">S/ 0.00</div>
                </div>
                <div class="total-row">
                    <div class="total-label">Op. Inafecta:</div>
                    <div class="total-value">S/ 0.00</div>
                </div>
                <div class="total-row">
                    <div class="total-label">IGV (18%):</div>
                    <div class="total-value">S/ {{ number_format($totales['igv'], 2) }}</div>
                </div>
                <div class="total-row total-final">
                    <div class="total-label">IMPORTE TOTAL:</div>
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
                <strong>REPRESENTACIÓN IMPRESA DE LA FACTURA ELECTRÓNICA</strong><br>
                <span class="badge">SISTEMA ARLUDENT - FACTURACIÓN ELECTRÓNICA</span><br><br>
                Puede verificar este documento en: www.arludent.com<br>
                Autorizado mediante Resolución de Superintendencia SUNAT<br>
                <br>
                @if(!empty($comprobante['observaciones']))
                <strong>Observaciones:</strong> {{ $comprobante['observaciones'] }}<br>
                @endif
                <br>
                <small style="color: #9ca3af;">
                    Para consultas sobre su factura, comuníquese con nuestro departamento de facturación<br>
                    Email: facturacion@arludent.com | Teléfono: (01) 234-5678
                </small>
            </div>

            @if(!empty($comprobante['qr_code']))
            <div class="qr-section">
                <strong>Código QR para verificación SUNAT</strong><br>
                <img src="{{ $comprobante['qr_code'] }}" alt="QR Code" style="width: 100px; height: 100px; margin-top: 10px;">
            </div>
            @endif
        </div>
    </div>
</body>
</html>
