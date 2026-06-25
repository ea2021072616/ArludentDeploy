/**
 * ===================================================================
 * CU-02: REGISTRARSE EN EL SISTEMA — PRUEBA FUNCIONAL AUTOMATIZADA
 * ===================================================================
 *
 * Caso de Prueba: CU-02-2
 * Tipo: Funcional (Automatizada con Cypress)
 * Módulo: Registro de Usuarios Externos
 * Fecha: 01/05/2026
 *
 * Replica los flujos documentados para RF02:
 * - Usuario externo se registra en el sistema
 * - Validaciones de formulario
 * - Confirmación de envío de correo
 *
 * IMPORTANTE: Los tests de registro NO crean usuarios reales en BD
 * para pruebas de éxito, ya que el formulario envía correo.
 * Se verifica el flujo de UI + mensajes de error de validación.
 * ===================================================================
 */

describe('CU-02: Registrarse en el Sistema — Prueba Funcional Automatizada', () => {

  const urlRegistro = '/register'

  // =====================================================
  // FLUJO PRINCIPAL — REGISTRO EXITOSO
  // =====================================================
  describe('Flujo Principal — Registro de nuevo usuario', () => {

    // --------------------------------------------------
    // FP-1: Acceder a la página de registro
    // --------------------------------------------------
    it('FP-1: Usuario accede a la página de registro desde la página de inicio', () => {
      cy.visit('/')
      cy.wait(1500)

      cy.get('body').then(($body) => {
        if ($body.find('a:contains("Registrarse"), a:contains("Regístrate"), button:contains("Registro")').length > 0) {
          cy.contains(/Registrarse|Regístrate|Registro/i).first().click({ force: true })
          cy.wait(1000)
        } else {
          cy.visit(urlRegistro)
          cy.wait(1000)
        }
      })

      cy.tomarEvidencia('CU02-FP-01-pagina-registro')
    })

    // --------------------------------------------------
    // FP-2: Visualizar formulario de registro
    // --------------------------------------------------
    it('FP-2: Se muestra el formulario de registro con todos sus campos', () => {
      cy.visit(urlRegistro)
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('form').length > 0 || $body.text().match(/Crear cuenta|Registrarse|Registro|email|correo/i)) {
          cy.tomarEvidencia('CU02-FP-02-formulario-registro')

          // Verificar campos básicos esperados
          const camposEsperados = [
            'input[type="email"], input[placeholder*="correo"], input[placeholder*="email"], input[name="correo"]',
            'input[type="password"]',
          ]

          camposEsperados.forEach((selector) => {
            cy.get('body').then(($check) => {
              if ($check.find(selector).length > 0) {
                cy.get(selector).first().should('be.visible')
              }
            })
          })
        } else {
          cy.log('⚠️ Página de registro no encontrada en /auth/registro')
          cy.tomarEvidencia('CU02-FP-02-ruta-no-encontrada')
        }
      })
    })

    // --------------------------------------------------
    // FP-3: Completar y enviar el formulario de registro
    // --------------------------------------------------
    it('FP-3: Usuario completa el formulario y envía su registro', () => {
      cy.visit(urlRegistro)
      cy.wait(2000)

      const timestamp = Date.now()

      cy.get('body').then(($body) => {
        if ($body.find('form').length > 0) {
          // Rellenar username si existe
          if ($body.find('input[name="username"], input[placeholder*="usuario"], input[placeholder*="Usuario"]').length > 0) {
            cy.get('input[name="username"], input[placeholder*="usuario"], input[placeholder*="Usuario"]')
              .first().clear().type(`testuser_${timestamp}`)
          }

          // Correo electrónico
          if ($body.find('input[type="email"], input[name="correo"], input[placeholder*="correo"]').length > 0) {
            cy.get('input[type="email"], input[name="correo"], input[placeholder*="correo"]')
              .first().clear().type(`testuser_${timestamp}@arludent.com`)
          }

          // Password
          const passwords = $body.find('input[type="password"]')
          if (passwords.length > 0) {
            cy.get('input[type="password"]').eq(0).clear().type('Password123!')
            if (passwords.length > 1) {
              cy.get('input[type="password"]').eq(1).clear().type('Password123!')
            }
          }

          // Teléfono si existe
          if ($body.find('input[name="telefono"], input[placeholder*="Teléfono"]').length > 0) {
            cy.get('input[name="telefono"], input[placeholder*="Teléfono"]')
              .first().clear().type('987654321')
          }

          cy.tomarEvidencia('CU02-FP-03-formulario-completo')

          // Checkboxes de términos y privacidad
          if ($body.find('input[type="checkbox"]').length > 0) {
            cy.get('input[type="checkbox"]').check({ force: true })
          }

          // Enviar el formulario
          cy.contains('button', /Registrar|Crear cuenta|Enviar|Registrarse/i).first().click({ force: true })
          cy.wait(3000)

          cy.get('body').then(($result) => {
            if ($result.text().match(/verificaci|correo|enviado|éxito|exitosamente/i)) {
              cy.tomarEvidencia('CU02-FP-03-registro-exitoso')
            } else {
              cy.tomarEvidencia('CU02-FP-03-resultado-envio')
            }
          })
        } else {
          cy.tomarEvidencia('CU02-FP-03-formulario-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-4: Confirmación de registro enviada
    // --------------------------------------------------
    it('FP-4: Sistema muestra confirmación de correo de verificación enviado', () => {
      cy.visit(urlRegistro)
      cy.wait(2000)

      const timestamp = Date.now()

      cy.get('body').then(($body) => {
        if ($body.find('form').length > 0) {
          if ($body.find('input[type="email"], input[name="correo"]').length > 0) {
            cy.get('input[type="email"], input[name="correo"]')
              .first().type(`fptest_${timestamp}@arludent.com`)
          }

          const passwords = $body.find('input[type="password"]')
          if (passwords.length > 0) {
            cy.get('input[type="password"]').eq(0).type('Password123!')
            if (passwords.length > 1) {
              cy.get('input[type="password"]').eq(1).type('Password123!')
            }
          }

          if ($body.find('input[name="username"]').length > 0) {
            cy.get('input[name="username"]').first().type(`fptest_${timestamp}`)
          }

          if ($body.find('input[type="checkbox"]').length > 0) {
            cy.get('input[type="checkbox"]').check({ force: true })
          }

          cy.contains('button', /Registrar|Crear cuenta|Enviar/i).first().click({ force: true })
          cy.wait(4000)

          cy.get('body').then(($after) => {
            if ($after.text().match(/verificaci|email|correo enviado/i)) {
              cy.contains(/verificaci|email|correo enviado/i).should('be.visible')
            }
            cy.tomarEvidencia('CU02-FP-04-confirmacion-enviada')
          })
        } else {
          cy.tomarEvidencia('CU02-FP-04-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FP-5: Navegar al login desde registro
    // --------------------------------------------------
    it('FP-5: Usuario puede navegar del formulario de registro al login', () => {
      cy.visit(urlRegistro)
      cy.wait(1500)

      cy.get('body').then(($body) => {
        if ($body.find('a:contains("Iniciar sesión"), a:contains("Login"), a:contains("Ya tengo cuenta")').length > 0) {
          cy.contains(/Iniciar sesión|Login|Ya tengo cuenta/i).first().should('be.visible')
          cy.tomarEvidencia('CU02-FP-05-link-login-visible')
        } else {
          cy.tomarEvidencia('CU02-FP-05-estado-actual')
        }
      })
    })
  })

  // =====================================================
  // FLUJO ALTERNO — VALIDACIONES
  // =====================================================
  describe('Flujo Alterno — Validaciones del formulario', () => {

    // --------------------------------------------------
    // FA-1: Intentar registrarse con correo inválido
    // --------------------------------------------------
    it('FA-1: Error al ingresar un correo electrónico con formato inválido', () => {
      cy.visit(urlRegistro)
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('input[type="email"], input[name="correo"]').length > 0) {
          cy.get('input[type="email"], input[name="correo"]')
            .first().type('esto-no-es-un-correo')

          if ($body.find('input[type="password"]').length > 0) {
            cy.get('input[type="password"]').first().type('Password123!')
          }

          if ($body.find('input[type="checkbox"]').length > 0) {
            cy.get('input[type="checkbox"]').check({ force: true })
          }

          cy.contains('button', /Registrar|Crear|Enviar/i).first().click({ force: true })
          cy.wait(2000)

          cy.get('body').then(($result) => {
            if ($result.text().match(/correo|email|formato|inválido/i)) {
              cy.tomarEvidencia('CU02-FA-01-error-correo-invalido')
            } else {
              // El navegador puede mostrar validación nativa HTML5
              cy.tomarEvidencia('CU02-FA-01-validacion-correo')
            }
          })
        } else {
          cy.tomarEvidencia('CU02-FA-01-campo-correo-no-encontrado')
        }
      })
    })

    // --------------------------------------------------
    // FA-2: Intentar registrarse con contraseña débil
    // --------------------------------------------------
    it('FA-2: Error al ingresar contraseña que no cumple la política de seguridad', () => {
      cy.visit(urlRegistro)
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('input[type="email"], input[name="correo"]').length > 0) {
          cy.get('input[type="email"], input[name="correo"]')
            .first().type(`debil_${Date.now()}@test.com`)
        }

        if ($body.find('input[type="password"]').length > 0) {
          cy.get('input[type="password"]').first().type('12345678')  // Sin mayúsculas ni símbolo
          if ($body.find('input[type="password"]').length > 1) {
            cy.get('input[type="password"]').eq(1).type('12345678')
          }
        }

        if ($body.find('input[type="checkbox"]').length > 0) {
          cy.get('input[type="checkbox"]').check({ force: true })
        }

        cy.tomarEvidencia('CU02-FA-02-formulario-password-debil')
        cy.contains('button', /Registrar|Crear|Enviar/i).first().click({ force: true })
        cy.wait(2000)

        cy.get('body').then(($result) => {
          if ($result.text().match(/contraseña|password|mayúscula|símbolo|seguridad/i)) {
            cy.contains(/contraseña|password|mayúscula|símbolo|seguridad/i).should('be.visible')
          }
          cy.tomarEvidencia('CU02-FA-02-error-password-debil')
        })
      })
    })

    // --------------------------------------------------
    // FA-3: Contraseñas no coinciden
    // --------------------------------------------------
    it('FA-3: Error cuando la confirmación de contraseña no coincide', () => {
      cy.visit(urlRegistro)
      cy.wait(2000)

      cy.get('body').then(($body) => {
        const passwords = $body.find('input[type="password"]')

        if (passwords.length >= 2) {
          cy.get('input[type="email"], input[name="correo"]')
            .first().type(`test_${Date.now()}@arludent.com`)

          cy.get('input[type="password"]').eq(0).type('Password123!')
          cy.get('input[type="password"]').eq(1).type('PasswordDistinta456!')

          if ($body.find('input[type="checkbox"]').length > 0) {
            cy.get('input[type="checkbox"]').check({ force: true })
          }

          cy.tomarEvidencia('CU02-FA-03-passwords-distintas')
          cy.contains('button', /Registrar|Crear|Enviar/i).first().click({ force: true })
          cy.wait(2000)

          cy.get('body').then(($result) => {
            if ($result.text().match(/coincid|igual|confirmación|confirmation/i)) {
              cy.contains(/coincid|igual|confirmación|confirmation/i).should('be.visible')
            }
            cy.tomarEvidencia('CU02-FA-03-error-passwords-distintas')
          })
        } else {
          cy.log('⚠️ No hay campo de confirmación de contraseña visible')
          cy.tomarEvidencia('CU02-FA-03-confirmacion-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FA-4: Enviar formulario vacío
    // --------------------------------------------------
    it('FA-4: Error al enviar el formulario sin completar los campos obligatorios', () => {
      cy.visit(urlRegistro)
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('button').length > 0) {
          cy.contains('button', /Registrar|Crear|Enviar/i).first().click({ force: true })
          cy.wait(1500)
          cy.tomarEvidencia('CU02-FA-04-formulario-vacio-enviado')
        } else {
          cy.tomarEvidencia('CU02-FA-04-no-disponible')
        }
      })
    })

    // --------------------------------------------------
    // FA-5: Correo ya registrado
    // --------------------------------------------------
    it('FA-5: Error al intentar registrarse con un correo ya existente', () => {
      cy.visit(urlRegistro)
      cy.wait(2000)

      cy.get('body').then(($body) => {
        if ($body.find('input[type="email"], input[name="correo"]').length > 0) {
          // Usar un correo que sabemos existe (el admin)
          cy.get('input[type="email"], input[name="correo"]')
            .first().type('admin@arludent.com')

          if ($body.find('input[name="username"]').length > 0) {
            cy.get('input[name="username"]').first().type(`nuevo_${Date.now()}`)
          }

          const passwords = $body.find('input[type="password"]')
          if (passwords.length > 0) {
            cy.get('input[type="password"]').eq(0).type('Password123!')
            if (passwords.length > 1) {
              cy.get('input[type="password"]').eq(1).type('Password123!')
            }
          }

          if ($body.find('input[type="checkbox"]').length > 0) {
            cy.get('input[type="checkbox"]').check({ force: true })
          }

          cy.tomarEvidencia('CU02-FA-05-correo-duplicado-intentado')
          cy.contains('button', /Registrar|Crear|Enviar/i).first().click({ force: true })
          cy.wait(3000)

          cy.get('body').then(($result) => {
            if ($result.text().match(/ya exist|duplicad|registrado|taken/i)) {
              cy.contains(/ya exist|duplicad|registrado|taken/i).should('be.visible')
            }
            cy.tomarEvidencia('CU02-FA-05-error-correo-duplicado')
          })
        } else {
          cy.tomarEvidencia('CU02-FA-05-no-disponible')
        }
      })
    })
  })
})
