import apiClient from './axios'

/**
 * Interfaces para Dashboard de Secretaria
 */

export interface AlertaSecretaria {
  tipo: 'info' | 'warning' | 'danger' | 'urgente' | 'advertencia'
  mensaje: string
  icono: string
}

export interface AccesoRapidoSecretaria {
  titulo: string
  icono: string
  url: string
  color: string
}

export interface EstadisticaSecretaria {
  titulo: string
  valor: number | string
  porcentaje?: number
  tendencia: 'subida' | 'bajada' | 'neutral'
  icono: string
  color: string
}

export interface DashboardSecretariaResponse {
  fecha: string
  resumen_citas: {
    total: number
    confirmadas: number
    en_espera: number
    siendo_atendidas: number
    completadas: number
    no_asistieron: number
    canceladas: number
  }
  pacientes_en_cola: Array<{
    cita_id: number
    paciente: string
    hora_cita: string
    estado: string
    tiempo_espera: number | null
  }>
  resumen_pagos: {
    ingresos_dia: number
    pagos_realizados: number
    pagos_pendientes: number
  }
  alertas: AlertaSecretaria[]
  accesos_rapidos: AccesoRapidoSecretaria[]
  estadisticas_rapidas: {
    eficiencia_atencion: number
    tiempo_promedio_espera: number
    pacientes_nuevos_semana: number
    porcentaje_asistencia: number
  }
  actualizacion: string
}

/**
 * Servicio para Dashboard de Secretaria
 */
export const secretariaService = {
  /**
   * Obtener datos del dashboard de secretaria
   */
  async getDashboard(): Promise<DashboardSecretariaResponse> {
    const response = await apiClient.get('/secretaria/dashboard')
    return response.data.data
  }
}

export default secretariaService