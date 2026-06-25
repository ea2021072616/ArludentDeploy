<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Clínico - {{ $historial->codigo_historial ?? 'Sin código' }}</title>
    <style>
    @page { margin: 15mm 20mm 15mm 20mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #1e293b; padding: 0; }

    .container { max-width: 820px; margin: 0 auto; }

    /* Header */
    .header { background: #1e3a5f; color: #fff; padding: 14px 18px;  margin-bottom: 12px; }
    .header-row { display: table; width: 100%; }
    .header-left { display: table-cell; vertical-align: middle; width: 60%; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 40%; }
    .header h1 { font-size: 16pt; font-weight: 800; margin-bottom: 2px; }
    .header .subtitle { font-size: 9pt; color: #93c5fd; }
    .header .codigo { background: rgba(255,255,255,0.15); padding: 6px 12px; border-radius: 6px; display: inline-block; font-size: 11pt; font-weight: 700; }
    .header .fecha { font-size: 8pt; color: #bfdbfe; margin-top: 4px; }

    /* Section headers */
    .section { margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
    .section-header { background: #f1f5f9; padding: 8px 14px; border-bottom: 1px solid #e2e8f0; }
    .section-header h2 { font-size: 11pt; font-weight: 700; color: #1e3a5f; }
    .section-header.fase-i { background: #ecfeff; border-bottom-color: #a5f3fc; }
    .section-header.fase-i h2 { color: #0e7490; }
    .section-header.fase-ii { background: #fef3c7; border-bottom-color: #fcd34d; }
    .section-header.fase-ii h2 { color: #92400e; }
    .section-header.fase-iii { background: #ede9fe; border-bottom-color: #c4b5fd; }
    .section-header.fase-iii h2 { color: #5b21b6; }
    .section-header.tratamientos { background: #dcfce7; border-bottom-color: #86efac; }
    .section-header.tratamientos h2 { color: #166534; }
    .section-header.consultas { background: #fce7f3; border-bottom-color: #f9a8d4; }
    .section-header.consultas h2 { color: #9d174d; }
    .section-header.prescripciones { background: #fff7ed; border-bottom-color: #fdba74; }
    .section-header.prescripciones h2 { color: #9a3412; }
    .section-body { padding: 12px 14px; }

    /* Data grid */
    .data-grid { display: table; width: 100%; }
    .data-row { display: table-row; }
    .data-label { display: table-cell; width: 160px; padding: 4px 8px 4px 0; font-weight: 700; color: #475569; font-size: 9pt; vertical-align: top; }
    .data-value { display: table-cell; padding: 4px 0; color: #1e293b; font-size: 9.5pt; vertical-align: top; }

    /* Two column layout */
    .two-col { display: table; width: 100%; }
    .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
    .col:last-child { padding-right: 0; padding-left: 10px; }

    /* Tables */
    .data-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    .data-table thead { background: #f8fafc; }
    .data-table th { padding: 8px 10px; text-align: left; font-weight: 700; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 8.5pt; text-transform: uppercase; }
    .data-table td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .data-table tr:last-child td { border-bottom: none; }

    /* Badges */
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8pt; font-weight: 700; }
    .badge-bueno { background: #dcfce7; color: #166534; }
    .badge-regular { background: #fef3c7; color: #92400e; }
    .badge-malo { background: #fecaca; color: #991b1b; }
    .badge-en-curso { background: #dbeafe; color: #1e40af; }
    .badge-completado { background: #dcfce7; color: #166534; }
    .badge-sugerido { background: #f3e8ff; color: #6b21a8; }
    .badge-cancelado { background: #f1f5f9; color: #64748b; }
    .badge-si { background: #dcfce7; color: #166534; }
    .badge-no { background: #f1f5f9; color: #64748b; }

    /* Footer */
    .footer { margin-top: 16px; padding: 10px 14px; text-align: center; border-top: 2px solid #e2e8f0; color: #94a3b8; font-size: 8pt; }
    .footer .clinica { font-weight: 700; color: #64748b; }

    /* Separator */
    .separator { border: none; border-top: 1px dashed #cbd5e1; margin: 8px 0; }

    /* Empty state */
    .empty { color: #94a3b8; font-style: italic; font-size: 9pt; padding: 6px 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-row">
                <div class="header-left">
                    <h1>ARLUDENT</h1>
                    <div class="subtitle">Clínica Dental — Historial Clínico del Paciente</div>
                </div>
                <div class="header-right">
                    <div class="codigo">{{ $historial->codigo_historial ?? 'N/A' }}</div>
                    <div class="fecha">Generado: {{ now()->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <!-- FASE I: DATOS PERSONALES -->
        <div class="section">
            <div class="section-header fase-i">
                <h2>FASE I. DATOS PERSONALES</h2>
            </div>
            <div class="section-body">
                <div class="two-col">
                    <div class="col">
                        <div class="data-grid">
                            <div class="data-row">
                                <div class="data-label">Apellidos:</div>
                                <div class="data-value">{{ $historial->paciente->apellidos ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Nombres:</div>
                                <div class="data-value">{{ $historial->paciente->nombres ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">DNI:</div>
                                <div class="data-value">{{ $historial->paciente->dni ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Fecha de Nacimiento:</div>
                                <div class="data-value">{{ $historial->paciente->fecha_nacimiento ? \Carbon\Carbon::parse($historial->paciente->fecha_nacimiento)->format('d/m/Y') : '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Sexo:</div>
                                <div class="data-value">{{ $historial->paciente->sexo == 'M' ? 'Masculino' : ($historial->paciente->sexo == 'F' ? 'Femenino' : ($historial->paciente->sexo ?? '—')) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="data-grid">
                            <div class="data-row">
                                <div class="data-label">Domicilio:</div>
                                <div class="data-value">{{ $historial->paciente->domicilio ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Teléfono:</div>
                                <div class="data-value">{{ $historial->paciente->usuario->telefono ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Correo:</div>
                                <div class="data-value">{{ $historial->paciente->usuario->correo ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Grupo Sanguíneo:</div>
                                <div class="data-value">{{ $historial->paciente->grupo_sanguineo ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Responsable:</div>
                                <div class="data-value">{{ $historial->paciente->persona_responsable ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($historial->paciente->alergias)
                <hr class="separator">
                <div class="data-grid">
                    <div class="data-row">
                        <div class="data-label">Alergias:</div>
                        <div class="data-value">{{ $historial->paciente->alergias }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- FASE II: ANAMNESIS -->
        <div class="section">
            <div class="section-header fase-ii">
                <h2>FASE II. ANAMNESIS</h2>
            </div>
            <div class="section-body">
                <div class="two-col">
                    <div class="col">
                        <div class="data-grid">
                            <div class="data-row">
                                <div class="data-label">Síntoma Principal:</div>
                                <div class="data-value">{{ $historial->sintoma_principal ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Tiempo de Inicio:</div>
                                <div class="data-value">{{ $historial->tiempo_inicio_sintomas ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Tratamiento Previo:</div>
                                <div class="data-value">{{ $historial->tratamiento_previo ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Enfermedades Actuales:</div>
                                <div class="data-value">{{ $historial->enfermedades_actuales ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="data-grid">
                            <div class="data-row">
                                <div class="data-label">Bajo Trat. Médico:</div>
                                <div class="data-value">
                                    <span class="badge {{ $historial->bajo_tratamiento_medico ? 'badge-si' : 'badge-no' }}">{{ $historial->bajo_tratamiento_medico ? 'Sí' : 'No' }}</span>
                                </div>
                            </div>
                            @if($historial->bajo_tratamiento_medico && $historial->detalle_tratamiento_actual)
                            <div class="data-row">
                                <div class="data-label">Detalle:</div>
                                <div class="data-value">{{ $historial->detalle_tratamiento_actual }}</div>
                            </div>
                            @endif
                            <div class="data-row">
                                <div class="data-label">Alergias Paciente:</div>
                                <div class="data-value">{{ $historial->alergias_paciente ?? '—' }}</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Cirugías Previas:</div>
                                <div class="data-value">
                                    <span class="badge {{ $historial->intervenciones_quirurgicas_previas ? 'badge-si' : 'badge-no' }}">{{ $historial->intervenciones_quirurgicas_previas ? 'Sí' : 'No' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($historial->intervenciones_quirurgicas_previas && $historial->detalle_intervenciones)
                <hr class="separator">
                <div class="data-grid">
                    <div class="data-row">
                        <div class="data-label">Detalle Cirugías:</div>
                        <div class="data-value">{{ $historial->detalle_intervenciones }}</div>
                    </div>
                </div>
                @endif
                <hr class="separator">
                <div class="two-col">
                    <div class="col">
                        <div class="data-grid">
                            <div class="data-row">
                                <div class="data-label">Hemorragia Post-Trat.:</div>
                                <div class="data-value">
                                    <span class="badge {{ $historial->hemorragia_post_tratamiento ? 'badge-si' : 'badge-no' }}">{{ $historial->hemorragia_post_tratamiento ? 'Sí' : 'No' }}</span>
                                </div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Prob. c/ Anestesia:</div>
                                <div class="data-value">
                                    <span class="badge {{ $historial->problema_anestesia ? 'badge-si' : 'badge-no' }}">{{ $historial->problema_anestesia ? 'Sí' : 'No' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="data-grid">
                            <div class="data-row">
                                <div class="data-label">Dific. Abrir/Masticar:</div>
                                <div class="data-value">
                                    <span class="badge {{ $historial->dificultad_abrir_masticar ? 'badge-si' : 'badge-no' }}">{{ $historial->dificultad_abrir_masticar ? 'Sí' : 'No' }}</span>
                                </div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Sensibilidad Dental:</div>
                                <div class="data-value">
                                    <span class="badge {{ $historial->sensibilidad_dental ? 'badge-si' : 'badge-no' }}">{{ $historial->sensibilidad_dental ? 'Sí' : 'No' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FASE III: DIAGNÓSTICO -->
        <div class="section">
            <div class="section-header fase-iii">
                <h2>FASE III. DIAGNÓSTICO</h2>
            </div>
            <div class="section-body">
                <div class="data-grid">
                    <div class="data-row">
                        <div class="data-label">Motivo de Consulta:</div>
                        <div class="data-value">{{ $historial->motivo_consulta ?? '—' }}</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Diag. Presuntivo:</div>
                        <div class="data-value">{{ $historial->diagnostico_presuntivo ?? '—' }}</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Diag. Principal:</div>
                        <div class="data-value">{{ $historial->diagnostico_principal ?? '—' }}</div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Higiene Bucal:</div>
                        <div class="data-value">
                            @if($historial->higiene_bucal)
                                @php
                                    $badgeClass = match($historial->higiene_bucal) {
                                        'Bueno' => 'badge-bueno',
                                        'Regular' => 'badge-regular',
                                        'Malo' => 'badge-malo',
                                        default => ''
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $historial->higiene_bucal }}</span>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
                @if($historial->medicoResponsable)
                <hr class="separator">
                <div class="data-grid">
                    <div class="data-row">
                        <div class="data-label">Médico Responsable:</div>
                        <div class="data-value">Dr(a). {{ $historial->medicoResponsable->nombres }} {{ $historial->medicoResponsable->apellidos }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- ODONTOGRAMA -->
        @if($historial->odontograma_image)
        <div class="section">
            <div class="section-header" style="background: #3B82F6;">
                <h2>ODONTOGRAMA</h2>
            </div>
            <div class="section-body text-center" style="padding: 15px; text-align: center;">
                <img src="{{ $historial->odontograma_image }}" alt="Odontograma" style="max-width: 100%; height: auto; border: 1px solid #E5E7EB; border-radius: 8px;">
            </div>
        </div>
        @endif

        <!-- TRATAMIENTOS -->
        @if($historial->tratamientos && $historial->tratamientos->count() > 0)
        <div class="section">
            <div class="section-header tratamientos">
                <h2>TRATAMIENTOS ({{ $historial->tratamientos->count() }})</h2>
            </div>
            <div class="section-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Tratamiento</th>
                            <th style="width: 22%;">Descripción</th>
                            <th style="width: 14%;">F. Inicio</th>
                            <th style="width: 14%;">F. Fin</th>
                            <th style="width: 10%;">Estado</th>
                            <th style="width: 10%;">Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial->tratamientos as $trat)
                        <tr>
                            <td><strong>{{ $trat->tratamiento->nombre_tratamiento ?? '—' }}</strong></td>
                            <td>{{ $trat->descripcion ?? '—' }}</td>
                            <td>{{ $trat->fecha_inicio ? \Carbon\Carbon::parse($trat->fecha_inicio)->format('d/m/Y') : '—' }}</td>
                            <td>{{ $trat->fecha_fin ? \Carbon\Carbon::parse($trat->fecha_fin)->format('d/m/Y') : '—' }}</td>
                            <td>
                                @php
                                    $estadoClass = match($trat->estado) {
                                        'en_curso' => 'badge-en-curso',
                                        'completado' => 'badge-completado',
                                        'sugerido' => 'badge-sugerido',
                                        'cancelado' => 'badge-cancelado',
                                        default => ''
                                    };
                                    $estadoLabel = match($trat->estado) {
                                        'en_curso' => 'En Curso',
                                        'completado' => 'Completado',
                                        'sugerido' => 'Sugerido',
                                        'cancelado' => 'Cancelado',
                                        default => $trat->estado
                                    };
                                @endphp
                                <span class="badge {{ $estadoClass }}">{{ $estadoLabel }}</span>
                            </td>
                            <td>{{ $trat->precio ? 'S/ ' . number_format($trat->precio, 2) : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- CONSULTAS Y CONTROLES -->
        @if($historial->detalles && $historial->detalles->count() > 0)
        <div class="section">
            <div class="section-header consultas">
                <h2>CONSULTAS Y CONTROLES ({{ $historial->detalles->count() }})</h2>
            </div>
            <div class="section-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 14%;">Fecha</th>
                            <th style="width: 28%;">Diagnóstico</th>
                            <th style="width: 28%;">Procedimiento</th>
                            <th style="width: 15%;">Notas</th>
                            <th style="width: 15%;">Médico</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial->detalles->sortByDesc('fecha') as $detalle)
                        <tr>
                            <td>{{ $detalle->fecha ? \Carbon\Carbon::parse($detalle->fecha)->format('d/m/Y') : '—' }}</td>
                            <td>{{ $detalle->diagnostico ?? '—' }}</td>
                            <td>{{ $detalle->procedimiento_realizado ?? '—' }}</td>
                            <td>{{ $detalle->notas_medicas ?? '—' }}</td>
                            <td>{{ $detalle->realizadoPor ? 'Dr(a). ' . $detalle->realizadoPor->apellidos : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- PRESCRIPCIONES -->
        @if($historial->prescripciones && $historial->prescripciones->count() > 0)
        <div class="section">
            <div class="section-header prescripciones">
                <h2>PRESCRIPCIONES ({{ $historial->prescripciones->count() }})</h2>
            </div>
            <div class="section-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Medicamento</th>
                            <th style="width: 20%;">Dosis</th>
                            <th style="width: 15%;">Frecuencia</th>
                            <th style="width: 15%;">Duración</th>
                            <th style="width: 30%;">Indicaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial->prescripciones as $prescripcion)
                        <tr>
                            <td><strong>{{ $prescripcion->medicamento ?? '—' }}</strong></td>
                            <td>{{ $prescripcion->dosis ?? '—' }}</td>
                            <td>{{ $prescripcion->frecuencia ?? '—' }}</td>
                            <td>{{ $prescripcion->duracion ?? '—' }}</td>
                            <td>{{ $prescripcion->indicaciones ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            <div class="clinica">CLÍNICA DENTAL ARLUDENT S.A.C.</div>
            <div>Av. Principal 123, Lima, Perú • Tel: (01) 234-5678 • contacto@arludent.com</div>
            <div style="margin-top: 4px;">Documento generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }} hrs. — Este documento es de uso confidencial.</div>
        </div>
    </div>
</body>
</html>
