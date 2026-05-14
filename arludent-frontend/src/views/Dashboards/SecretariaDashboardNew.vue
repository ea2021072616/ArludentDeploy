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
                alerta.tipo === 'danger' ? 'border-red-500 bg-red-50' : '',
                alerta.tipo === 'warning' ? 'border-orange-500 bg-orange-50' : '',
                alerta.tipo === 'info' ? 'border-blue-500 bg-blue-50' : ''
              ]"
            >
              <div class="flex items-start gap-3">
                <div
                  :class="[
                    'w-10 h-10 rounded-full flex items-center justify-center',
                    alerta.tipo === 'danger' ? 'bg-red-100' : '',
                    alerta.tipo === 'warning' ? 'bg-orange-100' : '',
                    alerta.tipo === 'info' ? 'bg-blue-100' : ''
                  ]"
                >
                  <component
                    :is="getAlertaIcon(alerta.icono)"
                    :class="[
                      'w-5 h-5',
                      alerta.tipo === 'danger' ? 'text-red-600' : '',
                      alerta.tipo === 'warning' ? 'text-orange-600' : '',
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

        <!-- Resumen de Citas -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <Calendar class="w-5 h-5 text-blue-600" />
            Resumen de Citas del Día
          </h2>
          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <div class="text-center">
              <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <CalendarCheck class="w-6 h-6 text-blue-600" />
              </div>
              <p class="text-xl font-bold text-gray-900">{{ dashboardData.resumen_citas.total }}</p>
              <p class="text-xs text-gray-600">Total</p>
            </div>
            <div class="text-center">
              <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <CheckCircle class="w-6 h-6 text-green-600" />
              </div>
              <p class="text-xl font-bold text-gray-900">{{ dashboardData.resumen_citas.confirmadas }}</p>
              <p class="text-xs text-gray-600">Confirmadas</p>
            </div>
            <div class="text-center">
              <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <Clock class="w-6 h-6 text-yellow-600" />
              </div>
              <p class="text-xl font-bold text-gray-900">{{ dashboardData.resumen_citas.en_espera }}</p>
              <p class="text-xs text-gray-600">En espera</p>
            </div>
            <div class="text-center">
              <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <UserCheck class="w-6 h-6 text-purple-600" />
              </div>
              <p class="text-xl font-bold text-gray-900">{{ dashboardData.resumen_citas.siendo_atendidas }}</p>
              <p class="text-xs text-gray-600">Atendiendo</p>
            </div>
            <div class="text-center">
              <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <CheckCircle2 class="w-6 h-6 text-emerald-600" />
              </div>
              <p class="text-xl font-bold text-gray-900">{{ dashboardData.resumen_citas.completadas }}</p>
              <p class="text-xs text-gray-600">Completadas</p>
            </div>
            <div class="text-center">
              <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <UserX class="w-6 h-6 text-red-600" />
              </div>
              <p class="text-xl font-bold text-gray-900">{{ dashboardData.resumen_citas.no_asistieron }}</p>
              <p class="text-xs text-gray-600">No asistió</p>
            </div>
            <div class="text-center">
              <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <X class="w-6 h-6 text-gray-600" />
              </div>
              <p class="text-xl font-bold text-gray-900">{{ dashboardData.resumen_citas.canceladas }}</p>
              <p class="text-xs text-gray-600">Canceladas</p>
            </div>
          </div>
        </div>

        <!-- Accesos Rápidos -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <Zap class="w-5 h-5 text-purple-600" />
            Accesos Rápidos
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card
              v-for="acceso in dashboardData.accesos_rapidos"
              :key="acceso.titulo"
              class="cursor-pointer hover:shadow-lg transition-shadow"
              @click="navegarA(acceso.url)"
            >
              <div class="p-4 flex items-center gap-4">
                <div
                  :class="[
                    'w-12 h-12 rounded-full flex items-center justify-center',
                    getAccesoColor(acceso.color)
                  ]"
                >
                  <component
                    :is="getAccesoIcon(acceso.icono)"
                    class="w-6 h-6"
                  />
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">{{ acceso.titulo }}</h3>
                </div>
                <ChevronRight class="w-5 h-5 text-gray-400" />
              </div>
            </Card>
          </div>
        </div>

        <!-- Estadísticas Rápidas y Pagos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Estadísticas -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
              <TrendingUp class="w-5 h-5 text-green-600" />
              Estadísticas Rápidas
            </h2>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">Eficiencia de atención</span>
                <span class="font-semibold text-green-600">{{ dashboardData.estadisticas_rapidas.eficiencia_atencion }}%</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">Tiempo promedio espera</span>
                <span class="font-semibold text-blue-600">{{ dashboardData.estadisticas_rapidas.tiempo_promedio_espera }} min</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">Pacientes nuevos (semana)</span>
                <span class="font-semibold text-purple-600">{{ dashboardData.estadisticas_rapidas.pacientes_nuevos_semana }}</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">% Asistencia</span>
                <span class="font-semibold text-orange-600">{{ dashboardData.estadisticas_rapidas.porcentaje_asistencia }}%</span>
              </div>
            </div>
          </div>

          <!-- Resumen de Pagos -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
              <DollarSign class="w-5 h-5 text-green-600" />
              Resumen de Pagos
            </h2>
            <div class="space-y-4">
              <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-green-700">Ingresos del día</p>
                <p class="text-2xl font-bold text-green-900">${{ formatearMonto(dashboardData.resumen_pagos.ingresos_dia) }}</p>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                  <p class="text-xs text-blue-700">Pagos realizados</p>
                  <p class="text-lg font-bold text-blue-900">{{ dashboardData.resumen_pagos.pagos_realizados }}</p>
                </div>
                <div class="text-center p-3 bg-orange-50 rounded-lg">
                  <p class="text-xs text-orange-700">Pagos pendientes</p>
                  <p class="text-lg font-bold text-orange-900">{{ dashboardData.resumen_pagos.pagos_pendientes }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pacientes en Cola -->
        <div v-if="dashboardData.pacientes_en_cola?.length > 0" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <Users class="w-5 h-5 text-blue-600" />
            Pacientes en Cola ({{ dashboardData.pacientes_en_cola.length }})
          </h2>
          <div class="space-y-3">
            <div
              v-for="paciente in dashboardData.pacientes_en_cola.slice(0, 5)"
              :key="paciente.cita_id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <div
                  :class="[
                    'w-3 h-3 rounded-full',
                    paciente.estado === 'confirmado' ? 'bg-green-500' : '',
                    paciente.estado === 'en_espera' ? 'bg-yellow-500' : '',
                    paciente.estado === 'siendo_atendido' ? 'bg-blue-500' : ''
                  ]"
                ></div>
                <div>
                  <p class="font-medium text-gray-900">{{ paciente.paciente }}</p>
                  <p class="text-sm text-gray-600">Cita: {{ paciente.hora_cita }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900 capitalize">{{ paciente.estado.replace('_', ' ') }}</p>
                <p v-if="paciente.tiempo_espera" class="text-xs text-gray-600">
                  {{ paciente.tiempo_espera }} min esperando
                </p>
              </div>
            </div>
          </div>
          <div v-if="dashboardData.pacientes_en_cola.length > 5" class="mt-4 text-center">
            <Button variant="outline" @click="navegarA('/secretaria/checkin')">
              Ver todos los pacientes ({{ dashboardData.pacientes_en_cola.length }})
            </Button>
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
  Users, AlertTriangle, TrendingUp, Zap, ChevronRight, Calendar, CalendarCheck, 
  DollarSign, Clock, RefreshCw, CheckCircle, UserCheck, CheckCircle2, UserX, X,
  CalendarPlus, UserPlus, CreditCard
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

// Utilidades para iconos y colores
const getAlertaIcon = (icono: string) => {
  const iconos: Record<string, any> = {
    'clock': Clock,
    'exclamation-triangle': AlertTriangle,
    'user-clock': UserCheck
  }
  return iconos[icono] || AlertTriangle
}

const getAccesoIcon = (icono: string) => {
  const iconos: Record<string, any> = {
    'calendar-plus': CalendarPlus,
    'user-plus': UserPlus,
    'credit-card': CreditCard,
    'user-check': UserCheck
  }
  return iconos[icono] || Calendar
}

const getAccesoColor = (color: string) => {
  const colores: Record<string, string> = {
    'primary': 'bg-blue-100 text-blue-600',
    'success': 'bg-green-100 text-green-600',
    'info': 'bg-cyan-100 text-cyan-600',
    'warning': 'bg-orange-100 text-orange-600'
  }
  return colores[color] || 'bg-gray-100 text-gray-600'
}

const formatearMonto = (monto: number) => {
  return new Intl.NumberFormat('es-CO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(monto)
}

// Lifecycle
onMounted(() => {
  cargarDashboard()
  actualizarHora()
  intervalHora = setInterval(actualizarHora, 60000) // Actualizar cada minuto
  
  // Auto-refresh cada 5 minutos
  setInterval(cargarDashboard, 300000)
})

onUnmounted(() => {
  if (intervalHora) {
    clearInterval(intervalHora)
  }
})
</script>