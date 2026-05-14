<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
            <ClipboardList class="w-8 h-8 text-purple-600" />
            Seguimiento Post-Tratamiento
          </h1>
          <p class="text-gray-600 mt-1">
            Registro y control de evolución de pacientes después de tratamientos
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <Button variant="outline" @click="mostrarFiltros = !mostrarFiltros">
            <Filter class="w-5 h-5" />
            Filtros
          </Button>
          <Button variant="primary" @click="abrirModalNuevo">
            <Plus class="w-5 h-5" />
            Nuevo Seguimiento
          </Button>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <Card class="border-l-4 border-blue-500">
          <div class="p-4">
            <p class="text-xs font-medium text-gray-600 uppercase">Total</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              {{ estadisticas.total_seguimientos }}
            </p>
          </div>
        </Card>

        <Card class="border-l-4 border-orange-500">
          <div class="p-4">
            <p class="text-xs font-medium text-gray-600 uppercase">Pendientes</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">
              {{ estadisticas.pendientes }}
            </p>
          </div>
        </Card>

        <Card class="border-l-4 border-red-500">
          <div class="p-4">
            <p class="text-xs font-medium text-gray-600 uppercase">Vencidos</p>
            <p class="text-2xl font-bold text-red-600 mt-1">
              {{ estadisticas.vencidos }}
            </p>
          </div>
        </Card>

        <Card class="border-l-4 border-yellow-500">
          <div class="p-4">
            <p class="text-xs font-medium text-gray-600 uppercase">Urgentes</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">
              {{ estadisticas.urgentes }}
            </p>
          </div>
        </Card>

        <Card class="border-l-4 border-purple-500">
          <div class="p-4">
            <p class="text-xs font-medium text-gray-600 uppercase">Con Problemas</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">
              {{ estadisticas.con_problemas }}
            </p>
          </div>
        </Card>

        <Card class="border-l-4 border-green-500">
          <div class="p-4">
            <p class="text-xs font-medium text-gray-600 uppercase">Completados</p>
            <p class="text-2xl font-bold text-green-600 mt-1">
              {{ estadisticas.completados }}
            </p>
          </div>
        </Card>
      </div>

      <!-- Filtros -->
      <Card v-if="mostrarFiltros" class="p-6">
        <h3 class="text-lg font-semibold mb-4">Filtros</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
            <select
              v-model="filtros.estado"
              class="w-full px-3 py-2 border rounded-lg"
              @change="aplicarFiltros"
            >
              <option value="">Todos</option>
              <option value="pendiente">Pendiente</option>
              <option value="realizado">Realizado</option>
              <option value="requiere_revision">Requiere Revisión</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
            <select
              v-model="filtros.tipo_seguimiento"
              class="w-full px-3 py-2 border rounded-lg"
              @change="aplicarFiltros"
            >
              <option value="">Todos</option>
              <option value="postoperatorio">Postoperatorio</option>
              <option value="revision">Revisión</option>
              <option value="medicacion">Medicación</option>
              <option value="general">General</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
            <input
              v-model="filtros.fecha_desde"
              type="date"
              class="w-full px-3 py-2 border rounded-lg"
              @change="aplicarFiltros"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
            <input
              v-model="filtros.fecha_hasta"
              type="date"
              class="w-full px-3 py-2 border rounded-lg"
              @change="aplicarFiltros"
            />
          </div>

          <div class="flex items-end">
            <Button variant="outline" @click="limpiarFiltros" class="w-full">
              <X class="w-4 h-4 mr-2" />
              Limpiar
            </Button>
          </div>
        </div>

        <div class="mt-4">
          <label class="flex items-center">
            <input
              v-model="filtros.solo_vencidos"
              type="checkbox"
              class="mr-2"
              @change="aplicarFiltros"
            />
            <span class="text-sm text-gray-700">Mostrar solo vencidos</span>
          </label>
        </div>
      </Card>

      <!-- Tabs de prioridad -->
      <div class="flex space-x-2 border-b">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="tabActivo = tab.value; aplicarFiltros()"
          :class="[
            'px-4 py-2 font-medium transition-colors',
            tabActivo === tab.value
              ? 'border-b-2 border-purple-600 text-purple-600'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          {{ tab.label }}
          <span
            v-if="tab.count"
            class="ml-2 px-2 py-0.5 text-xs rounded-full"
            :class="tab.badgeClass"
          >
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Lista de Seguimientos -->
      <Card v-if="!cargando && seguimientos.length > 0">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Paciente
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Tipo / Método
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Fecha Programada
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Estado / IA
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Prioridad
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Análisis IA
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="seg in seguimientos"
                :key="seg.id_seguimiento"
                :class="[
                  'hover:bg-gray-50 transition-colors',
                  seg.prioridad === 'urgente' ? 'bg-red-50' : '',
                  seg.analisis_ia?.nivel_urgencia === 'alto' ? 'bg-red-50' : '',
                  seg.analisis_ia?.nivel_urgencia === 'medio' ? 'bg-yellow-50' : '',
                  seg.tiene_problema ? 'bg-orange-50' : ''
                ]"
              >
                <td class="px-6 py-4">
                  <div>
                    <div class="font-medium text-gray-900">
                      {{ seg.paciente?.nombres }} {{ seg.paciente?.apellidos }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ seg.paciente?.dni || 'Sin documento' }}
                    </div>
                    <div v-if="seg.paciente?.email" class="text-xs text-gray-400 mt-1">
                      📧 {{ seg.paciente.email }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div>
                    <div class="text-sm font-medium capitalize">
                      {{ seg.tipo_seguimiento || seg.tipo }}
                    </div>
                    <div class="flex items-center gap-1 text-xs text-gray-500 capitalize mt-1">
                      <span v-if="seg.metodo_contacto === 'email'">📧</span>
                      <span v-else-if="seg.metodo_contacto === 'whatsapp'">💬</span>
                      <span v-else-if="seg.metodo_contacto === 'llamada'">📞</span>
                      <span v-else-if="seg.metodo_contacto === 'portal'">🌐</span>
                      {{ seg.metodo_contacto }}
                    </div>
                    <div v-if="seg.gestionado_por_ia" class="mt-1">
                      <span class="inline-flex items-center px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">
                        🤖 Con IA
                      </span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm">
                  <div class="font-medium">{{ formatearFecha(seg.fecha_seguimiento) }}</div>
                  <div v-if="estaVencido(seg)" class="text-xs text-red-600 font-medium mt-1 flex items-center gap-1">
                    ⚠️ Vencido hace {{ diasVencido(seg) }} días
                  </div>
                  <div v-if="seg.enviado_ia_at" class="text-xs text-blue-600 mt-1">
                    📤 Enviado: {{ formatearFechaHora(seg.enviado_ia_at) }}
                  </div>
                  <div v-if="seg.respondido_paciente_at" class="text-xs text-green-600 mt-1">
                    ✅ Respondido: {{ formatearFechaHora(seg.respondido_paciente_at) }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="space-y-1">
                    <span
                      :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        seg.estado === 'pendiente' ? 'bg-orange-100 text-orange-800' :
                        seg.estado === 'enviado' ? 'bg-blue-100 text-blue-800' :
                        seg.estado === 'respondido' ? 'bg-green-100 text-green-800' :
                        seg.estado === 'realizado' ? 'bg-green-100 text-green-800' :
                        seg.estado === 'requiere_revision' ? 'bg-purple-100 text-purple-800' :
                        'bg-gray-100 text-gray-800'
                      ]"
                    >
                      {{ estadoTexto(seg.estado) }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="space-y-1">
                    <span
                      :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        seg.prioridad === 'urgente' ? 'bg-red-100 text-red-800' :
                        seg.prioridad === 'alta' ? 'bg-orange-100 text-orange-800' :
                        seg.prioridad === 'media' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-gray-100 text-gray-800'
                      ]"
                    >
                      {{ seg.prioridad }}
                    </span>
                    <div v-if="seg.tiene_problema">
                      <span class="inline-flex items-center px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                        <AlertTriangle class="w-3 h-3 mr-1" />
                        Problema
                      </span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm">
                  <!-- Análisis IA -->
                  <div v-if="seg.analisis_ia" class="space-y-2">
                    <!-- Nivel de Urgencia -->
                    <div v-if="seg.analisis_ia.nivel_urgencia">
                      <span
                        :class="[
                          'inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full',
                          seg.analisis_ia.nivel_urgencia === 'alto' ? 'bg-red-100 text-red-800' :
                          seg.analisis_ia.nivel_urgencia === 'medio' ? 'bg-yellow-100 text-yellow-800' :
                          'bg-green-100 text-green-800'
                        ]"
                      >
                        {{ seg.analisis_ia.nivel_urgencia === 'alto' ? '🔴' : seg.analisis_ia.nivel_urgencia === 'medio' ? '🟡' : '🟢' }}
                        {{ seg.analisis_ia.nivel_urgencia.toUpperCase() }}
                      </span>
                    </div>

                    <!-- Resumen -->
                    <div v-if="seg.analisis_ia.resumen" class="text-xs text-gray-700 max-w-xs">
                      <strong>Resumen IA:</strong> {{ seg.analisis_ia.resumen }}
                    </div>

                    <!-- Requiere Atención -->
                    <div v-if="seg.analisis_ia.requiere_atencion" class="text-xs">
                      <span class="inline-flex items-center px-2 py-0.5 bg-purple-100 text-purple-800 rounded-full">
                        👨‍⚕️ Requiere atención
                      </span>
                    </div>

                    <!-- Sentimiento -->
                    <div v-if="seg.analisis_ia.sentimiento_general" class="text-xs">
                      <span class="text-gray-600">
                        Sentimiento:
                        <span :class="[
                          seg.analisis_ia.sentimiento_general === 'positivo' ? 'text-green-600' :
                          seg.analisis_ia.sentimiento_general === 'negativo' ? 'text-red-600' :
                          'text-gray-600'
                        ]">
                          {{ seg.analisis_ia.sentimiento_general === 'positivo' ? '😊' : seg.analisis_ia.sentimiento_general === 'negativo' ? '😰' : '😐' }}
                          {{ seg.analisis_ia.sentimiento_general }}
                        </span>
                      </span>
                    </div>
                  </div>

                  <!-- Sin análisis aún -->
                  <div v-else-if="seg.estado === 'respondido' || seg.respondido_paciente_at" class="text-xs text-gray-400 italic">
                    ⏳ Analizando...
                  </div>
                  <div v-else class="text-xs text-gray-400 italic">
                    Pendiente de respuesta
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <Button size="sm" variant="outline" @click="verDetalle(seg)" title="Ver detalle completo">
                      <Eye class="w-4 h-4" />
                    </Button>
                    <Button
                      v-if="seg.estado === 'pendiente' || seg.analisis_ia?.requiere_atencion"
                      size="sm"
                      variant="primary"
                      @click="registrarSeguimiento(seg)"
                      title="Registrar seguimiento"
                    >
                      <Phone class="w-4 h-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="px-6 py-4 border-t flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ seguimientos.length }} de {{ paginacion.total }} seguimientos
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
      </Card>

      <!-- Estado vacío -->
      <Card v-if="!cargando && seguimientos.length === 0" class="p-12 text-center">
        <ClipboardList class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay seguimientos</h3>
        <p class="text-gray-600 mb-6">
          No se encontraron seguimientos con los filtros aplicados
        </p>
        <Button variant="primary" @click="abrirModalNuevo">
          <Plus class="w-5 h-5 mr-2" />
          Crear Primer Seguimiento
        </Button>
      </Card>

      <!-- Loading -->
      <div v-if="cargando" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
      </div>

      <!-- Modal Detalle -->
      <Modal :show="modalDetalle" @close="cerrarModalDetalle" title="Detalle del Seguimiento" size="xl">
        <div v-if="seguimientoSeleccionado" class="p-6">
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-gray-600">Paciente</label>
                <p class="text-base font-semibold">
                  {{ seguimientoSeleccionado.paciente?.nombres }}
                  {{ seguimientoSeleccionado.paciente?.apellidos }}
                </p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Tipo</label>
                <p class="text-base capitalize">{{ seguimientoSeleccionado.tipo_seguimiento }}</p>
              </div>
            </div>

            <div v-if="seguimientoSeleccionado.respuesta_paciente" class="bg-blue-50 p-4 rounded-lg">
              <label class="text-sm font-medium text-gray-600">Respuesta del Paciente</label>
              <p class="mt-2">"{{ seguimientoSeleccionado.respuesta_paciente }}"</p>
            </div>

            <div v-if="seguimientoSeleccionado.tiene_problema" class="bg-yellow-50 p-4 rounded-lg">
              <label class="text-sm font-medium text-yellow-800 flex items-center">
                <AlertTriangle class="w-4 h-4 mr-2" />
                Problema Reportado
              </label>
              <p class="mt-2 font-medium">{{ seguimientoSeleccionado.descripcion_problema }}</p>
              <p v-if="seguimientoSeleccionado.sintomas" class="mt-1 text-sm">
                <strong>Síntomas:</strong> {{ seguimientoSeleccionado.sintomas }}
              </p>
            </div>

            <div v-if="seguimientoSeleccionado.notas_secretaria" class="bg-gray-50 p-4 rounded-lg">
              <label class="text-sm font-medium text-gray-600">Notas de Secretaría</label>
              <p class="mt-2">{{ seguimientoSeleccionado.notas_secretaria }}</p>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <Button variant="outline" @click="cerrarModalDetalle">Cerrar</Button>
          </div>
        </div>
      </Modal>

      <!-- Modal Crear Seguimiento -->
      <ModalCrearSeguimiento
        :show="modalCrearSeguimiento"
        :pacientes="listaPacientes"
        @close="cerrarModalCrear"
        @guardado="guardarSeguimiento"
      />

      <!-- Modal Registrar Contacto -->
      <ModalRegistrarContacto
        :show="modalRegistrarContacto"
        :seguimiento="seguimientoSeleccionado"
        @close="cerrarModalContacto"
        @guardado="guardarContacto"
      />
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Modal from '@/components/common/Modal.vue'
import ModalCrearSeguimiento from '@/components/seguimiento/ModalCrearSeguimiento.vue'
import ModalRegistrarContacto from '@/components/seguimiento/ModalRegistrarContacto.vue'
import { useNotification } from '@/composables/useNotification'
import seguimientoService from '@/api/seguimientoService'
import {
  ClipboardList, Filter, Plus, X, Eye, Phone, AlertTriangle
} from 'lucide-vue-next'

const { showSuccess, showError } = useNotification()

// Estado
const cargando = ref(true)
const mostrarFiltros = ref(false)
const seguimientos = ref<any[]>([])
const listaPacientes = ref<any[]>([])
const tabActivo = ref('todos')
const modalDetalle = ref(false)
const modalCrearSeguimiento = ref(false)
const modalRegistrarContacto = ref(false)
const seguimientoSeleccionado = ref<any>(null)

const estadisticas = ref({
  total_seguimientos: 0,
  pendientes: 0,
  completados: 0,
  vencidos: 0,
  con_problemas: 0,
  urgentes: 0,
  por_tipo: {}
})

const filtros = ref({
  estado: '',
  tipo_seguimiento: '',
  fecha_desde: '',
  fecha_hasta: '',
  buscar_paciente: '',
  solo_vencidos: false
})

const paginacion = ref({
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0
})

const tabs = computed(() => [
  { label: 'Todos', value: 'todos', count: estadisticas.value.total_seguimientos },
  { label: 'Pendientes', value: 'pendiente', count: estadisticas.value.pendientes, badgeClass: 'bg-orange-100 text-orange-800' },
  { label: 'Vencidos', value: 'vencidos', count: estadisticas.value.vencidos, badgeClass: 'bg-red-100 text-red-800' },
  { label: 'Urgentes', value: 'urgentes', count: estadisticas.value.urgentes, badgeClass: 'bg-red-100 text-red-800' },
  { label: 'Con Problemas', value: 'problemas', count: estadisticas.value.con_problemas, badgeClass: 'bg-yellow-100 text-yellow-800' },
  { label: 'Completados', value: 'realizado', count: estadisticas.value.completados, badgeClass: 'bg-green-100 text-green-800' }
])

// Métodos
const cargarDatos = async () => {
  try {
    cargando.value = true

    // Preparar filtros según tab activo
    const filtrosApi: any = {
      ...filtros.value,
      page: paginacion.value.current_page,
      per_page: paginacion.value.per_page
    }

    if (tabActivo.value !== 'todos') {
      if (tabActivo.value === 'vencidos') {
        filtrosApi.solo_vencidos = true
      } else if (tabActivo.value === 'urgentes') {
        // El backend maneja esto con un scope
      } else if (tabActivo.value === 'problemas') {
        // Filtrar en frontend por ahora
      } else {
        filtrosApi.estado = tabActivo.value
      }
    }

    const [seguimientosData, estadisticasData] = await Promise.all([
      seguimientoService.getSeguimientos(filtrosApi),
      seguimientoService.getEstadisticasSeguimiento()
    ])

    seguimientos.value = seguimientosData.data?.seguimientos || seguimientosData.seguimientos || []

    if (seguimientosData.data?.pagination || seguimientosData.pagination) {
      const pag = seguimientosData.data?.pagination || seguimientosData.pagination
      paginacion.value = {
        current_page: pag.current_page,
        last_page: pag.last_page,
        per_page: pag.per_page,
        total: pag.total
      }
    }

    estadisticas.value = estadisticasData

  } catch (error: any) {
    console.error('Error al cargar datos:', error)
    showError(error.response?.data?.message || 'Error al cargar seguimientos')
  } finally {
    cargando.value = false
  }
}

const aplicarFiltros = () => {
  paginacion.value.current_page = 1
  cargarDatos()
}

const limpiarFiltros = () => {
  filtros.value = {
    estado: '',
    tipo_seguimiento: '',
    fecha_desde: '',
    fecha_hasta: '',
    buscar_paciente: '',
    solo_vencidos: false
  }
  tabActivo.value = 'todos'
  aplicarFiltros()
}

const cambiarPagina = (pagina: number) => {
  paginacion.value.current_page = pagina
  cargarDatos()
}

const verDetalle = (seguimiento: any) => {
  seguimientoSeleccionado.value = seguimiento
  modalDetalle.value = true
}

const cerrarModalDetalle = () => {
  modalDetalle.value = false
  seguimientoSeleccionado.value = null
}

const registrarSeguimiento = (seguimiento: any) => {
  seguimientoSeleccionado.value = seguimiento
  modalRegistrarContacto.value = true
}

const abrirModalNuevo = async () => {
  // Cargar lista de pacientes usando el servicio
  try {
    const response: any = await seguimientoService.obtenerPacientesActivos()
    console.log('Respuesta de pacientes:', response)

    // La respuesta puede venir en diferentes formatos
    let pacientes = []
    if (Array.isArray(response)) {
      pacientes = response
    } else if (response && Array.isArray(response.pacientes)) {
      // Formato: {pacientes: [...], pagination: {...}}
      pacientes = response.pacientes
    } else if (response && Array.isArray(response.data)) {
      pacientes = response.data
    } else if (response && response.data && Array.isArray(response.data.data)) {
      pacientes = response.data.data
    }

    console.log('Pacientes procesados:', pacientes)
    listaPacientes.value = pacientes
    modalCrearSeguimiento.value = true
  } catch (error) {
    console.error('Error al cargar pacientes:', error)
    showError('Error al cargar lista de pacientes')
  }
}

const cerrarModalCrear = () => {
  modalCrearSeguimiento.value = false
}

const cerrarModalContacto = () => {
  modalRegistrarContacto.value = false
  seguimientoSeleccionado.value = null
}

const guardarSeguimiento = async (datos: any) => {
  try {
    await seguimientoService.crearSeguimiento(datos)
    showSuccess('Seguimiento creado correctamente')
    modalCrearSeguimiento.value = false
    cargarDatos()
  } catch (error: any) {
    console.error('Error al crear seguimiento:', error)
    showError(error.response?.data?.message || 'Error al crear seguimiento')
  }
}

const guardarContacto = async (datos: any) => {
  try {
    await seguimientoService.registrarContacto(seguimientoSeleccionado.value.id_seguimiento, datos)
    showSuccess('Contacto registrado correctamente')
    modalRegistrarContacto.value = false
    seguimientoSeleccionado.value = null
    cargarDatos()
  } catch (error: any) {
    console.error('Error al registrar contacto:', error)
    showError(error.response?.data?.message || 'Error al registrar contacto')
  }
}

// Utilidades
const formatearFecha = (fecha: string) => {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatearFechaHora = (fecha: string) => {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const estaVencido = (seg: any) => {
  if (seg.estado !== 'pendiente') return false
  return new Date(seg.fecha_seguimiento) < new Date()
}

const diasVencido = (seg: any) => {
  const hoy = new Date()
  const fecha = new Date(seg.fecha_seguimiento)
  const diff = hoy.getTime() - fecha.getTime()
  return Math.floor(diff / (1000 * 60 * 60 * 24))
}

const estadoTexto = (estado: string) => {
  const textos: any = {
    pendiente: 'Pendiente',
    enviado: 'Enviado',
    respondido: 'Respondido',
    realizado: 'Realizado',
    requiere_revision: 'Requiere Revisión',
    cancelado: 'Cancelado'
  }
  return textos[estado] || estado
}

// Lifecycle
onMounted(() => {
  cargarDatos()
})
</script>
