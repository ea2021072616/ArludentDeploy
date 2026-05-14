<template>
  <Modal
    :show="show"
    title="Nuevo Seguimiento Post-Tratamiento"
    size="2xl"
    @close="cerrar"
  >
    <div class="space-y-5">
      <!-- Banner informativo -->
      <div class="flex items-start space-x-3 p-4 bg-purple-50 rounded-lg border border-purple-200">
        <ClipboardList class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-purple-900">Crear Seguimiento Automático</p>
          <p class="text-sm text-purple-700 mt-1">
            El sistema enviará automáticamente un email al paciente en la fecha programada.
          </p>
        </div>
      </div>

      <!-- Paciente con Buscador -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Paciente *
        </label>
        <div class="relative">
          <input
            v-model="busquedaPaciente"
            type="text"
            placeholder="Buscar paciente por nombre, apellido o DNI..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            @input="filtrarPacientes"
            @focus="mostrarDropdown = true"
          />

          <!-- Dropdown de resultados -->
          <div
            v-if="mostrarDropdown && pacientesFiltrados.length > 0"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
          >
            <div
              v-for="pac in pacientesFiltrados"
              :key="pac.id_paciente"
              @click="seleccionarPaciente(pac)"
              class="px-4 py-3 hover:bg-purple-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-gray-900">
                    {{ pac.nombres }} {{ pac.apellidos }}
                  </p>
                  <p class="text-sm text-gray-500">DNI: {{ pac.dni || pac.numero_documento }}</p>
                </div>
                <div class="text-xs text-gray-400">
                  {{ pac.email || pac.correo || 'Sin email' }}
                </div>
              </div>
            </div>
          </div>

          <!-- Paciente seleccionado -->
          <div
            v-if="pacienteSeleccionado"
            class="mt-2 p-3 bg-purple-50 border border-purple-200 rounded-lg flex items-center justify-between"
          >
            <div>
              <p class="font-medium text-purple-900">
                {{ pacienteSeleccionado.nombres }} {{ pacienteSeleccionado.apellidos }}
              </p>
              <p class="text-sm text-purple-700">
                DNI: {{ pacienteSeleccionado.dni || pacienteSeleccionado.numero_documento }}
              </p>
            </div>
            <button
              @click="limpiarSeleccion"
              class="text-purple-600 hover:text-purple-800 text-sm font-medium"
            >
              Cambiar
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tipo de seguimiento -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tipo de Seguimiento *
          </label>
          <select
            v-model="form.tipo_seguimiento"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
          >
            <option value="postoperatorio">Postoperatorio</option>
            <option value="revision">Revisión</option>
            <option value="medicacion">Medicación</option>
            <option value="general">General</option>
          </select>
        </div>

        <!-- Prioridad -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Prioridad *
          </label>
          <select
            v-model="form.prioridad"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
          >
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
            <option value="urgente">Urgente</option>
          </select>
        </div>
      </div>

      <!-- Fecha de seguimiento -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Fecha de Seguimiento *
        </label>
        <input
          v-model="form.fecha_seguimiento"
          type="date"
          :min="minDate"
          required
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
        />
        <p class="text-xs text-gray-500 mt-1">
          Fecha en la que se realizará el seguimiento
        </p>
      </div>

      <!-- Método de contacto -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Método de Contacto
        </label>
        <div class="flex items-center space-x-2">
          <select
            v-model="form.metodo_contacto"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
          >
            <option value="">Automático (el sistema decide)</option>
            <option value="email">Email</option>
            <option value="portal">Portal del Paciente</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="llamada">Llamada Telefónica</option>
            <option value="presencial">Presencial</option>
          </select>
          <span v-if="metodoSugerido" class="text-xs text-gray-500 whitespace-nowrap">
            Sugerido: {{ metodoSugerido }}
          </span>
        </div>
      </div>

      <!-- Notas -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Notas Internas
        </label>
        <textarea
          v-model="form.notas_secretaria"
          rows="3"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
          placeholder="Notas o indicaciones para el seguimiento..."
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          {{ form.notas_secretaria?.length || 0 }} caracteres
        </p>
      </div>

      <!-- Error -->
      <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>
    </div>

    <template #footer>
      <Button
        variant="secondary"
        @click="cerrar"
        :disabled="guardando"
      >
        Cancelar
      </Button>
      <Button
        variant="primary"
        @click="guardar"
        :loading="guardando"
        :disabled="!formValido"
      >
        {{ guardando ? 'Creando...' : 'Crear Seguimiento' }}
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { ClipboardList } from 'lucide-vue-next'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'

interface Props {
  show: boolean
  pacientes: any[]
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'guardado'])

const guardando = ref(false)
const error = ref('')
const metodoSugerido = ref('')
const busquedaPaciente = ref('')
const mostrarDropdown = ref(false)
const pacienteSeleccionado = ref<any>(null)
const pacientesFiltrados = ref<any[]>([])

const form = ref({
  id_paciente: '',
  tipo_seguimiento: 'postoperatorio',
  prioridad: 'media',
  fecha_seguimiento: '',
  metodo_contacto: '',
  notas_secretaria: ''
})

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const formValido = computed(() => {
  return form.value.id_paciente &&
         form.value.tipo_seguimiento &&
         form.value.fecha_seguimiento &&
         form.value.prioridad
})

const filtrarPacientes = () => {
  const termino = busquedaPaciente.value.toLowerCase().trim()

  if (!termino) {
    pacientesFiltrados.value = props.pacientes || []
  } else {
    pacientesFiltrados.value = (props.pacientes || []).filter(p => {
      const nombreCompleto = `${p.nombres} ${p.apellidos}`.toLowerCase()
      const dni = (p.dni || p.numero_documento || '').toLowerCase()
      const email = (p.email || p.correo || '').toLowerCase()

      return nombreCompleto.includes(termino) ||
             dni.includes(termino) ||
             email.includes(termino)
    })
  }

  mostrarDropdown.value = true
}

const seleccionarPaciente = (paciente: any) => {
  pacienteSeleccionado.value = paciente
  form.value.id_paciente = paciente.id_paciente
  busquedaPaciente.value = `${paciente.nombres} ${paciente.apellidos}`
  mostrarDropdown.value = false
  cargarDatosPaciente()
}

const limpiarSeleccion = () => {
  pacienteSeleccionado.value = null
  form.value.id_paciente = ''
  busquedaPaciente.value = ''
  metodoSugerido.value = ''
  mostrarDropdown.value = false
}

const cargarDatosPaciente = () => {
  if (!props.pacientes || !Array.isArray(props.pacientes)) {
    console.warn('La lista de pacientes no está disponible')
    return
  }

  const paciente = props.pacientes.find(p => p.id_paciente === form.value.id_paciente)
  if (paciente) {
    // Sugerir método de contacto
    if (paciente.correo || paciente.email) {
      metodoSugerido.value = 'Email'
    } else if (paciente.telefono) {
      metodoSugerido.value = 'WhatsApp/Llamada'
    } else {
      metodoSugerido.value = 'Portal'
    }
  }
}

const resetForm = () => {
  form.value = {
    id_paciente: '',
    tipo_seguimiento: 'postoperatorio',
    prioridad: 'media',
    fecha_seguimiento: '',
    metodo_contacto: '',
    notas_secretaria: ''
  }
  error.value = ''
  metodoSugerido.value = ''
  busquedaPaciente.value = ''
  pacienteSeleccionado.value = null
  mostrarDropdown.value = false
  pacientesFiltrados.value = []
}

const cerrar = () => {
  resetForm()
  emit('close')
}

const guardar = async () => {
  error.value = ''

  if (!formValido.value) {
    error.value = 'Por favor complete todos los campos requeridos'
    return
  }

  const datos = {
    id_paciente: parseInt(form.value.id_paciente),
    tipo_seguimiento: form.value.tipo_seguimiento,
    prioridad: form.value.prioridad,
    fecha_seguimiento: form.value.fecha_seguimiento,
    metodo_contacto: form.value.metodo_contacto || null,
    notas_secretaria: form.value.notas_secretaria || null
  }

  emit('guardado', datos)
}

// Reset form cuando se abre el modal
watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm()
    // Cargar todos los pacientes inicialmente
    pacientesFiltrados.value = props.pacientes || []
  }
})

// Cerrar dropdown cuando cambian los pacientes
watch(() => props.pacientes, (newVal) => {
  if (newVal && newVal.length > 0 && !busquedaPaciente.value) {
    pacientesFiltrados.value = newVal
  }
}, { immediate: true })
</script>
