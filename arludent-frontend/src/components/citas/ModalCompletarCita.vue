<template>
  <Modal :show="show" title="Completar Cita" @close="emit('close')">
    <form @submit.prevent="completar" class="space-y-4">
      <div class="flex items-start space-x-3 p-4 bg-green-50 rounded-lg">
        <CheckCircle class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-green-900">¿Marcar esta cita como completada?</p>
          <p class="text-sm text-green-700 mt-1">
            Esta acción indica que la consulta médica ha finalizado.
          </p>
        </div>
      </div>

      <div v-if="cita" class="p-4 bg-gray-50 rounded-lg space-y-2">
        <div class="flex items-center space-x-2 text-sm">
          <Calendar class="w-4 h-4 text-gray-500" />
          <span class="text-gray-900 font-medium">{{ formatearFecha(cita.fecha_hora_inicio) }}</span>
        </div>
        <div class="flex items-center space-x-2 text-sm">
          <Clock class="w-4 h-4 text-gray-500" />
          <span class="text-gray-900">{{ formatearHora(cita.fecha_hora_inicio) }}</span>
        </div>
        <div class="flex items-center space-x-2 text-sm">
          <User class="w-4 h-4 text-gray-500" />
          <span class="text-gray-900">Paciente: {{ cita.paciente.nombres }} {{ cita.paciente.apellidos }}</span>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Notas adicionales (opcional)
        </label>
        <textarea
          v-model="form.notas"
          rows="4"
          maxlength="1000"
          placeholder="Agregue observaciones sobre la consulta..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          {{ form.notas?.length || 0 }}/1000 caracteres
        </p>
      </div>

      <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>
    </form>

    <template #footer>
      <Button variant="secondary" @click="emit('close')" :disabled="cargando">
        Cancelar
      </Button>
      <Button @click="completar" :loading="cargando">
        Marcar como Completada
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { CheckCircle, Calendar, Clock, User } from 'lucide-vue-next'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'
import type { CitaMedico } from '@/api/citasMedicoService'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'

interface Props {
  show: boolean
  cita: CitaMedico | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'completar'])

const cargando = ref(false)
const error = ref('')

const form = ref({
  notas: ''
})

const formatearFecha = (fecha: string) => {
  return format(new Date(fecha), "d 'de' MMMM 'de' yyyy", { locale: es })
}

const formatearHora = (fecha: string) => {
  return format(new Date(fecha), 'HH:mm', { locale: es })
}

const completar = async () => {
  error.value = ''

  cargando.value = true
  try {
    emit('completar', {
      notas: form.notas || undefined
    })
  } finally {
    cargando.value = false
  }
}
</script>
