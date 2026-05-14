<?php

namespace App\Http\Controllers\Facturacion;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Pago;
use App\Models\Paciente;
use App\Http\Controllers\Controller;
use App\Services\PdfComprobanteService;
use Carbon\Carbon;

/**
 * Controlador para pagos del paciente
 */
class PagoPacienteController extends Controller
{
    protected $pdfService;

    public function __construct(PdfComprobanteService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Listar pagos del paciente autenticado (paginado)
     */
    public function misPagos(Request $request)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();

        $paciente = Paciente::where('id_usuario', $usuario->id_usuario)->first();

        if (!$paciente) {
            return response()->json([
                'success' => true,
                'data' => ['pagos' => [], 'total' => 0],
                'message' => 'No tienes un registro de paciente aún.'
            ]);
        }

        $perPage = (int) $request->query('per_page', 10);

        $query = Pago::where('id_paciente', $paciente->id_paciente)
            ->orderBy('fecha_pago', 'desc')
            ->orderBy('created_at', 'desc');

        $pagos = $query->paginate($perPage);

        // Transformar a array para mantener compatibilidad con frontend
        $pagosArray = $pagos->map(function ($pago) {
            return [
                'id_pago' => $pago->id_pago,
                'id_cita' => $pago->id_cita,
                'concepto' => $pago->concepto,
                'monto' => (float) $pago->monto,
                'metodo_pago' => $pago->metodo_pago,
                'estado_pago' => $pago->estado_pago,
                'fecha_pago' => $pago->fecha_pago,
                'notas' => $pago->notas,
                'registrado_por' => $pago->registrado_por,
                'tipo_comprobante' => $pago->tipo_comprobante,
                'created_at' => $pago->created_at,
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'pagos' => $pagosArray,
                'total' => $pagos->total(),
                'per_page' => $pagos->perPage(),
                'current_page' => $pagos->currentPage(),
            ],
            'message' => 'Pagos obtenidos exitosamente.'
        ]);
    }

    // ============================================================
    // MÉTODOS ESPECÍFICOS PARA SECRETARIA - GESTIÓN DE CAJA
    // ============================================================

    /**
     * @OA\Get(
     *     path="/api/secretaria/caja/resumen-diario",
     *     summary="Resumen diario de caja",
     *     description="Obtiene el resumen de ingresos y egresos del día",
     *     tags={"Caja Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="fecha",
     *         in="query",
     *         description="Fecha específica (Y-m-d). Si no se proporciona, usa fecha actual",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     )
     * )
     */
    public function resumenDiario(Request $request)
    {
        try {
            $fecha = $request->fecha ?? now()->format('Y-m-d');

            // Ingresos del día
            $ingresosPagados = Pago::where('estado_pago', 'pagado')
                ->whereDate('fecha_pago', $fecha)
                ->get();

            // Pagos registrados pero pendientes
            $pagosPendientes = Pago::where('estado_pago', 'pendiente')
                ->whereDate('created_at', $fecha)
                ->get();

            // Resumen por método de pago
            $resumenMetodos = $ingresosPagados->groupBy('metodo_pago')->map(function ($pagos, $metodo) {
                return [
                    'metodo' => $metodo,
                    'cantidad_transacciones' => $pagos->count(),
                    'monto_total' => $pagos->sum('monto')
                ];
            })->values();

            // Ingresos por hora (para gráfico)
            $ingresosPorHora = $ingresosPagados->groupBy(function ($pago) {
                return \Carbon\Carbon::parse($pago->fecha_pago)->format('H:00');
            })->map(function ($pagos, $hora) {
                return [
                    'hora' => $hora,
                    'monto' => $pagos->sum('monto'),
                    'transacciones' => $pagos->count()
                ];
            })->values();

            // Top pacientes del día
            $topPacientes = $ingresosPagados->groupBy('id_paciente')->map(function ($pagos) {
                $paciente = $pagos->first()->paciente;
                return [
                    'paciente_id' => $paciente->id_paciente,
                    'nombre_completo' => $paciente->nombres . ' ' . $paciente->apellidos,
                    'total_pagado' => $pagos->sum('monto'),
                    'cantidad_pagos' => $pagos->count()
                ];
            })->sortByDesc('total_pagado')->take(5)->values();

            $resumen = [
                'fecha' => $fecha,
                'ingresos_totales' => $ingresosPagados->sum('monto'),
                'cantidad_transacciones_pagadas' => $ingresosPagados->count(),
                'pagos_pendientes_monto' => $pagosPendientes->sum('monto'),
                'cantidad_pagos_pendientes' => $pagosPendientes->count(),
                'promedio_por_transaccion' => $ingresosPagados->count() > 0 ?
                    $ingresosPagados->sum('monto') / $ingresosPagados->count() : 0,
                'resumen_por_metodos' => $resumenMetodos,
                'ingresos_por_hora' => $ingresosPorHora,
                'top_pacientes' => $topPacientes,
                'estadisticas_adicionales' => [
                    'monto_efectivo' => $ingresosPagados->where('metodo_pago', 'efectivo')->sum('monto'),
                    'monto_tarjeta' => $ingresosPagados->where('metodo_pago', 'tarjeta')->sum('monto'),
                    'monto_transferencia' => $ingresosPagados->where('metodo_pago', 'transferencia')->sum('monto'),
                    'transacciones_mañana' => $ingresosPagados->filter(function ($pago) {
                        $hora = \Carbon\Carbon::parse($pago->fecha_pago)->hour;
                        return $hora >= 6 && $hora < 12;
                    })->count(),
                    'transacciones_tarde' => $ingresosPagados->filter(function ($pago) {
                        $hora = \Carbon\Carbon::parse($pago->fecha_pago)->hour;
                        return $hora >= 12 && $hora < 18;
                    })->count(),
                    'transacciones_noche' => $ingresosPagados->filter(function ($pago) {
                        $hora = \Carbon\Carbon::parse($pago->fecha_pago)->hour;
                        return $hora >= 18 || $hora < 6;
                    })->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $resumen,
                'message' => 'Resumen diario obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen diario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/caja/resumen-semanal",
     *     summary="Resumen semanal de caja",
     *     description="Obtiene el resumen de ingresos y comparativas de la semana",
     *     tags={"Caja Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         description="Fecha de inicio de la semana (Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     )
     * )
     */
    public function resumenSemanal(Request $request)
    {
        try {
            $fechaInicio = $request->fecha_inicio ?
                \Carbon\Carbon::parse($request->fecha_inicio)->startOfWeek() :
                now()->startOfWeek();
            $fechaFin = $fechaInicio->copy()->endOfWeek();

            // Ingresos de la semana
            $ingresosSemana = Pago::where('estado_pago', 'pagado')
                ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                ->with('paciente')
                ->get();

            // Ingresos por día de la semana
            $ingresosPorDia = collect();
            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay()) {
                $ingresosDia = $ingresosSemana->filter(function ($pago) use ($fecha) {
                    return \Carbon\Carbon::parse($pago->fecha_pago)->isSameDay($fecha);
                });

                $ingresosPorDia->push([
                    'fecha' => $fecha->format('Y-m-d'),
                    'dia_semana' => $fecha->locale('es')->dayName,
                    'monto_total' => $ingresosDia->sum('monto'),
                    'cantidad_transacciones' => $ingresosDia->count(),
                    'promedio_transaccion' => $ingresosDia->count() > 0 ?
                        $ingresosDia->sum('monto') / $ingresosDia->count() : 0
                ]);
            }

            // Comparación con semana anterior
            $fechaInicioAnterior = $fechaInicio->copy()->subWeek();
            $fechaFinAnterior = $fechaFin->copy()->subWeek();

            $ingresosSemanaAnterior = Pago::where('estado_pago', 'pagado')
                ->whereBetween('fecha_pago', [$fechaInicioAnterior, $fechaFinAnterior])
                ->sum('monto');

            $totalSemanaActual = $ingresosSemana->sum('monto');
            $variacionSemanal = $ingresosSemanaAnterior > 0 ?
                (($totalSemanaActual - $ingresosSemanaAnterior) / $ingresosSemanaAnterior) * 100 : 0;

            // Análisis de rendimiento
            $analisisRendimiento = [
                'dia_mayor_ingreso' => $ingresosPorDia->sortByDesc('monto_total')->first(),
                'dia_menor_ingreso' => $ingresosPorDia->sortBy('monto_total')->first(),
                'promedio_diario' => $totalSemanaActual / 7,
                'dias_productivos' => $ingresosPorDia->where('monto_total', '>', 0)->count(),
                'meta_semanal_estimada' => $totalSemanaActual * 1.1, // 10% más como meta
                'cumplimiento_meta' => ($totalSemanaActual / ($totalSemanaActual * 1.1)) * 100
            ];

            $resumen = [
                'periodo' => [
                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                    'fecha_fin' => $fechaFin->format('Y-m-d')
                ],
                'ingresos_totales' => $totalSemanaActual,
                'cantidad_transacciones' => $ingresosSemana->count(),
                'promedio_diario' => $totalSemanaActual / 7,
                'variacion_semanal' => [
                    'porcentaje' => round($variacionSemanal, 2),
                    'monto_diferencia' => $totalSemanaActual - $ingresosSemanaAnterior,
                    'semana_anterior' => $ingresosSemanaAnterior,
                    'semana_actual' => $totalSemanaActual
                ],
                'ingresos_por_dia' => $ingresosPorDia,
                'analisis_rendimiento' => $analisisRendimiento
            ];

            return response()->json([
                'success' => true,
                'data' => $resumen,
                'message' => 'Resumen semanal obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen semanal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/caja/pacientes-deuda",
     *     summary="Pacientes con deuda pendiente",
     *     description="Lista todos los pacientes que tienen pagos pendientes",
     *     tags={"Caja Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="monto_minimo",
     *         in="query",
     *         description="Filtrar por monto mínimo de deuda",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="dias_vencido",
     *         in="query",
     *         description="Filtrar por días de vencimiento",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function pacientesConDeuda(Request $request)
    {
        try {
            $query = Pago::where('estado_pago', 'pendiente')
                ->with(['paciente']);

            // Filtro por monto mínimo
            if ($request->filled('monto_minimo')) {
                $query->where('monto', '>=', $request->monto_minimo);
            }

            // Filtro por días de vencimiento
            if ($request->filled('dias_vencido')) {
                $fechaLimite = now()->subDays($request->dias_vencido);
                $query->where('created_at', '<=', $fechaLimite);
            }

            $pagosDeuda = $query->get();

            // Agrupar por paciente
            $pacientesConDeuda = $pagosDeuda->groupBy('id_paciente')->map(function ($pagos) {
                $paciente = $pagos->first()->paciente;
                $deudaTotal = $pagos->sum('monto');
                $pagoMasAntiguo = $pagos->sortBy('created_at')->first();
                $diasVencido = \Carbon\Carbon::parse($pagoMasAntiguo->created_at)->diffInDays(now());

                return [
                    'paciente_id' => $paciente->id_paciente,
                    'nombre_completo' => $paciente->nombres . ' ' . $paciente->apellidos,
                    'numero_documento' => $paciente->numero_documento,
                    'telefono' => $paciente->telefono,
                    'email' => $paciente->email,
                    'deuda_total' => $deudaTotal,
                    'cantidad_pagos_pendientes' => $pagos->count(),
                    'dias_vencido' => $diasVencido,
                    'pago_mas_antiguo' => $pagoMasAntiguo->created_at,
                    'prioridad' => $this->calcularPrioridadCobranza($deudaTotal, $diasVencido),
                    'pagos_detalle' => $pagos->map(function ($pago) {
                        return [
                            'id_pago' => $pago->id_pago,
                            'concepto' => $pago->concepto,
                            'monto' => $pago->monto,
                            'fecha_creacion' => $pago->created_at,
                            'dias_pendiente' => \Carbon\Carbon::parse($pago->created_at)->diffInDays(now())
                        ];
                    })
                ];
            })->sortByDesc('deuda_total')->values();

            // Estadísticas de deuda
            $estadisticasDeuda = [
                'total_pacientes_con_deuda' => $pacientesConDeuda->count(),
                'deuda_total_sistema' => $pacientesConDeuda->sum('deuda_total'),
                'deuda_promedio_por_paciente' => $pacientesConDeuda->count() > 0 ?
                    $pacientesConDeuda->sum('deuda_total') / $pacientesConDeuda->count() : 0,
                'pacientes_prioridad_alta' => $pacientesConDeuda->where('prioridad', 'alta')->count(),
                'pacientes_prioridad_media' => $pacientesConDeuda->where('prioridad', 'media')->count(),
                'pacientes_prioridad_baja' => $pacientesConDeuda->where('prioridad', 'baja')->count(),
                'deuda_mas_antigua' => $pacientesConDeuda->max('dias_vencido'),
                'mayor_deudor' => $pacientesConDeuda->sortByDesc('deuda_total')->first()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'pacientes_con_deuda' => $pacientesConDeuda,
                    'estadisticas' => $estadisticasDeuda
                ],
                'message' => 'Pacientes con deuda obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pacientes con deuda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/secretaria/caja/registrar-pago",
     *     summary="Registrar nuevo pago",
     *     description="Registra un pago desde la secretaria",
     *     tags={"Caja Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="paciente_id", type="integer", example=1),
     *             @OA\Property(property="cita_id", type="integer", example=1),
     *             @OA\Property(property="concepto", type="string", example="Consulta general"),
     *             @OA\Property(property="monto", type="number", format="float", example=150.00),
     *             @OA\Property(property="metodo_pago", type="string", enum={"efectivo", "tarjeta", "transferencia"}, example="efectivo"),
     *             @OA\Property(property="estado_pago", type="string", enum={"pagado", "pendiente"}, example="pagado"),
     *             @OA\Property(property="numero_comprobante", type="string", example="BOL-001234"),
     *             @OA\Property(property="notas", type="string", example="Pago completo del tratamiento")
     *         )
     *     )
     * )
     */
    public function registrarPago(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'paciente_id' => 'required|exists:pacientes,id_paciente',
                'cita_id' => 'nullable|exists:citas,id_cita',
                'concepto' => 'required|string|max:255',
                'monto' => 'required|numeric|min:0.01',
                'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
                'estado_pago' => 'required|in:pagado,pendiente',
                'numero_comprobante' => 'nullable|string|max:50',
                'notas' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pago = Pago::create([
                'id_paciente' => $request->paciente_id,
                'id_cita' => $request->cita_id,
                'concepto' => $request->concepto,
                'monto' => $request->monto,
                'metodo_pago' => $request->metodo_pago,
                'estado_pago' => $request->estado_pago,
                'numero_comprobante' => $request->numero_comprobante,
                'notas' => $request->notas,
                'fecha_pago' => $request->estado_pago === 'pagado' ? now() : null,
                'registrado_por' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'pago' => [
                        'id_pago' => $pago->id_pago,
                        'concepto' => $pago->concepto,
                        'monto' => $pago->monto,
                        'metodo_pago' => $pago->metodo_pago,
                        'estado_pago' => $pago->estado_pago,
                        'fecha_pago' => $pago->fecha_pago,
                        'numero_comprobante' => $pago->numero_comprobante
                    ]
                ],
                'message' => 'Pago registrado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/secretaria/caja/movimientos",
     *     summary="Control de movimientos de caja",
     *     description="Obtiene todos los movimientos de caja con filtros",
     *     tags={"Caja Secretaria"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="fecha_desde",
     *         in="query",
     *         description="Fecha desde",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_hasta",
     *         in="query",
     *         description="Fecha hasta",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="metodo_pago",
     *         in="query",
     *         description="Filtrar por método de pago",
     *         required=false,
     *         @OA\Schema(type="string", enum={"efectivo", "tarjeta", "transferencia"})
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pagado", "pendiente"})
     *     )
     * )
     */
    public function controlMovimientos(Request $request)
    {
        try {
            $query = Pago::with(['paciente']);

            // Aplicar filtros
            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            if ($request->filled('metodo_pago')) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            if ($request->filled('estado')) {
                $query->where('estado_pago', $request->estado);
            }

            // Buscar por paciente
            if ($request->filled('buscar_paciente')) {
                $query->whereHas('paciente', function ($q) use ($request) {
                    $q->where('nombres', 'like', '%' . $request->buscar_paciente . '%')
                      ->orWhere('apellidos', 'like', '%' . $request->buscar_paciente . '%')
                      ->orWhere('numero_documento', 'like', '%' . $request->buscar_paciente . '%');
                });
            }

            $movimientos = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            // Formatear movimientos
            $movimientosFormateados = $movimientos->getCollection()->map(function ($pago) {
                return [
                    'id_pago' => $pago->id_pago,
                    'fecha_registro' => $pago->created_at,
                    'fecha_pago' => $pago->fecha_pago,
                    'paciente' => [
                        'id' => $pago->paciente->id_paciente,
                        'nombre_completo' => $pago->paciente->nombres . ' ' . $pago->paciente->apellidos,
                        'documento' => $pago->paciente->numero_documento
                    ],
                    'concepto' => $pago->concepto,
                    'monto' => $pago->monto,
                    'metodo_pago' => $pago->metodo_pago,
                    'estado_pago' => $pago->estado_pago,
                    'numero_comprobante' => $pago->numero_comprobante,
                    'notas' => $pago->notas,
                    'dias_pendiente' => $pago->estado_pago === 'pendiente' ?
                        \Carbon\Carbon::parse($pago->created_at)->diffInDays(now()) : null
                ];
            });

            // Resumen de movimientos
            $todosLosMovimientos = $query->get();
            $resumenMovimientos = [
                'total_movimientos' => $todosLosMovimientos->count(),
                'ingresos_confirmados' => $todosLosMovimientos->where('estado_pago', 'pagado')->sum('monto'),
                'pendientes_cobro' => $todosLosMovimientos->where('estado_pago', 'pendiente')->sum('monto'),
                'por_metodo_pago' => $todosLosMovimientos->groupBy('metodo_pago')->map(function ($pagos, $metodo) {
                    return [
                        'metodo' => $metodo,
                        'cantidad' => $pagos->count(),
                        'monto_total' => $pagos->where('estado_pago', 'pagado')->sum('monto'),
                        'pendiente' => $pagos->where('estado_pago', 'pendiente')->sum('monto')
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'movimientos' => $movimientosFormateados,
                    'paginacion' => [
                        'total' => $movimientos->total(),
                        'por_pagina' => $movimientos->perPage(),
                        'pagina_actual' => $movimientos->currentPage(),
                        'total_paginas' => $movimientos->lastPage()
                    ],
                    'resumen' => $resumenMovimientos
                ],
                'message' => 'Movimientos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================================
    // MÉTODOS AUXILIARES PARA CAJA
    // ============================================================

    /**
     * Calcular prioridad de cobranza
     */
    private function calcularPrioridadCobranza($monto, $diasVencido)
    {
        if ($monto >= 500 || $diasVencido >= 30) {
            return 'alta';
        } elseif ($monto >= 200 || $diasVencido >= 15) {
            return 'media';
        } else {
            return 'baja';
        }
    }

    /**
     * Descargar PDF de un pago del paciente autenticado
     * Endpoint: GET /clinica/mis-pagos/{id}/pdf?tipo=boleta|factura
     */
    public function descargarPDFPago(Request $request, $idPago)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:boleta,factura',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            /** @var \App\Models\User $usuario */
            $usuario = Auth::user();
            $paciente = Paciente::where('id_usuario', $usuario->id_usuario)->first();

            if (!$paciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un registro de paciente.'
                ], 403);
            }

            // Verificar que el pago pertenece al paciente autenticado
            $pago = Pago::where('id_pago', $idPago)
                ->where('id_paciente', $paciente->id_paciente)
                ->with('paciente')
                ->first();

            if (!$pago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado o no tienes permiso para acceder a él.'
                ], 404);
            }

            // Generar PDF según el tipo solicitado
            if ($request->tipo === 'boleta') {
                $pdfInfo = $this->pdfService->generarBoletaSimplePDF($pago, $pago->paciente);
            } else {
                $pdfInfo = $this->pdfService->generarFacturaSimplePDF($pago, $pago->paciente);
            }

            $relativePath = $pdfInfo['path'] ?? null;
            if (!$relativePath || !Storage::disk('public')->exists($relativePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo PDF no encontrado en el servidor'
                ], 404);
            }

            $absolute = Storage::disk('public')->path($relativePath);

            return response()->file($absolute, [
                'Content-Type' => 'application/pdf'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
