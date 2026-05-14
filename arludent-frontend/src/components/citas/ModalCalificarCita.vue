<template>
  <Modal :show="show" title="Calificar Cita" @close="cerrar">
    <form @submit.prevent="calificar" class="space-y-4">
      <div class="p-4 bg-yellow-50 rounded-lg flex items-start space-x-3">
        <Star class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
        <div class="text-sm">
          <p class="font-medium text-yellow-900">¿Cómo fue tu experiencia?</p>
          <p class="text-yellow-700 mt-1">
            Tu calificación ayuda a mejorar la calidad del servicio.
          </p>
        </div>
      </div>

      <div v-if="cita" class="p-4 bg-gray-50 rounded-lg space-y-2">
        <div class="flex items-center space-x-2 text-sm">
          <Calendar class="w-4 h-4 text-gray-500" />
          <span class="text-gray-900 font-medium">{{ formatearFecha(cita.fecha_hora_inicio) }}</span>
        </div>
        <div class="flex items-center space-x-2 text-sm">
          <User class="w-4 h-4 text-gray-500" />
          <span class="text-gray-900">Dr. {{ cita.medico.nombres }} {{ cita.medico.apellidos }}</span>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Puntuación *
        </label>
        <div class="flex items-center justify-center space-x-2 py-4">
          <button
            v-for="estrella in 5"
            :key="estrella"
            type="button"
            @click="form.puntuacion = estrella"
            class="transition-all hover:scale-110"
          >
            <Star
              :class="[
                'w-10 h-10 transition-colors',
                estrella <= form.puntuacion
                  ? 'fill-yellow-400 text-yellow-400'
                  : 'text-gray-300 hover:text-yellow-300'
              ]"
            />
          </button>
        </div>
        <p class="text-center text-sm text-gray-600">
          {{ textoPuntuacion }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Comentario (opcional)
        </label>
        <textarea
          v-model="form.comentario"
          rows="4"
          maxlength="1000"
          placeholder="Cuéntanos sobre tu experiencia con el doctor..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          {{ form.comentario?.length || 0 }}/1000 caracteres
        </p>
      </div>

      <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>
    </form>

    <template #footer>
      <Button variant="secondary" @click="cerrar" :disabled="cargando">
        Cancelar
      </Button>
      <Button @click="calificar" :loading="cargando" :disabled="form.puntuacion === 0">
        Enviar Calificación
      </Button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Star, Calendar, User } from 'lucide-vue-next'
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
const emit = defineEmits(['close', 'calificar'])

const cargando = ref(false)
const error = ref('')

const form = ref({
  puntuacion: 0,
  comentario: ''
})

const textoPuntuacion = computed(() => {
  const textos = [
    'Selecciona una puntuación',
    'Muy malo',
    'Malo',
    'Regular',
    'Bueno',
    'Excelente'
  ]
  return textos[form.value.puntuacion] || textos[0]
})

watch(() => props.show, (newVal) => {
  if (newVal) {
    form.value = {
      puntuacion: 0,
      comentario: ''
    }
    error.value = ''
  }
})

const formatearFecha = (fecha: string) => {
  return format(new Date(fecha), "d 'de' MMMM 'de' yyyy", { locale: es })
}

const cerrar = () => {
  emit('close')
}

const calificar = async () => {
  error.value = ''

  if (form.value.puntuacion === 0) {
    error.value = 'Debes seleccionar una puntuación.'
    return
  }

  cargando.value = true
  try {
    emit('calificar', {
      puntuacion: form.value.puntuacion,
      comentario: form.value.comentario || undefined
    })
  } finally {
    cargando.value = false
  }
}
</script>
