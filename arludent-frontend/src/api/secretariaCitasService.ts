import apiClient from './axios'

/**
 * Servicio para gestión de citas desde secretaría
 */

export interface CitaSecretaria {
  id_cita: number
  fecha_hora_inicio: string
  fecha_hora_fin: string | null
  motivo: string | null
  estado: string
  notas: string | null
  paciente: {
    id_paciente: number | null
    nombres: string
    apellidos: string
    dni: string | null
    telefono: string | null
  } | null
  usuario_externo: {
    id_usuario: number
    username: string
  } | null
  medico: {
    id_medico: number
    nombres: string
    apellidos: string
    especialidad: string
  } | null
  calificacion: any
  created_at: string
  updated_at: string
}

export interface FiltrosCitasSecretaria {
  estado?: string
  fecha_desde?: string
  fecha_hasta?: string
  medico_id?: number
}

const secretariaCitasService = {
  /**
   * Obtener todas las citas
   */
  async obtenerTodasLasCitas(filtros?: FiltrosCitasSecretaria) {
    const response = await apiClient.get('/secretaria/citas', {
      params: filtros
    })
    return response.data
  },

  /**
   * Obtener citas para vista de calendario
   */
  async obtenerCitasCalendario(filtros?: { mes?: string; medico_id?: number }) {
    const response = await apiClient.get('/secretaria/citas/calendario', {
      params: filtros
    })
    return response.data
  },

  /**
   * Obtener detalle de una cita
   */
  async obtenerDetalleCita(idCita: number) {
    const response = await apiClient.get(`/secretaria/citas/${idCita}`)
    return response.data
  },

  /**
   * Confirmar una cita
   */
  async confirmarCita(idCita: number) {
    const response = await apiClient.patch(`/secretaria/citas/${idCita}/confirmar`)
    return response.data
  },

  /**
   * Cambiar estado de una cita
   */
  async cambiarEstado(idCita: number, nuevoEstado: string) {
    const response = await apiClient.patch(`/secretaria/citas/${idCita}/estado`, {
      estado: nuevoEstado
    })
    return response.data
  },

  /**
   * Cancelar una cita
   */
  async cancelarCita(idCita: number, datos?: { notas?: string }) {
    const response = await apiClient.patch(`/secretaria/citas/${idCita}/cancelar`, datos)
    return response.data
  },

  /**
   * Obtener lista de médicos
   */
  async obtenerMedicos() {
    const response = await apiClient.get('/secretaria/medicos')
    // Normalizar respuesta similar a otros endpoints: devolver el objeto 'data' interno
    return response.data?.data ?? response.data
  },

  /**
   * Obtener lista de pacientes
   */
  async obtenerPacientes(search?: string) {
    // El backend espera el parámetro 'buscar' (ver PacienteSecretariaController::busquedaAvanzada)
    // Enviar 'buscar' en lugar de 'search' para asegurar compatibilidad
    const params: any = {}
    if (search !== undefined) params.buscar = search
    const response = await apiClient.get('/secretaria/pacientes', {
      params
    })

    // Normalizar la respuesta: el backend devuelve { success, message, data }
    // Queremos devolver el objeto interno 'data' cuando exista para que la vista
    // obtenga directamente { pacientes, ... }.
    return response.data?.data ?? response.data
  },

  /**
   * Crear nueva cita
   */
  async crearCita(datos: {
    id_paciente: number
    id_medico: number
    fecha_hora_inicio: string
    duracion?: number
    motivo?: string
    notas?: string
  }) {
    const response = await apiClient.post('/secretaria/citas', datos)
    return response.data
  }
}

export default secretariaCitasService
