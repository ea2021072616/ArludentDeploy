<template>
  <Modal :show="show" title="Cancelar Cita" @close="cerrar">
    <form @submit.prevent="cancelar" class="space-y-4">
      <div class="flex items-start space-x-3 p-4 bg-red-50 rounded-lg">
        <AlertCircle class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-red-900">¿Estás seguro de cancelar esta cita?</p>
          <p class="text-sm text-red-700 mt-1">
            Esta acción notificará al médico y liberará el horario.
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
          <span class="text-gray-900">
            {{ isMedicoView ? `${paciente?.nombres} ${paciente?.apellidos}` : `Dr. ${medico?.nombres} ${medico?.apellidos}` }}
          </span>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Motivo de cancelación (opcional)
        </label>
        <textarea
          v-model="form.motivo_cancelacion"
          rows="3"
          maxlength="500"
          placeholder="Explica por qué necesitas cancelar la cita..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          {{ form.motivo_cancelacion?.length || 0 }}/500 caracteres
        </p>
      </div>
    </form>

    <template #footer>
      <Button variant="secondary" @click="cerrar" :disabled="cargando">
        No cancelar
      </Button>
      <Button variant="danger" @click="cancelar" :loading="cargando">
        Sí, cancelar cita
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { AlertCircle, Calendar, Clock, User } from 'lucide-vue-next'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'
import type { Cita } from '@/api/citasPacienteService'
import type { CitaMedico } from '@/api/citasMedicoService'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'

interface Props {
  show: boolean
  cita: Cita | CitaMedico | null
  isMedicoView?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isMedicoView: false
})
const emit = defineEmits(['close', 'cancelar'])

const cargando = ref(false)

const form = ref({
  motivo_cancelacion: ''
})

// Computed properties para acceder a los datos según el tipo de cita
const medico = computed(() => {
  if (props.isMedicoView) return null
  return (props.cita as Cita)?.medico
})

const paciente = computed(() => {
  if (!props.isMedicoView) return null
  return (props.cita as CitaMedico)?.paciente
})

watch(() => props.show, (newVal) => {
  if (newVal) {
    form.value.motivo_cancelacion = ''
  }
})

const formatearFecha = (fecha: string) => {
  return format(new Date(fecha), "d 'de' MMMM 'de' yyyy", { locale: es })
}

const formatearHora = (fecha: string) => {
  return format(new Date(fecha), 'HH:mm', { locale: es })
}

const cerrar = () => {
  emit('close')
}

const cancelar = async () => {
  cargando.value = true
  try {
    emit('cancelar', {
      motivo_cancelacion: form.value.motivo_cancelacion || undefined
    })
  } finally {
    cargando.value = false
  }
}
</script>
