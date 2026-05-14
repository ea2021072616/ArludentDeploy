<template>
  <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 sticky top-0 z-30">
    <!-- Mobile Menu Button -->
    <button @click="$emit('toggleSidebar')" class="md:hidden text-gray-600 hover:text-gray-900">
      <Menu class="w-6 h-6" />
    </button>

    <!-- Page Title -->
    <div class="flex-1">
      <h1 class="text-xl font-semibold text-gray-900">{{ pageTitle }}</h1>
    </div>

    <!-- Right Section -->
    <div class="flex items-center space-x-4">
      <!-- MFA Badge -->
      <div v-if="isMFAEnabled" class="hidden md:flex items-center space-x-2 px-3 py-1.5 bg-green-50 rounded-lg">
        <ShieldCheck class="w-4 h-4 text-green-600" />
        <span class="text-xs font-medium text-green-700">2FA Activo</span>
      </div>

      <!-- Email Verification Status -->
      <div v-if="!isEmailVerified" class="hidden md:flex items-center space-x-2 px-3 py-1.5 bg-yellow-50 rounded-lg">
        <AlertCircle class="w-4 h-4 text-yellow-600" />
        <span class="text-xs font-medium text-yellow-700">Correo sin verificar</span>
      </div>

      <!-- Notifications (placeholder) -->
      <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
        <Bell class="w-5 h-5" />
        <!-- <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span> -->
      </button>

      <!-- User Menu -->
      <router-link
        to="/perfil"
        class="flex items-center space-x-2 px-3 py-2 hover:bg-gray-100 rounded-lg transition-colors"
      >
        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
          <User class="w-4 h-4 text-primary-700" />
        </div>
        <span class="hidden md:block text-sm font-medium text-gray-700">
          {{ userName }}
        </span>
      </router-link>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import {
  Menu,
  Bell,
  User,
  ShieldCheck,
  AlertCircle
} from 'lucide-vue-next'

defineEmits(['toggleSidebar'])

const route = useRoute()
const authStore = useAuthStore()

const pageTitle = computed(() => {
  const titles: Record<string, string> = {
    dashboard: 'Dashboard',
    perfil: 'Mi Perfil',
    pacientes: 'Pacientes',
    citas: 'Citas',
    historial: 'Historial Clínico',
    usuarios: 'Usuarios',
    auditoria: 'Auditoría'
  }
  return titles[route.name as string] || 'Arludent'
})

const userName = computed(() => authStore.user?.username || 'Usuario')
const isMFAEnabled = computed(() => authStore.isMFAEnabled)
const isEmailVerified = computed(() => authStore.isEmailVerified)
</script>
