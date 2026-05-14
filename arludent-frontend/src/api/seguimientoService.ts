import apiClient from './axios'

/**
 * Interfaces para Seguimiento Post-Tratamiento
 */

export interface SeguimientoTratamiento {
  id_seguimiento: number
  paciente: {
    id_paciente: number
    nombres: string
    apellidos: string
    numero_documento: string
    telefono?: string
    email?: string
  }
  historial_clinico?: {
    id_historial: number
    diagnostico?: string
    tratamiento_realizado?: string
  }
  cita?: {
    id_cita: number
    fecha_hora_inicio: string
    motivo?: string
    medico: {
      nombres: string
      apellidos: string
      especialidad?: string
    }
  }
  fecha_seguimiento: string
  tipo_seguimiento: 'control' | 'revision' | 'medicacion' | 'otro'
  estado: 'pendiente' | 'contactado' | 'completado' | 'cancelado'
  notas?: string
  resultado?: string
  proxima_fecha?: string
  created_at: string
  updated_at: string
}

export interface CrearSeguimientoData {
  paciente_id: number
  historial_clinico_id?: number
  cita_id?: number
  fecha_seguimiento: string
  tipo_seguimiento: 'control' | 'revision' | 'medicacion' | 'otro'
  notas?: string
  proxima_fecha?: string
}

export interface ActualizarSeguimientoData {
  estado?: 'pendiente' | 'contactado' | 'completado' | 'cancelado'
  notas?: string
  resultado?: string
  proxima_fecha?: string
}

export interface FiltrosSeguimiento {
  estado?: string
  tipo_seguimiento?: string
  fecha_desde?: string
  fecha_hasta?: string
  paciente_id?: number
  vencidos?: boolean
  page?: number
  per_page?: number
}

export interface EstadisticasSeguimiento {
  total_seguimientos: number
  pendientes: number
  completados: number
  vencidos: number
  con_problemas: number
  urgentes: number
  por_tipo: {
    [key: string]: number
  }
}

/**
 * Servicio para Seguimiento Post-Tratamiento
 */
export const seguimientoService = {
  /**
   * Obtener lista de seguimientos
   */
  async getSeguimientos(filtros?: FiltrosSeguimiento): Promise<any> {
    const response = await apiClient.get('/secretaria/seguimiento', {
      params: filtros
    })
    return response.data
  },

  /**
   * Crear nuevo seguimiento
   */
  async crearSeguimiento(data: any): Promise<any> {
    const response = await apiClient.post('/secretaria/seguimiento', data)
    return response.data
  },

  /**
   * Actualizar seguimiento
   */
  async actualizarSeguimiento(id: number, data: any): Promise<any> {
    const response = await apiClient.put(`/secretaria/seguimiento/${id}`, data)
    return response.data
  },

  /**
   * Eliminar seguimiento
   */
  async eliminarSeguimiento(id: number): Promise<any> {
    const response = await apiClient.delete(`/secretaria/seguimiento/${id}`)
    return response.data
  },

  /**
   * Registrar contacto manual con el paciente
   */
  async registrarContacto(id: number, data: any): Promise<any> {
    const response = await apiClient.post(`/secretaria/seguimiento/${id}/registrar-contacto`, data)
    return response.data
  },

  /**
   * Obtener seguimientos vencidos
   */
  async getSeguimientosVencidos(dias_atraso?: number): Promise<SeguimientoTratamiento[]> {
    const response = await apiClient.get('/secretaria/seguimiento/vencidos', {
      params: { dias_atraso }
    })
    return response.data.data
  },

  /**
   * Obtener estadísticas de seguimiento
   */
  async getEstadisticasSeguimiento(filtros?: {
    fecha_desde?: string
    fecha_hasta?: string
  }): Promise<EstadisticasSeguimiento> {
    const response = await apiClient.get('/secretaria/seguimiento/estadisticas', {
      params: filtros
    })
    return response.data.data
  },

  /**
   * Marcar seguimiento como contactado
   */
  async marcarContactado(id: number, notas?: string): Promise<any> {
    const response = await apiClient.post(`/secretaria/seguimiento/${id}/contactado`, {
      notas
    })
    return response.data
  },

  /**
   * Completar seguimiento
   */
  async completarSeguimiento(id: number, data: {
    resultado: string
    proxima_fecha?: string
    notas?: string
  }): Promise<any> {
    const response = await apiClient.post(`/secretaria/seguimiento/${id}/completar`, data)
    return response.data
  },

  /**
   * Buscar pacientes para seguimiento
   */
  async buscarPacientesParaSeguimiento(busqueda: string): Promise<any[]> {
    const response = await apiClient.get('/secretaria/seguimiento/buscar-pacientes', {
      params: { busqueda }
    })
    return response.data.data
  },

  /**
   * Obtener todos los pacientes activos
   */
  async obtenerPacientesActivos(): Promise<any[]> {
    const response = await apiClient.get('/secretaria/pacientes')
    return response.data.data || response.data
  }
}

export default seguimientoService
