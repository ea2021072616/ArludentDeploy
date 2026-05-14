<template>
  <Modal :show="show" title="Detalles de la Cita" @close="emit('close')" size="lg">
    <div class="space-y-6">
      <!-- Información del Médico/Paciente -->
      <div class="flex items-start space-x-4 p-4 bg-blue-50 rounded-lg">
        <div
          v-if="isMedicoView && medico && medico.foto_url && getFotoUrl(medico.foto_url)"
          class="w-20 h-20 rounded-full overflow-hidden flex-shrink-0"
        >
          <img
            :src="getFotoUrl(medico.foto_url)!"
            :alt="`Dr. ${medico.nombres}`"
            class="w-full h-full object-cover"
          />
        </div>
        <div
          v-else
          class="w-20 h-20 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0"
        >
          <User class="w-10 h-10 text-primary-600" />
        </div>
        <div class="flex-1">
          <h3 class="text-xl font-semibold text-gray-900">
            {{ isMedicoView ? `Paciente: ${paciente?.nombres} ${paciente?.apellidos}` : `Dr. ${medico?.nombres} ${medico?.apellidos}` }}
          </h3>
          <p v-if="!isMedicoView && medico?.especialidad" class="text-sm text-gray-600 mt-1">
            {{ medico.especialidad }}
          </p>
          <p v-if="isMedicoView && paciente?.dni" class="text-sm text-gray-600 mt-1">
            DNI: {{ paciente.dni }}
          </p>
          <div v-if="!isMedicoView && medico?.calificacion_promedio" class="flex items-center space-x-2 mt-2">
            <div class="flex items-center">
              <Star class="w-4 h-4 text-yellow-500 fill-yellow-500" />
              <span class="text-sm font-medium text-gray-900 ml-1">
                {{ medico.calificacion_promedio.toFixed(1) }}/5
              </span>
            </div>
            <span class="text-sm text-gray-600">
              ({{ medico.total_calificaciones || 0 }} calificaciones)
            </span>
          </div>
          <span
            :class="[
              'inline-block px-3 py-1 rounded-full text-sm font-medium mt-2',
              getBadgeClasses(cita?.estado)
            ]"
          >
            {{ cita?.estado }}
          </span>
        </div>
      </div>

      <!-- Detalles de la Cita -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Información de la Cita</h4>
            <div class="space-y-3">
              <div class="flex items-center space-x-3">
                <Calendar class="w-5 h-5 text-gray-400" />
                <div>
                  <p class="text-sm text-gray-600">Fecha</p>
                  <p class="font-medium text-gray-900">{{ formatearFecha(cita?.fecha_hora_inicio) }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-3">
                <Clock class="w-5 h-5 text-gray-400" />
                <div>
                  <p class="text-sm text-gray-600">Hora</p>
                  <p class="font-medium text-gray-900">{{ formatearHora(cita?.fecha_hora_inicio) }}</p>
                </div>
              </div>
              <div v-if="cita?.motivo" class="flex items-start space-x-3">
                <FileText class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="text-sm text-gray-600">Motivo</p>
                  <p class="font-medium text-gray-900">{{ cita.motivo }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Estado y Acciones</h4>
            <div class="space-y-3">
              <div class="flex items-center space-x-3">
                <Info class="w-5 h-5 text-gray-400" />
                <div>
                  <p class="text-sm text-gray-600">Estado actual</p>
                  <p class="font-medium text-gray-900 capitalize">{{ cita?.estado }}</p>
                </div>
              </div>
              <div v-if="cita?.estado === 'completado' && cita.notas" class="flex items-start space-x-3">
                <MessageSquare class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="text-sm text-gray-600">Notas del médico</p>
                  <p class="font-medium text-gray-900">{{ cita.notas }}</p>
                </div>
              </div>
              <div v-if="cita?.calificacion" class="flex items-center space-x-3">
                <Star class="w-5 h-5 text-yellow-500 fill-yellow-500" />
                <div>
                  <p class="text-sm text-gray-600">{{ isMedicoView ? 'Calificación del paciente' : 'Tu calificación' }}</p>
                  <p class="font-medium text-gray-900">{{ cita.calificacion.puntuacion }}/5</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas del Médico (solo para pacientes) -->
      <div v-if="!isMedicoView && medico?.estadisticas" class="p-4 bg-gray-50 rounded-lg">
        <h4 class="text-lg font-semibold text-gray-900 mb-3">Estadísticas del Médico</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="text-center">
            <p class="text-2xl font-bold text-primary-600">{{ medico.estadisticas.total_citas || 0 }}</p>
            <p class="text-sm text-gray-600">Citas totales</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-green-600">{{ medico.estadisticas.citas_completadas || 0 }}</p>
            <p class="text-sm text-gray-600">Completadas</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-blue-600">{{ medico.estadisticas.puntuacion_promedio?.toFixed(1) || '0.0' }}</p>
            <p class="text-sm text-gray-600">Calificación</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-purple-600">{{ medico.estadisticas.total_resenas || 0 }}</p>
            <p class="text-sm text-gray-600">Reseñas</p>
          </div>
        </div>
      </div>

      <!-- Últimas reseñas (solo para pacientes) -->
      <div v-if="!isMedicoView && medico?.ultimas_resenas && medico.ultimas_resenas.length > 0" class="space-y-3">
        <h4 class="text-lg font-semibold text-gray-900">Últimas Reseñas</h4>
        <div class="space-y-3 max-h-60 overflow-y-auto">
          <div
            v-for="resena in medico.ultimas_resenas"
            :key="resena.id"
            class="p-3 bg-white border border-gray-200 rounded-lg"
          >
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center space-x-2">
                <div class="flex">
                  <Star
                    v-for="i in 5"
                    :key="i"
                    :class="[
                      'w-4 h-4',
                      i <= resena.puntuacion ? 'text-yellow-500 fill-yellow-500' : 'text-gray-300'
                    ]"
                  />
                </div>
                <span class="text-sm font-medium text-gray-900">{{ resena.puntuacion }}/5</span>
              </div>
              <span class="text-xs text-gray-500">{{ formatearFecha(resena.fecha) }}</span>
            </div>
            <p v-if="resena.comentario" class="text-sm text-gray-700">{{ resena.comentario }}</p>
            <p v-else class="text-sm text-gray-500 italic">Sin comentario</p>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <Button variant="secondary" @click="emit('close')">
        Cerrar
      </Button>
      <!-- Acciones según el rol -->
      <div v-if="isMedicoView" class="flex space-x-2">
        <Button
          v-if="cita?.puede_agregar_notas"
          variant="outline"
          @click="emit('agregarNotas', cita)"
        >
          <MessageSquare class="w-4 h-4" />
          Notas
        </Button>
        <Button
          v-if="cita?.puede_completar"
          variant="success"
          @click="emit('completar', cita)"
        >
          <CheckCircle class="w-4 h-4" />
          Completar
        </Button>
        <Button
          v-if="cita?.puede_cancelar"
          variant="danger"
          @click="emit('cancelar', cita)"
        >
          <XCircle class="w-4 h-4" />
          Cancelar
        </Button>
      </div>
      <div v-else class="flex space-x-2">
        <Button
          v-if="cita?.puede_confirmar"
          variant="success"
          @click="emit('confirmar', cita)"
        >
          <CheckCircle class="w-4 h-4" />
          Confirmar
        </Button>
        <Button
          v-if="cita?.puede_reprogramar"
          variant="outline"
          @click="emit('reprogramar', cita)"
        >
          <Calendar class="w-4 h-4" />
          Reprogramar
        </Button>
        <Button
          v-if="cita?.puede_cancelar"
          variant="danger"
          @click="emit('cancelar', cita)"
        >
          <XCircle class="w-4 h-4" />
          Cancelar
        </Button>
        <Button
          v-if="cita?.puede_calificar"
          variant="primary"
          @click="emit('calificar', cita)"
        >
          <Star class="w-4 h-4" />
          Calificar
        </Button>
      </div>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  Calendar,
  Clock,
  User,
  FileText,
  MessageSquare,
  Star,
  Info,
  CheckCircle,
  XCircle
} from 'lucide-vue-next'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'
import type { Cita } from '@/api/citasPacienteService'
import type { CitaMedico } from '@/api/citasMedicoService'
import medicoService from '@/api/medicoService'

interface Props {
  show: boolean
  cita: Cita | CitaMedico | null
  isMedicoView?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isMedicoView: false
})

const emit = defineEmits(['close', 'confirmar', 'reprogramar', 'cancelar', 'calificar', 'completar', 'agregarNotas'])

const formatearFecha = (fecha?: string) => {
  if (!fecha) return ''
  return format(new Date(fecha), "d 'de' MMMM 'de' yyyy", { locale: es })
}

const formatearHora = (fecha?: string) => {
  if (!fecha) return ''
  return format(new Date(fecha), 'HH:mm', { locale: es })
}

const getFotoUrl = (fotoPath?: string): string | null => {
  return medicoService.getFotoUrl(fotoPath)
}

const getBadgeClasses = (estado?: string) => {
  const classes: Record<string, string> = {
    pendiente: 'bg-orange-100 text-orange-700',
    confirmada: 'bg-green-100 text-green-700',
    reprogramada: 'bg-blue-100 text-blue-700',
    completada: 'bg-gray-100 text-gray-700',
    cancelada: 'bg-red-100 text-red-700',
    no_asistio: 'bg-gray-100 text-gray-500'
  }
  return classes[estado || ''] || 'bg-gray-100 text-gray-700'
}

// Computed properties para acceder a los datos según el tipo de cita
const medico = computed(() => {
  if (props.isMedicoView) return null
  return (props.cita as Cita)?.medico
})

const paciente = computed(() => {
  if (!props.isMedicoView) return null
  return (props.cita as CitaMedico)?.paciente
})
</script>
