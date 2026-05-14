<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Calendario de Citas</h1>
          <p class="text-gray-600 mt-1">Vista mensual de todas las citas</p>
        </div>
        <div class="flex items-center space-x-3">
          <Button
            variant="outline"
            @click="router.push('/secretaria/agenda')"
          >
            <List class="w-5 h-5" />
            <span>Vista Lista</span>
          </Button>
          <Button
            variant="primary"
            @click="router.push('/secretaria/agenda/nueva-cita')"
          >
            <Plus class="w-5 h-5" />
            <span>Nueva Cita</span>
          </Button>
        </div>
      </div>

      <!-- Controles del calendario -->
      <Card class="p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <Button
              variant="outline"
              @click="mesAnterior"
            >
              <ChevronLeft class="w-5 h-5" />
            </Button>
            <h2 class="text-xl font-bold text-gray-900 min-w-[200px] text-center">
              {{ nombreMes }} {{ anioActual }}
            </h2>
            <Button
              variant="outline"
              @click="mesSiguiente"
            >
              <ChevronRight class="w-5 h-5" />
            </Button>
            <Button
              variant="outline"
              @click="irHoy"
            >
              Hoy
            </Button>
          </div>

          <!-- Leyenda de colores -->
          <div class="flex items-center space-x-4 text-sm">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-orange-500"></div>
              <span>Pendiente</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
              <span>Confirmado</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <span>En Espera</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-blue-500"></div>
              <span>Siendo Atendido</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-gray-500"></div>
              <span>Completado</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <span>Cancelado</span>
            </div>
          </div>
        </div>
      </Card>

      <!-- Calendario -->
      <Card class="p-6">
        <div v-if="cargando" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
          <p class="text-gray-600 mt-4">Cargando calendario...</p>
        </div>

        <div v-else class="calendar-grid">
          <!-- Encabezado días de la semana -->
          <div class="grid grid-cols-7 gap-2 mb-2">
            <div
              v-for="dia in diasSemana"
              :key="dia"
              class="text-center font-semibold text-gray-700 py-2"
            >
              {{ dia }}
            </div>
          </div>

          <!-- Días del mes -->
          <div class="grid grid-cols-7 gap-2">
            <div
              v-for="(dia, index) in diasDelMes"
              :key="index"
              :class="[
                'min-h-[120px] border rounded-lg p-2 transition-all',
                dia.esDelMes ? 'bg-white hover:shadow-md cursor-pointer' : 'bg-gray-50',
                dia.esHoy ? 'ring-2 ring-blue-500' : '',
                !dia.esDelMes ? 'opacity-40' : ''
              ]"
              @click="dia.esDelMes && verCitasDia(dia)"
            >
              <!-- Número del día -->
              <div
                :class="[
                  'text-sm font-semibold mb-1',
                  dia.esHoy ? 'bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center' : 'text-gray-700'
                ]"
              >
                {{ dia.numero }}
              </div>

              <!-- Citas del día -->
              <div class="space-y-1">
                <div
                  v-for="cita in dia.citas.slice(0, 3)"
                  :key="cita.id_cita"
                  :class="[
                    'text-xs p-1 rounded truncate',
                    getColorEstado(cita.estado)
                  ]"
                  :title="`${cita.paciente?.nombres || 'Usuario'} - ${cita.motivo || 'Sin motivo'}`"
                >
                  {{ formatearHora(cita.fecha_hora_inicio) }}
                  {{ cita.paciente?.nombres || 'Usuario' }}
                </div>

                <!-- Indicador de más citas -->
                <div
                  v-if="dia.citas.length > 3"
                  class="text-xs text-blue-600 font-semibold text-center"
                >
                  +{{ dia.citas.length - 3 }} más
                </div>
              </div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Modal detalle del día -->
      <Modal
        :show="modalDetalleDia"
        :title="`Citas del ${fechaSeleccionada}`"
        size="xl"
        @close="cerrarModalDetalleDia"
      >
        <div class="space-y-3 max-h-[500px] overflow-y-auto">
          <Card
            v-for="cita in citasDelDiaSeleccionado"
            :key="cita.id_cita"
            class="p-4 hover:shadow-lg transition-shadow"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                  <Clock class="w-4 h-4 text-gray-500" />
                  <span class="font-semibold">{{ formatearHora(cita.fecha_hora_inicio) }}</span>
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-semibold',
                      getColorEstadoFull(cita.estado)
                    ]"
                  >
                    {{ formatEstado(cita.estado) }}
                  </span>
                </div>

                <div class="flex items-center space-x-2 mb-1">
                  <User class="w-4 h-4 text-gray-500" />
                  <span class="text-gray-900">
                    {{ cita.paciente ? `${cita.paciente.nombres} ${cita.paciente.apellidos}` : 'Usuario Externo' }}
                  </span>
                </div>

                <div class="flex items-center space-x-2 mb-1">
                  <UserCheck class="w-4 h-4 text-gray-500" />
                  <span class="text-gray-700">
                    Dr(a). {{ cita.medico.nombres }} {{ cita.medico.apellidos }}
                  </span>
                </div>

                <div v-if="cita.motivo" class="flex items-start space-x-2 mt-2">
                  <FileText class="w-4 h-4 text-gray-500 mt-0.5" />
                  <span class="text-sm text-gray-600">{{ cita.motivo }}</span>
                </div>
              </div>

              <div class="flex flex-col space-y-2">
                <Button
                  size="sm"
                  variant="outline"
                  @click="verDetallesCita(cita)"
                >
                  <Eye class="w-4 h-4" />
                </Button>
              </div>
            </div>
          </Card>

          <div v-if="citasDelDiaSeleccionado.length === 0" class="text-center py-8 text-gray-500">
            No hay citas programadas para este día
          </div>
        </div>

        <template #footer>
          <div class="flex justify-end space-x-3">
            <Button
              variant="outline"
              @click="cerrarModalDetalleDia"
            >
              Cerrar
            </Button>
            <Button
              variant="primary"
              @click="nuevaCitaEnFecha"
            >
              <Plus class="w-4 h-4 mr-2" />
              Nueva Cita
            </Button>
          </div>
        </template>
      </Modal>

      <!-- Modal detalles de cita individual -->
      <Modal
        :show="modalDetalles"
        title="Detalle de la cita"
        size="md"
        @close="cerrarModalDetalles"
      >
        <div v-if="citaDetalle" class="space-y-3 text-sm text-gray-700">
          <p class="text-lg font-semibold text-gray-900">Cita # {{ citaDetalle.id_cita }}</p>
          <p><strong>Paciente:</strong> {{ citaDetalle.paciente ? `${citaDetalle.paciente.nombres} ${citaDetalle.paciente.apellidos}` : (citaDetalle.usuario_externo?.username || 'Usuario Externo') }}</p>
          <p><strong>Médico:</strong> Dr(a). {{ citaDetalle.medico?.nombres }} {{ citaDetalle.medico?.apellidos }}</p>
          <p><strong>Fecha:</strong> {{ formatearFecha(citaDetalle.fecha_hora_inicio) }}</p>
          <p><strong>Hora:</strong> {{ formatearHora(citaDetalle.fecha_hora_inicio) }} - {{ formatearHora(citaDetalle.fecha_hora_fin) }}</p>
          <p><strong>Estado:</strong> {{ formatEstado(citaDetalle.estado) }}</p>
          <p v-if="citaDetalle.motivo"><strong>Motivo:</strong> {{ citaDetalle.motivo }}</p>
          <p v-if="citaDetalle.notas"><strong>Notas:</strong> {{ citaDetalle.notas }}</p>
        </div>

        <template #footer>
          <div class="flex justify-end">
            <Button variant="outline" @click="cerrarModalDetalles">Cerrar</Button>
          </div>
        </template>
      </Modal>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Modal from '@/components/common/Modal.vue'
import { useNotification } from '@/composables/useNotification'
import secretariaCitasService from '@/api/secretariaCitasService'

import {
  Calendar, Plus, List, ChevronLeft, ChevronRight,
  Clock, User, UserCheck, FileText, Eye
} from 'lucide-vue-next'

const router = useRouter()
const { showError } = useNotification()

// Estado
const cargando = ref(true)
const mesActual = ref(new Date().getMonth())
const anioActual = ref(new Date().getFullYear())
const citas = ref<any[]>([])
const modalDetalleDia = ref(false)
const citasDelDiaSeleccionado = ref<any[]>([])
const fechaSeleccionada = ref('')
// Modal detalle de cita individual
const modalDetalles = ref(false)
const citaDetalle = ref<any>(null)

// Días de la semana
const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

// Nombre del mes actual
const nombreMes = computed(() => {
  const meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ]
  return meses[mesActual.value]
})

// Generar días del mes
const diasDelMes = computed(() => {
  const primerDia = new Date(anioActual.value, mesActual.value, 1)
  const ultimoDia = new Date(anioActual.value, mesActual.value + 1, 0)
  const diasEnMes = ultimoDia.getDate()
  const primerDiaSemana = primerDia.getDay()

  const hoy = new Date()
  const dias = []

  // Días del mes anterior (para llenar)
  const mesAnterior = new Date(anioActual.value, mesActual.value, 0)
  const diasMesAnterior = mesAnterior.getDate()

  for (let i = primerDiaSemana - 1; i >= 0; i--) {
    dias.push({
      numero: diasMesAnterior - i,
      esDelMes: false,
      esHoy: false,
      fecha: new Date(anioActual.value, mesActual.value - 1, diasMesAnterior - i),
      citas: []
    })
  }

  // Días del mes actual
  for (let i = 1; i <= diasEnMes; i++) {
    const fecha = new Date(anioActual.value, mesActual.value, i)
    const esHoy = fecha.toDateString() === hoy.toDateString()
    const fechaStr = fecha.toISOString().split('T')[0]

    // Filtrar citas de este día
    const citasDelDia = citas.value.filter(c => {
      const fechaCita = c.fecha_hora_inicio.split('T')[0]
      return fechaCita === fechaStr
    })

    dias.push({
      numero: i,
      esDelMes: true,
      esHoy,
      fecha,
      citas: citasDelDia
    })
  }

  // Días del mes siguiente (para completar la grilla)
  const diasRestantes = 42 - dias.length // 6 semanas x 7 días
  for (let i = 1; i <= diasRestantes; i++) {
    dias.push({
      numero: i,
      esDelMes: false,
      esHoy: false,
      fecha: new Date(anioActual.value, mesActual.value + 1, i),
      citas: []
    })
  }

  return dias
})

// Cargar citas del mes
const cargarCitasMes = async () => {
  try {
    cargando.value = true

    const primerDia = new Date(anioActual.value, mesActual.value, 1)
    const ultimoDia = new Date(anioActual.value, mesActual.value + 1, 0)

    const response = await secretariaCitasService.obtenerTodasLasCitas({
      fecha_desde: primerDia.toISOString().split('T')[0],
      fecha_hasta: ultimoDia.toISOString().split('T')[0]
    })

    citas.value = response.data?.data || []
    console.log('📅 Citas del mes cargadas:', citas.value.length)
  } catch (error: any) {
    console.error('Error al cargar citas:', error)
    showError('Error al cargar las citas del mes')
  } finally {
    cargando.value = false
  }
}

// Navegación
const mesAnterior = () => {
  if (mesActual.value === 0) {
    mesActual.value = 11
    anioActual.value--
  } else {
    mesActual.value--
  }
  cargarCitasMes()
}

const mesSiguiente = () => {
  if (mesActual.value === 11) {
    mesActual.value = 0
    anioActual.value++
  } else {
    mesActual.value++
  }
  cargarCitasMes()
}

const irHoy = () => {
  const hoy = new Date()
  mesActual.value = hoy.getMonth()
  anioActual.value = hoy.getFullYear()
  cargarCitasMes()
}

// Ver citas de un día
const verCitasDia = (dia: any) => {
  if (!dia.esDelMes) return

  citasDelDiaSeleccionado.value = dia.citas
  const fecha = new Date(anioActual.value, mesActual.value, dia.numero)
  fechaSeleccionada.value = fecha.toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
  modalDetalleDia.value = true
}

const cerrarModalDetalleDia = () => {
  modalDetalleDia.value = false
  citasDelDiaSeleccionado.value = []
}

const verDetallesCita = (cita: any) => {
  citaDetalle.value = cita
  modalDetalles.value = true
}

const cerrarModalDetalles = () => {
  modalDetalles.value = false
  citaDetalle.value = null
}

const nuevaCitaEnFecha = () => {
  router.push('/secretaria/agenda/nueva-cita')
}

// Utilidades de formato
const formatearHora = (fecha: string) => {
  return new Date(fecha).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatearFecha = (fecha: string) => {
  return new Date(fecha).toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatEstado = (estado: string) => {
  const estados: Record<string, string> = {
    'pendiente': '⏳ Pendiente',
    'confirmado': '✓ Confirmado',
    'en_espera': '🔔 En Espera',
    'siendo_atendido': '👨‍⚕️ Atendiendo',
    'completado': '✅ Completado',
    'cancelado': '❌ Cancelado',
    'no_asistio': '⚠️ No Asistió'
  }
  return estados[estado] || estado
}

const getColorEstado = (estado: string) => {
  const colores: Record<string, string> = {
    'pendiente': 'bg-orange-100 text-orange-800 border-l-2 border-orange-500',
    'confirmado': 'bg-green-100 text-green-800 border-l-2 border-green-500',
    'en_espera': 'bg-yellow-100 text-yellow-800 border-l-2 border-yellow-500',
    'siendo_atendido': 'bg-blue-100 text-blue-800 border-l-2 border-blue-500',
    'completado': 'bg-gray-100 text-gray-800 border-l-2 border-gray-500',
    'cancelado': 'bg-red-100 text-red-800 border-l-2 border-red-500'
  }
  return colores[estado] || 'bg-gray-100 text-gray-800'
}

const getColorEstadoFull = (estado: string) => {
  const colores: Record<string, string> = {
    'pendiente': 'bg-orange-100 text-orange-800',
    'confirmado': 'bg-green-100 text-green-800',
    'en_espera': 'bg-yellow-100 text-yellow-800',
    'siendo_atendido': 'bg-blue-100 text-blue-800',
    'completado': 'bg-gray-100 text-gray-800',
    'cancelado': 'bg-red-100 text-red-800'
  }
  return colores[estado] || 'bg-gray-100 text-gray-800'
}

// Inicialización
onMounted(() => {
  cargarCitasMes()
})
</script>

<style scoped>
.calendar-grid {
  font-family: system-ui, -apple-system, sans-serif;
}
</style>
