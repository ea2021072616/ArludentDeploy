<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-50 via-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <!-- Loading State -->
    <div v-if="cargando" class="flex justify-center items-center min-h-[60vh]">
      <div class="text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-purple-600 mx-auto"></div>
        <p class="mt-4 text-gray-600 font-medium">Cargando información...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="max-w-2xl mx-auto">
      <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-6 shadow-lg">
        <div class="flex items-center">
          <AlertCircle class="h-8 w-8 text-red-500 mr-3" />
          <div>
            <h3 class="text-red-800 font-bold text-lg">Error al cargar el seguimiento</h3>
            <p class="text-red-600 mt-2">{{ error }}</p>
          </div>
        </div>
        <p class="mt-4 text-sm text-red-700">
          Si el problema persiste, por favor contacta con la clínica.
        </p>
      </div>
    </div>

    <!-- Ya Respondido -->
    <div v-else-if="seguimiento && (seguimiento.estado === 'respondido' || seguimiento.estado === 'realizado')" class="max-w-2xl mx-auto">
      <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-8 py-12 text-center">
          <CheckCircle class="h-20 w-20 text-white mx-auto mb-4" />
          <h1 class="text-3xl font-bold text-white mb-2">¡Gracias por tu respuesta!</h1>
          <p class="text-green-50 text-lg">Ya has completado este cuestionario</p>
        </div>
        <div class="p-8">
          <p class="text-gray-600 text-center mb-4">
            Respondiste el {{ formatearFecha(seguimiento.respondido_paciente_at) }}
          </p>
          <p class="text-gray-700 text-center">
            Nuestro equipo ha recibido tu información. Si es necesario, nos pondremos en contacto contigo.
          </p>
        </div>
      </div>
    </div>

    <!-- Formulario -->
    <div v-else-if="seguimiento" class="max-w-3xl mx-auto">
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-8 py-12 text-center">
          <div class="text-6xl mb-4">🦷</div>
          <h1 class="text-3xl font-bold text-white mb-2">Clínica Arludent</h1>
          <p class="text-purple-100 text-lg">Seguimiento Post-Tratamiento</p>
        </div>

        <!-- Patient Info -->
        <div class="bg-purple-50 px-8 py-6 border-b border-purple-100">
          <div class="flex items-center justify-center">
            <User class="h-5 w-5 text-purple-600 mr-2" />
            <p class="text-lg font-semibold text-gray-800">
              Hola, {{ seguimiento.paciente?.nombre || 'Paciente' }}
            </p>
          </div>
          <p class="text-center text-gray-600 mt-2">
            Nos gustaría saber cómo te sientes después de tu tratamiento
          </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="enviarRespuesta" class="p-8 space-y-8">
          <!-- Estado General -->
          <div>
            <label class="block text-gray-800 font-bold mb-4 text-lg">
              <Smile class="inline h-5 w-5 mr-2 text-purple-600" />
              ¿Cómo te sientes en general?
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <button
                v-for="estado in estadosDisponibles"
                :key="estado.valor"
                type="button"
                @click="formulario.estado_paciente = estado.valor"
                :class="[
                  'p-6 rounded-xl border-2 transition-all duration-200 hover:scale-105',
                  formulario.estado_paciente === estado.valor
                    ? 'border-purple-600 bg-purple-50 shadow-lg'
                    : 'border-gray-200 hover:border-purple-300'
                ]"
              >
                <div class="text-5xl mb-2">{{ estado.emoji }}</div>
                <div class="text-sm font-semibold text-gray-700">{{ estado.texto }}</div>
              </button>
            </div>
            <p v-if="errores.estado_paciente" class="mt-2 text-sm text-red-600">
              {{ errores.estado_paciente }}
            </p>
          </div>

          <!-- Síntomas o Molestias -->
          <div>
            <label for="sintomas" class="block text-gray-800 font-bold mb-3">
              <AlertTriangle class="inline h-5 w-5 mr-2 text-purple-600" />
              ¿Tienes algún síntoma o molestia?
            </label>
            <textarea
              id="sintomas"
              v-model="formulario.sintomas_reportados"
              rows="4"
              placeholder="Describe cualquier dolor, inflamación, sensibilidad u otro síntoma que hayas experimentado..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
            ></textarea>
            <p class="mt-2 text-sm text-gray-500">
              Opcional. Si no tienes ninguna molestia, puedes dejar este campo vacío.
            </p>
          </div>

          <!-- Observaciones Adicionales -->
          <div>
            <label for="observaciones" class="block text-gray-800 font-bold mb-3">
              <MessageSquare class="inline h-5 w-5 mr-2 text-purple-600" />
              ¿Algo más que quieras comentarnos?
            </label>
            <textarea
              id="observaciones"
              v-model="formulario.observaciones_paciente"
              rows="4"
              placeholder="Cualquier duda, sugerencia o comentario adicional es bienvenido..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
            ></textarea>
          </div>

          <!-- Necesita Cita -->
          <div class="bg-indigo-50 rounded-xl p-6 border border-indigo-100">
            <label class="flex items-start cursor-pointer">
              <input
                type="checkbox"
                v-model="formulario.necesita_revision"
                class="mt-1 h-5 w-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
              />
              <span class="ml-3">
                <span class="block text-gray-800 font-bold">
                  <Calendar class="inline h-5 w-5 mr-2 text-purple-600" />
                  Me gustaría agendar una cita de revisión
                </span>
                <span class="block text-gray-600 text-sm mt-1">
                  Si marcas esta opción, nuestro equipo se pondrá en contacto contigo para coordinar una cita.
                </span>
              </span>
            </label>
          </div>

          <!-- Error General -->
          <div v-if="errorEnvio" class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <p class="text-red-700">{{ errorEnvio }}</p>
          </div>

          <!-- Mensaje Éxito -->
          <div v-if="exitoEnvio" class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <div class="flex items-center">
              <CheckCircle class="h-5 w-5 text-green-500 mr-2" />
              <p class="text-green-700 font-medium">¡Respuesta enviada exitosamente!</p>
            </div>
          </div>

          <!-- Botón Submit -->
          <div class="pt-4">
            <button
              type="submit"
              :disabled="enviando"
              class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-4 px-6 rounded-xl font-bold text-lg shadow-lg hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-purple-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-xl"
            >
              <span v-if="!enviando" class="flex items-center justify-center">
                <Send class="h-5 w-5 mr-2" />
                Enviar Respuesta
              </span>
              <span v-else class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                Enviando...
              </span>
            </button>
          </div>

          <!-- Footer Note -->
          <div class="text-center text-sm text-gray-500 pt-4 border-t border-gray-200">
            <p class="flex items-center justify-center">
              <Lock class="h-4 w-4 mr-1" />
              Tus datos están protegidos y serán tratados con confidencialidad
            </p>
          </div>
        </form>
      </div>

      <!-- Info Box IA -->
      <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6 shadow-lg">
        <div class="flex items-start">
          <div class="text-4xl mr-4">🤖</div>
          <div>
            <h3 class="text-blue-900 font-bold text-lg mb-2">Análisis con Inteligencia Artificial</h3>
            <p class="text-blue-800 text-sm leading-relaxed">
              Tu respuesta será analizada automáticamente por nuestro sistema de IA.
              Si detectamos alguna urgencia o situación que requiera atención inmediata,
              un miembro de nuestro equipo se pondrá en contacto contigo rápidamente.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  AlertCircle, CheckCircle, User, Smile, AlertTriangle,
  MessageSquare, Calendar, Send, Lock
} from 'lucide-vue-next'
import axios from 'axios'

const route = useRoute()
const token = route.params.token as string

// Estados
const cargando = ref(true)
const error = ref('')
const seguimiento = ref<any>(null)
const enviando = ref(false)
const errorEnvio = ref('')
const exitoEnvio = ref(false)

// Formulario
const formulario = ref({
  estado_paciente: '',
  sintomas_reportados: '',
  observaciones_paciente: '',
  necesita_revision: false
})

const errores = ref<Record<string, string>>({})

// Estados disponibles
const estadosDisponibles = [
  { valor: 'excelente', emoji: '😊', texto: 'Excelente' },
  { valor: 'bien', emoji: '🙂', texto: 'Bien' },
  { valor: 'regular', emoji: '😐', texto: 'Regular' },
  { valor: 'mal', emoji: '😰', texto: 'Con molestias' }
]

// Cargar seguimiento
const cargarSeguimiento = async () => {
  try {
    cargando.value = true
    error.value = ''

    const baseUrl = (import.meta.env.VITE_API_URL || 'http://localhost:8000/api').replace('/api', '')
    const response = await axios.get(`${baseUrl}/seguimiento/${token}`)
    seguimiento.value = response.data.seguimiento

  } catch (err: any) {
    console.error('Error al cargar seguimiento:', err)
    if (err.response?.status === 404) {
      error.value = 'El enlace no es válido o ha expirado.'
    } else {
      error.value = 'No se pudo cargar la información. Por favor, intenta nuevamente.'
    }
  } finally {
    cargando.value = false
  }
}

// Validar formulario
const validarFormulario = (): boolean => {
  errores.value = {}

  if (!formulario.value.estado_paciente) {
    errores.value.estado_paciente = 'Por favor, selecciona cómo te sientes'
    return false
  }

  return true
}

// Enviar respuesta
const enviarRespuesta = async () => {
  if (!validarFormulario()) {
    return
  }

  try {
    enviando.value = true
    errorEnvio.value = ''
    exitoEnvio.value = false

    const baseUrl = (import.meta.env.VITE_API_URL || 'http://localhost:8000/api').replace('/api', '')
    await axios.post(`${baseUrl}/seguimiento/${token}/responder`, formulario.value)

    exitoEnvio.value = true

    // Recargar para mostrar estado "respondido"
    setTimeout(() => {
      cargarSeguimiento()
    }, 2000)

  } catch (err: any) {
    console.error('Error al enviar respuesta:', err)
    if (err.response?.data?.message) {
      errorEnvio.value = err.response.data.message
    } else {
      errorEnvio.value = 'No se pudo enviar la respuesta. Por favor, intenta nuevamente.'
    }
  } finally {
    enviando.value = false
  }
}

// Formatear fecha
const formatearFecha = (fecha: string | null): string => {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  cargarSeguimiento()
})
</script>
