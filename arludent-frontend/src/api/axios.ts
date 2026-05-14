import axios from 'axios'
import { useAuthStore } from '@/stores/authStore'
import { useNotification } from '@/composables/useNotification'
import router from '@/router'

// Crear instancia de Axios
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 15000
})

// Interceptor de solicitudes - Agregar token JWT
apiClient.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    const token = authStore.token

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor de respuestas - Manejar errores y renovar token
apiClient.interceptors.response.use(
  (response) => {
    return response
  },
  async (error) => {
    const { showError } = useNotification()
    const authStore = useAuthStore()
    const originalRequest = error.config

    // Si el error es 401 y no es un intento de renovación
    // Y NO es la llamada de refresh en sí misma
    // Y el usuario está autenticado (tiene token)
    if (
      error.response?.status === 401 &&
      !originalRequest._retry &&
      !originalRequest.url?.includes('/auth/refresh') &&
      !originalRequest.url?.includes('/auth/login') &&
      authStore.token && // Solo intentar renovar si hay token
      authStore.isAuthenticated // Solo si está autenticado
    ) {
      originalRequest._retry = true

      try {
        // Intentar renovar el token
        await authStore.refreshToken()

        // Reintentar la solicitud original con el nuevo token
        const token = authStore.token
        originalRequest.headers.Authorization = `Bearer ${token}`
        return apiClient(originalRequest)
      } catch (refreshError) {
        // Si falla la renovación, cerrar sesión
        authStore.logout()
        router.push('/login')
        showError('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.')
        return Promise.reject(refreshError)
      }
    }

    // Si es un error 401 en login o refresh, solo rechazar (sin loop)
    if (
      error.response?.status === 401 &&
      (originalRequest.url?.includes('/auth/refresh') || originalRequest.url?.includes('/auth/login'))
    ) {
      // No llamar logout aquí para login, solo para refresh
      if (originalRequest.url?.includes('/auth/refresh')) {
        authStore.logout()
        router.push('/login')
        showError('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.')
      }
      return Promise.reject(error)
    }

    // Manejo de errores específicos
    if (error.response) {
      switch (error.response.status) {
        case 400:
          showError(error.response.data?.message || 'Solicitud incorrecta')
          break
        case 403:
          showError('No tienes permisos para realizar esta acción')
          break
        case 404:
          showError('Recurso no encontrado')
          break
        case 422:
          // Errores de validación - se manejan en los componentes
          break
        case 429:
          showError('Demasiadas solicitudes. Por favor, intenta más tarde.')
          break
        case 500:
          showError('Error en el servidor. Por favor, intenta más tarde.')
          break
        case 503:
          showError('Servicio no disponible. Por favor, intenta más tarde.')
          break
        default:
          showError(error.response.data?.message || 'Ha ocurrido un error inesperado')
      }
    } else if (error.request) {
      showError('No se pudo conectar con el servidor. Verifica tu conexión a Internet.')
    } else {
      showError('Error al procesar la solicitud')
    }

    return Promise.reject(error)
  }
)

export default apiClient
