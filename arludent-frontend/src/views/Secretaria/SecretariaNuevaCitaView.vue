<template>
  <DashboardLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Nueva Cita</h1>
          <p class="text-gray-600 mt-1">Agenda una nueva cita médica</p>
        </div>
        <Button variant="outline" @click="router.back()">
          <ArrowLeft class="w-5 h-5" />
          <span>Volver</span>
        </Button>
      </div>

      <!-- Formulario -->
      <Card>
        <form @submit.prevent="guardarCita" class="space-y-6">
          <!-- Seleccionar Paciente -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Paciente <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="searchPaciente"
                type="text"
                placeholder="Escribe nombre, apellido o DNI..."
                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                @input="buscarPacientes"
                @focus="mostrarResultadosPacientes = true"
                :disabled="!!form.id_paciente"
                autocomplete="off"
              />

              <!-- Indicador de carga -->
              <div v-if="buscandoPacientes" class="absolute right-3 top-3">
                <div class="animate-spin rounded-full h-5 w-5 border-2 border-primary-200 border-t-primary-600"></div>
              </div>

              <!-- Ícono de búsqueda -->
              <Search v-else class="w-5 h-5 text-gray-400 absolute right-3 top-3 pointer-events-none" />

              <!-- Lista de pacientes (estilo GitHub) -->
              <div
                v-if="pacientes.length > 0 && searchPaciente && mostrarResultadosPacientes && !form.id_paciente"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto"
              >
                <div class="p-2 bg-gray-50 border-b border-gray-200 text-xs text-gray-600 flex items-center justify-between">
                  <span>{{ pacientes.length }} paciente(s) encontrado(s)</span>
                  <button
                    type="button"
                    @click="mostrarResultadosPacientes = false"
                    class="text-gray-400 hover:text-gray-600"
                  >
                    <X class="w-4 h-4" />
                  </button>
                </div>
                <button
                  v-for="(paciente, index) in pacientes"
                  :key="paciente.id_paciente"
                  type="button"
                  @click="seleccionarPaciente(paciente)"
                  class="w-full px-4 py-3 text-left hover:bg-primary-50 border-b border-gray-100 last:border-b-0 transition-colors focus:outline-none focus:bg-primary-50"
                  :class="{ 'bg-primary-50': index === 0 }"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex-1">
                      <div class="font-medium text-gray-900">{{ paciente.nombres }} {{ paciente.apellidos }}</div>
                      <div class="text-sm text-gray-600 flex items-center gap-3 mt-1">
                        <span class="flex items-center gap-1">
                          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                          </svg>
                          DNI: {{ paciente.dni }}
                        </span>
                        <span v-if="paciente.edad" class="flex items-center gap-1">
                          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                          </svg>
                          {{ paciente.edad }} años
                        </span>
                      </div>
                    </div>
                    <div class="ml-3 text-gray-400">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                      </svg>
                    </div>
                  </div>
                </button>
              </div>

              <!-- Sin resultados -->
              <div
                v-if="searchPaciente.length >= 2 && pacientes.length === 0 && !buscandoPacientes && mostrarResultadosPacientes && !form.id_paciente"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center"
              >
                <p class="text-sm text-gray-600">No se encontraron pacientes</p>
                <p class="text-xs text-gray-500 mt-1">Intenta con otro término de búsqueda</p>
              </div>

              <!-- Paciente seleccionado -->
              <div v-if="form.id_paciente" class="mt-3 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <User class="w-5 h-5 text-green-600" />
                  <div>
                    <p class="font-medium text-green-900">{{ pacienteSeleccionado?.nombres }} {{ pacienteSeleccionado?.apellidos }}</p>
                    <p class="text-sm text-green-700">DNI: {{ pacienteSeleccionado?.dni }}</p>
                  </div>
                </div>
                <button type="button" @click="limpiarPaciente" class="text-green-600 hover:text-green-800">
                  <X class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Seleccionar Médico -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Médico <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.id_medico"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="">Seleccione un médico</option>
              <option v-for="medico in medicos" :key="medico.id_medico" :value="medico.id_medico">
                Dr(a). {{ medico.nombres }} {{ medico.apellidos }} - {{ medico.especialidad }}
              </option>
            </select>
          </div>

          <!-- Fecha y Hora -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Fecha <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.fecha"
                type="date"
                required
                :min="fechaMinima"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Hora <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.hora"
                type="time"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
          </div>

          <!-- Duración -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Duración (minutos)
            </label>
            <select
              v-model="form.duracion"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option :value="15">15 minutos</option>
              <option :value="30">30 minutos</option>
              <option :value="45">45 minutos</option>
              <option :value="60">1 hora</option>
              <option :value="90">1 hora 30 minutos</option>
              <option :value="120">2 horas</option>
            </select>
          </div>

          <!-- Motivo -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Motivo de la consulta
            </label>
            <textarea
              v-model="form.motivo"
              rows="3"
              placeholder="Describe el motivo de la cita..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
            ></textarea>
          </div>

          <!-- Notas -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Notas adicionales
            </label>
            <textarea
              v-model="form.notas"
              rows="2"
              placeholder="Notas internas (opcional)..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
            ></textarea>
          </div>

          <!-- Botones -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
            <Button variant="outline" type="button" @click="router.back()">
              Cancelar
            </Button>
            <Button variant="primary" type="submit" :disabled="guardando">
              <CheckCircle class="w-5 h-5" />
              <span>{{ guardando ? 'Guardando...' : 'Crear Cita' }}</span>
            </Button>
          </div>
        </form>
      </Card>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { useNotification } from '@/composables/useNotification'
import secretariaCitasService from '@/api/secretariaCitasService'
import pacienteSecretariaService from '@/api/pacienteSecretariaService'
import { ArrowLeft, User, Search, X, CheckCircle } from 'lucide-vue-next'

const router = useRouter()
const { showSuccess, showError } = useNotification()

// Estado
const medicos = ref<any[]>([])
const pacientes = ref<any[]>([])
const searchPaciente = ref('')
const pacienteSeleccionado = ref<any>(null)
const guardando = ref(false)
const buscandoPacientes = ref(false)
const mostrarResultadosPacientes = ref(false)

const form = ref({
  id_paciente: null as number | null,
  id_medico: '',
  fecha: '',
  hora: '',
  duracion: 30,
  motivo: '',
  notas: ''
})

// Fecha mínima (hoy)
const fechaMinima = computed(() => {
  return new Date().toISOString().split('T')[0]
})

// Cargar médicos
const cargarMedicos = async () => {
  try {
    console.log('🔄 Cargando médicos...')
    const response = await secretariaCitasService.obtenerMedicos()
    console.log('✅ Respuesta médicos:', response)
    medicos.value = response.medicos || []
    console.log('📋 Médicos cargados:', medicos.value.length)
  } catch (error: any) {
    console.error('❌ Error al cargar médicos:', error)
    console.error('Detalles:', error.response?.data)
    showError(error.response?.data?.message || 'Error al cargar la lista de médicos')
  }
}

// Buscar pacientes
let timeoutBusqueda: any = null
const buscarPacientes = () => {
  clearTimeout(timeoutBusqueda)

  if (searchPaciente.value.length < 2) {
    pacientes.value = []
    mostrarResultadosPacientes.value = false
    return
  }

  buscandoPacientes.value = true
  mostrarResultadosPacientes.value = true

  // limpiar resultados previos inmediatamente para evitar mostrar pacientes obsoletos
  pacientes.value = []

  timeoutBusqueda = setTimeout(async () => {
    try {
      // Trim del término de búsqueda antes de enviarlo al backend
      const termino = searchPaciente.value.trim()
      if (termino.length < 2) {
        pacientes.value = []
        buscandoPacientes.value = false
        mostrarResultadosPacientes.value = false
        return
      }

      // Si el término es totalmente numérico, priorizar búsqueda rápida / exacta por documento
      const soloNumeros = /^\d+$/.test(termino)
      let response
      if (soloNumeros) {
        // Usar endpoint de búsqueda rápida (autocompletado) que suele priorizar por documento
        response = await pacienteSecretariaService.busquedaRapida(termino)
        // Normalizar si viene envuelto
        response = response.data ?? response

        // Si vienen resultados, priorizar los que coincidan exactamente con el número de documento
        const exactos = (response.pacientes || []).filter((p: any) => (p.numero_documento || p.dni || '').toString() === termino)
        if (exactos.length > 0) {
          pacientes.value = exactos
        } else {
          pacientes.value = response.pacientes || []
        }
      } else {
        response = await secretariaCitasService.obtenerPacientes(termino)
        // Filtrado cliente por nombre/apellidos para priorizar coincidencias relevantes
        const termLower = termino.toLowerCase()
        const lista = response.pacientes || []
        const filtrados = lista.filter((p: any) => {
          const nombreCompleto = ((p.nombres || p.nombre || '') + ' ' + (p.apellidos || '')).toLowerCase()
          const doc = (p.numero_documento || p.dni || '').toString().toLowerCase()
          return nombreCompleto.includes(termLower) || doc.includes(termLower)
        })

        pacientes.value = filtrados.length > 0 ? filtrados : lista
      }
    } catch (error) {
      console.error('Error al buscar pacientes:', error)
      pacientes.value = []
    } finally {
      buscandoPacientes.value = false
    }
  }, 300)
}

// Seleccionar paciente
const seleccionarPaciente = (paciente: any) => {
  form.value.id_paciente = paciente.id_paciente
  pacienteSeleccionado.value = paciente
  searchPaciente.value = ''
  pacientes.value = []
  mostrarResultadosPacientes.value = false
}

// Limpiar paciente seleccionado
const limpiarPaciente = () => {
  form.value.id_paciente = null
  pacienteSeleccionado.value = null
  searchPaciente.value = ''
  mostrarResultadosPacientes.value = false
}

// Guardar cita
const guardarCita = async () => {
  if (!form.value.id_paciente) {
    showError('Por favor selecciona un paciente')
    return
  }

  if (!form.value.id_medico) {
    showError('Por favor selecciona un médico')
    return
  }

  if (!form.value.fecha || !form.value.hora) {
    showError('Por favor ingresa la fecha y hora de la cita')
    return
  }

  try {
    guardando.value = true

    // Combinar fecha y hora
    const fechaHoraInicio = `${form.value.fecha} ${form.value.hora}:00`

    const datos = {
      id_paciente: form.value.id_paciente!,
      id_medico: Number(form.value.id_medico),
      fecha_hora_inicio: fechaHoraInicio,
      duracion: form.value.duracion,
      motivo: form.value.motivo || undefined,
      notas: form.value.notas || undefined
    }

    console.log('Creando cita:', datos)

    await secretariaCitasService.crearCita(datos)

    showSuccess('Cita creada exitosamente')
    router.push('/secretaria/agenda')
  } catch (error: any) {
    console.error('Error al crear cita:', error)
    showError(error.response?.data?.message || 'Error al crear la cita')
  } finally {
    guardando.value = false
  }
}

// Lifecycle
onMounted(() => {
  cargarMedicos()
})
</script>
