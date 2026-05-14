import apiClient from './axios'

// Interfaces
export interface PacienteHistorial {
  tipo: 'paciente' | 'externo' // Nuevo: indica el tipo de registro
  id_usuario: number | null
  id_paciente: number | null
  id_usuario_externo: number | null
  nombres: string
  apellidos: string
  nombre_completo: string
  dni: string | null
  telefono?: string | null
  correo?: string | null
  tiene_registro_paciente: boolean // Nuevo: indica si tiene registro completo
  tiene_historial: boolean
  id_historial?: number | null
  codigo_historial?: string | null
  fecha_creacion_historial?: string | null
  estado: 'pendiente_registro' | 'completo' // Nuevo: estado del registro
}

export interface HistorialClinico {
  id_historial: number
  id_paciente: number
  id_medico_responsable?: number
  codigo_historial?: string
  motivo_consulta?: string
  diagnostico_presuntivo?: string
  diagnostico_principal?: string
  higiene_bucal?: 'Bueno' | 'Regular' | 'Malo'
  // Campos de anamnesis
  sintoma_principal?: string
  tiempo_inicio_sintomas?: string
  tratamiento_previo?: string
  enfermedades_actuales?: string
  bajo_tratamiento_medico?: boolean
  detalle_tratamiento_actual?: string
  alergias_paciente?: string
  intervenciones_quirurgicas_previas?: boolean
  detalle_intervenciones?: string
  hemorragia_post_tratamiento?: boolean
  problema_anestesia?: boolean
  dificultad_abrir_masticar?: boolean
  sensibilidad_dental?: boolean
  consentimiento_id?: number
  created_by?: number
  created_at: string
  updated_at: string
  paciente: {
    id_paciente: number
    nombres: string
    apellidos: string
    dni: string
    telefono?: string
    correo?: string
    fecha_nacimiento?: string
    sexo?: 'M' | 'F' | 'Otro'
    domicilio?: string
    persona_responsable?: string
    telefono_responsable?: string
    grupo_sanguineo?: string
    alergias?: string
  }
  medicoResponsable?: {
    id_medico: number
    nombres: string
    apellidos: string
  }
  detalles: DetalleHistorial[]
  tratamientos: TratamientoHistorial[]
  odontograma: Odontograma[]
  prescripciones: Prescripcion[]
}

export interface DetalleHistorial {
  id_detalle: number
  id_historial: number
  id_cita?: number
  fecha: string
  diagnostico?: string
  procedimiento_realizado?: string
  notas_medicas?: string
  realizado_por?: number
  created_at: string
  updated_at: string
  realizadoPor?: {
    id_medico: number
    nombres: string
    apellidos: string
  }
}

export interface SeguimientoTratamiento {
  id_seguimiento: number
  id_historial: number
  id_tratamiento_historial: number
  fecha_registro: string
  descripcion?: string
  duracion_restante?: number
  registrado_por?: number
  created_at: string
  updated_at: string
  registradoPor?: {
    id_medico: number
    nombres: string
    apellidos: string
  }
}

export interface TratamientoHistorial {
  id: number
  id_historial: number
  id_detalle_historial?: number
  id_tratamiento: number
  descripcion?: string
  fecha_inicio?: string
  fecha_fin?: string
  estado: 'sugerido' | 'en_curso' | 'completado' | 'cancelado'
  precio?: number
  realizado_por?: number
  created_at: string
  updated_at: string
  tratamiento: {
    id_tratamiento: number
    nombre: string
  }
  seguimientos?: SeguimientoTratamiento[]
}

export interface Odontograma {
  id_odontograma: number
  id_historial: number
  pieza: string
  estado_pieza: 'sano' | 'cariado' | 'restaurado' | 'ausente' | 'protesis' | 'otros'
  tratamiento_asociado?: string
  comentario?: string
  fecha_registro: string
}

export interface Prescripcion {
  id_prescripcion: number
  id_historial: number
  medicamento: string
  dosis?: string
  frecuencia?: string
  duracion?: string
  indicaciones?: string
  fecha_prescripcion: string
  prescrito_por?: number
  created_at: string
  updated_at: string
  prescritoPor?: {
    id_medico: number
    nombres: string
    apellidos: string
  }
}

export interface CrearHistorialData {
  id_paciente?: number
  id_usuario_externo?: number
  motivo_consulta?: string
  diagnostico_presuntivo?: string
  diagnostico_principal?: string
  higiene_bucal?: 'Bueno' | 'Regular' | 'Malo' | null
  // Campos de anamnesis
  sintoma_principal?: string
  tiempo_inicio_sintomas?: string
  tratamiento_previo?: string
  enfermedades_actuales?: string
  bajo_tratamiento_medico?: boolean
  detalle_tratamiento_actual?: string
  alergias_paciente?: string
  intervenciones_quirurgicas_previas?: boolean
  detalle_intervenciones?: string
  hemorragia_post_tratamiento?: boolean
  problema_anestesia?: boolean
  dificultad_abrir_masticar?: boolean
  sensibilidad_dental?: boolean
  // Datos del paciente (para usuarios externos)
  datos_paciente?: {
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
  }
}

export interface ListaPacientesResponse {
  success: boolean
  message: string
  data: {
    personas: PacienteHistorial[] // Cambiado de 'pacientes' a 'personas'
    total: number
    estadisticas: {
      externos_sin_registro: number
      pacientes_sin_historial: number
      pacientes_con_historial: number
    }
    medico: {
      id_medico: number
      nombres: string
      apellidos: string
    }
  }
}

export interface HistorialResponse {
  success: boolean
  message: string
  data: {
    historial: HistorialClinico
  }
}

export interface CrearHistorialResponse {
  success: boolean
  message: string
  data: {
    historial: HistorialClinico
  }
}

export interface MiHistorialResponse {
  success: boolean
  message: string
  data: {
    tiene_historial: boolean
    mensaje?: string
    historial?: HistorialClinico
  }
}

// Servicio
class HistorialClinicoService {
  /**
   * Obtener lista de pacientes atendidos con estado de historial
   */
  async obtenerPacientes(busqueda?: string): Promise<ListaPacientesResponse['data']> {
    const params = new URLSearchParams()
    if (busqueda) params.append('busqueda', busqueda)

    const response = await apiClient.get<ListaPacientesResponse>(`/clinica/historiales?${params}`)
    return response.data.data
  }

  /**
   * Obtener historial clínico específico
   */
  async obtenerHistorial(id: number): Promise<HistorialResponse['data']> {
    const response = await apiClient.get<HistorialResponse>(`/clinica/historiales/${id}`)
    return response.data.data
  }

  /**
   * Crear nuevo historial clínico (paciente existente o usuario externo)
   */
  async crearHistorial(data: CrearHistorialData): Promise<CrearHistorialResponse['data']> {
    const response = await apiClient.post<CrearHistorialResponse>('/clinica/historiales', data)
    return response.data.data
  }

  /**
   * Actualizar historial clínico
   */
  async actualizarHistorial(id: number, data: Partial<CrearHistorialData>): Promise<HistorialResponse['data']> {
    const response = await apiClient.put<HistorialResponse>(`/clinica/historiales/${id}`, data)
    return response.data.data
  }

  /**
   * Obtener mi historial clínico (para pacientes)
   */
  async obtenerMiHistorial(): Promise<MiHistorialResponse['data']> {
    const response = await apiClient.get<MiHistorialResponse>('/clinica/mi-historial')
    return response.data.data
  }

  /**
   * Agregar detalle/consulta al historial
   */
  async agregarDetalle(idHistorial: number, data: {
    fecha: string
    diagnostico?: string
    procedimiento_realizado?: string
    notas_medicas?: string
    id_cita?: number
  }): Promise<{ detalle: DetalleHistorial }> {
    const response = await apiClient.post(`/clinica/historiales/${idHistorial}/detalles`, data)
    return response.data.data
  }

  /**
   * Actualizar detalle/consulta
   */
  async actualizarDetalle(idDetalle: number, data: {
    fecha?: string
    diagnostico?: string
    procedimiento_realizado?: string
    notas_medicas?: string
  }): Promise<{ detalle: DetalleHistorial }> {
    const response = await apiClient.put(`/clinica/historiales/detalles/${idDetalle}`, data)
    return response.data.data
  }

  /**
   * Eliminar detalle/consulta
   */
  async eliminarDetalle(idDetalle: number): Promise<void> {
    await apiClient.delete(`/clinica/historiales/detalles/${idDetalle}`)
  }

  /**
   * Agregar tratamiento al historial
   */
  async agregarTratamiento(idHistorial: number, data: {
    id_tratamiento: number
    descripcion?: string
    fecha_inicio?: string
    fecha_fin?: string
    estado: 'sugerido' | 'en_curso' | 'completado' | 'cancelado'
    precio?: number
    id_detalle_historial?: number
  }): Promise<{ tratamiento: TratamientoHistorial }> {
    const response = await apiClient.post(`/clinica/historiales/${idHistorial}/tratamientos`, data)
    return response.data.data
  }

  /**
   * Actualizar tratamiento
   */
  async actualizarTratamiento(idTratamiento: number, data: {
    descripcion?: string
    fecha_inicio?: string
    fecha_fin?: string
    estado?: 'sugerido' | 'en_curso' | 'completado' | 'cancelado'
    precio?: number
  }): Promise<{ tratamiento: TratamientoHistorial }> {
    const response = await apiClient.put(`/clinica/historiales/tratamientos/${idTratamiento}`, data)
    return response.data.data
  }

  /**
   * Eliminar tratamiento
   */
  async eliminarTratamiento(idTratamiento: number): Promise<void> {
    await apiClient.delete(`/clinica/historiales/tratamientos/${idTratamiento}`)
  }

  /**
   * Agregar prescripción al historial
   */
  async agregarPrescripcion(idHistorial: number, data: {
    medicamento: string
    dosis?: string
    frecuencia?: string
    duracion?: string
    indicaciones?: string
    fecha_prescripcion: string
  }): Promise<{ prescripcion: Prescripcion }> {
    const response = await apiClient.post(`/clinica/historiales/${idHistorial}/prescripciones`, data)
    return response.data.data
  }

  /**
   * Actualizar prescripción
   */
  async actualizarPrescripcion(idPrescripcion: number, data: {
    medicamento?: string
    dosis?: string
    frecuencia?: string
    duracion?: string
    indicaciones?: string
    fecha_prescripcion?: string
  }): Promise<{ prescripcion: Prescripcion }> {
    const response = await apiClient.put(`/clinica/historiales/prescripciones/${idPrescripcion}`, data)
    return response.data.data
  }

  /**
   * Eliminar prescripción
   */
  async eliminarPrescripcion(idPrescripcion: number): Promise<void> {
    await apiClient.delete(`/clinica/historiales/prescripciones/${idPrescripcion}`)
  }

  /**
   * Agregar seguimiento a un tratamiento
   */
  async agregarSeguimiento(idHistorial: number, idTratamiento: number, data: {
    fecha_registro: string
    descripcion: string
    duracion_restante?: number | null
  }): Promise<any> {
    const response = await apiClient.post(
      `/clinica/historiales/${idHistorial}/tratamientos/${idTratamiento}/seguimientos`,
      data
    )
    return response.data
  }

  /**
   * Actualizar seguimiento de tratamiento
   */
  async actualizarSeguimiento(
    idHistorial: number,
    idTratamiento: number,
    idSeguimiento: number,
    data: {
      fecha_registro?: string
      descripcion?: string
      duracion_restante?: number | null
    }
  ): Promise<any> {
    const response = await apiClient.put(
      `/clinica/historiales/${idHistorial}/tratamientos/${idTratamiento}/seguimientos/${idSeguimiento}`,
      data
    )
    return response.data
  }

  /**
   * Eliminar seguimiento de tratamiento
   */
  async eliminarSeguimiento(idHistorial: number, idTratamiento: number, idSeguimiento: number): Promise<void> {
    await apiClient.delete(
      `/clinica/historiales/${idHistorial}/tratamientos/${idTratamiento}/seguimientos/${idSeguimiento}`
    )
  }

  /**
   * Obtener catálogo de tratamientos disponibles
   */
  async obtenerCatalogoTratamientos(): Promise<{
    tratamientos: Array<{
      id_tratamiento: number
      nombre: string
      descripcion?: string
      precio?: number
      categoria?: string
    }>
  }> {
    const response = await apiClient.get('/clinica/catalogo-tratamientos')
    return response.data.data
  }
}

// Helper para crear historial clínico con datos completos
export async function crearHistorialClinico(data: CrearHistorialData): Promise<CrearHistorialResponse['data']> {
  const service = new HistorialClinicoService()
  return service.crearHistorial(data)
}

export default new HistorialClinicoService()
