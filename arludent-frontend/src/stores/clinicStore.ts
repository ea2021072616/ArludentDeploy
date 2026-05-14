import { defineStore } from 'pinia'
import { ref } from 'vue'
import clinicService from '@/api/clinicService'

export const useClinicStore = defineStore('clinic', () => {
  // Estado - Pacientes
  const patients = ref<any[]>([])
  const currentPatient = ref<any>(null)

  // Estado - Citas
  const appointments = ref<any[]>([])
  const currentAppointment = ref<any>(null)

  // Estado - Médicos
  const doctors = ref<any[]>([])

  // Estado - Tratamientos
  const treatments = ref<any[]>([])

  // Estado - Loading
  const isLoading = ref(false)

  // ============ PACIENTES ============

  /**
   * Obtener lista de pacientes
   */
  const fetchPatients = async (params?: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.getPatients(params)
      patients.value = response.data
      return response.data
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Obtener paciente por ID
   */
  const fetchPatientById = async (id: number) => {
    try {
      isLoading.value = true
      const response = await clinicService.getPatientById(id)
      currentPatient.value = response.data
      return response.data
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Crear nuevo paciente
   */
  const createPatient = async (data: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.createPatient(data)
      patients.value.push(response.data)
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Actualizar paciente
   */
  const updatePatient = async (id: number, data: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.updatePatient(id, data)

      // Actualizar en la lista
      const index = patients.value.findIndex(p => p.id_paciente === id)
      if (index !== -1) {
        patients.value[index] = response.data
      }

      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Eliminar paciente
   */
  const deletePatient = async (id: number) => {
    try {
      isLoading.value = true
      await clinicService.deletePatient(id)

      // Eliminar de la lista
      patients.value = patients.value.filter(p => p.id_paciente !== id)

      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  // ============ CITAS ============

  /**
   * Obtener lista de citas
   */
  const fetchAppointments = async (params?: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.getAppointments(params)
      appointments.value = response.data
      return response.data
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Obtener cita por ID
   */
  const fetchAppointmentById = async (id: number) => {
    try {
      isLoading.value = true
      const response = await clinicService.getAppointmentById(id)
      currentAppointment.value = response.data
      return response.data
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Crear nueva cita
   */
  const createAppointment = async (data: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.createAppointment(data)
      appointments.value.push(response.data)
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Actualizar cita
   */
  const updateAppointment = async (id: number, data: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.updateAppointment(id, data)

      // Actualizar en la lista
      const index = appointments.value.findIndex(a => a.id_cita === id)
      if (index !== -1) {
        appointments.value[index] = response.data
      }

      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Cancelar cita
   */
  const cancelAppointment = async (id: number) => {
    try {
      isLoading.value = true
      await clinicService.cancelAppointment(id)

      // Eliminar de la lista
      appointments.value = appointments.value.filter(a => a.id_cita !== id)

      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  // ============ MÉDICOS ============

  /**
   * Obtener lista de médicos
   */
  const fetchDoctors = async (params?: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.getDoctors(params)
      doctors.value = response.data
      return response.data
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Obtener disponibilidad de un médico
   */
  const fetchDoctorAvailability = async (doctorId: number, params?: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.getDoctorAvailability(doctorId, params)
      return response.data
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  // ============ TRATAMIENTOS ============

  /**
   * Obtener lista de tratamientos
   */
  const fetchTreatments = async (params?: any) => {
    try {
      isLoading.value = true
      const response = await clinicService.getTreatments(params)
      treatments.value = response.data
      return response.data
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Limpiar datos del store
   */
  const clearData = () => {
    patients.value = []
    currentPatient.value = null
    appointments.value = []
    currentAppointment.value = null
    doctors.value = []
    treatments.value = []
  }

  return {
    // Estado
    patients,
    currentPatient,
    appointments,
    currentAppointment,
    doctors,
    treatments,
    isLoading,

    // Acciones - Pacientes
    fetchPatients,
    fetchPatientById,
    createPatient,
    updatePatient,
    deletePatient,

    // Acciones - Citas
    fetchAppointments,
    fetchAppointmentById,
    createAppointment,
    updateAppointment,
    cancelAppointment,

    // Acciones - Médicos
    fetchDoctors,
    fetchDoctorAvailability,

    // Acciones - Tratamientos
    fetchTreatments,

    // Utilidades
    clearData
  }
})
