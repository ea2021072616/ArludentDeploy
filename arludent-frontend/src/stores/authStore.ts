import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService, { type LoginCredentials, type RegisterData, type ResetPasswordData, type ChangePasswordData } from '@/api/authService'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  // Estado
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const user = ref<any>(null)
  const mfaRequired = ref(false)
  const mfaTempToken = ref<string | null>(null)
  const isLoading = ref(false)

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRoles = computed(() => user.value?.roles || [])
  const isMFAEnabled = computed(() => user.value?.mfa_enabled || false)
  const isEmailVerified = computed(() => user.value?.email_verified_at || user.value?.correo_verificado || false)

  /**
   * Verificar si el usuario tiene un rol específico
   */
  const hasRole = (roleName: string): boolean => {
    if (!roleName) return false
    const target = roleName.toLowerCase()
    return userRoles.value.some((role: any) => {
      // Aceptar múltiples claves posibles del backend
      const nameCandidate = (
        role?.nombre ??
        role?.nombre_rol ??
        role?.name ??
        role?.role ??
        (typeof role === 'string' ? role : undefined)
      )

      if (!nameCandidate || typeof nameCandidate !== 'string') return false
      return nameCandidate.toLowerCase() === target
    })
  }

  /**
   * Guardar token en localStorage y estado
   */
  const setToken = (newToken: string) => {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
  }

  /**
   * Limpiar token
   */
  const clearToken = () => {
    token.value = null
    localStorage.removeItem('auth_token')
  }

  /**
   * Iniciar sesión
   */
  const login = async (credentials: LoginCredentials) => {
    try {
      isLoading.value = true
      const response = await authService.login(credentials)

      // Si requiere MFA, guardamos información temporal
      if (response.data.requires_mfa) {
        mfaRequired.value = true
        // Guardar el user_id para la verificación MFA
        if (response.data.user_id) {
          localStorage.setItem('mfa_user_id', response.data.user_id.toString())
        }
        return { requiresMFA: true }
      }

      // Si no requiere MFA, guardamos el token y usuario
      setToken(response.data.token)
      user.value = response.data.user

      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Verificar código MFA
   */
  const verifyMFA = async (codigo: string) => {
    try {
      isLoading.value = true
      const response = await authService.verifyMFA(codigo)

      // Guardar token y usuario después de verificar MFA
      setToken(response.data.token)
      user.value = response.data.user
      mfaRequired.value = false
      mfaTempToken.value = null

      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Registrar nuevo usuario
   */
  const register = async (data: RegisterData) => {
    try {
      isLoading.value = true
      const response = await authService.register(data)
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Cerrar sesión
   */
  const logout = async () => {
    try {
      await authService.logout()
    } catch (error) {
      console.error('Error al cerrar sesión:', error)
    } finally {
      clearToken()
      user.value = null
      mfaRequired.value = false
      mfaTempToken.value = null
      router.push('/login')
    }
  }

  /**
   * Renovar token JWT
   */
  const refreshToken = async () => {
    try {
      const response = await authService.refreshToken()
      setToken(response.data.token)
      return response.data
    } catch (error) {
      clearToken()
      user.value = null
      throw error
    }
  }

  /**
   * Obtener perfil del usuario actual
   */
  const fetchUser = async () => {
    try {
      const response = await authService.me()
      user.value = response.data
      return response.data
    } catch (error) {
      clearToken()
      user.value = null
      throw error
    }
  }

  /**
   * Verificar correo electrónico
   */
  const verifyEmail = async (tokenEmail: string) => {
    try {
      isLoading.value = true
      const response = await authService.verifyEmail(tokenEmail)

      // Actualizar estado del usuario
      if (user.value) {
        user.value.correo_verificado = true
      }

      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Reenviar correo de verificación
   */
  const resendVerification = async (correo: string) => {
    try {
      isLoading.value = true
      const response = await authService.resendVerification(correo)
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Solicitar recuperación de contraseña
   */
  const forgotPassword = async (correo: string) => {
    try {
      isLoading.value = true
      const response = await authService.forgotPassword(correo)
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Restablecer contraseña
   */
  const resetPassword = async (data: ResetPasswordData) => {
    try {
      isLoading.value = true
      const response = await authService.resetPassword(data)
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Cambiar contraseña (usuario autenticado)
   */
  const changePassword = async (data: ChangePasswordData) => {
    try {
      isLoading.value = true
      const response = await authService.changePassword(data)
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Habilitar MFA
   */
  const enableMFA = async () => {
    try {
      isLoading.value = true
      const response = await authService.enableMFA()
      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Confirmar habilitación de MFA
   */
  const confirmMFA = async (codigo: string) => {
    try {
      isLoading.value = true
      const response = await authService.confirmMFA(codigo)

      // Actualizar estado del usuario
      if (user.value) {
        user.value.mfa_enabled = true
      }

      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Deshabilitar MFA
   */
  const disableMFA = async (codigo: string) => {
    try {
      isLoading.value = true
      const response = await authService.disableMFA(codigo)

      // Actualizar estado del usuario
      if (user.value) {
        user.value.mfa_enabled = false
      }

      return { success: true, data: response.data }
    } catch (error: any) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Inicializar store (verificar si hay sesión activa)
   */
  const initialize = async () => {
    if (token.value && !user.value) {
      try {
        await fetchUser()
      } catch (error) {
        clearToken()
      }
    }
  }

  return {
    // Estado
    token,
    user,
    mfaRequired,
    mfaTempToken,
    isLoading,

    // Getters
    isAuthenticated,
    userRoles,
    isMFAEnabled,
    isEmailVerified,

    // Acciones
    login,
    verifyMFA,
    register,
    logout,
    refreshToken,
    fetchUser,
    verifyEmail,
    resendVerification,
    forgotPassword,
    resetPassword,
    changePassword,
    enableMFA,
    confirmMFA,
    disableMFA,
    hasRole,
    initialize
  }
})
