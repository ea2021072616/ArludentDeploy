import apiClient from './axios'

export interface Pago {
  id_pago: number
  id_cita?: number | null
  concepto: string
  monto: number
  metodo_pago: string
  estado_pago: string
  fecha_pago?: string | null
  notas?: string | null
  registrado_por?: number | null
  created_at?: string
  tipo_comprobante?: 'boleta' | 'factura' | null
}

export interface PagosResponse {
  pagos: Pago[]
  total: number
  per_page?: number
  current_page?: number
}

class PagosService {
  async obtenerMisPagos(params: { per_page?: number } = {}): Promise<{ success: boolean; data: PagosResponse }> {
    const response = await apiClient.get<{ success: boolean; data: PagosResponse }>('/clinica/mis-pagos', { params })
    return response.data
  }

  /**
   * Descargar PDF de un pago individual (boleta o factura)
   * Usa el endpoint específico para pacientes
   */
  async descargarPDFPago(idPago: number, params: { tipo: 'boleta' | 'factura' }) {
    const response = await apiClient.get(`/clinica/mis-pagos/${idPago}/pdf`, {
      params,
      responseType: 'blob'
    })
    return response
  }
}

export default new PagosService()
