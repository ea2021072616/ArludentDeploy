<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
            <Calendar class="w-8 h-8 text-blue-600" />
            Agenda - Secretaría
          </h1>
          <p class="text-gray-600 mt-1">Gestión completa de citas y calendario</p>
        </div>
        <div class="flex items-center space-x-3">
          <Button
            variant="primary"
            @click="abrirModalNuevaCita"
          >
            <Plus class="w-5 h-5" />
            <span>Nueva Cita</span>
          </Button>
          <Button
            variant="outline"
            @click="mostrarFiltros = !mostrarFiltros"
          >
            <Filter class="w-5 h-5" />
            <span>Filtros</span>
          </Button>
        </div>
      </div>

      <!-- Filtros Avanzados -->
      <Card v-if="mostrarFiltros" class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros Avanzados</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Médico</label>
            <select
              v-model="filtros.medico_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="aplicarFiltros"
            >
              <option value="">Todos los médicos</option>
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
            <select
              v-model="filtros.estado"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="aplicarFiltros"
            >
              <option value="">Todos los estados</option>
              <option value="pendiente">Pendiente</option>
              <option value="confirmado">Confirmado</option>
              <option value="en_espera">En Espera</option>
              <option value="siendo_atendido">Siendo Atendido</option>
              <option value="completado">Completado</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Paciente</label>
            <input
              v-model="filtros.buscar_paciente"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
            <span class="text-sm text-gray-600">En Espera</span>
          </div>
          <div class="flex items-center space-x-2">
            <div class="w-4 h-4 rounded bg-purple-500"></div>
            <span class="text-sm text-gray-600">En Atención</span>
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
      <div v-if="cargando" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
        <p class="text-gray-600 mt-4">Cargando calendario...</p>
      </div>

      <!-- Calendario -->
      <Card v-else class="p-6">
        <div id="calendar" class="w-full"></div>
      </Card>

      <!-- Modal Nueva Cita -->
      <Modal
        :show="modalNuevaCita"
        @close="cerrarModalNuevaCita"
        title="Nueva Cita"
        size="xl"
      >
        <form @submit.prevent="guardarCita" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Buscar Paciente -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Paciente <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="busquedaPaciente"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Buscar por nombre o documento..."
                  @input="buscarPacientes"
                />
                <!-- Lista de pacientes encontrados -->
                <div
                  v-if="pacientesEncontrados.length > 0"
                  class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                >
                  <div
                    v-for="paciente in pacientesEncontrados"
                    :key="paciente.id_paciente"
                    class="px-4 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                    @click="seleccionarPaciente(paciente)"
                  >
                    <div class="flex justify-between items-center">
                      <div>
                        <p class="font-medium text-gray-900">{{ paciente.nombres }} {{ paciente.apellidos }}</p>
                        <p class="text-sm text-gray-600">{{ paciente.numero_documento }}</p>
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ paciente.telefono }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Paciente seleccionado -->
              <div v-if="nuevaCita.paciente_id" class="mt-2 p-3 bg-blue-50 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-medium text-blue-900">{{ pacienteSeleccionado?.nombres }} {{ pacienteSeleccionado?.apellidos }}</p>
                    <p class="text-sm text-blue-700">{{ pacienteSeleccionado?.numero_documento }}</p>
                  </div>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="limpiarPacienteSeleccionado"
                  >
                    <X class="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </div>

            <!-- Médico -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Médico <span class="text-red-500">*</span>
              </label>
              <select
                v-model="nuevaCita.medico_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              >
                <option value="">Seleccionar médico</option>
                <option
                  v-for="medico in medicos"
                  :key="medico.id_medico"
                  :value="medico.id_medico"
                >
                  Dr. {{ medico.nombres }} {{ medico.apellidos }} - {{ medico.especialidad }}
                </option>
              </select>
            </div>

            <!-- Fecha y Hora -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Fecha y Hora <span class="text-red-500">*</span>
              </label>
              <input
                v-model="nuevaCita.fecha_hora_inicio"
                type="datetime-local"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>

            <!-- Duración -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Duración (minutos)
              </label>
              <select
                v-model="nuevaCita.duracion"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="30">30 minutos</option>
                <option value="45">45 minutos</option>
                <option value="60">60 minutos</option>
                <option value="90">90 minutos</option>
                <option value="120">120 minutos</option>
              </select>
            </div>

            <!-- Motivo -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Motivo
              </label>
              <input
                v-model="nuevaCita.motivo"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Motivo de la cita"
              />
            </div>

            <!-- Notas -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Notas adicionales
              </label>
              <textarea
                v-model="nuevaCita.notas"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                rows="3"
                placeholder="Notas o instrucciones adicionales..."
              ></textarea>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <Button
              type="button"
              variant="outline"
              @click="cerrarModalNuevaCita"
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="primary"
              :disabled="guardandoCita"
            >
              <div v-if="guardandoCita" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                Guardando...
              </div>
              <span v-else>Guardar Cita</span>
            </Button>
          </div>
        </form>
      </Modal>

      <!-- Modal Detalle/Edición Cita -->
      <Modal
        :show="modalDetalleCita && citaSeleccionada !== null"
        @close="cerrarModalDetalleCita"
        :title="`Cita - ${citaSeleccionada?.paciente?.nombres} ${citaSeleccionada?.paciente?.apellidos}`"
        size="xl"
      >
        <div v-if="citaSeleccionada" class="space-y-6">
          <!-- Información del paciente -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">Información del Paciente</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Nombre:</span>
                <span class="ml-2 font-medium">{{ citaSeleccionada.paciente?.nombres }} {{ citaSeleccionada.paciente?.apellidos }}</span>
              </div>
              <div>
                <span class="text-gray-600">Documento:</span>
                <span class="ml-2 font-medium">{{ citaSeleccionada.paciente?.numero_documento }}</span>
              </div>
              <div>
                <span class="text-gray-600">Teléfono:</span>
                <span class="ml-2 font-medium">{{ citaSeleccionada.paciente?.telefono || 'No registrado' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Email:</span>
                <span class="ml-2 font-medium">{{ citaSeleccionada.paciente?.email || 'No registrado' }}</span>
              </div>
            </div>
          </div>

          <!-- Información de la cita -->
          <div>
            <h3 class="font-semibold text-gray-900 mb-4">Detalles de la Cita</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha y Hora</label>
                <input
                  v-model="citaEditada.fecha_hora_inicio"
                  type="datetime-local"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :disabled="!editandoCita"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select
                  v-model="citaEditada.estado"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :disabled="!editandoCita"
                >
                  <option value="pendiente">Pendiente</option>
                  <option value="confirmado">Confirmado</option>
                  <option value="en_espera">En Espera</option>
                  <option value="siendo_atendido">Siendo Atendido</option>
                  <option value="completado">Completado</option>
                  <option value="cancelado">Cancelado</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo</label>
                <input
                  v-model="citaEditada.motivo"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :disabled="!editandoCita"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Médico</label>
                <select
                  v-model="citaEditada.medico_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :disabled="!editandoCita"
                >
                  <option
                    v-for="medico in medicos"
                    :key="medico.id_medico"
                    :value="medico.id_medico"
                  >
                    Dr. {{ medico.nombres }} {{ medico.apellidos }}
                  </option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                <textarea
                  v-model="citaEditada.notas"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  rows="3"
                  :disabled="!editandoCita"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex justify-between pt-6 border-t border-gray-200">
            <div class="flex space-x-3">
              <Button
                v-if="!editandoCita"
                variant="primary"
                @click="habilitarEdicion"
              >
                <Edit class="w-4 h-4 mr-2" />
                Editar
              </Button>
              <Button
                v-if="citaSeleccionada && citaSeleccionada.estado !== 'cancelado'"
                variant="outline"
                @click="confirmarCancelacion"
              >
                <X class="w-4 h-4 mr-2" />
                Cancelar Cita
              </Button>
            </div>
            <div class="flex space-x-3">
              <Button
                v-if="editandoCita"
                variant="outline"
                @click="cancelarEdicion"
              >
                Cancelar
              </Button>
              <Button
                v-if="editandoCita"
                variant="primary"
                @click="guardarCambiosCita"
                :disabled="guardandoCambios"
              >
                <div v-if="guardandoCambios" class="flex items-center">
                  <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                  Guardando...
                </div>
                <span v-else>Guardar Cambios</span>
              </Button>
              <Button
                v-if="!editandoCita"
                variant="outline"
                @click="cerrarModalDetalleCita"
              >
                Cerrar
              </Button>
            </div>
          </div>
        </div>
      </Modal>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Modal from '@/components/common/Modal.vue'
import { useNotification } from '@/composables/useNotification'

// Importar servicios (cuando estén implementados)
// import citasSecretariaService from '@/api/citasSecretariaService'
// import pacienteService from '@/api/pacienteService'
// import medicoService from '@/api/medicoService'

// Icons
import {
  Calendar, Plus, Filter, X, Edit, Users
} from 'lucide-vue-next'

// FullCalendar imports (cuando esté disponible)
// import FullCalendar from '@fullcalendar/vue3'
// import dayGridPlugin from '@fullcalendar/daygrid'
// import timeGridPlugin from '@fullcalendar/timegrid'
// import interactionPlugin from '@fullcalendar/interaction'
// import esLocale from '@fullcalendar/core/locales/es'

const router = useRouter()
const { showSuccess, showError, show } = useNotification()

// Interfaces
interface Medico {
  id_medico: number
  nombres: string
  apellidos: string
  especialidad: string
}

interface Paciente {
  id_paciente: number
  nombres: string
  apellidos: string
  numero_documento: string
  telefono?: string
  email?: string
}

interface Cita {
  id_cita: number
  paciente_id: number
  medico_id: number
  fecha_hora_inicio: string
  estado: string
  motivo?: string
  notas?: string
  paciente?: Paciente
  medico?: Medico
}

// Estado principal
const cargando = ref(true)
const mostrarFiltros = ref(false)
const eventos = ref<any[]>([])
const medicos = ref<Medico[]>([])

// Filtros
const filtros = ref({
  medico_id: '',
  estado: '',
  buscar_paciente: ''
})

// Modal Nueva Cita
const modalNuevaCita = ref(false)
const guardandoCita = ref(false)
const nuevaCita = ref({
  paciente_id: '',
  medico_id: '',
  fecha_hora_inicio: '',
  duracion: '30',
  motivo: '',
  notas: ''
})

// Búsqueda de pacientes
const busquedaPaciente = ref('')
const pacientesEncontrados = ref<Paciente[]>([])
const pacienteSeleccionado = ref<Paciente | null>(null)

// Modal Detalle Cita
const modalDetalleCita = ref(false)
const citaSeleccionada = ref<Cita | null>(null)
const citaEditada = ref<Partial<Cita>>({})
const editandoCita = ref(false)
const guardandoCambios = ref(false)

let timeoutBusqueda: ReturnType<typeof setTimeout>

// Métodos principales
const cargarDatos = async () => {
  try {
    cargando.value = true
    // TODO: Implementar carga de datos
    // const [citasData, medicosData] = await Promise.all([
    //   citasSecretariaService.getCalendario(filtros.value),
    //   medicoService.getMedicos()
    // ])
    // eventos.value = citasData
    // medicos.value = medicosData

    // Datos de ejemplo para desarrollo
    medicos.value = [
      { id_medico: 1, nombres: 'Juan', apellidos: 'Pérez', especialidad: 'Odontología General' },
      { id_medico: 2, nombres: 'María', apellidos: 'González', especialidad: 'Ortodoncia' }
    ]

    await nextTick()
    inicializarCalendario()
  } catch (error) {
    console.error('Error al cargar datos:', error)
    showError('Error al cargar los datos del calendario')
  } finally {
    cargando.value = false
  }
}

const inicializarCalendario = () => {
  // TODO: Implementar FullCalendar
  // const calendarEl = document.getElementById('calendar')
  // if (calendarEl) {
  //   const calendar = new FullCalendar.Calendar(calendarEl, {
  //     plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  //     locale: esLocale,
  //     headerToolbar: {
  //       left: 'prev,next today',
  //       center: 'title',
  //       right: 'dayGridMonth,timeGridWeek,timeGridDay'
  //     },
  //     events: eventos.value,
  //     eventClick: handleEventClick,
  //     dateClick: handleDateClick,
  //     editable: true,
  //     eventDrop: handleEventDrop
  //   })
  //   calendar.render()
  // }
}

// Gestión de modales
const abrirModalNuevaCita = () => {
  modalNuevaCita.value = true
  resetearFormularioNuevaCita()
}

const cerrarModalNuevaCita = () => {
  modalNuevaCita.value = false
  resetearFormularioNuevaCita()
}

const resetearFormularioNuevaCita = () => {
  nuevaCita.value = {
    paciente_id: '',
    medico_id: '',
    fecha_hora_inicio: '',
    duracion: '30',
    motivo: '',
    notas: ''
  }
  busquedaPaciente.value = ''
  pacientesEncontrados.value = []
  pacienteSeleccionado.value = null
}

// Búsqueda de pacientes
const buscarPacientes = () => {
  if (timeoutBusqueda) {
    clearTimeout(timeoutBusqueda)
  }

  timeoutBusqueda = setTimeout(async () => {
    if (busquedaPaciente.value.length < 2) {
      pacientesEncontrados.value = []
      return
    }

    try {
      // TODO: Implementar búsqueda real
      // pacientesEncontrados.value = await pacienteService.buscarPacientes(busquedaPaciente.value)

      // Datos de ejemplo
      pacientesEncontrados.value = [
        {
          id_paciente: 1,
          nombres: 'Ana',
          apellidos: 'García',
          numero_documento: '12345678',
          telefono: '123456789'
        }
      ]
    } catch (error) {
      console.error('Error al buscar pacientes:', error)
    }
  }, 300)
}

const seleccionarPaciente = (paciente: any) => {
  pacienteSeleccionado.value = paciente
  nuevaCita.value.paciente_id = paciente.id_paciente
  busquedaPaciente.value = `${paciente.nombres} ${paciente.apellidos}`
  pacientesEncontrados.value = []
}

const limpiarPacienteSeleccionado = () => {
  pacienteSeleccionado.value = null
  nuevaCita.value.paciente_id = ''
  busquedaPaciente.value = ''
  pacientesEncontrados.value = []
}

// Gestión de citas
const guardarCita = async () => {
  try {
    guardandoCita.value = true

    // TODO: Implementar guardado real
    // await citasSecretariaService.crearCita(nuevaCita.value)

    showSuccess('Cita creada exitosamente')
    cerrarModalNuevaCita()
    cargarDatos()
  } catch (error) {
    console.error('Error al guardar cita:', error)
    showError('Error al crear la cita')
  } finally {
    guardandoCita.value = false
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
    medico_id: '',
    estado: '',
    buscar_paciente: ''
  }
  aplicarFiltros()
}

// Modal detalle cita
const cerrarModalDetalleCita = () => {
  modalDetalleCita.value = false
  citaSeleccionada.value = null
  editandoCita.value = false
}

const habilitarEdicion = () => {
  editandoCita.value = true
  citaEditada.value = { ...citaSeleccionada.value }
}

const cancelarEdicion = () => {
  editandoCita.value = false
  citaEditada.value = {}
}

const guardarCambiosCita = async () => {
  try {
    guardandoCambios.value = true

    // TODO: Implementar actualización real
    // await citasSecretariaService.actualizarCita(citaSeleccionada.value.id_cita, citaEditada.value)

    showSuccess('Cita actualizada exitosamente')
    cerrarModalDetalleCita()
    cargarDatos()
  } catch (error) {
    console.error('Error al actualizar cita:', error)
    showError('Error al actualizar la cita')
  } finally {
    guardandoCambios.value = false
  }
}

const confirmarCancelacion = async () => {
  const result = await show(
    '¿Estás seguro de cancelar esta cita?',
    'Esta acción no se puede deshacer',
    'warning'
  )

  if (result.isConfirmed) {
    try {
      // TODO: Implementar cancelación
      // await citasSecretariaService.cancelarCita(citaSeleccionada.value.id_cita)

      showSuccess('Cita cancelada exitosamente')
      cerrarModalDetalleCita()
      cargarDatos()
    } catch (error) {
      console.error('Error al cancelar cita:', error)
      showError('Error al cancelar la cita')
    }
  }
}

// Lifecycle
onMounted(() => {
  cargarDatos()
})

onUnmounted(() => {
  if (timeoutBusqueda) {
    clearTimeout(timeoutBusqueda)
  }
})
</script>

<style scoped>
/* Estilos específicos para el calendario */
#calendar {
  min-height: 600px;
}

/* Personalización de FullCalendar si es necesario */
:deep(.fc-event) {
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
}

:deep(.fc-daygrid-event) {
  padding: 2px 4px;
}
</style>
