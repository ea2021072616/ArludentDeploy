import apiClient from './axios'

/**
 * Servicio de clínica
 * Maneja peticiones relacionadas con pacientes, citas e historial
 */
const clinicService = {
  // ============ PACIENTES ============

  /**
   * Obtener lista de pacientes
   */
  async getPatients(params?: any) {
    const response = await apiClient.get('/pacientes', { params })
    return response.data
  },

  /**
   * Obtener paciente por ID
   */
  async getPatientById(id: number) {
    const response = await apiClient.get(`/pacientes/${id}`)
    return response.data
  },

  /**
   * Crear nuevo paciente
   */
  async createPatient(data: any) {
    const response = await apiClient.post('/pacientes', data)
    return response.data
  },

  /**
   * Actualizar paciente
   */
  async updatePatient(id: number, data: any) {
    const response = await apiClient.put(`/pacientes/${id}`, data)
    return response.data
  },

  /**
   * Eliminar paciente
   */
  async deletePatient(id: number) {
    const response = await apiClient.delete(`/pacientes/${id}`)
    return response.data
  },

  // ============ CITAS ============

  /**
   * Obtener lista de citas
   */
  async getAppointments(params?: any) {
    const response = await apiClient.get('/citas', { params })
    return response.data
  },

  /**
   * Obtener cita por ID
   */
  async getAppointmentById(id: number) {
    const response = await apiClient.get(`/citas/${id}`)
    return response.data
  },

  /**
   * Crear nueva cita
   */
  async createAppointment(data: any) {
    const response = await apiClient.post('/citas', data)
    return response.data
  },

  /**
   * Actualizar cita
   */
  async updateAppointment(id: number, data: any) {
    const response = await apiClient.put(`/citas/${id}`, data)
    return response.data
  },

  /**
   * Cancelar cita
   */
  async cancelAppointment(id: number) {
    const response = await apiClient.delete(`/citas/${id}`)
    return response.data
  },

  // ============ HISTORIAL CLÍNICO ============

  /**
   * Obtener historial de un paciente
   */
  async getPatientHistory(patientId: number) {
    const response = await apiClient.get(`/pacientes/${patientId}/historial`)
    return response.data
  },

  /**
   * Crear entrada en historial
   */
  async createHistoryEntry(data: any) {
    const response = await apiClient.post('/historial', data)
    return response.data
  },

  /**
   * Actualizar entrada en historial
   */
  async updateHistoryEntry(id: number, data: any) {
    const response = await apiClient.put(`/historial/${id}`, data)
    return response.data
  },

  // ============ MÉDICOS ============

  /**
   * Obtener lista de médicos
   */
  async getDoctors(params?: any) {
    const response = await apiClient.get('/medicos', { params })
    return response.data
  },

  /**
   * Obtener disponibilidad de un médico
   */
  async getDoctorAvailability(doctorId: number, params?: any) {
    const response = await apiClient.get(`/medicos/${doctorId}/disponibilidad`, { params })
    return response.data
  },

  // ============ TRATAMIENTOS ============

  /**
   * Obtener lista de tratamientos
   */
  async getTreatments(params?: any) {
    const response = await apiClient.get('/tratamientos', { params })
    return response.data
  },

  /**
   * Obtener tratamiento por ID
   */
  async getTreatmentById(id: number) {
    const response = await apiClient.get(`/tratamientos/${id}`)
    return response.data
  },

  // ============ PAGOS ============

  /**
   * Obtener lista de pagos
   */
  async getPayments(params?: any) {
    const response = await apiClient.get('/pagos', { params })
    return response.data
  },

  /**
   * Crear nuevo pago
   */
  async createPayment(data: any) {
    const response = await apiClient.post('/pagos', data)
    return response.data
  }
}

export default clinicService
