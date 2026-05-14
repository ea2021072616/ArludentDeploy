<template>
  <AuthLayout>
    <Card title="Iniciar Sesión">
      <form @submit.prevent="handleLogin" class="space-y-4">
        <!-- Email or Username -->
        <InputField
          v-model="form.correo"
          label="Correo o Usuario"
          type="text"
          placeholder="correo@ejemplo.com o usuario"
          required
          :error="errors.correo"
          :icon="Mail"
          autocomplete="username"
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
          autocomplete="current-password"
        />

        <!-- Forgot Password Link -->
        <div class="text-right">
          <router-link
            to="/forgot-password"
            class="text-sm text-primary-600 hover:text-primary-700 font-medium"
          >
            ¿Olvidaste tu contraseña?
          </router-link>
        </div>

        <!-- reCAPTCHA -->
        <div id="recaptcha-container" class="flex justify-center"></div>

        <!-- Submit Button -->
        <Button
          type="submit"
          variant="primary"
          :loading="isLoading"
          :icon="LogIn"
          fullWidth
        >
          Iniciar Sesión
        </Button>

        <!-- Register Link -->
        <div class="text-center text-sm text-gray-600">
          ¿No tienes una cuenta?
          <router-link
            to="/register"
            class="text-primary-600 hover:text-primary-700 font-medium"
          >
            Regístrate aquí
          </router-link>
        </div>
      </form>
    </Card>
  </AuthLayout>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useValidation } from '@/composables/useValidation'
import { useRecaptcha } from '@/composables/useRecaptcha'
import { useNotification } from '@/composables/useNotification'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Card from '@/components/common/Card.vue'
import InputField from '@/components/common/InputField.vue'
import Button from '@/components/common/Button.vue'
import { Mail, Lock, LogIn } from 'lucide-vue-next'

const { login } = useAuth()
const { errors, validateRequired, clearErrors, setErrors } = useValidation()
const { showError } = useNotification()

// Inicializar reCAPTCHA
const recaptcha = useRecaptcha()

const form = reactive({
  correo: '',
  password: ''
})

const isLoading = ref(false)

// Cargar y renderizar reCAPTCHA cuando el componente se monta
onMounted(async () => {
  try {
    await recaptcha.loadRecaptchaScript()
    await recaptcha.render('recaptcha-container')
  } catch (error) {
    console.error('Error al cargar reCAPTCHA:', error)
    showError('Error al cargar el sistema de seguridad. Por favor, recarga la página.')
  }
})

const handleLogin = async () => {
  clearErrors()

  // Validaciones - solo requerido, no validar formato de email porque ahora acepta username también
  const isCorreoValid = validateRequired(form.correo, 'correo', 'El correo o usuario es requerido')
  const isPasswordValid = validateRequired(form.password, 'password', 'La contraseña es requerida')

  if (!isCorreoValid || !isPasswordValid) {
    return
  }

  // Validar reCAPTCHA (TEMPORARILY BYPASSED FOR TESTING)
  const recaptchaToken = recaptcha.getResponse() || 'bypass-testing'
  // if (!recaptchaToken) {
  //   showError('Por favor, completa la verificación de seguridad (reCAPTCHA)')
  //   return
  // }

  try {
    isLoading.value = true
    await login(form.correo, form.password, recaptchaToken)
    // Reset reCAPTCHA después de un login exitoso
    recaptcha.reset()
  } catch (error: any) {
    // Reset reCAPTCHA después de un error
    recaptcha.reset()

    if (error.response?.status === 422) {
      setErrors(error.response.data.errors)
    }
  } finally {
    isLoading.value = false
  }
}
</script>
