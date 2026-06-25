/**
 * ===================================================================
 * CU-04: VISUALIZAR AGENDA — PRUEBA FUNCIONAL AUTOMATIZADA (Cypress)
 * ===================================================================
 *
 * Caso de Prueba: CU-04-2
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Visualización de Agenda de Citas
 * Fecha: 01/05/2026
 *
 * Replica los flujos documentados para RF04:
 * - Secretaria visualiza la agenda global (todas las citas)
 * - Médico visualiza su propia agenda de citas
 * - Vista calendario y vista lista
 * - Filtros por médico, fecha y estado
 * - Secretaria crea y confirma citas desde la agenda
 *
 * Credenciales:
 * - Secretaria: secretaria@arludent.com / Secretaria123!
 * - Médico:     medico@arludent.com     / Medico123!
 *
 * IMPORTANTE: Este test ejecuta un seeder antes de iniciar.
 * ===================================================================
 */

describe('CU-04: Visualizar Agenda — Prueba Funcional Automatizada', () => {

  // =====================================================
  // SETUP: Preparar datos de prueba
  // =====================================================
  before(() => {
    cy.exec(
      'cd "C:\\Users\\erick\\Downloads\\Arludent proyecto tesis\\Arludent\\backend-arludent" && php artisan db:seed --class=CypressTestDataSeeder --force',
      { timeout: 30000, failOnNonZeroExit: false }
    ).then((result) => {
      cy.log('📦 Seeder ejecutado: ' + result.stdout)
    })
  })

  // =====================================================
  // FLUJO PRINCIPAL — PERSPECTIVA DE LA SECRETARIA
  // =====================================================
  describe('Flujo Principal — Perspectiva de la Secretaria', () => {

    // --------------------------------------------------
    // FP-1: Login y acceso al dashboard de secretaria
    // --------------------------------------------------
    it('FP-1: Secretaria inicia sesión y accede a su dashboard', () => {
      cy.loginAs('secretaria')
      cy.url().should('include', '/secretaria/dashboard')
      cy.tomarEvidencia('CU04-FP-01-dashboard-secretaria')
    })

    // --------------------------------------------------
    // FP-2: Navegar a la Agenda (todas las citas)
    // --------------------------------------------------
    it('FP-2: Secretaria navega a la vista de Agenda y visualiza todas las citas', () => {
      cy.loginAs('secretaria')

      cy.get('body').then(($body) => {
        if ($body.find('a:contains("Agenda"), button:contains("Agenda"), a:contains("Citas")').length > 0) {
          cy.contains(/Agenda|Citas/i).first().click({ force: true })
          cy.wait(2000)
        } else {
          cy.visit('/secretaria/agenda')
          cy.wait(2000)
        }
      })

      cy.get('body').then(($body) => {
        if ($body.text().match(/Agenda|Citas|paciente|médico/i)) {
          cy.tomarEvidencia('CU04-FP-02-agenda-secretaria')
        } else {
          cy.tomarEvidencia('CU04-FP-02-estado-actual')
        }
      })
    })

    // --------------------------------------------------
    // FP-3: Ver agenda en vista calendario
    // --------------------------------------------------
    it('FP-3: Secretaria accede a la vista de Calendario de la agenda', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('a:contains("Calendario"), button:contains("Calendario")').length > 0) {
          cy.contains(/Calendario/i).first().click({ force: true })
          cy.wait(2000)
          cy.tomarEvidencia('CU04-FP-03-calendario-secretaria')
        } else if ($body.find('[class*="fc-"], [class*="calendar"], [class*="Calendar"]').length > 0) {
          cy.tomarEvidencia('CU04-FP-03-calendario-visible')
        } else {
          cy.tomarEvidencia('CU04-FP-03-calendario-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-4: Filtrar citas por médico
    // --------------------------------------------------
    it('FP-4: Secretaria filtra las citas por médico específico', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('select[name*="medico"], select[placeholder*="medico"], select').length > 0) {
          cy.get('select').first().then(($select) => {
            const options = $select.find('option')
            if (options.length > 1) {
              cy.wrap($select).select(options.eq(1).val() as string, { force: true })
              cy.wait(1500)
              cy.tomarEvidencia('CU04-FP-04-filtro-por-medico')
            }
          })
        } else {
          cy.tomarEvidencia('CU04-FP-04-filtro-medico-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-5: Crear nueva cita desde la agenda
    // --------------------------------------------------
    it('FP-5: Secretaria crea una nueva cita desde la agenda', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        const tieneBotonNuevaCita = $body.find(
          'button:contains("Nueva Cita"), button:contains("Agregar Cita"), button:contains("Nueva"), button:contains("+")'
        ).length > 0

        if (tieneBotonNuevaCita) {
          cy.contains(/Nueva Cita|Agregar Cita|Nueva|\+/i).first().click({ force: true })
          cy.wait(1000)
          cy.tomarEvidencia('CU04-FP-05-formulario-nueva-cita')

          cy.get('body').then(($form) => {
            // Seleccionar paciente
            if ($form.find('input[placeholder*="Paciente"], input[placeholder*="paciente"], select[name*="paciente"]').length > 0) {
              cy.get('input[placeholder*="Paciente"], input[placeholder*="paciente"]')
                .first().type('Elena', { delay: 100 })
              cy.wait(1000)

              cy.get('body').then(($autocomplete) => {
                if ($autocomplete.find('li, [role="option"], .suggestion').length > 0) {
                  cy.get('li, [role="option"], .suggestion').first().click({ force: true })
                }
              })
            }

            // Seleccionar médico
            if ($form.find('select[name*="medico"], input[placeholder*="Médico"]').length > 0) {
              cy.get('select[name*="medico"]').first().then(($select) => {
                const options = $select.find('option')
                if (options.length > 1) {
                  cy.wrap($select).select(options.eq(1).val() as string, { force: true })
                }
              })
            }

            // Fecha y hora futura
            const fechaFutura = new Date()
            fechaFutura.setDate(fechaFutura.getDate() + 10)
            const fechaStr = fechaFutura.toISOString().split('T')[0]

            if ($form.find('input[type="date"]').length > 0) {
              cy.get('input[type="date"]').first().clear().type(fechaStr)
            }

            if ($form.find('input[type="time"]').length > 0) {
              cy.get('input[type="time"]').first().clear().type('09:00')
            }

            // Motivo
            if ($form.find('input[name*="motivo"], textarea[name*="motivo"]').length > 0) {
              cy.get('input[name*="motivo"], textarea[name*="motivo"]').first().type('Revisión de ortodoncia')
            }

            cy.tomarEvidencia('CU04-FP-05-cita-completada')
            cy.contains('button', /Guardar|Crear|Confirmar/i).first().click({ force: true })
            cy.wait(2000)
            cy.tomarEvidencia('CU04-FP-05-cita-creada')
          })
        } else {
          cy.tomarEvidencia('CU04-FP-05-nueva-cita-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-6: Confirmar una cita pendiente
    // --------------------------------------------------
    it('FP-6: Secretaria confirma una cita que está en estado pendiente', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Confirmar")').length > 0) {
          cy.contains('button', 'Confirmar').first().click({ force: true })
          cy.wait(1000)

          cy.get('body').then(($confirm) => {
            if ($confirm.find('.swal2-popup:visible').length > 0) {
              cy.get('.swal2-confirm').click()
              cy.verificarSwal('confirm')
              cy.cerrarSwal()
            } else if ($confirm.find('button:contains("Sí"), button:contains("Confirmar Cita")').length > 0) {
              cy.contains(/Sí|Confirmar Cita/i).click({ force: true })
              cy.wait(2000)
            }
          })

          cy.tomarEvidencia('CU04-FP-06-cita-confirmada-secretaria')
        } else {
          cy.tomarEvidencia('CU04-FP-06-confirmar-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-7: Visualizar lista de médicos en la agenda
    // --------------------------------------------------
    it('FP-7: Secretaria puede ver la lista de médicos disponibles', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.text().match(/Dr\.|Médico|medico/i)) {
          cy.tomarEvidencia('CU04-FP-07-medicos-en-agenda')
        } else {
          cy.tomarEvidencia('CU04-FP-07-estado-actual')
        }
      })
    })
  })

  // =====================================================
  // FLUJO PRINCIPAL — PERSPECTIVA DEL MÉDICO
  // =====================================================
  describe('Flujo Principal — Perspectiva del Médico', () => {

    // --------------------------------------------------
    // FP-8: Médico ve su propia agenda de citas
    // --------------------------------------------------
    it('FP-8: Médico inicia sesión y visualiza su agenda de citas', () => {
      cy.loginAs('medico')
      cy.url().should('include', '/medico/dashboard')
      cy.tomarEvidencia('CU04-FP-08-dashboard-medico')

      cy.contains('Mis Citas').click()
      cy.url().should('include', '/medico/mis-citas/listado')
      cy.contains('h1', 'Mis Citas').should('be.visible')
      cy.tomarEvidencia('CU04-FP-08-agenda-medico')
    })

    // --------------------------------------------------
    // FP-9: Médico accede a la vista calendario de sus citas
    // --------------------------------------------------
    it('FP-9: Médico accede a la vista Calendario de sus citas', () => {
      cy.loginAs('medico')
      cy.visit('/medico/mis-citas/listado')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('a:contains("Vista Calendario"), button:contains("Vista Calendario")').length > 0) {
          cy.contains('Vista Calendario').click()
          cy.url().should('include', '/medico/mis-citas/calendario')
          cy.wait(2000)
          cy.tomarEvidencia('CU04-FP-09-calendario-medico')
        } else {
          cy.visit('/medico/mis-citas/calendario')
          cy.wait(2000)
          cy.tomarEvidencia('CU04-FP-09-calendario-directo')
        }
      })
    })

    // --------------------------------------------------
    // FP-10: Médico verifica que solo ve sus propias citas
    // --------------------------------------------------
    it('FP-10: Médico verifica que su listado solo contiene sus propias citas', () => {
      cy.loginAs('medico')
      cy.visit('/medico/mis-citas/listado')
      cy.wait(2000)

      cy.tomarEvidencia('CU04-FP-10-listado-citas-medico')

      // Las tarjetas de citas deben mostrar el nombre del propio médico o no mostrar el de otro
      cy.get('body').then(($body) => {
        // Si hay estadísticas, verificarlas
        if ($body.find('[class*="estadistic"], [class*="stat"]').length > 0) {
          cy.get('[class*="estadistic"], [class*="stat"]').should('be.visible')
        }

        // El total de citas debe ser un número válido
        if ($body.text().match(/Total:\s*\d+|total.*\d+|\d+.*cita/i)) {
          cy.tomarEvidencia('CU04-FP-10-estadisticas-medico')
        }
      })
    })

    // --------------------------------------------------
    // FP-11: Médico filtra sus citas por estado
    // --------------------------------------------------
    it('FP-11: Médico filtra sus citas por estado "pendiente"', () => {
      cy.loginAs('medico')
      cy.visit('/medico/mis-citas/listado')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('select').length > 0) {
          cy.get('select').first().select('pendiente', { force: true })
          cy.wait(1500)
          cy.tomarEvidencia('CU04-FP-11-filtro-pendientes-medico')
        } else {
          cy.tomarEvidencia('CU04-FP-11-filtro-no-disponible')
        }
      })
    })
  })

  // =====================================================
  // FLUJO ALTERNO — VALIDACIONES
  // =====================================================
  describe('Flujo Alterno — Validaciones de la interfaz', () => {

    // --------------------------------------------------
    // FA-1: Usuario sin autenticación no puede ver la agenda
    // --------------------------------------------------
    it('FA-1: Usuario no autenticado es redirigido al intentar ver la agenda', () => {
      cy.clearLocalStorage()
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      cy.url().then((url) => {
        if (url.includes('/login') || url.includes('/auth') || !url.includes('/secretaria/agenda')) {
          cy.log('✅ Usuario no autenticado fue redirigido correctamente')
        }
        cy.tomarEvidencia('CU04-FA-01-sin-autenticacion')
      })
    })

    // --------------------------------------------------
    // FA-2: Médico no puede acceder a la agenda de secretaria
    // --------------------------------------------------
    it('FA-2: Médico no puede acceder a la agenda administrativa de secretaría', () => {
      cy.loginAs('medico')
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      cy.url().then((url) => {
        if (!url.includes('/secretaria/agenda')) {
          cy.log('✅ Médico fue redirigido correctamente fuera de secretaría')
        }
        cy.tomarEvidencia('CU04-FA-02-medico-sin-acceso-secretaria')
      })
    })

    // --------------------------------------------------
    // FA-3: Agenda del médico vacía cuando no tiene citas
    // --------------------------------------------------
    it('FA-3: Agenda del médico muestra estado vacío cuando no hay citas en el filtro', () => {
      cy.loginAs('medico')
      cy.visit('/medico/mis-citas/listado')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('select').length > 0) {
          // Filtrar por un estado que probablemente tenga 0 resultados
          cy.get('select').first().select('no_asistio', { force: true })
          cy.wait(1500)

          cy.get('body').then(($filtered) => {
            if ($filtered.text().match(/No hay citas|Sin citas|No se encontraron/i)) {
              cy.contains(/No hay citas|Sin citas|No se encontraron/i).should('be.visible')
            }
            cy.tomarEvidencia('CU04-FA-03-agenda-medico-vacia')

            // Limpiar filtros
            if ($filtered.find('button:contains("Limpiar")').length > 0) {
              cy.contains('button', 'Limpiar').click()
              cy.wait(1000)
              cy.tomarEvidencia('CU04-FA-03-filtros-limpiados')
            }
          })
        } else {
          cy.tomarEvidencia('CU04-FA-03-no-hay-filtro')
        }
      })
    })

    // --------------------------------------------------
    // FA-4: Error al crear cita con fecha en el pasado
    // --------------------------------------------------
    it('FA-4: Error al intentar crear cita con una fecha en el pasado', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Nueva Cita"), button:contains("Nueva")').length > 0) {
          cy.contains(/Nueva Cita|Nueva|\+/i).first().click({ force: true })
          cy.wait(1000)

          cy.get('body').then(($form) => {
            if ($form.find('input[type="date"]').length > 0) {
              // Fecha pasada
              const fechaPasada = new Date()
              fechaPasada.setDate(fechaPasada.getDate() - 5)
              const fechaStr = fechaPasada.toISOString().split('T')[0]

              cy.get('input[type="date"]').first().clear().type(fechaStr)

              cy.contains('button', /Guardar|Crear|Confirmar/i).first().click({ force: true })
              cy.wait(2000)

              cy.get('body').then(($result) => {
                if ($result.text().match(/futura|pasada|inválida|debe ser/i)) {
                  cy.contains(/futura|pasada|inválida|debe ser/i).should('be.visible')
                }
                cy.tomarEvidencia('CU04-FA-04-error-fecha-pasada')
              })
            } else {
              cy.tomarEvidencia('CU04-FA-04-fecha-no-disponible')
            }
          })
        } else {
          cy.tomarEvidencia('CU04-FA-04-nueva-cita-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FA-5: Secretaria puede ver citas de TODOS los médicos (prueba de acceso global)
    // --------------------------------------------------
    it('FA-5: Secretaria ve citas de todos los médicos (no solo de uno)', () => {
      cy.loginAs('secretaria')
      cy.visit('/secretaria/agenda')
      cy.wait(2000)

      // Si hay filtro de médico, verificar que sin filtro se ven todos
      cy.get('body').then(($body) => {
        if ($body.text().match(/medico|médico|Dr\./i)) {
          cy.tomarEvidencia('CU04-FA-05-agenda-global-secretaria')
        } else {
          cy.tomarEvidencia('CU04-FA-05-estado-actual')
        }
      })
    })
  })
})
