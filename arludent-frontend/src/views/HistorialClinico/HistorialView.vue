<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header con Estadísticas -->
      <Card>
        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Gestión de Historial Clínico</h1>
              <p class="text-gray-600">Gestiona todos los pacientes y sus historiales clínicos</p>
            </div>
          </div>

          <!-- Estadísticas -->
          <div v-if="estadisticas" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-orange-50 p-4 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-orange-600">Usuarios Externos</p>
                  <p class="text-2xl font-bold text-orange-900">{{ estadisticas.externos_sin_registro }}</p>
                  <p class="text-xs text-orange-600">Pendiente completar datos</p>
                </div>
                <UserX class="w-10 h-10 text-orange-400" />
              </div>
            </div>

            <div class="bg-yellow-50 p-4 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-yellow-600">Pacientes sin Historial</p>
                  <p class="text-2xl font-bold text-yellow-900">{{ estadisticas.pacientes_sin_historial }}</p>
                  <p class="text-xs text-yellow-600">Necesitan historial clínico</p>
                </div>
                <FileWarning class="w-10 h-10 text-yellow-400" />
              </div>
            </div>

            <div class="bg-green-50 p-4 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-600">Con Historial Completo</p>
                  <p class="text-2xl font-bold text-green-900">{{ estadisticas.pacientes_con_historial }}</p>
                  <p class="text-xs text-green-600">Historiales registrados</p>
                </div>
                <CheckCircle class="w-10 h-10 text-green-400" />
              </div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Filtros y Tabs -->
      <Card>
        <div class="space-y-4">
          <!-- Buscador -->
          <div class="flex gap-4 items-end">
            <div class="flex-1">
              <InputField
                v-model="busqueda"
                label="Buscar"
                placeholder="Nombre, apellido o DNI..."
                icon="search"
                @input="debouncedBuscar"
              />
            </div>
          </div>

          <!-- Tabs -->
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="tabActivo = tab.id"
                :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                  tabActivo === tab.id
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                <div class="flex items-center space-x-2">
                  <component :is="tab.icon" class="w-5 h-5" />
                  <span>{{ tab.label }}</span>
                  <span v-if="tab.count > 0" :class="[
                    'ml-2 py-0.5 px-2 rounded-full text-xs font-semibold',
                    tabActivo === tab.id
                      ? 'bg-blue-100 text-blue-600'
                      : 'bg-gray-100 text-gray-600'
                  ]">
                    {{ tab.count }}
                  </span>
                </div>
              </button>
            </nav>
          </div>
        </div>
      </Card>

      <!-- Lista de personas filtrada por tab -->
      <Card v-if="!loading">
        <div v-if="personasFiltradas.length === 0" class="text-center py-12">
          <FileText class="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay registros</h3>
          <p class="text-gray-600">
            No se encontraron personas en esta categoría.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Persona
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  DNI
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Contacto
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Estado
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Acciones Rápidas
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="persona in personasFiltradas" :key="persona.id_usuario || persona.id_paciente || Math.random()">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div>
                      <div class="text-sm font-medium text-gray-900">
                        {{ persona.nombre_completo }}
                      </div>
                      <div v-if="persona.tipo === 'externo'" class="text-xs text-orange-600">
                        ⚠ Usuario externo - Sin registro completo
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ persona.dni || 'Sin DNI' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500">
                    <div v-if="persona.telefono">{{ persona.telefono }}</div>
                    <div v-if="persona.correo" class="text-xs">{{ persona.correo }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <!-- Usuario externo sin registro -->
                  <span
                    v-if="persona.estado === 'pendiente_registro'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800"
                  >
                    <XCircle class="w-4 h-4 mr-1" />
                    Pendiente completar datos
                  </span>
                  <!-- Paciente con historial -->
                  <span
                    v-else-if="persona.tiene_historial"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    <CheckCircle class="w-4 h-4 mr-1" />
                    Tiene historial
                  </span>
                  <!-- Paciente sin historial -->
                  <span
                    v-else
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                  >
                    <XCircle class="w-4 h-4 mr-1" />
                    Sin historial
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <!-- Usuario externo: Completar datos -->
                    <Button
                      v-if="persona.tipo === 'externo'"
                      variant="warning"
                      size="sm"
                      @click="abrirModalCompletarDatos(persona)"
                      title="Completar datos del paciente y crear historial"
                    >
                      <UserPlus class="w-4 h-4 mr-1" />
                      Completar Registro
                    </Button>

                    <!-- Paciente sin historial: Crear historial -->
                    <Button
                      v-else-if="!persona.tiene_historial"
                      variant="primary"
                      size="sm"
                      @click="abrirModalCrear(persona)"
                      title="Crear historial clínico"
                    >
                      <FilePlus class="w-4 h-4 mr-1" />
                      Crear Historial
                    </Button>

                    <!-- Paciente con historial: Ver y editar -->
                    <template v-else>
                      <Button
                        variant="secondary"
                        size="sm"
                        @click="verHistorial(persona.id_historial!)"
                        title="Ver historial completo"
                      >
                        <Eye class="w-4 h-4 mr-1" />
                        Ver
                      </Button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Loading -->
      <Card v-else>
        <div class="flex justify-center py-12">
          <Loading />
        </div>
      </Card>

      <!-- Modal Crear Historial -->
      <Modal
        :show="modalCrearAbierto"
        title="Crear Historial Clínico"
        size="4xl"
        @close="cerrarModalCrear"
      >
        <form @submit.prevent="crearHistorial" class="space-y-4">
          <!-- Información del paciente -->
          <div class="bg-gray-50 p-3 rounded-lg">
            <h3 class="text-base font-medium text-gray-900 mb-1">Paciente</h3>
            <p class="text-sm text-gray-700">
              {{ pacienteSeleccionado?.nombres }} {{ pacienteSeleccionado?.apellidos }} - DNI: {{ pacienteSeleccionado?.dni }}
            </p>
          </div>

          <!-- Motivo y diagnósticos -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <InputField
              v-model="form.motivo_consulta"
              label="Motivo de consulta"
              placeholder="Describa el motivo de la consulta..."
              type="textarea"
              rows="2"
            />
            <SelectInput
              v-model="form.higiene_bucal"
              label="Higiene bucal"
              :options="[
                { value: 'Bueno', label: 'Bueno' },
                { value: 'Regular', label: 'Regular' },
                { value: 'Malo', label: 'Malo' }
              ]"
              placeholder="Seleccione..."
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <InputField
              v-model="form.diagnostico_presuntivo"
              label="Diagnóstico presuntivo"
              placeholder="Diagnóstico inicial..."
              type="textarea"
              rows="2"
            />
            <InputField
              v-model="form.diagnostico_principal"
              label="Diagnóstico principal"
              placeholder="Diagnóstico confirmado..."
              type="textarea"
              rows="2"
            />
          </div>

          <!-- Anamnesis -->
          <div class="border-t pt-4">
            <h3 class="text-base font-medium text-gray-900 mb-3">Anamnesis</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <InputField
                v-model="form.sintoma_principal"
                label="Síntoma principal"
                placeholder="Síntoma principal del paciente..."
              />
              <InputField
                v-model="form.tiempo_inicio_sintomas"
                label="Tiempo de inicio de síntomas"
                placeholder="Ej: 1 semana, 3 días..."
              />
            </div>

            <div class="grid grid-cols-1 gap-4">
              <InputField
                v-model="form.tratamiento_previo"
                label="Tratamiento previo"
                placeholder="Tratamientos anteriores realizados..."
                type="textarea"
                rows="2"
              />
              <InputField
                v-model="form.enfermedades_actuales"
                label="Enfermedades actuales"
                placeholder="Enfermedades que padece actualmente..."
                type="textarea"
                rows="2"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.bajo_tratamiento_medico"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <label class="text-sm font-medium text-gray-700">¿Bajo tratamiento médico?</label>
              </div>
              <InputField
                v-if="form.bajo_tratamiento_medico"
                v-model="form.detalle_tratamiento_actual"
                label="Detalle del tratamiento actual"
                placeholder="Especifique el tratamiento..."
              />
            </div>

            <div class="grid grid-cols-1 gap-4">
              <InputField
                v-model="form.alergias_paciente"
                label="Alergias del paciente"
                placeholder="Alergias conocidas..."
                type="textarea"
                rows="2"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.intervenciones_quirurgicas_previas"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <label class="text-sm font-medium text-gray-700">¿Intervenciones quirúrgicas previas?</label>
              </div>
              <InputField
                v-if="form.intervenciones_quirurgicas_previas"
                v-model="form.detalle_intervenciones"
                label="Detalle de intervenciones"
                placeholder="Especifique las intervenciones..."
              />
            </div>

            <!-- Problemas específicos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.hemorragia_post_tratamiento"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <label class="text-sm font-medium text-gray-700">¿Hemorragia post-tratamiento?</label>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.problema_anestesia"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <label class="text-sm font-medium text-gray-700">¿Problemas con anestesia?</label>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.dificultad_abrir_masticar"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <label class="text-sm font-medium text-gray-700">¿Dificultad para abrir/masticar?</label>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.sensibilidad_dental"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <label class="text-sm font-medium text-gray-700">¿Sensibilidad dental?</label>
              </div>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex justify-end space-x-3 pt-4 border-t">
            <Button variant="secondary" @click="cerrarModalCrear">
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="primary"
              :loading="creando"
              :disabled="creando"
            >
              Crear Historial
            </Button>
          </div>
        </form>
      </Modal>

      <!-- Modal Ver Historial -->
      <Modal
        :show="modalVerAbierto"
        title="Historial Clínico"
        size="5xl"
        @close="cerrarModalVer"
      >
        <div v-if="historialSeleccionado" class="space-y-6">
          <!-- Información básica -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Información del Paciente</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium">Nombre:</span> {{ historialSeleccionado.paciente.nombres }} {{ historialSeleccionado.paciente.apellidos }}
              </div>
              <div>
                <span class="font-medium">DNI:</span> {{ historialSeleccionado.paciente.dni }}
              </div>
              <div>
                <span class="font-medium">Código Historial:</span> {{ historialSeleccionado.codigo_historial }}
              </div>
              <div>
                <span class="font-medium">Fecha Creación:</span> {{ formatDate(historialSeleccionado.created_at) }}
              </div>
            </div>
          </div>

          <!-- Motivo y diagnósticos -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="font-medium text-gray-900 mb-2">Motivo de Consulta</h4>
              <p class="text-gray-700">{{ historialSeleccionado.motivo_consulta || 'No especificado' }}</p>
            </div>
            <div>
              <h4 class="font-medium text-gray-900 mb-2">Higiene Bucal</h4>
              <p class="text-gray-700">{{ historialSeleccionado.higiene_bucal || 'No especificado' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="font-medium text-gray-900 mb-2">Diagnóstico Presuntivo</h4>
              <p class="text-gray-700">{{ historialSeleccionado.diagnostico_presuntivo || 'No especificado' }}</p>
            </div>
            <div>
              <h4 class="font-medium text-gray-900 mb-2">Diagnóstico Principal</h4>
              <p class="text-gray-700">{{ historialSeleccionado.diagnostico_principal || 'No especificado' }}</p>
            </div>
          </div>

          <!-- Anamnesis -->
          <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Anamnesis</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div>
                <span class="font-medium">Síntoma Principal:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.sintoma_principal || 'No especificado' }}</p>
              </div>
              <div>
                <span class="font-medium">Tiempo de Inicio:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.tiempo_inicio_sintomas || 'No especificado' }}</p>
              </div>
              <div class="md:col-span-2">
                <span class="font-medium">Tratamiento Previo:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.tratamiento_previo || 'No especificado' }}</p>
              </div>
              <div class="md:col-span-2">
                <span class="font-medium">Enfermedades Actuales:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.enfermedades_actuales || 'No especificado' }}</p>
              </div>
              <div>
                <span class="font-medium">Bajo Tratamiento Médico:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.bajo_tratamiento_medico ? 'Sí' : 'No' }}</p>
              </div>
              <div v-if="historialSeleccionado.bajo_tratamiento_medico">
                <span class="font-medium">Detalle Tratamiento:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.detalle_tratamiento_actual || 'No especificado' }}</p>
              </div>
              <div class="md:col-span-2">
                <span class="font-medium">Alergias:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.alergias_paciente || 'No especificado' }}</p>
              </div>
              <div>
                <span class="font-medium">Intervenciones Quirúrgicas:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.intervenciones_quirurgicas_previas ? 'Sí' : 'No' }}</p>
              </div>
              <div v-if="historialSeleccionado.intervenciones_quirurgicas_previas">
                <span class="font-medium">Detalle Intervenciones:</span>
                <p class="text-gray-700 mt-1">{{ historialSeleccionado.detalle_intervenciones || 'No especificado' }}</p>
              </div>
            </div>

            <div class="mt-4">
              <h4 class="font-medium text-gray-900 mb-2">Problemas Específicos</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                  <input
                    :checked="historialSeleccionado.hemorragia_post_tratamiento"
                    type="checkbox"
                    disabled
                    class="rounded border-gray-300"
                  />
                  <label>Hemorragia post-tratamiento</label>
                </div>
                <div class="flex items-center space-x-2">
                  <input
                    :checked="historialSeleccionado.problema_anestesia"
                    type="checkbox"
                    disabled
                    class="rounded border-gray-300"
                  />
                  <label>Problemas con anestesia</label>
                </div>
                <div class="flex items-center space-x-2">
                  <input
                    :checked="historialSeleccionado.dificultad_abrir_masticar"
                    type="checkbox"
                    disabled
                    class="rounded border-gray-300"
                  />
                  <label>Dificultad abrir/masticar</label>
                </div>
                <div class="flex items-center space-x-2">
                  <input
                    :checked="historialSeleccionado.sensibilidad_dental"
                    type="checkbox"
                    disabled
                    class="rounded border-gray-300"
                  />
                  <label>Sensibilidad dental</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Detalles del historial -->
          <div v-if="historialSeleccionado.detalles && historialSeleccionado.detalles.length > 0" class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Detalles del Historial</h3>
            <div class="space-y-4">
              <div v-for="detalle in historialSeleccionado.detalles" :key="detalle.id_detalle" class="bg-gray-50 p-4 rounded-lg">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-medium">{{ formatDate(detalle.fecha) }}</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <div v-if="detalle.diagnostico">
                    <span class="font-medium">Diagnóstico:</span>
                    <p class="text-gray-700 mt-1">{{ detalle.diagnostico }}</p>
                  </div>
                  <div v-if="detalle.procedimiento_realizado">
                    <span class="font-medium">Procedimiento:</span>
                    <p class="text-gray-700 mt-1">{{ detalle.procedimiento_realizado }}</p>
                  </div>
                  <div v-if="detalle.notas_medicas" class="md:col-span-2">
                    <span class="font-medium">Notas Médicas:</span>
                    <p class="text-gray-700 mt-1">{{ detalle.notas_medicas }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tratamientos -->
          <div v-if="historialSeleccionado.tratamientos && historialSeleccionado.tratamientos.length > 0" class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Tratamientos</h3>
            <div class="space-y-2">
              <div v-for="tratamiento in historialSeleccionado.tratamientos" :key="tratamiento.id" class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                  <span class="font-medium">{{ tratamiento.tratamiento.nombre }}</span>
                  <span v-if="tratamiento.descripcion" class="text-gray-600 ml-2">({{ tratamiento.descripcion }})</span>
                </div>
                <span
                  class="px-2 py-1 text-xs rounded-full"
                  :class="{
                    'bg-yellow-100 text-yellow-800': tratamiento.estado === 'sugerido',
                    'bg-blue-100 text-blue-800': tratamiento.estado === 'en_curso',
                    'bg-green-100 text-green-800': tratamiento.estado === 'completado',
                    'bg-red-100 text-red-800': tratamiento.estado === 'cancelado'
                  }"
                >
                  {{ tratamiento.estado.replace('_', ' ') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </Modal>

      <!-- Modal Completar Datos de Usuario Externo -->
      <FormCompletarDatos
        v-if="modalCompletarDatosAbierto && usuarioExternoSeleccionado"
        :persona="usuarioExternoSeleccionado"
        @cerrar="cerrarModalCompletarDatos"
        @guardado="onDatosGuardados"
      />
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  CheckCircle, XCircle, Eye, Plus, FileText,
  UserPlus, FilePlus, Edit, Users, UserX, FileWarning, UserCheck
} from 'lucide-vue-next'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import InputField from '@/components/common/InputField.vue'
import SelectInput from '@/components/common/SelectInput.vue'
import Modal from '@/components/common/Modal.vue'
import Loading from '@/components/common/Loading.vue'
import FormCompletarDatos from '@/components/historial/FormCompletarDatos.vue'
import historialClinicoService, {
  type PacienteHistorial,
  type HistorialClinico,
  type CrearHistorialData
} from '@/api/historialClinicoService'
import Swal from 'sweetalert2'

const router = useRouter()

// Estado
const loading = ref(false)
const busqueda = ref('')
const pacientes = ref<PacienteHistorial[]>([])
const estadisticas = ref<any>(null)
const tabActivo = ref<string>('todos')

// Tabs
const tabs = computed(() => [
  { id: 'todos', label: 'Todos', icon: Users, count: pacientes.value.length },
  { id: 'externos', label: 'Usuarios Externos', icon: UserX, count: estadisticas.value?.externos_sin_registro || 0 },
  { id: 'sin_historial', label: 'Sin Historial', icon: FileWarning, count: estadisticas.value?.pacientes_sin_historial || 0 },
  { id: 'con_historial', label: 'Con Historial', icon: UserCheck, count: estadisticas.value?.pacientes_con_historial || 0 },
])

// Filtrar personas por tab
const personasFiltradas = computed(() => {
  switch (tabActivo.value) {
    case 'externos':
      return pacientes.value.filter(p => p.tipo === 'externo')
    case 'sin_historial':
      return pacientes.value.filter(p => p.tipo === 'paciente' && !p.tiene_historial)
    case 'con_historial':
      return pacientes.value.filter(p => p.tipo === 'paciente' && p.tiene_historial)
    default:
      return pacientes.value
  }
})

// Modal completar datos (usuario externo)
const modalCompletarDatosAbierto = ref(false)
const usuarioExternoSeleccionado = ref<PacienteHistorial | null>(null)

// Modal crear
const modalCrearAbierto = ref(false)
const pacienteSeleccionado = ref<PacienteHistorial | null>(null)
const creando = ref(false)
const form = ref<CrearHistorialData>({
  id_paciente: 0,
  motivo_consulta: '',
  diagnostico_presuntivo: '',
  diagnostico_principal: '',
  higiene_bucal: null,
  sintoma_principal: '',
  tiempo_inicio_sintomas: '',
  tratamiento_previo: '',
  enfermedades_actuales: '',
  bajo_tratamiento_medico: false,
  detalle_tratamiento_actual: '',
  alergias_paciente: '',
  intervenciones_quirurgicas_previas: false,
  detalle_intervenciones: '',
  hemorragia_post_tratamiento: false,
  problema_anestesia: false,
  dificultad_abrir_masticar: false,
  sensibilidad_dental: false
})

// Modal ver
const modalVerAbierto = ref(false)
const historialSeleccionado = ref<HistorialClinico | null>(null)

// Funciones
const cargarPacientes = async () => {
  loading.value = true
  try {
    const data = await historialClinicoService.obtenerPacientes(busqueda.value)
    pacientes.value = data.personas
    estadisticas.value = data.estadisticas
  } catch (error) {
    console.error('Error al cargar personas:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al cargar la lista de personas atendidas'
    })
  } finally {
    loading.value = false
  }
}

const debouncedBuscar = (() => {
  let timeout: NodeJS.Timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(cargarPacientes, 500)
  }
})()

// Completar datos de usuario externo
const abrirModalCompletarDatos = (persona: PacienteHistorial) => {
  usuarioExternoSeleccionado.value = persona
  modalCompletarDatosAbierto.value = true
}

const cerrarModalCompletarDatos = () => {
  modalCompletarDatosAbierto.value = false
  usuarioExternoSeleccionado.value = null
}

const onDatosGuardados = () => {
  cargarPacientes()
  cerrarModalCompletarDatos()
}

// Editar paciente
const editarPaciente = (idPaciente: number) => {
  Swal.fire({
    title: 'Editar Paciente',
    text: 'Funcionalidad de edición estará disponible próximamente',
    icon: 'info'
  })
}

const abrirModalCrear = (paciente: PacienteHistorial) => {
  pacienteSeleccionado.value = paciente
  form.value = {
    id_paciente: paciente.id_paciente!,
    motivo_consulta: '',
    diagnostico_presuntivo: '',
    diagnostico_principal: '',
    higiene_bucal: null,
    sintoma_principal: '',
    tiempo_inicio_sintomas: '',
    tratamiento_previo: '',
    enfermedades_actuales: '',
    bajo_tratamiento_medico: false,
    detalle_tratamiento_actual: '',
    alergias_paciente: '',
    intervenciones_quirurgicas_previas: false,
    detalle_intervenciones: '',
    hemorragia_post_tratamiento: false,
    problema_anestesia: false,
    dificultad_abrir_masticar: false,
    sensibilidad_dental: false
  }
  modalCrearAbierto.value = true
}

const cerrarModalCrear = () => {
  modalCrearAbierto.value = false
  pacienteSeleccionado.value = null
}

const crearHistorial = async () => {
  if (!pacienteSeleccionado.value) return

  creando.value = true
  try {
    await historialClinicoService.crearHistorial(form.value)
    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: 'Historial clínico creado exitosamente',
      timer: 2000
    })
    cerrarModalCrear()
    await cargarPacientes() // Recargar lista
  } catch (error) {
    console.error('Error al crear historial:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al crear el historial clínico'
    })
  } finally {
    creando.value = false
  }
}

const verHistorial = (idHistorial: number) => {
  router.push({ name: 'ver-historial', params: { id: idHistorial } })
}

const cerrarModalVer = () => {
  modalVerAbierto.value = false
  historialSeleccionado.value = null
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  cargarPacientes()
})
</script>
