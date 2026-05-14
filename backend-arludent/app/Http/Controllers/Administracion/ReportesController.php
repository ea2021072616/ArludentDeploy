<?php

namespace App\Http\Controllers\Administracion;

use App\Models\Pago;
use App\Models\Paciente;
use App\Models\Cita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador de Reportes
 *
 * Genera reportes predefinidos del sistema
 */
class ReportesController extends Controller
{
    /**
     * Reporte de Ingresos
     * Muestra ingresos totales, por período, forma de pago, etc.
     */
    public function reporteIngresos(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'metodo_pago' => 'nullable|string|in:efectivo,tarjeta,transferencia,cheque,otros',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Pago::query();

            // Filtro de fechas
            if ($request->fecha_inicio) {
                $query->where('fecha_pago', '>=', $request->fecha_inicio);
            }
            if ($request->fecha_fin) {
                $query->where('fecha_pago', '<=', $request->fecha_fin);
            }
            if ($request->metodo_pago) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            // Solo pagos pagados (completados)
            $query->where('estado_pago', 'pagado');

            // Totales generales
            $totalIngresos = $query->sum('monto');
            $cantidadPagos = $query->count();
            $promedioMonto = $query->avg('monto');

            // Si no hay pagos, devolver datos vacíos/cero
            if ($cantidadPagos === 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'resumen' => [
                            'total_ingresos' => 0,
                            'cantidad_pagos' => 0,
                            'promedio_monto' => 0,
                        ],
                        'por_forma_pago' => [],
                        'por_dia' => [],
                        'top_pacientes' => [],
                    ]
                ]);
            }

            // Ingresos por método de pago
            $ingresosPorFormaPago = Pago::select('metodo_pago', DB::raw('SUM(monto) as total'), DB::raw('COUNT(*) as cantidad'))
                ->where('estado_pago', 'pagado')
                ->when($request->fecha_inicio, fn($q) => $q->where('fecha_pago', '>=', $request->fecha_inicio))
                ->when($request->fecha_fin, fn($q) => $q->where('fecha_pago', '<=', $request->fecha_fin))
                ->groupBy('metodo_pago')
                ->get();

            // Ingresos por día (últimos 30 días o rango especificado)
            $ingresosPorDia = Pago::select(
                    DB::raw('DATE(fecha_pago) as fecha'),
                    DB::raw('SUM(monto) as total'),
                    DB::raw('COUNT(*) as cantidad')
                )
                ->where('estado_pago', 'pagado')
                ->when($request->fecha_inicio, fn($q) => $q->where('fecha_pago', '>=', $request->fecha_inicio))
                ->when($request->fecha_fin, fn($q) => $q->where('fecha_pago', '<=', $request->fecha_fin))
                ->groupBy('fecha')
                ->orderBy('fecha', 'desc')
                ->limit(30)
                ->get();

            // Top 5 pacientes que más pagaron
            $topPacientes = Pago::select('id_paciente', DB::raw('SUM(monto) as total_pagado'))
                ->with('paciente:id_paciente,nombres,apellidos')
                ->where('estado_pago', 'pagado')
                ->when($request->fecha_inicio, fn($q) => $q->where('fecha_pago', '>=', $request->fecha_inicio))
                ->when($request->fecha_fin, fn($q) => $q->where('fecha_pago', '<=', $request->fecha_fin))
                ->groupBy('id_paciente')
                ->orderBy('total_pagado', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'resumen' => [
                        'total_ingresos' => round($totalIngresos, 2),
                        'cantidad_pagos' => $cantidadPagos,
                        'promedio_monto' => round($promedioMonto, 2),
                    ],
                    'por_forma_pago' => $ingresosPorFormaPago,
                    'por_dia' => $ingresosPorDia,
                    'top_pacientes' => $topPacientes,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de ingresos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de Flujo de Clientes
     * Muestra nuevos pacientes, pacientes activos, retención, etc.
     */
    public function reporteFlujoClientes(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Total de pacientes
            $totalPacientes = Paciente::count();

            // Nuevos pacientes en el período
            $nuevosPacientes = Paciente::query()
                ->when($request->fecha_inicio, fn($q) => $q->where('created_at', '>=', $request->fecha_inicio))
                ->when($request->fecha_fin, fn($q) => $q->where('created_at', '<=', $request->fecha_fin))
                ->count();

            // Pacientes con citas en el período
            $pacientesConCitas = Cita::query()
                ->when($request->fecha_inicio, fn($q) => $q->where('fecha_hora_inicio', '>=', $request->fecha_inicio))
                ->when($request->fecha_fin, fn($q) => $q->where('fecha_hora_inicio', '<=', $request->fecha_fin))
                ->distinct('id_paciente')
                ->count('id_paciente');

            // Nuevos pacientes por mes (últimos 12 meses)
            $pacientesPorMes = Paciente::select(
                    DB::raw('YEAR(created_at) as año'),
                    DB::raw('MONTH(created_at) as mes'),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('año', 'mes')
                ->orderBy('año', 'desc')
                ->orderBy('mes', 'desc')
                ->limit(12)
                ->get();

            // Distribución por género
            $distribucionGenero = Paciente::select('sexo as genero', DB::raw('COUNT(*) as total'))
                ->groupBy('sexo')
                ->get();

            // Distribución por rango de edad
            $distribucionEdad = Paciente::select(
                    DB::raw('CASE
                        WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 18 THEN "Menor de 18"
                        WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 18 AND 30 THEN "18-30"
                        WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 31 AND 50 THEN "31-50"
                        WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 51 AND 70 THEN "51-70"
                        ELSE "Mayor de 70"
                    END as rango'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereNotNull('fecha_nacimiento')
                ->groupBy('rango')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'resumen' => [
                        'total_pacientes' => $totalPacientes,
                        'nuevos_pacientes' => $nuevosPacientes,
                        'pacientes_activos' => $pacientesConCitas,
                    ],
                    'pacientes_por_mes' => $pacientesPorMes,
                    'distribucion_genero' => $distribucionGenero,
                    'distribucion_edad' => $distribucionEdad,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de flujo de clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de Citas
     * Estadísticas sobre citas: completadas, canceladas, no show, por estado
     */
    public function reporteCitas(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'id_medico' => 'nullable|integer|exists:medicos,id_medico',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Cita::query();

            if ($request->fecha_inicio) {
                $query->where('fecha_hora_inicio', '>=', $request->fecha_inicio);
            }
            if ($request->fecha_fin) {
                $query->where('fecha_hora_inicio', '<=', $request->fecha_fin);
            }
            if ($request->id_medico) {
                $query->where('id_medico', $request->id_medico);
            }

            // Totales por estado
            $totalCitas = $query->count();
            $citasPorEstado = Cita::select('estado', DB::raw('COUNT(*) as total'))
                ->when($request->fecha_inicio, fn($q) => $q->where('fecha_hora_inicio', '>=', $request->fecha_inicio))
                ->when($request->fecha_fin, fn($q) => $q->where('fecha_hora_inicio', '<=', $request->fecha_fin))
                ->when($request->id_medico, fn($q) => $q->where('id_medico', $request->id_medico))
                ->groupBy('estado')
                ->get();

            // Citas por día
            $citasPorDia = Cita::select(
                    DB::raw('DATE(fecha_hora_inicio) as fecha'),
                    DB::raw('COUNT(*) as total')
                )
                ->when($request->fecha_inicio, fn($q) => $q->where('fecha_hora_inicio', '>=', $request->fecha_inicio))
                ->when($request->fecha_fin, fn($q) => $q->where('fecha_hora_inicio', '<=', $request->fecha_fin))
                ->when($request->id_medico, fn($q) => $q->where('id_medico', $request->id_medico))
                ->groupBy('fecha')
                ->orderBy('fecha', 'desc')
                ->limit(30)
                ->get();

            // Citas por médico
            $citasPorMedico = Cita::select('id_medico', DB::raw('COUNT(*) as total'))
                ->with('medico:id_medico,nombres,apellidos,especialidad')
                ->when($request->fecha_inicio, fn($q) => $q->where('fecha_hora_inicio', '>=', $request->fecha_inicio))
                ->when($request->fecha_fin, fn($q) => $q->where('fecha_hora_inicio', '<=', $request->fecha_fin))
                ->groupBy('id_medico')
                ->orderBy('total', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'resumen' => [
                        'total_citas' => $totalCitas,
                    ],
                    'por_estado' => $citasPorEstado,
                    'por_dia' => $citasPorDia,
                    'por_medico' => $citasPorMedico,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de citas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
