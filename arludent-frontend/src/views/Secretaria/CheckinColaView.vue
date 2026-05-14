<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
            <Users class="w-8 h-8 text-green-600" />
            Cola de Atención
          </h1>
          <p class="text-gray-600 mt-1">Gestión de llegadas y control de pacientes</p>
        </div>
        <div class="flex items-center space-x-3">
          <Button
            variant="outline"
            @click="mostrarFiltros = !mostrarFiltros"
          >
            <Filter class="w-5 h-5" />
            <span>Filtros</span>
          </Button>
          <Button
            variant="primary"
            @click="actualizarCola"
          >
            <RefreshCw class="w-5 h-5" />
            <span>Actualizar</span>
          </Button>
        </div>
      </div>

      <!-- Estadísticas del día -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card class="border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Pacientes Hoy</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.total_pacientes_hoy }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
              <CalendarCheck class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-orange-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">En Sala de Espera</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.en_sala_espera }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
              <Clock class="w-6 h-6 text-orange-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-purple-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Siendo Atendidos</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.siendo_atendidos }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
              <UserCheck class="w-6 h-6 text-purple-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Completados Hoy</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.completados_hoy }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <CheckCircle class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Filtros -->
      <Card v-if="mostrarFiltros" class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
            <select
              v-model="filtros.estado"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              @change="aplicarFiltros"
            >
              <option value="">Todos los estados</option>
              <option value="pendiente">Pendiente</option>
              <option value="confirmado">Confirmado</option>
              <option value="en_espera">En Espera</option>
              <option value="siendo_atendido">En Atención</option>
              <option value="completado">Completado</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Médico</label>
            <select
              v-model.number="filtros.medico_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              @change="aplicarFiltros"
            >
              <option :value="undefined">Todos los médicos</option>
              <option
                v-for="medico in medicos"
                :key="medico.id_medico"
                :value="medico.id_medico"
              >
                Dr. {{ medico.nombres }} {{ medico.apellidos }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Paciente</label>
            <input
              v-model="filtros.buscar_paciente"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="Nombre, documento..."
              @input="buscarConDelay"
            />
          </div>
          <div class="flex items-end">
            <Button
              variant="outline"
              @click="limpiarFiltros"
              class="w-full"
            >
              <X class="w-4 h-4 mr-2" />
              Limpiar
            </Button>
          </div>
        </div>
      </Card>

      <!-- Loading -->
      <div v-if="cargando" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-green-200 border-t-green-600"></div>
        <p class="text-gray-600 mt-4">Cargando cola de atención...</p>
      </div>

      <!-- Cola de Atención -->
      <div v-else class="space-y-6">
        <!-- Búsqueda rápida para check-in -->
        <Card class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Registrar Llegada</h3>
          <div class="flex gap-4">
            <div class="flex-1">
              <input
                v-model="busquedaCheckIn"
                type="text"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="Buscar cita por nombre del paciente o documento..."
                @input="buscarCitasParaCheckIn"
              />
            </div>
            <Button
              variant="primary"
              @click="buscarCitasParaCheckIn"
            >
              <Search class="w-5 h-5" />
              Buscar
            </Button>
          </div>

          <!-- Resultados de búsqueda -->
          <div v-if="citasParaCheckIn.length > 0" class="mt-4 space-y-2">
            <div
              v-for="cita in citasParaCheckIn"
              :key="cita.id_cita"
              class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
              @click="registrarLlegada(cita)"
            >
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-gray-900">
                    {{ cita.paciente.nombres }} {{ cita.paciente.apellidos }}
                  </p>
                  <p class="text-sm text-gray-600">
                    {{ cita.paciente.numero_documento }} -
                    Dr. {{ cita.medico.nombres }} {{ cita.medico.apellidos }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ formatearFechaHora(cita.fecha_hora_inicio) }}
                  </p>
                </div>
                <div class="text-right">
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      getEstadoColor(cita.estado_cita)
                    ]"
                  >
                    {{ getEstadoTexto(cita.estado_cita) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Lista de pacientes en cola -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
          <div
            v-for="paciente in colaAtencion"
            :key="paciente.id_cita"
            class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
          >
            <!-- Header de la tarjeta -->
            <div
              :class="[
                'px-6 py-4 border-b border-gray-200',
                getHeaderColor(paciente.estado_cita)
              ]"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-semibold text-white">
                    {{ paciente.paciente.nombres }} {{ paciente.paciente.apellidos }}
                  </h3>
                  <p class="text-sm opacity-90">
                    {{ paciente.paciente.numero_documento }}
                  </p>
                </div>
                <div class="text-right">
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                      paciente.prioridad === 'urgente' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                    ]"
                  >
                    {{ paciente.prioridad === 'urgente' ? 'URGENTE' : 'Normal' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Contenido de la tarjeta -->
            <div class="p-6">
              <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Médico:</span>
                  <span class="font-medium">Dr. {{ paciente.medico.nombres }} {{ paciente.medico.apellidos }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Hora cita:</span>
                  <span class="font-medium">{{ formatearHora(paciente.fecha_hora_inicio) }}</span>
                </div>
                <div v-if="paciente.hora_llegada" class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Llegada:</span>
                  <span class="font-medium">{{ formatearHora(paciente.hora_llegada) }}</span>
                </div>
                <div v-if="paciente.tiempo_espera" class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Tiempo espera:</span>
                  <span class="font-medium text-orange-600">{{ paciente.tiempo_espera }}</span>
                </div>
                <div v-if="paciente.motivo" class="text-sm">
                  <span class="text-gray-600">Motivo:</span>
                  <p class="mt-1 text-gray-900">{{ paciente.motivo }}</p>
                </div>
                <div v-if="paciente.notas_secretaria" class="text-sm">
                  <span class="text-gray-600">Notas:</span>
                  <p class="mt-1 text-gray-900">{{ paciente.notas_secretaria }}</p>
                </div>
              </div>

              <!-- Acciones -->
              <div class="mt-6 flex flex-wrap gap-2">
                <Button
                  v-if="paciente.estado_cita === 'confirmado'"
                  size="sm"
                  variant="primary"
                  @click="cambiarEstado(paciente, 'en_espera')"
                >
                  <Clock class="w-4 h-4 mr-1" />
                  Marcar Llegada
                </Button>

                <Button
                  v-if="paciente.estado_cita === 'en_espera'"
                  size="sm"
                  variant="primary"
                  @click="cambiarEstado(paciente, 'siendo_atendido')"
                >
                  <UserCheck class="w-4 h-4 mr-1" />
                  Pasar a Atención
                </Button>

                <Button
                  v-if="paciente.estado_cita === 'siendo_atendido'"
                  size="sm"
                  variant="success"
                  @click="cambiarEstado(paciente, 'completado')"
                >
                  <CheckCircle class="w-4 h-4 mr-1" />
                  Completar
                </Button>

                <Button
                  v-if="['confirmado', 'en_espera'].includes(paciente.estado_cita)"
                  size="sm"
                  variant="outline"
                  @click="abrirModalNotas(paciente)"
                >
                  <FileText class="w-4 h-4 mr-1" />
                  Notas
                </Button>

                <Button
                  v-if="paciente.estado_cita !== 'completado'"
                  size="sm"
                  variant="outline"
                  @click="confirmarCancelacion(paciente)"
                >
                  <X class="w-4 h-4 mr-1" />
                  Cancelar
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-if="colaAtencion.length === 0" class="text-center py-12">
          <Users class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay pacientes en cola</h3>
          <p class="text-gray-600">Los pacientes que lleguen aparecerán aquí automáticamente.</p>
        </div>
      </div>

      <!-- Modal Notas -->
      <Modal
        :show="modalNotas"
        @close="cerrarModalNotas"
        title="Agregar Notas"
        size="md"
      >
        <div v-if="pacienteSeleccionado" class="space-y-4">
          <div>
            <p class="font-medium text-gray-900">
              {{ pacienteSeleccionado.paciente.nombres }} {{ pacienteSeleccionado.paciente.apellidos }}
            </p>
            <p class="text-sm text-gray-600">
              {{ formatearFechaHora(pacienteSeleccionado.fecha_hora_inicio) }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Notas de secretaría
            </label>
            <textarea
              v-model="notasTexto"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              rows="4"
              placeholder="Agregar notas sobre el paciente o la cita..."
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Prioridad
            </label>
            <select
              v-model="prioridadSeleccionada"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
            >
              <option value="normal">Normal</option>
              <option value="urgente">Urgente</option>
            </select>
          </div>

          <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <Button
              variant="outline"
              @click="cerrarModalNotas"
            >
              Cancelar
            </Button>
            <Button
              variant="primary"
              @click="guardarNotas"
              :disabled="guardandoNotas"
            >
              <div v-if="guardandoNotas" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                Guardando...
              </div>
              <span v-else>Guardar</span>
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Modal from '@/components/common/Modal.vue'
import { useNotification } from '@/composables/useNotification'
import checkinService, { type PacienteEnCola, type EstadisticasCheckin } from '@/api/checkinService'

// Icons
import {
  Users, Filter, RefreshCw, CalendarCheck, Clock, UserCheck, CheckCircle,
  X, Search, FileText
} from 'lucide-vue-next'

const router = useRouter()
const { showSuccess, showError, show } = useNotification()

// Interfaces
interface Medico {
  id_medico: number
  nombres: string
  apellidos: string
  especialidad?: string
}

// Estado principal
const cargando = ref(true)
const mostrarFiltros = ref(false)
const colaAtencion = ref<PacienteEnCola[]>([])
const estadisticas = ref<EstadisticasCheckin>({
  total_pacientes_hoy: 0,
  en_sala_espera: 0,
  siendo_atendidos: 0,
  completados_hoy: 0,
  tiempo_promedio_espera: '0 min',
  tiempo_promedio_atencion: '0 min',
  ocupacion_actual: {
    porcentaje: 0,
    capacidad_maxima: 0,
    pacientes_actuales: 0
  }
})
const medicos = ref<Medico[]>([])

// Filtros
const filtros = ref({
  estado: '',
  medico_id: undefined as number | undefined,
  buscar_paciente: ''
})

// Check-in
const busquedaCheckIn = ref('')
const citasParaCheckIn = ref<PacienteEnCola[]>([])

// Modal notas
const modalNotas = ref(false)
const pacienteSeleccionado = ref<PacienteEnCola | null>(null)
const notasTexto = ref('')
const prioridadSeleccionada = ref('normal')
const guardandoNotas = ref(false)

let timeoutBusqueda: ReturnType<typeof setTimeout>
let intervalActualizacion: ReturnType<typeof setInterval>

// Métodos principales
const cargarDatos = async () => {
  try {
    cargando.value = true
    const [colaData, estadisticasData] = await Promise.all([
      checkinService.getColaAtencion(filtros.value),
      checkinService.getEstadisticasCheckin()
    ])

    colaAtencion.value = colaData
    estadisticas.value = estadisticasData
  } catch (error) {
    console.error('Error al cargar datos:', error)
    showError('Error al cargar los datos de la cola')
  } finally {
    cargando.value = false
  }
}

const actualizarCola = () => {
  cargarDatos()
}

// Búsqueda para check-in
const buscarCitasParaCheckIn = async () => {
  if (busquedaCheckIn.value.length < 2) {
    citasParaCheckIn.value = []
    return
  }

  try {
    citasParaCheckIn.value = await checkinService.buscarCitasParaCheckin(busquedaCheckIn.value)
  } catch (error) {
    console.error('Error al buscar citas:', error)
    showError('Error al buscar citas')
  }
}

// Registrar llegada
const registrarLlegada = async (cita: PacienteEnCola) => {
  try {
    await checkinService.registrarLlegada({
      id_cita: cita.id_cita,
      prioridad: 'normal'
    })

    showSuccess('Llegada registrada exitosamente')
    busquedaCheckIn.value = ''
    citasParaCheckIn.value = []
    cargarDatos()
  } catch (error) {
    console.error('Error al registrar llegada:', error)
    showError('Error al registrar la llegada')
  }
}

// Cambiar estado de paciente
const cambiarEstado = async (paciente: PacienteEnCola, nuevoEstado: string) => {
  try {
    await checkinService.cambiarEstado({
      id_cita: paciente.id_cita,
      nuevo_estado: nuevoEstado as any
    })

    const estadoTexto = getEstadoTexto(nuevoEstado)
    showSuccess(`Paciente marcado como ${estadoTexto}`)
    cargarDatos()
  } catch (error) {
    console.error('Error al cambiar estado:', error)
    showError('Error al cambiar el estado')
  }
}

// Modal notas
const abrirModalNotas = (paciente: PacienteEnCola) => {
  pacienteSeleccionado.value = paciente
  notasTexto.value = paciente.notas_secretaria || ''
  prioridadSeleccionada.value = paciente.prioridad
  modalNotas.value = true
}

const cerrarModalNotas = () => {
  modalNotas.value = false
  pacienteSeleccionado.value = null
  notasTexto.value = ''
  prioridadSeleccionada.value = 'normal'
}

const guardarNotas = async () => {
  if (!pacienteSeleccionado.value) return

  try {
    guardandoNotas.value = true

    await checkinService.cambiarEstado({
      id_cita: pacienteSeleccionado.value.id_cita,
      nuevo_estado: pacienteSeleccionado.value.estado_cita as any,
      notas: notasTexto.value
    })

    showSuccess('Notas guardadas exitosamente')
    cerrarModalNotas()
    cargarDatos()
  } catch (error) {
    console.error('Error al guardar notas:', error)
    showError('Error al guardar las notas')
  } finally {
    guardandoNotas.value = false
  }
}

// Confirmar cancelación
const confirmarCancelacion = async (paciente: PacienteEnCola) => {
  const result = await show(
    '¿Cancelar esta cita?',
    `Se cancelará la cita de ${paciente.paciente.nombres} ${paciente.paciente.apellidos}`,
    'warning'
  )

  if (result.isConfirmed) {
    try {
      await cambiarEstado(paciente, 'cancelado')
    } catch (error) {
      console.error('Error al cancelar cita:', error)
      showError('Error al cancelar la cita')
    }
  }
}

// Filtros
const aplicarFiltros = () => {
  cargarDatos()
}

const buscarConDelay = () => {
  if (timeoutBusqueda) {
    clearTimeout(timeoutBusqueda)
  }
  timeoutBusqueda = setTimeout(() => {
    aplicarFiltros()
  }, 500)
}

const limpiarFiltros = () => {
  filtros.value = {
    estado: '',
    medico_id: undefined,
    buscar_paciente: ''
  }
  aplicarFiltros()
}

// Utilidades
const formatearFechaHora = (fechaHora: string) => {
  return new Date(fechaHora).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatearHora = (fechaHora: string) => {
  return new Date(fechaHora).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getEstadoTexto = (estado: string) => {
  const estados: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmado: 'Confirmado',
    en_espera: 'En Espera',
    siendo_atendido: 'En Atención',
    completado: 'Completado',
    cancelado: 'Cancelado',
    no_asistio: 'No Asistió'
  }
  return estados[estado] || estado
}

const getEstadoColor = (estado: string) => {
  const colores: Record<string, string> = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    confirmado: 'bg-blue-100 text-blue-800',
    en_espera: 'bg-orange-100 text-orange-800',
    siendo_atendido: 'bg-purple-100 text-purple-800',
    completado: 'bg-green-100 text-green-800',
    cancelado: 'bg-red-100 text-red-800',
    no_asistio: 'bg-gray-100 text-gray-800'
  }
  return colores[estado] || 'bg-gray-100 text-gray-800'
}

const getHeaderColor = (estado: string) => {
  const colores: Record<string, string> = {
    pendiente: 'bg-yellow-500',
    confirmado: 'bg-blue-500',
    en_espera: 'bg-orange-500',
    siendo_atendido: 'bg-purple-500',
    completado: 'bg-green-500',
    cancelado: 'bg-red-500',
    no_asistio: 'bg-gray-500'
  }
  return colores[estado] || 'bg-gray-500'
}

// Lifecycle
onMounted(() => {
  cargarDatos()
  // Actualizar cada 30 segundos
  intervalActualizacion = setInterval(cargarDatos, 30000)
})

onUnmounted(() => {
  if (timeoutBusqueda) {
    clearTimeout(timeoutBusqueda)
  }
  if (intervalActualizacion) {
    clearInterval(intervalActualizacion)
  }
})
</script>

<style scoped>
/* Animaciones para las tarjetas */
.fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Hover effects */
.hover\:scale-105:hover {
  transform: scale(1.05);
  transition: transform 0.2s ease-in-out;
}
</style>
