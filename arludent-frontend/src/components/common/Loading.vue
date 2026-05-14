<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl p-8 shadow-2xl max-w-sm w-full mx-4">
      <div class="flex flex-col items-center space-y-4">
        <!-- Spinner -->
        <div class="spinner"></div>

        <!-- Message -->
        <div class="text-center">
          <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ title }}</h3>
          <p v-if="message" class="text-sm text-gray-600">{{ message }}</p>
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
