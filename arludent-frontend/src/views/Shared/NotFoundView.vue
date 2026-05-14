<template>
  <AuthLayout>
    <Card>
      <div class="text-center space-y-4">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto">
          <AlertCircle class="w-10 h-10 text-red-600" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900">Página No Encontrada</h2>
        <p class="text-gray-600">
          La página que buscas no existe o ha sido movida.
        </p>
        <Button
          @click="goHome"
          variant="primary"
          :icon="Home"
          fullWidth
        >
          Volver al Inicio
        </Button>
      </div>
    </Card>
  </AuthLayout>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { AlertCircle, Home } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const goHome = () => {
  if (authStore.isAuthenticated) {
    router.push('/dashboard')
  } else {
    router.push('/login')
  }
}
</script>
