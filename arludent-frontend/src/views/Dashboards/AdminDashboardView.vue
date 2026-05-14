<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Welcome Section -->
      <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3 text-gray-900">
              <Shield class="w-8 h-8 text-primary-600" />
              Panel de Administración
            </h1>
            <p class="text-gray-600">Sistema Arludent - Gestión y Control Total</p>
            <p class="text-sm text-gray-500 mt-2">
              Período: {{ formatDate(kpis?.periodo.fecha_inicio) }} - {{ formatDate(kpis?.periodo.fecha_fin) }}
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
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-primary-200 border-t-primary-600"></div>
        <p class="text-gray-600 mt-4">Cargando indicadores...</p>
      </div>

      <!-- Main Dashboard -->
      <template v-else-if="kpis">
        <!-- KPIs Principales (solo 3) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Ingresos Totales -->
          <Card hover class="border-l-4 border-green-500">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 text-green-600 mb-2">
                  <DollarSign class="w-5 h-5" />
                  <p class="text-sm font-medium">Ingresos Totales</p>
                </div>
                <p class="text-3xl font-bold text-gray-900 mb-1">
                  ${{ formatNumber(kpis.kpis.total_ingresos) }}
                </p>
                <div class="flex items-center gap-1 text-sm">
                  <TrendingUp class="w-4 h-4 text-green-600" />
                  <span class="text-green-700 font-medium">Últimos 30 días</span>
                </div>
              </div>
              <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <DollarSign class="w-7 h-7 text-green-600" />
              </div>
            </div>
          </Card>

          <!-- Total Citas -->
          <Card hover class="border-l-4 border-blue-500">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 text-blue-600 mb-2">
                  <Calendar class="w-5 h-5" />
                  <p class="text-sm font-medium">Total de Citas</p>
                </div>
                <p class="text-3xl font-bold text-gray-900 mb-1">
                  {{ formatNumber(kpis.kpis.total_citas) }}
                </p>
                <div class="flex items-center gap-1 text-sm">
                  <CheckCircle class="w-4 h-4 text-blue-600" />
                  <span class="text-blue-700 font-medium">{{ kpis.kpis.tasa_asistencia }}% asistencia</span>
                </div>
              </div>
              <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                <Calendar class="w-7 h-7 text-blue-600" />
              </div>
            </div>
          </Card>

          <!-- Nuevos Pacientes -->
          <Card hover class="border-l-4 border-purple-500">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 text-purple-600 mb-2">
                  <UserPlus class="w-5 h-5" />
                  <p class="text-sm font-medium">Nuevos Pacientes</p>
                </div>
                <p class="text-3xl font-bold text-gray-900 mb-1">
                  {{ formatNumber(kpis.kpis.nuevos_pacientes) }}
                </p>
                <div class="flex items-center gap-1 text-sm">
                  <Users class="w-4 h-4 text-purple-600" />
                  <span class="text-purple-700 font-medium">Últimos 30 días</span>
                </div>
              </div>
              <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                <UserPlus class="w-7 h-7 text-purple-600" />
              </div>
            </div>
          </Card>
        </div>

        <!-- Grid de Cards: Satisfacción y Logs -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Satisfacción de Pacientes (Gráfico Circular) -->
          <Card v-if="satisfaccion" class="border-l-4 border-orange-500">
            <template #header>
              <div class="flex items-center gap-2">
                <Heart class="w-5 h-5 text-orange-600" />
                <h3 class="text-lg font-semibold text-gray-900">Satisfacción de Pacientes</h3>
              </div>
            </template>

            <div class="flex items-center gap-6">
              <!-- Gráfico Circular (Donut) -->
              <div class="relative w-48 h-48 flex-shrink-0">
                <svg viewBox="0 0 100 100" class="transform -rotate-90">
                  <!-- Background circle -->
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke="#f3f4f6"
                    stroke-width="12"
                  />

                  <!-- Segmentos de cada puntuación -->
                  <circle
                    v-for="(segmento, index) in segmentosDonut"
                    :key="index"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    :stroke="segmento.color"
                    stroke-width="12"
                    :stroke-dasharray="`${segmento.longitud} ${circunferencia - segmento.longitud}`"
                    :stroke-dashoffset="segmento.offset"
                    class="transition-all duration-500"
                  />
                </svg>

                <!-- Centro del donut con promedio -->
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <div class="text-4xl font-bold text-gray-900">
                    {{ Number(satisfaccion.promedio_general || 0).toFixed(1) }}
                  </div>
                  <div class="flex items-center mt-1">
                    <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                  </div>
                  <span
                    class="mt-2 px-2 py-1 rounded-full text-xs font-bold"
                    :class="{
                      'bg-green-500 text-white': satisfaccion.nivel_satisfaccion === 'Excelente',
                      'bg-blue-500 text-white': satisfaccion.nivel_satisfaccion === 'Muy Bueno',
                      'bg-yellow-500 text-white': satisfaccion.nivel_satisfaccion === 'Bueno',
                      'bg-orange-500 text-white': satisfaccion.nivel_satisfaccion === 'Regular',
                      'bg-red-500 text-white': satisfaccion.nivel_satisfaccion === 'Bajo'
                    }"
                  >
                    {{ satisfaccion.nivel_satisfaccion }}
                  </span>
                </div>
              </div>

              <!-- Leyenda y Stats -->
              <div class="flex-1 space-y-3">
                <div
                  v-for="item in distribucionOrdenada"
                  :key="item.puntuacion"
                  class="flex items-center justify-between"
                >
                  <div class="flex items-center gap-2">
                    <div
                      class="w-3 h-3 rounded-full"
                      :style="{ backgroundColor: item.color }"
                    ></div>
                    <div class="flex items-center gap-1">
                      <Star
                        v-for="i in item.puntuacion"
                        :key="i"
                        class="w-3 h-3 fill-current"
                        :style="{ color: item.color }"
                      />
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="font-bold text-gray-900">{{ item.cantidad }}</span>
                    <span class="text-xs text-gray-500 ml-1">({{ item.porcentaje }}%)</span>
                  </div>
                </div>

                <div class="pt-3 border-t border-gray-200 text-sm text-gray-600">
                  <strong>{{ satisfaccion.total_calificaciones }}</strong> calificaciones totales
                </div>
              </div>
            </div>
          </Card>

          <!-- Logs de Actividad Reciente -->
          <Card class="border-l-4 border-indigo-500">
            <template #header>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Activity class="w-5 h-5 text-indigo-600" />
                  <h3 class="text-lg font-semibold text-gray-900">Actividad Reciente</h3>
                </div>
                <span class="text-xs text-gray-500">Últimas 10 acciones</span>
              </div>
            </template>

            <!-- Loading Logs -->
            <div v-if="cargandoLogs" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-indigo-200 border-t-indigo-600"></div>
              <p class="text-gray-600 mt-2 text-sm">Cargando logs...</p>
            </div>

            <!-- Lista de Logs -->
            <div v-else-if="logs.length > 0" class="space-y-2 max-h-[320px] overflow-y-auto">
              <div
                v-for="log in logs"
                :key="log.id_log"
                class="p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors"
              >
                <div class="flex items-start justify-between gap-2 mb-1">
                  <span
                    class="text-xs font-semibold px-2 py-1 rounded"
                    :class="{
                      'bg-green-100 text-green-700': log.accion.includes('CREAR') || log.accion.includes('REGISTRAR'),
                      'bg-blue-100 text-blue-700': log.accion.includes('ACTUALIZAR') || log.accion.includes('MODIFICAR'),
                      'bg-red-100 text-red-700': log.accion.includes('ELIMINAR') || log.accion.includes('BORRAR'),
                      'bg-purple-100 text-purple-700': log.accion.includes('LOGIN') || log.accion.includes('LOGOUT'),
                      'bg-gray-100 text-gray-700': !log.accion.includes('CREAR') && !log.accion.includes('ACTUALIZAR') && !log.accion.includes('ELIMINAR') && !log.accion.includes('LOGIN')
                    }"
                  >
                    {{ log.accion }}
                  </span>
                  <span class="text-xs text-gray-500 whitespace-nowrap">
                    {{ formatTimeAgo(log.fecha_hora) }}
                  </span>
                </div>
                <p class="text-sm text-gray-700">{{ log.descripcion || 'Sin descripción' }}</p>
                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                  <span v-if="log.usuario">👤 {{ log.usuario.username }}</span>
                  <span v-if="log.modulo_afectado">📁 {{ log.modulo_afectado }}</span>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8 text-gray-400">
              <Activity class="w-12 h-12 mx-auto mb-2" />
              <p class="text-sm">No hay actividad reciente</p>
            </div>
          </Card>
        </div>
      </template>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <AlertCircle class="w-16 h-16 text-red-500 mx-auto mb-4" />
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Error al cargar el dashboard</h3>
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <Button @click="cargarDatos" variant="primary">
          <RefreshCw class="w-4 h-4 mr-2" />
          Reintentar
        </Button>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { format, formatDistanceToNow } from 'date-fns'
import { es } from 'date-fns/locale'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import {
  Users,
  Shield,
  Calendar,
  DollarSign,
  TrendingUp,
  CheckCircle,
  UserPlus,
  AlertCircle,
  RefreshCw,
  Heart,
  Star,
  MessageSquare,
  Activity
} from 'lucide-vue-next'
import { obtenerDashboardKPIs, obtenerSatisfaccionPacientes, type DashboardKPIsResponse, type SatisfaccionPacientes } from '@/api/indicadoresService'
import { obtenerLogs, type LogActividad } from '@/api/auditoriaService'

// ============ STATE ============
const cargando = ref(false)
const cargandoSatisfaccion = ref(false)
const cargandoLogs = ref(false)
const error = ref<string | null>(null)
const kpis = ref<DashboardKPIsResponse | null>(null)
const satisfaccion = ref<SatisfaccionPacientes | null>(null)
const logs = ref<LogActividad[]>([])
const horaActual = ref(format(new Date(), 'HH:mm:ss', { locale: es }))
let intervaloReloj: number | null = null

// ============ COMPUTED - Gráfico Circular ============
const circunferencia = 2 * Math.PI * 40 // radio = 40

const distribucionOrdenada = computed(() => {
  if (!satisfaccion.value) return []

  const colores = [
    { puntuacion: 5, color: '#22c55e' }, // green-500
    { puntuacion: 4, color: '#3b82f6' }, // blue-500
    { puntuacion: 3, color: '#eab308' }, // yellow-500
    { puntuacion: 2, color: '#f97316' }, // orange-500
    { puntuacion: 1, color: '#ef4444' }  // red-500
  ]

  return satisfaccion.value.distribucion
    .map(d => {
      const colorInfo = colores.find(c => c.puntuacion === d.puntuacion)
      return {
        ...d,
        color: colorInfo?.color || '#9ca3af',
        porcentaje: satisfaccion.value!.total_calificaciones > 0
          ? Math.round((d.cantidad / satisfaccion.value!.total_calificaciones) * 100)
          : 0
      }
    })
    .sort((a, b) => b.puntuacion - a.puntuacion)
})

const segmentosDonut = computed(() => {
  if (!satisfaccion.value || satisfaccion.value.total_calificaciones === 0) return []

  let offsetAcumulado = 0

  return distribucionOrdenada.value.map(item => {
    const porcentaje = item.cantidad / satisfaccion.value!.total_calificaciones
    const longitud = porcentaje * circunferencia
    const segmento = {
      color: item.color,
      longitud,
      offset: -offsetAcumulado
    }

    offsetAcumulado += longitud
    return segmento
  })
})

// ============ METHODS ============
const cargarDatos = async () => {
  cargando.value = true
  error.value = null

  try {
    // Cargar KPIs principales
    const kpisData = await obtenerDashboardKPIs()
    kpis.value = kpisData

    // Cargar satisfacción y logs de forma independiente (no bloquean si fallan)
    cargarSatisfaccion()
    cargarLogsRecientes()
  } catch (err: any) {
    console.error('Error cargando dashboard:', err)
    error.value = err.response?.data?.message || 'Error al cargar los datos del dashboard'
  } finally {
    cargando.value = false
  }
}

const cargarSatisfaccion = async () => {
  cargandoSatisfaccion.value = true
  try {
    const satisfaccionData = await obtenerSatisfaccionPacientes()
    satisfaccion.value = satisfaccionData
  } catch (err: any) {
    console.error('Error cargando satisfacción:', err)
    // No mostrar error al usuario, solo en consola
  } finally {
    cargandoSatisfaccion.value = false
  }
}

const cargarLogsRecientes = async () => {
  cargandoLogs.value = true
  try {
    const response = await obtenerLogs({ page: 1, per_page: 10 })
    logs.value = response.logs // Corregido: response.logs en lugar de response.data
  } catch (err: any) {
    console.error('Error cargando logs:', err)
    // No mostrar error al usuario
  } finally {
    cargandoLogs.value = false
  }
}

const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('es-MX').format(num)
}

const formatDate = (date?: string): string => {
  if (!date) return 'N/A'
  try {
    return format(new Date(date), 'dd MMM yyyy', { locale: es })
  } catch {
    return date
  }
}

const formatTimeAgo = (date: string): string => {
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true, locale: es })
  } catch {
    return date
  }
}

// ============ LIFECYCLE ============
onMounted(() => {
  cargarDatos()

  // Actualizar hora cada segundo
  intervaloReloj = setInterval(() => {
    horaActual.value = format(new Date(), 'HH:mm:ss', { locale: es })
  }, 1000)
})

// Limpiar el interval cuando el componente se desmonte
onBeforeUnmount(() => {
  if (intervaloReloj) {
    clearInterval(intervaloReloj)
    intervaloReloj = null
  }
})
</script>
