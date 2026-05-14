<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Welcome Section -->
      <div class="bg-gradient-to-r from-primary-600 to-dental-600 rounded-2xl p-8 text-white">
        <h1 class="text-3xl font-bold mb-2">¡Bienvenido, {{ userName }}!</h1>
        <p class="text-primary-100">Sistema de Gestión Odontológica Arludent</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Pacientes -->
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Pacientes</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">150</p>
            </div>
            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
              <Users class="w-6 h-6 text-primary-600" />
            </div>
          </div>
        </Card>

        <!-- Citas Hoy -->
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Citas Hoy</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">12</p>
            </div>
            <div class="w-12 h-12 bg-dental-100 rounded-lg flex items-center justify-center">
              <Calendar class="w-6 h-6 text-dental-600" />
            </div>
          </div>
        </Card>

        <!-- Tratamientos Activos -->
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Tratamientos</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">45</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <Activity class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>

        <!-- Ingresos del Mes -->
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Ingresos Mes</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">S/ 15,000</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <DollarSign class="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Quick Actions -->
      <Card title="Accesos Rápidos">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            to="/pacientes"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <Users class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Pacientes</span>
          </router-link>

          <router-link
            to="/citas"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <Calendar class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Agendar Cita</span>
          </router-link>

          <router-link
            to="/historial"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <FileText class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Historial</span>
          </router-link>

          <router-link
            to="/perfil"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <Settings class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Configuración</span>
          </router-link>
        </div>
      </Card>

      <!-- Recent Activity (Placeholder) -->
      <Card title="Actividad Reciente">
        <div class="space-y-4">
          <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
              <Calendar class="w-5 h-5 text-primary-600" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">Nueva cita agendada</p>
              <p class="text-xs text-gray-600">Hace 2 horas</p>
            </div>
          </div>

          <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
              <UserPlus class="w-5 h-5 text-green-600" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">Nuevo paciente registrado</p>
              <p class="text-xs text-gray-600">Hace 5 horas</p>
            </div>
          </div>

          <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
            <div class="w-10 h-10 bg-dental-100 rounded-full flex items-center justify-center">
              <FileText class="w-5 h-5 text-dental-600" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">Historial clínico actualizado</p>
              <p class="text-xs text-gray-600">Hace 1 día</p>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import {
  Users,
  Calendar,
  Activity,
  DollarSign,
  FileText,
  Settings,
  UserPlus
} from 'lucide-vue-next'

const authStore = useAuthStore()
const userName = computed(() => authStore.user?.username || 'Usuario')
</script>
