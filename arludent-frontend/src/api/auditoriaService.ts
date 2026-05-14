import axios from './axios'

// ============ INTERFACES ============

export interface LogActividad {
  id_log: number
  id_usuario: number | null
  accion: string
  modulo_afectado: string | null
  registro_afectado: string | null
  descripcion: string | null
  ip_usuario: string | null
  fecha_hora: string
  usuario?: {
    id_usuario: number
    username: string
    correo: string
  }
}

export interface PaginacionLogs {
  total: number
  per_page: number
  current_page: number
  last_page: number
  from: number | null
  to: number | null
}

export interface FiltrosAuditoria {
  page?: number
  per_page?: number
  accion?: string
  modulo?: string
  id_usuario?: number
  fecha_inicio?: string
  fecha_fin?: string
  search?: string
}

export interface EstadisticasAuditoria {
  total_logs: number
  acciones_frecuentes: Array<{ accion: string; total: number }>
  modulos_afectados: Array<{ modulo_afectado: string; total: number }>
  usuarios_activos: Array<{
    id_usuario: number
    total: number
    usuario: {
      id_usuario: number
      username: string
      correo: string
    }
  }>
  actividad_diaria: Array<{ fecha: string; total: number }>
}

// ============ SERVICIO DE AUDITORÍA ============

/**
 * Obtener logs de auditoría con filtros y paginación
 */
export const obtenerLogs = async (
  filtros: FiltrosAuditoria = {}
): Promise<{ logs: LogActividad[]; pagination: PaginacionLogs }> => {
  const response = await axios.get('/sistema/auditoria', { params: filtros })
  return response.data.data
}

/**
 * Obtener un log específico por ID
 */
export const obtenerLog = async (id: number): Promise<LogActividad> => {
  const response = await axios.get(`/sistema/auditoria/${id}`)
  return response.data.data.log
}

/**
 * Obtener estadísticas de auditoría
 */
export const obtenerEstadisticas = async (filtros?: {
  fecha_inicio?: string
  fecha_fin?: string
}): Promise<EstadisticasAuditoria> => {
  const response = await axios.get('/sistema/auditoria-estadisticas', { params: filtros })
  return response.data.data
}

/**
 * Obtener lista de acciones únicas (para filtros)
 */
export const obtenerAcciones = async (): Promise<string[]> => {
  const response = await axios.get('/sistema/auditoria-acciones')
  return response.data.data.acciones
}

/**
 * Obtener lista de módulos únicos (para filtros)
 */
export const obtenerModulos = async (): Promise<string[]> => {
  const response = await axios.get('/sistema/auditoria-modulos')
  return response.data.data.modulos
}

export default {
  obtenerLogs,
  obtenerLog,
  obtenerEstadisticas,
  obtenerAcciones,
  obtenerModulos
}
