import Swal, { type SweetAlertIcon } from 'sweetalert2'

/**
 * Composable para notificaciones con SweetAlert2
 * Centraliza todas las notificaciones de la aplicación
 */
export const useNotification = () => {
  /**
   * Mostrar notificación genérica
   */
  const show = (title: string, text: string, icon: SweetAlertIcon = 'info') => {
    return Swal.fire({
      title,
      text,
      icon,
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#2563eb'
    })
  }

  /**
   * Mostrar notificación de éxito
   */
  const showSuccess = (message: string, title: string = '¡Éxito!') => {
    return Swal.fire({
      title,
      text: message,
      icon: 'success',
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#0d9488',
      timer: 3000,
      timerProgressBar: true
    })
  }

  /**
   * Mostrar notificación de error
   */
  const showError = (message: string, title: string = 'Error') => {
    return Swal.fire({
      title,
      text: message,
      icon: 'error',
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#dc2626'
    })
  }

  /**
   * Mostrar notificación de advertencia
   */
  const showWarning = (message: string, title: string = 'Advertencia') => {
    return Swal.fire({
      title,
      text: message,
      icon: 'warning',
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#ea580c'
    })
  }

  /**
   * Mostrar notificación de información
   */
  const showInfo = (message: string, title: string = 'Información') => {
    return Swal.fire({
      title,
      text: message,
      icon: 'info',
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#2563eb'
    })
  }

  /**
   * Confirmar acción
   */
  const confirm = async (
    message: string,
    title: string = '¿Estás seguro?',
    confirmText: string = 'Sí, confirmar',
    cancelText: string = 'Cancelar'
  ) => {
    const result = await Swal.fire({
      title,
      text: message,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#6b7280',
      reverseButtons: true
    })

    return result.isConfirmed
  }

  /**
   * Confirmar eliminación
   */
  const confirmDelete = async (
    itemName: string = 'este elemento'
  ) => {
    const result = await Swal.fire({
      title: '¿Eliminar?',
      text: `¿Estás seguro de que deseas eliminar ${itemName}? Esta acción no se puede deshacer.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      reverseButtons: true
    })

    return result.isConfirmed
  }

  /**
   * Toast - Notificación pequeña
   */
  const toast = (message: string, icon: SweetAlertIcon = 'success') => {
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    })

    return Toast.fire({
      icon,
      title: message
    })
  }

  /**
   * Mostrar loading
   */
  const showLoading = (title: string = 'Cargando...', text: string = 'Por favor espera') => {
    return Swal.fire({
      title,
      text,
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading()
      }
    })
  }

  /**
   * Cerrar loading
   */
  const closeLoading = () => {
    Swal.close()
  }

  /**
   * Notificación con HTML personalizado
   */
  const showHtml = (title: string, html: string, icon: SweetAlertIcon = 'info') => {
    return Swal.fire({
      title,
      html,
      icon,
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#2563eb'
    })
  }

  return {
    show,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    confirm,
    confirmDelete,
    toast,
    showLoading,
    closeLoading,
    showHtml
  }
}
