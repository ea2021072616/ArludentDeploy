import apiClient from './axios'

export interface LoginCredentials {
  correo: string
  password: string
  recaptcha_token?: string
}

export interface RegisterData {
  username: string
  correo: string
  password: string
  password_confirmation: string
  telefono?: string
}

export interface ResetPasswordData {
  token: string
  correo: string
  password: string
  password_confirmation: string
}

export interface ChangePasswordData {
  password_actual: string
  password: string
  password_confirmation: string
}

/**
 * Servicio de autenticación
 * Maneja todas las peticiones relacionadas con auth
 */
const authService = {
  /**
   * Iniciar sesión
   */
  async login(credentials: LoginCredentials) {
    const response = await apiClient.post('/auth/login', credentials)
    return response.data
  },

  /**
   * Registrar nuevo usuario
   */
  async register(data: RegisterData) {
    const response = await apiClient.post('/auth/registro', data, {
      timeout: 60000 // 60s: el envío de correo de verificación tarda ~20-30s
    })
    return response.data
  },

  /**
   * Cerrar sesión
   */
  async logout() {
    const response = await apiClient.post('/auth/logout')
    return response.data
  },

  /**
   * Renovar token JWT
   */
  async refreshToken() {
    const response = await apiClient.post('/auth/refresh')
    return response.data
  },

  /**
   * Obtener datos del usuario actual
   */
  async me() {
    const response = await apiClient.get('/auth/me')
    return response.data
  },

  /**
   * Verificar correo electrónico
   */
  async verifyEmail(token: string) {
    const response = await apiClient.get('/auth/verificar-correo', {
      params: { token }
    })
    return response.data
  },

  /**
   * Reenviar correo de verificación
   */
  async resendVerification(correo: string) {
    const response = await apiClient.post('/auth/reenviar-verificacion', { correo })
    return response.data
  },

  /**
   * Solicitar recuperación de contraseña
   */
  async forgotPassword(correo: string) {
    const response = await apiClient.post('/auth/recuperar-password', { correo }, {
      timeout: 60000 // 60s: el envío de correo de recuperación tarda ~20-30s
    })
    return response.data
  },

  /**
   * Restablecer contraseña
   */
  async resetPassword(data: ResetPasswordData) {
    const response = await apiClient.post('/auth/restablecer-password', data)
    return response.data
  },

  /**
   * Cambiar contraseña (usuario autenticado)
   */
  async changePassword(data: ChangePasswordData) {
    const response = await apiClient.post('/auth/cambiar-password', data)
    return response.data
  },

  // ============ MFA (Autenticación de Dos Factores) ============

  /**
   * Generar MFA - Obtener código QR
   */
  async enableMFA() {
    const response = await apiClient.post('/auth/mfa/generar')
    return response.data
  },

  /**
   * Activar MFA después de verificar código
   */
  async confirmMFA(codigo: string) {
    const response = await apiClient.post('/auth/mfa/activar', { codigo })
    return response.data
  },

  /**
   * Verificar código MFA durante el login
   */
  async verifyMFA(codigo: string, userId?: number) {
    const user_id = userId || localStorage.getItem('mfa_user_id')
    const response = await apiClient.post('/auth/mfa/verificar-login', {
      codigo,
      user_id: user_id ? parseInt(user_id.toString()) : undefined
    })
    // Limpiar user_id guardado
    localStorage.removeItem('mfa_user_id')
    return response.data
  },

  /**
   * Deshabilitar MFA
   */
  async disableMFA(codigo: string) {
    const response = await apiClient.post('/auth/mfa/desactivar', { codigo })
    return response.data
  },

  /**
   * Obtener códigos de respaldo
   */
  async getBackupCodes() {
    const response = await apiClient.get('/auth/mfa/codigos-respaldo')
    return response.data
  },

  /**
   * Regenerar códigos de respaldo
   */
  async regenerateBackupCodes() {
    const response = await apiClient.post('/auth/mfa/regenerar-codigos')
    return response.data
  }
}

export default authService
