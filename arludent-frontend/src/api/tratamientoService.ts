import apiClient from './axios'

// Interfaces
export interface Tratamiento {
  id_tratamiento: number
  categoria: string | null
  nombre: string
  descripcion: string | null
  precio_actual: number | null
  estado: 'activo' | 'inactivo'
  created_at: string
  updated_at: string
}

export interface CrearTratamientoData {
  nombre: string
  categoria?: string
  descripcion?: string
  precio_actual?: number
  estado?: 'activo' | 'inactivo'
}

export interface ActualizarTratamientoData {
  nombre?: string
  categoria?: string
  descripcion?: string
  precio_actual?: number
  estado?: 'activo' | 'inactivo'
}

export interface FiltrosTratamientos {
  categoria?: string
  estado?: 'activo' | 'inactivo'
  busqueda?: string
  page?: number
  per_page?: number | 'all'
}

export interface TratamientosResponse {
  current_page?: number
  data: Tratamiento[]
  first_page_url?: string
  from?: number
  last_page?: number
  last_page_url?: string
  links?: Array<{ url: string | null; label: string; active: boolean }>
  next_page_url?: string | null
  path?: string
  per_page?: number
  prev_page_url?: string | null
  to?: number
  total?: number
}

class TratamientoService {
  /**
   * Listar tratamientos con filtros
   */
  async listarTratamientos(filtros?: FiltrosTratamientos): Promise<TratamientosResponse> {
    const params = new URLSearchParams()

    if (filtros?.categoria) params.append('categoria', filtros.categoria)
    if (filtros?.estado) params.append('estado', filtros.estado)
    if (filtros?.busqueda) params.append('busqueda', filtros.busqueda)
    if (filtros?.page) params.append('page', filtros.page.toString())
    if (filtros?.per_page) params.append('per_page', filtros.per_page.toString())

    const response = await apiClient.get<{ success: boolean; data: { tratamientos: TratamientosResponse } }>(
      `/admin/tratamientos?${params.toString()}`
    )

    const data = response.data.data.tratamientos

    // Si es una respuesta paginada, devolverla tal cual
    if ('data' in data) {
      return data as TratamientosResponse
    }

    // Si es un array simple, convertirlo al formato esperado
    return {
      data: data as unknown as Tratamiento[]
    }
  }

  /**
   * Obtener un tratamiento específico
   */
  async obtenerTratamiento(id: number): Promise<Tratamiento> {
    const response = await apiClient.get<{ success: boolean; data: { tratamiento: Tratamiento } }>(
      `/admin/tratamientos/${id}`
    )
    return response.data.data.tratamiento
  }

  /**
   * Crear nuevo tratamiento
   */
  async crearTratamiento(data: CrearTratamientoData): Promise<Tratamiento> {
    const response = await apiClient.post<{ success: boolean; data: { tratamiento: Tratamiento } }>(
      '/admin/tratamientos',
      data
    )
    return response.data.data.tratamiento
  }

  /**
   * Actualizar tratamiento
   */
  async actualizarTratamiento(id: number, data: ActualizarTratamientoData): Promise<Tratamiento> {
    const response = await apiClient.put<{ success: boolean; data: { tratamiento: Tratamiento } }>(
      `/admin/tratamientos/${id}`,
      data
    )
    return response.data.data.tratamiento
  }

  /**
   * Cambiar estado del tratamiento
   */
  async cambiarEstado(id: number, estado: 'activo' | 'inactivo'): Promise<Tratamiento> {
    const response = await apiClient.post<{ success: boolean; data: { tratamiento: Tratamiento } }>(
      `/admin/tratamientos/${id}/cambiar-estado`,
      { estado }
    )
    return response.data.data.tratamiento
  }

  /**
   * Obtener lista de categorías
   */
  async obtenerCategorias(): Promise<string[]> {
    const response = await apiClient.get<{ success: boolean; data: { categorias: string[] } }>(
      '/admin/tratamientos/categorias'
    )
    return response.data.data.categorias
  }
}

export default new TratamientoService()
