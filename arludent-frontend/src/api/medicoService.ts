import apiClient from './axios'

// Interfaces
export interface PerfilMedico {
  id_medico: number
  nombres?: string
  apellidos?: string
  nro_colegiado: string
  especialidad?: string
  tipo_medico: 'cabecera_manana' | 'cabecera_tarde' | 'especialista'
  anios_experiencia?: number
  foto_url?: string
  estado_disponibilidad: 'disponible' | 'no_disponible'
  created_at: string
  updated_at: string
  usuario: {
    id_usuario: number
    username: string
    correo: string
    telefono?: string
  }
}

export interface ActualizarPerfilMedicoData {
  nombres?: string
  apellidos?: string
  nro_colegiado?: string
  especialidad?: string
  anios_experiencia?: number
  estado_disponibilidad?: 'disponible' | 'no_disponible'
  foto?: File
}

// Servicio
class MedicoService {
  /**
   * Obtener perfil profesional del médico autenticado
   */
  async obtenerPerfil(): Promise<PerfilMedico> {
    const response = await apiClient.get<{ data: { medico: PerfilMedico } }>('/usuarios/medico/perfil')
    return response.data.data.medico
  }

  /**
   * Actualizar perfil profesional del médico
   */
  async actualizarPerfil(data: ActualizarPerfilMedicoData): Promise<PerfilMedico> {
    const formData = new FormData()
    // Añadir el campo _method para simular una petición PUT
    formData.append('_method', 'PUT')

    // Añadir los campos de texto si existen
  if (data.nombres) formData.append('nombres', data.nombres)
  if (data.apellidos) formData.append('apellidos', data.apellidos)
    if (data.nro_colegiado) formData.append('nro_colegiado', data.nro_colegiado)
    if (data.especialidad) formData.append('especialidad', data.especialidad)
    if (data.anios_experiencia !== undefined) {
      formData.append('anios_experiencia', data.anios_experiencia.toString())
    }
    if (data.estado_disponibilidad) {
      formData.append('estado_disponibilidad', data.estado_disponibilidad)
    }

    // Añadir el archivo de foto si existe
    if (data.foto) {
      formData.append('foto', data.foto)
    }

    // La petición se envía como POST, pero Laravel la interpretará como PUT
    const response = await apiClient.post<{ data: { medico: PerfilMedico } }>('/usuarios/medico/perfil', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    // El backend responde con { data: { medico: ... } }
    return response.data.data.medico
  }

  /**
   * Eliminar foto de perfil
   */
  async eliminarFoto(): Promise<{ message: string }> {
    const response = await apiClient.delete<{ message: string }>('/usuarios/medico/perfil/foto')
    return response.data
  }

  /**
   * Obtener URL completa de la foto
   */
  getFotoUrl(fotoPath?: string): string | null {
    if (!fotoPath) return null

    // Si ya es una URL completa, retornarla
    if (fotoPath.startsWith('http')) return fotoPath

    // Normalizar posible slash inicial
    let path = fotoPath
    if (path.startsWith('/')) path = path.substring(1)

    // Eliminar /api del final de la base URL si existe
    const apiEnv = import.meta.env.VITE_API_URL || 'http://localhost:8000'
    let baseUrl: string
    try {
      const url = new URL(apiEnv)
      baseUrl = url.origin
    } catch (e) {
      baseUrl = apiEnv.replace(/\/+$/, '').replace(/\/api$/i, '')
    }

    // Si el path ya incluye 'storage/' no lo dupliquemos
    if (path.startsWith('storage/')) {
      const fullUrl = `${baseUrl}/${path}`
      console.log('🖼️ Foto URL (ya incluye storage):', fullUrl)
      return fullUrl
    }

    // Por defecto servir desde /storage/<path>
    const fullUrl = `${baseUrl}/storage/${path}`
    console.log('🖼️ Foto URL (añadiendo storage):', fullUrl)
    return fullUrl
  }
}

export default new MedicoService()
