import apiClient from './axios'

// Interfaces
export interface UsuarioListItem {
  id_usuario: number
  username: string
  correo: string
  telefono?: string
  estado: 'activo' | 'inactivo'
  email_verified_at: string | null
  mfa_enabled: boolean
  created_at: string
  updated_at: string
  roles: Array<{
    id_rol: number
    nombre_rol: string
    descripcion?: string
  }>
  medico?: {
    id_medico: number
    nombres?: string
    apellidos?: string
    nro_colegiado: string
    especialidad?: string
    tipo_medico: 'cabecera_manana' | 'cabecera_tarde' | 'especialista'
    anios_experiencia?: number
    foto_url?: string
    estado_disponibilidad: 'disponible' | 'no_disponible'
  }
  paciente?: {
    id_paciente: number
    nombres: string
    apellidos: string
    fecha_nacimiento: string
    dni?: string
    sexo?: 'M' | 'F' | 'Otro'
    domicilio?: string
  }
}

export interface UsuarioDetalle extends UsuarioListItem {}

export interface CrearUsuarioData {
  username: string
  correo: string
  password: string
  password_confirmation: string
  telefono?: string
  rol: string // 'admin' | 'medico' | 'paciente'
  estado?: 'activo' | 'inactivo'
  // Datos de médico (si rol = médico) - pero el admin NO los envía, el doctor completa su perfil
  medico?: {
    nro_colegiado: string
    tipo_medico: 'cabecera_manana' | 'cabecera_tarde' | 'especialista'
    especialidad?: string
    anios_experiencia?: number
  }
  // Datos de paciente (si rol = paciente)
  paciente?: {
    nombres: string
    apellidos: string
    fecha_nacimiento: string
    direccion?: string
    ciudad?: string
  }
}

export interface ActualizarUsuarioData {
  username?: string
  correo?: string
  password?: string
  password_confirmation?: string
  telefono?: string
  estado?: 'activo' | 'inactivo'
  mfa_enabled?: boolean
  // Datos de médico
  medico?: {
    nro_colegiado?: string
    tipo_medico?: 'cabecera_manana' | 'cabecera_tarde' | 'especialista'
    especialidad?: string
    anios_experiencia?: number
    estado_disponibilidad?: 'disponible' | 'no_disponible'
  }
  // Datos de paciente
  paciente?: {
    nombres?: string
    apellidos?: string
    fecha_nacimiento?: string
    dni?: string
    sexo?: 'M' | 'F' | 'Otro'
    domicilio?: string
  }
}

export interface FiltrosUsuarios {
  rol?: number
  rolNombre?: string
  estado?: 'activo' | 'inactivo'
  busqueda?: string
  page?: number
  per_page?: number
}

export interface UsuariosResponse {
  data: UsuarioListItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface CambiarRolData {
  rol_id: number
}

// Servicio
class UserAdminService {
  /**
   * Listar usuarios con filtros y paginación
   */
  async listarUsuarios(filtros?: FiltrosUsuarios): Promise<UsuariosResponse> {
    const params = new URLSearchParams()

  // Backend espera nombre_rol, pero mantenemos compatibilidad
  if (filtros?.rolNombre) params.append('rol', filtros.rolNombre)
  else if (filtros?.rol) params.append('rol', filtros.rol.toString())
    if (filtros?.estado) params.append('estado', filtros.estado)
    // Backend usa 'buscar' (aceptar ambos por compatibilidad)
    if (filtros?.busqueda) params.append('buscar', filtros.busqueda)
    if (filtros?.page) params.append('page', filtros.page.toString())
    if (filtros?.per_page) params.append('per_page', filtros.per_page.toString())

    const queryString = params.toString()
    const url = queryString ? `/admin/usuarios?${queryString}` : '/admin/usuarios'

    const response = await apiClient.get(url)
    // El backend devuelve: { success: true, data: { usuarios: {...} } }
    const data = response.data?.data || response.data
    if (data?.usuarios) {
      return data.usuarios as UsuariosResponse
    }
    return data as UsuariosResponse
  }

  /**
   * Crear nuevo usuario
   */
  async crearUsuario(data: CrearUsuarioData): Promise<UsuarioDetalle> {
    const response = await apiClient.post<{ success: boolean; data: { user: UsuarioDetalle } }>('/admin/usuarios', data)
    return response.data.data.user
  }

  /**
   * Obtener detalles de un usuario
   */
  async obtenerUsuario(id: number): Promise<UsuarioDetalle> {
    const response = await apiClient.get<{ success: boolean; data: { user: UsuarioDetalle } }>(`/admin/usuarios/${id}`)
    return response.data.data.user
  }

  /**
   * Actualizar usuario
   */
  async actualizarUsuario(id: number, data: ActualizarUsuarioData): Promise<UsuarioDetalle> {
    const response = await apiClient.put<{ success: boolean; data: { user: UsuarioDetalle } }>(`/admin/usuarios/${id}`, data)
    return response.data.data.user
  }

  /**
   * Eliminar usuario
   */
  async eliminarUsuario(id: number): Promise<{ message: string }> {
    const response = await apiClient.delete<{ message: string }>(`/admin/usuarios/${id}`)
    return response.data
  }

  /**
   * Cambiar rol de usuario
   */
  async cambiarRol(id: number, data: CambiarRolData): Promise<UsuarioDetalle> {
    const response = await apiClient.post<UsuarioDetalle>(`/admin/usuarios/${id}/cambiar-rol`, data)
    return response.data
  }
}

export default new UserAdminService()
