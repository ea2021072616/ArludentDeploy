<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
            <Users class="w-8 h-8 text-blue-600" />
            Consulta de Pacientes
          </h1>
          <p class="text-gray-600 mt-1">Búsqueda y visualización de información de pacientes</p>
        </div>
        <div class="flex items-center space-x-3">
          <Button
            variant="outline"
            @click="mostrarFiltros = !mostrarFiltros"
          >
            <Filter class="w-5 h-5" />
            <span>Filtros</span>
          </Button>
        </div>
      </div>

      <!-- Estadísticas generales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card class="border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Pacientes</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.total_pacientes }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
              <Users class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Citas Pendientes</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.citas_pendientes }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <UserCheck class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-orange-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Citas Hoy</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.citas_hoy }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
              <UserPlus class="w-6 h-6 text-orange-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-purple-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Próximas Citas</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.pacientes_citas_proximas }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
              <Calendar class="w-6 h-6 text-purple-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Filtros avanzados -->
      <Card v-if="mostrarFiltros" class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Búsqueda Avanzada</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar por nombre</label>
            <input
              v-model="filtros.nombre"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Nombre del paciente..."
              @input="buscarConDelay"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Documento</label>
            <input
              v-model="filtros.documento"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Número de documento..."
              @input="buscarConDelay"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
            <input
              v-model="filtros.telefono"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Número de teléfono..."
              @input="buscarConDelay"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input
              v-model="filtros.email"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Email del paciente..."
              @input="buscarConDelay"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
            <select
              v-model="filtros.estado"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="aplicarFiltros"
            >
              <option value="">Todos</option>
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha registro</label>
            <input
              v-model="filtros.fecha_desde"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="aplicarFiltros"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hasta fecha</label>
            <input
              v-model="filtros.fecha_hasta"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="aplicarFiltros"
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
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
        <p class="text-gray-600 mt-4">Cargando pacientes...</p>
      </div>

      <!-- Lista de pacientes -->
      <Card v-else class="overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
              Lista de Pacientes ({{ paginacion.total }} total)
            </h3>
            <div class="flex items-center space-x-3">
              <!-- Buscador en la lista: filtra la tabla de pacientes -->
              <div class="relative">
                <input
                  v-model="filtros.buscar"
                  @input="buscarConDelay"
                  type="text"
                  class="px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64"
                  placeholder="Buscar en la lista por DNI, nombre o apellido..."
                  aria-label="Buscar en la lista de pacientes"
                />
                <button
                  v-if="filtros.buscar"
                  @click="limpiarBusquedaRapida"
                  type="button"
                  class="absolute right-1 top-1 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded"
                >
                  Limpiar
                </button>
              </div>

              <select
                v-model="paginacion.per_page"
                class="px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                @change="aplicarFiltros"
              >
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Tabla de pacientes -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Paciente
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Documento
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Contacto
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Última Cita
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Estado
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="paciente in pacientes"
                :key="paciente.id_paciente"
                class="hover:bg-gray-50"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                      <User class="w-5 h-5 text-blue-600" />
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">
                        {{ paciente.nombre }} {{ paciente.apellidos }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ paciente.edad ? `${paciente.edad} años` : (paciente.fecha_nacimiento ? `${calcularEdad(paciente.fecha_nacimiento)} años` : 'Sin edad registrada') }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ paciente.dni }}</div>
                  <div class="text-sm text-gray-500">DNI</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ paciente.telefono || 'N/A' }}</div>
                  <div class="text-sm text-gray-500">{{ paciente.email || 'Sin email' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="paciente.ultima_cita" class="text-sm text-gray-900">
                    {{ formatearFecha(paciente.ultima_cita.fecha) }}
                  </div>
                  <div v-else class="text-sm text-gray-500">Sin citas</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      paciente.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ paciente.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <Button
                      size="sm"
                      variant="outline"
                      @click="verFichaCompleta(paciente)"
                      title="Ver información completa"
                    >
                      <Eye class="w-4 h-4" />
                    </Button>
                    <Button
                      size="sm"
                      variant="primary"
                      @click="crearCita(paciente)"
                      title="Agendar cita"
                    >
                      <Calendar class="w-4 h-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ (paginacion.current_page - 1) * paginacion.per_page + 1 }}
            a {{ Math.min(paginacion.current_page * paginacion.per_page, paginacion.total) }}
            de {{ paginacion.total }} resultados
          </div>
          <div class="flex space-x-2">
            <Button
              size="sm"
              variant="outline"
              :disabled="paginacion.current_page === 1"
              @click="cambiarPagina(paginacion.current_page - 1)"
            >
              Anterior
            </Button>
            <Button
              size="sm"
              variant="outline"
              :disabled="paginacion.current_page === paginacion.last_page"
              @click="cambiarPagina(paginacion.current_page + 1)"
            >
              Siguiente
            </Button>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-if="pacientes.length === 0" class="text-center py-12">
          <Users class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No se encontraron pacientes</h3>
          <p class="text-gray-600">Intenta ajustar los filtros de búsqueda.</p>
        </div>
      </Card>

      <!-- Modal Nuevo/Editar Paciente -->
      <Modal
        :show="modalPaciente"
        @close="cerrarModalPaciente"
        :title="pacienteEditando ? 'Editar Paciente' : 'Nuevo Paciente'"
        size="xl"
      >
        <form @submit.prevent="guardarPaciente" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Información personal -->
            <div class="md:col-span-2">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Información Personal</h3>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nombres <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formularioPaciente.nombres"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Apellidos <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formularioPaciente.apellidos"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tipo de Documento
              </label>
              <select
                v-model="formularioPaciente.tipo_documento"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="CC">Cédula de Ciudadanía</option>
                <option value="TI">Tarjeta de Identidad</option>
                <option value="CE">Cédula de Extranjería</option>
                <option value="PAS">Pasaporte</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Número de Documento <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formularioPaciente.numero_documento"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Fecha de Nacimiento
              </label>
              <input
                v-model="formularioPaciente.fecha_nacimiento"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Género
              </label>
              <select
                v-model="formularioPaciente.genero"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
                <option value="O">Otro</option>
              </select>
            </div>

            <!-- Información de contacto -->
            <div class="md:col-span-2">
              <h3 class="text-lg font-semibold text-gray-900 mb-4 mt-6">Información de Contacto</h3>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Teléfono
              </label>
              <input
                v-model="formularioPaciente.telefono"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Email
              </label>
              <input
                v-model="formularioPaciente.email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Dirección
              </label>
              <textarea
                v-model="formularioPaciente.direccion"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                rows="2"
              ></textarea>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <Button
              type="button"
              variant="outline"
              @click="cerrarModalPaciente"
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="primary"
              :disabled="guardandoPaciente"
            >
              <div v-if="guardandoPaciente" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                Guardando...
              </div>
              <span v-else>{{ pacienteEditando ? 'Actualizar' : 'Crear' }} Paciente</span>
            </Button>
          </div>
        </form>
      </Modal>

      <!-- Modal Ficha Completa -->
      <Modal
        :show="modalFicha"
        @close="cerrarModalFicha"
        :title="`Ficha Completa - ${pacienteSeleccionado?.nombre} ${pacienteSeleccionado?.apellidos}`"
        size="xl"
      >
        <div v-if="pacienteSeleccionado" class="space-y-6">
          <!-- Información básica -->
          <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Información Personal</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-sm text-gray-600">DNI:</span>
                <p class="font-medium">{{ pacienteSeleccionado.dni }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-600">Fecha de nacimiento:</span>
                <p class="font-medium">{{ pacienteSeleccionado.fecha_nacimiento || 'N/A' }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-600">Teléfono:</span>
                <p class="font-medium">{{ pacienteSeleccionado.telefono || 'N/A' }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-600">Email:</span>
                <p class="font-medium">{{ pacienteSeleccionado.email || 'N/A' }}</p>
              </div>
              <div class="col-span-2">
                <span class="text-sm text-gray-600">Dirección:</span>
                <p class="font-medium">{{ pacienteSeleccionado.domicilio || 'N/A' }}</p>
              </div>
            </div>
          </div>

          <!-- Estadísticas de citas -->
          <div v-if="fichaCompleta">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Estadísticas de Citas</h3>
            <div class="grid grid-cols-3 gap-4">
              <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">{{ fichaCompleta.total_citas }}</p>
                <p class="text-sm text-gray-600">Total Citas</p>
              </div>
              <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ fichaCompleta.citas_completadas }}</p>
                <p class="text-sm text-gray-600">Completadas</p>
              </div>
              <div class="text-center p-4 bg-orange-50 rounded-lg">
                <p class="text-2xl font-bold text-orange-600">{{ fichaCompleta.citas_pendientes }}</p>
                <p class="text-sm text-gray-600">Pendientes</p>
              </div>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <Button
              variant="primary"
              @click="crearCita(pacienteSeleccionado)"
            >
              <Calendar class="w-4 h-4 mr-2" />
              Agendar Cita
            </Button>
            <Button
              variant="outline"
              @click="cerrarModalFicha"
            >
              Cerrar
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Modal from '@/components/common/Modal.vue'
import { useNotification } from '@/composables/useNotification'
import pacienteSecretariaService from '@/api/pacienteSecretariaService'

// Icons
import {
  Users, Filter, Plus, X, UserCheck, UserPlus, AlertTriangle, User,
  Eye, Calendar
} from 'lucide-vue-next'

const router = useRouter()
const { showSuccess, showError } = useNotification()

// Interfaces
interface Paciente {
  id_paciente: number
  nombre: string // Campo correcto de la BD
  apellidos: string
  dni: string // Campo correcto de la BD
  sexo?: string // Campo correcto de la BD
  domicilio?: string // Campo correcto de la BD
  fecha_nacimiento?: string
  edad?: number // Viene calculado del backend
  telefono?: string
  email?: string
  estado: 'activo' | 'inactivo'
  ultima_cita?: any
}

interface EstadisticasPacientes {
  total_pacientes: number
  citas_pendientes: number
  citas_hoy: number
  pacientes_citas_proximas: number
}

// Estado principal
const cargando = ref(true)
const mostrarFiltros = ref(false)
const pacientes = ref<Paciente[]>([])
const estadisticas = ref<EstadisticasPacientes>({
  total_pacientes: 0,
  citas_pendientes: 0,
  citas_hoy: 0,
  pacientes_citas_proximas: 0
})

// Paginación
const paginacion = ref({
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0
})

// Filtros
const filtros = ref({
  nombre: '',
  documento: '',
  telefono: '',
  email: '',
  estado: '',
  fecha_desde: '',
  fecha_hasta: '',
  // Búsqueda rápida para lista y header
  buscar: ''
})

// Modal paciente
const modalPaciente = ref(false)
const pacienteEditando = ref<Paciente | null>(null)
const guardandoPaciente = ref(false)
const formularioPaciente = ref({
  nombres: '',
  apellidos: '',
  tipo_documento: 'CC',
  numero_documento: '',
  fecha_nacimiento: '',
  genero: 'M',
  telefono: '',
  email: '',
  direccion: ''
})

// Modal ficha completa
const modalFicha = ref(false)
const pacienteSeleccionado = ref<Paciente | null>(null)
const fichaCompleta = ref<{
  total_citas: number
  citas_completadas: number
  citas_pendientes: number
} | null>(null)

let timeoutBusqueda: ReturnType<typeof setTimeout>

// Métodos principales
const cargarDatos = async () => {
  try {
    cargando.value = true

    // Cargar solo pacientes desde la BD
    const pacientesData = await pacienteSecretariaService.busquedaAvanzada({
      ...filtros.value,
      page: paginacion.value.current_page,
      per_page: paginacion.value.per_page
    })

    console.log('📦 Datos de pacientes recibidos:', pacientesData)

    // Actualizar datos de pacientes
    let listaPacientes = pacientesData.data?.pacientes || pacientesData.pacientes || []

    // Normalizar cada paciente para tener campos consistentes en el frontend
    listaPacientes = (listaPacientes || []).map((p: any) => {
      const normalized: any = { ...p }
      // Asegurar campo 'dni' usado en la vista
      normalized.dni = p.dni || p.numero_documento || p.numeroDocumento || p.documento || ''
      // Asegurar nombre/apellidos
      normalized.nombre = p.nombre || p.nombres || ''
      normalized.apellidos = p.apellidos || p.apellido || ''
      return normalized
    })

    // Si hay un término de búsqueda rápida, aplicamos lógica adicional
    const termino = (filtros.value.buscar || '').trim()
    if (termino) {
      const soloNumeros = /^\d+$/.test(termino)
      if (soloNumeros) {
        // Para números (DNI) aplicamos coincidencia estricta por dígitos.
        const digitsTerm = termino.replace(/\D/g, '')
        listaPacientes = listaPacientes.filter((p: any) => {
          const doc = String(p.dni || '')
          const docDigits = doc.replace(/\D/g, '')
          // Permitir coincidencias parciales en DNI (ej. '111' coincide con '11111')
          return docDigits.includes(digitsTerm)
        })

        // Si no hubo resultados en la lista paginada, intentar el endpoint de búsqueda rápida
        // (autocompletado) que puede devolver coincidencias que la paginación no trajo.
        if (listaPacientes.length === 0) {
          try {
            console.log('🔁 Fallback: buscando DNI en endpoint rápido', termino)
            const rapido = await pacienteSecretariaService.busquedaRapida(termino)
            const rapPac = rapido.pacientes || rapido.data?.pacientes || rapido || []
            const rapNorm = (rapPac || []).map((p: any) => ({
              ...p,
              dni: p.dni || p.numero_documento || p.numeroDocumento || p.documento || '' ,
              nombre: p.nombres || p.nombre || '',
              apellidos: p.apellidos || p.apellido || ''
            }))
            // Filtrar estricto en fallback también
            const docsFiltered = rapNorm.filter((p: any) => String(p.dni || '').replace(/\D/g, '').includes(digitsTerm))
            listaPacientes = docsFiltered
          } catch (err) {
            console.warn('Fallback rápido falló', err)
          }
        }
      } else {
        // Búsqueda textual: comparación insensible a mayúsculas y acentos
        const normalize = (s: string) =>
          (s || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')

        const tNorm = normalize(termino)

        listaPacientes = listaPacientes.filter((p: any) => {
          const apellidos = normalize(p.apellidos || '')
          const nombres = normalize(p.nombre || '')
          const nombreCompleto = normalize(p.nombre_completo || `${p.nombre || ''} ${p.apellidos || ''}`)
          const numeroDoc = normalize(String(p.dni || ''))

          return (
            apellidos.includes(tNorm) ||
            nombres.includes(tNorm) ||
            nombreCompleto.includes(tNorm) ||
            numeroDoc.includes(tNorm)
          )
        })
      }
    }

    pacientes.value = listaPacientes

    // Actualizar paginación si viene en la respuesta
    if (pacientesData.data?.pagination || pacientesData.pagination) {
      const pag = pacientesData.data?.pagination || pacientesData.pagination
      paginacion.value = {
        current_page: pag.current_page,
        last_page: pag.last_page,
        per_page: pag.per_page,
        total: pag.total
      }
    }

    // Inicializar estadísticas con valores por defecto
    estadisticas.value = {
      total_pacientes: paginacion.value.total || pacientes.value.length,
      citas_pendientes: 0,
      citas_hoy: 0,
      pacientes_citas_proximas: 0
    }

    // Cargar estadísticas reales del backend
    try {
      const estadisticasData = await pacienteSecretariaService.estadisticasGenerales()
      if (estadisticasData.data) {
        estadisticas.value = estadisticasData.data
      }
    } catch (statsError: any) {
      console.log('ℹ️ Endpoint de estadísticas no disponible, usando valores calculados')
    }

  } catch (error: any) {
    console.error('Error al cargar datos:', error)
    showError(error.response?.data?.message || 'Error al cargar los datos de pacientes')
  } finally {
    cargando.value = false
  }
}

// Gestión de pacientes
const abrirModalNuevoPaciente = () => {
  pacienteEditando.value = null
  resetearFormulario()
  modalPaciente.value = true
}

const editarPaciente = (paciente: Paciente) => {
  pacienteEditando.value = paciente
  formularioPaciente.value = {
    nombres: paciente.nombre, // Usar 'nombre' del backend
    apellidos: paciente.apellidos,
    tipo_documento: 'DNI', // Por defecto DNI
    numero_documento: paciente.dni, // Usar 'dni' del backend
    fecha_nacimiento: paciente.fecha_nacimiento || '',
    genero: paciente.sexo || 'M', // Usar 'sexo' del backend
    telefono: paciente.telefono || '',
    email: paciente.email || '',
    direccion: paciente.domicilio || '' // Usar 'domicilio' del backend
  }
  modalPaciente.value = true
}

const cerrarModalPaciente = () => {
  modalPaciente.value = false
  pacienteEditando.value = null
  resetearFormulario()
}

const resetearFormulario = () => {
  formularioPaciente.value = {
    nombres: '',
    apellidos: '',
    tipo_documento: 'CC',
    numero_documento: '',
    fecha_nacimiento: '',
    genero: 'M',
    telefono: '',
    email: '',
    direccion: ''
  }
}

const guardarPaciente = async () => {
  try {
    guardandoPaciente.value = true

    // TODO: Implementar guardado real
    // if (pacienteEditando.value) {
    //   await pacienteSecretariaService.actualizar(pacienteEditando.value.id_paciente, formularioPaciente.value)
    //   showSuccess('Paciente actualizado exitosamente')
    // } else {
    //   await pacienteSecretariaService.crear(formularioPaciente.value)
    //   showSuccess('Paciente creado exitosamente')
    // }

    showSuccess(pacienteEditando.value ? 'Paciente actualizado exitosamente' : 'Paciente creado exitosamente')
    cerrarModalPaciente()
    cargarDatos()
  } catch (error) {
    console.error('Error al guardar paciente:', error)
    showError('Error al guardar el paciente')
  } finally {
    guardandoPaciente.value = false
  }
}

// Ficha completa
const verFichaCompleta = async (paciente: Paciente) => {
  try {
    pacienteSeleccionado.value = paciente

    // TODO: Implementar carga de ficha completa
    // fichaCompleta.value = await pacienteSecretariaService.fichaCompleta(paciente.id_paciente)

    modalFicha.value = true
  } catch (error) {
    console.error('Error al cargar ficha:', error)
    showError('Error al cargar la ficha del paciente')
  }
}

const cerrarModalFicha = () => {
  modalFicha.value = false
  pacienteSeleccionado.value = null
  fichaCompleta.value = null
}

// Navegación
const crearCita = (paciente: Paciente) => {
  router.push({
    name: 'secretaria-agenda',
    query: { paciente_id: paciente.id_paciente }
  })
}

// Filtros y búsqueda
const aplicarFiltros = () => {
  paginacion.value.current_page = 1
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
    nombre: '',
    documento: '',
    telefono: '',
    email: '',
    estado: '',
    fecha_desde: '',
    fecha_hasta: '',
    buscar: ''
  }
  aplicarFiltros()
}

/** Limpiar búsqueda rápida específica (lista) */
const limpiarBusquedaRapida = () => {
  filtros.value.buscar = ''
  aplicarFiltros()
}

const cambiarPagina = (pagina: number) => {
  paginacion.value.current_page = pagina
  cargarDatos()
}

// Utilidades
const calcularEdad = (fechaNacimiento: string) => {
  const hoy = new Date()
  const nacimiento = new Date(fechaNacimiento)
  let edad = hoy.getFullYear() - nacimiento.getFullYear()
  const mes = hoy.getMonth() - nacimiento.getMonth()

  if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
    edad--
  }

  return edad
}

const formatearFecha = (fecha: string) => {
  return new Date(fecha).toLocaleDateString('es-ES')
}

// Lifecycle
onMounted(() => {
  cargarDatos()
})
</script>
