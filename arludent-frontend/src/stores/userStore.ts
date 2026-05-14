import { defineStore } from 'pinia'
import { ref } from 'vue'
import userService, { type UserProfile, type UpdateProfileData } from '@/api/userService'

export const useUserStore = defineStore('user', () => {
  // Estado
  const profile = ref<UserProfile | null>(null)
  const isLoading = ref(false)

  /**
   * Obtener perfil del usuario
   */
  const fetchProfile = async () => {
    try {
      isLoading.value = true
      const response = await userService.getProfile()
      profile.value = response.data
      return response.data
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Actualizar perfil del usuario
   */
  const updateProfile = async (data: UpdateProfileData) => {
    try {
      isLoading.value = true
      const response = await userService.updateProfile(data)
      profile.value = { ...profile.value, ...response.data }
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Limpiar datos del perfil
   */
  const clearProfile = () => {
    profile.value = null
  }

  return {
    // Estado
    profile,
    isLoading,

    // Acciones
    fetchProfile,
    updateProfile,
    clearProfile
  }
})
