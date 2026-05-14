<template>
  <Modal
    :show="show"
    title="Nuevo Control / Evolución"
    size="2xl"
    @close="cerrar"
  >
    <div class="space-y-4">
      <div class="flex items-start space-x-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <Activity class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-blue-900">Control de Evolución del Paciente</p>
          <p class="text-sm text-blue-700 mt-1">
            Registre la evolución del paciente, observaciones y seguimiento del tratamiento.
          </p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Control *</label>
        <input
          v-model="form.fecha"
          type="date"
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Estado / Diagnóstico Actual</label>
        <textarea
          v-model="form.diagnostico"
          rows="3"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Describa el estado actual del paciente y diagnóstico de evolución"
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Procedimiento / Intervención Realizada</label>
        <textarea
          v-model="form.procedimiento_realizado"
          rows="3"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Describa los procedimientos o intervenciones realizadas en este control"
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones y Notas de Evolución *</label>
        <textarea
          v-model="form.notas_medicas"
          rows="5"
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Registre las observaciones importantes sobre la evolución del paciente, respuesta al tratamiento, síntomas actuales, etc."
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          {{ form.notas_medicas?.length || 0 }} caracteres
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
        :disabled="!form.fecha || !form.notas_medicas?.trim()"
      >
        {{ guardando ? 'Guardando...' : 'Guardar Control' }}
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Activity } from 'lucide-vue-next'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'

interface Props {
  show: boolean
  idHistorial: number | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'guardado'])

const guardando = ref(false)
const error = ref('')

const form = ref({
  fecha: new Date().toISOString().split('T')[0],
  diagnostico: '',
  procedimiento_realizado: '',
  notas_medicas: ''
})

const resetForm = () => {
  form.value = {
    fecha: new Date().toISOString().split('T')[0],
    diagnostico: '',
    procedimiento_realizado: '',
    notas_medicas: ''
  }
  error.value = ''
}

const cerrar = () => {
  resetForm()
  emit('close')
}

const guardar = async () => {
  error.value = ''

  if (!form.value.fecha) {
    error.value = 'Debe seleccionar una fecha.'
    return
  }

  if (!form.value.notas_medicas?.trim()) {
    error.value = 'Debe ingresar las observaciones de evolución.'
    return
  }

  emit('guardado', {
    fecha: form.value.fecha,
    diagnostico: form.value.diagnostico?.trim() || null,
    procedimiento_realizado: form.value.procedimiento_realizado?.trim() || null,
    notas_medicas: form.value.notas_medicas.trim()
  })
}

// Reset form cuando se abre el modal
watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm()
  }
})
</script>
