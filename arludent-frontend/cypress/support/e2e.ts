// ***********************************************************
// Archivo de soporte global para Cypress E2E
// Se ejecuta antes de cada archivo de tests
// ***********************************************************

import './commands'

// Ignorar errores de reCAPTCHA y otros errores externos
Cypress.on('uncaught:exception', (err) => {
  // Ignorar errores de reCAPTCHA de Google
  if (err.message.includes('recaptcha') || err.message.includes('grecaptcha')) {
    return false
  }
  // Ignorar errores de ResizeObserver (comunes en SPAs)
  if (err.message.includes('ResizeObserver')) {
    return false
  }
  // Ignorar errores de red que no son del test
  if (err.message.includes('Network Error') || err.message.includes('ECONNREFUSED')) {
    return false
  }
  // Dejar que Cypress falle para otros errores no capturados
  return true
})
