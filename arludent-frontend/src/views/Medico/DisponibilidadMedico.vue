<template>
  <DashboardLayout>
    <div class="disponibilidad-medico">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
          Gestión de Disponibilidad
        </h1>
        <p class="text-gray-600">
          Administra tus horarios de atención y periodos de bloqueo
        </p>
      </div>

    <!-- Información del tipo de médico -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
        <div>
          <p class="text-sm font-medium text-blue-800">
            Tipo de Médico: {{ tipoMedicoTexto }}
          </p>
          <p class="text-xs text-blue-600 mt-1">
            {{ esCabecera
              ? 'Como médico de cabecera, tus horarios están predefinidos según tu turno y no pueden modificarse.'
              : 'Como médico especialista, tienes flexibilidad para gestionar tu disponibilidad.' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Explicación del sistema -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="flex items-start">
        <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
        <div>
          <h4 class="text-sm font-medium text-yellow-800 mb-2">¿Cómo funciona la disponibilidad?</h4>
          <div class="text-xs text-yellow-700 space-y-1">
            <p><strong>Horario semanal recurrente:</strong> Selecciona un día de la semana y horario. Se repetirá todos los días de esa semana.</p>
            <p><strong>Horario específico por fechas:</strong> Especifica fechas de inicio y fin. Solo aplica para ese período específico.</p>
            <p><strong>Bloqueos:</strong> Pueden ser semanales (ej: todos los viernes) o por fecha específica.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros y acciones -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <!-- Filtros -->
        <div class="flex flex-wrap gap-3">
          <select
            v-model="filtros.tipo"
            @change="cargarDisponibilidad"
            class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos los tipos</option>
            <option value="horario">Horarios</option>
            <option value="bloqueo">Bloqueos</option>
          </select>

          <select
            v-model="filtros.dia_semana"
            @change="cargarDisponibilidad"
            class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos los días</option>
            <option value="0">Domingo</option>
            <option value="1">Lunes</option>
            <option value="2">Martes</option>
            <option value="3">Miércoles</option>
            <option value="4">Jueves</option>
            <option value="5">Viernes</option>
            <option value="6">Sábado</option>
          </select>
        </div>

        <!-- Pestañas de vista -->
        <div class="flex border-b border-gray-200">
          <button
            @click="vistaActual = 'lista'"
            :class="[
              'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
              vistaActual === 'lista'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            ]"
          >
            <i class="fas fa-list mr-2"></i>
            Lista
          </button>
          <button
            @click="vistaActual = 'calendario'"
            :class="[
              'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
              vistaActual === 'calendario'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            ]"
          >
            <i class="fas fa-calendar-alt mr-2"></i>
            Calendario
          </button>
        </div>

        <!-- Botones de acción -->
        <div class="flex gap-2">
          <!-- Solo mostrar botón para especialistas -->
          <button
            v-if="!esCabecera"
            @click="abrirModalCrear"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
          >
            <i class="fas fa-plus mr-2"></i>
            Nuevo Horario
          </button>
          
          <!-- Mensaje informativo para médicos de cabecera -->
          <div
            v-else
            class="px-4 py-2 bg-blue-50 text-blue-700 rounded-md border border-blue-200 text-sm flex items-center"
          >
            <i class="fas fa-info-circle mr-2"></i>
            Tus horarios están predefinidos automáticamente (Lunes a Sábado)
          </div>

          <button
            @click="cargarDisponibilidad"
            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
          >
            <i class="fas fa-sync-alt mr-2"></i>
            Actualizar
          </button>
        </div>
      </div>
    </div>

    <!-- Lista de disponibilidad -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <!-- Vista Lista -->
      <div v-if="vistaActual === 'lista'">
        <div v-if="cargando" class="p-8 text-center">
          <i class="fas fa-spinner fa-spin text-blue-500 text-xl"></i>
          <p class="text-gray-500 mt-2">Cargando disponibilidad...</p>
        </div>

        <div v-else-if="disponibilidades.length === 0" class="p-8 text-center">
          <i class="fas fa-calendar-times text-gray-400 text-4xl mb-4"></i>
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            {{ esCabecera ? 'Cargando horarios predefinidos...' : 'No hay disponibilidad configurada' }}
          </h3>
          <p class="text-gray-500 mb-4">
            {{ esCabecera 
              ? 'Tus horarios de Lunes a Sábado se crearán automáticamente' 
              : 'Comienza creando tu primer horario de atención' }}
          </p>
          <button
            v-if="!esCabecera"
            @click="abrirModalCrear"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Crear primer horario
          </button>
          <button
            v-else
            @click="cargarDisponibilidad"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            <i class="fas fa-sync-alt mr-2"></i>
            Cargar horarios
          </button>
        </div>

        <div v-else class="divide-y divide-gray-200">
          <div
            v-for="disp in disponibilidades"
            :key="disp.id_disp"
            class="p-4 hover:bg-gray-50"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      disp.tipo === 'horario'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800'
                    ]"
                  >
                    <i :class="disp.tipo === 'horario' ? 'fas fa-clock' : 'fas fa-ban'" class="mr-1"></i>
                    {{ disp.tipo === 'horario' ? 'Horario' : 'Bloqueo' }}
                  </span>

                  <span v-if="disp.dia_semana_texto" class="text-sm text-gray-600">
                    <i class="fas fa-calendar-day mr-1"></i>
                    {{ disp.dia_semana_texto }}
                  </span>

                  <span v-if="disp.es_horario_cabecera" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                    Horario de Cabecera
                  </span>
                </div>

                <div class="text-sm text-gray-900 mb-1">
                  <span v-if="disp.hora_inicio && disp.hora_fin">
                    {{ disp.hora_inicio }} - {{ disp.hora_fin }}
                  </span>
                  <span v-else-if="disp.fecha_inicio && disp.fecha_fin">
                    Del {{ formatDate(disp.fecha_inicio) }} al {{ formatDate(disp.fecha_fin) }}
                  </span>
                  <span v-else>
                    Horario semanal recurrente
                  </span>
                </div>

                <div v-if="disp.motivo" class="text-sm text-gray-600">
                  <i class="fas fa-comment mr-1"></i>
                  {{ disp.motivo }}
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">ID: {{ disp.id_disp }}</span>
                <button
                  @click="onEditarClick(disp)"
                  class="p-2 text-gray-400 hover:text-blue-600 transition-colors border border-gray-300"
                  title="Editar"
                >
                  EDITAR
                </button>

                <button
                  v-if="disp.puede_eliminar"
                  @click="confirmarEliminar(disp)"
                  class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                  title="Eliminar"
                >
                  <i class="fas fa-trash"></i>
                </button>

                <span v-else class="text-xs text-gray-400 px-2 py-1 bg-gray-100 rounded">
                  No eliminable
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vista Calendario -->
      <div v-else-if="vistaActual === 'calendario'">
        <div v-if="cargando" class="p-8 text-center">
          <i class="fas fa-spinner fa-spin text-blue-500 text-xl"></i>
          <p class="text-gray-500 mt-2">Cargando calendario...</p>
        </div>

        <div v-else class="p-6">
          <CalendarioDisponibilidad
            :disponibilidades="disponibilidades"
            @editar-disponibilidad="editarDisponibilidad"
            @mes-cambio="onMesCambio"
          />
        </div>
      </div>
    </div>

    <!-- Modal Crear/Editar Disponibilidad -->
    <div
      v-if="mostrarModalCrear || mostrarModalEditar"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="cerrarModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ mostrarModalEditar ? 'Editar Disponibilidad' : 'Nueva Disponibilidad' }}
          </h3>

          <form @submit.prevent="guardarDisponibilidad" class="space-y-4">
            <!-- Tipo -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Tipo *
              </label>
              <select
                v-model="formulario.tipo"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="horario">Horario de atención</option>
                <option value="bloqueo">Bloqueo temporal</option>
              </select>
            </div>

            <!-- Tipo de horario -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Tipo de horario *
              </label>
              <select
                v-model="tipoHorarioSeleccionado"
                @change="onTipoHorarioChange"
                :disabled="esCabecera && formulario.tipo === 'horario'"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
              >
                <option value="semanal">Horario semanal recurrente</option>
                <option value="fecha_unica" :disabled="esCabecera && formulario.tipo === 'horario'">Fecha específica única</option>
                <option value="especifico" :disabled="esCabecera && formulario.tipo === 'horario'">Rango de fechas específicas</option>
              </select>
              <p v-if="esCabecera && formulario.tipo === 'horario'" class="text-xs text-blue-600 mt-1">
                <i class="fas fa-info-circle mr-1"></i>
                Los médicos de cabecera solo pueden usar horarios semanales
              </p>
            </div>

            <!-- Explicación del tipo seleccionado -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
              <div v-if="tipoHorarioSeleccionado === 'semanal'" class="flex items-center">
                <i class="fas fa-repeat text-blue-500 mr-2"></i>
                <div>
                  <p class="text-sm font-medium text-blue-800">Horario semanal recurrente</p>
                  <p class="text-xs text-blue-600">Se repetirá todos los [día seleccionado] del año</p>
                </div>
              </div>
              <div v-else-if="tipoHorarioSeleccionado === 'fecha_unica'" class="flex items-center">
                <i class="fas fa-calendar-day text-blue-500 mr-2"></i>
                <div>
                  <p class="text-sm font-medium text-blue-800">Fecha específica única</p>
                  <p class="text-xs text-blue-600">Solo aplicará para un día específico seleccionado</p>
                </div>
              </div>
              <div v-else class="flex items-center">
                <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                <div>
                  <p class="text-sm font-medium text-blue-800">Rango de fechas específicas</p>
                  <p class="text-xs text-blue-600">Aplicará para un período específico con fecha de inicio y fin</p>
                </div>
              </div>
            </div>

            <!-- Día de la semana (solo para horarios semanales) -->
            <div v-if="tipoHorarioSeleccionado === 'semanal'">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Día de la semana
                <span v-if="esCabecera && formulario.tipo === 'horario'" class="text-red-500">*</span>
              </label>
              <select
                v-model="formulario.dia_semana"
                :required="esCabecera && formulario.tipo === 'horario'"
                @change="onDiaSemanaChange"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Seleccionar día</option>
                <option value="0">Domingo</option>
                <option value="1">Lunes</option>
                <option value="2">Martes</option>
                <option value="3">Miércoles</option>
                <option value="4">Jueves</option>
                <option value="5">Viernes</option>
                <option value="6">Sábado</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">
                Todos los [día seleccionado] tendrán este horario
              </p>
            </div>

            <!-- Fecha específica única -->
            <div v-if="tipoHorarioSeleccionado === 'fecha_unica'">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Fecha específica *
              </label>
              <input
                v-model="formulario.fecha_inicio"
                type="date"
                :min="fechaMinima"
                @change="onFechaUnicaChange"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
              <p class="text-xs text-gray-500 mt-1">
                El horario aplicará solo para este día específico
              </p>
            </div>

            <!-- Fechas específicas (solo para horarios de rango) -->
            <div v-if="tipoHorarioSeleccionado === 'especifico'" class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Fecha inicio *
                </label>
                <input
                  v-model="formulario.fecha_inicio"
                  type="date"
                  :min="fechaMinima"
                  @change="onFechaInicioChange"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                <p class="text-xs text-gray-500 mt-1">
                  Inicio del período específico
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Fecha fin *
                </label>
                <input
                  v-model="formulario.fecha_fin"
                  type="date"
                  :min="formulario.fecha_inicio || fechaMinima"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                <p class="text-xs text-gray-500 mt-1">
                  Fin del período específico
                </p>
              </div>
            </div>

            <!-- Horarios predefinidos (solo lectura para cabecera) -->
            <div v-if="esCabecera && formulario.tipo === 'horario' && horariosPredefinidos" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-center mb-2">
                <i class="fas fa-clock text-blue-500 mr-2"></i>
                <span class="text-sm font-medium text-blue-800">Horario Predefinido</span>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-blue-700 mb-1">Hora inicio</label>
                  <div class="text-lg font-semibold text-blue-900">{{ horariosPredefinidos.hora_inicio }}</div>
                </div>
                <div>
                  <label class="block text-xs font-medium text-blue-700 mb-1">Hora fin</label>
                  <div class="text-lg font-semibold text-blue-900">{{ horariosPredefinidos.hora_fin }}</div>
                </div>
              </div>
              <p class="text-xs text-blue-600 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                Los horarios para médicos de cabecera están predefinidos y no pueden modificarse.
              </p>
            </div>

            <!-- Horarios editables (para especialistas o bloqueos) -->
            <div v-else class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Hora inicio
                  <span v-if="formulario.tipo === 'horario'" class="text-red-500">*</span>
                </label>
                <input
                  v-model="formulario.hora_inicio"
                  type="time"
                  :required="formulario.tipo === 'horario'"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Hora fin
                  <span v-if="formulario.tipo === 'horario'" class="text-red-500">*</span>
                </label>
                <input
                  v-model="formulario.hora_fin"
                  type="time"
                  :required="formulario.tipo === 'horario'"
                  :min="formulario.hora_inicio || undefined"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
            </div>

            <!-- Motivo -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Motivo (opcional)
              </label>
              <textarea
                v-model="formulario.motivo"
                rows="3"
                maxlength="500"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Describe el motivo del bloqueo o notas adicionales..."
              ></textarea>
            </div>

            <!-- Errores de validación -->
            <div v-if="errores.length > 0" class="bg-red-50 border border-red-200 rounded-md p-3">
              <div class="flex">
                <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 mr-2"></i>
                <div>
                  <h4 class="text-sm font-medium text-red-800">Errores de validación:</h4>
                  <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                    <li v-for="error in errores" :key="error">{{ error }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="cerrarModal"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="guardando"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <i v-if="guardando" class="fas fa-spinner fa-spin mr-2"></i>
                {{ guardando ? 'Guardando...' : (mostrarModalEditar ? 'Actualizar' : 'Crear') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div
      v-if="mostrarModalEliminar"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="mostrarModalEliminar = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">
              Confirmar eliminación
            </h3>
          </div>

          <p class="text-gray-600 mb-6">
            ¿Estás seguro de que deseas eliminar esta disponibilidad?
            Esta acción no se puede deshacer.
          </p>

          <div v-if="disponibilidadAEliminar" class="bg-gray-50 rounded-md p-3 mb-6">
            <p class="text-sm text-gray-700">
              <strong>Tipo:</strong> {{ disponibilidadAEliminar.tipo === 'horario' ? 'Horario' : 'Bloqueo' }}
            </p>
            <p v-if="disponibilidadAEliminar.dia_semana_texto" class="text-sm text-gray-700">
              <strong>Día:</strong> {{ disponibilidadAEliminar.dia_semana_texto }}
            </p>
            <p v-if="disponibilidadAEliminar.hora_inicio && disponibilidadAEliminar.hora_fin" class="text-sm text-gray-700">
              <strong>Horario:</strong> {{ disponibilidadAEliminar.hora_inicio }} - {{ disponibilidadAEliminar.hora_fin }}
            </p>
          </div>

          <div class="flex justify-end gap-3">
            <button
              @click="mostrarModalEliminar = false"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
            >
              Cancelar
            </button>
            <button
              @click="eliminarDisponibilidad"
              :disabled="eliminando"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i v-if="eliminando" class="fas fa-spinner fa-spin mr-2"></i>
              {{ eliminando ? 'Eliminando...' : 'Eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useNotification } from '@/composables/useNotification'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import CalendarioDisponibilidad from '@/components/medico/CalendarioDisponibilidad.vue'
import disponibilidadMedicoService, {
  type DisponibilidadMedico,
  type CrearDisponibilidadData,
  type ActualizarDisponibilidadData
} from '@/api/disponibilidadMedicoService'

// Composables
const { showSuccess, showError } = useNotification()

// Estado reactivo
const cargando = ref(false)
const guardando = ref(false)
const eliminando = ref(false)
const tipoMedico = ref<'cabecera_manana' | 'cabecera_tarde' | 'especialista'>('especialista')
const horariosPredefinidos = ref<{ hora_inicio: string; hora_fin: string } | null>(null)
const esCabecera = ref(false)
const tipoHorarioSeleccionado = ref<'semanal' | 'especifico' | 'fecha_unica'>('semanal')
const vistaActual = ref<'lista' | 'calendario'>('lista')

const disponibilidades = ref<DisponibilidadMedico[]>([])
const filtros = ref({
  tipo: '',
  dia_semana: ''
})

// Modales
const mostrarModalCrear = ref(false)
const mostrarModalEditar = ref(false)
const mostrarModalEliminar = ref(false)
const disponibilidadAEliminar = ref<DisponibilidadMedico | null>(null)

// Formulario
const formulario = ref<CrearDisponibilidadData & { id_disp?: number }>({
  tipo: 'horario',
  dia_semana: null,
  fecha_inicio: null,
  fecha_fin: null,
  hora_inicio: null,
  hora_fin: null,
  motivo: null
})

const errores = ref<string[]>([])

// Computed
const tipoMedicoTexto = computed(() => {
  switch (tipoMedico.value) {
    case 'cabecera_manana':
      return 'Médico de Cabecera - Turno Mañana (9:00-13:00)'
    case 'cabecera_tarde':
      return 'Médico de Cabecera - Turno Tarde (13:00-20:00)'
    case 'especialista':
      return 'Médico Especialista'
    default:
      return 'Médico'
  }
})

const fechaMinima = computed(() => {
  const hoy = new Date()
  return hoy.toISOString().split('T')[0]
})

// Métodos
const cargarDisponibilidad = async () => {
  try {
    console.log('Cargando disponibilidad...')
    cargando.value = true
    const params: any = {}

    if (filtros.value.tipo) params.tipo = filtros.value.tipo
    if (filtros.value.dia_semana) params.dia_semana = parseInt(filtros.value.dia_semana)

    console.log('Parámetros de carga:', params)

    const response = await disponibilidadMedicoService.obtenerDisponibilidad(params)

    console.log('Respuesta de carga:', response)

    if (response.success && response.data) {
      disponibilidades.value = response.data.disponibilidades
      tipoMedico.value = response.data.tipo_medico
      horariosPredefinidos.value = response.data.horarios_predefinidos
      esCabecera.value = response.data.es_cabecera
      console.log('Disponibilidades cargadas:', disponibilidades.value.length)
      console.log('Tipo médico:', tipoMedico.value)
      console.log('Horarios predefinidos:', horariosPredefinidos.value)
      console.log('Es cabecera:', esCabecera.value)
    } else {
      console.log('Error en respuesta:', response.message)
      showError(response.message || 'Error al cargar disponibilidad')
    }
  } catch (error: any) {
    console.error('Error al cargar disponibilidad:', error)
    showError(error.message || 'Error al cargar disponibilidad')
  } finally {
    cargando.value = false
  }
}

const guardarDisponibilidad = async () => {
  try {
    console.log('Guardando disponibilidad...')
    console.log('Mostrar modal editar:', mostrarModalEditar.value)
    console.log('ID en formulario:', formulario.value.id_disp)

    // Validar formulario - temporalmente comentado para nuevo tipo
    // const validacion = disponibilidadMedicoService.validarDisponibilidad(formulario.value, tipoHorarioSeleccionado.value)
    // if (!validacion.valido) {
    //   errores.value = validacion.errores
    //   return
    // }

    // Validación manual temporal para fecha única
    if (tipoHorarioSeleccionado.value === 'fecha_unica' && !formulario.value.fecha_inicio) {
      errores.value = ['Debe seleccionar una fecha específica']
      return
    }

    errores.value = []
    guardando.value = true

    // Preparar datos con tipos correctos
    const datos: any = {
      ...formulario.value,
      dia_semana: formulario.value.dia_semana !== null && formulario.value.dia_semana !== undefined 
        ? (typeof formulario.value.dia_semana === 'string' ? parseInt(formulario.value.dia_semana) : formulario.value.dia_semana)
        : null
    }

    // Para médicos de cabecera con horarios, usar horarios predefinidos
    if (esCabecera.value && formulario.value.tipo === 'horario' && horariosPredefinidos.value) {
      datos.hora_inicio = horariosPredefinidos.value.hora_inicio
      datos.hora_fin = horariosPredefinidos.value.hora_fin
    }

    console.log('Datos a enviar:', datos)

    if (mostrarModalEditar.value && formulario.value.id_disp) {
      console.log('Actualizando disponibilidad con ID:', formulario.value.id_disp)
      // Actualizar
      const response = await disponibilidadMedicoService.actualizarDisponibilidad(
        formulario.value.id_disp,
        datos as ActualizarDisponibilidadData
      )

      console.log('Respuesta de actualización:', response)

      if (response.success) {
        showSuccess('Disponibilidad actualizada exitosamente')
        cerrarModal()
        await cargarDisponibilidad()
      } else {
        showError(response.message || 'Error al actualizar disponibilidad')
      }
    } else {
      console.log('Creando nueva disponibilidad')
      // Crear
      const response = await disponibilidadMedicoService.crearDisponibilidad(
        datos as CrearDisponibilidadData
      )

      console.log('Respuesta de creación:', response)

      if (response.success) {
        showSuccess('Disponibilidad creada exitosamente')
        cerrarModal()
        await cargarDisponibilidad()
      } else {
        showError(response.message || 'Error al crear disponibilidad')
      }
    }
  } catch (error: any) {
    console.error('Error al guardar disponibilidad:', error)
    if (error.errors) {
      errores.value = Object.values(error.errors).flat() as string[]
    } else {
      showError(error.message || 'Error al guardar disponibilidad')
    }
  } finally {
    guardando.value = false
  }
}

const editarDisponibilidad = (disp: DisponibilidadMedico) => {
  console.log('Editando disponibilidad:', disp)
  console.log('ID de disponibilidad:', disp.id_disp)
  console.log('Fecha inicio original:', disp.fecha_inicio)
  console.log('Fecha fin original:', disp.fecha_fin)
  console.log('Hora inicio original:', disp.hora_inicio)
  console.log('Hora fin original:', disp.hora_fin)

  // Determinar el tipo de horario basado en los datos existentes
  if (disp.dia_semana !== null) {
    tipoHorarioSeleccionado.value = 'semanal'
  } else if (disp.fecha_inicio === disp.fecha_fin) {
    tipoHorarioSeleccionado.value = 'fecha_unica'
  } else {
    tipoHorarioSeleccionado.value = 'especifico'
  }

  // Convertir fechas de manera segura
  let fechaInicioFormateada = null
  let fechaFinFormateada = null

  if (disp.fecha_inicio) {
    try {
      const fecha = new Date(disp.fecha_inicio)
      if (!isNaN(fecha.getTime())) {
        fechaInicioFormateada = fecha.toISOString().split('T')[0]
      }
    } catch (error) {
      console.error('Error convirtiendo fecha_inicio:', error)
    }
  }

  if (disp.fecha_fin) {
    try {
      const fecha = new Date(disp.fecha_fin)
      if (!isNaN(fecha.getTime())) {
        fechaFinFormateada = fecha.toISOString().split('T')[0]
      }
    } catch (error) {
      console.error('Error convirtiendo fecha_fin:', error)
    }
  }

  // Asegurar que las horas estén en formato HH:MM
  let horaInicioFormateada = null
  let horaFinFormateada = null

  if (disp.hora_inicio) {
    // Si viene con segundos, quitarlos
    horaInicioFormateada = disp.hora_inicio.substring(0, 5)
  }

  if (disp.hora_fin) {
    // Si viene con segundos, quitarlos
    horaFinFormateada = disp.hora_fin.substring(0, 5)
  }

  formulario.value = {
    id_disp: disp.id_disp,
    tipo: disp.tipo,
    dia_semana: disp.dia_semana !== null ? disp.dia_semana : null,
    fecha_inicio: fechaInicioFormateada,
    fecha_fin: fechaFinFormateada,
    hora_inicio: horaInicioFormateada,
    hora_fin: horaFinFormateada,
    motivo: disp.motivo || null
  }

  console.log('Formulario después de asignar:', formulario.value)
  console.log('Tipo de horario seleccionado:', tipoHorarioSeleccionado.value)

  errores.value = []
  mostrarModalEditar.value = true
  console.log('Modal de editar abierto:', mostrarModalEditar.value)
}

const confirmarEliminar = (disp: DisponibilidadMedico) => {
  disponibilidadAEliminar.value = disp
  mostrarModalEliminar.value = true
}

const eliminarDisponibilidad = async () => {
  if (!disponibilidadAEliminar.value) return

  try {
    eliminando.value = true

    const response = await disponibilidadMedicoService.eliminarDisponibilidad(
      disponibilidadAEliminar.value.id_disp
    )

    if (response.success) {
      showSuccess('Disponibilidad eliminada exitosamente')
      mostrarModalEliminar.value = false
      disponibilidadAEliminar.value = null
      await cargarDisponibilidad()
    } else {
      showError(response.message || 'Error al eliminar disponibilidad')
    }
  } catch (error: any) {
    console.error('Error al eliminar disponibilidad:', error)
    showError(error.message || 'Error al eliminar disponibilidad')
  } finally {
    eliminando.value = false
  }
}

const abrirModalCrear = () => {
  // Resetear formulario
  formulario.value = {
    tipo: 'horario',
    dia_semana: null,
    fecha_inicio: null,
    fecha_fin: null,
    hora_inicio: null,
    hora_fin: null,
    motivo: null
  }

  // Si es médico de cabecera y hay horarios predefinidos, pre-cargar los horarios
  if (esCabecera.value && horariosPredefinidos.value) {
    formulario.value.hora_inicio = horariosPredefinidos.value.hora_inicio
    formulario.value.hora_fin = horariosPredefinidos.value.hora_fin
  }

  errores.value = []
  tipoHorarioSeleccionado.value = 'semanal'
  mostrarModalCrear.value = true
}

const cerrarModal = () => {
  mostrarModalCrear.value = false
  mostrarModalEditar.value = false
  errores.value = []

  // Resetear tipo de horario
  tipoHorarioSeleccionado.value = 'semanal'

  // Resetear formulario
  formulario.value = {
    tipo: 'horario',
    dia_semana: null,
    fecha_inicio: null,
    fecha_fin: null,
    hora_inicio: null,
    hora_fin: null,
    motivo: null
  }
}

const onTipoHorarioChange = () => {
  // Limpiar campos según el tipo seleccionado
  if (tipoHorarioSeleccionado.value === 'semanal') {
    // Para horario semanal: limpiar fechas, mantener día de semana
    formulario.value.fecha_inicio = null
    formulario.value.fecha_fin = null
  } else if (tipoHorarioSeleccionado.value === 'fecha_unica') {
    // Para fecha única: limpiar día de semana y fecha fin
    formulario.value.dia_semana = null
    formulario.value.fecha_fin = null
  } else {
    // Para horario específico: limpiar día de semana, mantener fechas
    formulario.value.dia_semana = null
  }
}

const onDiaSemanaChange = () => {
  // Si se selecciona un día de la semana, limpiar las fechas
  if (formulario.value.dia_semana) {
    formulario.value.fecha_inicio = null
    formulario.value.fecha_fin = null
  }
}

const onFechaUnicaChange = () => {
  // Para fecha única, fecha_fin debe ser igual a fecha_inicio
  if (formulario.value.fecha_inicio) {
    formulario.value.fecha_fin = formulario.value.fecha_inicio
    formulario.value.dia_semana = null
  }
}

const onFechaInicioChange = () => {
  // Si se especifica fecha de inicio, limpiar el día de la semana
  if (formulario.value.fecha_inicio) {
    formulario.value.dia_semana = null
    // Para rangos, no establecer automáticamente fecha_fin
  }
}

// Método para filtrar disponibilidades por día de la semana
const getDisponibilidadesPorDia = (diaSemana: number) => {
  return disponibilidades.value.filter(disp =>
    disp.dia_semana === diaSemana && disp.hora_inicio && disp.hora_fin
  )
}

// Método para formatear fechas
const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  cargarDisponibilidad()
})

// Función para manejar cambio de mes en el calendario
const onMesCambio = (fecha: Date) => {
  console.log('Mes cambió a:', fecha.toLocaleDateString())
  // Opcional: cargar disponibilidades específicas para el mes
  // Si queremos filtrar por rango de fechas del mes
  // const primerDia = new Date(fecha.getFullYear(), fecha.getMonth(), 1)
  // const ultimoDia = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0)
  // cargarDisponibilidadPorRango(primerDia, ultimoDia)
}

const onEditarClick = (disp: DisponibilidadMedico) => {
  console.log('Botón editar clickeado para disponibilidad:', disp.id_disp)
  editarDisponibilidad(disp)
}

// Watchers para debug
watch(mostrarModalEditar, (nuevoValor) => {
  console.log('mostrarModalEditar cambió a:', nuevoValor)
})

watch(mostrarModalCrear, (nuevoValor) => {
  console.log('mostrarModalCrear cambió a:', nuevoValor)
})
</script>

<style scoped>
.disponibilidad-medico {
  max-width: 80rem;
  margin: 0 auto;
  padding: 1.5rem 1rem;
}

@media (min-width: 640px) {
  .disponibilidad-medico {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .disponibilidad-medico {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}
</style>
