import apiClient from './axios'

export interface UserProfile {
  id_usuario?: number
  username: string
  correo: string
  telefono?: string
  estado?: string
  mfa_enabled?: boolean
  email_verified_at?: string | null
  correo_verificado?: boolean
  roles?: any[]
}

export interface UpdateProfileData {
  username?: string
  telefono?: string
}

/**
 * Servicio de usuario
 * Maneja las peticiones relacionadas con el perfil del usuario
 */
const userService = {
  /**
   * Obtener perfil del usuario actual
   */
  async getProfile() {
    const response = await apiClient.get('/usuarios/perfil')
    return response.data
  },

  /**
   * Actualizar perfil del usuario
   */
  async updateProfile(data: UpdateProfileData) {
    const response = await apiClient.put('/usuarios/perfil', data)
    return response.data
  },

  /**
   * Obtener lista de usuarios (admin)
   */
  async getUsers(params?: any) {
    const response = await apiClient.get('/usuarios', { params })
    return response.data
  },

  /**
   * Obtener usuario por ID (admin)
   */
  async getUserById(id: number) {
    const response = await apiClient.get(`/usuarios/${id}`)
    return response.data
  },

  /**
   * Crear nuevo usuario (admin)
   */
  async createUser(data: any) {
    const response = await apiClient.post('/usuarios', data)
    return response.data
  },

  /**
   * Actualizar usuario (admin)
   */
  async updateUser(id: number, data: any) {
    const response = await apiClient.put(`/usuarios/${id}`, data)
    return response.data
  },

  /**
   * Eliminar usuario (admin)
   */
  async deleteUser(id: number) {
    const response = await apiClient.delete(`/usuarios/${id}`)
    return response.data
  },

  /**
   * Asignar rol a usuario (admin)
   */
  async assignRole(userId: number, roleId: number) {
    const response = await apiClient.post(`/usuarios/${userId}/roles`, { id_rol: roleId })
    return response.data
  },

  /**
   * Remover rol de usuario (admin)
   */
  async removeRole(userId: number, roleId: number) {
    const response = await apiClient.delete(`/usuarios/${userId}/roles/${roleId}`)
    return response.data
  }
}

export default userService
