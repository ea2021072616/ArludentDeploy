/**
 * ===================================================================
 * CU-11: GENERAR REPORTES — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-11
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Reportes (Administración)
 *
 * Cobertura exhaustiva:
 *   - Navegación al módulo de reportes
 *   - Visualización de las 3 tarjetas de reportes (Ingresos, Flujo, Citas)
 *   - Filtro global de fechas (Fecha Inicio / Fecha Fin)
 *   - Apertura de modal de Reporte de Ingresos
 *   - Apertura de modal de Reporte de Flujo de Clientes
 *   - Apertura de modal de Reporte de Citas
 *   - Reporte IA marcado como "Próximamente"
 *   - Limpiar filtros
 *   - Restricción de acceso por rol (secretaria no entra)
 *   - Restricción de acceso por rol (paciente no entra)
 * ===================================================================
 */

describe('CU-11: Generar Reportes — Prueba Funcional Automatizada', () => {

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
      cy.visit('/admin/reportes')
      cy.intercept('GET', '**/api/**').as('apiCalls')
      cy.wait(2000)
    })

    it('FP-1: Administrador navega al módulo de Reportes y visualiza el panel completo', () => {
      // ── Encabezado principal ──
      cy.get('h1').should('contain.text', 'Reportes')
      cy.contains('Genera reportes predefinidos del sistema').should('be.visible')

      // ── Filtro global de fechas ──
      cy.contains('Fecha Inicio').should('be.visible')
      cy.contains('Fecha Fin').should('be.visible')
      cy.get('input[type="date"]').should('have.length.at.least', 2)

      // ── Botón de Limpiar Filtros ──
      cy.contains('button', 'Limpiar Filtros').should('be.visible')

      // ── Las 3 tarjetas de reportes disponibles ──
      cy.contains('Reporte de Ingresos').should('be.visible')
      cy.contains('Flujo de Clientes').should('be.visible')
      cy.contains('Reporte de Citas').should('be.visible')

      // ── Los botones "Generar Reporte →" ──
      cy.contains('Generar Reporte →').should('exist')
    })

    it('FP-2: Administrador aplica filtros de fecha y los limpia', () => {
      // ── Llenar las fechas de inicio y fin ──
      cy.get('input[type="date"]').first().type('2026-01-01', { force: true })
      cy.get('input[type="date"]').last().type('2026-12-31', { force: true })

      // ── Verificar que los valores se registraron ──
      cy.get('input[type="date"]').first().should('have.value', '2026-01-01')
      cy.get('input[type="date"]').last().should('have.value', '2026-12-31')

      // ── Limpiar filtros ──
      cy.contains('button', 'Limpiar Filtros').click({ force: true })

      // ── Verificar que los campos se vaciaron ──
      cy.get('input[type="date"]').first().should('have.value', '')
      cy.get('input[type="date"]').last().should('have.value', '')
    })

    it('FP-3: Administrador abre el Reporte de Ingresos', () => {
      // ── Hacer clic en la tarjeta de Reporte de Ingresos ──
      cy.contains('Reporte de Ingresos').click({ force: true })

      // ── Verificar que se abrió el modal ──
      cy.get('.fixed.inset-0').should('be.visible')
      cy.contains('Reporte de Ingresos').should('be.visible')
    })

    it('FP-4: Administrador abre el Reporte de Flujo de Clientes', () => {
      // ── Hacer clic en la tarjeta de Flujo de Clientes ──
      cy.contains('Flujo de Clientes').click({ force: true })

      // ── Verificar que se abrió el modal ──
      cy.get('.fixed.inset-0').should('be.visible')
      cy.contains('Reporte de Flujo de Clientes').should('be.visible')
    })

    it('FP-5: Administrador abre el Reporte de Citas', () => {
      // ── Hacer clic en la tarjeta de Reporte de Citas ──
      cy.contains('Reporte de Citas').click({ force: true })

      // ── Verificar que se abrió el modal ──
      cy.get('.fixed.inset-0').should('be.visible')
      cy.contains('Reporte de Citas').should('be.visible')
    })
  })

  // ═══════════════════════════════════════════════════════════════════
  //  FLUJO ALTERNO — Validaciones de la interfaz
  // ═══════════════════════════════════════════════════════════════════
  describe('Flujo Alterno — Validaciones de la interfaz', () => {

    it('FA-1: El reporte IA está deshabilitado con la etiqueta "Próximamente"', () => {
      cy.loginAs('admin')
      cy.visit('/admin/reportes')
      cy.wait(2000)

      // ── El reporte IA tiene la etiqueta de "Próximamente" ──
      cy.contains('Reporte Personalizado IA').should('be.visible')
      cy.contains('Próximamente').should('be.visible')

      // ── El botón está deshabilitado ──
      cy.contains('button', 'En desarrollo...').should('be.disabled')
    })

    it('FA-2: Secretaria no puede acceder al módulo de reportes gerenciales', () => {
      cy.loginAs('secretaria')
      cy.visit('/admin/reportes')

      // ── El sistema redirige fuera de la ruta de administración ──
      cy.url().should('not.include', '/admin/reportes')
    })

    it('FA-3: Paciente no puede acceder al módulo de reportes', () => {
      cy.loginAs('paciente')
      cy.visit('/admin/reportes')

      // ── El sistema redirige fuera de la ruta de administración ──
      cy.url().should('not.include', '/admin/reportes')
    })

    it('FA-4: El modal de reporte se cierra correctamente', () => {
      cy.loginAs('admin')
      cy.visit('/admin/reportes')
      cy.wait(2000)

      // ── Abrir modal ──
      cy.contains('Reporte de Ingresos').click({ force: true })
      cy.get('.fixed.inset-0.bg-black').should('be.visible')

      // ── Cerrar el modal haciendo clic en el fondo (self) ──
      cy.get('.fixed.inset-0.bg-black').click({ force: true })

      // ── El modal debe haberse cerrado ──
      cy.get('.fixed.inset-0.bg-black').should('not.exist')
    })
  })
})
