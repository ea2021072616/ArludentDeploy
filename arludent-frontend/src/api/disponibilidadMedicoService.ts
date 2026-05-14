import axios from '@/api/axios'
import type { ApiResponse } from '@/api/types'

export interface DisponibilidadMedico {
  id_disp: number
  tipo: 'horario' | 'bloqueo'
  dia_semana: number | null
  dia_semana_texto: string | null
  fecha_inicio: string | null
  fecha_fin: string | null
  hora_inicio: string | null
  hora_fin: string | null
  motivo: string | null
  es_horario_cabecera: boolean
  puede_eliminar: boolean
  created_at: string
  updated_at: string
}

export interface CrearDisponibilidadData {
  tipo: 'horario' | 'bloqueo'
  dia_semana?: number | null
  fecha_inicio?: string | null
  fecha_fin?: string | null
  hora_inicio?: string | null
  hora_fin?: string | null
  motivo?: string | null
}

export interface ActualizarDisponibilidadData extends Partial<CrearDisponibilidadData> {}

export interface HorariosDisponiblesResponse {
  fecha: string
  dia_semana: number
  dia_semana_texto: string
  horarios: Array<{
    hora_inicio: string
    hora_fin: string
    disponible: boolean
  }>
}

class DisponibilidadMedicoService {
  private readonly baseUrl = '/clinica/medico/disponibilidad'

  /**
   * Obtener disponibilidad del médico autenticado
   */
  async obtenerDisponibilidad(params?: {
    tipo?: 'horario' | 'bloqueo'
    dia_semana?: number
    desde?: string
    hasta?: string
  }): Promise<ApiResponse<{
    disponibilidades: DisponibilidadMedico[]
    tipo_medico: 'cabecera_manana' | 'cabecera_tarde' | 'especialista'
    horarios_predefinidos: { hora_inicio: string; hora_fin: string } | null
    es_cabecera: boolean
    total: number
  }>> {
    try {
      const response = await axios.get(this.baseUrl, { params })
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  /**
   * Crear nueva disponibilidad
   */
  async crearDisponibilidad(data: CrearDisponibilidadData): Promise<ApiResponse<{
    disponibilidad: DisponibilidadMedico
  }>> {
    try {
      const response = await axios.post(this.baseUrl, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  /**
   * Actualizar disponibilidad existente
   */
  async actualizarDisponibilidad(
    id: number,
    data: ActualizarDisponibilidadData
  ): Promise<ApiResponse<{
    disponibilidad: DisponibilidadMedico
  }>> {
    try {
      const response = await axios.put(`${this.baseUrl}/${id}`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  /**
   * Eliminar disponibilidad
   */
  async eliminarDisponibilidad(id: number): Promise<ApiResponse<null>> {
    try {
      const response = await axios.delete(`${this.baseUrl}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  /**
   * Obtener horarios disponibles para agendar citas
   */
  async obtenerHorariosDisponibles(params: {
    id_medico: number
    fecha: string
    duracion_minutos?: number
  }): Promise<ApiResponse<HorariosDisponiblesResponse>> {
    try {
      const response = await axios.get(`${this.baseUrl}/horarios-disponibles`, { params })
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  /**
   * Utilidades para trabajar con disponibilidad
   */
  getDiaSemanaTexto(dia: number | null): string | null {
    if (dia === null) return null

    const dias = [
      'Domingo',
      'Lunes',
      'Martes',
      'Miércoles',
      'Jueves',
      'Viernes',
      'Sábado'
    ]

    return dias[dia] || null
  }

  /**
   * Validar datos de disponibilidad antes de enviar
   */
  validarDisponibilidad(data: CrearDisponibilidadData, tipoHorario?: 'semanal' | 'especifico'): { valido: boolean; errores: string[] } {
    const errores: string[] = []

    if (!data.tipo || !['horario', 'bloqueo'].includes(data.tipo)) {
      errores.push('El tipo debe ser "horario" o "bloqueo"')
    }

    // Validaciones específicas según el tipo de horario
    if (tipoHorario === 'semanal') {
      // Para horarios semanales: debe tener día de semana, NO fechas
      if (!data.dia_semana && data.dia_semana !== 0) {
        errores.push('Los horarios semanales requieren seleccionar un día de la semana')
      }
      if (data.fecha_inicio || data.fecha_fin) {
        errores.push('Los horarios semanales no deben tener fechas específicas')
      }
    } else if (tipoHorario === 'especifico') {
      // Para horarios específicos: debe tener fechas, NO día de semana
      if (data.dia_semana !== null && data.dia_semana !== undefined) {
        errores.push('Los horarios específicos no deben tener día de la semana seleccionado')
      }
      if (!data.fecha_inicio || !data.fecha_fin) {
        errores.push('Los horarios específicos requieren fecha de inicio y fin')
      }
    }

    if (data.dia_semana !== undefined && data.dia_semana !== null) {
      if (data.dia_semana < 0 || data.dia_semana > 6) {
        errores.push('El día de la semana debe estar entre 0 (Domingo) y 6 (Sábado)')
      }
    }

    if (data.fecha_inicio && data.fecha_fin) {
      const fechaInicio = new Date(data.fecha_inicio)
      const fechaFin = new Date(data.fecha_fin)

      if (fechaInicio >= fechaFin) {
        errores.push('La fecha de fin debe ser posterior a la fecha de inicio')
      }
    }

    if (data.hora_inicio && data.hora_fin) {
      if (data.hora_inicio >= data.hora_fin) {
        errores.push('La hora de fin debe ser posterior a la hora de inicio')
      }
    }

    // Validaciones específicas por tipo
    if (data.tipo === 'horario') {
      if (!data.hora_inicio || !data.hora_fin) {
        errores.push('Los horarios requieren hora de inicio y fin')
      }
    }

    return {
      valido: errores.length === 0,
      errores
    }
  }
}

const disponibilidadMedicoService = new DisponibilidadMedicoService()
export default disponibilidadMedicoService
