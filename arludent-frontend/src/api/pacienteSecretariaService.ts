import apiClient from './axios'

/**
 * Servicio para gestión de pacientes desde secretaría
 */

export interface PacienteSecretaria {
  id_paciente: number
  nombre: string // Campo correcto de la BD
  apellidos: string
  dni: string // Campo correcto de la BD
  sexo: string | null // Campo correcto de la BD
  domicilio: string | null // Campo correcto de la BD
  telefono: string | null
  email: string | null
  edad: number | null
  fecha_nacimiento: string | null
  estado: string
  created_at: string
  updated_at: string
  ultima_cita?: {
    fecha: string
    motivo: string
  }
}

export interface FiltrosPacientes {
  nombre?: string
  documento?: string
  telefono?: string
  email?: string
  estado?: string
  fecha_desde?: string
  fecha_hasta?: string
  page?: number
  per_page?: number
}

export interface EstadisticasPacientes {
  total_pacientes: number
  pacientes_activos_mes: number
  pacientes_nuevos_mes: number
  pacientes_con_deuda: number
}

const pacienteSecretariaService = {
  /**
   * Búsqueda avanzada de pacientes
   */
  async busquedaAvanzada(filtros?: FiltrosPacientes) {
    const response = await apiClient.get('/secretaria/pacientes', {
      params: filtros
    })
    return response.data
  },

  /**
   * Obtener estadísticas generales de pacientes
   */
  async estadisticasGenerales() {
    const response = await apiClient.get('/secretaria/pacientes/estadisticas-generales')
    return response.data
  },

  /**
   * Obtener ficha completa de un paciente
   */
  async obtenerFichaCompleta(idPaciente: number) {
    const response = await apiClient.get(`/secretaria/pacientes/${idPaciente}`)
    return response.data
  },

  /**
   * Crear nuevo paciente
   */
  async crearPaciente(datos: {
    nombres: string
    apellidos: string
    tipo_documento: string
    numero_documento: string
    fecha_nacimiento?: string
    genero?: string
    telefono?: string
    email?: string
    direccion?: string
  }) {
    const response = await apiClient.post('/secretaria/pacientes', datos)
    return response.data
  },

  /**
   * Actualizar paciente
   */
  async actualizarPaciente(idPaciente: number, datos: Partial<PacienteSecretaria>) {
    const response = await apiClient.put(`/secretaria/pacientes/${idPaciente}`, datos)
    return response.data
  },

  /**
   * Obtener lista simple de pacientes (para búsqueda rápida)
   */
  async busquedaRapida(search?: string) {
    const response = await apiClient.get('/secretaria/pacientes/buscar', {
      params: { q: search }
    })
    return response.data
  }
}

export default pacienteSecretariaService
