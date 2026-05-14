import { useAuthStore } from '@/stores/authStore'
import { useNotification } from './useNotification'
import QRCode from 'qrcode'

/**
 * Composable para gestión de MFA (Autenticación de Dos Factores)
 */
export const useMFA = () => {
  const authStore = useAuthStore()
  const { showSuccess, showError, showLoading, closeLoading, showHtml } = useNotification()

  /**
   * Habilitar MFA y obtener código QR
   */
  const enableMFA = async () => {
    try {
      showLoading('Generando código QR...')

      const result = await authStore.enableMFA()
      const qrData = result.data

      closeLoading()

      // Generar imagen QR (el backend ya devuelve el SVG en qr_code)
      // Si es SVG, convertirlo a data URL
      let qrImageUrl = qrData.qr_code

      // Si qr_code es una URL de Google Charts o similar, usarla directamente
      // Si no, intentar generar desde el secret
      if (qrData.secret && !qrImageUrl.startsWith('http') && !qrImageUrl.startsWith('data:')) {
        // Es un código otpauth://, generar QR
        const qrUrl = `otpauth://totp/${encodeURIComponent('Arludent')}:${encodeURIComponent(authStore.user?.correo || '')}?secret=${qrData.secret}&issuer=${encodeURIComponent('Arludent')}`
        qrImageUrl = await QRCode.toDataURL(qrUrl)
      }

      // Mostrar QR y código secreto
      await showHtml(
        'Configurar Autenticación de Dos Factores',
        `
          <div class="text-left">
            <p class="mb-4">Escanea este código QR con tu aplicación de autenticación (Google Authenticator, Authy, etc.)</p>
            <div class="flex justify-center mb-4">
              <img src="${qrImageUrl}" alt="QR Code" class="w-48 h-48" />
            </div>
            <p class="mb-2"><strong>O ingresa este código manualmente:</strong></p>
            <p class="bg-gray-100 p-3 rounded text-center font-mono text-sm mb-4">${qrData.secret}</p>
            <p class="text-sm text-gray-600">Después de escanear, ingresa el código de 6 dígitos para confirmar.</p>
          </div>
        `,
        'info'
      )

      return { success: true, data: qrData }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Error al habilitar MFA'
      showError(errorMessage)

      throw error
    }
  }

  /**
   * Confirmar habilitación de MFA
   */
  const confirmMFA = async (codigo: string) => {
    try {
      showLoading('Confirmando MFA...')

      const result = await authStore.confirmMFA(codigo)

      closeLoading()

      // Mostrar códigos de respaldo si los hay
      if (result.data.backup_codes) {
        await showHtml(
          'MFA Habilitado',
          `
            <div class="text-left">
              <p class="mb-4 text-green-600 font-semibold">✓ Autenticación de dos factores habilitada correctamente</p>
              <p class="mb-2"><strong>Códigos de respaldo:</strong></p>
              <p class="text-sm text-gray-600 mb-3">Guarda estos códigos en un lugar seguro. Puedes usar cada uno solo una vez si pierdes acceso a tu dispositivo.</p>
              <div class="bg-gray-100 p-3 rounded font-mono text-sm">
                ${result.data.backup_codes.map((code: string) => `<div>${code}</div>`).join('')}
              </div>
            </div>
          `,
          'success'
        )
      } else {
        showSuccess('Autenticación de dos factores habilitada correctamente')
      }

      return { success: true, data: result.data }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Código incorrecto'
      showError(errorMessage)

      throw error
    }
  }

  /**
   * Verificar código MFA durante el login
   */
  const verifyMFA = async (codigo: string) => {
    try {
      showLoading('Verificando código...')

      await authStore.verifyMFA(codigo)

      closeLoading()
      showSuccess('Código verificado correctamente')

      return { success: true }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Código incorrecto'
      showError(errorMessage)

      throw error
    }
  }

  /**
   * Deshabilitar MFA
   */
  const disableMFA = async (codigo: string) => {
    try {
      showLoading('Deshabilitando MFA...')

      await authStore.disableMFA(codigo)

      closeLoading()
      showSuccess('Autenticación de dos factores deshabilitada')

      return { success: true }
    } catch (error: any) {
      closeLoading()

      const errorMessage = error.response?.data?.message || 'Error al deshabilitar MFA'
      showError(errorMessage)

      throw error
    }
  }

  return {
    enableMFA,
    confirmMFA,
    verifyMFA,
    disableMFA
  }
}
