<template>
  <AuthLayout>
    <Card>
      <div class="text-center space-y-5 py-4">
        <div class="relative inline-block">
          <div class="w-20 h-20 bg-gradient-to-br from-red-50 to-red-100 rounded-2xl flex items-center justify-center mx-auto shadow-sm">
            <AlertCircle class="w-10 h-10 text-red-500" />
          </div>
        </div>
        <div class="space-y-2">
          <h2 class="text-2xl font-bold text-gray-900">Página No Encontrada</h2>
          <p class="text-gray-500 text-sm leading-relaxed">
            La página que buscas no existe o ha sido movida.
          </p>
        </div>
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
