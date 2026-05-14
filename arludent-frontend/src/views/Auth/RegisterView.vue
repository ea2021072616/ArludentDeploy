<template>
  <AuthLayout>
    <Card title="Crear Cuenta">
      <form @submit.prevent="handleRegister" class="space-y-4">
        <!-- Username -->
        <InputField
          v-model="form.username"
          label="Nombre de Usuario"
          type="text"
          placeholder="usuario123"
          required
          :error="errors.username"
          :icon="User"
          autocomplete="username"
        />

        <!-- Email -->
        <InputField
          v-model="form.correo"
          label="Correo Electrónico"
          type="email"
          placeholder="correo@ejemplo.com"
          required
          :error="errors.correo"
          :icon="Mail"
          autocomplete="email"
        />

        <!-- Phone -->
        <InputField
          v-model="form.telefono"
          label="Teléfono (opcional)"
          type="tel"
          placeholder="+51 999 999 999"
          :error="errors.telefono"
          :icon="Phone"
          autocomplete="tel"
        />

        <!-- Password -->
        <InputField
          v-model="form.password"
          label="Contraseña"
          type="password"
          placeholder="••••••••"
          required
          :error="errors.password"
          :icon="Lock"
          helpText="Mínimo 8 caracteres, incluye mayúsculas, minúsculas y números"
          autocomplete="new-password"
        />

        <!-- Confirm Password -->
        <InputField
          v-model="form.password_confirmation"
          label="Confirmar Contraseña"
          type="password"
          placeholder="••••••••"
          required
          :error="errors.password_confirmation"
          :icon="Lock"
          autocomplete="new-password"
        />

        <!-- Consent Checkbox -->
        <div class="space-y-3">
          <!-- Privacy Policy Consent -->
          <div class="flex items-start">
            <div class="flex items-center h-5">
              <input
                id="privacy-consent"
                v-model="form.privacy_consent"
                type="checkbox"
                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 focus:ring-2"
                required
              />
            </div>
            <div class="ml-3 text-sm">
              <label for="privacy-consent" class="text-gray-700">
                Acepto que mis datos personales sean tratados de forma confidencial conforme a la
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium underline" @click.prevent="showPrivacyPolicy">
                  Ley N° 29733 - Ley de Protección de Datos Personales
                </a>
              </label>
            </div>
          </div>

          <!-- Terms and Conditions -->
          <div class="flex items-start">
            <div class="flex items-center h-5">
              <input
                id="terms-consent"
                v-model="form.terms_consent"
                type="checkbox"
                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 focus:ring-2"
                required
              />
            </div>
            <div class="ml-3 text-sm">
              <label for="terms-consent" class="text-gray-700">
                Acepto los
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium underline" @click.prevent="showTerms">
                  Términos y Condiciones de Uso
                </a>
              </label>
            </div>
          </div>

          <!-- Error message for consents -->
          <div v-if="errors.privacy_consent || errors.terms_consent" class="text-sm text-red-600">
            {{ errors.privacy_consent || errors.terms_consent }}
          </div>

          <!-- Info Box -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <div class="flex items-start">
              <ShieldCheck class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
              <div class="ml-3 text-sm text-blue-800">
                <p class="font-medium">Tus datos están protegidos</p>
                <p class="text-blue-700 mt-1">
                  Solo usaremos tu información para gestionar tus citas médicas, historial clínico y comunicación relacionada con el servicio.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <Button
          type="submit"
          variant="primary"
          :loading="isLoading"
          :icon="UserPlus"
          fullWidth
        >
          Registrarse
        </Button>

        <!-- Login Link -->
        <div class="text-center text-sm text-gray-600">
          ¿Ya tienes una cuenta?
          <router-link
            to="/login"
            class="text-primary-600 hover:text-primary-700 font-medium"
          >
            Inicia sesión aquí
          </router-link>
        </div>
      </form>
    </Card>
  </AuthLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useValidation } from '@/composables/useValidation'
import { useNotification } from '@/composables/useNotification'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Card from '@/components/common/Card.vue'
import InputField from '@/components/common/InputField.vue'
import Button from '@/components/common/Button.vue'
import { User, Mail, Phone, Lock, UserPlus, ShieldCheck } from 'lucide-vue-next'
import Swal from 'sweetalert2'

const { register } = useAuth()
const { showError } = useNotification()
const {
  errors,
  validateUsername,
  validateEmail,
  validatePassword,
  validatePasswordConfirmation,
  validatePhone,
  clearErrors,
  setErrors
} = useValidation()

const form = reactive({
  username: '',
  correo: '',
  telefono: '',
  password: '',
  password_confirmation: '',
  privacy_consent: false,
  terms_consent: false
})

const isLoading = ref(false)

/**
 * Mostrar modal de Política de Privacidad
 */
const showPrivacyPolicy = () => {
  Swal.fire({
    title: '🔒 Política de Privacidad',
    html: `
      <div class="text-left space-y-3">
        <h3 class="font-semibold text-lg">Ley N° 29733 - Ley de Protección de Datos Personales</h3>

        <p><strong>1. Responsable del tratamiento:</strong></p>
        <p>Arludent - Sistema de Gestión Odontológica</p>

        <p><strong>2. Finalidad del tratamiento:</strong></p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Gestión de citas médicas odontológicas</li>
          <li>Elaboración y almacenamiento de historiales clínicos</li>
          <li>Comunicación relacionada con el servicio médico</li>
          <li>Envío de recordatorios y notificaciones</li>
        </ul>

        <p><strong>3. Datos recopilados:</strong></p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Datos personales (nombre, correo, teléfono)</li>
          <li>Datos médicos (historial clínico, tratamientos)</li>
          <li>Datos de contacto y citas</li>
        </ul>

        <p><strong>4. Tus derechos:</strong></p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Acceso a tus datos personales</li>
          <li>Rectificación de datos incorrectos</li>
          <li>Cancelación de tus datos</li>
          <li>Oposición al tratamiento</li>
        </ul>

        <p><strong>5. Seguridad:</strong></p>
        <p>Tus datos están protegidos con cifrado y medidas de seguridad técnicas y organizativas.</p>

        <p><strong>6. Conservación:</strong></p>
        <p>Los datos médicos se conservarán según la normativa legal vigente (mínimo 5 años).</p>

        <p class="mt-4 text-sm text-gray-600">
          Al aceptar, declaras haber leído y entendido esta política.
        </p>
      </div>
    `,
    width: '600px',
    confirmButtonText: 'Entendido',
    confirmButtonColor: '#3b82f6',
    customClass: {
      popup: 'text-sm'
    }
  })
}

/**
 * Mostrar modal de Términos y Condiciones
 */
const showTerms = () => {
  Swal.fire({
    title: '📋 Términos y Condiciones',
    html: `
      <div class="text-left space-y-3">
        <h3 class="font-semibold text-lg">Condiciones de Uso del Sistema Arludent</h3>

        <p><strong>1. Aceptación de los términos:</strong></p>
        <p>Al registrarte, aceptas estos términos y te comprometes a cumplirlos.</p>

        <p><strong>2. Uso del servicio:</strong></p>
        <ul class="list-disc pl-5 space-y-1">
          <li>El servicio es exclusivo para fines médicos odontológicos</li>
          <li>Debes proporcionar información veraz y actualizada</li>
          <li>Eres responsable de mantener la confidencialidad de tu cuenta</li>
        </ul>

        <p><strong>3. Obligaciones del usuario:</strong></p>
        <ul class="list-disc pl-5 space-y-1">
          <li>No compartir tus credenciales de acceso</li>
          <li>Notificar cambios en tus datos personales</li>
          <li>Asistir puntualmente a tus citas o cancelarlas con anticipación</li>
          <li>Respetar las políticas del consultorio</li>
        </ul>

        <p><strong>4. Propiedad intelectual:</strong></p>
        <p>Todo el contenido del sistema está protegido por derechos de autor.</p>

        <p><strong>5. Limitación de responsabilidad:</strong></p>
        <p>El sistema es una herramienta de gestión. Los diagnósticos y tratamientos son responsabilidad del profesional médico.</p>

        <p><strong>6. Modificaciones:</strong></p>
        <p>Nos reservamos el derecho de modificar estos términos. Te notificaremos los cambios importantes.</p>

        <p><strong>7. Terminación:</strong></p>
        <p>Podemos suspender tu cuenta si incumples estos términos.</p>

        <p class="mt-4 text-sm text-gray-600">
          Última actualización: Octubre 2025
        </p>
      </div>
    `,
    width: '600px',
    confirmButtonText: 'Entendido',
    confirmButtonColor: '#3b82f6',
    customClass: {
      popup: 'text-sm'
    }
  })
}

const handleRegister = async () => {
  clearErrors()

  // Validaciones
  const isUsernameValid = validateUsername(form.username, 'username')
  const isEmailValid = validateEmail(form.correo, 'correo')
  const isPhoneValid = validatePhone(form.telefono, 'telefono')
  const isPasswordValid = validatePassword(form.password, 'password')
  const isConfirmationValid = validatePasswordConfirmation(
    form.password,
    form.password_confirmation,
    'password_confirmation'
  )

  // Validar consentimientos
  if (!form.privacy_consent) {
    showError('Debes aceptar la Política de Protección de Datos Personales')
    errors.value.privacy_consent = 'Este campo es obligatorio'
    return
  }

  if (!form.terms_consent) {
    showError('Debes aceptar los Términos y Condiciones de Uso')
    errors.value.terms_consent = 'Este campo es obligatorio'
    return
  }

  if (!isUsernameValid || !isEmailValid || !isPhoneValid || !isPasswordValid || !isConfirmationValid) {
    return
  }

  try {
    isLoading.value = true
    await register(form)
  } catch (error: any) {
    if (error.response?.status === 422) {
      setErrors(error.response.data.errors)
    }
  } finally {
    isLoading.value = false
  }
}
</script>
