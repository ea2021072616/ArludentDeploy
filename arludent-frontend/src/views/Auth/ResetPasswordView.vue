<template>
  <AuthLayout>
    <Card title="Restablecer Contraseña">
      <form @submit.prevent="handleResetPassword" class="space-y-4">
        <p class="text-sm text-gray-600">
          Ingresa tu nueva contraseña para restablecer el acceso a tu cuenta.
        </p>

        <!-- Email (readonly) -->
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

        <!-- New Password -->
        <InputField
          v-model="form.password"
          label="Nueva Contraseña"
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

        <!-- Submit Button -->
        <Button
          type="submit"
          variant="primary"
          :loading="isLoading"
          :icon="KeyRound"
          fullWidth
        >
          Restablecer Contraseña
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
import { reactive, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useValidation } from '@/composables/useValidation'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Card from '@/components/common/Card.vue'
import InputField from '@/components/common/InputField.vue'
import Button from '@/components/common/Button.vue'
import { Mail, Lock, KeyRound } from 'lucide-vue-next'

const route = useRoute()
const { resetPassword } = useAuth()
const {
  errors,
  validateEmail,
  validatePassword,
  validatePasswordConfirmation,
  clearErrors,
  setErrors
} = useValidation()

const form = reactive({
  token: '',
  correo: '',
  password: '',
  password_confirmation: ''
})

const isLoading = ref(false)

onMounted(() => {
  // Decodificar token de la URL (prevenir problemas con caracteres especiales)
  const rawToken = route.params.token as string
  form.token = decodeURIComponent(rawToken)
  
  // Obtener correo de query params (viene en el enlace del correo)
  const email = route.query.email as string
  if (email) {
    form.correo = decodeURIComponent(email)
  }
})

const handleResetPassword = async () => {
  clearErrors()

  const isEmailValid = validateEmail(form.correo, 'correo')
  const isPasswordValid = validatePassword(form.password, 'password')
  const isConfirmationValid = validatePasswordConfirmation(
    form.password,
    form.password_confirmation,
    'password_confirmation'
  )

  if (!isEmailValid || !isPasswordValid || !isConfirmationValid) {
    return
  }

  try {
    isLoading.value = true
    await resetPassword(form)
  } catch (error: any) {
    if (error.response?.status === 422) {
      setErrors(error.response.data.errors)
    }
  } finally {
    isLoading.value = false
  }
}
</script>
