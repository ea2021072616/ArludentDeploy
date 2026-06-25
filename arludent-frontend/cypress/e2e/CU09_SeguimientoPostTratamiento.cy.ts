/**
 * ===================================================================
 * CU-09: SEGUIMIENTO POST-TRATAMIENTO — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-09
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Seguimientos
 *
 * Replica los flujos documentados para RF09 de manera estricta.
 * - FP-1: Secretaria crea seguimiento.
 * - FP-2: Secretaria registra contacto manual.
 * - FP-3: Paciente visualiza su cuestionario (como ya fue contactado, debe decir "completado").
 * - FA-1: Token inválido.
 * - FA-2: Control de acceso de roles.
 * ===================================================================
 */

describe('CU-09: Seguimiento Post-Tratamiento — Prueba Funcional Automatizada', () => {

  before(() => {
    cy.exec(
      'cd "C:\\Users\\erick\\Downloads\\Arludent proyecto tesis\\Arludent\\backend-arludent" && php artisan db:seed --class=CypressTestDataSeeder --force',
      { timeout: 30000, failOnNonZeroExit: false }
    ).then((result) => {
      cy.log('📦 Seeder ejecutado: ' + result.stdout)
    })
  })

  describe('Flujo Principal — Perspectiva de la Secretaria', () => {

    it('FP-1: Secretaria navega a Seguimientos y crea uno nuevo', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/seguimiento')

      cy.contains('Seguimiento Post-Tratamiento').should('be.visible')
      
      cy.intercept('POST', '**/secretaria/seguimiento').as('crearSeguimiento')

      cy.contains('button', 'Nuevo Seguimiento').click()
      cy.contains('Nuevo Seguimiento Post-Tratamiento').should('be.visible')

      cy.get('input[placeholder*="Buscar paciente"]').type('paciente')
      cy.get('.absolute.z-10').contains('paciente', { matchCase: false }).click()

      cy.get('select').eq(0).select('postoperatorio') // Tipo de seguimiento
      cy.get('select').eq(1).select('alta') // Prioridad
      
      const tomorrow = new Date()
      tomorrow.setDate(tomorrow.getDate() + 1)
      const tomorrowStr = tomorrow.toISOString().split('T')[0]
      cy.get('input[type="date"]').type(tomorrowStr)

      cy.contains('button', 'Crear Seguimiento').click()

      cy.wait('@crearSeguimiento').then((interception) => {
        const response = interception.response?.body
        const token = response?.seguimiento?.token_respuesta || 'test-token-12345'
        Cypress.env('seguimientoToken', token)
        cy.log('🔑 Token guardado: ' + token)
      })

      cy.contains('Seguimiento creado correctamente').should('be.visible')
    })

    it('FP-2: Secretaria registra un contacto manual', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/seguimiento')
      
      cy.get('table').should('be.visible')

      // Hacer clic en registrar contacto (solo aparece para seguimientos pendientes)
      cy.get('button[title="Registrar seguimiento"]').first().click()
      cy.contains('Registrar Contacto con Paciente').should('be.visible')

      // Seleccionar estado y escribir respuesta
      cy.contains('button', 'Muy bien').click()
      cy.get('textarea').first().type('El paciente reporta que se siente bien, sin dolor ni molestias tras el tratamiento.')
      
      cy.contains('button', 'Registrar Contacto').click()
      cy.contains('Contacto registrado correctamente').should('be.visible')
    })

  })

  describe('Flujo Principal — Perspectiva del Paciente', () => {

    it('FP-3: Paciente responde su estado a través del portal público', () => {
      // Como la secretaria ya lo contactó en FP-2, el link debe decir que ya fue respondido.
      const token = Cypress.env('seguimientoToken') || 'test-token-12345'
      
      cy.visit(`/seguimiento/${token}`)
      
      // Validamos que la interfaz pública reconozca que ya fue gestionado
      cy.contains('¡Gracias por tu respuesta!').should('be.visible')
      cy.contains('Ya has completado este cuestionario').should('be.visible')
    })

  })

  describe('Flujo Alterno — Validaciones de la interfaz', () => {

    it('FA-1: Muestra error si el token del seguimiento es inválido o expiró', () => {
      cy.visit(`/seguimiento/token-invalido-inexistente`)
      cy.contains('El enlace no es válido o ha expirado').should('be.visible')
    })

    it('FA-2: Un paciente autenticado no puede acceder a la gestión interna de seguimientos', () => {
      cy.loginAs('paciente')
      cy.visit('/secretaria/seguimiento')
      
      // Debe ser redirigido
      cy.url().should('not.include', '/secretaria/seguimiento')
    })
    
  })

})
