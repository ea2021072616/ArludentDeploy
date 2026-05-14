<template>
  <AuthLayout>
    <Card>
      <div class="text-center space-y-4">
        <div v-if="isVerifying" class="flex justify-center">
          <div class="spinner"></div>
        </div>

        <template v-else>
          <!-- Success -->
          <div v-if="isVerified" class="space-y-4">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
              <CheckCircle class="w-10 h-10 text-green-600" />
            </div>
            <h2 class="text-2xl font-bold text-gray-900">¡Correo Verificado!</h2>
            <p class="text-gray-600">
              Tu cuenta ha sido verificada exitosamente. Ya puedes iniciar sesión.
            </p>
            <Button
              @click="goToLogin"
              variant="primary"
              :icon="LogIn"
              fullWidth
            >
              Ir al inicio de sesión
            </Button>
          </div>

          <!-- Error -->
          <div v-else class="space-y-4">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto">
              <XCircle class="w-10 h-10 text-red-600" />
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Error de Verificación</h2>
            <p class="text-gray-600">
              {{ errorMessage }}
            </p>
            <Button
              @click="goToLogin"
              variant="outline"
              fullWidth
            >
              Volver al inicio
            </Button>
          </div>
        </template>
      </div>
    </Card>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { CheckCircle, XCircle, LogIn } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const { verifyEmail } = useAuth()

const isVerifying = ref(true)
const isVerified = ref(false)
const errorMessage = ref('El enlace de verificación no es válido o ha expirado.')

onMounted(async () => {
  const token = route.query.token as string

  if (!token) {
    isVerifying.value = false
    errorMessage.value = 'No se proporcionó un token de verificación.'
    return
  }

  try {
    await verifyEmail(token)
    isVerified.value = true
  } catch (error: any) {
    isVerified.value = false
    errorMessage.value = error.response?.data?.message || 'Error al verificar el correo'
  } finally {
    isVerifying.value = false
  }
})

const goToLogin = () => {
  router.push('/login')
}
</script>
