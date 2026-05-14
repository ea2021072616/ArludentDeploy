import apiClient from './axios'

/**
 * Interfaces para Caja y Pagos con SUNAT
 */

export interface Pago {
  id_pago: number
  id_paciente: number
  id_cita?: number
  monto: number
  fecha_pago: string
  metodo_pago: 'efectivo' | 'tarjeta' | 'transferencia' | 'cheque' | 'yape' | 'plin' | 'otros'
  estado_pago: 'pagado' | 'pendiente' | 'anulado'
  concepto?: string
  observaciones?: string

  // Campos SUNAT
  tipo_comprobante?: 'boleta' | 'factura' | 'nota_credito' | 'nota_debito' | 'ninguno'
  serie_comprobante?: string
  numero_comprobante?: string
  ruc_emisor?: string
  razon_social_emisor?: string
  tipo_documento_cliente?: '1' | '6' | '4' // 1=DNI, 6=RUC, 4=CE
  numero_documento_cliente?: string
  nombre_cliente?: string
  direccion_cliente?: string
  subtotal?: number
  igv?: number
  total?: number
  estado_sunat?: 'aceptado' | 'rechazado' | 'pendiente'
  respuesta_sunat?: string
  hash_comprobante?: string
  codigo_qr?: string
  enlace_pdf?: string
  enlace_xml?: string
  fecha_emision_comprobante?: string

  // Relaciones
  paciente?: {
    id_paciente: number
    nombre: string
    apellidos: string
    dni: string
    telefono?: string
  }

  cita?: {
    id_cita: number
    fecha_cita: string
    hora_inicio: string
  }

  registrado_por?: {
    id_usuario: number
    nombre: string
    apellidos: string
  }

  // Computed
  comprobante_completo?: string
  aceptado_sunat?: boolean
  tiene_comprobante?: boolean
}

export interface EstadisticasCaja {
  total_pagos: number
  monto_total: number
  pagos_pendientes: number
  monto_pendiente: number
  pagos_por_metodo: {
    metodo: string
    cantidad: number
    monto: number
  }[]
  pagos_por_tipo_comprobante: {
    tipo: string
    cantidad: number
    monto: number
  }[]
  ultimos_7_dias: {
    fecha: string
    cantidad: number
    monto: number
  }[]
}

export interface DatosComprobante {
  tipo: 'boleta' | 'factura'
  serie?: string

  // Para Boleta (DNI/CE)
  tipo_documento_cliente?: '1' | '4' // 1=DNI, 4=CE
  numero_documento_cliente?: string
  nombre_cliente?: string

  // Para Factura (RUC)
  ruc_cliente?: string
  razon_social_cliente?: string
  direccion_cliente?: string

  // Datos de la empresa emisora (opcionales)
  ruc_emisor?: string
  razon_social_emisor?: string

  // Observaciones
  observaciones?: string
}

export interface PacienteBusqueda {
  id_paciente: number
  nombre: string
  apellidos: string
  dni: string
  telefono?: string
  email?: string
  edad?: number
}

export interface FiltrosPagos {
  estado_pago?: 'pagado' | 'pendiente' | 'anulado'
  tipo_comprobante?: 'boleta' | 'factura' | 'nota_credito' | 'nota_debito' | 'ninguno'
  metodo_pago?: 'efectivo' | 'tarjeta' | 'transferencia' | 'cheque' | 'yape' | 'plin' | 'otros'
  fecha_inicio?: string
  fecha_fin?: string
  paciente?: string
  comprobante?: string
}

export interface ListaPagosResponse {
  pagos: Pago[]
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface RegistrarPagoData {
  id_paciente: number
  id_cita?: number
  monto: number
  metodo_pago: 'efectivo' | 'tarjeta' | 'transferencia' | 'cheque' | 'yape' | 'plin' | 'otros'
  concepto: string
  fecha_pago: string
  notas?: string
  emitir_comprobante?: boolean
  datos_comprobante?: DatosComprobante
}


/**
 * Servicio para Caja y Pagos con SUNAT
 */
export const cajaService = {
  /**
   * Obtener estadísticas de caja
   */
  async getEstadisticasCaja(filtros?: {
    fecha_inicio?: string
    fecha_fin?: string
  }): Promise<EstadisticasCaja> {
    const response = await apiClient.get('/secretaria/caja/estadisticas', {
      params: filtros
    })
    return response.data.data
  },

  /**
   * Listar pagos con filtros
   */
  async listarPagos(filtros: FiltrosPagos = {}): Promise<ListaPagosResponse> {
    const response = await apiClient.get('/secretaria/caja/pagos', {
      params: filtros
    })
    return response.data.data
  },

  /**
   * Ver detalle de un pago
   */
  async verPago(idPago: number): Promise<Pago> {
    const response = await apiClient.get(`/secretaria/caja/pagos/${idPago}`)
    return response.data.data
  },

  /**
   * Registrar nuevo pago
   */
  async registrarPago(data: RegistrarPagoData): Promise<Pago> {
    const response = await apiClient.post('/secretaria/caja/pagos', data)
    return response.data.data
  },

  /**
   * Emitir comprobante electrónico para un pago
   */
  async emitirComprobante(
    idPago: number,
    datosComprobante: DatosComprobante
  ): Promise<Pago> {
    console.log('🎫 cajaService.emitirComprobante() - Input:', {
      idPago,
      datosComprobante
    })

    // Transformar datos para el backend
    const payload = {
      tipo_comprobante: datosComprobante.tipo,
      serie: datosComprobante.serie || (datosComprobante.tipo === 'boleta' ? 'B001' : 'F001'),
      tipo_documento_cliente: datosComprobante.tipo === 'boleta'
        ? (datosComprobante.tipo_documento_cliente || '1')
        : '6', // RUC para facturas
      numero_documento_cliente: datosComprobante.tipo === 'boleta'
        ? datosComprobante.numero_documento_cliente
        : datosComprobante.ruc_cliente,
      nombre_cliente: datosComprobante.tipo === 'boleta'
        ? datosComprobante.nombre_cliente
        : datosComprobante.razon_social_cliente,
      direccion_cliente: datosComprobante.direccion_cliente || '',
      observaciones: datosComprobante.observaciones || ''
    }

    console.log('📤 cajaService.emitirComprobante() - Payload transformado:', payload)

    const response = await apiClient.post(
      `/secretaria/caja/pagos/${idPago}/comprobante`,
      payload
    )

    console.log('✅ cajaService.emitirComprobante() - Response:', response.data)

    return response.data.data
  },  /**
   * Buscar pacientes (para asignar pago)
   */
  async buscarPacientes(termino: string): Promise<PacienteBusqueda[]> {
    const response = await apiClient.get('/secretaria/caja/pacientes/buscar', {
      params: { termino }
    })
    return response.data.data
  },

  /**
   * Descargar PDF de comprobante
   */
  descargarPDF(enlacePdf: string): void {
    window.open(enlacePdf, '_blank')
  },

  /**
   * Descargar XML de comprobante
   */
  descargarXML(enlaceXml: string): void {
    window.open(enlaceXml, '_blank')
  },

  /**
   * Calcular IGV y totales
   */
  calcularIGV(monto: number): { subtotal: number; igv: number; total: number } {
    const subtotal = parseFloat((monto / 1.18).toFixed(2))
    const igv = parseFloat((monto - subtotal).toFixed(2))
    return {
      subtotal,
      igv,
      total: monto
    }
  },

  /**
   * Formatear monto en soles
   */
  formatearMonto(monto: number | undefined | null): string {
    if (monto === undefined || monto === null || isNaN(monto)) {
      return 'S/ 0.00'
    }
    return `S/ ${Number(monto).toFixed(2)}`
  },

  /**
   * Obtener etiqueta de método de pago
   */
  obtenerEtiquetaMetodo(metodo: string): string {
    const etiquetas: Record<string, string> = {
      efectivo: 'Efectivo',
      tarjeta: 'Tarjeta',
      transferencia: 'Transferencia',
      cheque: 'Cheque',
      yape: 'Yape',
      plin: 'Plin',
      otros: 'Otros'
    }
    return etiquetas[metodo] || metodo
  },

  /**
   * Obtener etiqueta de tipo de comprobante
   */
  obtenerEtiquetaTipoComprobante(tipo: string): string {
    const etiquetas: Record<string, string> = {
      boleta: 'Boleta',
      factura: 'Factura',
      nota_credito: 'Nota de Crédito',
      nota_debito: 'Nota de Débito',
      ninguno: 'Sin comprobante'
    }
    return etiquetas[tipo] || tipo
  },

  /**
   * Obtener clase CSS para badge de estado SUNAT
   */
  obtenerClaseEstadoSunat(estado?: string): string {
    switch (estado) {
      case 'aceptado':
        return 'bg-green-100 text-green-800'
      case 'rechazado':
        return 'bg-red-100 text-red-800'
      case 'pendiente':
        return 'bg-yellow-100 text-yellow-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  },

  /**
   * Obtener clase CSS para badge de estado de pago
   */
  obtenerClaseEstadoPago(estado: string): string {
    switch (estado) {
      case 'pagado':
        return 'bg-green-100 text-green-800'
      case 'pendiente':
        return 'bg-yellow-100 text-yellow-800'
      case 'anulado':
        return 'bg-red-100 text-red-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  },

  /**
   * Generar PDF simple de un pago
   */
  async generarPDFPago(idPago: number, params: { tipo: 'boleta' | 'factura' }) {
    const response = await apiClient.get(`/secretaria/caja/pagos/${idPago}/pdf`, {
      params
    })
    return response.data
  }
  ,

  /**
   * Descargar PDF (stream) desde backend — devuelve Blob
   */
  async descargarPDFPago(idPago: number, params: { tipo: 'boleta' | 'factura' }) {
    const response = await apiClient.get(`/secretaria/caja/pagos/${idPago}/pdf/download`, {
      params,
      responseType: 'blob'
    })
    return response
  },
}

export default cajaService
