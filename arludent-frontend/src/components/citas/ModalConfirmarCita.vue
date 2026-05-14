<template>
  <Modal :show="show" title="Confirmar Cita" @close="emit('close')">
    <div class="space-y-4">
      <div class="flex items-start space-x-3 p-4 bg-green-50 rounded-lg">
        <CheckCircle class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-green-900">¿Confirmar esta cita?</p>
          <p class="text-sm text-green-700 mt-1">
            Al confirmar, el médico será notificado de tu asistencia.
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
          <span class="text-gray-900">Dr. {{ cita.medico.nombres }} {{ cita.medico.apellidos }}</span>
        </div>
        <div v-if="cita.medico.especialidad" class="flex items-center space-x-2 text-sm">
          <Stethoscope class="w-4 h-4 text-gray-500" />
          <span class="text-gray-600">{{ cita.medico.especialidad }}</span>
        </div>
      </div>
    </div>

    <template #footer>
      <Button variant="secondary" @click="emit('close')" :disabled="cargando">
        Cancelar
      </Button>
      <Button @click="confirmar" :loading="cargando">
        Confirmar Cita
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { CheckCircle, Calendar, Clock, User, Stethoscope } from 'lucide-vue-next'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'
import type { Cita } from '@/api/citasPacienteService'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'

interface Props {
  show: boolean
  cita: Cita | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'confirmar'])

const cargando = ref(false)

const formatearFecha = (fecha: string) => {
  return format(new Date(fecha), "d 'de' MMMM 'de' yyyy", { locale: es })
}

const formatearHora = (fecha: string) => {
  return format(new Date(fecha), 'HH:mm', { locale: es })
}

const confirmar = async () => {
  cargando.value = true
  try {
    emit('confirmar')
  } finally {
    cargando.value = false
  }
}
</script>
