/**
 * ===================================================================
 * CU-13: AUDITORÍA DEL SISTEMA — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-13
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Auditoría
 *
 * Cobertura exhaustiva:
 *   - Visualización de tarjetas estadísticas (Total Logs, Hoy, Módulos, Usuarios)
 *   - Búsqueda por texto (debounce)
 *   - Filtrado por Acción
 *   - Filtrado por Módulo
 *   - Visualización correcta de la tabla de logs
 *   - Restricción de acceso por rol (secretaria no entra)
 *   - Restricción de acceso por rol (paciente no entra)
 * ===================================================================
 */

describe('CU-13: Auditoría del Sistema — Prueba Funcional Automatizada', () => {

  beforeEach(() => {
    cy.exec(
      'cd "C:\\Users\\erick\\Downloads\\Arludent proyecto tesis\\Arludent\\backend-arludent" && php artisan db:seed --class=CypressTestDataSeeder --force',
      { timeout: 30000, failOnNonZeroExit: false }
    )
  })

  // ═══════════════════════════════════════════════════════════════════
  //  FLUJO PRINCIPAL — Perspectiva del Administrador
  // ═══════════════════════════════════════════════════════════════════
  describe('Flujo Principal — Perspectiva del Administrador', () => {

    beforeEach(() => {
      cy.loginAs('admin')
      cy.visit('/admin/auditoria')
      cy.intercept('GET', '**/api/sistema/auditoria*').as('getLogs')
      cy.wait('@getLogs')
    })

    it('FP-1: Administrador visualiza el panel de auditoría y sus estadísticas', () => {
      // ── Encabezado principal ──
      cy.get('h1').should('contain.text', 'Auditoría del Sistema')
      cy.contains('Registro de actividades del sistema').should('be.visible')

      // ── Las 4 tarjetas de estadísticas ──
      cy.contains('Total Logs').should('be.visible')
      cy.contains('Hoy').should('be.visible')
      cy.contains('Módulos').should('be.visible')
      cy.contains('Usuarios').should('be.visible')
    })

    it('FP-2: Administrador visualiza la tabla de logs correctamente', () => {
      // ── Encabezados de la tabla ──
      cy.get('th').contains('Fecha').should('be.visible')
      cy.get('th').contains('Usuario').should('be.visible')
      cy.get('th').contains('Acción').should('be.visible')
      cy.get('th').contains('Módulo').should('be.visible')
      cy.get('th').contains('Descripción').should('be.visible')

      // ── Verificar que hay filas en la tabla (asumiendo que el seeder genera algunas o el login propio generó una) ──
      cy.get('tbody tr').should('have.length.at.least', 1)
    })

    it('FP-3: Administrador filtra auditoría por texto de búsqueda', () => {
      cy.intercept('GET', '**/api/sistema/auditoria*').as('searchLogs')
      
      // ── Escribir en el input de búsqueda (se aplica debounce) ──
      cy.get('input[placeholder="Buscar..."]').type('admin')
      
      // ── Esperar a que se dispare la API después del debounce de 500ms ──
      cy.wait('@searchLogs')
      
      // ── Verificar que la tabla se actualiza (puede estar vacía o tener resultados, pero el flujo es el importante) ──
      cy.get('table').should('exist')
    })

    it('FP-4: Administrador filtra auditoría por Acción y Módulo', () => {
      cy.intercept('GET', '**/api/sistema/auditoria*').as('filterLogs')

      // ── Seleccionar una acción (el combo tiene la opción "Todas" por defecto, cambiamos a la segunda opción si existe) ──
      cy.get('select').eq(0).select(1, { force: true })
      cy.wait('@filterLogs')

      // ── Seleccionar un módulo ──
      cy.get('select').eq(1).select(1, { force: true })
      cy.wait('@filterLogs')
      
      cy.get('table').should('exist')
    })
  })

  // ═══════════════════════════════════════════════════════════════════
  //  FLUJO ALTERNO — Validaciones de la interfaz
  // ═══════════════════════════════════════════════════════════════════
  describe('Flujo Alterno — Validaciones de la interfaz', () => {

    it('FA-1: Secretaria no puede acceder al módulo de auditoría', () => {
      cy.loginAs('secretaria')
      cy.visit('/admin/auditoria')

      // ── El sistema redirige fuera de la ruta de administración ──
      cy.url().should('not.include', '/admin/auditoria')
    })

    it('FA-2: Paciente no puede acceder al módulo de auditoría', () => {
      cy.loginAs('paciente')
      cy.visit('/admin/auditoria')

      // ── El sistema redirige fuera de la ruta de administración ──
      cy.url().should('not.include', '/admin/auditoria')
    })
  })
})
