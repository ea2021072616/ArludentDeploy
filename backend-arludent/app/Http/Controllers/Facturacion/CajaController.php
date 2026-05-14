<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Paciente;
use App\Services\SunatService;
use App\Services\PdfComprobanteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CajaController extends Controller
{
    protected SunatService $sunatService;
    protected PdfComprobanteService $pdfService;

    public function __construct(SunatService $sunatService, PdfComprobanteService $pdfService)
    {
        $this->sunatService = $sunatService;
        $this->pdfService = $pdfService;
    }

    /**
     * Listar pagos/transacciones con filtros
     */
    public function listarPagos(Request $request)
    {
        try {
            $query = Pago::with(['paciente', 'cita', 'registradoPor']);

            // Filtros
            if ($request->filled('estado_pago')) {
                $query->where('estado_pago', $request->estado_pago);
            }

            if ($request->filled('tipo_comprobante')) {
                $query->where('tipo_comprobante', $request->tipo_comprobante);
            }

            if ($request->filled('metodo_pago')) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_pago', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_pago', '<=', $request->fecha_hasta);
            }

            if ($request->filled('buscar_paciente')) {
                $query->whereHas('paciente', function ($q) use ($request) {
                    $q->where('nombres', 'like', "%{$request->buscar_paciente}%")
                      ->orWhere('apellidos', 'like', "%{$request->buscar_paciente}%")
                      ->orWhere('dni', 'like', "%{$request->buscar_paciente}%");
                });
            }

            if ($request->filled('buscar_comprobante')) {
                $query->where(function ($q) use ($request) {
                    $q->where('serie_comprobante', 'like', "%{$request->buscar_comprobante}%")
                      ->orWhere('numero_comprobante', 'like', "%{$request->buscar_comprobante}%");
                });
            }

            // Paginación
            $perPage = $request->input('per_page', 25);
            $pagos = $query->orderBy('created_at', 'DESC')->paginate($perPage);

            // Mapear items para incluir campos computados y evitar inconsistencias
            $items = collect($pagos->items())->map(function ($pago) {
                // Si ya es un modelo, obtener sus atributos como array
                $base = $pago instanceof \Illuminate\Database\Eloquent\Model ? $pago->toArray() : (array) $pago;

                // Añadir explícitamente campos computados que el frontend espera
                $base['tiene_comprobante'] = $pago->tiene_comprobante ?? false;
                $base['comprobante_completo'] = $pago->comprobante_completo ?? null;
                $base['aceptado_sunat'] = $pago->aceptado_sunat ?? false;

                return $base;
            })->all();

            return $this->successResponse([
                'pagos' => $items,
                'pagination' => [
                    'current_page' => $pagos->currentPage(),
                    'last_page' => $pagos->lastPage(),
                    'per_page' => $pagos->perPage(),
                    'total' => $pagos->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al listar pagos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Estadísticas de caja
     */
    public function estadisticasCaja(Request $request)
    {
        try {
            $fechaInicio = $request->input('fecha_desde', Carbon::today()->toDateString());
            $fechaFin = $request->input('fecha_hasta', Carbon::today()->toDateString());

            // Total de pagos
            $totalPagos = Pago::whereBetween('fecha_pago', [$fechaInicio, $fechaFin])->count();

            // Monto total recaudado (solo pagados)
            $montoTotal = Pago::pagados()
                ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                ->sum('monto');

            // Pagos pendientes
            $pagosPendientes = Pago::pendientes()
                ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                ->count();

            $montoPendiente = Pago::pendientes()
                ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                ->sum('monto');

            // Comprobantes emitidos hoy
            $comprobantesHoy = Pago::conComprobante()
                ->whereDate('fecha_emision_comprobante', Carbon::today())
                ->count();

            // Por método de pago
            $porMetodo = Pago::pagados()
                ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                ->select('metodo_pago', DB::raw('SUM(monto) as total'))
                ->groupBy('metodo_pago')
                ->get()
                ->mapWithKeys(fn($item) => [$item->metodo_pago => floatval($item->total)]);

            // Por tipo de comprobante
            $porTipoComprobante = Pago::whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                ->select('tipo_comprobante', DB::raw('COUNT(*) as cantidad'))
                ->groupBy('tipo_comprobante')
                ->get()
                ->mapWithKeys(fn($item) => [$item->tipo_comprobante => $item->cantidad]);

            // Últimos 7 días (para gráfica)
            $ultimos7Dias = [];
            for ($i = 6; $i >= 0; $i--) {
                $fecha = Carbon::today()->subDays($i);
                $monto = Pago::pagados()
                    ->whereDate('fecha_pago', $fecha)
                    ->sum('monto');
                $ultimos7Dias[] = [
                    'fecha' => $fecha->format('Y-m-d'),
                    'monto' => floatval($monto)
                ];
            }

            return $this->successResponse([
                'total_pagos' => $totalPagos,
                'monto_total' => floatval($montoTotal),
                'pagos_pendientes' => $pagosPendientes,
                'monto_pendiente' => floatval($montoPendiente),
                'comprobantes_hoy' => $comprobantesHoy,
                'por_metodo' => $porMetodo,
                'por_tipo_comprobante' => $porTipoComprobante,
                'ultimos_7_dias' => $ultimos7Dias,
                'rango_fechas' => [
                    'desde' => $fechaInicio,
                    'hasta' => $fechaFin
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener estadísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Registrar un nuevo pago
     */
    public function registrarPago(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_paciente' => 'required|exists:pacientes,id_paciente',
            'id_cita' => 'nullable|exists:citas,id_cita',
            'concepto' => 'required|string|max:200',
            'monto' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia,cheque,yape,plin,otros',
            'fecha_pago' => 'required|date',
            'notas' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $pago = Pago::create([
                'id_paciente' => $request->id_paciente,
                'id_cita' => $request->id_cita,
                'concepto' => $request->concepto,
                'monto' => $request->monto,
                'metodo_pago' => $request->metodo_pago,
                'estado_pago' => 'pagado',
                'fecha_pago' => $request->fecha_pago,
                'notas' => $request->notas,
                'registrado_por' => auth('api')->id() ?? 1,
                'tipo_comprobante' => 'ninguno', // Sin comprobante por defecto
            ]);

            DB::commit();

            return $this->successResponse([
                'mensaje' => 'Pago registrado exitosamente',
                'pago' => $pago->load(['paciente.usuario', 'cita'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al registrar pago: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Emitir comprobante (boleta o factura) para un pago existente
     */
    public function emitirComprobante(Request $request, $idPago)
    {
        \Log::info('📤 Emitir comprobante request:', [
            'id_pago' => $idPago,
            'data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'tipo_comprobante' => 'required|in:boleta,factura',
            'serie' => 'required|string|max:20',
            'tipo_documento_cliente' => 'required|string',
            'numero_documento_cliente' => 'required|string',
            'nombre_cliente' => 'required|string',
            'direccion_cliente' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::error('❌ Validación fallida:', $validator->errors()->toArray());
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $pago = Pago::findOrFail($idPago);

            \Log::info('✅ Pago encontrado:', [
                'id' => $pago->id_pago,
                'tiene_comprobante' => $pago->tiene_comprobante,
                'tipo_actual' => $pago->tipo_comprobante,
                'serie_actual' => $pago->serie_comprobante,
                'numero_actual' => $pago->numero_comprobante
            ]);

            // Verificar que no tenga comprobante ya
            if ($pago->tiene_comprobante) {
                return $this->errorResponse('Este pago ya tiene un comprobante emitido', 400);
            }

            // Calcular IGV
            $montos = Pago::calcularIGV($pago->monto);

            // Normalizar serie: preferir la serie enviada, pero forzar prefijo según tipo
            // (Factura -> 'F', Boleta -> 'B'). Si la serie no tiene formato válido, usar F001/B001.
            $requestedSerie = strtoupper($request->serie ?? '');
            $expectedPrefix = $request->tipo_comprobante === 'factura' ? 'F' : 'B';

            if (preg_match('/^[A-Z]\d+$/', $requestedSerie)) {
                if ($requestedSerie[0] !== $expectedPrefix) {
                    // Reemplazar prefijo por el esperado, mantener el resto
                    $serie = $expectedPrefix . substr($requestedSerie, 1);
                } else {
                    $serie = $requestedSerie;
                }
            } else {
                // Fallback por defecto
                $serie = $expectedPrefix . '001';
            }

            // Generar siguiente número usando la serie normalizada
            $numero = Pago::siguienteNumero($request->tipo_comprobante, $serie);

            // Datos para SUNAT (usar la serie normalizada)
            $datosSunat = [
                'serie' => $serie,
                'numero' => $numero,
                'ruc_emisor' => config('app.ruc_clinica', '20123456789'),
                'razon_social_emisor' => config('app.razon_social_clinica', 'CLINICA DENTAL ARLUDENT S.A.C.'),
                'direccion_emisor' => config('app.direccion_clinica', 'Lima, Perú'),
                'tipo_documento_cliente' => $request->tipo_documento_cliente,
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'nombre_cliente' => $request->nombre_cliente,
                'direccion_cliente' => $request->direccion_cliente ?? '',
                'items' => [[
                    'descripcion' => $pago->concepto,
                    'cantidad' => 1,
                    'precio_unitario' => $montos['subtotal'],
                    'total' => $montos['subtotal']
                ]],
                'subtotal' => $montos['subtotal'],
                'igv' => $montos['igv'],
                'total' => $montos['total'],
                'observaciones' => $pago->notas ?? '',
            ];

            \Log::info('📋 Datos preparados para SUNAT:', [
                'tipo' => $request->tipo_comprobante,
                'serie' => $datosSunat['serie'],
                'numero' => $numero,
                'tipo_doc_cliente' => $datosSunat['tipo_documento_cliente'],
                'num_doc_cliente' => $datosSunat['numero_documento_cliente'],
                'nombre_cliente' => $datosSunat['nombre_cliente'],
                'montos' => $montos
            ]);

            // Emitir en SUNAT
            if ($request->tipo_comprobante === 'boleta') {
                \Log::info('🔵 Emitiendo BOLETA...');
                $respuestaSunat = $this->sunatService->emitirBoleta($datosSunat);
            } else {
                \Log::info('🟢 Emitiendo FACTURA...');
                $respuestaSunat = $this->sunatService->emitirFactura($datosSunat);
            }

            \Log::info('📨 Respuesta SUNAT:', [
                'success' => $respuestaSunat['success'] ?? false,
                'data' => $respuestaSunat
            ]);

            if (!$respuestaSunat['success']) {
                return $this->errorResponse('Error al emitir comprobante en SUNAT', 500);
            }

            // Actualizar pago con datos del comprobante (usar la serie normalizada)
            $pago->update([
                'tipo_comprobante' => $request->tipo_comprobante,
                'serie_comprobante' => $serie,
                'numero_comprobante' => $numero,
                'ruc_emisor' => $datosSunat['ruc_emisor'],
                'razon_social_emisor' => $datosSunat['razon_social_emisor'],
                'tipo_documento_cliente' => $request->tipo_documento_cliente,
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'nombre_cliente' => $request->nombre_cliente,
                'direccion_cliente' => $request->direccion_cliente,
                'subtotal' => $montos['subtotal'],
                'igv' => $montos['igv'],
                'total' => $montos['total'],
                'estado_sunat' => 'aceptado',
                'respuesta_sunat' => $respuestaSunat,
                'hash_comprobante' => $respuestaSunat['data']['hash'] ?? null,
                'codigo_qr' => $respuestaSunat['data']['codigo_qr'] ?? null,
                'enlace_pdf' => null, // Se generará localmente
                'enlace_xml' => $respuestaSunat['data']['enlace_xml'] ?? null,
                'fecha_emision_comprobante' => now(),
            ]);

            // 🎨 GENERAR PDF LOCAL (NO SUNAT, ES NUESTRO PDF)
            \Log::info('📄 Generando PDF local...');
            try {
                if ($request->tipo_comprobante === 'boleta') {
                    $pdfInfo = $this->pdfService->generarBoletaPDF($pago->fresh());
                } else {
                    $pdfInfo = $this->pdfService->generarFacturaPDF($pago->fresh());
                }

                // Actualizar con la URL del PDF local
                $pago->update([
                    'enlace_pdf' => $pdfInfo['url']
                ]);

                \Log::info('✅ PDF generado:', $pdfInfo);
            } catch (\Exception $e) {
                \Log::error('❌ Error al generar PDF:', ['error' => $e->getMessage()]);
                // No falla si el PDF falla, el comprobante ya está emitido
            }

            return $this->successResponse([
                'mensaje' => 'Comprobante emitido exitosamente',
                'pago' => $pago->fresh()->load(['paciente.usuario']),
                'comprobante' => $respuestaSunat['data']
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al emitir comprobante: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener detalle de un pago
     */
    public function verPago($idPago)
    {
        try {
            $pago = Pago::with(['paciente.usuario', 'cita', 'registradoPor'])
                ->findOrFail($idPago);

            return $this->successResponse($pago);

        } catch (\Exception $e) {
            return $this->errorResponse('Pago no encontrado', 404);
        }
    }

    /**
     * Buscar pacientes para asignar pago
     */
    public function buscarPacientes(Request $request)
    {
        try {
            $busqueda = $request->input('termino', $request->input('q', ''));

            if (strlen($busqueda) < 2) {
                return $this->successResponse([]);
            }

            $pacientes = Paciente::query()
                ->where(function ($q) use ($busqueda) {
                    $q->where('nombres', 'like', "%{$busqueda}%")
                      ->orWhere('apellidos', 'like', "%{$busqueda}%")
                      ->orWhere('dni', 'like', "%{$busqueda}%");
                })
                ->limit(10)
                ->get()
                ->map(function ($p) {
                    return [
                        'id_paciente' => $p->id_paciente,
                        'nombre' => $p->nombres, // Mapear nombres -> nombre para el frontend
                        'apellidos' => $p->apellidos,
                        'dni' => $p->dni,
                        'nombre_completo' => "{$p->nombres} {$p->apellidos}",
                        'telefono' => $p->telefono_responsable,
                        'edad' => $p->fecha_nacimiento
                            ? \Carbon\Carbon::parse($p->fecha_nacimiento)->age
                            : null,
                    ];
                });

            return $this->successResponse($pacientes);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al buscar pacientes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generar PDF simple de un pago (usando plantillas sin SUNAT)
     */
    public function generarPDFPago(Request $request, $idPago)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:boleta,factura',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $pago = Pago::with('paciente')->findOrFail($idPago);

            if ($request->tipo === 'boleta') {
                $pdfInfo = $this->pdfService->generarBoletaSimplePDF($pago, $pago->paciente);
            } else {
                $pdfInfo = $this->pdfService->generarFacturaSimplePDF($pago, $pago->paciente);
            }

            return $this->successResponse([
                'mensaje' => 'PDF generado exitosamente',
                'pdf_url' => $pdfInfo['url'],
                'filename' => $pdfInfo['filename']
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al generar PDF: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Descargar/servir el PDF generado desde storage (evita dependencias en public/storage)
     * Ruta: GET /secretaria/caja/pagos/{id}/pdf/download?tipo=boleta|factura
     */
    public function descargarPDFPago(Request $request, $idPago)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:boleta,factura',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $pago = Pago::with('paciente')->findOrFail($idPago);

            if ($request->tipo === 'boleta') {
                $pdfInfo = $this->pdfService->generarBoletaSimplePDF($pago, $pago->paciente);
            } else {
                $pdfInfo = $this->pdfService->generarFacturaSimplePDF($pago, $pago->paciente);
            }

            $relativePath = $pdfInfo['path'] ?? null;
            if (!$relativePath || !Storage::disk('public')->exists($relativePath)) {
                return $this->errorResponse('Archivo PDF no encontrado en el servidor', 404);
            }

            $absolute = Storage::disk('public')->path($relativePath);

            return response()->file($absolute, [
                'Content-Type' => 'application/pdf'
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al descargar PDF: ' . $e->getMessage(), 500);
        }
    }
}
