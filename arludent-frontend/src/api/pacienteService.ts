import apiClient from './axios'

// Interfaces
export interface Paciente {
  id_paciente: number
  id_usuario?: number
  nombres: string
  apellidos: string
  dni?: string
  fecha_nacimiento?: string
  sexo?: 'M' | 'F' | 'Otro'
  domicilio?: string
  persona_responsable?: string
  telefono_responsable?: string
  grupo_sanguineo?: string
  alergias?: string
  estado: 'activo' | 'inactivo'
}

export interface ActualizarPacienteData {
  nombres?: string
  apellidos?: string
  dni?: string
  fecha_nacimiento?: string
  sexo?: 'M' | 'F' | 'Otro'
  domicilio?: string
  persona_responsable?: string
  telefono_responsable?: string
  grupo_sanguineo?: string
  alergias?: string
  estado?: 'activo' | 'inactivo'
}

// API
export async function actualizarPaciente(id: number, data: ActualizarPacienteData): Promise<Paciente> {
  const response = await apiClient.put(`/clinica/pacientes/${id}`, data)
  return response.data.data.paciente
}

export async function obtenerPaciente(id: number): Promise<Paciente> {
  const response = await apiClient.get(`/clinica/pacientes/${id}`)
  return response.data.data.paciente
}
