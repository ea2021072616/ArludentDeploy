<template>
  <div
    :class="[
      'fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl transform transition-transform duration-300 ease-in-out',
      { '-translate-x-full md:translate-x-0': !isOpen }
    ]"
  >
    <!-- Logo Section -->
    <div class="h-16 flex items-center justify-between px-6 bg-primary-600">
      <router-link to="/dashboard" class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
          <Smile class="w-5 h-5 text-primary-600" />
        </div>
        <span class="text-white font-bold text-lg">Arludent</span>
      </router-link>
      <button @click="$emit('toggle')" class="md:hidden text-white">
        <X class="w-6 h-6" />
      </button>
    </div>

    <!-- User Info -->
    <div class="px-6 py-4 bg-primary-50 border-b border-primary-100">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-primary-200 rounded-full flex items-center justify-center">
          <User class="w-5 h-5 text-primary-700" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 truncate">
            {{ user?.username || 'Usuario' }}
          </p>
          <p class="text-xs text-gray-600 truncate">
            {{ user?.correo || '' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4">
      <div class="px-3 space-y-1">
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
        <div class="my-4 border-t border-gray-200"></div>
        <p v-if="hasRole('medico')" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
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
        <div class="my-4 border-t border-gray-200"></div>

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
        <div v-if="hasRole('paciente') || hasRole('usuario')" class="my-4 border-t border-gray-200"></div>

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
        <div v-if="hasRole('secretaria')" class="my-4 border-t border-gray-200"></div>
        <p v-if="hasRole('secretaria')" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
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
        <div v-if="hasRole('admin')" class="my-4 border-t border-gray-200"></div>
        <p v-if="hasRole('admin')" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
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
        <div v-if="hasRole('medico')" class="my-4 border-t border-gray-200"></div>
        <p v-if="hasRole('medico')" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
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
        <div v-if="hasRole('admin')" class="my-4 border-t border-gray-200"></div>
        <p v-if="hasRole('admin')" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
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
    <div class="p-4 border-t border-gray-200">
      <button @click="handleLogout" class="w-full btn btn-danger flex items-center justify-center space-x-2">
        <LogOut class="w-5 h-5" />
        <span>Cerrar Sesión</span>
      </button>
    </div>
  </div>

  <!-- Overlay para mobile -->
  <div
    v-if="isOpen"
    @click="$emit('toggle')"
    class="fixed inset-0 bg-black/50 z-40 md:hidden"
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
.nav-item {
  @apply flex items-center space-x-3 px-3 py-2.5 text-gray-700 rounded-lg hover:bg-primary-50 hover:text-primary-700 transition-colors duration-200;
}

.nav-item-active {
  @apply bg-primary-100 text-primary-700 font-medium;
}
</style>
