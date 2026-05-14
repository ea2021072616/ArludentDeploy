<template>
  <Modal :show="show" title="Reprogramar Cita" @close="cerrar">
    <form @submit.prevent="reprogramar" class="space-y-4">
      <div class="p-4 bg-blue-50 rounded-lg flex items-start space-x-3">
        <Calendar class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
        <div class="text-sm">
          <p class="font-medium text-blue-900">Selecciona nueva fecha y hora</p>
          <p class="text-blue-700 mt-1">
            La cita será reprogramada y el médico será notificado.
          </p>
        </div>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Nueva Fecha
          </label>
          <input
            v-model="form.fecha"
            type="date"
            :min="fechaMinima"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Nueva Hora
          </label>
          <input
            v-model="form.hora"
            type="time"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Motivo de reprogramación (opcional)
          </label>
          <textarea
            v-model="form.motivo_reprogramacion"
            rows="3"
            maxlength="500"
            placeholder="Explica por qué necesitas reprogramar la cita..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">
            {{ form.motivo_reprogramacion?.length || 0 }}/500 caracteres
          </p>
        </div>
      </div>

      <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>
    </form>

    <template #footer>
      <Button variant="secondary" @click="cerrar" :disabled="cargando">
        Cancelar
      </Button>
      <Button @click="reprogramar" :loading="cargando">
        Reprogramar
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Calendar } from 'lucide-vue-next'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'
import type { Cita } from '@/api/citasPacienteService'
import { format } from 'date-fns'

interface Props {
  show: boolean
  cita: Cita | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'reprogramar'])

const cargando = ref(false)
const error = ref('')

const form = ref({
  fecha: '',
  hora: '',
  motivo_reprogramacion: ''
})

const fechaMinima = computed(() => {
  const manana = new Date()
  manana.setDate(manana.getDate() + 1)
  return format(manana, 'yyyy-MM-dd')
})

watch(() => props.show, (newVal) => {
  if (newVal) {
    // Reset form
    form.value = {
      fecha: '',
      hora: '',
      motivo_reprogramacion: ''
    }
    error.value = ''
  }
})

const cerrar = () => {
  emit('close')
}

const reprogramar = async () => {
  error.value = ''

  if (!form.value.fecha || !form.value.hora) {
    error.value = 'Debes seleccionar fecha y hora.'
    return
  }

  const fechaHora = `${form.value.fecha}T${form.value.hora}:00`
  const ahora = new Date()
  const fechaSeleccionada = new Date(fechaHora)

  if (fechaSeleccionada <= ahora) {
    error.value = 'La fecha debe ser futura.'
    return
  }

  cargando.value = true
  try {
    emit('reprogramar', {
      fecha_hora_inicio: fechaHora,
      motivo_reprogramacion: form.value.motivo_reprogramacion || undefined
    })
  } finally {
    cargando.value = false
  }
}
</script>
