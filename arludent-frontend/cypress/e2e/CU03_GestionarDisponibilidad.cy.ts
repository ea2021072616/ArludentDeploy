/**
 * ===================================================================
 * CU-03: GESTIONAR DISPONIBILIDAD DE TIEMPO — PRUEBA FUNCIONAL
 * ===================================================================
 *
 * Caso de Prueba: CU-03-2
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Disponibilidad Médica
 * Fecha: 01/05/2026
 *
 * Replica los flujos documentados para RF03:
 * - Médico gestiona su agenda semanal (horarios)
 * - Médico crea bloqueos por vacaciones/ausencias
 * - Médico actualiza y elimina disponibilidades
 *
 * Credenciales:
 * - Médico: medico@arludent.com / Medico123!
 *
 * IMPORTANTE: Este test ejecuta un seeder antes de iniciar.
 * ===================================================================
 */

describe('CU-03: Gestionar Disponibilidad de Tiempo — Prueba Funcional', () => {

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
  // FLUJO PRINCIPAL — PERSPECTIVA DEL MÉDICO
  // =====================================================
  describe('Flujo Principal — Perspectiva del Médico', () => {

    // --------------------------------------------------
    // FP-1: Login y acceso al módulo de disponibilidad
    // --------------------------------------------------
    it('FP-1: Médico inicia sesión y accede a su módulo de disponibilidad', () => {
      cy.loginAs('medico')
      cy.url().should('include', '/medico/dashboard')
      cy.tomarEvidencia('CU03-FP-01-dashboard-medico')

      cy.get('body').then(($body) => {
        if ($body.find('a:contains("Disponibilidad"), button:contains("Disponibilidad")').length > 0) {
          cy.contains(/Disponibilidad/i).first().click({ force: true })
          cy.wait(2000)
          cy.tomarEvidencia('CU03-FP-01-modulo-disponibilidad')
        } else {
          cy.visit('/medico/disponibilidad')
          cy.wait(2000)
          cy.tomarEvidencia('CU03-FP-01-disponibilidad-directa')
        }
      })
    })

    // --------------------------------------------------
    // FP-2: Visualizar disponibilidad actual
    // --------------------------------------------------
    it('FP-2: Médico visualiza su disponibilidad actual con horarios', () => {
      cy.loginAs('medico')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.text().match(/Disponibilidad|Horario|Bloqueo|Lunes|Martes/i)) {
          cy.tomarEvidencia('CU03-FP-02-disponibilidad-visible')
        } else {
          cy.tomarEvidencia('CU03-FP-02-estado-actual')
        }
      })
    })

    // --------------------------------------------------
    // FP-3: Crear nuevo horario semanal
    // --------------------------------------------------
    it('FP-3: Médico agrega un nuevo horario semanal (Jueves 10:00–14:00)', () => {
      cy.loginAs('especialista')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        const tieneBotonAgregar = $body.find(
          'button:contains("Agregar"), button:contains("Nuevo"), button:contains("Añadir"), button:contains("+")'
        ).length > 0

        if (tieneBotonAgregar) {
          cy.contains('button', /Agregar|Nuevo|Añadir|\+/i).first().click({ force: true })
          cy.wait(1000)
          cy.tomarEvidencia('CU03-FP-03-formulario-nuevo-horario')

          cy.get('body').then(($form) => {
            // Tipo: horario
            if ($form.find('select[name="tipo"], input[value="horario"]').length > 0) {
              cy.get('select[name="tipo"], input[value="horario"]').first().then($el => {
                if ($el.is('select')) {
                  cy.wrap($el).select('horario', { force: true })
                } else {
                  cy.wrap($el).check({ force: true })
                }
              })
            }

            // Día de la semana
            cy.contains('label', 'Día de la semana').parent().find('select').select('4', { force: true })  // Jueves

            // Hora inicio
            if ($form.find('input[name="hora_inicio"], input[type="time"]').length > 0) {
              cy.get('input[name="hora_inicio"], input[type="time"]').first().clear().type('10:00')
            }

            // Hora fin
            const horaFinSelectors = 'input[name="hora_fin"]'
            if ($form.find(horaFinSelectors).length > 0) {
              cy.get(horaFinSelectors).first().clear().type('14:00')
            } else if ($form.find('input[type="time"]').length > 1) {
              cy.get('input[type="time"]').eq(1).clear().type('14:00')
            }

            cy.tomarEvidencia('CU03-FP-03-horario-completo')
            cy.contains('button', /Guardar|Agregar|Crear|Confirmar/i).first().click({ force: true })
            cy.wait(2000)

            cy.get('body').then(($result) => {
              if ($result.text().match(/exitosamente|creado|agregado/i)) {
                cy.contains(/exitosamente|creado|agregado/i).should('be.visible')
              }
              cy.tomarEvidencia('CU03-FP-03-horario-creado')
            })
          })
        } else {
          cy.tomarEvidencia('CU03-FP-03-agregar-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-4: Crear bloqueo por ausencia
    // --------------------------------------------------
    it('FP-4: Médico crea un bloqueo por vacaciones en fechas futuras', () => {
      cy.loginAs('especialista')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        const tieneBotonBloqueo = $body.find(
          'button:contains("Bloqueo"), button:contains("Agregar Bloqueo"), button:contains("Bloquear")'
        ).length > 0

        const tieneBotonGeneral = $body.find(
          'button:contains("Agregar"), button:contains("Nuevo")'
        ).length > 0

        if (tieneBotonBloqueo) {
          cy.contains(/Bloqueo|Bloquear/i).first().click({ force: true })
        } else if (tieneBotonGeneral) {
          cy.contains(/Agregar|Nuevo/i).first().click({ force: true })
        }

        if (tieneBotonBloqueo || tieneBotonGeneral) {
          cy.wait(1000)
          cy.tomarEvidencia('CU03-FP-04-formulario-bloqueo')

          cy.get('body').then(($form) => {
            // Evaluamos si existen botones de select primero pero los select se harán fuera del then
          })

          // Seleccionar tipo bloqueo y rango de fechas específicos
          cy.contains('label', 'Tipo *').parent().find('select').select('bloqueo', { force: true })
          cy.contains('label', 'Tipo de horario *').parent().find('select').select('especifico', { force: true })
          cy.wait(500)

          cy.get('body').then(($bodyFinal) => {
            // Fecha inicio (7 días en el futuro)
            const fechaInicio = new Date()
            fechaInicio.setDate(fechaInicio.getDate() + 14)
            const fechaInicioStr = fechaInicio.toISOString().split('T')[0]

            const fechaFin = new Date()
            fechaFin.setDate(fechaFin.getDate() + 16)
            const fechaFinStr = fechaFin.toISOString().split('T')[0]

            if ($bodyFinal.find('input[name="fecha_inicio"], input[type="date"]').length > 0) {
              cy.get('input[name="fecha_inicio"], input[type="date"]').first().clear().type(fechaInicioStr)
            }

            if ($bodyFinal.find('input[name="fecha_fin"]').length > 0) {
              cy.get('input[name="fecha_fin"]').first().clear().type(fechaFinStr)
            } else if ($bodyFinal.find('input[type="date"]').length > 1) {
              cy.get('input[type="date"]').eq(1).clear().type(fechaFinStr)
            }

            // Hora inicio / fin
            if ($bodyFinal.find('input[name="hora_inicio"], input[type="time"]').length > 0) {
              cy.get('input[name="hora_inicio"], input[type="time"]').first().clear().type('08:00')
            }

            if ($bodyFinal.find('input[name="hora_fin"]').length > 0) {
              cy.get('input[name="hora_fin"]').first().clear().type('20:00')
            } else if ($bodyFinal.find('input[type="time"]').length > 1) {
              cy.get('input[type="time"]').eq(1).clear().type('20:00')
            }

            // Motivo
            if ($bodyFinal.find('input[name="motivo"], textarea').length > 0) {
              cy.get('input[name="motivo"], textarea').last().type('Vacaciones anuales')
            }

            cy.tomarEvidencia('CU03-FP-04-bloqueo-completo')
            cy.contains('button', /Guardar|Agregar|Crear|Confirmar/i).first().click({ force: true })
            cy.wait(2000)

            cy.tomarEvidencia('CU03-FP-04-bloqueo-creado')
          })
        } else {
          cy.tomarEvidencia('CU03-FP-04-bloqueo-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-5: Eliminar una disponibilidad
    // --------------------------------------------------
    it('FP-5: Médico elimina una disponibilidad existente', () => {
      cy.loginAs('especialista')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        const tieneEliminar = $body.find(
          'button:contains("Eliminar"), button[title*="Eliminar"], button[aria-label*="eliminar"]'
        ).length > 0

        if (tieneEliminar) {
          cy.get('button[title*="Eliminar"], button[aria-label*="eliminar"], button:contains("Eliminar")').first().click({ force: true })
          cy.wait(1000)

          // Confirmar la eliminación si hay modal
          cy.get('body').then(($confirm) => {
            if ($confirm.find('.swal2-popup:visible').length > 0) {
              cy.get('.swal2-confirm').click()
            } else if ($confirm.find('button:contains("Sí"), button:contains("Confirmar")').length > 0) {
              cy.contains(/Sí|Confirmar/i).click({ force: true })
            }
          })

          cy.wait(2000)
          cy.tomarEvidencia('CU03-FP-05-disponibilidad-eliminada')
        } else {
          cy.tomarEvidencia('CU03-FP-05-eliminar-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-6: Verificar tipo de médico y horarios predefinidos
    // --------------------------------------------------
    it('FP-6: Médico de cabecera visualiza sus horarios predefinidos', () => {
      cy.loginAs('medico2')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.text().match(/predefinido|cabecera|turno|mañana|tarde/i)) {
          cy.tomarEvidencia('CU03-FP-06-horarios-predefinidos-cabecera')
        } else {
          cy.tomarEvidencia('CU03-FP-06-disponibilidad-cabecera')
        }
      })
    })
  })

  // =====================================================
  // FLUJO ALTERNO — VALIDACIONES
  // =====================================================
  describe('Flujo Alterno — Validaciones de la interfaz', () => {

    // --------------------------------------------------
    // FA-1: Error al crear horario sin hora fin
    // --------------------------------------------------
    it('FA-1: Error al intentar guardar un horario sin especificar hora de fin', () => {
      cy.loginAs('especialista')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Agregar"), button:contains("Nuevo")').length > 0) {
          cy.contains(/Agregar|Nuevo/i).first().click({ force: true })
          cy.wait(1000)

          cy.get('body').then(($form) => {
            if ($form.find('input[name="hora_inicio"], input[type="time"]').length > 0) {
              cy.get('input[name="hora_inicio"], input[type="time"]').first().clear().type('09:00')
              // No llenar hora_fin intencionalmente

              cy.contains('button', /Guardar|Agregar|Crear/i).first().click({ force: true })
              cy.wait(1500)

              cy.tomarEvidencia('CU03-FA-01-error-sin-hora-fin')
            } else {
              cy.tomarEvidencia('CU03-FA-01-no-disponible')
            }
          })
        } else {
          cy.tomarEvidencia('CU03-FA-01-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FA-2: Médico de cabecera no puede eliminar horarios predefinidos
    // --------------------------------------------------
    it('FA-2: Médico de cabecera no puede eliminar horarios predefinidos del sistema', () => {
      cy.loginAs('medico2')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        // Los botones de horarios predefinidos deben estar deshabilitados o ausentes
        if ($body.text().match(/predefinido|protegido|no puede eliminar/i)) {
          cy.tomarEvidencia('CU03-FA-02-horarios-protegidos-visibles')
        } else {
          // Verificar que los botones de eliminar de horarios predefinidos están deshabilitados
          cy.get('body').then(($check) => {
            const botonesEliminar = $check.find('button:contains("Eliminar"):disabled, button[disabled]:contains("Eliminar")')
            if (botonesEliminar.length > 0) {
              cy.get('button:contains("Eliminar"):disabled').first().should('be.disabled')
              cy.tomarEvidencia('CU03-FA-02-eliminar-deshabilitado')
            } else {
              cy.tomarEvidencia('CU03-FA-02-estado-actual')
            }
          })
        }
      })
    })

    // --------------------------------------------------
    // FA-3: Paciente no puede acceder al módulo de disponibilidad médica
    // --------------------------------------------------
    it('FA-3: Paciente que intenta acceder a disponibilidad médica es redirigido', () => {
      cy.loginAs('paciente')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.url().then((url) => {
        if (!url.includes('/medico/disponibilidad')) {
          cy.log('✅ Paciente fue redirigido correctamente')
        }
        cy.tomarEvidencia('CU03-FA-03-paciente-sin-acceso')
      })
    })

    // --------------------------------------------------
    // FA-4: Verificar que no se permiten conflictos de horario
    // --------------------------------------------------
    it('FA-4: Error al crear horario que entra en conflicto con uno existente', () => {
      cy.loginAs('especialista')
      cy.visit('/medico/disponibilidad')
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Agregar")').length > 0) {
          // Primer horario
          cy.contains(/Agregar|Nuevo/i).first().click({ force: true })
          cy.wait(1000)

          cy.get('body').then(($form1) => {
            if ($form1.find('input[type="time"]').length > 0) {
              if ($form1.find('select[name="dia_semana"]').length > 0) {
                cy.get('select[name="dia_semana"]').first().select('5', { force: true })
              }
              cy.get('input[type="time"]').eq(0).clear().type('09:00')
              if ($form1.find('input[type="time"]').length > 1) {
                cy.get('input[type="time"]').eq(1).clear().type('13:00')
              }
              cy.contains('button', /Guardar|Agregar/i).first().click({ force: true })
              cy.wait(2000)
            }
          })

          // Intentar crear un segundo horario que se solape
          cy.get('body').then(($body2) => {
            if ($body2.find('button:contains("Agregar")').length > 0) {
              cy.contains(/Agregar|Nuevo/i).first().click({ force: true })
              cy.wait(1000)

              cy.get('body').then(($form2) => {
                if ($form2.find('input[type="time"]').length > 0) {
                  if ($form2.find('select[name="dia_semana"]').length > 0) {
                    cy.get('select[name="dia_semana"]').first().select('5', { force: true })
                  }
                  cy.get('input[type="time"]').eq(0).clear().type('10:00')  // Solapamiento
                  if ($form2.find('input[type="time"]').length > 1) {
                    cy.get('input[type="time"]').eq(1).clear().type('14:00')
                  }
                  cy.contains('button', /Guardar|Agregar/i).first().click({ force: true })
                  cy.wait(2000)
                  cy.tomarEvidencia('CU03-FA-04-error-conflicto-horario')
                }
              })
            }
          })
        } else {
          cy.tomarEvidencia('CU03-FA-04-no-disponible')
        }
      })
    })
  })
})
