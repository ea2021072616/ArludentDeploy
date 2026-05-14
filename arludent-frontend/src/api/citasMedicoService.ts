import apiClient from './axios'

/**
 * Interfaces para Citas del Médico
 */

export interface Paciente {
  id_paciente: number
  nombres: string
  apellidos: string
  dni: string | null
  telefono: string | null
}

export interface Calificacion {
  id_calificacion: number
  puntuacion: number
  comentario: string | null
  fecha: string
}

export interface CitaMedico {
  id_cita: number
  fecha_hora_inicio: string
  fecha_hora_fin: string | null
  motivo: string | null
  estado: 'pendiente' | 'confirmado' | 'en_espera' | 'siendo_atendido' | 'completado' | 'cancelado' | 'no_asistio'
  notas: string | null
  paciente: Paciente
  puede_cancelar?: boolean
  puede_completar?: boolean
  puede_agregar_notas?: boolean
  puede_confirmar?: boolean
  puede_reprogramar?: boolean
  puede_calificar?: boolean
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
    paciente: string
    motivo: string | null
    notas: string | null
  }
}

export interface CancelarCitaData {
  motivo_cancelacion?: string
}

export interface CompletarCitaData {
  notas?: string
}

export interface AgregarNotasData {
  notas: string
}

export interface EstadisticasCitasMedico {
  total: number
  pendientes: number
  confirmadas: number
  completadas: number
  canceladas: number
  proxima_cita: CitaMedico | null
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
 * Servicio de Citas del Médico
 * Maneja todas las peticiones relacionadas con las citas del médico autenticado
 */
const citasMedicoService = {
  /**
   * Obtener todas las citas del médico
   */
  async obtenerMisCitas(filtros?: FiltrosCitas) {
    const response = await apiClient.get('/clinica/medico/citas', {
      params: filtros
    })
    return response.data
  },

  /**
   * Obtener citas para vista de calendario (agrupadas por día)
   */
  async obtenerMisCitasCalendario(filtros?: FiltrosCalendario) {
    const response = await apiClient.get('/clinica/medico/citas/calendario', {
      params: filtros
    })
    return response.data
  },

  /**
   * Obtener detalle de una cita específica
   */
  async obtenerDetalleCita(idCita: number) {
    const response = await apiClient.get(`/clinica/medico/citas/${idCita}`)
    return response.data
  },

  /**
   * Cancelar una cita
   */
  async cancelarCita(idCita: number, datos?: CancelarCitaData) {
    const response = await apiClient.patch(`/clinica/medico/citas/${idCita}/cancelar`, datos)
    return response.data
  },

  /**
   * Marcar cita como completada
   */
  async completarCita(idCita: number, datos?: CompletarCitaData) {
    const response = await apiClient.patch(`/clinica/medico/citas/${idCita}/completar`, datos)
    return response.data
  },

  /**
   * Agregar notas a una cita
   */
  async agregarNotas(idCita: number, datos: AgregarNotasData) {
    const response = await apiClient.patch(`/clinica/medico/citas/${idCita}/notas`, datos)
    return response.data
  },

  /**
   * Obtener estadísticas de citas del médico
   */
  async obtenerEstadisticas() {
    const response = await apiClient.get('/clinica/medico/citas/estadisticas/general')
    return response.data
  }
}

export default citasMedicoService
