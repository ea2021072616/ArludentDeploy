<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Welcome Section -->
      <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3 text-gray-900">
              <Users class="w-8 h-8 text-blue-600" />
              Panel de Secretaría
            </h1>
            <p class="text-gray-600">Gestión de Pacientes, Citas y Atención</p>
            <p class="text-sm text-gray-500 mt-2">
              {{ new Date().toLocaleDateString('es-ES', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
              }) }}
            </p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-500">Última actualización</p>
            <p class="text-lg font-semibold text-gray-900">{{ horaActual }}</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="cargando" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
        <p class="text-gray-600 mt-4">Cargando dashboard...</p>
      </div>

      <!-- Main Dashboard -->
      <template v-else-if="dashboardData">
        <!-- Alertas Importantes -->
        <div v-if="dashboardData.alertas?.length > 0" class="space-y-4">
          <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
            <AlertTriangle class="w-5 h-5 text-orange-600" />
            Alertas Importantes
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="(alerta, index) in dashboardData.alertas"
              :key="index"
              :class="[
                'p-4 rounded-xl border-l-4 bg-white shadow-sm',
                alerta.tipo === 'danger' || alerta.tipo === 'urgente' ? 'border-red-500 bg-red-50' : '',
                alerta.tipo === 'warning' || alerta.tipo === 'advertencia' ? 'border-orange-500 bg-orange-50' : '',
                alerta.tipo === 'info' ? 'border-blue-500 bg-blue-50' : ''
              ]"
            >
              <div class="flex items-start gap-3">
                <div
                  :class="[
                    'w-10 h-10 rounded-full flex items-center justify-center',
                    alerta.tipo === 'danger' || alerta.tipo === 'urgente' ? 'bg-red-100' : '',
                    alerta.tipo === 'warning' || alerta.tipo === 'advertencia' ? 'bg-orange-100' : '',
                    alerta.tipo === 'info' ? 'bg-blue-100' : ''
                  ]"
                >
                  <component
                    :is="getIconComponent(alerta.icono)"
                    :class="[
                      'w-5 h-5',
                      alerta.tipo === 'danger' || alerta.tipo === 'urgente' ? 'text-red-600' : '',
                      alerta.tipo === 'warning' || alerta.tipo === 'advertencia' ? 'text-orange-600' : '',
                      alerta.tipo === 'info' ? 'text-blue-600' : ''
                    ]"
                  />
                </div>
                <div class="flex-1">
                  <p class="font-medium text-gray-900">{{ alerta.mensaje }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card hover class="border-l-4 border-blue-500">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <Clock class="w-5 h-5 text-blue-600" />
                  <p class="text-sm font-medium text-gray-600">Tiempo Espera Promedio</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 mb-1">
                  {{ dashboardData.estadisticas_rapidas.tiempo_promedio_espera }} min
                </p>
              </div>
              <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100">
                <Clock class="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </Card>

          <Card hover class="border-l-4 border-green-500">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <Users class="w-5 h-5 text-green-600" />
                  <p class="text-sm font-medium text-gray-600">Pacientes Nuevos (Semana)</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 mb-1">
                  {{ dashboardData.estadisticas_rapidas.pacientes_nuevos_semana }}
                </p>
              </div>
              <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-green-100">
                <Users class="w-6 h-6 text-green-600" />
              </div>
            </div>
          </Card>

          <Card hover class="border-l-4 border-orange-500">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <CalendarCheck class="w-5 h-5 text-orange-600" />
                  <p class="text-sm font-medium text-gray-600">% Asistencia</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 mb-1">
                  {{ dashboardData.estadisticas_rapidas.porcentaje_asistencia }}%
                </p>
              </div>
              <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-orange-100">
                <CalendarCheck class="w-6 h-6 text-orange-600" />
              </div>
            </div>
          </Card>

          <Card hover class="border-l-4 border-purple-500">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <TrendingUp class="w-5 h-5 text-purple-600" />
                  <p class="text-sm font-medium text-gray-600">Eficiencia Atención</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 mb-1">
                  {{ dashboardData.estadisticas_rapidas.eficiencia_atencion }}%
                </p>
              </div>
              <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-purple-100">
                <TrendingUp class="w-6 h-6 text-purple-600" />
              </div>
            </div>
          </Card>
        </div>

        <!-- Accesos Rápidos -->
        <div class="space-y-4">
          <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
            <Zap class="w-5 h-5 text-blue-600" />
            Accesos Rápidos
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card
              v-for="acceso in dashboardData.accesos_rapidos"
              :key="acceso.titulo"
              hover
              class="cursor-pointer transition-all duration-200 hover:scale-105"
              @click="navegarA(acceso.url)"
            >
              <div class="flex items-start gap-4">
                <div
                  class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                  :class="getBgColor(acceso.color)"
                >
                  <component
                    :is="getIconComponent(acceso.icono)"
                    class="w-6 h-6"
                    :class="getIconColor(acceso.color)"
                  />
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900 mb-1">{{ acceso.titulo }}</h3>
                  <p class="text-sm text-gray-600">Ver y gestionar</p>
                </div>
                <ChevronRight class="w-5 h-5 text-gray-400" />
              </div>
            </Card>
          </div>
        </div>

        <!-- Resumen de Actividad del Día -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <Calendar class="w-5 h-5 text-blue-600" />
            Resumen del Día
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
              <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <CalendarCheck class="w-8 h-8 text-blue-600" />
              </div>
              <p class="text-2xl font-bold text-gray-900">{{ dashboardData.resumen_citas.total }}</p>
              <p class="text-sm text-gray-600">Citas totales</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <Users class="w-8 h-8 text-green-600" />
              </div>
              <p class="text-2xl font-bold text-gray-900">{{ dashboardData.pacientes_en_cola.length }}</p>
              <p class="text-sm text-gray-600">En sala de espera</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <DollarSign class="w-8 h-8 text-orange-600" />
              </div>
              <p class="text-2xl font-bold text-gray-900">{{ dashboardData.resumen_pagos.pagos_pendientes }}</p>
              <p class="text-sm text-gray-600">Pagos pendientes</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <Clock class="w-8 h-8 text-purple-600" />
              </div>
              <p class="text-2xl font-bold text-gray-900">{{ dashboardData.resumen_citas.completadas }}</p>
              <p class="text-sm text-gray-600">Citas completadas</p>
            </div>
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-600 mb-4">
          <AlertTriangle class="w-12 h-12 mx-auto" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Error al cargar el dashboard</h3>
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <Button @click="cargarDashboard" class="mx-auto">
          <RefreshCw class="w-4 h-4 mr-2" />
          Reintentar
        </Button>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { useNotification } from '@/composables/useNotification'
import secretariaService, { type DashboardSecretariaResponse } from '@/api/secretariaService'

// Icons
import {
  Users, AlertTriangle, TrendingUp, TrendingDown, Minus, Zap, ChevronRight,
  Calendar, CalendarCheck, DollarSign, Clock, RefreshCw
} from 'lucide-vue-next'

const router = useRouter()
const { showError } = useNotification()

// Estado
const dashboardData = ref<DashboardSecretariaResponse | null>(null)
const cargando = ref(true)
const error = ref<string | null>(null)
const horaActual = ref('')

// Actualizar hora actual
const actualizarHora = () => {
  horaActual.value = new Date().toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Interval para actualizar la hora
let intervalHora: ReturnType<typeof setInterval>

// Cargar datos del dashboard
const cargarDashboard = async () => {
  try {
    cargando.value = true
    error.value = null
    dashboardData.value = await secretariaService.getDashboard()
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Error al cargar los datos del dashboard'
    showError('Error al cargar el dashboard')
    console.error('Error al cargar dashboard:', err)
  } finally {
    cargando.value = false
  }
}

// Navegación
const navegarA = (ruta?: string) => {
  if (ruta) {
    router.push(ruta)
  }
}

// Utilidades para colores e iconos
const getEstadisticaColor = (color: string) => {
  const colors = {
    blue: 'border-blue-500',
    green: 'border-green-500',
    orange: 'border-orange-500',
    purple: 'border-purple-500',
    red: 'border-red-500'
  }
  return colors[color as keyof typeof colors] || 'border-gray-500'
}

const getIconColor = (color: string) => {
  const colors = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    orange: 'text-orange-600',
    purple: 'text-purple-600',
    red: 'text-red-600'
  }
  return colors[color as keyof typeof colors] || 'text-gray-600'
}

const getBgColor = (color: string) => {
  const colors = {
    blue: 'bg-blue-100',
    green: 'bg-green-100',
    orange: 'bg-orange-100',
    purple: 'bg-purple-100',
    red: 'bg-red-100'
  }
  return colors[color as keyof typeof colors] || 'bg-gray-100'
}

const getBadgeColor = (color: string) => {
  const colors = {
    blue: 'bg-blue-100 text-blue-800',
    green: 'bg-green-100 text-green-800',
    orange: 'bg-orange-100 text-orange-800',
    purple: 'bg-purple-100 text-purple-800',
    red: 'bg-red-100 text-red-800'
  }
  return colors[color as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

const getTendenciaColor = (tendencia: string) => {
  const colors = {
    subida: 'text-green-600',
    bajada: 'text-red-600',
    neutral: 'text-gray-600'
  }
  return colors[tendencia as keyof typeof colors] || 'text-gray-600'
}

const getIconComponent = (iconName: string) => {
  const icons = {
    users: Users,
    calendar: Calendar,
    'calendar-check': CalendarCheck,
    'dollar-sign': DollarSign,
    clock: Clock,
    'alert-triangle': AlertTriangle
  }
  return icons[iconName as keyof typeof icons] || Users
}

// Lifecycle
onMounted(() => {
  cargarDashboard()
  actualizarHora()
  intervalHora = setInterval(actualizarHora, 60000) // Actualizar cada minuto
})

onUnmounted(() => {
  if (intervalHora) {
    clearInterval(intervalHora)
  }
})
</script>