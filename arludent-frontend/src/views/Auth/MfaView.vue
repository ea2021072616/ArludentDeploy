<template>
  <AuthLayout>
    <Card title="Verificación de Dos Factores">
      <div class="space-y-6">
        <!-- Info Message -->
        <div class="bg-primary-50 border border-primary-200 rounded-lg p-4">
          <div class="flex items-start space-x-3">
            <ShieldCheck class="w-5 h-5 text-primary-600 mt-0.5" />
            <div>
              <p class="text-sm font-medium text-primary-900">Autenticación de Dos Factores</p>
              <p class="text-sm text-primary-700 mt-1">
                Ingresa el código de 6 dígitos de tu aplicación de autenticación
              </p>
            </div>
          </div>
        </div>

        <form @submit.prevent="handleVerifyMFA" class="space-y-4">
          <!-- MFA Code -->
          <InputField
            v-model="codigo"
            label="Código de Verificación"
            type="text"
            placeholder="000000"
            required
            :error="errors.codigo"
            :icon="Key"
            maxlength="6"
            autocomplete="one-time-code"
          />

          <!-- Submit Button -->
          <Button
            type="submit"
            variant="primary"
            :loading="isLoading"
            :icon="CheckCircle"
            fullWidth
          >
            Verificar Código
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
      </div>
    </Card>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useMFA } from '@/composables/useMFA'
import { useValidation } from '@/composables/useValidation'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Card from '@/components/common/Card.vue'
import InputField from '@/components/common/InputField.vue'
import Button from '@/components/common/Button.vue'
import { ShieldCheck, Key, CheckCircle } from 'lucide-vue-next'

const router = useRouter()
const { verifyMFA } = useMFA()
const { errors, validateMFACode, clearErrors } = useValidation()

const codigo = ref('')
const isLoading = ref(false)

const handleVerifyMFA = async () => {
  clearErrors()

  if (!validateMFACode(codigo.value, 'codigo')) {
    return
  }

  try {
    isLoading.value = true
    await verifyMFA(codigo.value)
    router.push('/dashboard')
  } catch (error) {
    // Error handled by composable
  } finally {
    isLoading.value = false
  }
}
</script>
