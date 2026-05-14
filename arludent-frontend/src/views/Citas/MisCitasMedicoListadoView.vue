<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Mis Citas</h1>
          <p class="text-gray-600 mt-1">Gestión de citas médicas</p>
        </div>
        <div class="flex items-center space-x-3">
          <Button
            variant="outline"
            @click="cambiarVista('calendario')"
          >
            <Calendar class="w-5 h-5" />
            <span>Vista Calendario</span>
          </Button>
        </div>
      </div>

      <!-- Estadísticas -->
      <div v-if="estadisticas" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.total }}</p>
            </div>
            <div class="p-3 bg-blue-100 rounded-lg">
              <Calendar class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Pendientes</p>
              <p class="text-2xl font-bold text-orange-600">{{ estadisticas.pendientes }}</p>
            </div>
            <div class="p-3 bg-orange-100 rounded-lg">
              <Clock class="w-6 h-6 text-orange-600" />
            </div>
          </div>
        </Card>

        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Confirmadas</p>
              <p class="text-2xl font-bold text-green-600">{{ estadisticas.confirmadas }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-lg">
              <CheckCircle class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>

        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Completadas</p>
              <p class="text-2xl font-bold text-gray-600">{{ estadisticas.completadas }}</p>
            </div>
            <div class="p-3 bg-gray-100 rounded-lg">
              <CheckSquare class="w-6 h-6 text-gray-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Próxima Cita -->
      <Card v-if="estadisticas?.proxima_cita" class="p-6 bg-gradient-to-r from-primary-50 to-primary-100 border-primary-200">
        <div class="flex items-start justify-between">
          <div class="flex items-start space-x-4">
            <div class="p-3 bg-primary-600 rounded-lg">
              <CalendarCheck class="w-6 h-6 text-white" />
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Próxima Cita</h3>
              <div class="mt-2 space-y-1">
                <p class="text-gray-700">
                  <span class="font-medium">Fecha:</span>
                  {{ formatearFecha(estadisticas.proxima_cita.fecha_hora_inicio) }}
                </p>
                <p class="text-gray-700">
                  <span class="font-medium">Hora:</span>
                  {{ formatearHora(estadisticas.proxima_cita.fecha_hora_inicio) }}
                </p>
                <p class="text-gray-700">
                  <span class="font-medium">Paciente:</span>
                  {{ estadisticas.proxima_cita.paciente.nombres }} {{ estadisticas.proxima_cita.paciente.apellidos }}
                </p>
                <p v-if="estadisticas.proxima_cita.paciente.dni" class="text-gray-600">
                  DNI: {{ estadisticas.proxima_cita.paciente.dni }}
                </p>
              </div>
            </div>
          </div>
          <span
            :class="[
              'px-3 py-1 rounded-full text-sm font-medium',
              getBadgeClasses(estadisticas.proxima_cita.estado)
            ]"
          >
            {{ estadisticas.proxima_cita.estado }}
          </span>
        </div>
      </Card>

      <!-- Filtros -->
      <Card class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select
              v-model="filtros.estado"
              @change="cargarCitas"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="pendiente">Pendiente</option>
              <option value="confirmado">Confirmado</option>
              <option value="en_espera">En Espera</option>
              <option value="completado">Completado</option>
              <option value="cancelado">Cancelado</option>
              <option value="no_asistio">No asistió</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
            <input
              v-model="filtros.desde"
              @change="cargarCitas"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
            <input
              v-model="filtros.hasta"
              @change="cargarCitas"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          <div class="flex items-end">
            <Button
              variant="outline"
              @click="limpiarFiltros"
              fullWidth
            >
              <X class="w-4 h-4" />
              <span>Limpiar</span>
            </Button>
          </div>
        </div>
      </Card>

      <!-- Loading -->
      <Loading v-if="cargando" :show="cargando" />

      <!-- Lista de citas -->
      <div v-else-if="citas.length > 0" class="space-y-4">
        <Card
          v-for="cita in citas"
          :key="cita.id_cita"
          class="p-6 hover:shadow-lg transition-shadow"
        >
          <div class="flex items-start justify-between">
            <!-- Info de la cita -->
            <div class="flex-1">
              <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-3">
                  <div
                    v-if="cita.paciente"
                    class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center"
                  >
                    <User class="w-6 h-6 text-primary-600" />
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                      {{ cita.paciente ? cita.paciente.nombres + ' ' + cita.paciente.apellidos : 'Paciente no asignado' }}
                    </h3>
                    <p v-if="cita.paciente?.dni" class="text-sm text-gray-600">
                      DNI: {{ cita.paciente.dni }}
                    </p>
                  </div>
                </div>
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-sm font-medium',
                    getBadgeClasses(cita.estado)
                  ]"
                >
                  {{ cita.estado }}
                </span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="flex items-center space-x-2 text-gray-700">
                  <Calendar class="w-5 h-5 text-gray-400" />
                  <span>{{ formatearFecha(cita.fecha_hora_inicio) }}</span>
                </div>
                <div class="flex items-center space-x-2 text-gray-700">
                  <Clock class="w-5 h-5 text-gray-400" />
                  <span>{{ formatearHora(cita.fecha_hora_inicio) }}</span>
                </div>
                <div v-if="cita.motivo" class="flex items-start space-x-2 text-gray-700 md:col-span-2">
                  <FileText class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
                  <span>{{ cita.motivo }}</span>
                </div>
              </div>

              <!-- Notas del médico -->
              <div v-if="cita.notas" class="p-3 bg-blue-50 rounded-lg mb-4">
                <div class="flex items-start space-x-2">
                  <MessageSquare class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-blue-900">Notas:</p>
                    <p class="text-sm text-blue-700 mt-1">{{ cita.notas }}</p>
                  </div>
                </div>
              </div>

              <!-- Calificación del paciente -->
              <div v-if="cita.calificacion" class="flex items-center space-x-2 p-3 bg-yellow-50 rounded-lg">
                <Star class="w-5 h-5 text-yellow-500 fill-yellow-500" />
                <span class="text-sm font-medium text-gray-900">
                  Calificación del paciente: {{ cita.calificacion.puntuacion }}/5
                </span>
                <span v-if="cita.calificacion.comentario" class="text-sm text-gray-600">
                  - {{ cita.calificacion.comentario }}
                </span>
              </div>
            </div>
          </div>

          <!-- Acciones -->
          <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-200">
            <Button
              variant="outline"
              size="sm"
              @click="abrirModalDetalles(cita)"
            >
              <Eye class="w-4 h-4" />
              <span>Ver Detalles</span>
            </Button>

            <Button
              v-if="cita.puede_agregar_notas"
              variant="outline"
              size="sm"
              @click="abrirModalNotas(cita)"
            >
              <MessageSquare class="w-4 h-4" />
              <span>Notas</span>
            </Button>

            <Button
              v-if="cita.puede_completar"
              variant="success"
              size="sm"
              @click="abrirModalCompletar(cita)"
            >
              <CheckCircle class="w-4 h-4" />
              <span>Completar</span>
            </Button>

            <Button
              v-if="cita.puede_cancelar"
              variant="danger"
              size="sm"
              @click="abrirModalCancelar(cita)"
            >
              <XCircle class="w-4 h-4" />
              <span>Cancelar</span>
            </Button>
          </div>
        </Card>
      </div>

      <!-- Empty State -->
      <Card v-else class="p-12 text-center">
        <CalendarX class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay citas</h3>
        <p class="text-gray-600">No se encontraron citas con los filtros seleccionados</p>
      </Card>
    </div>

    <!-- Modales -->
    <ModalDetallesCita
      :show="modales.detalles"
      :cita="citaSeleccionada"
      :isMedicoView="true"
      @close="cerrarModales"
      @completar="abrirModalCompletar"
      @agregarNotas="abrirModalNotas"
      @cancelar="abrirModalCancelar"
    />

    <ModalCancelarCita
      :show="modales.cancelar"
      :cita="citaSeleccionada"
      :isMedicoView="true"
      @close="cerrarModales"
      @cancelar="cancelarCita"
    />

    <ModalCompletarCita
      :show="modales.completar"
      :cita="citaSeleccionada"
      @close="cerrarModales"
      @completar="completarCita"
    />

    <ModalAgregarNotas
      :show="modales.notas"
      :cita="citaSeleccionada"
      @close="cerrarModales"
      @agregar="agregarNotas"
    />
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Calendar,
  Clock,
  User,
  CheckCircle,
  CheckSquare,
  CalendarCheck,
  FileText,
  MessageSquare,
  Star,
  XCircle,
  CalendarClock,
  CalendarX,
  X,
  Eye
} from 'lucide-vue-next'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Loading from '@/components/common/Loading.vue'
import ModalDetallesCita from '@/components/citas/ModalDetallesCita.vue'
import ModalCancelarCita from '@/components/citas/ModalCancelarCita.vue'
import ModalCompletarCita from '@/components/citas/ModalCompletarCita.vue'
import ModalAgregarNotas from '@/components/citas/ModalAgregarNotas.vue'
import citasMedicoService from '@/api/citasMedicoService'
import type { CitaMedico, EstadisticasCitasMedico } from '@/api/citasMedicoService'
import { useNotification } from '@/composables/useNotification'

const router = useRouter()
const { showSuccess, showError } = useNotification()

const cargando = ref(false)
const citas = ref<CitaMedico[]>([])
const estadisticas = ref<EstadisticasCitasMedico | null>(null)
const citaSeleccionada = ref<CitaMedico | null>(null)

const filtros = ref({
  estado: '',
  desde: '',
  hasta: ''
})

const modales = ref({
  detalles: false,
  cancelar: false,
  completar: false,
  notas: false
})

onMounted(() => {
  cargarDatos()
})

const cargarDatos = async () => {
  await Promise.all([cargarCitas(), cargarEstadisticas()])
}

const cargarCitas = async () => {
  cargando.value = true
  try {
    const response = await citasMedicoService.obtenerMisCitas(filtros.value)
    if (response.success) {
      citas.value = response.data.citas
    }
  } catch (error: any) {
    showError(error.response?.data?.message || 'Error al cargar las citas')
  } finally {
    cargando.value = false
  }
}

const cargarEstadisticas = async () => {
  try {
    const response = await citasMedicoService.obtenerEstadisticas()
    if (response.success) {
      estadisticas.value = response.data.estadisticas
    }
  } catch (error) {
    console.error('Error al cargar estadísticas:', error)
  }
}

const limpiarFiltros = () => {
  filtros.value = {
    estado: '',
    desde: '',
    hasta: ''
  }
  cargarCitas()
}

const cambiarVista = (vista: string) => {
  router.push({ name: 'medico-mis-citas-calendario' })
}

// Modales
const abrirModalDetalles = (cita: CitaMedico) => {
  citaSeleccionada.value = cita
  modales.value.detalles = true
}

const abrirModalCancelar = (cita: CitaMedico) => {
  citaSeleccionada.value = cita
  modales.value.cancelar = true
}

const abrirModalCompletar = (cita: CitaMedico) => {
  citaSeleccionada.value = cita
  modales.value.completar = true
}

const abrirModalNotas = (cita: CitaMedico) => {
  citaSeleccionada.value = cita
  modales.value.notas = true
}

const cerrarModales = () => {
  modales.value = {
    detalles: false,
    cancelar: false,
    completar: false,
    notas: false
  }
  citaSeleccionada.value = null
}

// Acciones
const cancelarCita = async (datos: any) => {
  if (!citaSeleccionada.value) return

  try {
    const response = await citasMedicoService.cancelarCita(citaSeleccionada.value.id_cita, datos)
    if (response.success) {
      showSuccess('Cita cancelada exitosamente')
      cerrarModales()
      cargarDatos()
    }
  } catch (error: any) {
    showError(error.response?.data?.message || 'Error al cancelar la cita')
  }
}

const completarCita = async (datos: any) => {
  if (!citaSeleccionada.value) return

  try {
    const response = await citasMedicoService.completarCita(citaSeleccionada.value.id_cita, datos)
    if (response.success) {
      showSuccess('Cita marcada como completada exitosamente')
      cerrarModales()
      cargarDatos()
    }
  } catch (error: any) {
    showError(error.response?.data?.message || 'Error al completar la cita')
  }
}

const agregarNotas = async (datos: any) => {
  if (!citaSeleccionada.value) return

  try {
    const response = await citasMedicoService.agregarNotas(citaSeleccionada.value.id_cita, datos)
    if (response.success) {
      showSuccess('Notas agregadas exitosamente')
      cerrarModales()
      cargarDatos()
    }
  } catch (error: any) {
    showError(error.response?.data?.message || 'Error al agregar notas')
  }
}

// Funciones auxiliares (placeholders para acciones no disponibles para médicos)
const abrirModalConfirmar = () => {
  // Los médicos no confirman citas
}

const abrirModalReprogramar = () => {
  // Los médicos no reprograman citas
}

const abrirModalCalificar = () => {
  // Los médicos no calificar citas
}

// Utilidades
const formatearFecha = (fecha: string) => {
  return format(new Date(fecha), "d 'de' MMMM 'de' yyyy", { locale: es })
}

const formatearHora = (fecha: string) => {
  return format(new Date(fecha), 'HH:mm', { locale: es })
}

const getBadgeClasses = (estado: string) => {
  const classes: Record<string, string> = {
    pendiente: 'bg-orange-100 text-orange-700',
    confirmada: 'bg-green-100 text-green-700',
    reprogramada: 'bg-blue-100 text-blue-700',
    completada: 'bg-gray-100 text-gray-700',
    cancelada: 'bg-red-100 text-red-700',
    no_asistio: 'bg-gray-100 text-gray-500'
  }
  return classes[estado] || 'bg-gray-100 text-gray-700'
}
</script>
