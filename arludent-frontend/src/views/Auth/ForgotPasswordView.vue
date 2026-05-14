<template>
  <AuthLayout>
    <Card title="Recuperar Contraseña">
      <div v-if="emailSent" class="text-center space-y-4">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
          <MailCheck class="w-10 h-10 text-green-600" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900">¡Correo Enviado!</h3>
        <p class="text-gray-600">
          Hemos enviado un enlace de recuperación a <strong>{{ form.correo }}</strong>.
          Por favor, revisa tu bandeja de entrada.
        </p>
        <Button
          @click="goToLogin"
          variant="outline"
          fullWidth
        >
          Volver al inicio de sesión
        </Button>
      </div>

      <form v-else @submit.prevent="handleForgotPassword" class="space-y-4">
        <p class="text-sm text-gray-600">
          Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
        </p>

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

        <!-- Submit Button -->
        <Button
          type="submit"
          variant="primary"
          :loading="isLoading"
          :icon="Send"
          fullWidth
        >
          Enviar Enlace de Recuperación
        </Button>

        <!-- Back to Login -->
        <div class="text-center">
          <router-link
            to="/login"
            class="text-sm text-gray-600 hover:text-gray-900"
          >
            Volver al inicio de sesión
          </router-link>
        </div>
      </form>
    </Card>
  </AuthLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useValidation } from '@/composables/useValidation'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Card from '@/components/common/Card.vue'
import InputField from '@/components/common/InputField.vue'
import Button from '@/components/common/Button.vue'
import { Mail, Send, MailCheck } from 'lucide-vue-next'

const router = useRouter()
const { requestPasswordReset } = useAuth()
const { errors, validateEmail, clearErrors, setErrors } = useValidation()

const form = reactive({
  correo: ''
})

const isLoading = ref(false)
const emailSent = ref(false)

const handleForgotPassword = async () => {
  clearErrors()

  if (!validateEmail(form.correo, 'correo')) {
    return
  }

  try {
    isLoading.value = true
    await requestPasswordReset(form.correo)
    emailSent.value = true
  } catch (error: any) {
    if (error.response?.status === 422) {
      setErrors(error.response.data.errors)
    }
  } finally {
    isLoading.value = false
  }
}

const goToLogin = () => {
  router.push('/login')
}
</script>
