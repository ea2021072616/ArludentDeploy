/**
 * ===================================================================
 * CU-12: DASHBOARD E INDICADORES — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-12
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Dashboard
 *
 * Cobertura exhaustiva:
 *   - Dashboard del Administrador (KPIs, Satisfacción, Logs)
 *   - Dashboard de la Secretaria (Resumen Citas, Accesos Rápidos,
 *     Estadísticas Rápidas, Resumen de Pagos)
 *   - Indicadores KPI: Ingresos, Citas, Nuevos Pacientes
 *   - Gráfico circular de satisfacción de pacientes (SVG)
 *   - Actividad reciente (logs de auditoría)
 *   - Resumen de Citas del Día (7 estados)
 *   - Accesos Rápidos de Secretaria
 *   - Estadísticas Rápidas de Secretaria
 *   - Resumen de Pagos de Secretaria
 *   - Restricciones de acceso por rol
 * ===================================================================
 */

describe('CU-12: Dashboard e Indicadores — Prueba Funcional Automatizada', () => {

  beforeEach(() => {
    cy.exec(
      'cd "C:\\Users\\erick\\Downloads\\Arludent proyecto tesis\\Arludent\\backend-arludent" && php artisan db:seed --class=CypressTestDataSeeder --force',
      { timeout: 30000, failOnNonZeroExit: false }
    )
  })

  // ═══════════════════════════════════════════════════════════════════
  //  FLUJO PRINCIPAL — Dashboard de Administración
  // ═══════════════════════════════════════════════════════════════════
  describe('Flujo Principal — Dashboard de Administración', () => {

    beforeEach(() => {
      cy.loginAs('admin')
      cy.visit('/admin/dashboard')
      cy.wait(3000)
    })

    it('FP-1: Administrador visualiza el encabezado y los KPIs generales', () => {
      // ── Encabezado del panel ──
      cy.get('h1').should('contain.text', 'Panel de Administración')
      cy.contains('Sistema Arludent - Gestión y Control Total').should('be.visible')

      // ── Los 3 KPIs principales ──
      cy.contains('Ingresos Totales').should('be.visible')
      cy.contains('Total de Citas').should('be.visible')
      cy.contains('Nuevos Pacientes').should('be.visible')

      // ── Período y hora ──
      cy.contains('Última actualización').should('be.visible')
    })

    it('FP-2: Administrador visualiza la sección de Satisfacción de Pacientes', () => {
      // ── Título de la sección ──
      cy.contains('Satisfacción de Pacientes').should('be.visible')

      // ── Gráfico circular SVG ──
      cy.get('svg').should('exist')

      // ── Indicador del total de calificaciones ──
      cy.contains('calificaciones totales').should('be.visible')
    })

    it('FP-3: Administrador visualiza la sección de Actividad Reciente (Logs)', () => {
      // ── Título de la sección ──
      cy.contains('Actividad Reciente').should('be.visible')
      cy.contains('Últimas 10 acciones').should('be.visible')
    })
  })

  // ═══════════════════════════════════════════════════════════════════
  //  FLUJO PRINCIPAL — Dashboard de Secretaria
  // ═══════════════════════════════════════════════════════════════════
  describe('Flujo Principal — Dashboard de Secretaria', () => {

    beforeEach(() => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/dashboard')
      cy.wait(3000)
    })

    it('FP-4: Secretaria visualiza el encabezado y el Resumen de Citas del Día', () => {
      // ── Encabezado del panel ──
      cy.get('h1').should('contain.text', 'Panel de Secretaría')
      cy.contains('Gestión de Pacientes, Citas y Atención').should('be.visible')

      // ── Resumen de Citas del Día con los 7 estados ──
      cy.contains('Resumen de Citas del Día').should('be.visible')
      cy.contains('Total').should('be.visible')
      cy.contains('Confirmadas').should('be.visible')
      cy.contains('En espera').should('be.visible')
      cy.contains('Atendiendo').should('be.visible')
      cy.contains('Completadas').should('be.visible')
      cy.contains('No asistió').should('be.visible')
      cy.contains('Canceladas').should('be.visible')
    })

    it('FP-5: Secretaria visualiza los Accesos Rápidos', () => {
      // ── Título de la sección ──
      cy.contains('Accesos Rápidos').should('be.visible')
    })

    it('FP-6: Secretaria visualiza las Estadísticas Rápidas y Resumen de Pagos', () => {
      // ── Sección de Estadísticas Rápidas ──
      cy.contains('Estadísticas Rápidas').should('be.visible')
      cy.contains('Eficiencia de atención').should('be.visible')
      cy.contains('Tiempo promedio espera').should('be.visible')
      cy.contains('Pacientes nuevos (semana)').should('be.visible')
      cy.contains('% Asistencia').should('be.visible')

      // ── Sección de Resumen de Pagos ──
      cy.contains('Resumen de Pagos').should('be.visible')
      cy.contains('Ingresos del día').should('be.visible')
      cy.contains('Pagos realizados').should('be.visible')
      cy.contains('Pagos pendientes').should('be.visible')
    })
  })

  // ═══════════════════════════════════════════════════════════════════
  //  FLUJO ALTERNO — Validaciones de permisos
  // ═══════════════════════════════════════════════════════════════════
  describe('Flujo Alterno — Validaciones de permisos', () => {

    it('FA-1: Secretaria no puede acceder al dashboard de administración', () => {
      cy.loginAs('secretaria')
      cy.visit('/admin/dashboard')

      // ── El sistema redirige fuera del panel de administración ──
      cy.url().should('not.include', '/admin/dashboard')
    })

    it('FA-2: Paciente no puede acceder al dashboard de administración', () => {
      cy.loginAs('paciente')
      cy.visit('/admin/dashboard')

      // ── El sistema redirige fuera del panel de administración ──
      cy.url().should('not.include', '/admin/dashboard')
    })

    it('FA-3: Paciente no puede acceder al dashboard de secretaría', () => {
      cy.loginAs('paciente')
      cy.visit('/secretaria/dashboard')

      // ── El sistema redirige fuera del panel de secretaría ──
      cy.url().should('not.include', '/secretaria/dashboard')
    })
  })
})
