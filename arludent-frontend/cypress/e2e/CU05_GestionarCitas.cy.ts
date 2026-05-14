/**
 * ===================================================================
 * CU-05: GESTIONAR CITAS — PRUEBA FUNCIONAL AUTOMATIZADA (Cypress)
 * ===================================================================
 *
 * Caso de Prueba: CU-05-2
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Gestión de Citas Médicas
 * Fecha: 01/05/2025
 *
 * Replica los flujos documentados en:
 * informe_evidencias_pruebas_CU05_CU06.md → Sección CU-05-2
 *
 * Credenciales:
 * - Paciente: paciente@arludent.com / Paciente123!
 * - Médico:   medico@arludent.com   / Medico123!
 *
 * IMPORTANTE: Este test ejecuta un seeder antes de iniciar para
 * garantizar que existan citas en todos los estados necesarios.
 * Esto produce cambios REALES en la base de datos `consultorio`.
 * ===================================================================
 */

describe('CU-05: Gestionar Citas — Prueba Funcional Automatizada', () => {

  // =====================================================
  // SETUP: Ejecutar seeder para preparar datos de prueba
  // =====================================================
  before(() => {
    cy.exec(
      'cd "C:\\Users\\erick\\Downloads\\Arludent proyecto tesis\\Arludent\\backend-arludent" && php artisan db:seed --class=CypressTestDataSeeder --force',
      { timeout: 30000 }
    ).then((result) => {
      cy.log('📦 Seeder ejecutado: ' + result.stdout)
    })
  })

  // =====================================================
  // FLUJO PRINCIPAL — PERSPECTIVA DEL PACIENTE
  // =====================================================
  describe('Flujo Principal — Perspectiva del Paciente', () => {

    // --------------------------------------------------
    // FP-1: Login del paciente y acceso al dashboard
    // --------------------------------------------------
    it('FP-1: Paciente inicia sesión y accede al dashboard', () => {
      cy.loginAs('paciente')
      cy.url().should('include', '/paciente/dashboard')
      cy.contains('Mis Citas').should('be.visible')
      cy.tomarEvidencia('FP-01-dashboard-paciente')
    })

    // --------------------------------------------------
    // FP-2: Navegar a "Mis Citas" y ver estadísticas
    // --------------------------------------------------
    it('FP-2: Paciente navega a "Mis Citas" y visualiza estadísticas, filtros y listado', () => {
      cy.loginAs('paciente')
      cy.contains('Mis Citas').click()
      cy.url().should('include', '/mis-citas/listado')
      cy.contains('h1', 'Mis Citas').should('be.visible')
      cy.contains('Total').should('be.visible')
      cy.contains('Pendientes').should('be.visible')
      cy.contains('Confirmadas').should('be.visible')
      cy.contains('Completadas').should('be.visible')
      cy.contains('label', 'Estado').should('be.visible')
      cy.contains('label', 'Desde').should('be.visible')
      cy.contains('label', 'Hasta').should('be.visible')
      cy.contains('Limpiar').should('be.visible')
      cy.tomarEvidencia('FP-02-mis-citas-vista-completa')
    })

    // --------------------------------------------------
    // FP-3: Confirmar cita pendiente
    // --------------------------------------------------
    it('FP-3: Paciente confirma una cita en estado pendiente', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.tomarEvidencia('FP-03-antes-confirmar')

      cy.contains('button', 'Confirmar').should('exist')
      cy.contains('button', 'Confirmar').first().click()

      cy.contains('¿Confirmar esta cita?').should('be.visible')
      cy.tomarEvidencia('FP-03-modal-confirmar-abierto')

      cy.contains('button', 'Confirmar Cita').click()

      cy.verificarSwal('confirmada')
      cy.tomarEvidencia('FP-03-swal-exito-confirmar')
      cy.cerrarSwal()
      cy.tomarEvidencia('FP-03-despues-confirmar')
    })

    // --------------------------------------------------
    // FP-4: Cancelar cita pendiente con motivo
    // --------------------------------------------------
    it('FP-4: Paciente cancela una cita pendiente con motivo', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.tomarEvidencia('FP-04-antes-cancelar')

      cy.contains('button', /^Cancelar$/).should('exist')
      cy.contains('button', /^Cancelar$/).first().click()

      cy.contains('¿Estás seguro de cancelar esta cita?').should('be.visible')
      cy.get('textarea').first().type('No podré asistir por motivos laborales')
      cy.tomarEvidencia('FP-04-modal-cancelar-con-motivo')

      cy.contains('button', 'Sí, cancelar cita').click()

      cy.verificarSwal('cancelada')
      cy.tomarEvidencia('FP-04-swal-exito-cancelar')
      cy.cerrarSwal()
      cy.tomarEvidencia('FP-04-despues-cancelar')
    })

    // --------------------------------------------------
    // FP-5: Reprogramar cita
    // --------------------------------------------------
    it('FP-5: Paciente reprograma una cita a fecha futura', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.tomarEvidencia('FP-05-antes-reprogramar')

      cy.contains('button', 'Reprogramar').should('exist')
      cy.contains('button', 'Reprogramar').first().click()

      // Esperar a que el modal se abra
      cy.contains('h3', 'Reprogramar Cita').should('be.visible')

      // Calcular fecha futura
      const fechaFutura = new Date()
      fechaFutura.setDate(fechaFutura.getDate() + 7)
      const fechaStr = fechaFutura.toISOString().split('T')[0]

      // Dentro del modal (.fixed) buscar los inputs de fecha y hora
      // El modal tiene su propio input[type="date"] y input[type="time"]
      // Usar label "Nueva Fecha" y "Nueva Hora" para ubicar los inputs dentro del modal
      cy.contains('label', 'Nueva Fecha').parent().find('input[type="date"]').clear().type(fechaStr)
      cy.contains('label', 'Nueva Hora').parent().find('input[type="time"]').clear().type('15:00')

      // Escribir motivo
      cy.get('textarea:visible').last().type('Cambio de horario laboral')
      cy.tomarEvidencia('FP-05-modal-reprogramar-completado')

      // Click en el botón "Reprogramar" del footer del modal
      cy.get('.fixed').contains('button', 'Reprogramar').click()

      cy.verificarSwal('reprogramada')
      cy.tomarEvidencia('FP-05-swal-exito-reprogramar')
      cy.cerrarSwal()
      cy.tomarEvidencia('FP-05-despues-reprogramar')
    })

    // --------------------------------------------------
    // FP-6: Vista Calendario
    // --------------------------------------------------
    it('FP-6: Paciente accede a Vista Calendario', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(1500)

      cy.contains('Vista Calendario').click()
      cy.url().should('include', '/mis-citas/calendario')
      cy.wait(2000)
      cy.tomarEvidencia('FP-06-vista-calendario')
    })

    // --------------------------------------------------
    // FP-10: Calificar cita completada (5 estrellas)
    // --------------------------------------------------
    it('FP-10: Paciente califica cita completada con 5 estrellas', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.contains('button', 'Calificar').should('exist')
      cy.contains('button', 'Calificar').first().click()

      // Verificar que se abre ModalCalificarCita
      cy.contains('¿Cómo fue tu experiencia?').should('be.visible')

      // Las estrellas tienen class "transition-all" y están dentro del div puntuación
      // Usamos selector más específico: botones con hover:scale-110 (solo las estrellas)
      cy.get('button.transition-all').should('have.length', 5)

      // Click en la 5ta estrella (index 4)
      cy.get('button.transition-all').eq(4).click()

      // Verificar texto "Excelente" (aparece como textoPuntuacion)
      cy.contains('Excelente').should('be.visible')

      // Escribir comentario — buscar el textarea visible dentro del modal
      cy.get('textarea:visible').last().type('Excelente atención, muy profesional y puntual')
      cy.tomarEvidencia('FP-10-modal-calificar-5-estrellas')

      // Enviar calificación
      cy.contains('button', 'Enviar Calificación').click()

      cy.verificarSwal('calificación')
      cy.tomarEvidencia('FP-10-swal-exito-calificar')
      cy.cerrarSwal()
      cy.tomarEvidencia('FP-10-calificacion-visible')
    })
  })

  // =====================================================
  // FLUJO PRINCIPAL — PERSPECTIVA DEL MÉDICO
  // =====================================================
  describe('Flujo Principal — Perspectiva del Médico', () => {

    // --------------------------------------------------
    // FP-7: Login del médico y acceso a Agenda
    // --------------------------------------------------
    it('FP-7: Médico inicia sesión y accede a su Agenda', () => {
      cy.loginAs('medico')
      cy.url().should('include', '/medico/dashboard')
      cy.tomarEvidencia('FP-07-dashboard-medico')

      cy.contains('Mis Citas').click()
      cy.url().should('include', '/medico/mis-citas/listado')
      cy.contains('h1', 'Mis Citas').should('be.visible')
      cy.contains('Total').should('be.visible')
      cy.contains('Pendientes').should('be.visible')
      cy.contains('Confirmadas').should('be.visible')
      cy.contains('Completadas').should('be.visible')
      cy.tomarEvidencia('FP-07-agenda-medico')
    })

    // --------------------------------------------------
    // FP-8: Médico completa cita confirmada
    // --------------------------------------------------
    it('FP-8: Médico completa una cita confirmada', () => {
      cy.loginAs('medico')
      cy.visit('/medico/mis-citas/listado')
      cy.wait(2000)

      cy.tomarEvidencia('FP-08-antes-completar')

      cy.contains('button', 'Completar').should('exist')
      cy.contains('button', 'Completar').first().click()

      cy.wait(1000)
      cy.tomarEvidencia('FP-08-modal-completar')

      // El botón dice "Marcar como Completada" (no "Completar")
      cy.get('.fixed').contains('button', 'Marcar como Completada').click()

      cy.verificarSwal('completada')
      cy.tomarEvidencia('FP-08-swal-exito-completar')
      cy.cerrarSwal()
      cy.tomarEvidencia('FP-08-despues-completar')
    })

    // --------------------------------------------------
    // FP-9: Médico agrega notas clínicas
    // --------------------------------------------------
    it('FP-9: Médico agrega notas clínicas a una cita', () => {
      cy.loginAs('medico')
      cy.visit('/medico/mis-citas/listado')
      cy.wait(2000)

      cy.tomarEvidencia('FP-09-antes-notas')

      cy.contains('button', 'Notas').should('exist')
      cy.contains('button', 'Notas').first().click()
      cy.wait(1000)
      cy.tomarEvidencia('FP-09-modal-notas')

      cy.get('textarea:visible').last().type('Paciente reporta dolor en molar inferior derecho. Se recomienda radiografía panorámica.')
      cy.tomarEvidencia('FP-09-modal-notas-completado')

      cy.get('.fixed').contains('button', /Guardar|Agregar/).click()

      cy.verificarSwal('notas')
      cy.tomarEvidencia('FP-09-swal-exito-notas')
      cy.cerrarSwal()
      cy.tomarEvidencia('FP-09-notas-visibles')
    })
  })

  // =====================================================
  // FLUJO ALTERNO — VALIDACIONES
  // =====================================================
  describe('Flujo Alterno — Validaciones de la interfaz', () => {

    // --------------------------------------------------
    // FA-1: Cita confirmada NO muestra botón "Confirmar"
    // --------------------------------------------------
    it('FA-1: Cita confirmada no muestra botón "Confirmar"', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.get('select').first().select('confirmado')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        const hasCards = $body.find('.hover\\:shadow-lg').length > 0
        if (hasCards) {
          cy.get('.hover\\:shadow-lg').first().within(() => {
            cy.contains('button', 'Confirmar').should('not.exist')
          })
          cy.tomarEvidencia('FA-01-confirmada-sin-boton-confirmar')
        } else {
          cy.contains('No hay citas').should('be.visible')
          cy.tomarEvidencia('FA-01-sin-citas-confirmadas')
        }
      })
    })

    // --------------------------------------------------
    // FA-2: Cita completada solo muestra "Ver Detalles" y "Calificar"
    // --------------------------------------------------
    it('FA-2: Cita completada solo muestra "Ver Detalles" y "Calificar"', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.get('select').first().select('completado')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        const hasCards = $body.find('.hover\\:shadow-lg').length > 0
        if (hasCards) {
          cy.get('.hover\\:shadow-lg').first().within(() => {
            cy.contains('button', 'Ver Detalles').should('exist')
            cy.contains('button', /^Cancelar$/).should('not.exist')
            cy.contains('button', 'Reprogramar').should('not.exist')
            cy.contains('button', /^Confirmar$/).should('not.exist')
          })
          cy.tomarEvidencia('FA-02-completada-solo-ver-detalles')
        } else {
          cy.contains('No hay citas').should('be.visible')
          cy.tomarEvidencia('FA-02-sin-citas-completadas')
        }
      })
    })

    // --------------------------------------------------
    // FA-3: Error al reprogramar con fecha pasada
    // --------------------------------------------------
    it('FA-3: Error al reprogramar con fecha pasada muestra validación', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Reprogramar")').length > 0) {
          cy.contains('button', 'Reprogramar').first().click()

          cy.contains('h3', 'Reprogramar Cita').should('be.visible')

          // Usar fecha pasada — buscar inputs DENTRO del modal
          const fechaPasada = new Date()
          fechaPasada.setDate(fechaPasada.getDate() - 1)
          const fechaStr = fechaPasada.toISOString().split('T')[0]

          cy.contains('label', 'Nueva Fecha').parent().find('input[type="date"]').clear().type(fechaStr)
          cy.contains('label', 'Nueva Hora').parent().find('input[type="time"]').clear().type('10:00')

          // Intentar reprogramar
          cy.get('.fixed').contains('button', 'Reprogramar').click()

          // Verificar mensaje de error — scrollear el modal para que sea visible
          cy.contains('La fecha debe ser futura').scrollIntoView().should('be.visible')
          cy.tomarEvidencia('FA-03-error-fecha-pasada')

          // Cerrar el modal
          cy.contains('button', 'Cancelar').click({ force: true })
        } else {
          cy.log('⚠️ No hay citas disponibles para reprogramar')
          cy.tomarEvidencia('FA-03-sin-citas-reprogramables')
        }
      })
    })

    // --------------------------------------------------
    // FA-4: Error al calificar sin seleccionar estrellas
    // --------------------------------------------------
    it('FA-4: Error al calificar sin seleccionar puntuación', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Calificar")').length > 0) {
          cy.contains('button', 'Calificar').first().click({ force: true })

          // Verificar que se abre el modal
          cy.contains('¿Cómo fue tu experiencia?').should('be.visible')

          // Verificar que el texto dice "Selecciona una puntuación"
          cy.get('.fixed').contains('Selecciona una puntuación').should('be.visible')

          // El botón "Enviar Calificación" debe estar deshabilitado (disabled)
          cy.get('.fixed').contains('button', 'Enviar Calificación').should('be.disabled')
          cy.tomarEvidencia('FA-04-calificar-sin-puntuacion')

          // Cerrar el modal con el botón Cancelar del footer
          cy.get('.fixed').contains('button', 'Cancelar').click({ force: true })
        } else {
          cy.log('⚠️ No hay citas para calificar')
          cy.tomarEvidencia('FA-04-sin-citas-calificables')
        }
      })
    })

    // --------------------------------------------------
    // FA-5: Filtrar por estado "Cancelado"
    // --------------------------------------------------
    it('FA-5: Filtro por estado "cancelado" muestra solo citas canceladas', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      cy.get('select').first().select('cancelado')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        const hasCards = $body.find('.hover\\:shadow-lg').length > 0
        if (hasCards) {
          cy.get('.hover\\:shadow-lg').first().within(() => {
            cy.contains('cancelado').should('exist')
          })
          cy.tomarEvidencia('FA-05-filtro-cancelado')
        } else {
          cy.contains('No hay citas').should('be.visible')
          cy.tomarEvidencia('FA-05-sin-citas-canceladas')
        }
      })
    })

    // --------------------------------------------------
    // FA-6: Estado vacío cuando no hay citas con filtro
    // --------------------------------------------------
    it('FA-6: Estado vacío muestra "No hay citas" con filtros sin resultados', () => {
      cy.loginAs('paciente')
      cy.visit('/mis-citas/listado')
      cy.wait(2000)

      // Usar el select de estado para filtrar por un estado con 0 resultados
      // Primero filtrar por "no_asistio" que es un estado poco probable
      cy.get('select').first().select('no_asistio')
      cy.wait(2000)

      // Verificar estado vacío
      cy.contains('No hay citas').should('be.visible')
      cy.contains('No se encontraron citas').should('be.visible')
      cy.tomarEvidencia('FA-06-estado-vacio')

      // Limpiar filtros y verificar que vuelven las citas
      cy.contains('Limpiar').click()
      cy.wait(2000)
      cy.tomarEvidencia('FA-06-despues-limpiar-filtros')
    })
  })
})
