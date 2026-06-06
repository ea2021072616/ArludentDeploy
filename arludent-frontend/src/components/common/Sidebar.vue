<template>
  <div
    :class="[
      'sidebar-container fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-300 ease-in-out',
      { '-translate-x-full md:translate-x-0': !isOpen }
    ]"
  >
    <!-- Logo Section -->
    <div class="sidebar-logo h-16 flex items-center justify-between px-5">
      <router-link to="/dashboard" class="flex items-center space-x-3 group">
        <div class="w-9 h-9 bg-accent-500/20 rounded-xl flex items-center justify-center group-hover:bg-accent-500/30 transition-colors duration-300">
          <Smile class="w-5 h-5 text-accent-400" />
        </div>
        <span class="text-white font-bold text-lg tracking-wide">Arludent</span>
      </router-link>
      <button @click="$emit('toggle')" class="md:hidden text-white/70 hover:text-white transition-colors">
        <X class="w-6 h-6" />
      </button>
    </div>

    <!-- User Info -->
    <div class="mx-4 mt-4 mb-5 p-3 rounded-xl bg-white/[0.07] border border-white/[0.08]">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400/30 to-accent-500/20 flex items-center justify-center ring-2 ring-white/10">
          <User class="w-5 h-5 text-primary-200" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-white truncate">
            {{ user?.username || 'Usuario' }}
          </p>
          <p class="text-xs text-primary-300/70 truncate">
            {{ user?.correo || '' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 pb-4 sidebar-nav">
      <div class="space-y-0.5">
        <!-- Dashboard -->
        <router-link
          :to="dashboardTarget"
          class="nav-item"
          active-class="nav-item-active"
        >
          <LayoutDashboard class="w-5 h-5" />
          <span>Dashboard</span>
        </router-link>

        <!-- Perfil -->
        <router-link
          to="/perfil"
          class="nav-item"
          active-class="nav-item-active"
        >
          <UserCircle class="w-5 h-5" />
          <span>Mi Perfil</span>
        </router-link>

        <!-- Separador -->
        <div class="my-3 border-t border-white/[0.08]"></div>
        <p v-if="hasRole('medico')" class="nav-section-label">
          Gestión de Citas
        </p>

        <!-- Mi Agenda (Médico) -->
        <router-link
          v-if="hasRole('medico')"
          to="/medico/mis-citas/listado"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Calendar class="w-5 h-5" />
          <span>Mi Agenda</span>
        </router-link>

        <!-- Separador -->
        <div class="my-3 border-t border-white/[0.08]"></div>

        <!-- Historial Clínico (Médico) -->
        <router-link
          v-if="hasRole('medico')"
          to="/historial"
          class="nav-item"
          active-class="nav-item-active"
        >
          <FileText class="w-5 h-5" />
          <span>Historial Clínico</span>
        </router-link>

        <!-- Separador - Paciente -->
        <div v-if="hasRole('paciente') || hasRole('usuario')" class="my-3 border-t border-white/[0.08]"></div>

        <!-- Mis Citas (Paciente) -->
        <router-link
          v-if="hasRole('paciente') || hasRole('usuario')"
          to="/mis-citas/listado"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Calendar class="w-5 h-5" />
          <span>Mis Citas</span>
        </router-link>

        <!-- Mis Pagos (Paciente) -->
        <router-link
          v-if="hasRole('paciente') || hasRole('usuario')"
          to="/mis-pagos"
          class="nav-item"
          active-class="nav-item-active"
        >
          <ClipboardList class="w-5 h-5" />
          <span>Mis Pagos</span>
        </router-link>

        <!-- Mi Historial (Paciente) -->
        <router-link
          v-if="hasRole('paciente') || hasRole('usuario')"
          to="/mi-historial"
          class="nav-item"
          active-class="nav-item-active"
        >
          <FileText class="w-5 h-5" />
          <span>Mi Historial</span>
        </router-link>

        <!-- Separador - Secretaría -->
        <div v-if="hasRole('secretaria')" class="my-3 border-t border-white/[0.08]"></div>
        <p v-if="hasRole('secretaria')" class="nav-section-label">
          Gestión de Secretaría
        </p>

        <!-- Agenda y Check-in (Secretaría) - UNIFICADO -->
        <router-link
          v-if="hasRole('secretaria')"
          to="/secretaria/agenda"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Calendar class="w-5 h-5" />
          <span>Agenda y Check-in</span>
        </router-link>

        <!-- Pacientes (Secretaría) -->
        <router-link
          v-if="hasRole('secretaria')"
          to="/secretaria/pacientes"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Users class="w-5 h-5" />
          <span>Pacientes</span>
        </router-link>

        <!-- Seguimiento (Secretaría) -->
        <router-link
          v-if="hasRole('secretaria')"
          to="/secretaria/seguimiento"
          class="nav-item"
          active-class="nav-item-active"
        >
          <ClipboardList class="w-5 h-5" />
          <span>Seguimiento Post Tratamiento</span>
        </router-link>

        <!-- Pagos (Secretaría) -->
        <router-link
          v-if="hasRole('secretaria')"
          to="/secretaria/caja"
          class="nav-item"
          active-class="nav-item-active"
        >
          <DollarSign class="w-5 h-5" />
          <span>Pagos</span>
        </router-link>

        <!-- Separador - Admin -->
        <div v-if="hasRole('admin')" class="my-3 border-t border-white/[0.08]"></div>
        <p v-if="hasRole('admin')" class="nav-section-label">
          Administración
        </p>

        <!-- Gestión de Usuarios (Admin) -->
        <router-link
          v-if="hasRole('admin')"
          to="/admin/usuarios"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Users class="w-5 h-5" />
          <span>Gestión de Usuarios</span>
        </router-link>

        <!-- Perfiles de Doctores (Admin) -->
        <router-link
          v-if="hasRole('admin')"
          to="/admin/perfiles-doctores"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Stethoscope class="w-5 h-5" />
          <span>Perfiles de Doctores</span>
        </router-link>

        <!-- Catálogo de Tratamientos (Admin) -->
        <router-link
          v-if="hasRole('admin')"
          to="/admin/tratamientos"
          class="nav-item"
          active-class="nav-item-active"
        >
          <ClipboardList class="w-5 h-5" />
          <span>Catálogo de Tratamientos</span>
        </router-link>

        <!-- Separador - Área Personal -->
        <div v-if="hasRole('medico')" class="my-3 border-t border-white/[0.08]"></div>
        <p v-if="hasRole('medico')" class="nav-section-label">
          Mi Perfil Profesional
        </p>

        <!-- Mi Disponibilidad (Médico) -->
        <router-link
          v-if="hasRole('medico')"
          to="/medico/disponibilidad"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Calendar class="w-5 h-5" />
          <span>Mi Disponibilidad</span>
        </router-link>

        <!-- Perfil Profesional (Médico) -->
        <router-link
          v-if="hasRole('medico')"
          to="/medico/perfil-profesional"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Stethoscope class="w-5 h-5" />
          <span>Perfil Profesional</span>
        </router-link>

        <!-- Separador - Sistema (Admin) -->
        <div v-if="hasRole('admin')" class="my-3 border-t border-white/[0.08]"></div>
        <p v-if="hasRole('admin')" class="nav-section-label">
          Sistema
        </p>

        <!-- Auditoría (Admin) -->
        <router-link
          v-if="hasRole('admin')"
          to="/admin/auditoria"
          class="nav-item"
          active-class="nav-item-active"
        >
          <Shield class="w-5 h-5" />
          <span>Auditoría</span>
        </router-link>

        <!-- Reportes (Admin) -->
        <router-link
          v-if="hasRole('admin')"
          to="/admin/reportes"
          class="nav-item"
          active-class="nav-item-active"
        >
          <FileText class="w-5 h-5" />
          <span>Reportes</span>
        </router-link>

        <!-- Indicadores (Admin) -->
        <router-link
          v-if="hasRole('admin')"
          to="/admin/indicadores"
          class="nav-item"
          active-class="nav-item-active"
        >
          <BarChart3 class="w-5 h-5" />
          <span>Indicadores</span>
        </router-link>
      </div>
    </nav>

    <!-- Logout Button -->
    <div class="p-4 border-t border-white/[0.08]">
      <button @click="handleLogout" class="w-full flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-red-300 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 hover:border-red-500/30 transition-all duration-300">
        <LogOut class="w-5 h-5" />
        <span>Cerrar Sesión</span>
      </button>
    </div>
  </div>

  <!-- Overlay para mobile -->
  <div
    v-if="isOpen"
    @click="$emit('toggle')"
    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden"
  ></div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useAuth } from '@/composables/useAuth'
import {
  Smile,
  User,
  X,
  LayoutDashboard,
  UserCircle,
  Users,
  Calendar,
  FileText,
  Settings,
  Shield,
  LogOut,
  Stethoscope,
  ClipboardList,
  BarChart3,
  UserCheck,
  DollarSign
} from 'lucide-vue-next'

interface Props {
  isOpen: boolean
}

defineProps<Props>()
defineEmits(['toggle'])

const router = useRouter()
const authStore = useAuthStore()
const { logout } = useAuth()

const user = computed(() => authStore.user)

const hasRole = (roleName: string): boolean => {
  return authStore.hasRole(roleName)
}

// Ruta de dashboard por rol
const dashboardTarget = computed(() => {
  if (hasRole('admin')) return '/admin/dashboard'
  if (hasRole('medico')) return '/medico/dashboard'
  if (hasRole('secretaria')) return '/secretaria/dashboard'
  if (hasRole('paciente') || hasRole('usuario')) return '/paciente/dashboard'
  return '/dashboard'
})

const handleLogout = async () => {
  await logout()
}
</script>

<style scoped>
.sidebar-container {
  background: linear-gradient(180deg, #0A2A45 0%, #103F66 50%, #0D4B7A 100%);
  box-shadow: 4px 0 25px -5px rgba(10, 42, 69, 0.4);
}

.sidebar-logo {
  background: rgba(0, 0, 0, 0.15);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

/* Scrollbar del sidebar */
.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 100px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Navigation Items */
.nav-item {
  @apply flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium
         text-primary-200/80 
         hover:bg-white/[0.08] hover:text-white 
         transition-all duration-200;
}

.nav-item-active {
  @apply bg-white/[0.12] text-white font-semibold;
  border-left: 3px solid #F5B820;
  padding-left: 9px;
  box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.04);
}

/* Section Labels */
.nav-section-label {
  @apply px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.12em] text-primary-300/50;
}
</style>
