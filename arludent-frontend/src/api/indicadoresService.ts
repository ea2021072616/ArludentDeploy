import axios from './axios'

// ============ INTERFACES - INDICADORES ============

export interface TratamientoSolicitado {
  id_tratamiento: number
  cantidad: number
  ingresos_totales: number
  tratamiento: {
    id_tratamiento: number
    nombre: string
    categoria: string
    precio_actual: number
  }
}

export interface CategoriaSolicitada {
  categoria: string
  cantidad: number
}

export interface TratamientosSolicitados {
  tratamientos: TratamientoSolicitado[]
  categorias: CategoriaSolicitada[]
}

export interface CitasPorMedicoData {
  id_medico: number
  total_citas: number
  completadas: number
  canceladas: number
  no_asistio: number
  tasa_completitud: number
  medico: {
    id_medico: number
    nombres: string
    apellidos: string
    especialidad: string
  }
}

export interface CitasPorEspecialidad {
  especialidad: string
  total_citas: number
}

export interface CitasPorMedicoResponse {
  por_medico: CitasPorMedicoData[]
  por_especialidad: CitasPorEspecialidad[]
}

export interface TendenciaIngresos {
  periodo: string
  total: number
  cantidad: number
  promedio: number
}

export interface TendenciasIngresosResponse {
  tendencias: TendenciaIngresos[]
  crecimiento_porcentual: number
  periodo_actual: number
  periodo_anterior: number
}

export interface KPIs {
  total_ingresos: number
  total_citas: number
  nuevos_pacientes: number
  tratamientos_realizados: number
  tasa_asistencia: number
  ticket_promedio: number
}

export interface DashboardKPIsResponse {
  periodo: {
    fecha_inicio: string
    fecha_fin: string
  }
  kpis: KPIs
}

export interface DistribucionPuntuacion {
  puntuacion: number
  cantidad: number
  porcentaje: number
}

export interface UltimaCalificacion {
  id_calificacion: number
  puntuacion: number
  comentario: string
  fecha: string
  nombre_paciente: string
  nombre_medico: string
}

export interface SatisfaccionPacientes {
  total_calificaciones: number
  promedio_general: number
  nivel_satisfaccion: string
  distribucion: DistribucionPuntuacion[]
  ultimas_calificaciones: UltimaCalificacion[]
}

export interface FiltrosIndicadores {
  fecha_inicio?: string
  fecha_fin?: string
  limit?: number
  periodo?: 'diario' | 'semanal' | 'mensual'
}

// ============ SERVICIO DE INDICADORES ============

/**
 * Obtener tratamientos más solicitados
 */
export const obtenerTratamientosSolicitados = async (
  filtros?: FiltrosIndicadores
): Promise<TratamientosSolicitados> => {
  const response = await axios.get('/sistema/indicadores/tratamientos-solicitados', { params: filtros })
  return response.data.data
}

/**
 * Obtener citas por médico
 */
export const obtenerCitasPorMedico = async (
  filtros?: FiltrosIndicadores
): Promise<CitasPorMedicoResponse> => {
  const response = await axios.get('/sistema/indicadores/citas-por-medico', { params: filtros })
  return response.data.data
}

/**
 * Obtener tendencias de ingresos
 */
export const obtenerTendenciasIngresos = async (
  filtros?: FiltrosIndicadores
): Promise<TendenciasIngresosResponse> => {
  const response = await axios.get('/sistema/indicadores/tendencias-ingresos', { params: filtros })
  return response.data.data
}

/**
 * Obtener KPIs del dashboard
 */
export const obtenerDashboardKPIs = async (
  filtros?: FiltrosIndicadores
): Promise<DashboardKPIsResponse> => {
  const response = await axios.get('/sistema/indicadores/dashboard-kpis', { params: filtros })
  return response.data.data
}

/**
 * Obtener satisfacción de pacientes
 */
export const obtenerSatisfaccionPacientes = async (
  filtros?: FiltrosIndicadores
): Promise<SatisfaccionPacientes> => {
  const response = await axios.get('/sistema/indicadores/satisfaccion-pacientes', { params: filtros })
  return response.data.data
}

export default {
  obtenerTratamientosSolicitados,
  obtenerCitasPorMedico,
  obtenerTendenciasIngresos,
  obtenerDashboardKPIs,
  obtenerSatisfaccionPacientes
}
