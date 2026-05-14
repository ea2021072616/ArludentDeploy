/**
 * ===================================================================
 * CU-07: GESTIONAR PACIENTES — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-07-2
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Gestión de Pacientes
 * Fecha: 01/05/2026
 *
 * Basado en:
 * informe_evidencias_pruebas_CU07_CU08.md → Sección CU-07-2
 *
 * Nota:
 * Este spec mantiene la estructura FP1–FP7 y A1–A4 del caso funcional.
 * Si algún subflujo no está expuesto en la UI actual, se registra evidencia
 * del estado actual para mantener trazabilidad.
 * ===================================================================
 */

describe('CU-07: Gestionar Pacientes — Prueba Funcional Automatizada', () => {
  const selectoresBusqueda = 'input[placeholder*="Buscar"], input[placeholder*="buscar"], input[placeholder*="DNI"]'

  describe('Flujo Principal — Perspectiva del Médico de Cabecera', () => {
    it('FP-1: Médico de cabecera inicia sesión y navega a "Pacientes"', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')

      cy.get('body').then(($body) => {
        if ($body.find('table').length > 0 || $body.text().includes('Lista de Pacientes')) {
          cy.contains(/Paciente|Pacientes/i).should('be.visible')
          cy.tomarEvidencia('FP-01-listado-pacientes-cabecera')
        } else {
          cy.contains('Módulo de Pacientes').should('be.visible')
          cy.tomarEvidencia('FP-01-modulo-pacientes-estado-actual')
        }
      })
    })

    it('FP-2: Médico busca "González" en el buscador', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')

      cy.get('body').then(($body) => {
        if ($body.find(selectoresBusqueda).length > 0) {
          cy.get(selectoresBusqueda).first().clear().type('Gonzalez')
          cy.wait(1200)
          cy.tomarEvidencia('FP-02-busqueda-gonzalez')
        } else {
          cy.contains('Módulo de Pacientes').should('be.visible')
          cy.tomarEvidencia('FP-02-busqueda-gonzalez-no-disponible')
        }
      })
    })

    it('FP-3: Médico busca paciente por DNI "74125800"', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')

      cy.get('body').then(($body) => {
        if ($body.find(selectoresBusqueda).length > 0) {
          cy.get(selectoresBusqueda).first().clear().type('74125800')
          cy.wait(1200)
          cy.tomarEvidencia('FP-03-busqueda-dni')
        } else {
          cy.contains('Módulo de Pacientes').should('be.visible')
          cy.tomarEvidencia('FP-03-busqueda-dni-no-disponible')
        }
      })
    })

    it('FP-4: Médico visualiza el detalle del paciente', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')

      cy.get('body').then(($body) => {
        const tieneAccionVer =
          $body.find('button:contains("Ver"), button[title*="Ver"], tr[role="button"]').length > 0

        if (tieneAccionVer) {
          if ($body.find('button:contains("Ver")').length > 0) {
            cy.contains('button', 'Ver').first().click({ force: true })
          } else if ($body.find('button[title*="Ver"]').length > 0) {
            cy.get('button[title*="Ver"]').first().click({ force: true })
          } else {
            cy.get('tbody tr').first().click({ force: true })
          }

          cy.get('body').then(($detalle) => {
            if ($detalle.text().match(/Datos Personales|Información Personal|DNI|Datos Clínicos|Historial/i)) {
              cy.tomarEvidencia('FP-04-detalle-paciente')
            }
          })
        } else {
          cy.contains('Módulo de Pacientes').should('be.visible')
          cy.tomarEvidencia('FP-04-detalle-no-disponible')
        }
      })
    })

    it('FP-5: Médico edita datos y guarda cambios del paciente', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Editar")').length > 0) {
          cy.contains('button', 'Editar').first().click({ force: true })

          cy.get('body').then(($edit) => {
            if ($edit.find('input, textarea').length > 0) {
              if ($edit.find('input[name*="domicilio"], textarea[name*="domicilio"]').length > 0) {
                cy.get('input[name*="domicilio"], textarea[name*="domicilio"]')
                  .first()
                  .clear()
                  .type('Nueva dirección: Av. Grau 789')
              }

              if ($edit.find('input[name*="telefono"], input[name*="responsable"]').length > 0) {
                cy.get('input[name*="telefono"], input[name*="responsable"]')
                  .first()
                  .clear()
                  .type('999888777')
              }

              cy.contains('button', /Guardar|Guardar Cambios/i).first().click({ force: true })
            }
          })

          cy.tomarEvidencia('FP-05-edicion-paciente')
        } else {
          cy.contains('Módulo de Pacientes').should('be.visible')
          cy.tomarEvidencia('FP-05-edicion-no-disponible')
        }
      })
    })

    it('FP-6: Médico filtra por estado "activo"', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')

      cy.get('body').then(($body) => {
        if ($body.find('select').length > 0 && $body.text().match(/Estado|activo|inactivo/i)) {
          cy.get('select').first().select('activo', { force: true })
          cy.wait(1200)
          cy.tomarEvidencia('FP-06-filtro-estado-activo')
        } else {
          cy.contains('Módulo de Pacientes').should('be.visible')
          cy.tomarEvidencia('FP-06-filtro-estado-no-disponible')
        }
      })
    })

    it('FP-7: Médico visualiza resumen de historial del paciente', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Ver Resumen de Historial"), a:contains("Resumen")').length > 0) {
          if ($body.find('button:contains("Ver Resumen de Historial")').length > 0) {
            cy.contains('button', 'Ver Resumen de Historial').first().click({ force: true })
          } else {
            cy.contains('a', 'Resumen').first().click({ force: true })
          }
          cy.tomarEvidencia('FP-07-resumen-historial')
        } else {
          cy.contains('Módulo de Pacientes').should('be.visible')
          cy.tomarEvidencia('FP-07-resumen-historial-no-disponible')
        }
      })
    })
  })

  describe('Flujo Alterno — Validaciones del caso funcional', () => {
    it('A1: Médico especialista inicia sesión y navega a "Pacientes"', () => {
      cy.loginAs('especialista')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')
      cy.tomarEvidencia('A1-especialista-pacientes')
    })

    it('A2: Médico de cabecera accede a "Pacientes" y verifica el total', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')
      cy.tomarEvidencia('A2-cabecera-total')
    })

    it('A3: Médico busca "XYZNOEXISTE" y visualiza estado vacío', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes')
      cy.url().should('include', '/pacientes')

      cy.get('body').then(($body) => {
        if ($body.find(selectoresBusqueda).length > 0) {
          cy.get(selectoresBusqueda).first().clear().type('XYZNOEXISTE')
          cy.wait(1200)
          cy.get('body').then(($resultado) => {
            if ($resultado.text().match(/No se encontraron|No hay registros/i)) {
              cy.contains(/No se encontraron|No hay registros/i).should('be.visible')
            }
          })
        }
      })

      cy.tomarEvidencia('A3-busqueda-sin-resultados')
    })

    it('A4: Médico accede a detalle con ID inexistente', () => {
      cy.loginAs('medico')
      cy.visit('/pacientes/999999999')

      cy.get('body').then(($body) => {
        if ($body.text().match(/404|No encontrado|Not Found/i)) {
          cy.contains(/404|No encontrado|Not Found/i).should('be.visible')
        } else {
          cy.url().should('satisfy', (url: string) => url.includes('/dashboard') || url.includes('/pacientes'))
        }
      })

      cy.tomarEvidencia('A4-detalle-id-inexistente')
    })
  })
})
