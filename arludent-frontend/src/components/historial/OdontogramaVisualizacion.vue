<template>
  <div class="space-y-6">
    <!-- Header con ícono premium -->
    <div class="text-center space-y-2">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-primary-50 mb-2">
        <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg>
      </div>
      <h3 class="text-xl font-bold text-gray-900">Mi Odontograma</h3>
      <p class="text-sm text-gray-500">Estado actual de mis dientes</p>
    </div>

    <!-- Leyenda con tarjetas premium -->
    <div class="bg-white rounded-2xl p-4 shadow-soft border border-gray-100">
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-center">
        <div class="flex flex-col items-center space-y-1.5 p-2 rounded-xl bg-emerald-50/50">
          <div class="w-7 h-7 rounded-lg bg-[#FFF8F0] border-2 border-[#D4C5B0] shadow-sm"></div>
          <span class="text-xs font-semibold text-gray-600">Sano</span>
        </div>
        <div class="flex flex-col items-center space-y-1.5 p-2 rounded-xl bg-red-50/50">
          <div class="w-7 h-7 rounded-lg bg-red-200 border-2 border-red-400 shadow-sm"></div>
          <span class="text-xs font-semibold text-gray-600">Cariado</span>
        </div>
        <div class="flex flex-col items-center space-y-1.5 p-2 rounded-xl bg-primary-50/50">
          <div class="w-7 h-7 rounded-lg bg-primary-100 border-2 border-primary-300 shadow-sm"></div>
          <span class="text-xs font-semibold text-gray-600">Restaurado</span>
        </div>
        <div class="flex flex-col items-center space-y-1.5 p-2 rounded-xl bg-gray-50">
          <div class="w-7 h-7 rounded-lg bg-gray-200 border-2 border-gray-300 shadow-sm"></div>
          <span class="text-xs font-semibold text-gray-600">Ausente</span>
        </div>
        <div class="flex flex-col items-center space-y-1.5 p-2 rounded-xl bg-violet-50/50">
          <div class="w-7 h-7 rounded-lg bg-violet-100 border-2 border-violet-300 shadow-sm"></div>
          <span class="text-xs font-semibold text-gray-600">Prótesis</span>
        </div>
      </div>
    </div>

    <!-- Odontograma con forma de boca -->
    <div class="bg-gradient-to-b from-white to-slate-50/80 border border-gray-100 rounded-2xl p-8 shadow-soft">
      <!-- Arcada Superior (curva superior) -->
      <div class="mb-12">
        <h4 class="text-center text-sm font-medium text-gray-600 mb-6">Dientes Superiores</h4>
        <div class="relative">
          <!-- Línea curva superior (encía) -->
          <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-[#FFB5B5] to-transparent rounded-full opacity-40"></div>
          
          <!-- Dientes superiores en forma de arco -->
          <div class="flex justify-center">
            <div class="grid grid-cols-8 gap-3 max-w-2xl">
              <!-- Lado derecho (18-11) -->
              <div v-for="num in [18, 17, 16, 15, 14, 13, 12, 11]" :key="`superior-${num}`"
                   class="flex flex-col items-center transform hover:scale-105 transition-transform">
                <div
                  :class="[
                    'w-9 h-14 rounded-t-xl border-2 transition-all cursor-pointer shadow-sm hover:shadow-md hover:-translate-y-0.5',
                    obtenerColorDiente(num.toString())
                  ]"
                  :title="obtenerInfoDiente(num.toString())"
                  @click="mostrarDetalleDiente(num.toString())"
                >
                  <div class="flex flex-col items-center justify-center h-full">
                    <span class="text-[10px] font-bold text-gray-600">{{ num }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Lado izquierdo (21-28) -->
              <div v-for="num in [21, 22, 23, 24, 25, 26, 27, 28]" :key="`superior-${num}`"
                   class="flex flex-col items-center transform hover:scale-105 transition-transform">
                <div
                  :class="[
                    'w-9 h-14 rounded-t-xl border-2 transition-all cursor-pointer shadow-sm hover:shadow-md hover:-translate-y-0.5',
                    obtenerColorDiente(num.toString())
                  ]"
                  :title="obtenerInfoDiente(num.toString())"
                  @click="mostrarDetalleDiente(num.toString())"
                >
                  <div class="flex flex-col items-center justify-center h-full">
                    <span class="text-[10px] font-bold text-gray-600">{{ num }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Separador central con línea media -->
      <div class="flex justify-center items-center mb-8 gap-3">
        <div class="w-48 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
        <span class="text-[10px] font-bold text-gray-400 tracking-widest uppercase whitespace-nowrap">Línea Media</span>
        <div class="w-48 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
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
                    'w-9 h-14 rounded-b-xl border-2 transition-all cursor-pointer shadow-sm hover:shadow-md hover:translate-y-0.5',
                    obtenerColorDiente(num.toString())
                  ]"
                  :title="obtenerInfoDiente(num.toString())"
                  @click="mostrarDetalleDiente(num.toString())"
                >
                  <div class="flex flex-col items-center justify-center h-full">
                    <span class="text-[10px] font-bold text-gray-600">{{ num }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Lado izquierdo (31-38) -->
              <div v-for="num in [31, 32, 33, 34, 35, 36, 37, 38]" :key="`inferior-${num}`"
                   class="flex flex-col items-center transform hover:scale-105 transition-transform">
                <div
                  :class="[
                    'w-9 h-14 rounded-b-xl border-2 transition-all cursor-pointer shadow-sm hover:shadow-md hover:translate-y-0.5',
                    obtenerColorDiente(num.toString())
                  ]"
                  :title="obtenerInfoDiente(num.toString())"
                  @click="mostrarDetalleDiente(num.toString())"
                >
                  <div class="flex flex-col items-center justify-center h-full">
                    <span class="text-[10px] font-bold text-gray-600">{{ num }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Línea curva inferior (encía) -->
          <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-transparent via-[#FFB5B5] to-transparent rounded-full opacity-40"></div>
        </div>
        <h4 class="text-center text-sm font-medium text-gray-600 mt-6">Dientes Inferiores</h4>
      </div>
    </div>

    <!-- Resumen de estado para paciente -->
    <div v-if="piezasDentales.length > 0" class="bg-gradient-to-br from-primary-50 to-dental-50 rounded-2xl p-6 shadow-soft border border-primary-100/50">
      <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
        <Info class="w-5 h-5 mr-2 text-primary-600" />
        Resumen de mi salud dental
      </h4>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600">{{ contarEstado('sano') }}</div>
          <div class="text-sm text-gray-600">Dientes sanos</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-red-600">{{ contarEstado('cariado') }}</div>
          <div class="text-sm text-gray-600">Necesitan atención</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-primary-600">{{ contarEstado('restaurado') }}</div>
          <div class="text-sm text-gray-600">Restaurados</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-violet-600">{{ contarEstado('protesis') }}</div>
          <div class="text-sm text-gray-600">Prótesis</div>
        </div>
      </div>
    </div>

    <!-- Lista de tratamientos pendientes para paciente -->
    <div v-if="tratamientosPendientes.length > 0" class="bg-accent-50 border border-accent-200 rounded-2xl p-5 shadow-soft">
      <h4 class="font-semibold text-accent-800 mb-3 flex items-center">
        <AlertTriangle class="w-5 h-5 mr-2" />
        Tratamientos pendientes
      </h4>
      <div class="space-y-2">
        <div v-for="tratamiento in tratamientosPendientes" :key="tratamiento.pieza"
             class="bg-white p-3 rounded-xl border border-accent-100 text-sm shadow-sm">
          <div class="flex justify-between items-center">
            <div>
              <span class="font-medium text-gray-800">Diente {{ tratamiento.pieza }}</span>
              <span class="text-accent-700 ml-2">{{ tratamiento.tratamiento_asociado }}</span>
            </div>
            <span class="text-xs font-semibold text-accent-700 bg-accent-100 px-2.5 py-1 rounded-lg ring-1 ring-inset ring-accent-200">Pendiente</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de detalle del diente -->
    <div v-if="modalDetalle" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50" @click.self="cerrarModalDetalle">
      <div class="bg-white rounded-2xl shadow-soft-xl p-6 max-w-md w-full mx-4 border border-gray-100">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-gray-900">Diente {{ dienteSeleccionado }}</h3>
          <button @click="cerrarModalDetalle" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-gray-100">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <div v-if="detalleDiente" class="space-y-3">
          <div class="flex items-center space-x-2">
            <span class="font-medium text-gray-700">Estado:</span>
            <span :class="[
              'px-2 py-1 rounded-lg text-sm font-medium',
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
  if (!pieza) return 'bg-[#FFF8F0] border-[#D4C5B0] hover:border-[#C4B5A0]'
  
  switch (pieza.estado_pieza) {
    case 'sano':
      return 'bg-[#FFF8F0] border-[#D4C5B0] hover:border-[#C4B5A0]'
    case 'cariado':
      return 'bg-red-200 border-red-400 hover:border-red-500'
    case 'restaurado':
      return 'bg-primary-100 border-primary-300 hover:border-primary-400'
    case 'ausente':
      return 'bg-gray-200 border-gray-300 hover:border-gray-400'
    case 'protesis':
      return 'bg-violet-100 border-violet-300 hover:border-violet-400'
    default:
      return 'bg-[#FFF8F0] border-[#D4C5B0] hover:border-[#C4B5A0]'
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