<template>
  <router-view />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'

const authStore = useAuthStore()

// Inicializar autenticación al cargar la app
onMounted(async () => {
  await authStore.initialize()
})
</script>

<style scoped></style>

