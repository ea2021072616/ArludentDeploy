import { useAuthStore } from '@/stores/authStore'
import { useNotification } from './useNotification'
import router from '@/router'

/**
 * Composable para gestión de autenticación
 * Proporciona métodos reutilizables para auth
 */
export const useAuth = () => {
  const authStore = useAuthStore()
  const { showSuccess, showError, showLoading, closeLoading } = useNotification()

  /**
   * Iniciar sesión
   */
  const login = async (correo: string, password: string, recaptchaToken?: string) => {
    try {
      showLoading('Iniciando sesión...')

      const result = await authStore.login({ correo, password, recaptcha_token: recaptchaToken })

      closeLoading()

      if (result.requiresMFA) {
        router.push('/mfa')
        return { requiresMFA: true }
      }

      showSuccess('Sesión iniciada correctamente')
      // Asegurar que el perfil (y roles) estén cargados antes de redirigir
      try {
        await authStore.fetchUser()
      } catch (e) {
        // Si falla, continuamos con los datos de login
        console.warn('No se pudo refrescar el perfil tras login. Continuando con datos del login.')
      }

      // Redirigir al dashboard correspondiente según rol
      if (authStore.hasRole('admin')) {
        router.push('/admin/dashboard')
      } else if (authStore.hasRole('medico')) {
        router.push('/medico/dashboard')
      } else {
        router.push('/dashboard')
      }

      return { success: true }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Error al iniciar sesión'
      showError(errorMessage)

      throw error
    }
  }

  /**
   * Registrar usuario
   */
  const register = async (data: any) => {
    try {
      showLoading('Registrando usuario...')

      await authStore.register(data)

      closeLoading()
      showSuccess('Registro exitoso. Por favor, verifica tu correo electrónico.')

      router.push('/login')

      return { success: true }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Error al registrar usuario'
      showError(errorMessage)

      throw error
    }
  }

  /**
   * Cerrar sesión
   */
  const logout = async () => {
    try {
      await authStore.logout()
      showSuccess('Sesión cerrada correctamente')
    } catch (error) {
      console.error('Error al cerrar sesión:', error)
    }
  }

  /**
   * Verificar correo electrónico
   */
  const verifyEmail = async (token: string) => {
    try {
      showLoading('Verificando correo...')

      await authStore.verifyEmail(token)

      closeLoading()
      showSuccess('Correo verificado correctamente. Ya puedes iniciar sesión.')

      router.push('/login')

      return { success: true }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Error al verificar correo'
      showError(errorMessage)

      throw error
    }
  }

  /**
   * Solicitar recuperación de contraseña
   */
  const requestPasswordReset = async (correo: string) => {
    try {
      showLoading('Enviando correo...')

      await authStore.forgotPassword(correo)

      closeLoading()
      showSuccess('Se ha enviado un correo con instrucciones para restablecer tu contraseña.')

      return { success: true }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Error al solicitar recuperación'
      showError(errorMessage)

      throw error
    }
  }

  /**
   * Restablecer contraseña
   */
  const resetPassword = async (data: any) => {
    try {
      showLoading('Restableciendo contraseña...')

      await authStore.resetPassword(data)

      closeLoading()
      showSuccess('Contraseña restablecida correctamente. Ya puedes iniciar sesión.')

      router.push('/login')

      return { success: true }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Error al restablecer contraseña'
      showError(errorMessage)

      throw error
    }
  }

  /**
   * Verificar si el usuario tiene un rol específico
   */
  const hasRole = (roleName: string): boolean => {
    return authStore.hasRole(roleName)
  }

  /**
   * Verificar si el usuario está autenticado
   */
  const isAuthenticated = (): boolean => {
    return authStore.isAuthenticated
  }

  /**
   * Obtener usuario actual
   */
  const currentUser = () => {
    return authStore.user
  }

  return {
    login,
    register,
    logout,
    verifyEmail,
    requestPasswordReset,
    resetPassword,
    hasRole,
    isAuthenticated,
    currentUser
  }
}
