/**
 * Tipos comunes para la API
 */

export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  message?: string
  errors?: Record<string, string[]>
}

export interface PaginationMeta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: PaginationMeta
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

export interface User {
  id_usuario: number
  username: string
  correo: string
  telefono?: string
  estado: 'activo' | 'inactivo' | 'suspendido'
  id_rol: number
  rol?: {
    id_rol: number
    nombre: string
    descripcion: string
  }
  created_at: string
  updated_at: string
}

export interface Medico {
  id_medico: number
  id_usuario: number
  nombres: string
  apellidos: string
  nro_colegiado?: string
  especialidad?: string
  anios_experiencia?: number
  tipo_medico: 'cabecera_manana' | 'cabecera_tarde' | 'especialista'
  estado_disponibilidad: 'disponible' | 'no_disponible'
  foto_url?: string
  usuario?: User
  created_at: string
  updated_at: string
}

export interface Paciente {
  id_paciente: number
  id_usuario: number
  nombres: string
  apellidos: string
  fecha_nacimiento: string
  genero: 'M' | 'F' | 'O'
  telefono?: string
  direccion?: string
  usuario?: User
  created_at: string
  updated_at: string
}

export interface ValidationError {
  field: string
  message: string
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  status?: number
}
