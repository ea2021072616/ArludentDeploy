import apiClient from './axios'

/**
 * Interfaces para Citas del Paciente
 */

export interface Medico {
  id_medico: number
  nombres: string
  apellidos: string
  especialidad: string | null
  foto_url: string | null
  calificacion_promedio?: number
  total_calificaciones?: number
  estadisticas?: {
    total_citas: number
    citas_completadas: number
    puntuacion_promedio: number
    total_resenas: number
  }
  ultimas_resenas?: Array<{
    id: number
    puntuacion: number
    comentario: string | null
    fecha: string
  }>
}

export interface Calificacion {
  id_calificacion: number
  puntuacion: number
  comentario: string | null
  fecha: string
}

export interface Cita {
  id_cita: number
  fecha_hora_inicio: string
  fecha_hora_fin: string | null
  motivo: string | null
  estado: 'pendiente' | 'confirmado' | 'en_espera' | 'siendo_atendido' | 'completado' | 'cancelado' | 'no_asistio'
  notas: string | null
  medico: Medico
  puede_confirmar?: boolean
  puede_reprogramar?: boolean
  puede_cancelar?: boolean
  puede_calificar?: boolean
  puede_agregar_notas?: boolean
  puede_completar?: boolean
  calificacion: Calificacion | null
  created_at: string
  updated_at: string
}

export interface EventoCalendario {
  id: number
  title: string
  start: string
  end: string | null
  color: string
  extendedProps: {
    id_cita: number
    estado: string
    medico: string
    especialidad: string | null
    notas: string | null
  }
}

export interface ReprogramarCitaData {
  fecha_hora_inicio: string
  fecha_hora_fin?: string
  motivo_reprogramacion?: string
}

export interface CancelarCitaData {
  motivo_cancelacion?: string
}

export interface CalificarCitaData {
  puntuacion: number
  comentario?: string
}

export interface EstadisticasCitas {
  total: number
  pendientes: number
  confirmadas: number
  completadas: number
  canceladas: number
  proxima_cita: Cita | null
}

export interface FiltrosCitas {
  estado?: string
  desde?: string
  hasta?: string
  mes?: string
}

export interface FiltrosCalendario {
  desde?: string
  hasta?: string
}

/**
 * Servicio de Citas del Paciente
 * Maneja todas las peticiones relacionadas con las citas del paciente autenticado
 */
const citasPacienteService = {
  /**
   * Obtener todas las citas del paciente
   */
  async obtenerMisCitas(filtros?: FiltrosCitas) {
    const response = await apiClient.get('/clinica/mis-citas', {
      params: filtros
    })
    return response.data
  },

  /**
   * Obtener citas para vista de calendario
   */
  async obtenerMisCitasCalendario(filtros?: FiltrosCalendario) {
    const response = await apiClient.get('/clinica/mis-citas/calendario', {
      params: filtros
    })
    return response.data
  },

  /**
   * Obtener detalle de una cita específica
   */
  async obtenerDetalleCita(idCita: number) {
    const response = await apiClient.get(`/clinica/mis-citas/${idCita}`)
    return response.data
  },

  /**
   * Confirmar una cita pendiente
   */
  async confirmarCita(idCita: number) {
    const response = await apiClient.patch(`/clinica/mis-citas/${idCita}/confirmar`)
    return response.data
  },

  /**
   * Reprogramar una cita
   */
  async reprogramarCita(idCita: number, datos: ReprogramarCitaData) {
    const response = await apiClient.patch(`/clinica/mis-citas/${idCita}/reprogramar`, datos)
    return response.data
  },

  /**
   * Cancelar una cita
   */
  async cancelarCita(idCita: number, datos?: CancelarCitaData) {
    const response = await apiClient.patch(`/clinica/mis-citas/${idCita}/cancelar`, datos)
    return response.data
  },

  /**
   * Calificar una cita completada
   */
  async calificarCita(idCita: number, datos: CalificarCitaData) {
    const response = await apiClient.post(`/clinica/mis-citas/${idCita}/calificar`, datos)
    return response.data
  },

  /**
   * Obtener estadísticas de citas del paciente
   */
  async obtenerEstadisticas() {
    const response = await apiClient.get('/clinica/mis-citas/estadisticas/general')
    return response.data
  }
}

export default citasPacienteService
