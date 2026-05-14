import apiClient from './axios'

/**
 * Interfaces para Check-in y Cola de Atención
 */

export interface PacienteEnCola {
  id_cita: number
  paciente: {
    id_paciente: number
    nombres: string
    apellidos: string
    numero_documento: string
    telefono?: string
  }
  medico: {
    id_medico: number
    nombres: string
    apellidos: string
    especialidad?: string
  }
  fecha_hora_inicio: string
  fecha_hora_fin?: string
  motivo?: string
  estado_cita: 'pendiente' | 'confirmado' | 'en_espera' | 'siendo_atendido' | 'completado' | 'cancelado' | 'no_asistio'
  hora_llegada?: string
  tiempo_espera?: string
  prioridad: 'normal' | 'urgente'
  notas_secretaria?: string
}

export interface EstadisticasCheckin {
  total_pacientes_hoy: number
  en_sala_espera: number
  siendo_atendidos: number
  completados_hoy: number
  tiempo_promedio_espera: string
  tiempo_promedio_atencion: string
  ocupacion_actual: {
    porcentaje: number
    capacidad_maxima: number
    pacientes_actuales: number
  }
}

export interface RegistrarLlegadaData {
  id_cita: number
  notas?: string
  prioridad?: 'normal' | 'urgente'
}

export interface CambiarEstadoData {
  id_cita: number
  nuevo_estado: 'en_espera' | 'siendo_atendido' | 'completado' | 'cancelado'
  notas?: string
}

/**
 * Servicio para Check-in y Cola de Atención
 */
export const checkinService = {
  /**
   * Obtener cola de atención actual
   */
  async getColaAtencion(filtros?: {
    estado?: string
    fecha?: string
    medico_id?: number
  }): Promise<PacienteEnCola[]> {
    const response = await apiClient.get('/secretaria/checkin/cola-atencion', {
      params: filtros
    })
    return response.data.data
  },

  /**
   * Registrar llegada de paciente
   */
  async registrarLlegada(data: RegistrarLlegadaData): Promise<any> {
    const response = await apiClient.post('/secretaria/checkin/registrar-llegada', data)
    return response.data
  },

  /**
   * Cambiar estado de paciente en cola
   */
  async cambiarEstado(data: CambiarEstadoData): Promise<any> {
    const response = await apiClient.put('/secretaria/checkin/cambiar-estado', data)
    return response.data
  },

  /**
   * Obtener estadísticas de check-in
   */
  async getEstadisticasCheckin(fecha?: string): Promise<EstadisticasCheckin> {
    const response = await apiClient.get('/secretaria/checkin/estadisticas', {
      params: { fecha }
    })
    return response.data.data
  },

  /**
   * Obtener historial de check-in
   */
  async getHistorialCheckin(filtros?: {
    fecha_desde?: string
    fecha_hasta?: string
    paciente_id?: number
    medico_id?: number
    estado?: string
    page?: number
    per_page?: number
  }): Promise<any> {
    const response = await apiClient.get('/secretaria/checkin/historial', {
      params: filtros
    })
    return response.data
  },

  /**
   * Buscar citas para check-in
   */
  async buscarCitasParaCheckin(busqueda: string): Promise<PacienteEnCola[]> {
    const response = await apiClient.get('/secretaria/checkin/buscar-citas', {
      params: { busqueda }
    })
    return response.data.data
  },

  /**
   * Obtener próximas citas del día
   */
  async getProximasCitas(fecha?: string): Promise<PacienteEnCola[]> {
    const response = await apiClient.get('/secretaria/checkin/proximas-citas', {
      params: { fecha }
    })
    return response.data.data
  }
}

export default checkinService
