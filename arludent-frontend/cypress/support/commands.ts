// ***********************************************************
// Comandos personalizados de Cypress para Arludent
// ***********************************************************

// Extender tipos de Cypress con nuestros comandos
declare global {
  namespace Cypress {
    interface Chainable {
      /**
       * Inicia sesión como un usuario específico via API directa (bypasea reCAPTCHA)
       * @param tipo - 'paciente' | 'medico' | 'admin' etc.
       */
      loginAs(tipo: 'paciente' | 'medico' | 'medico2' | 'especialista' | 'admin' | 'secretaria'): Chainable<void>

      /**
       * Verifica que aparezca una notificación SweetAlert2 con el texto esperado
       * @param texto - Texto parcial o completo a buscar en el popup
       */
      verificarSwal(texto: string): Chainable<void>

      /**
       * Cierra el popup SweetAlert2 activo (hace clic en "Aceptar" o espera el timer)
       */
      cerrarSwal(): Chainable<void>

      /**
       * Toma un screenshot con nombre descriptivo para evidencia
       * @param nombre - Nombre descriptivo del screenshot
       */
      tomarEvidencia(nombre: string): Chainable<void>
    }
  }
}

// ============================================================
// COMANDO: loginAs
// Hace login DIRECTO via API (bypasea reCAPTCHA y UI del login)
// Luego guarda el token JWT en localStorage y navega al dashboard
// ============================================================
const credenciales: Record<string, { correo: string; password: string; dashboardUrl: string }> = {
  paciente: {
    correo: 'paciente@arludent.com',
    password: 'Paciente123!',
    dashboardUrl: '/paciente/dashboard',
  },
  medico: {
    correo: 'medico@arludent.com',
    password: 'Medico123!',
    dashboardUrl: '/medico/dashboard',
  },
  medico2: {
    correo: 'medico2@arludent.com',
    password: 'Medico123!',
    dashboardUrl: '/medico/dashboard',
  },
  especialista: {
    correo: 'especialista@arludent.com',
    password: 'Medico123!',
    dashboardUrl: '/medico/dashboard',
  },
  admin: {
    correo: 'admin@arludent.com',
    password: 'Admin123!',
    dashboardUrl: '/admin/dashboard',
  },
  secretaria: {
    correo: 'secretaria@arludent.com',
    password: 'Secretaria123!',
    dashboardUrl: '/secretaria/dashboard',
  },
}

Cypress.Commands.add('loginAs', (tipo: string) => {
  const creds = credenciales[tipo]
  if (!creds) throw new Error(`Credenciales no encontradas para tipo: ${tipo}`)

  // 1. Hacer login DIRECTO via API al backend (bypasea reCAPTCHA)
  cy.request({
    method: 'POST',
    url: 'http://localhost:8000/api/auth/login',
    body: {
      correo: creds.correo,
      password: creds.password,
      recaptcha_token: 'cypress-test-bypass', // El backend lo acepta si recaptcha está en modo test
    },
    failOnStatusCode: false,
  }).then((response) => {
    // Si el login falla con 422 (validación reCAPTCHA), intentar sin el token
    if (response.status === 422) {
      // Intentar login sin recaptcha_token (por si el backend lo ignora)
      cy.request({
        method: 'POST',
        url: 'http://localhost:8000/api/auth/login',
        body: {
          correo: creds.correo,
          password: creds.password,
        },
        failOnStatusCode: false,
      }).then((retryResponse) => {
        if (retryResponse.status !== 200) {
          throw new Error(
            `Login falló para ${tipo} (status ${retryResponse.status}): ${JSON.stringify(retryResponse.body)}`
          )
        }
        handleLoginSuccess(retryResponse.body, creds.dashboardUrl)
      })
    } else if (response.status === 200) {
      handleLoginSuccess(response.body, creds.dashboardUrl)
    } else {
      throw new Error(
        `Login falló para ${tipo} (status ${response.status}): ${JSON.stringify(response.body)}`
      )
    }
  })
})

function handleLoginSuccess(responseBody: any, dashboardUrl: string) {
  const token = responseBody.data?.token || responseBody.token
  const user = responseBody.data?.user || responseBody.user

  if (!token) {
    throw new Error(`No se obtuvo token JWT del login. Response: ${JSON.stringify(responseBody)}`)
  }

  // 2. Guardar token en localStorage (como lo hace el authStore)
  localStorage.setItem('auth_token', token)

  // 3. Navegar al dashboard — la app detectará el token en localStorage
  cy.visit(dashboardUrl)

  // 4. Esperar a que la app cargue y reconozca la sesión
  cy.url({ timeout: 15000 }).should('include', '/dashboard')
}

// ============================================================
// COMANDO: verificarSwal
// Busca el popup de SweetAlert2 y verifica que contenga el texto
// SweetAlert2 usa: .swal2-popup, .swal2-title, .swal2-html-container
// ============================================================
Cypress.Commands.add('verificarSwal', (texto: string) => {
  // Esperar a que aparezca el popup de SweetAlert2
  cy.get('.swal2-popup', { timeout: 10000 }).should('be.visible')

  // Verificar que el texto esté en el título o en el contenido
  cy.get('.swal2-popup').then(($popup) => {
    const textoPopup = $popup.text()
    expect(textoPopup.toLowerCase()).to.include(texto.toLowerCase())
  })
})

// ============================================================
// COMANDO: cerrarSwal
// Cierra el popup SweetAlert2 haciendo clic en el botón de confirmar
// ============================================================
Cypress.Commands.add('cerrarSwal', () => {
  cy.get('body').then(($body) => {
    if ($body.find('.swal2-popup:visible').length > 0) {
      // Si hay botón "Aceptar" visible, hacer clic
      cy.get('.swal2-popup').then(($popup) => {
        const confirmBtn = $popup.find('.swal2-confirm:visible')
        if (confirmBtn.length > 0) {
          cy.get('.swal2-confirm').click()
        }
      })
      // Esperar a que desaparezca
      cy.get('.swal2-popup', { timeout: 5000 }).should('not.exist')
    }
  })
})

// ============================================================
// COMANDO: tomarEvidencia
// Toma screenshot con nombre descriptivo
// ============================================================
Cypress.Commands.add('tomarEvidencia', (nombre: string) => {
  // Pequeña pausa para que la UI se estabilice
  cy.wait(500)
  // Usar viewport para evitar repetición del sidebar fijo en capturas largas
  cy.screenshot(nombre, { capture: 'viewport', overwrite: true })
})

export {}
