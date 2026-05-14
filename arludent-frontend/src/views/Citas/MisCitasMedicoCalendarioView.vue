<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Mis Citas</h1>
          <p class="text-gray-600 mt-1">Vista de calendario</p>
        </div>
        <div class="flex items-center space-x-3">
          <Button
            variant="outline"
            @click="cambiarVista('listado')"
          >
            <List class="w-5 h-5" />
            <span>Vista Listado</span>
          </Button>
        </div>
      </div>

      <!-- Leyenda de estados -->
      <Card class="p-4">
        <div class="flex flex-wrap items-center gap-4">
          <span class="text-sm font-medium text-gray-700">Estados:</span>
          <div class="flex items-center space-x-2">
            <div class="w-4 h-4 rounded bg-orange-500"></div>
            <span class="text-sm text-gray-600">Pendiente</span>
          </div>
          <div class="flex items-center space-x-2">
            <div class="w-4 h-4 rounded bg-green-500"></div>
            <span class="text-sm text-gray-600">Confirmada</span>
          </div>
          <div class="flex items-center space-x-2">
            <div class="w-4 h-4 rounded bg-blue-500"></div>
            <span class="text-sm text-gray-600">Reprogramada</span>
          </div>
          <div class="flex items-center space-x-2">
            <div class="w-4 h-4 rounded bg-gray-500"></div>
            <span class="text-sm text-gray-600">Completada</span>
          </div>
          <div class="flex items-center space-x-2">
            <div class="w-4 h-4 rounded bg-red-500"></div>
            <span class="text-sm text-gray-600">Cancelada</span>
          </div>
        </div>
      </Card>

      <!-- Loading -->
      <Loading v-if="cargando" :show="cargando" />

      <!-- Calendario -->
      <Card v-else class="p-6">
        <!-- Navegación del mes -->
        <div class="flex items-center justify-between mb-6">
          <Button
            variant="outline"
            size="sm"
            @click="mesAnterior"
          >
            <ChevronLeft class="w-5 h-5" />
          </Button>

          <h2 class="text-2xl font-bold text-gray-900">
            {{ nombreMes }} {{ anioActual }}
          </h2>

          <Button
            variant="outline"
            size="sm"
            @click="mesSiguiente"
          >
            <ChevronRight class="w-5 h-5" />
          </Button>
        </div>

        <!-- Días de la semana -->
        <div class="grid grid-cols-7 gap-2 mb-2">
          <div
            v-for="dia in diasSemana"
            :key="dia"
            class="text-center text-sm font-semibold text-gray-700 py-2"
          >
            {{ dia }}
          </div>
        </div>

        <!-- Días del mes -->
        <div class="grid grid-cols-7 gap-2">
          <div
            v-for="(dia, index) in diasMes"
            :key="index"
            :class="[
              'min-h-[120px] p-2 border rounded-lg transition-all',
              dia.esDelMes
                ? 'bg-white hover:bg-gray-50 border-gray-200'
                : 'bg-gray-50 border-gray-100',
              dia.esHoy ? 'ring-2 ring-primary-500' : ''
            ]"
          >
            <div class="flex items-center justify-between mb-2">
              <span
                :class="[
                  'text-sm font-medium',
                  dia.esDelMes ? 'text-gray-900' : 'text-gray-400',
                  dia.esHoy ? 'text-primary-600 font-bold' : ''
                ]"
              >
                {{ dia.numero }}
              </span>
              <span
                v-if="dia.esHoy"
                class="text-xs px-2 py-0.5 bg-primary-100 text-primary-700 rounded-full"
              >
                Hoy
              </span>
            </div>

            <!-- Citas del día -->
            <div class="space-y-1">
              <button
                v-for="cita in dia.citas"
                :key="cita.id_cita"
                @click="verDetalleCita(cita)"
                :class="[
                  'w-full text-left px-2 py-1 rounded text-xs font-medium transition-all hover:scale-105',
                  getColorCita(cita.estado)
                ]"
              >
                <div class="flex items-center space-x-1">
                  <Clock class="w-3 h-3" />
                  <span>{{ formatearHora(cita.fecha_hora_inicio) }}</span>
                </div>
                <div class="truncate mt-0.5">
                  {{ cita.paciente ? cita.paciente.nombres.split(' ')[0] : 'Sin paciente' }}
                </div>
              </button>
            </div>

            <!-- Indicador de más citas -->
            <div
              v-if="dia.citas && dia.citas.length > 3"
              class="text-xs text-gray-500 text-center mt-1"
            >
              +{{ dia.citas.length - 3 }} más
            </div>
          </div>
        </div>
      </Card>

      <!-- Modal de detalle de cita -->
      <Modal
        :show="modalDetalle"
        title="Detalle de Cita"
        @close="cerrarModalDetalle"
      >
        <div v-if="citaSeleccionada" class="space-y-4">
          <!-- Info del paciente -->
          <div class="flex items-start space-x-4">
            <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
              <User class="w-8 h-8 text-primary-600" />
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ citaSeleccionada.paciente ? citaSeleccionada.paciente.nombres + ' ' + citaSeleccionada.paciente.apellidos : 'Paciente no asignado' }}
              </h3>
              <p v-if="citaSeleccionada.paciente?.dni" class="text-sm text-gray-600">
                DNI: {{ citaSeleccionada.paciente.dni }}
              </p>
              <span
                :class="[
                  'inline-block px-3 py-1 rounded-full text-sm font-medium mt-2',
                  getBadgeClasses(citaSeleccionada.estado)
                ]"
              >
                {{ citaSeleccionada.estado }}
              </span>
            </div>
          </div>

          <!-- Detalles -->
          <div class="space-y-3 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-2">
              <Calendar class="w-5 h-5 text-gray-400" />
              <span class="text-gray-900">{{ formatearFecha(citaSeleccionada.fecha_hora_inicio) }}</span>
            </div>
            <div class="flex items-center space-x-2">
              <Clock class="w-5 h-5 text-gray-400" />
              <span class="text-gray-900">{{ formatearHora(citaSeleccionada.fecha_hora_inicio) }}</span>
            </div>
            <div v-if="citaSeleccionada.motivo" class="flex items-start space-x-2">
              <FileText class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
              <span class="text-gray-900">{{ citaSeleccionada.motivo }}</span>
            </div>
          </div>

          <!-- Notas -->
          <div v-if="citaSeleccionada.notas" class="p-3 bg-blue-50 rounded-lg">
            <div class="flex items-start space-x-2">
              <MessageSquare class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-blue-900">Notas:</p>
                <p class="text-sm text-blue-700 mt-1">{{ citaSeleccionada.notas }}</p>
              </div>
            </div>
          </div>

          <!-- Calificación -->
          <div v-if="citaSeleccionada.calificacion" class="flex items-center space-x-2 p-3 bg-yellow-50 rounded-lg">
            <Star class="w-5 h-5 text-yellow-500 fill-yellow-500" />
            <span class="text-sm font-medium text-gray-900">
              Calificación del paciente: {{ citaSeleccionada.calificacion.puntuacion }}/5
            </span>
          </div>

          <!-- Acciones -->
          <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-200">
            <Button
              variant="outline"
              size="sm"
              @click="abrirModalDetalles"
            >
              <Eye class="w-4 h-4" />
              <span>Ver Detalles</span>
            </Button>

            <Button
              v-if="citaSeleccionada.puede_agregar_notas"
              variant="outline"
              size="sm"
              @click="abrirModalNotas"
            >
              <MessageSquare class="w-4 h-4" />
              <span>Notas</span>
            </Button>

            <Button
              v-if="citaSeleccionada.puede_completar"
              variant="success"
              size="sm"
              @click="abrirModalCompletar"
            >
              <CheckCircle class="w-4 h-4" />
              <span>Completar</span>
            </Button>

            <Button
              v-if="citaSeleccionada.puede_cancelar"
              variant="danger"
              size="sm"
              @click="abrirModalCancelar"
            >
              <XCircle class="w-4 h-4" />
              <span>Cancelar</span>
            </Button>
          </div>
        </div>
      </Modal>
    </div>

    <!-- Modales de acciones -->
    <ModalDetallesCita
      :show="modales.detalles"
      :cita="citaSeleccionada"
      :isMedicoView="true"
      @close="cerrarModalesAccion"
      @completar="abrirModalCompletar"
      @agregarNotas="abrirModalNotas"
      @cancelar="abrirModalCancelar"
    />

    <ModalCancelarCita
      :show="modales.cancelar"
      :cita="citaSeleccionada"
      :isMedicoView="true"
      @close="cerrarModalesAccion"
      @cancelar="cancelarCita"
    />

    <ModalCompletarCita
      :show="modales.completar"
      :cita="citaSeleccionada"
      @close="cerrarModalesAccion"
      @completar="completarCita"
    />

    <ModalAgregarNotas
      :show="modales.notas"
      :cita="citaSeleccionada"
      @close="cerrarModalesAccion"
      @agregar="agregarNotas"
    />
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Calendar,
  Clock,
  User,
  List,
  ChevronLeft,
  ChevronRight,
  FileText,
  MessageSquare,
  Star,
  CheckCircle,
  XCircle,
  Eye
} from 'lucide-vue-next'
import {
  format,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  isSameDay,
  isToday,
  isSameMonth,
  addMonths,
  subMonths
} from 'date-fns'
import { es } from 'date-fns/locale'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Loading from '@/components/common/Loading.vue'
import Modal from '@/components/common/Modal.vue'
import ModalDetallesCita from '@/components/citas/ModalDetallesCita.vue'
import ModalCancelarCita from '@/components/citas/ModalCancelarCita.vue'
import ModalCompletarCita from '@/components/citas/ModalCompletarCita.vue'
import ModalAgregarNotas from '@/components/citas/ModalAgregarNotas.vue'
import citasMedicoService from '@/api/citasMedicoService'
import type { CitaMedico } from '@/api/citasMedicoService'
import { useNotification } from '@/composables/useNotification'

const router = useRouter()
const { showSuccess, showError } = useNotification()

const cargando = ref(false)
const fechaActual = ref(new Date())
const citas = ref<CitaMedico[]>([])
const citaSeleccionada = ref<CitaMedico | null>(null)
const modalDetalle = ref(false)

const modales = ref({
  detalles: false,
  cancelar: false,
  completar: false,
  notas: false
})

const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const mesActual = computed(() => fechaActual.value.getMonth())
const anioActual = computed(() => fechaActual.value.getFullYear())
const nombreMes = computed(() =>
  format(fechaActual.value, 'MMMM', { locale: es }).charAt(0).toUpperCase() +
  format(fechaActual.value, 'MMMM', { locale: es }).slice(1)
)

const diasMes = computed(() => {
  const inicio = startOfMonth(fechaActual.value)
  const fin = endOfMonth(fechaActual.value)

  // Agregar días del mes anterior
  const diasAnteriores = inicio.getDay()
  const inicioCalendario = new Date(inicio)
  inicioCalendario.setDate(inicioCalendario.getDate() - diasAnteriores)

  // Agregar días del mes siguiente
  const diasSiguientes = 6 - fin.getDay()
  const finCalendario = new Date(fin)
  finCalendario.setDate(finCalendario.getDate() + diasSiguientes)

  const todosDias = eachDayOfInterval({ start: inicioCalendario, end: finCalendario })

  return todosDias.map(dia => {
    const citasDelDia = citas.value.filter(cita =>
      isSameDay(new Date(cita.fecha_hora_inicio), dia)
    )

    return {
      fecha: dia,
      numero: dia.getDate(),
      esDelMes: isSameMonth(dia, fechaActual.value),
      esHoy: isToday(dia),
      citas: citasDelDia.slice(0, 3) // Mostrar máximo 3 citas
    }
  })
})

onMounted(() => {
  cargarCitas()
})

const cargarCitas = async () => {
  cargando.value = true
  try {
    // Cargar citas de 3 meses atrás y 6 meses adelante
    const desde = format(subMonths(fechaActual.value, 3), 'yyyy-MM-dd')
    const hasta = format(addMonths(fechaActual.value, 6), 'yyyy-MM-dd')

    const response = await citasMedicoService.obtenerMisCitasCalendario({ desde, hasta })
    if (response.success) {
      citas.value = response.data.eventos.map((evento: any) => ({
        id_cita: evento.id,
        fecha_hora_inicio: evento.start,
        fecha_hora_fin: evento.end,
        motivo: evento.extendedProps.motivo,
        estado: evento.extendedProps.estado,
        notas: evento.extendedProps.notas,
        paciente: {
          id_paciente: 0,
          nombres: evento.extendedProps.paciente.split(' ')[0] || 'Sin',
          apellidos: evento.extendedProps.paciente.split(' ').slice(1).join(' ') || 'paciente',
          dni: null,
          telefono: null
        },
        puede_cancelar: evento.extendedProps.estado !== 'cancelado' && evento.extendedProps.estado !== 'completado',
        puede_completar: evento.extendedProps.estado === 'confirmado',
        puede_agregar_notas: true,
        calificacion: null,
        created_at: '',
        updated_at: ''
      }))
    }
  } catch (error: any) {
    showError(error.response?.data?.message || 'Error al cargar las citas')
  } finally {
    cargando.value = false
  }
}

const mesAnterior = () => {
  fechaActual.value = subMonths(fechaActual.value, 1)
  cargarCitas()
}

const mesSiguiente = () => {
  fechaActual.value = addMonths(fechaActual.value, 1)
  cargarCitas()
}

const cambiarVista = (vista: string) => {
  router.push({ name: 'medico-mis-citas-listado' })
}

const verDetalleCita = (cita: CitaMedico) => {
  citaSeleccionada.value = cita
  modalDetalle.value = true
}

const cerrarModalDetalle = () => {
  modalDetalle.value = false
  setTimeout(() => {
    citaSeleccionada.value = null
  }, 300)
}

// Modales de acciones
const abrirModalDetalles = () => {
  modalDetalle.value = false
  modales.value.detalles = true
}

const abrirModalCancelar = () => {
  modalDetalle.value = false
  modales.value.cancelar = true
}

const abrirModalCompletar = () => {
  modalDetalle.value = false
  modales.value.completar = true
}

const abrirModalNotas = () => {
  modalDetalle.value = false
  modales.value.notas = true
}

const cerrarModalesAccion = () => {
  modales.value = {
    detalles: false,
    cancelar: false,
    completar: false,
    notas: false
  }
}

// Acciones
const cancelarCita = async (datos: any) => {
  if (!citaSeleccionada.value) return

  try {
    const response = await citasMedicoService.cancelarCita(citaSeleccionada.value.id_cita, datos)
    if (response.success) {
      showSuccess('Cita cancelada exitosamente')
      cerrarModalesAccion()
      cargarCitas()
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
      cerrarModalesAccion()
      cargarCitas()
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
      cerrarModalesAccion()
      cargarCitas()
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

const getColorCita = (estado: string) => {
  const colores: Record<string, string> = {
    pendiente: 'bg-orange-500 text-white hover:bg-orange-600',
    confirmada: 'bg-green-500 text-white hover:bg-green-600',
    reprogramada: 'bg-blue-500 text-white hover:bg-blue-600',
    completada: 'bg-gray-500 text-white hover:bg-gray-600',
    cancelada: 'bg-red-500 text-white hover:bg-red-600',
    no_asistio: 'bg-gray-400 text-white hover:bg-gray-500'
  }
  return colores[estado] || 'bg-gray-500 text-white'
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
