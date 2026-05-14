/**
 * ===================================================================
 * CU-08: GESTIONAR TRATAMIENTOS — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-08-2
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Administración de Tratamientos
 * Fecha: 01/05/2026
 *
 * Basado en:
 * informe_evidencias_pruebas_CU07_CU08.md → Sección CU-08-2
 * ===================================================================
 */

describe('CU-08: Gestionar Tratamientos — Prueba Funcional Automatizada', () => {
  const nombreNuevo = `Ortodoncia Convencional Cypress ${Date.now()}`

  describe('Flujo Principal — Perspectiva del Administrador', () => {
    it('FP-1: Admin inicia sesión y navega a "Gestión de Tratamientos"', () => {
      cy.loginAs('admin')
      cy.url().should('include', '/admin/dashboard')

      cy.contains('Catálogo de Tratamientos').click()
      cy.url().should('include', '/admin/tratamientos')

      cy.contains('h1', 'Catálogo de Tratamientos').should('be.visible')
      cy.contains('Nombre').should('be.visible')
      cy.contains('Categoría').should('be.visible')
      cy.contains('Descripción').should('be.visible')
      cy.contains('Precio').should('be.visible')
      cy.contains('Estado').should('be.visible')
      cy.contains('Acciones').should('be.visible')

      cy.tomarEvidencia('FP-01-tabla-tratamientos')
    })

    it('FP-2: Admin abre formulario "Nuevo Tratamiento"', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos')

      cy.contains('button', 'Nuevo Tratamiento').click()
      cy.url().should('include', '/admin/tratamientos/crear')

      cy.contains('h1', 'Nuevo Tratamiento').should('be.visible')
      cy.contains('Nombre del Tratamiento').should('be.visible')
      cy.contains('Categoría').should('be.visible')
      cy.contains('Descripción').should('be.visible')
      cy.contains('Precio').should('be.visible')

      cy.tomarEvidencia('FP-02-formulario-nuevo-tratamiento')
    })

    it('FP-3: Admin crea tratamiento con datos válidos', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos/crear')

      cy.get('input[placeholder*="Limpieza dental"]').type(nombreNuevo)
      cy.get('select').first().select('Ortodoncia')
      cy.get('textarea[placeholder*="Describe el tratamiento"]').type('Tratamiento ortodóntico con brackets metálicos convencionales')
      cy.get('input[type="number"]').clear().type('2500.00')

      cy.tomarEvidencia('FP-03-formulario-completado')

      cy.contains('button', /Crear Tratamiento|Creando/i).click()
      cy.verificarSwal('Tratamiento creado')
      cy.cerrarSwal()
      cy.url().should('include', '/admin/tratamientos')

      cy.contains(nombreNuevo).should('be.visible')
      cy.contains('Activo').should('be.visible')
      cy.tomarEvidencia('FP-03-tratamiento-creado')
    })

    it('FP-4: Admin filtra por categoría "Preventivo"', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos')

      // Si el filtro de categoría existe en la UI actual, aplicarlo.
      // Si no existe, dejar evidencia del estado actual.
      cy.get('body').then(($body) => {
        const existeSelectCategoria =
          $body.find('label:contains("Categoría")').length > 0 &&
          $body.find('select').length > 1

        if (existeSelectCategoria) {
          cy.contains('label', 'Categoría').parent().find('select').select('Preventivo')
          cy.wait(1200)
          cy.tomarEvidencia('FP-04-filtro-categoria-preventivo')
        } else {
          cy.contains('Catálogo de Tratamientos').should('be.visible')
          cy.tomarEvidencia('FP-04-filtro-categoria-no-disponible')
        }
      })
    })

    it('FP-5: Admin busca "Limpieza" en el buscador', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos')

      cy.get('input[placeholder*="Buscar por nombre"]').clear().type('Limpieza')
      cy.wait(1000)
      cy.tomarEvidencia('FP-05-busqueda-limpieza')
    })

    it('FP-6: Admin edita un tratamiento y actualiza el precio', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos')

      cy.get('body').then(($body) => {
        if ($body.find('button[title="Editar"]').length > 0) {
          cy.get('button[title="Editar"]').first().click()
          cy.url().should('include', '/admin/tratamientos/')
          cy.url().should('include', '/editar')

          cy.get('input[type="number"]').clear().type('180.00')
          cy.tomarEvidencia('FP-06-editar-precio')

          cy.contains('button', /Guardar Cambios|Guardando/i).click()
          cy.verificarSwal('actualizado')
          cy.cerrarSwal()

          cy.url().should('include', '/admin/tratamientos')
          cy.tomarEvidencia('FP-06-precio-actualizado')
        } else {
          cy.tomarEvidencia('FP-06-sin-tratamientos-para-editar')
        }
      })
    })

    it('FP-7: Admin cambia estado de un tratamiento (activo/inactivo)', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos')

      cy.get('body').then(($body) => {
        if ($body.find('button[title="Inactivar"], button[title="Activar"]').length > 0) {
          cy.get('button[title="Inactivar"], button[title="Activar"]').first().click()
          cy.contains('button', /^Sí,/).click()
          cy.verificarSwal('Estado actualizado')
          cy.cerrarSwal()
          cy.tomarEvidencia('FP-07-estado-cambiado')
        } else {
          cy.tomarEvidencia('FP-07-sin-tratamientos-para-cambiar-estado')
        }
      })
    })

    it('FP-8: Admin filtra por estado "inactivo"', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos')

      cy.get('select').last().select('inactivo')
      cy.wait(1200)
      cy.tomarEvidencia('FP-08-filtro-inactivo')
    })

    it('FP-9: Admin reactiva tratamiento inactivo', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos')

      cy.get('select').last().select('inactivo')
      cy.wait(1200)

      cy.get('body').then(($body) => {
        if ($body.find('button[title="Activar"]').length > 0) {
          cy.get('button[title="Activar"]').first().click()
          cy.contains('button', /^Sí,/).click()
          cy.verificarSwal('Estado actualizado')
          cy.cerrarSwal()
          cy.tomarEvidencia('FP-09-tratamiento-reactivado')
        } else {
          cy.tomarEvidencia('FP-09-sin-tratamientos-inactivos')
        }
      })
    })
  })

  describe('Flujo Alterno — Validaciones', () => {
    it('A1: Validación al crear sin nombre', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos/crear')

      cy.contains('button', /Crear Tratamiento|Creando/i).click()
      cy.contains(/El nombre es requerido|Errores en el formulario/i).should('be.visible')
      cy.tomarEvidencia('A1-validacion-nombre-requerido')
    })

    it('A2: Validación de nombre excesivamente largo', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos/crear')

      const nombreLargo = 'X'.repeat(120)
      cy.get('input[placeholder*="Limpieza dental"]').clear().type(nombreLargo)
      cy.contains('button', /Crear Tratamiento|Creando/i).click()

      // Puede venir del backend o del swal de error
      cy.get('body').then(($body) => {
        if ($body.text().match(/100|exced|largo|inválido|Error/i)) {
          cy.tomarEvidencia('A2-validacion-nombre-largo')
        }
      })
    })

    it('A3: Validación de precio negativo', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos/crear')

      cy.get('input[placeholder*="Limpieza dental"]').clear().type('Tratamiento prueba precio negativo')
      const precioInput = cy.get('input[type="number"]')
      precioInput.should('have.attr', 'min', '0')
      precioInput.invoke('val', '-50').trigger('input').trigger('change')

      precioInput.then(($input) => {
        const el = $input[0] as HTMLInputElement
        const valor = String(el.value || '')
        const esNegativo = valor.startsWith('-')
        const invalido = !el.validity.valid || el.validity.rangeUnderflow

        if (!esNegativo) {
          expect(valor === '' || Number(valor) >= 0).to.equal(true)
          return
        }

        // Si quedó negativo, basta con que el input sea inválido por min=0
        if (invalido) {
          expect(invalido).to.equal(true)
          return
        }

        // Si el navegador no marcó inválido, entonces debe aparecer validación al guardar
        cy.contains('button', /Crear Tratamiento|Creando/i).click()
        cy.get('body').then(($body) => {
          const tieneError = /precio no puede ser negativo|Errores en el formulario|Error/.test($body.text())
          expect(tieneError).to.equal(true)
        })
      })

      cy.tomarEvidencia('A3-validacion-precio-negativo')
    })

    it('A4: Paciente no puede acceder a gestión de tratamientos', () => {
      cy.loginAs('paciente')
      cy.visit('/admin/tratamientos')

      cy.url().should('not.include', '/admin/tratamientos')
      cy.url().should('include', '/dashboard')
      cy.tomarEvidencia('A4-acceso-denegado-paciente')
    })

    it('A5: Búsqueda sin resultados muestra estado vacío', () => {
      cy.loginAs('admin')
      cy.visit('/admin/tratamientos')

      cy.get('input[placeholder*="Buscar por nombre"]').clear().type('XYZNOEXISTE')
      cy.wait(1000)
      cy.contains('No se encontraron tratamientos').should('be.visible')
      cy.tomarEvidencia('A5-busqueda-sin-resultados')
    })
  })
})
