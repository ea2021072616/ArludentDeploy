<template>
  <div class="space-y-6">
    <!-- Header simplificado para paciente -->
    <div class="text-center">
      <h3 class="text-xl font-semibold text-gray-800 mb-2">Mi Odontograma</h3>
      <p class="text-sm text-gray-600">Estado actual de mis dientes</p>
    </div>

    <!-- Leyenda simplificada -->
    <div class="bg-gray-50 rounded-lg p-4">
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-center">
        <div class="flex flex-col items-center space-y-1">
          <div class="w-6 h-6 bg-green-100 border-2 border-green-400 rounded-full"></div>
          <span class="text-xs font-medium text-gray-700">Sano</span>
        </div>
        <div class="flex flex-col items-center space-y-1">
          <div class="w-6 h-6 bg-red-100 border-2 border-red-400 rounded-full"></div>
          <span class="text-xs font-medium text-gray-700">Cariado</span>
        </div>
        <div class="flex flex-col items-center space-y-1">
          <div class="w-6 h-6 bg-blue-100 border-2 border-blue-400 rounded-full"></div>
          <span class="text-xs font-medium text-gray-700">Restaurado</span>
        </div>
        <div class="flex flex-col items-center space-y-1">
          <div class="w-6 h-6 bg-gray-200 border-2 border-gray-400 rounded-full"></div>
          <span class="text-xs font-medium text-gray-700">Ausente</span>
        </div>
        <div class="flex flex-col items-center space-y-1">
          <div class="w-6 h-6 bg-purple-100 border-2 border-purple-400 rounded-full"></div>
          <span class="text-xs font-medium text-gray-700">Prótesis</span>
        </div>
      </div>
    </div>

    <!-- Odontograma con forma de boca -->
    <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
      <!-- Arcada Superior (curva superior) -->
      <div class="mb-12">
        <h4 class="text-center text-sm font-medium text-gray-600 mb-6">Dientes Superiores</h4>
        <div class="relative">
          <!-- Línea curva superior -->
          <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-gray-300 to-transparent rounded-full opacity-30"></div>
          
          <!-- Dientes superiores en forma de arco -->
          <div class="flex justify-center">
            <div class="grid grid-cols-8 gap-3 max-w-2xl">
              <!-- Lado derecho (18-11) -->
              <div v-for="num in [18, 17, 16, 15, 14, 13, 12, 11]" :key="`superior-${num}`"
                   class="flex flex-col items-center transform hover:scale-105 transition-transform">
                <div
                  :class="[
                    'w-8 h-12 rounded-t-lg border-2 transition-all cursor-pointer shadow-sm',
                    obtenerColorDiente(num.toString()),
                    'hover:shadow-md'
                  ]"
                  :title="obtenerInfoDiente(num.toString())"
                  @click="mostrarDetalleDiente(num.toString())"
                >
                  <div class="flex flex-col items-center justify-center h-full">
                    <span class="text-xs font-bold text-gray-700">{{ num }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Lado izquierdo (21-28) -->
              <div v-for="num in [21, 22, 23, 24, 25, 26, 27, 28]" :key="`superior-${num}`"
                   class="flex flex-col items-center transform hover:scale-105 transition-transform">
                <div
                  :class="[
                    'w-8 h-12 rounded-t-lg border-2 transition-all cursor-pointer shadow-sm',
                    obtenerColorDiente(num.toString()),
                    'hover:shadow-md'
                  ]"
                  :title="obtenerInfoDiente(num.toString())"
                  @click="mostrarDetalleDiente(num.toString())"
                >
                  <div class="flex flex-col items-center justify-center h-full">
                    <span class="text-xs font-bold text-gray-700">{{ num }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Separador central -->
      <div class="flex justify-center mb-8">
        <div class="w-32 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
      </div>

      <!-- Arcada Inferior (curva inferior) -->
      <div class="mb-6">
        <div class="relative">
          <!-- Dientes inferiores en forma de arco -->
          <div class="flex justify-center">
            <div class="grid grid-cols-8 gap-3 max-w-2xl">
              <!-- Lado derecho (48-41) -->
              <div v-for="num in [48, 47, 46, 45, 44, 43, 42, 41]" :key="`inferior-${num}`"
                   class="flex flex-col items-center transform hover:scale-105 transition-transform">
                <div
                  :class="[
                    'w-8 h-12 rounded-b-lg border-2 transition-all cursor-pointer shadow-sm',
                    obtenerColorDiente(num.toString()),
                    'hover:shadow-md'
                  ]"
                  :title="obtenerInfoDiente(num.toString())"
                  @click="mostrarDetalleDiente(num.toString())"
                >
                  <div class="flex flex-col items-center justify-center h-full">
                    <span class="text-xs font-bold text-gray-700">{{ num }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Lado izquierdo (31-38) -->
              <div v-for="num in [31, 32, 33, 34, 35, 36, 37, 38]" :key="`inferior-${num}`"
                   class="flex flex-col items-center transform hover:scale-105 transition-transform">
                <div
                  :class="[
                    'w-8 h-12 rounded-b-lg border-2 transition-all cursor-pointer shadow-sm',
                    obtenerColorDiente(num.toString()),
                    'hover:shadow-md'
                  ]"
                  :title="obtenerInfoDiente(num.toString())"
                  @click="mostrarDetalleDiente(num.toString())"
                >
                  <div class="flex flex-col items-center justify-center h-full">
                    <span class="text-xs font-bold text-gray-700">{{ num }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Línea curva inferior -->
          <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-transparent via-gray-300 to-transparent rounded-full opacity-30"></div>
        </div>
        <h4 class="text-center text-sm font-medium text-gray-600 mt-6">Dientes Inferiores</h4>
      </div>
    </div>

    <!-- Resumen de estado para paciente -->
    <div v-if="piezasDentales.length > 0" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
      <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
        <Info class="w-5 h-5 mr-2 text-blue-600" />
        Resumen de mi salud dental
      </h4>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ contarEstado('sano') }}</div>
          <div class="text-sm text-gray-600">Dientes sanos</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-red-600">{{ contarEstado('cariado') }}</div>
          <div class="text-sm text-gray-600">Necesitan atención</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ contarEstado('restaurado') }}</div>
          <div class="text-sm text-gray-600">Restaurados</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600">{{ contarEstado('protesis') }}</div>
          <div class="text-sm text-gray-600">Prótesis</div>
        </div>
      </div>
    </div>

    <!-- Lista de tratamientos pendientes para paciente -->
    <div v-if="tratamientosPendientes.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <h4 class="font-semibold text-yellow-800 mb-3 flex items-center">
        <AlertTriangle class="w-5 h-5 mr-2" />
        Tratamientos pendientes
      </h4>
      <div class="space-y-2">
        <div v-for="tratamiento in tratamientosPendientes" :key="tratamiento.pieza"
             class="bg-white p-3 rounded border border-yellow-200 text-sm">
          <div class="flex justify-between items-center">
            <div>
              <span class="font-medium">Diente {{ tratamiento.pieza }}</span>
              <span class="text-yellow-700 ml-2">{{ tratamiento.tratamiento_asociado }}</span>
            </div>
            <span class="text-xs text-yellow-600 bg-yellow-100 px-2 py-1 rounded">Pendiente</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de detalle del diente -->
    <div v-if="modalDetalle" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" @click.self="cerrarModalDetalle">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Diente {{ dienteSeleccionado }}</h3>
          <button @click="cerrarModalDetalle" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <div v-if="detalleDiente" class="space-y-3">
          <div class="flex items-center space-x-2">
            <span class="font-medium text-gray-700">Estado:</span>
            <span :class="[
              'px-2 py-1 rounded text-sm font-medium',
              obtenerClaseEstado(detalleDiente.estado_pieza)
            ]">
              {{ obtenerLabelEstado(detalleDiente.estado_pieza) }}
            </span>
          </div>
          
          <div v-if="detalleDiente.tratamiento_asociado">
            <span class="font-medium text-gray-700">Tratamiento:</span>
            <p class="text-gray-600 mt-1">{{ detalleDiente.tratamiento_asociado }}</p>
          </div>
          
          <div v-if="detalleDiente.comentario">
            <span class="font-medium text-gray-700">Observaciones:</span>
            <p class="text-gray-600 mt-1">{{ detalleDiente.comentario }}</p>
          </div>
          
          <div v-if="detalleDiente.fecha_registro">
            <span class="font-medium text-gray-700">Fecha de registro:</span>
            <p class="text-gray-600 mt-1">{{ formatearFecha(detalleDiente.fecha_registro) }}</p>
          </div>
        </div>
        
        <div v-else class="text-center py-4">
          <div :class="['w-16 h-16 mx-auto mb-3 rounded-full border-4', obtenerColorDiente(dienteSeleccionado)]"></div>
          <p class="text-gray-600">Este diente está en estado normal</p>
          <p class="text-sm text-gray-500 mt-1">Sin tratamientos registrados</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Info, AlertTriangle, X } from 'lucide-vue-next'

// Props
const props = defineProps<{
  piezasDentales: Array<{
    id_odontograma: number
    pieza: string
    estado_pieza: string
    tratamiento_asociado?: string
    comentario?: string
    fecha_registro?: string
  }>
}>()

// Estado
const modalDetalle = ref(false)
const dienteSeleccionado = ref('')
const detalleDiente = ref<any>(null)

// Computed
const tratamientosPendientes = computed(() => {
  return props.piezasDentales.filter(pieza => 
    pieza.estado_pieza === 'cariado' || 
    pieza.tratamiento_asociado?.includes('pendiente')
  )
})

// Métodos
const obtenerColorDiente = (numeroDiente: string) => {
  const pieza = props.piezasDentales.find(p => p.pieza === numeroDiente)
  if (!pieza) return 'bg-green-100 border-green-300 hover:border-green-400'
  
  switch (pieza.estado_pieza) {
    case 'sano':
      return 'bg-green-100 border-green-400 hover:border-green-500'
    case 'cariado':
      return 'bg-red-100 border-red-400 hover:border-red-500'
    case 'restaurado':
      return 'bg-blue-100 border-blue-400 hover:border-blue-500'
    case 'ausente':
      return 'bg-gray-200 border-gray-400 hover:border-gray-500'
    case 'protesis':
      return 'bg-purple-100 border-purple-400 hover:border-purple-500'
    default:
      return 'bg-green-100 border-green-300 hover:border-green-400'
  }
}

const obtenerClaseEstado = (estado: string) => {
  switch (estado) {
    case 'sano':
      return 'bg-green-100 text-green-800 border border-green-200'
    case 'cariado':
      return 'bg-red-100 text-red-800 border border-red-200'
    case 'restaurado':
      return 'bg-blue-100 text-blue-800 border border-blue-200'
    case 'ausente':
      return 'bg-gray-100 text-gray-800 border border-gray-200'
    case 'protesis':
      return 'bg-purple-100 text-purple-800 border border-purple-200'
    default:
      return 'bg-gray-100 text-gray-800 border border-gray-200'
  }
}

const obtenerLabelEstado = (estado: string) => {
  switch (estado) {
    case 'sano': return 'Sano'
    case 'cariado': return 'Cariado'
    case 'restaurado': return 'Restaurado'
    case 'ausente': return 'Ausente'
    case 'protesis': return 'Prótesis'
    default: return 'Sano'
  }
}

const obtenerInfoDiente = (numeroDiente: string) => {
  const pieza = props.piezasDentales.find(p => p.pieza === numeroDiente)
  if (!pieza) return `Diente ${numeroDiente}: Sano`
  
  return `Diente ${numeroDiente}: ${obtenerLabelEstado(pieza.estado_pieza)}${pieza.tratamiento_asociado ? ` - ${pieza.tratamiento_asociado}` : ''}`
}

const contarEstado = (estado: string) => {
  return props.piezasDentales.filter(pieza => pieza.estado_pieza === estado).length
}

const mostrarDetalleDiente = (numeroDiente: string) => {
  dienteSeleccionado.value = numeroDiente
  detalleDiente.value = props.piezasDentales.find(p => p.pieza === numeroDiente) || null
  modalDetalle.value = true
}

const cerrarModalDetalle = () => {
  modalDetalle.value = false
  dienteSeleccionado.value = ''
  detalleDiente.value = null
}

const formatearFecha = (fecha: string) => {
  return new Date(fecha).toLocaleDateString('es-PE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>