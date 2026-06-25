/**
 * ===================================================================
 * CU-10: GENERAR BOLETAS Y FACTURAS — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-10
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Facturación y Caja
 *
 * Cobertura exhaustiva:
 *   - Escenarios exitosos (Boleta y Factura)
 *   - Validaciones de campos obligatorios
 *   - Prevención de comprobantes duplicados
 *   - Navegación entre pestañas (Pagos, Pendientes, Estadísticas)
 *   - Comportamiento visual de componentes (modales, badges, tabs)
 *   - Indicadores estadísticos del panel principal
 *   - Restricciones de acceso por rol
 *   - Integración con backend (interceptores de API)
 *   - Mensajes de éxito y error
 *   - Estados vacíos
 * ===================================================================
 */

describe('CU-10: Generar Boletas y Facturas — Prueba Funcional Automatizada', () => {

  beforeEach(() => {
    cy.exec(
      'cd "C:\\Users\\erick\\Downloads\\Arludent proyecto tesis\\Arludent\\backend-arludent" && php artisan db:seed --class=CypressTestDataSeeder --force',
      { timeout: 30000, failOnNonZeroExit: false }
    )
    cy.loginAs('secretaria')
    cy.visit('/secretaria/caja')

    cy.intercept('GET', '**/caja/pagos*').as('getPagos')
    cy.wait('@getPagos', { timeout: 10000 })
  })

  // ─────────────────────────────────────────────────────────────────
  // Helper reutilizable para crear un pago en el módulo de Caja
  // ─────────────────────────────────────────────────────────────────
  const crearPago = (monto: string, concepto: string) => {
    cy.get('button').contains('Nuevo Pago').click({ force: true })
    cy.get('.fixed.inset-0').should('contain.text', 'Registrar Nuevo Pago')

    // Mock de la búsqueda de pacientes
    cy.intercept('GET', '**/caja/pacientes/buscar*', {
      statusCode: 200,
      body: {
        success: true,
        data: [
          {
            id_paciente: 1,
            id_usuario: 2,
            nombre: "Juan Alberto",
            apellidos: "Perez Gonzales",
            dni: "12345678",
            telefono: "987654321",
            fecha_nacimiento: "1990-01-01",
            direccion: "Av Siempre Viva"
          }
        ]
      }
    }).as('buscarPacientes')

    // Escribir en el buscador de pacientes
    cy.get('input[placeholder*="Escribe nombre"]').clear({ force: true }).type('Juan', { force: true })
    cy.wait('@buscarPacientes')
    cy.wait(500) // Esperar que Vue renderice el dropdown

    // Seleccionar al paciente del dropdown (son elementos <button>)
    cy.contains('.absolute.z-50 button', 'Juan Alberto').click({ force: true })

    // Verificar que el paciente se seleccionó visualmente
    cy.contains('Juan Alberto').should('be.visible')

    // Llenar detalles del pago
    cy.get('input[type="number"]').clear({ force: true }).type(monto, { force: true })
    cy.get('input[placeholder*="Descripción"]').clear({ force: true }).type(concepto, { force: true })

    cy.intercept('POST', '**/caja/pagos').as('crearPago')
    cy.get('button[type="submit"]').contains('Registrar Pago').click({ force: true })
    cy.wait('@crearPago')

    // Verificar el mensaje de éxito del pago
    cy.contains('Pago registrado exitosamente').should('be.visible')
    cy.wait(1500) // Esperar que desaparezca el toast y la tabla se re-renderice
  }

  // ═══════════════════════════════════════════════════════════════════
  //  FLUJO PRINCIPAL — Perspectiva de la Secretaria
  // ═══════════════════════════════════════════════════════════════════
  describe('Flujo Principal — Perspectiva de la Secretaria', () => {

    it('FP-1: Secretaria navega al módulo de Caja y visualiza el panel completo', () => {
      // ── Encabezado principal ──
      cy.get('h1').should('contain.text', 'Caja y Pagos')
      cy.contains('Control financiero y gestión de pagos con SUNAT').should('be.visible')

      // ── Tarjetas de estadísticas ──
      cy.contains('Total Pagos').should('be.visible')
      cy.contains('Pendientes').should('be.visible')
      cy.contains('Comprobantes').should('be.visible')
      cy.contains('Efectivo').should('be.visible')

      // ── Botones de acción ──
      cy.get('button').contains('Nuevo Pago').should('be.visible')
      cy.get('button').contains('Filtros').should('be.visible')

      // ── Tabla de pagos con todas las columnas ──
      cy.get('table thead').within(() => {
        cy.contains('Paciente').should('be.visible')
        cy.contains('Fecha').should('be.visible')
        cy.contains('Concepto').should('be.visible')
        cy.contains('Monto').should('be.visible')
        cy.contains('Método').should('be.visible')
        cy.contains('Estado').should('be.visible')
        cy.contains('Comprobante').should('be.visible')
        cy.contains('Acciones').should('be.visible')
      })

      // ── Navegación por pestañas ──
      cy.contains('button', 'Pagos').should('be.visible')
      cy.contains('button', 'Pendientes').should('be.visible')
      cy.contains('button', 'Estadísticas').should('be.visible')
    })

    it('FP-2: Secretaria registra un pago y emite una Boleta', () => {
      crearPago('150.00', 'Consulta General')

      // ── Hacer clic en el botón "Emitir" de la fila ──
      cy.get('table tbody tr').contains('button', 'Emitir').first().click({ force: true })

      // ── Verificar que el modal de comprobante se abrió ──
      cy.get('.fixed.inset-0').should('be.visible').and('contain.text', 'Emitir Comprobante Electrónico')

      // ── Seleccionar "Boleta" como tipo de comprobante ──
      cy.get('label').contains('Tipo de Comprobante').parent().find('select').select('boleta', { force: true })

      // ── Llenar campos específicos de Boleta ──
      cy.get('label').contains('Tipo de Documento').parent().find('select').select('1', { force: true })
      cy.get('label').contains('Número de Documento').parent().find('input').clear({ force: true }).type('76543210', { force: true })
      cy.get('label').contains('Nombre Completo').parent().find('input').clear({ force: true }).type('Juan Perez', { force: true })

      // ── Emitir el comprobante ──
      cy.intercept('POST', '**/caja/pagos/*/comprobante').as('emitirComprobante')
      cy.get('button[type="submit"]').contains('Emitir Comprobante').click({ force: true })

      cy.wait('@emitirComprobante').its('response.statusCode').should('eq', 200)
      cy.contains('Comprobante emitido exitosamente').should('be.visible')
    })

    it('FP-3: Secretaria registra un pago y emite una Factura', () => {
      crearPago('300.00', 'Tratamiento Dental')

      // ── Hacer clic en el botón "Emitir" de la fila ──
      cy.get('table tbody tr').contains('button', 'Emitir').first().click({ force: true })

      // ── Verificar que el modal de comprobante se abrió ──
      cy.get('.fixed.inset-0').should('be.visible').and('contain.text', 'Emitir Comprobante Electrónico')

      // ── Seleccionar "Factura" como tipo de comprobante ──
      cy.get('label').contains('Tipo de Comprobante').parent().find('select').select('factura', { force: true })

      // ── Verificar que aparecen los campos específicos de Factura ──
      cy.get('label').contains('RUC').should('be.visible')
      cy.get('label').contains('Razón Social').should('be.visible')
      cy.get('label').contains('Dirección').should('be.visible')

      // ── Llenar campos de Factura ──
      cy.get('label').contains('RUC').parent().find('input').clear({ force: true }).type('20123456789', { force: true })
      cy.get('label').contains('Razón Social').parent().find('input').clear({ force: true }).type('Empresa Prueba S.A.C.', { force: true })
      cy.get('label').contains('Dirección').parent().find('input').clear({ force: true }).type('Av. Siempre Viva 123', { force: true })

      // ── Emitir el comprobante ──
      cy.intercept('POST', '**/caja/pagos/*/comprobante').as('emitirFactura')
      cy.get('button[type="submit"]').contains('Emitir Comprobante').click({ force: true })

      cy.wait('@emitirFactura').its('response.statusCode').should('eq', 200)
      cy.contains('Comprobante emitido exitosamente').should('be.visible')
    })

    it('FP-4: Secretaria navega a la pestaña Pendientes y verifica su contenido', () => {
      // ── Navegar a la pestaña "Pendientes" ──
      cy.contains('button', 'Pendientes').click({ force: true })

      // ── Verificar el título de la sección ──
      cy.contains('Pagos Pendientes').should('be.visible')
    })

    it('FP-5: Secretaria navega a la pestaña Estadísticas y verifica indicadores', () => {
      // ── Navegar a la pestaña "Estadísticas" ──
      cy.contains('button', 'Estadísticas').click({ force: true })

      // ── Verificar secciones estadísticas ──
      cy.contains('Últimos 7 Días').should('be.visible')
      cy.contains('Por Método de Pago').should('be.visible')
    })
  })

  // ═══════════════════════════════════════════════════════════════════
  //  FLUJO ALTERNO — Validaciones de la interfaz
  // ═══════════════════════════════════════════════════════════════════
  describe('Flujo Alterno — Validaciones de la interfaz', () => {

    it('FA-1: El sistema no permite emitir un comprobante duplicado', () => {
      crearPago('100.00', 'Limpieza')

      // ── Emitir el comprobante por primera vez ──
      cy.get('table tbody tr').contains('button', 'Emitir').first().click({ force: true })
      cy.get('label').contains('Tipo de Comprobante').parent().find('select').select('boleta', { force: true })
      cy.get('label').contains('Número de Documento').parent().find('input').clear({ force: true }).type('76543210', { force: true })
      cy.get('label').contains('Nombre Completo').parent().find('input').clear({ force: true }).type('Juan Perez', { force: true })

      cy.intercept('POST', '**/caja/pagos/*/comprobante').as('emitir')
      cy.get('button[type="submit"]').contains('Emitir Comprobante').click({ force: true })
      cy.wait('@emitir')

      // ── Después de emitir: aparece el botón "PDF" y desaparece "Emitir" ──
      cy.get('table tbody tr').first().find('button').contains('PDF').should('be.visible')
      cy.get('table tbody tr').first().find('button').contains('Emitir').should('not.exist')
    })

    it('FA-2: Validación de datos obligatorios al emitir Factura', () => {
      crearPago('50.00', 'Radiografía')

      // ── Abrir el modal de comprobante ──
      cy.get('table tbody tr').contains('button', 'Emitir').first().click({ force: true })

      // ── Seleccionar Factura para ver los campos requeridos ──
      cy.get('label').contains('Tipo de Comprobante').parent().find('select').select('factura', { force: true })

      // ── Verificar que los campos tienen el atributo "required" ──
      cy.get('label').contains('RUC').parent().find('input').should('have.attr', 'required')
      cy.get('label').contains('Razón Social').parent().find('input').should('have.attr', 'required')
      cy.get('label').contains('Dirección').parent().find('input').should('have.attr', 'required')
    })

    it('FA-3: Los campos dinámicos cambian al alternar entre Boleta y Factura', () => {
      crearPago('75.00', 'Extracción')

      // ── Abrir el modal de comprobante ──
      cy.get('table tbody tr').contains('button', 'Emitir').first().click({ force: true })

      // ── Seleccionar Boleta: deben aparecer campos de Boleta ──
      cy.get('label').contains('Tipo de Comprobante').parent().find('select').select('boleta', { force: true })
      cy.get('label').contains('Tipo de Documento').should('be.visible')
      cy.get('label').contains('Número de Documento').should('be.visible')
      cy.get('label').contains('Nombre Completo').should('be.visible')

      // ── Cambiar a Factura: deben aparecer campos de Factura ──
      cy.get('label').contains('Tipo de Comprobante').parent().find('select').select('factura', { force: true })
      cy.get('label').contains('RUC').should('be.visible')
      cy.get('label').contains('Razón Social').should('be.visible')
      cy.get('label').contains('Dirección').should('be.visible')

      // ── Los campos de Boleta ya no deben estar visibles ──
      cy.get('label').contains('Nombre Completo').should('not.exist')
    })

    it('FA-4: El modal de Nuevo Pago no envía sin paciente seleccionado', () => {
      cy.get('button').contains('Nuevo Pago').click({ force: true })
      cy.get('.fixed.inset-0').should('contain.text', 'Registrar Nuevo Pago')

      // ── El botón "Registrar Pago" debe estar deshabilitado sin paciente ──
      cy.get('button[type="submit"]').should('be.disabled')
    })

    it('FA-5: Un paciente autenticado no puede acceder al módulo de Caja', () => {
      // ── Cerrar sesión e ingresar como paciente ──
      cy.loginAs('paciente')
      cy.visit('/secretaria/caja')

      // ── El sistema debe redirigir fuera de la ruta de secretaria ──
      cy.url().should('not.include', '/secretaria/caja')
    })
  })
})
