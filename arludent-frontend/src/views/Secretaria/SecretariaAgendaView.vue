<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Agenda General</h1>
          <p class="text-gray-600 mt-1">Gestión de todas las citas médicas</p>
        </div>
        <div class="flex items-center space-x-3">
          <Button
            variant="primary"
            @click="router.push('/secretaria/agenda/nueva-cita')"
          >
            <Plus class="w-5 h-5" />
            <span>Nueva Cita</span>
          </Button>
          <Button
            variant="outline"
            @click="cambiarVista"
          >
            <Calendar class="w-5 h-5" />
            <span>Vista Calendario</span>
          </Button>
        </div>
      </div>

      <!-- Estadísticas -->
      <div v-if="estadisticas" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">{{ labelTotalCitas }}</p>
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
              <p class="text-sm text-gray-600">En Espera</p>
              <p class="text-2xl font-bold text-orange-600">{{ estadisticas.en_espera }}</p>
            </div>
            <div class="p-3 bg-orange-100 rounded-lg">
              <Clock class="w-6 h-6 text-orange-600" />
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

        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Canceladas</p>
              <p class="text-2xl font-bold text-red-600">{{ estadisticas.canceladas }}</p>
            </div>
            <div class="p-3 bg-red-100 rounded-lg">
              <XCircle class="w-6 h-6 text-red-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Filtros -->
      <Card class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filtro Rápido</label>
            <select
              v-model="filtros.filtroRapido"
              @change="aplicarFiltroRapido"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="hoy">Hoy</option>
              <option value="semana">Esta Semana</option>
              <option value="mes">Este Mes</option>
              <option value="personalizado">Personalizado</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Médico</label>
            <select
              v-model="filtros.medico"
              @change="cargarCitas"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="">Todos los médicos</option>
              <option v-for="medico in medicos" :key="medico.id_medico" :value="medico.id_medico">
                Dr(a). {{ medico.nombres }} {{ medico.apellidos }}
              </option>
            </select>
          </div>

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
              <option value="siendo_atendido">Siendo Atendido</option>
              <option value="completado">Completado</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>

          <div v-show="filtros.filtroRapido === 'personalizado'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
            <input
              v-model="filtros.desde"
              @change="cargarCitas"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          <div v-show="filtros.filtroRapido === 'personalizado'">
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
      <div v-if="cargando" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
        <p class="text-gray-600 mt-4">Cargando citas...</p>
      </div>

      <!-- Lista de Citas -->
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
                <div class="flex items-start space-x-4">
                  <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                    <User class="w-6 h-6 text-primary-600" />
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                      {{ cita.paciente ? `${cita.paciente.nombres} ${cita.paciente.apellidos}` : cita.usuario_externo ? `${cita.usuario_externo.username} (Usuario Externo)` : 'Sin paciente' }}
                    </h3>
                    <p v-if="cita.paciente?.dni" class="text-sm text-gray-600">
                      DNI: {{ cita.paciente.dni }}
                    </p>
                    <p class="text-sm text-primary-600 font-medium mt-1">
                      Dr(a). {{ cita.medico.nombres }} {{ cita.medico.apellidos }}
                    </p>
                  </div>
                </div>
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap',
                    getBadgeClasses(cita.estado)
                  ]"
                >
                  {{ formatEstado(cita.estado) }}
                </span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="flex items-center space-x-2 text-gray-700">
                  <Calendar class="w-5 h-5 text-gray-400" />
                  <span>{{ formatearFecha(cita.fecha_hora_inicio) }}</span>
                </div>
                <div class="flex items-center space-x-2 text-gray-700">
                  <Clock class="w-5 h-5 text-gray-400" />
                  <span>{{ formatearHora(cita.fecha_hora_inicio) }} - {{ formatearHora(cita.fecha_hora_fin) }}</span>
                </div>
                <div v-if="cita.motivo" class="flex items-start space-x-2 text-gray-700 md:col-span-2">
                  <FileText class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
                  <span>{{ cita.motivo }}</span>
                </div>
              </div>

              <!-- Estado y Acciones Destacadas -->
              <div class="mt-4 p-4 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold mb-1">Estado Actual</p>
                    <span
                      :class="[
                        'inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold',
                        getBadgeClasses(cita.estado)
                      ]"
                    >
                      {{ formatEstado(cita.estado) }}
                    </span>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-gray-600 uppercase font-semibold mb-1">Acciones Disponibles</p>
                  </div>
                </div>

                <!-- Botones de Acción Prominentes -->
                <div class="flex flex-wrap items-center gap-2">
                  <!-- 1. Pendiente → Confirmar -->
                  <Button
                    v-if="cita.estado === 'pendiente'"
                    variant="primary"
                    size="md"
                    @click="cambiarEstado(cita, 'confirmado')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold"
                  >
                    <CheckSquare class="w-5 h-5" />
                    <span>→ Confirmar Cita</span>
                  </Button>

                  <!-- 2. Confirmado → Check-in (Paciente llegó) -->
                  <Button
                    v-if="cita.estado === 'confirmado'"
                    variant="success"
                    size="md"
                    @click="cambiarEstado(cita, 'en_espera')"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold"
                  >
                    <UserCheck class="w-5 h-5" />
                    <span>→ Check-in (Paciente Llegó)</span>
                  </Button>

                  <!-- 3. En Espera → Atender -->
                  <Button
                    v-if="cita.estado === 'en_espera'"
                    variant="primary"
                    size="md"
                    @click="cambiarEstado(cita, 'siendo_atendido')"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-semibold"
                  >
                    <Stethoscope class="w-5 h-5" />
                    <span>→ Pasar a Atención</span>
                  </Button>

                  <!-- 4. Siendo Atendido → Completar -->
                  <Button
                    v-if="cita.estado === 'siendo_atendido'"
                    variant="success"
                    size="md"
                    @click="cambiarEstado(cita, 'completado')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
                  >
                    <CheckCircle class="w-5 h-5" />
                    <span>→ Completar Atención</span>
                  </Button>

                  <!-- Estados finales - sin acción principal -->
                  <div
                    v-if="['completado', 'cancelado', 'no_asistio'].includes(cita.estado)"
                    class="text-gray-500 italic"
                  >
                    No hay acciones disponibles para este estado
                  </div>

                  <!-- Botón Ver Detalles -->
                  <Button
                    variant="outline"
                    size="md"
                    @click="verDetalles(cita)"
                    class="ml-auto"
                  >
                    <Eye class="w-5 h-5" />
                    <span>Ver Detalles</span>
                  </Button>

                  <!-- Botón Cancelar (solo para estados activos) -->
                  <Button
                    v-if="['pendiente', 'confirmado', 'en_espera'].includes(cita.estado)"
                    variant="danger"
                    size="md"
                    @click="cancelarCita(cita)"
                  >
                    <XCircle class="w-5 h-5" />
                    <span>Cancelar</span>
                  </Button>
                </div>
              </div>

              <!-- Notas -->
              <div v-if="cita.notas" class="mt-4 p-3 bg-blue-50 rounded-lg">
                <div class="flex items-start space-x-2">
                  <MessageSquare class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-blue-900">Notas:</p>
                    <p class="text-sm text-blue-700 mt-1">{{ cita.notas }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Empty State -->
      <Card v-else class="p-12 text-center">
        <div class="flex flex-col items-center justify-center">
          <Calendar class="w-16 h-16 text-gray-300 mb-4" />
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay citas para hoy</h3>
          <p class="text-gray-600 mb-2">No se encontraron citas con los filtros aplicados</p>
          <p class="text-sm text-gray-500 mb-6">Intenta cambiar el filtro rápido a "Esta Semana" o "Este Mes"</p>
          <div class="flex gap-3">
            <Button variant="outline" @click="limpiarFiltros">
              <X class="w-5 h-5" />
              <span>Limpiar Filtros</span>
            </Button>
            <Button @click="router.push('/secretaria/agenda/nueva-cita')">
              <Plus class="w-5 h-5" />
              <span>Agendar Cita</span>
            </Button>
          </div>
        </div>
      </Card>
    </div>

    <!-- Modal Cancelar Cita -->
    <Modal
      :show="modalCancelar"
      title="Cancelar Cita"
      size="md"
      @close="cerrarModalCancelar"
    >
      <div class="space-y-4">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas cancelar esta cita?
        </p>

        <div v-if="citaSeleccionada" class="p-4 bg-gray-50 rounded-lg">
          <div class="space-y-2 text-sm">
            <div class="flex items-center space-x-2">
              <User class="w-4 h-4 text-gray-400" />
              <span class="font-medium">
                {{ citaSeleccionada.paciente?.nombres }} {{ citaSeleccionada.paciente?.apellidos }}
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <Calendar class="w-4 h-4 text-gray-400" />
              <span>{{ formatearFecha(citaSeleccionada.fecha_hora_inicio) }}</span>
            </div>
            <div class="flex items-center space-x-2">
              <Clock class="w-4 h-4 text-gray-400" />
              <span>{{ formatearHora(citaSeleccionada.fecha_hora_inicio) }}</span>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Motivo de cancelación (opcional)
          </label>
          <textarea
            v-model="motivoCancelacion"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
            placeholder="Escribe el motivo de la cancelación..."
          ></textarea>
        </div>
      </div>

      <template #footer>
        <Button variant="outline" @click="cerrarModalCancelar">
          Cancelar
        </Button>
        <Button variant="danger" @click="confirmarCancelacion" :disabled="cancelando">
          <XCircle class="w-5 h-5" />
          <span>{{ cancelando ? 'Cancelando...' : 'Confirmar Cancelación' }}</span>
        </Button>
      </template>
    </Modal>

    <!-- Modal Detalles de Cita -->
    <Modal
      :show="modalDetalles"
      title="Detalle de la Cita"
      size="md"
      @close="cerrarModalDetalles"
    >
      <div v-if="citaDetalle" class="space-y-3 text-sm text-gray-700">
        <p><strong>Cita #</strong> {{ citaDetalle.id_cita }}</p>
        <p><strong>Paciente:</strong> {{ citaDetalle.paciente ? `${citaDetalle.paciente.nombres} ${citaDetalle.paciente.apellidos}` : (citaDetalle.usuario_externo?.username || 'Usuario Externo') }}</p>
        <p><strong>Médico:</strong> Dr(a). {{ citaDetalle.medico?.nombres }} {{ citaDetalle.medico?.apellidos }}</p>
        <p><strong>Fecha:</strong> {{ formatearFecha(citaDetalle.fecha_hora_inicio) }}</p>
        <p><strong>Hora:</strong> {{ formatearHora(citaDetalle.fecha_hora_inicio) }} - {{ formatearHora(citaDetalle.fecha_hora_fin) }}</p>
        <p><strong>Estado:</strong> {{ formatEstado(citaDetalle.estado) }}</p>
        <p v-if="citaDetalle.motivo"><strong>Motivo:</strong> {{ citaDetalle.motivo }}</p>
        <p v-if="citaDetalle.notas"><strong>Notas:</strong> {{ citaDetalle.notas }}</p>
      </div>

      <template #footer>
        <Button variant="outline" @click="cerrarModalDetalles">Cerrar</Button>
      </template>
    </Modal>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Modal from '@/components/common/Modal.vue'
import { useNotification } from '@/composables/useNotification'
import secretariaCitasService from '@/api/secretariaCitasService'

// Icons
import {
  Calendar, Clock, User, FileText, MessageSquare, Eye, CheckCircle,
  XCircle, Plus, X, CheckSquare, CalendarClock, UserCheck, Stethoscope
} from 'lucide-vue-next'

const router = useRouter()
const { showSuccess, showError } = useNotification()

// Estado
const citas = ref<any[]>([])
const medicos = ref<any[]>([])
const estadisticas = ref<any>(null)
const cargando = ref(true)

// Calcular inicio y fin de semana (Lunes a Domingo)
const hoy = new Date()
const diaSemana = hoy.getDay() // 0 = Domingo, 1 = Lunes, ..., 6 = Sábado
const diasDesdeInicioSemana = diaSemana === 0 ? 6 : diaSemana - 1 // Si es domingo, retroceder 6 días; si no, restar 1

const inicioSemana = new Date(hoy)
inicioSemana.setDate(hoy.getDate() - diasDesdeInicioSemana) // Lunes

const finSemana = new Date(inicioSemana)
finSemana.setDate(inicioSemana.getDate() + 6) // Domingo

console.log('📅 Semana actual:', {
  inicio: inicioSemana.toISOString().split('T')[0],
  fin: finSemana.toISOString().split('T')[0]
})

const filtros = ref({
  filtroRapido: 'semana',
  medico: '',
  estado: '',
  desde: inicioSemana.toISOString().split('T')[0],
  hasta: finSemana.toISOString().split('T')[0]
})

// Estado del modal de cancelación
const modalCancelar = ref(false)
const citaSeleccionada = ref<any>(null)
const motivoCancelacion = ref('')
const cancelando = ref(false)

// Label dinámico para las estadísticas
const labelTotalCitas = computed(() => {
  switch (filtros.value.filtroRapido) {
    case 'hoy':
      return 'Total Hoy'
    case 'semana':
      return 'Total Semana'
    case 'mes':
      return 'Total Mes'
    case 'personalizado':
      return 'Total Rango'
    default:
      return 'Total'
  }
})

// Cargar médicos
const cargarMedicos = async () => {
  try {
    // Aquí deberías tener un servicio para obtener médicos
    // Por ahora lo dejamos vacío, se puede implementar después
    medicos.value = []
  } catch (error) {
    console.error('Error al cargar médicos:', error)
  }
}

// Cargar citas
const cargarCitas = async () => {
  try {
    cargando.value = true

    const params = {
      estado: filtros.value.estado || undefined,
      fecha_desde: filtros.value.desde || undefined,
      fecha_hasta: filtros.value.hasta || undefined,
      medico_id: filtros.value.medico ? Number(filtros.value.medico) : undefined
    }

    console.log('🔄 Cargando citas con params:', params)
    console.log('📅 Filtro desde:', params.fecha_desde)
    console.log('📅 Filtro hasta:', params.fecha_hasta)
    const response = await secretariaCitasService.obtenerTodasLasCitas(params)
    console.log('✅ Response completa:', response)
    console.log('📦 response.data:', response.data)
    console.log('📦 response.data.data:', response.data?.data)

    // La respuesta viene en response.data.data (doble anidación)
    citas.value = response.data?.data || []

    console.log('📋 Citas cargadas:', citas.value.length)
    console.log('Citas array:', citas.value)

    // Debug: ver el estado de cada cita
    if (citas.value.length > 0) {
      console.log('Estados de las citas:')
      citas.value.forEach((cita, index) => {
        console.log(`Cita ${index + 1}:`, {
          id: cita.id_cita,
          estado: cita.estado,
          fecha: cita.fecha_hora_inicio,
          paciente: cita.paciente?.nombres || 'N/A'
        })
      })
    }

    // Calcular estadísticas
    calcularEstadisticas()
  } catch (error: any) {
    console.error('Error al cargar citas:', error)
    showError('Error al cargar las citas')
  } finally {
    cargando.value = false
  }
}

// Calcular estadísticas
const calcularEstadisticas = () => {
  // Usar todas las citas del rango de filtro actual
  const citasDelRango = citas.value

  // Filtrar las citas según el tipo de filtro aplicado
  let citasParaEstadisticas = citasDelRango

  if (filtros.value.filtroRapido === 'hoy') {
    // Para "hoy", filtrar solo las citas de hoy
    const hoy = new Date().toISOString().split('T')[0]
    citasParaEstadisticas = citasDelRango.filter(c => {
      const fechaCita = c.fecha_hora_inicio.split('T')[0]
      return fechaCita === hoy
    })
  } else {
    // Para "semana", "mes" o "personalizado", usar todas las citas del rango
    // ya que el filtro de fechas se aplica en la consulta al backend
    citasParaEstadisticas = citasDelRango
  }

  // Log detallado de los estados recibidos
  console.log('📋 Estados de citas recibidas:', citasParaEstadisticas.map(c => ({
    id: c.id_cita,
    estado: c.estado,
    fecha: c.fecha_hora_inicio
  })))

  // Estados válidos para contar en el total
  const estadosValidos = ['pendiente', 'confirmado', 'en_espera', 'siendo_atendido', 'completado', 'cancelado']

  estadisticas.value = {
    total: citasParaEstadisticas.filter(c => estadosValidos.includes(c.estado)).length,
    confirmadas: citasParaEstadisticas.filter(c => c.estado === 'confirmado').length,
    en_espera: citasParaEstadisticas.filter(c => c.estado === 'en_espera').length,
    completadas: citasParaEstadisticas.filter(c => c.estado === 'completado').length,
    canceladas: citasParaEstadisticas.filter(c => c.estado === 'cancelado').length
  }

  console.log('📊 Estadísticas calculadas para el rango:', {
    filtro: filtros.value.filtroRapido,
    desde: filtros.value.desde,
    hasta: filtros.value.hasta,
    totalCitas: citasParaEstadisticas.length,
    citasConEstadosValidos: citasParaEstadisticas.filter(c => estadosValidos.includes(c.estado)).length,
    estadisticas: estadisticas.value,
    estadosEncontrados: [...new Set(citasParaEstadisticas.map(c => c.estado))]
  })
}

// Aplicar filtro rápido
const aplicarFiltroRapido = () => {
  const hoy = new Date()

  // Calcular inicio de semana (Lunes a Domingo)
  const diaSemana = hoy.getDay() // 0 = Domingo, 1 = Lunes, ..., 6 = Sábado
  const diasDesdeInicioSemana = diaSemana === 0 ? 6 : diaSemana - 1

  const inicioSemana = new Date(hoy)
  inicioSemana.setDate(hoy.getDate() - diasDesdeInicioSemana) // Lunes

  const finSemana = new Date(inicioSemana)
  finSemana.setDate(inicioSemana.getDate() + 6) // Domingo

  const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
  const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0)

  switch (filtros.value.filtroRapido) {
    case 'hoy':
      filtros.value.desde = hoy.toISOString().split('T')[0]
      filtros.value.hasta = hoy.toISOString().split('T')[0]
      break
    case 'semana':
      filtros.value.desde = inicioSemana.toISOString().split('T')[0]
      filtros.value.hasta = finSemana.toISOString().split('T')[0]
      break
    case 'mes':
      filtros.value.desde = inicioMes.toISOString().split('T')[0]
      filtros.value.hasta = finMes.toISOString().split('T')[0]
      break
    case 'personalizado':
      // No hacer nada, el usuario elegirá las fechas manualmente
      return
  }
  cargarCitas()
}

// Limpiar filtros
const limpiarFiltros = () => {
  filtros.value = {
    filtroRapido: 'hoy',
    medico: '',
    estado: '',
    desde: new Date().toISOString().split('T')[0],
    hasta: new Date().toISOString().split('T')[0]
  }
  cargarCitas()
}

// Cambiar estado de una cita
const cambiarEstado = async (cita: any, nuevoEstado: string) => {
  try {
    console.log('🔄 Cambiando estado:', {
      citaId: cita.id_cita,
      estadoActual: cita.estado,
      nuevoEstado: nuevoEstado
    })

    const response = await secretariaCitasService.cambiarEstado(cita.id_cita, nuevoEstado)
    console.log('✅ Respuesta del servidor:', response)

    showSuccess(`Estado cambiado a: ${nuevoEstado}`)
    await cargarCitas()
  } catch (error: any) {
    console.error('❌ Error al cambiar estado:', error)
    showError(error.response?.data?.message || 'Error al cambiar estado de la cita')
  }
}

// Cambiar a vista calendario
const cambiarVista = () => {
  router.push('/secretaria/agenda/calendario')
}

// Ver detalles
// Detalles - usar modal en lugar de alert
const modalDetalles = ref(false)
const citaDetalle = ref<any>(null)

const verDetalles = (cita: any) => {
  citaDetalle.value = cita
  modalDetalles.value = true
}

// Confirmar cita
const confirmarCita = async (cita: any) => {
  if (!confirm(`¿Confirmar la cita de ${cita.paciente ? `${cita.paciente.nombres} ${cita.paciente.apellidos}` : 'Usuario Externo'}?`)) return

  try {
    await secretariaCitasService.confirmarCita(cita.id_cita)
    showSuccess('Cita confirmada exitosamente')
    cargarCitas()
  } catch (error) {
    showError('Error al confirmar la cita')
  }
}

// Cancelar cita - Abrir modal
const cancelarCita = (cita: any) => {
  citaSeleccionada.value = cita
  motivoCancelacion.value = ''
  modalCancelar.value = true
}

// Cerrar modal de cancelación
const cerrarModalCancelar = () => {
  modalCancelar.value = false
  citaSeleccionada.value = null
  motivoCancelacion.value = ''
  cancelando.value = false
}

// Cerrar modal de detalles
const cerrarModalDetalles = () => {
  modalDetalles.value = false
  citaDetalle.value = null
}

// Confirmar cancelación
const confirmarCancelacion = async () => {
  if (!citaSeleccionada.value) return

  try {
    cancelando.value = true
    console.log('🚫 Cancelando cita:', citaSeleccionada.value.id_cita)

    await secretariaCitasService.cancelarCita(
      citaSeleccionada.value.id_cita,
      { notas: motivoCancelacion.value || 'Cancelada por secretaría' }
    )

    showSuccess('Cita cancelada exitosamente')
    cerrarModalCancelar()
    await cargarCitas()
  } catch (error: any) {
    console.error('❌ Error al cancelar:', error)
    showError(error.response?.data?.message || 'Error al cancelar la cita')
  } finally {
    cancelando.value = false
  }
}

// Formatear fecha
const formatearFecha = (fecha: string) => {
  return new Date(fecha).toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Formatear hora
const formatearHora = (fecha: string) => {
  return new Date(fecha).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Formatear estado
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

// Obtener clases del badge
const getBadgeClasses = (estado: string) => {
  const classes: Record<string, string> = {
    'pendiente': 'bg-yellow-100 text-yellow-800 border border-yellow-300',
    'confirmado': 'bg-green-100 text-green-800 border border-green-300',
    'en_espera': 'bg-blue-100 text-blue-800 border border-blue-300',
    'siendo_atendido': 'bg-purple-100 text-purple-800 border border-purple-300',
    'completado': 'bg-emerald-100 text-emerald-800 border border-emerald-300',
    'cancelado': 'bg-red-100 text-red-800 border border-red-300',
    'no_asistio': 'bg-orange-100 text-orange-800 border border-orange-300'
  }
  return classes[estado] || 'bg-gray-100 text-gray-800'
}

// Lifecycle
onMounted(() => {
  cargarMedicos()
  cargarCitas()
})
</script>
