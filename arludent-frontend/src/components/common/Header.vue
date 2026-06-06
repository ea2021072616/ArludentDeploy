<template>
  <header class="header-bar h-16 bg-white/80 backdrop-blur-xl flex items-center justify-between px-6 sticky top-0 z-30 border-b border-gray-200/60">
    <!-- Mobile Menu Button -->
    <button @click="$emit('toggleSidebar')" class="md:hidden text-gray-500 hover:text-primary-600 transition-colors p-2 rounded-lg hover:bg-primary-50">
      <Menu class="w-5 h-5" />
    </button>

    <!-- Page Title -->
    <div class="flex-1">
      <h1 class="text-lg font-bold text-gray-900 tracking-tight">{{ pageTitle }}</h1>
    </div>

    <!-- Right Section -->
    <div class="flex items-center space-x-3">
      <!-- MFA Badge -->
      <div v-if="isMFAEnabled" class="hidden md:flex items-center space-x-1.5 px-3 py-1.5 bg-dental-50 rounded-lg ring-1 ring-dental-200">
        <ShieldCheck class="w-3.5 h-3.5 text-dental-600" />
        <span class="text-[11px] font-semibold text-dental-700">2FA Activo</span>
      </div>

      <!-- Email Verification Status -->
      <div v-if="!isEmailVerified" class="hidden md:flex items-center space-x-1.5 px-3 py-1.5 bg-accent-50 rounded-lg ring-1 ring-accent-200">
        <AlertCircle class="w-3.5 h-3.5 text-accent-600" />
        <span class="text-[11px] font-semibold text-accent-700">Correo sin verificar</span>
      </div>

      <!-- Notifications -->
      <button class="relative p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all duration-200">
        <Bell class="w-5 h-5" />
        <!-- <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span> -->
      </button>

      <!-- User Menu -->
      <router-link
        to="/perfil"
        class="flex items-center space-x-2.5 px-3 py-1.5 hover:bg-gray-50 rounded-xl transition-all duration-200 group"
      >
        <div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center shadow-primary-sm group-hover:shadow-primary transition-shadow duration-300">
          <User class="w-4 h-4 text-white" />
        </div>
        <span class="hidden md:block text-sm font-semibold text-gray-700 group-hover:text-primary-700 transition-colors">
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
