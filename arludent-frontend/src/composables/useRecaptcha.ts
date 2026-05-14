import { ref, onMounted, onBeforeUnmount } from 'vue'

interface RecaptchaConfig {
  siteKey: string
  theme?: 'light' | 'dark'
  size?: 'normal' | 'compact'
}

declare global {
  interface Window {
    grecaptcha: any
    onRecaptchaLoad?: () => void
  }
}

export function useRecaptcha(config?: RecaptchaConfig) {
  const isLoaded = ref(false)
  const widgetId = ref<number | null>(null)
  const token = ref<string | null>(null)

  const siteKey = config?.siteKey || import.meta.env.VITE_RECAPTCHA_SITE_KEY
  const theme = config?.theme || 'light'
  const size = config?.size || 'normal'

  /**
   * Cargar el script de reCAPTCHA
   */
  const loadRecaptchaScript = (): Promise<void> => {
    return new Promise((resolve, reject) => {
      if (window.grecaptcha) {
        isLoaded.value = true
        resolve()
        return
      }

      // Crear script tag
      const script = document.createElement('script')
      script.src = 'https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit'
      script.async = true
      script.defer = true

      // Callback cuando se carga
      window.onRecaptchaLoad = () => {
        isLoaded.value = true
        resolve()
      }

      script.onerror = () => {
        reject(new Error('Failed to load reCAPTCHA script'))
      }

      document.head.appendChild(script)
    })
  }

  /**
   * Renderizar el widget de reCAPTCHA
   */
  const render = (containerId: string): Promise<number> => {
    return new Promise((resolve, reject) => {
      if (!window.grecaptcha) {
        reject(new Error('reCAPTCHA not loaded'))
        return
      }

      try {
        const id = window.grecaptcha.render(containerId, {
          sitekey: siteKey,
          theme,
          size,
          callback: (responseToken: string) => {
            token.value = responseToken
          },
          'expired-callback': () => {
            token.value = null
          },
          'error-callback': () => {
            token.value = null
          }
        })

        widgetId.value = id
        resolve(id)
      } catch (error) {
        reject(error)
      }
    })
  }

  /**
   * Obtener el token de respuesta
   */
  const getResponse = (): string | null => {
    if (!window.grecaptcha || widgetId.value === null) {
      return null
    }

    return window.grecaptcha.getResponse(widgetId.value)
  }

  /**
   * Resetear el widget
   */
  const reset = () => {
    if (window.grecaptcha && widgetId.value !== null) {
      window.grecaptcha.reset(widgetId.value)
      token.value = null
    }
  }

  /**
   * Ejecutar el reCAPTCHA
   */
  const execute = (): Promise<string> => {
    return new Promise((resolve, reject) => {
      if (!window.grecaptcha || widgetId.value === null) {
        reject(new Error('reCAPTCHA not initialized'))
        return
      }

      try {
        window.grecaptcha.execute(widgetId.value)
        // El token se obtendrá a través del callback
        const checkToken = setInterval(() => {
          const response = getResponse()
          if (response) {
            clearInterval(checkToken)
            resolve(response)
          }
        }, 100)

        // Timeout después de 10 segundos
        setTimeout(() => {
          clearInterval(checkToken)
          reject(new Error('reCAPTCHA execution timeout'))
        }, 10000)
      } catch (error) {
        reject(error)
      }
    })
  }

  return {
    isLoaded,
    widgetId,
    token,
    loadRecaptchaScript,
    render,
    getResponse,
    reset,
    execute
  }
}
