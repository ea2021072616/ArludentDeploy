<template>
  <Modal
    :show="show"
    title="Registrar Contacto con Paciente"
    size="2xl"
    @close="cerrar"
  >
    <div class="space-y-5">
      <!-- Info del paciente -->
      <div v-if="seguimiento" class="bg-gradient-to-r from-blue-50 to-cyan-50 p-4 rounded-lg border border-blue-200">
        <div class="flex items-center space-x-3">
          <div class="bg-blue-600 p-2 rounded-lg">
            <ClipboardCheck class="w-5 h-5 text-white" />
          </div>
          <div>
            <h4 class="font-semibold text-gray-900">
              {{ seguimiento.paciente?.nombres }} {{ seguimiento.paciente?.apellidos }}
            </h4>
            <p class="text-sm text-gray-600">
              {{ tipoSeguimientoTexto }} - {{ seguimiento.fecha_seguimiento }}
            </p>
          </div>
        </div>
      </div>

      <!-- ¿Cómo se encuentra el paciente? -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">
          ¿Cómo se encuentra el paciente? *
        </label>
        <div class="grid grid-cols-2 gap-3">
          <button
            v-for="opcion in opcionesEstado"
            :key="opcion.value"
            @click="form.tiene_problema = opcion.problema"
            :class="[
              'p-4 rounded-lg border-2 transition-all',
              form.tiene_problema === opcion.problema
                ? 'border-purple-600 bg-purple-50'
                : 'border-gray-300 hover:border-purple-300'
            ]"
          >
            <div class="text-2xl mb-2">{{ opcion.emoji }}</div>
            <div class="font-medium text-sm">{{ opcion.label }}</div>
          </button>
        </div>
      </div>

      <!-- Respuesta del paciente -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Respuesta del Paciente *
        </label>
        <textarea
          v-model="form.respuesta_paciente"
          rows="4"
          required
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
          placeholder="Describe lo que el paciente comentó sobre su estado..."
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          {{ form.respuesta_paciente?.length || 0 }} caracteres
        </p>
      </div>

      <!-- Si tiene problema -->
      <div v-if="form.tiene_problema" class="space-y-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h4 class="font-semibold text-yellow-900 flex items-center">
          <AlertTriangle class="w-5 h-5 mr-2" />
          Detalles del Problema
        </h4>

        <!-- Descripción del problema -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Descripción del Problema *
          </label>
          <textarea
            v-model="form.descripcion_problema"
            rows="3"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
            placeholder="Describe el problema reportado..."
          ></textarea>
        </div>

        <!-- Síntomas -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Síntomas Reportados
          </label>
          <input
            v-model="form.sintomas"
            type="text"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
            placeholder="Ej: dolor intenso, sangrado, inflamación..."
          />
        </div>

        <!-- ¿Requiere cita urgente? -->
        <div class="flex items-center space-x-3 p-3 bg-white rounded-lg border border-yellow-300">
          <input
            v-model="form.requiere_cita_urgente"
            type="checkbox"
            class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500"
          />
          <label class="font-medium text-gray-900 cursor-pointer">
            Requiere cita urgente
          </label>
        </div>
      </div>

      <!-- Notas adicionales -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Notas Adicionales
        </label>
        <textarea
          v-model="form.notas_secretaria"
          rows="2"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
          placeholder="Notas internas sobre el contacto..."
        ></textarea>
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
        {{ guardando ? 'Guardando...' : 'Registrar Contacto' }}
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { ClipboardCheck, AlertTriangle } from 'lucide-vue-next'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'

interface Props {
  show: boolean
  seguimiento: any | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'guardado'])

const guardando = ref(false)
const error = ref('')

const form = ref({
  respuesta_paciente: '',
  tiene_problema: false,
  descripcion_problema: '',
  sintomas: '',
  requiere_cita_urgente: false,
  notas_secretaria: ''
})

const opcionesEstado = [
  { value: 'bien', label: 'Muy bien', emoji: '😊', problema: false },
  { value: 'normal', label: 'Normal', emoji: '🙂', problema: false },
  { value: 'regular', label: 'Regular', emoji: '😐', problema: true },
  { value: 'mal', label: 'Con problemas', emoji: '😰', problema: true }
]

const tipoSeguimientoTexto = computed(() => {
  if (!props.seguimiento) return ''
  const tipos: Record<string, string> = {
    postoperatorio: 'Postoperatorio',
    revision: 'Revisión',
    medicacion: 'Medicación',
    general: 'General'
  }
  return tipos[props.seguimiento.tipo_seguimiento] || props.seguimiento.tipo_seguimiento
})

const formValido = computed(() => {
  if (!form.value.respuesta_paciente?.trim()) return false
  if (form.value.tiene_problema && !form.value.descripcion_problema?.trim()) return false
  return true
})

const resetForm = () => {
  form.value = {
    respuesta_paciente: '',
    tiene_problema: false,
    descripcion_problema: '',
    sintomas: '',
    requiere_cita_urgente: false,
    notas_secretaria: ''
  }
  error.value = ''
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
    respuesta_paciente: form.value.respuesta_paciente.trim(),
    tiene_problema: form.value.tiene_problema,
    descripcion_problema: form.value.descripcion_problema?.trim() || null,
    sintomas: form.value.sintomas?.trim() || null,
    requiere_cita_urgente: form.value.requiere_cita_urgente,
    notas_secretaria: form.value.notas_secretaria?.trim() || null
  }

  emit('guardado', datos)
}

// Reset form cuando se abre el modal
watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm()
  }
})
</script>
