import axios from './axios'

// ============ INTERFACES - REPORTES ============

export interface ResumenIngresos {
  total_ingresos: number
  cantidad_pagos: number
  promedio_monto: number
}

export interface IngresosPorFormaPago {
  forma_pago: string
  total: number
  cantidad: number
}

export interface IngresosPorDia {
  fecha: string
  total: number
  cantidad: number
}

export interface TopPaciente {
  id_paciente: number
  total_pagado: number
  paciente: {
    id_paciente: number
    nombres: string
    apellidos: string
  }
}

export interface ReporteIngresos {
  resumen: ResumenIngresos
  por_forma_pago: IngresosPorFormaPago[]
  por_dia: IngresosPorDia[]
  top_pacientes: TopPaciente[]
}

export interface ResumenFlujoClientes {
  total_pacientes: number
  nuevos_pacientes: number
  pacientes_activos: number
}

export interface PacientesPorMes {
  año: number
  mes: number
  total: number
}

export interface DistribucionGenero {
  genero: string
  total: number
}

export interface DistribucionEdad {
  rango: string
  total: number
}

export interface ReporteFlujoClientes {
  resumen: ResumenFlujoClientes
  pacientes_por_mes: PacientesPorMes[]
  distribucion_genero: DistribucionGenero[]
  distribucion_edad: DistribucionEdad[]
}

export interface CitasPorEstado {
  estado: string
  total: number
}

export interface CitasPorDia {
  fecha: string
  total: number
}

export interface CitasPorMedico {
  id_medico: number
  total: number
  medico: {
    id_medico: number
    nombres: string
    apellidos: string
    especialidad: string
  }
}

export interface ReporteCitas {
  resumen: {
    total_citas: number
  }
  por_estado: CitasPorEstado[]
  por_dia: CitasPorDia[]
  por_medico: CitasPorMedico[]
}

export interface FiltrosReporte {
  fecha_inicio?: string
  fecha_fin?: string
  forma_pago?: string
  id_medico?: number
}

// ============ SERVICIO DE REPORTES ============

/**
 * Obtener reporte de ingresos
 */
export const obtenerReporteIngresos = async (filtros?: FiltrosReporte): Promise<ReporteIngresos> => {
  const response = await axios.get('/sistema/reportes/ingresos', { params: filtros })
  return response.data.data
}

/**
 * Obtener reporte de flujo de clientes
 */
export const obtenerReporteFlujoClientes = async (
  filtros?: FiltrosReporte
): Promise<ReporteFlujoClientes> => {
  const response = await axios.get('/sistema/reportes/flujo-clientes', { params: filtros })
  return response.data.data
}

/**
 * Obtener reporte de citas
 */
export const obtenerReporteCitas = async (filtros?: FiltrosReporte): Promise<ReporteCitas> => {
  const response = await axios.get('/sistema/reportes/citas', { params: filtros })
  return response.data.data
}

export default {
  obtenerReporteIngresos,
  obtenerReporteFlujoClientes,
  obtenerReporteCitas
}
