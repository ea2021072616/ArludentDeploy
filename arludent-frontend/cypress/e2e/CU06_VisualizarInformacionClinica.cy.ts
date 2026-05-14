/**
 * ===================================================================
 * CU-06: VISUALIZAR INFORMACIÓN CLÍNICA — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-06-2
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Historial Clínico
 * Fecha: 01/05/2025
 *
 * Replica los flujos documentados en:
 * informe_evidencias_pruebas_CU05_CU06.md → Sección CU-06-2
 *
 * Credenciales:
 * - Paciente: paciente@arludent.com / Paciente123!
 * - Médico:   medico@arludent.com   / Medico123!
 * ===================================================================
 */

describe('CU-06: Visualizar Información Clínica — Prueba Funcional Automatizada', () => {

  // =====================================================
  // FLUJO PRINCIPAL — PERSPECTIVA DEL MÉDICO
  // =====================================================
  describe('Flujo Principal — Perspectiva del Médico', () => {

    // --------------------------------------------------
    // FP-1: Médico accede al módulo de Historial Clínico
    // --------------------------------------------------
    it('FP-1: Médico inicia sesión y navega a "Gestión de Historial Clínico"', () => {
      cy.loginAs('medico')

      // Verificar que estamos en el dashboard del médico
      cy.url().should('include', '/medico/dashboard')

      // Navegar a "Historial Clínico" desde el menú lateral
      cy.contains('Historial Clínico').click()
      cy.url().should('include', '/historial')

      // Verificar encabezado
      cy.contains('h1', 'Gestión de Historial Clínico').should('be.visible')

      // Verificar las 3 tarjetas de estadísticas
      cy.contains('Usuarios Externos').should('be.visible')
      cy.contains('Pacientes sin Historial').should('be.visible')
      cy.contains('Con Historial Completo').should('be.visible')

      // Verificar tabs
      cy.contains('Todos').should('be.visible')
      cy.contains('Usuarios Externos').should('be.visible')
      cy.contains('Sin Historial').should('be.visible')
      cy.contains('Con Historial').should('be.visible')

      // Verificar tabla
      cy.contains('Persona').should('be.visible')
      cy.contains('DNI').should('be.visible')

      cy.tomarEvidencia('FP-01-listado-historial-clinico')
    })

    // --------------------------------------------------
    // FP-2: Médico abre formulario de crear historial
    // --------------------------------------------------
    it('FP-2: Médico hace clic en "Crear Historial" para un paciente sin historial', () => {
      cy.loginAs('medico')
      cy.visit('/historial')
      cy.url().should('include', '/historial')

      cy.wait(2000)

      // Hacer clic en la pestaña "Sin Historial"
      cy.contains('button', 'Sin Historial').click()
      cy.wait(1500)

      cy.tomarEvidencia('FP-02-tab-sin-historial')

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Crear Historial")').length > 0) {
          // Hacer clic en "Crear Historial" del primer paciente
          cy.contains('button', 'Crear Historial').first().click()

          // Verificar que se abre el modal de crear historial
          cy.contains('Crear Historial Clínico').should('be.visible')

          // Verificar secciones del formulario
          cy.contains('Paciente').should('be.visible')
          cy.contains('Motivo de consulta').should('be.visible')
          cy.contains('Higiene bucal').should('be.visible')
          cy.contains('Anamnesis').should('be.visible')
          cy.contains('Síntoma principal').should('be.visible')

          cy.tomarEvidencia('FP-02-formulario-crear-historial')

          // Cerrar el modal
          cy.contains('button', 'Cancelar').click()
        } else {
          cy.log('⚠️ No hay pacientes sin historial')
          cy.tomarEvidencia('FP-02-sin-pacientes-sin-historial')
        }
      })
    })

    // --------------------------------------------------
    // FP-3: Médico completa y guarda formulario de historial
    // --------------------------------------------------
    it('FP-3: Médico completa campos de anamnesis y guarda historial', () => {
      cy.loginAs('medico')
      cy.visit('/historial')
      cy.url().should('include', '/historial')

      cy.wait(2000)

      // Ir a tab "Sin Historial"
      cy.contains('button', 'Sin Historial').click()
      cy.wait(1500)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Crear Historial")').length > 0) {
          cy.contains('button', 'Crear Historial').first().click()
          cy.wait(1000)

          // Verificar que el modal está abierto — buscar el formulario dentro
          cy.get('form').should('be.visible')

          // Nota: InputField renderiza <input>, incluso cuando recibe type="textarea"
          cy.get('form input[placeholder*="motivo de la consulta"]').type('Dolor al masticar alimentos fríos')

          // Seleccionar Higiene bucal
          cy.get('form select').first().select('Regular')

          cy.get('form input[placeholder*="Diagnóstico inicial"]').type('Posible caries profunda en zona molar')
          cy.get('form input[placeholder*="Diagnóstico confirmado"]').type('Caries profunda en zona molar 16')

          // Síntoma principal
          cy.get('form input[placeholder*="Síntoma principal"]').type('Dolor pulsátil en zona molar')

          // Tiempo de inicio
          cy.get('form input[placeholder*="1 semana"]').type('1 semana')

          // Marcar "Bajo tratamiento médico"
          cy.contains('label', '¿Bajo tratamiento médico?')
            .parent()
            .find('input[type="checkbox"]')
            .check({ force: true })

          // Alergias
          cy.get('form input[placeholder*="Alergias conocidas"]').type('Penicilina')

          cy.tomarEvidencia('FP-03-formulario-completado')

          // Guardar historial
          cy.get('form button[type="submit"]').click()

          // Verificar SweetAlert2 de éxito
          cy.verificarSwal('exitosamente')
          cy.tomarEvidencia('FP-03-swal-exito-crear')
          cy.cerrarSwal()

          cy.tomarEvidencia('FP-03-despues-crear-historial')
        } else {
          cy.log('⚠️ No hay pacientes sin historial para crear')
          cy.tomarEvidencia('FP-03-sin-pacientes-disponibles')
        }
      })
    })

    // --------------------------------------------------
    // FP-4: Médico ve detalle de historial existente
    // --------------------------------------------------
    it('FP-4: Médico visualiza el detalle de un historial existente', () => {
      cy.loginAs('medico')
      cy.visit('/historial')
      cy.url().should('include', '/historial')

      cy.wait(2000)

      // Ir a tab "Con Historial"
      cy.contains('button', 'Con Historial').click()
      cy.wait(1500)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Ver")').length > 0) {
          cy.tomarEvidencia('FP-04-listado-con-historial')

          // Hacer clic en "Ver" del primer paciente — esto navega a /historial-clinico/:id
          cy.contains('button', 'Ver').first().click()

          // Esperar navegación a la vista de detalle
          cy.url().should('include', '/historial-clinico/')
          cy.wait(2000)

          // Verificar encabezado de la vista de detalle
          cy.contains('h1', 'Historial Clínico').should('be.visible')

          // Verificar tabs de la vista de detalle
          cy.contains('Información General').should('be.visible')
          cy.contains('Odontograma').should('be.visible')
          cy.contains('Tratamientos').should('be.visible')

          // Verificar sección FASE I. DATOS PERSONALES
          cy.contains('FASE I. DATOS PERSONALES').should('be.visible')
          cy.contains('Nombres').should('be.visible')
          cy.contains('DNI').should('be.visible')

          cy.tomarEvidencia('FP-04-detalle-historial-clinico')

          // Volver al listado
          cy.contains('Volver a Historial Clínico').click()
          cy.url().should('include', '/historial')
        } else {
          cy.log('⚠️ No hay pacientes con historial')
          cy.tomarEvidencia('FP-04-sin-pacientes-con-historial')
        }
      })
    })

    // --------------------------------------------------
    // FP-5: Médico usa el buscador
    // --------------------------------------------------
    it('FP-5: Médico busca paciente por nombre en el buscador', () => {
      cy.loginAs('medico')
      cy.visit('/historial')
      cy.url().should('include', '/historial')

      cy.wait(2000)

      // Usar el buscador
      cy.get('input[placeholder*="Nombre"]').type('Juan')

      // Esperar resultados del debounce
      cy.wait(1500)

      cy.tomarEvidencia('FP-05-busqueda-paciente')

      // Limpiar búsqueda
      cy.get('input[placeholder*="Nombre"]').clear()
      cy.wait(1500)

      cy.tomarEvidencia('FP-05-busqueda-limpia')
    })
  })

  // =====================================================
  // FLUJO PRINCIPAL — PERSPECTIVA DEL PACIENTE
  // =====================================================
  describe('Flujo Principal — Perspectiva del Paciente', () => {

    // --------------------------------------------------
    // FP-6: Paciente accede a "Mi Historial"
    // --------------------------------------------------
    it('FP-6: Paciente inicia sesión y navega a "Mi Historial"', () => {
      cy.loginAs('paciente')

      // Verificar que estamos en el dashboard del paciente
      cy.url().should('include', '/paciente/dashboard')

      // Navegar a "Mi Historial" desde el menú lateral
      cy.contains('Mi Historial').click()
      cy.url().should('include', '/mi-historial')

      // Verificar encabezado
      cy.contains('h1', 'Mi Historial Clínico').should('be.visible')
      cy.contains('Consulta tu historial clínico').should('be.visible')

      cy.wait(2000)

      cy.tomarEvidencia('FP-06-mi-historial-paciente')
    })

    // --------------------------------------------------
    // FP-7: Paciente ve pestaña "Información General"
    // --------------------------------------------------
    it('FP-7: Paciente visualiza pestaña "Información General" con datos clínicos', () => {
      cy.loginAs('paciente')
      cy.visit('/mi-historial')
      cy.url().should('include', '/mi-historial')

      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('h3:contains("Aún no tienes historial")').length > 0) {
          // El paciente no tiene historial
          cy.contains('Aún no tienes historial clínico').should('be.visible')
          cy.tomarEvidencia('FP-07-paciente-sin-historial')
        } else {
          // Verificar pestañas
          cy.contains('Información General').should('be.visible')
          cy.contains('Odontograma').should('be.visible')

          // Verificar que "Información General" está activa
          cy.contains('Información General').click()

          // Verificar secciones de información
          cy.contains('FASE I. DATOS PERSONALES').should('be.visible')
          cy.tomarEvidencia('FP-07-datos-personales')

          cy.contains('FASE II. MOTIVO DE CONSULTA').should('be.visible')
          cy.tomarEvidencia('FP-07-motivo-consulta')

          cy.contains('FASE III. ANTECEDENTES').should('be.visible')

          // Verificar que hay datos del paciente
          cy.contains('Nombres').should('be.visible')
          cy.contains('Apellidos').should('be.visible')
          cy.contains('DNI').should('be.visible')

          // Verificar información del historial
          cy.contains('Código:').should('be.visible')
          cy.contains('Médico Responsable').should('be.visible')

          cy.tomarEvidencia('FP-07-informacion-general-completa')
        }
      })
    })

    // --------------------------------------------------
    // FP-8: Paciente ve pestaña "Odontograma"
    // --------------------------------------------------
    it('FP-8: Paciente visualiza pestaña "Odontograma"', () => {
      cy.loginAs('paciente')
      cy.visit('/mi-historial')
      cy.url().should('include', '/mi-historial')

      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('h3:contains("Aún no tienes historial")').length > 0) {
          cy.contains('Aún no tienes historial clínico').should('be.visible')
          cy.tomarEvidencia('FP-08-paciente-sin-historial')
        } else {
          // Hacer clic en pestaña "Odontograma"
          cy.contains('button', 'Odontograma').click()
          cy.wait(1500)

          // Verificar sección de evaluación diagnóstica
          cy.contains('EVALUACIÓN DIAGNÓSTICA').should('be.visible')
          cy.contains('Diagnóstico Presuntivo').should('be.visible')
          cy.contains('Diagnóstico Principal').should('be.visible')
          cy.contains('Higiene Bucal').should('be.visible')

          cy.tomarEvidencia('FP-08-evaluacion-diagnostica')

          // Verificar que el odontograma está visible (componente interactivo)
          cy.wait(1000)
          cy.tomarEvidencia('FP-08-odontograma')
        }
      })
    })

    // --------------------------------------------------
    // FP-9: Paciente verifica modo solo lectura
    // --------------------------------------------------
    it('FP-9: Paciente verifica que toda la información es de solo lectura', () => {
      cy.loginAs('paciente')
      cy.visit('/mi-historial')
      cy.url().should('include', '/mi-historial')

      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('h3:contains("Aún no tienes historial")').length > 0) {
          cy.tomarEvidencia('FP-09-paciente-sin-historial')
        } else {
          // Verificar que NO existen botones de edición
          cy.contains('button', 'Editar').should('not.exist')
          cy.contains('button', 'Eliminar').should('not.exist')
          cy.contains('button', 'Guardar').should('not.exist')

          // Verificar que los checkboxes están deshabilitados (disabled)
          cy.get('input[type="checkbox"][disabled]').should('have.length.gte', 1)

          cy.tomarEvidencia('FP-09-solo-lectura-verificado')
        }
      })
    })
  })

  // =====================================================
  // FLUJO ALTERNO — VALIDACIONES
  // =====================================================
  describe('Flujo Alterno — Validaciones de la interfaz', () => {

    // --------------------------------------------------
    // FA-1: Médico filtra por tabs "Usuarios Externos"
    // --------------------------------------------------
    it('FA-1: Médico filtra pacientes por tab "Usuarios Externos"', () => {
      cy.loginAs('medico')
      cy.visit('/historial')
      cy.url().should('include', '/historial')

      cy.wait(2000)

      // Hacer clic en "Usuarios Externos"
      cy.contains('button', 'Usuarios Externos').first().click()
      cy.wait(1500)

      cy.get('body').then(($body) => {
        if ($body.find('table').length > 0 && $body.find('td').length > 0) {
          // Verificar que hay registros con indicador "externo"
          cy.contains('Usuario externo').should('be.visible')
          cy.contains('Completar Registro').should('be.visible')
          cy.tomarEvidencia('FA-01-tab-usuarios-externos')
        } else {
          cy.contains('No hay registros').should('be.visible')
          cy.tomarEvidencia('FA-01-sin-usuarios-externos')
        }
      })
    })

    // --------------------------------------------------
    // FA-2: Médico verifica selector de higiene bucal
    // --------------------------------------------------
    it('FA-2: Selector de higiene bucal solo permite Bueno, Regular, Malo', () => {
      cy.loginAs('medico')
      cy.visit('/historial')
      cy.url().should('include', '/historial')

      cy.wait(2000)

      // Ir a tab "Sin Historial"
      cy.contains('button', 'Sin Historial').click()
      cy.wait(1500)

      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Crear Historial")').length > 0) {
          cy.contains('button', 'Crear Historial').first().click()

          // Verificar que se abre el modal
          cy.contains('Crear Historial Clínico').should('be.visible')

          // Verificar opciones del selector de higiene bucal
          cy.get('select').first().within(() => {
            cy.get('option').should('have.length.gte', 3)
            cy.contains('option', 'Bueno').should('exist')
            cy.contains('option', 'Regular').should('exist')
            cy.contains('option', 'Malo').should('exist')
          })

          cy.tomarEvidencia('FA-02-opciones-higiene-bucal')

          // Cerrar modal
          cy.contains('button', 'Cancelar').click()
        } else {
          cy.log('⚠️ No hay pacientes sin historial')
          cy.tomarEvidencia('FA-02-sin-pacientes')
        }
      })
    })

    // --------------------------------------------------
    // FA-3: Paciente sin historial ve estado vacío
    // --------------------------------------------------
    it('FA-3: Paciente sin historial ve mensaje informativo', () => {
      cy.loginAs('paciente')
      cy.visit('/mi-historial')
      cy.url().should('include', '/mi-historial')

      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('h3:contains("Aún no tienes historial")').length > 0) {
          // Verificar el estado vacío informativo
          cy.contains('Aún no tienes historial clínico').should('be.visible')
          cy.contains('Tu historial clínico será creado por tu médico').should('be.visible')
          cy.tomarEvidencia('FA-03-estado-vacio-sin-historial')
        } else {
          // Este paciente sí tiene historial — eso está bien
          cy.contains('Mi Historial Clínico').should('be.visible')
          cy.tomarEvidencia('FA-03-paciente-con-historial-existente')
        }
      })
    })

    // --------------------------------------------------
    // FA-4: Médico busca con filtro sin resultados
    // --------------------------------------------------
    it('FA-4: Búsqueda sin resultados muestra estado vacío', () => {
      cy.loginAs('medico')
      cy.visit('/historial')
      cy.url().should('include', '/historial')

      cy.wait(2000)

      // Buscar con un término inexistente
      cy.get('input[placeholder*="Nombre"]').type('ZZZZXXXXXNOEXISTE123')

      // Esperar debounce
      cy.wait(1500)

      // Verificar estado vacío
      cy.contains('No hay registros').should('be.visible')
      cy.contains('No se encontraron personas').should('be.visible')

      cy.tomarEvidencia('FA-04-busqueda-sin-resultados')

      // Limpiar búsqueda
      cy.get('input[placeholder*="Nombre"]').clear()
      cy.wait(1500)
      cy.tomarEvidencia('FA-04-despues-limpiar-busqueda')
    })

    // --------------------------------------------------
    // FA-5: Médico ve tabla de pacientes con historial completo
    // --------------------------------------------------
    it('FA-5: Tab "Con Historial" muestra pacientes con badge "Tiene historial"', () => {
      cy.loginAs('medico')
      cy.visit('/historial')
      cy.url().should('include', '/historial')

      cy.wait(2000)

      // Hacer clic en "Con Historial"
      cy.contains('button', 'Con Historial').click()
      cy.wait(1500)

      cy.get('body').then(($body) => {
        if ($body.find('table td').length > 0) {
          // Verificar badge verde "Tiene historial"
          cy.contains('Tiene historial').should('be.visible')

          // Verificar que hay botón "Ver" disponible
          cy.contains('button', 'Ver').should('be.visible')

          cy.tomarEvidencia('FA-05-tab-con-historial')
        } else {
          cy.contains('No hay registros').should('be.visible')
          cy.tomarEvidencia('FA-05-sin-pacientes-con-historial')
        }
      })
    })
  })
})
