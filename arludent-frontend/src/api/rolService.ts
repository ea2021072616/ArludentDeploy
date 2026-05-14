import apiClient from './axios'

// Interfaces
export interface Rol {
  id_rol: number
  nombre_rol: string
  descripcion?: string
  created_at?: string
  updated_at?: string
}

// Servicio
class RolService {
  /**
   * Obtener todos los roles disponibles
   */
  async listarRoles(): Promise<Rol[]> {
    const response = await apiClient.get<Rol[]>('/sistema/roles')
    return response.data
  }
}

export default new RolService()
