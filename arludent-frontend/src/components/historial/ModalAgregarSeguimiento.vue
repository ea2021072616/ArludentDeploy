<template>
  <Modal
    :show="show"
    title="Nuevo Seguimiento de Tratamiento"
    size="2xl"
    @close="cerrar"
  >
    <div class="space-y-4">
      <div class="flex items-start space-x-3 p-4 bg-green-50 rounded-lg border border-green-200">
        <ClipboardCheck class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-green-900">Control de Seguimiento del Tratamiento</p>
          <p class="text-sm text-green-700 mt-1">
            Registre el progreso y evolución del tratamiento actual.
          </p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Seguimiento *</label>
        <input
          v-model="form.fecha_registro"
          type="date"
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción del Seguimiento *</label>
        <textarea
          v-model="form.descripcion"
          rows="6"
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
          placeholder="Describa el estado actual del tratamiento, progreso, observaciones importantes, síntomas o mejoras reportadas por el paciente..."
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          {{ form.descripcion?.length || 0 }} caracteres
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Duración Restante (días)</label>
        <input
          v-model.number="form.duracion_restante"
          type="number"
          min="0"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
          placeholder="Ej: 15"
        />
        <p class="text-xs text-gray-500 mt-1">
          Número aproximado de días restantes para completar el tratamiento
        </p>
      </div>

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
        :disabled="!form.fecha_registro || !form.descripcion?.trim()"
      >
        {{ guardando ? 'Guardando...' : 'Guardar Seguimiento' }}
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { ClipboardCheck } from 'lucide-vue-next'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'

interface Props {
  show: boolean
  idHistorial: number | null
  idTratamientoHistorial: number | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'guardado'])

const guardando = ref(false)
const error = ref('')

const form = ref({
  fecha_registro: new Date().toISOString().split('T')[0],
  descripcion: '',
  duracion_restante: null as number | null
})

const resetForm = () => {
  form.value = {
    fecha_registro: new Date().toISOString().split('T')[0],
    descripcion: '',
    duracion_restante: null
  }
  error.value = ''
}

const cerrar = () => {
  resetForm()
  emit('close')
}

const guardar = async () => {
  error.value = ''

  if (!form.value.fecha_registro) {
    error.value = 'Debe seleccionar una fecha.'
    return
  }

  if (!form.value.descripcion?.trim()) {
    error.value = 'Debe ingresar la descripción del seguimiento.'
    return
  }

  emit('guardado', {
    fecha_registro: form.value.fecha_registro,
    descripcion: form.value.descripcion.trim(),
    duracion_restante: form.value.duracion_restante
  })
}

// Reset form cuando se abre el modal
watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm()
  }
})
</script>
