<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-8 shadow-soft-xl max-w-sm w-full mx-4 border border-gray-100/80 animate-fade-in-up">
      <div class="flex flex-col items-center space-y-4">
        <!-- Spinner -->
        <div class="spinner-premium"></div>

        <!-- Message -->
        <div class="text-center">
          <h3 class="text-lg font-bold text-gray-900 mb-1">{{ title }}</h3>
          <p v-if="message" class="text-sm text-gray-500">{{ message }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  show: boolean
  title?: string
  message?: string
}

withDefaults(defineProps<Props>(), {
  title: 'Cargando...',
  message: 'Por favor espera'
})
</script>

<style scoped>
.spinner-premium {
  width: 44px;
  height: 44px;
  border: 3px solid rgba(14, 111, 181, 0.1);
  border-top-color: #0E6FB5;
  border-right-color: #3EA6E3;
  border-radius: 50%;
  animation: spin-premium 0.8s cubic-bezier(0.5, 0, 0.5, 1) infinite;
}

@keyframes spin-premium {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
