import { ref } from 'vue'

/**
 * Composable para validación de formularios
 */
export const useValidation = () => {
  const errors = ref<Record<string, string>>({})

  /**
   * Limpiar errores
   */
  const clearErrors = () => {
    errors.value = {}
  }

  /**
   * Limpiar error específico
   */
  const clearError = (field: string) => {
    delete errors.value[field]
  }

  /**
   * Establecer error
   */
  const setError = (field: string, message: string) => {
    errors.value[field] = message
  }

  /**
   * Establecer errores desde respuesta del servidor
   */
  const setErrors = (serverErrors: Record<string, string[]>) => {
    clearErrors()

    Object.keys(serverErrors).forEach(field => {
      const fieldErrors = serverErrors[field]
      if (fieldErrors && fieldErrors.length > 0 && fieldErrors[0]) {
        errors.value[field] = fieldErrors[0] // Tomar el primer error
      }
    })
  }

  /**
   * Validar campo requerido
   */
  const validateRequired = (value: any, fieldName: string): boolean => {
    if (!value || (typeof value === 'string' && value.trim() === '')) {
      setError(fieldName, 'Este campo es obligatorio')
      return false
    }
    clearError(fieldName)
    return true
  }

  /**
   * Validar email
   */
  const validateEmail = (email: string, fieldName: string = 'correo'): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    if (!email || email.trim() === '') {
      setError(fieldName, 'El correo es obligatorio')
      return false
    }

    if (!emailRegex.test(email)) {
      setError(fieldName, 'El correo no es válido')
      return false
    }

    clearError(fieldName)
    return true
  }

  /**
   * Validar contraseña
   */
  const validatePassword = (password: string, fieldName: string = 'password'): boolean => {
    if (!password || password.trim() === '') {
      setError(fieldName, 'La contraseña es obligatoria')
      return false
    }

    if (password.length < 8) {
      setError(fieldName, 'La contraseña debe tener al menos 8 caracteres')
      return false
    }

    // Validar que tenga al menos una mayúscula, una minúscula y un número
    const hasUpperCase = /[A-Z]/.test(password)
    const hasLowerCase = /[a-z]/.test(password)
    const hasNumber = /\d/.test(password)

    if (!hasUpperCase || !hasLowerCase || !hasNumber) {
      setError(fieldName, 'La contraseña debe incluir mayúsculas, minúsculas y números')
      return false
    }

    clearError(fieldName)
    return true
  }

  /**
   * Validar confirmación de contraseña
   */
  const validatePasswordConfirmation = (
    password: string,
    confirmation: string,
    fieldName: string = 'password_confirmation'
  ): boolean => {
    if (!confirmation || confirmation.trim() === '') {
      setError(fieldName, 'Debes confirmar la contraseña')
      return false
    }

    if (password !== confirmation) {
      setError(fieldName, 'Las contraseñas no coinciden')
      return false
    }

    clearError(fieldName)
    return true
  }

  /**
   * Validar teléfono
   */
  const validatePhone = (phone: string, fieldName: string = 'telefono'): boolean => {
    if (!phone || phone.trim() === '') {
      // El teléfono es opcional en muchos casos
      clearError(fieldName)
      return true
    }

    const phoneRegex = /^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{4,10}$/

    if (!phoneRegex.test(phone)) {
      setError(fieldName, 'El teléfono no es válido')
      return false
    }

    clearError(fieldName)
    return true
  }

  /**
   * Validar username
   */
  const validateUsername = (username: string, fieldName: string = 'username'): boolean => {
    if (!username || username.trim() === '') {
      setError(fieldName, 'El nombre de usuario es obligatorio')
      return false
    }

    if (username.length < 3) {
      setError(fieldName, 'El nombre de usuario debe tener al menos 3 caracteres')
      return false
    }

    if (username.length > 50) {
      setError(fieldName, 'El nombre de usuario no puede exceder 50 caracteres')
      return false
    }

    // Solo letras, números, puntos y guiones bajos
    const usernameRegex = /^[a-zA-Z0-9._]+$/
    if (!usernameRegex.test(username)) {
      setError(fieldName, 'El nombre de usuario solo puede contener letras, números, puntos y guiones bajos')
      return false
    }

    clearError(fieldName)
    return true
  }

  /**
   * Validar código MFA (6 dígitos)
   */
  const validateMFACode = (code: string, fieldName: string = 'codigo'): boolean => {
    if (!code || code.trim() === '') {
      setError(fieldName, 'El código es obligatorio')
      return false
    }

    if (!/^\d{6}$/.test(code)) {
      setError(fieldName, 'El código debe tener 6 dígitos')
      return false
    }

    clearError(fieldName)
    return true
  }

  /**
   * Validar longitud mínima
   */
  const validateMinLength = (value: string, minLength: number, fieldName: string): boolean => {
    if (value.length < minLength) {
      setError(fieldName, `Debe tener al menos ${minLength} caracteres`)
      return false
    }

    clearError(fieldName)
    return true
  }

  /**
   * Validar longitud máxima
   */
  const validateMaxLength = (value: string, maxLength: number, fieldName: string): boolean => {
    if (value.length > maxLength) {
      setError(fieldName, `No puede exceder ${maxLength} caracteres`)
      return false
    }

    clearError(fieldName)
    return true
  }

  /**
   * Obtener error de un campo
   */
  const getError = (fieldName: string): string | undefined => {
    return errors.value[fieldName]
  }

  /**
   * Verificar si un campo tiene error
   */
  const hasError = (fieldName: string): boolean => {
    return !!errors.value[fieldName]
  }

  /**
   * Verificar si hay algún error
   */
  const hasErrors = (): boolean => {
    return Object.keys(errors.value).length > 0
  }

  return {
    errors,
    clearErrors,
    clearError,
    setError,
    setErrors,
    validateRequired,
    validateEmail,
    validatePassword,
    validatePasswordConfirmation,
    validatePhone,
    validateUsername,
    validateMFACode,
    validateMinLength,
    validateMaxLength,
    getError,
    hasError,
    hasErrors
  }
}
