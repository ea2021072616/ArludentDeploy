<template>
  <div class="min-h-screen bg-slate-50/80 flex">
    <!-- Sidebar -->
    <SidebarComponent :isOpen="sidebarOpen" @toggle="toggleSidebar" />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col md:ml-64">
      <!-- Header -->
      <HeaderComponent @toggleSidebar="toggleSidebar" />

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto p-6">
        <div class="animate-fade-in-up">
          <slot />
        </div>
      </main>

      <!-- Footer -->
      <footer class="bg-white/80 backdrop-blur-sm border-t border-gray-200/60 py-4 px-6">
        <p class="text-center text-xs text-gray-400 font-medium">
          &copy; {{ currentYear }} Arludent - Sistema de Gestión Odontológica
        </p>
      </footer>
    </div>

    <!-- Chat Assistant - Solo para pacientes y externos -->
    <ChatAssistant v-if="mostrarChatbot" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import SidebarComponent from '@/components/common/Sidebar.vue'
import HeaderComponent from '@/components/common/Header.vue'
import ChatAssistant from '@/components/Chat/ChatAssistant.vue'
import { useAuthStore } from '@/stores/authStore'

const sidebarOpen = ref(true)
const currentYear = computed(() => new Date().getFullYear())
const authStore = useAuthStore()

// Mostrar chatbot solo para pacientes y externos
const mostrarChatbot = computed(() => {
  const roles = authStore.user?.roles || []
  const tieneRolPaciente = roles.some((r: any) => r.nombre_rol === 'paciente')
  const tieneRolExterno = roles.some((r: any) => r.nombre_rol === 'externo')
  return tieneRolPaciente || tieneRolExterno
})

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
</script>
