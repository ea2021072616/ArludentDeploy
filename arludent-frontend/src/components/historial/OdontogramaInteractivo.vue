<template>
  <div class="space-y-6">
    <!-- Header profesional -->
    <div class="relative bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 rounded-2xl p-5 text-white overflow-hidden shadow-primary">
      <!-- Subtle pattern overlay -->
      <div class="absolute inset-0 opacity-[0.07]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;20&quot; height=&quot;20&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Ccircle cx=&quot;2&quot; cy=&quot;2&quot; r=&quot;1&quot; fill=&quot;white&quot;/%3E%3C/svg%3E'); background-size: 20px 20px;"></div>
      <div class="relative flex justify-between items-center">
        <div>
          <h3 class="text-lg font-semibold tracking-tight">Odontograma Interactivo</h3>
          <p v-if="!soloLectura" class="text-sm text-primary-100 mt-0.5">Haz clic en cualquier diente para registrar su estado</p>
          <p v-else class="text-sm text-primary-100 mt-0.5">Estado actual de tu salud dental</p>
        </div>
        <div class="text-right text-sm text-primary-100 space-y-0.5">
          <div>Total registros: {{ piezasDentales.length }}</div>
          <div>Última actualización: {{ ultimaActualizacion }}</div>
        </div>
      </div>
    </div>

    <!-- Leyenda mejorada -->
    <div class="bg-white/80 backdrop-blur-xl border border-gray-100 rounded-xl p-5 shadow-soft">
      <h4 class="text-sm font-semibold text-gray-700 mb-3 tracking-wide uppercase">Estados Dentales</h4>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="flex items-center space-x-2.5 px-3 py-2.5 bg-emerald-50/60 rounded-xl ring-1 ring-inset ring-emerald-200/80">
          <div class="w-4 h-4 rounded-full bg-[#FFF8F0] ring-2 ring-[#D4C5B0] flex-shrink-0"></div>
          <span class="text-sm font-medium text-emerald-700">Sano</span>
        </div>
        <div class="flex items-center space-x-2.5 px-3 py-2.5 bg-red-50/60 rounded-xl ring-1 ring-inset ring-red-200/80">
          <div class="w-4 h-4 rounded-full bg-red-200 ring-2 ring-red-400 flex-shrink-0"></div>
          <span class="text-sm font-medium text-red-700">Cariado</span>
        </div>
        <div class="flex items-center space-x-2.5 px-3 py-2.5 bg-primary-50/60 rounded-xl ring-1 ring-inset ring-primary-200/80">
          <div class="w-4 h-4 rounded-full bg-primary-100 ring-2 ring-primary-400 flex-shrink-0"></div>
          <span class="text-sm font-medium text-primary-700">Restaurado</span>
        </div>
        <div class="flex items-center space-x-2.5 px-3 py-2.5 bg-gray-50/60 rounded-xl ring-1 ring-inset ring-gray-200/80">
          <div class="w-4 h-4 rounded-full bg-gray-200 ring-2 ring-gray-400 flex-shrink-0"></div>
          <span class="text-sm font-medium text-gray-600">Ausente</span>
        </div>
        <div class="flex items-center space-x-2.5 px-3 py-2.5 bg-violet-50/60 rounded-xl ring-1 ring-inset ring-violet-200/80">
          <div class="w-4 h-4 rounded-full bg-violet-100 ring-2 ring-violet-400 flex-shrink-0"></div>
          <span class="text-sm font-medium text-violet-700">Prótesis</span>
        </div>
      </div>
    </div>

    <!-- Odontograma Visual mejorado con formas realistas de dientes -->
    <div class="bg-gradient-to-b from-white to-slate-50 border border-gray-100 rounded-2xl p-8 shadow-soft">
      <!-- Arcada Superior con diseño mejorado -->
      <div class="mb-12">
        <h4 class="text-center text-base font-semibold text-primary-700 mb-6 flex items-center justify-center">
          <!-- Dental arch icon -->
          <svg class="w-5 h-5 mr-2 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <path d="M4 14c0-5.5 3.5-10 8-10s8 4.5 8 10"/>
            <path d="M7.5 12a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" fill="currentColor" stroke="none"/>
            <path d="M12 10a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" fill="currentColor" stroke="none"/>
            <path d="M16.5 12a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" fill="currentColor" stroke="none"/>
          </svg>
          Arcada Superior
        </h4>
        
        <div class="relative">
          <!-- Línea curva superior para simular encía -->
          <div class="absolute inset-x-0 top-0 h-3 bg-gradient-to-r from-transparent via-[#FFB5B5] to-transparent rounded-full opacity-60"></div>
          
          <!-- Dientes superiores en forma de arco -->
          <div class="flex justify-center pt-4">
            <div class="grid grid-cols-16 gap-2 max-w-6xl">
              <!-- Lado derecho (18-11) -->
              <div v-for="num in [18, 17, 16, 15, 14, 13, 12, 11]" :key="`superior-${num}`"
                   class="flex flex-col items-center relative group">
                <button
                  @click="editarDiente(num.toString())"
                  class="relative transition-all duration-200 hover:scale-110 hover:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 rounded tooth-hover"
                >
                  <svg :width="getSVGWidth(num)" :height="64" viewBox="0 0 48 64" class="drop-shadow-md">
                    <!-- Corona del diente -->
                    <path 
                      :d="getToothPath(num, 'superior')"
                      :class="obtenerColorFill(num.toString())"
                      :stroke="obtenerColorStroke(num.toString())"
                      stroke-width="2"
                    />
                    <!-- Raíz del diente (más clara) -->
                    <path 
                      :d="getRootPath(num, 'superior')"
                      :class="obtenerColorRoot(num.toString())"
                      :stroke="obtenerColorStroke(num.toString())"
                      stroke-width="1.5"
                      opacity="0.6"
                    />
                    <!-- Número del diente -->
                    <text x="24" y="28" text-anchor="middle" class="text-xs font-bold fill-gray-700">{{ num }}</text>
                    <!-- Indicador de tratamiento -->
                    <circle v-if="tieneTratamiento(num.toString())" cx="38" cy="12" r="4" class="fill-accent-400" />
                  </svg>
                </button>
                <!-- Tooltip al pasar el mouse -->
                <div class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-200 z-50 w-64">
                  <div class="bg-primary-950 backdrop-blur-xl text-white text-xs rounded-xl p-3 shadow-xl">
                    <div class="font-bold mb-1">Diente {{ num }} - {{ obtenerTipoDiente(num) }}</div>
                    <div class="space-y-1 text-xs">
                      <div><span class="font-semibold">Estado:</span> {{ obtenerEstadoInfo(num.toString()).label }}</div>
                      <div v-if="obtenerEstadoInfo(num.toString()).tratamiento">
                        <span class="font-semibold">Tratamiento:</span> {{ obtenerEstadoInfo(num.toString()).tratamiento }}
                      </div>
                      <div v-if="obtenerEstadoInfo(num.toString()).comentario" class="border-t border-primary-800 pt-1 mt-1">
                        {{ obtenerEstadoInfo(num.toString()).comentario }}
                      </div>
                    </div>
                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                      <div class="border-8 border-transparent border-t-primary-950"></div>
                    </div>
                  </div>
                </div>
                <span class="text-xs text-gray-500 mt-1">{{ obtenerTipoDiente(num) }}</span>
              </div>
              
              <!-- Lado izquierdo (21-28) -->
              <div v-for="num in [21, 22, 23, 24, 25, 26, 27, 28]" :key="`superior-${num}`"
                   class="flex flex-col items-center relative group">
                <button
                  @click="editarDiente(num.toString())"
                  class="relative transition-all duration-200 hover:scale-110 hover:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 rounded tooth-hover"
                >
                  <svg :width="getSVGWidth(num)" :height="64" viewBox="0 0 48 64" class="drop-shadow-md">
                    <!-- Corona del diente -->
                    <path 
                      :d="getToothPath(num, 'superior')"
                      :class="obtenerColorFill(num.toString())"
                      :stroke="obtenerColorStroke(num.toString())"
                      stroke-width="2"
                    />
                    <!-- Raíz del diente -->
                    <path 
                      :d="getRootPath(num, 'superior')"
                      :class="obtenerColorRoot(num.toString())"
                      :stroke="obtenerColorStroke(num.toString())"
                      stroke-width="1.5"
                      opacity="0.6"
                    />
                    <!-- Número del diente -->
                    <text x="24" y="28" text-anchor="middle" class="text-xs font-bold fill-gray-700">{{ num }}</text>
                    <!-- Indicador de tratamiento -->
                    <circle v-if="tieneTratamiento(num.toString())" cx="38" cy="12" r="4" class="fill-accent-400" />
                  </svg>
                </button>
                <!-- Tooltip al pasar el mouse -->
                <div class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-200 z-50 w-64">
                  <div class="bg-primary-950 backdrop-blur-xl text-white text-xs rounded-xl p-3 shadow-xl">
                    <div class="font-bold mb-1">Diente {{ num }} - {{ obtenerTipoDiente(num) }}</div>
                    <div class="space-y-1 text-xs">
                      <div><span class="font-semibold">Estado:</span> {{ obtenerEstadoInfo(num.toString()).label }}</div>
                      <div v-if="obtenerEstadoInfo(num.toString()).tratamiento">
                        <span class="font-semibold">Tratamiento:</span> {{ obtenerEstadoInfo(num.toString()).tratamiento }}
                      </div>
                      <div v-if="obtenerEstadoInfo(num.toString()).comentario" class="border-t border-primary-800 pt-1 mt-1">
                        {{ obtenerEstadoInfo(num.toString()).comentario }}
                      </div>
                    </div>
                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                      <div class="border-8 border-transparent border-t-primary-950"></div>
                    </div>
                  </div>
                </div>
                <span class="text-xs text-gray-500 mt-1">{{ obtenerTipoDiente(num) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Separador central -->
      <div class="flex justify-center items-center mb-8">
        <div class="flex items-center space-x-4">
          <div class="w-32 h-px bg-gradient-to-r from-transparent via-primary-300 to-transparent"></div>
          <span class="text-xs text-primary-400 font-semibold tracking-[0.2em]">LÍNEA MEDIA</span>
          <div class="w-32 h-px bg-gradient-to-r from-transparent via-primary-300 to-transparent"></div>
        </div>
      </div>

      <!-- Arcada Inferior con diseño mejorado -->
      <div class="mb-6">
        <div class="relative">
          <!-- Dientes inferiores en forma de arco -->
          <div class="flex justify-center pb-4">
            <div class="grid grid-cols-16 gap-2 max-w-6xl">
              <!-- Lado derecho (48-41) -->
              <div v-for="num in [48, 47, 46, 45, 44, 43, 42, 41]" :key="`inferior-${num}`"
                   class="flex flex-col items-center relative group">
                <span class="text-xs text-gray-500 mb-1">{{ obtenerTipoDiente(num) }}</span>
                <button
                  @click="editarDiente(num.toString())"
                  class="relative transition-all duration-200 hover:scale-110 hover:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 rounded tooth-hover"
                >
                  <svg :width="getSVGWidth(num)" :height="64" viewBox="0 0 48 64" class="drop-shadow-md">
                    <!-- Raíz del diente (en la parte superior para dientes inferiores) -->
                    <path 
                      :d="getRootPath(num, 'inferior')"
                      :class="obtenerColorRoot(num.toString())"
                      :stroke="obtenerColorStroke(num.toString())"
                      stroke-width="1.5"
                      opacity="0.6"
                    />
                    <!-- Corona del diente -->
                    <path 
                      :d="getToothPath(num, 'inferior')"
                      :class="obtenerColorFill(num.toString())"
                      :stroke="obtenerColorStroke(num.toString())"
                      stroke-width="2"
                    />
                    <!-- Indicador de tratamiento -->
                    <circle v-if="tieneTratamiento(num.toString())" cx="38" cy="52" r="4" class="fill-accent-400" />
                    <!-- Número del diente -->
                    <text x="24" y="42" text-anchor="middle" class="text-xs font-bold fill-gray-700">{{ num }}</text>
                  </svg>
                </button>
                <!-- Tooltip al pasar el mouse -->
                <div class="absolute top-full mt-2 left-1/2 transform -translate-x-1/2 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-200 z-50 w-64">
                  <div class="bg-primary-950 backdrop-blur-xl text-white text-xs rounded-xl p-3 shadow-xl">
                    <div class="font-bold mb-1">Diente {{ num }} - {{ obtenerTipoDiente(num) }}</div>
                    <div class="space-y-1 text-xs">
                      <div><span class="font-semibold">Estado:</span> {{ obtenerEstadoInfo(num.toString()).label }}</div>
                      <div v-if="obtenerEstadoInfo(num.toString()).tratamiento">
                        <span class="font-semibold">Tratamiento:</span> {{ obtenerEstadoInfo(num.toString()).tratamiento }}
                      </div>
                      <div v-if="obtenerEstadoInfo(num.toString()).comentario" class="border-t border-primary-800 pt-1 mt-1">
                        {{ obtenerEstadoInfo(num.toString()).comentario }}
                      </div>
                    </div>
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 -mb-1">
                      <div class="border-8 border-transparent border-b-primary-950"></div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Lado izquierdo (31-38) -->
              <div v-for="num in [31, 32, 33, 34, 35, 36, 37, 38]" :key="`inferior-${num}`"
                   class="flex flex-col items-center relative group">
                <span class="text-xs text-gray-500 mb-1">{{ obtenerTipoDiente(num) }}</span>
                <button
                  @click="editarDiente(num.toString())"
                  class="relative transition-all duration-200 hover:scale-110 hover:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 rounded tooth-hover"
                >
                  <svg :width="getSVGWidth(num)" :height="64" viewBox="0 0 48 64" class="drop-shadow-md">
                    <!-- Raíz del diente -->
                    <path 
                      :d="getRootPath(num, 'inferior')"
                      :class="obtenerColorRoot(num.toString())"
                      :stroke="obtenerColorStroke(num.toString())"
                      stroke-width="1.5"
                      opacity="0.6"
                    />
                    <!-- Corona del diente -->
                    <path 
                      :d="getToothPath(num, 'inferior')"
                      :class="obtenerColorFill(num.toString())"
                      :stroke="obtenerColorStroke(num.toString())"
                      stroke-width="2"
                    />
                    <!-- Indicador de tratamiento -->
                    <circle v-if="tieneTratamiento(num.toString())" cx="38" cy="52" r="4" class="fill-accent-400" />
                    <!-- Número del diente -->
                    <text x="24" y="42" text-anchor="middle" class="text-xs font-bold fill-gray-700">{{ num }}</text>
                  </svg>
                </button>
                <!-- Tooltip al pasar el mouse -->
                <div class="absolute top-full mt-2 left-1/2 transform -translate-x-1/2 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-200 z-50 w-64">
                  <div class="bg-primary-950 backdrop-blur-xl text-white text-xs rounded-xl p-3 shadow-xl">
                    <div class="font-bold mb-1">Diente {{ num }} - {{ obtenerTipoDiente(num) }}</div>
                    <div class="space-y-1 text-xs">
                      <div><span class="font-semibold">Estado:</span> {{ obtenerEstadoInfo(num.toString()).label }}</div>
                      <div v-if="obtenerEstadoInfo(num.toString()).tratamiento">
                        <span class="font-semibold">Tratamiento:</span> {{ obtenerEstadoInfo(num.toString()).tratamiento }}
                      </div>
                      <div v-if="obtenerEstadoInfo(num.toString()).comentario" class="border-t border-primary-800 pt-1 mt-1">
                        {{ obtenerEstadoInfo(num.toString()).comentario }}
                      </div>
                    </div>
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 -mb-1">
                      <div class="border-8 border-transparent border-b-primary-950"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Línea curva inferior para simular encía -->
          <div class="absolute inset-x-0 bottom-0 h-3 bg-gradient-to-r from-transparent via-[#FFB5B5] to-transparent rounded-full opacity-60"></div>
        </div>
        
        <h4 class="text-center text-base font-semibold text-primary-700 mt-6 flex items-center justify-center">
          <!-- Dental arch icon (inverted) -->
          <svg class="w-5 h-5 mr-2 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <path d="M4 10c0 5.5 3.5 10 8 10s8-4.5 8-10"/>
            <path d="M7.5 15a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" fill="currentColor" stroke="none"/>
            <path d="M12 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" fill="currentColor" stroke="none"/>
            <path d="M16.5 15a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" fill="currentColor" stroke="none"/>
          </svg>
          Arcada Inferior
        </h4>
      </div>
    </div>

    <!-- Tabla de Registros (solo para médicos) -->
    <div v-if="!soloLectura && piezasDentales.length > 0" class="bg-slate-50/80 rounded-2xl p-5">
      <h4 class="font-semibold text-gray-900 mb-3">Registros Dentales</h4>
      <div class="space-y-2">
        <div v-for="pieza in piezasDentales" :key="pieza.id_odontograma"
             class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm text-sm">
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <span class="font-semibold">Diente {{ pieza.pieza }}</span>
              <span :class="[
                'ml-2 px-2 py-0.5 rounded-lg text-xs font-medium ring-1 ring-inset',
                obtenerClaseEstado(pieza.estado_pieza)
              ]">
                {{ obtenerLabelEstado(pieza.estado_pieza) }}
              </span>
              <p v-if="pieza.tratamiento_asociado" class="text-gray-600 mt-1">
                Tratamiento: {{ pieza.tratamiento_asociado }}
              </p>
              <p v-if="pieza.comentario" class="text-gray-500 mt-1 text-xs">
                {{ pieza.comentario }}
              </p>
            </div>
            <button @click="editarDiente(pieza.pieza)" class="text-primary-500 hover:text-primary-700 transition-colors">
              <Edit class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar Diente -->
    <div v-if="modalAbierto" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center" @click.self="cerrarModal">
      <div class="relative bg-white rounded-2xl shadow-soft-xl p-6 w-full max-w-md animate-fade-in-up">
        <div class="flex justify-between items-center mb-5">
          <h3 class="text-xl font-bold text-gray-900">Diente {{ dienteSeleccionado }}</h3>
          <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="guardarDiente" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Estado del Diente</label>
            <select
              v-model="formDiente.estado_pieza"
              class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition-shadow"
              required
            >
              <option value="sano">Sano</option>
              <option value="cariado">Cariado</option>
              <option value="restaurado">Restaurado</option>
              <option value="ausente">Ausente</option>
              <option value="protesis">Prótesis</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Tratamiento Asociado</label>
            <input
              v-model="formDiente.tratamiento_asociado"
              type="text"
              class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition-shadow"
              placeholder="Ej: Obturación, Endodoncia, etc."
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Comentarios</label>
            <textarea
              v-model="formDiente.comentario"
              rows="3"
              class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition-shadow"
              placeholder="Observaciones adicionales..."
            ></textarea>
          </div>

          <div class="flex justify-end space-x-3 pt-5 border-t border-gray-100">
            <Button variant="secondary" @click="cerrarModal">
              Cancelar
            </Button>
            <Button variant="primary" type="submit" :loading="guardando">
              <Save class="w-4 h-4 mr-1" />
              Guardar
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { X, Edit, Save } from 'lucide-vue-next'
import Button from '@/components/common/Button.vue'
import apiClient from '@/api/axios'
import Swal from 'sweetalert2'

interface Props {
  idHistorial: number
  piezasDentales: any[]
  soloLectura?: boolean
}

interface Emits {
  (e: 'actualizado'): void
}

const props = withDefaults(defineProps<Props>(), {
  soloLectura: false
})
const emit = defineEmits<Emits>()

// Estado
const modalAbierto = ref(false)
const dienteSeleccionado = ref('')
const guardando = ref(false)

const formDiente = reactive({
  estado_pieza: 'sano',
  tratamiento_asociado: '',
  comentario: ''
})

// Computed properties
const ultimaActualizacion = computed(() => {
  if (props.piezasDentales.length === 0) return 'Sin registros'
  return new Date().toLocaleDateString('es-PE')
})

// Funciones para obtener colores
const obtenerColorDiente = (pieza: string) => {
  const registro = props.piezasDentales.find(p => p.pieza === pieza)
  if (!registro) return 'bg-green-100 border-green-400 hover:border-green-500'
  
  switch (registro.estado_pieza) {
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
      return 'bg-green-100 border-green-400 hover:border-green-500'
  }
}

const obtenerClaseEstado = (estado: string) => {
  switch (estado) {
    case 'sano':
      return 'bg-green-100 text-green-800'
    case 'cariado':
      return 'bg-red-100 text-red-800'
    case 'restaurado':
      return 'bg-blue-100 text-blue-800'
    case 'ausente':
      return 'bg-gray-100 text-gray-800'
    case 'protesis':
      return 'bg-purple-100 text-purple-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const obtenerLabelEstado = (estado: string) => {
  const labels = {
    sano: 'Sano',
    cariado: 'Cariado',
    restaurado: 'Restaurado',
    ausente: 'Ausente',
    protesis: 'Prótesis'
  }
  return labels[estado as keyof typeof labels] || estado
}

const tieneTratamiento = (pieza: string) => {
  const registro = props.piezasDentales.find(p => p.pieza === pieza)
  return registro && registro.tratamiento_asociado
}

const obtenerTipoDiente = (numeroDiente: number) => {
  const num = numeroDiente.toString()
  const ultimoDigito = parseInt(num.slice(-1))
  
  switch (ultimoDigito) {
    case 1:
    case 2:
      return 'Incisivo'
    case 3:
      return 'Canino'
    case 4:
    case 5:
      return 'Premolar'
    case 6:
    case 7:
    case 8:
      return 'Molar'
    default:
      return 'Diente'
  }
}

// Obtener información completa del diente para el tooltip
const obtenerEstadoInfo = (pieza: string) => {
  const registro = props.piezasDentales.find(p => p.pieza === pieza)
  
  if (!registro) {
    return {
      label: 'Sano',
      tratamiento: null,
      comentario: null
    }
  }
  
  return {
    label: obtenerLabelEstado(registro.estado_pieza),
    tratamiento: registro.tratamiento_asociado || null,
    comentario: registro.comentario || null
  }
}

// Funciones para obtener colores SVG - Realistic tooth colors
const obtenerColorFill = (pieza: string) => {
  const registro = props.piezasDentales.find(p => p.pieza === pieza)
  if (!registro) return 'fill-[#FFF8F0]'
  
  switch (registro.estado_pieza) {
    case 'sano':
      return 'fill-[#FFF8F0]'
    case 'cariado':
      return 'fill-red-200'
    case 'restaurado':
      return 'fill-primary-100'
    case 'ausente':
      return 'fill-gray-200'
    case 'protesis':
      return 'fill-violet-100'
    default:
      return 'fill-[#FFF8F0]'
  }
}

const obtenerColorStroke = (pieza: string) => {
  const registro = props.piezasDentales.find(p => p.pieza === pieza)
  if (!registro) return '#D4C5B0'
  
  switch (registro.estado_pieza) {
    case 'sano':
      return '#D4C5B0'
    case 'cariado':
      return '#ef4444'
    case 'restaurado':
      return '#3EA6E3'
    case 'ausente':
      return '#9ca3af'
    case 'protesis':
      return '#a78bfa'
    default:
      return '#D4C5B0'
  }
}

const obtenerColorRoot = (pieza: string) => {
  const registro = props.piezasDentales.find(p => p.pieza === pieza)
  if (!registro) return 'fill-[#FFF3E6]'
  if (registro.estado_pieza === 'ausente') return 'fill-gray-200'
  
  // La raíz siempre es más clara que la corona
  if (registro.estado_pieza === 'sano') return 'fill-[#FFF3E6]'
  return 'fill-white'
}

// Ancho SVG dinámico según tipo de diente
const getSVGWidth = (numeroDiente: number) => {
  const tipo = obtenerTipoDiente(numeroDiente)
  switch (tipo) {
    case 'Incisivo':
      return 40
    case 'Canino':
      return 44
    case 'Premolar':
      return 46
    case 'Molar':
      return 52
    default:
      return 48
  }
}

// Paths SVG para diferentes tipos de dientes
const getToothPath = (numeroDiente: number, arcada: 'superior' | 'inferior') => {
  const tipo = obtenerTipoDiente(numeroDiente)
  
  if (arcada === 'superior') {
    switch (tipo) {
      case 'Incisivo':
        // Incisivo superior: forma rectangular con borde redondeado
        return 'M 12 8 L 12 32 Q 12 36 16 36 L 32 36 Q 36 36 36 32 L 36 8 Q 36 4 32 4 L 16 4 Q 12 4 12 8 Z'
      case 'Canino':
        // Canino superior: forma puntiaguda
        return 'M 14 8 L 10 32 Q 10 36 14 36 L 34 36 Q 38 36 38 32 L 34 8 Q 32 4 24 2 Q 16 4 14 8 Z'
      case 'Premolar':
        // Premolar superior: forma con 2 cúspides
        return 'M 12 8 L 10 32 Q 10 36 14 36 L 34 36 Q 38 36 38 32 L 36 8 Q 36 6 34 5 L 28 4 L 24 6 L 20 4 L 14 5 Q 12 6 12 8 Z'
      case 'Molar':
        // Molar superior: forma grande con 4 cúspides
        return 'M 8 10 L 6 32 Q 6 36 10 36 L 38 36 Q 42 36 42 32 L 40 10 Q 40 8 38 7 L 32 5 L 28 4 L 24 5 L 20 4 L 16 5 L 10 7 Q 8 8 8 10 Z'
      default:
        return 'M 12 8 L 12 32 Q 12 36 16 36 L 32 36 Q 36 36 36 32 L 36 8 Q 36 4 32 4 L 16 4 Q 12 4 12 8 Z'
    }
  } else {
    // Arcada inferior (dientes invertidos)
    switch (tipo) {
      case 'Incisivo':
        return 'M 12 32 L 12 56 Q 12 60 16 60 L 32 60 Q 36 60 36 56 L 36 32 Q 36 28 32 28 L 16 28 Q 12 28 12 32 Z'
      case 'Canino':
        return 'M 14 32 L 10 56 Q 10 60 14 60 L 34 60 Q 38 60 38 56 L 34 32 Q 32 28 24 26 Q 16 28 14 32 Z'
      case 'Premolar':
        return 'M 12 32 L 10 56 Q 10 60 14 60 L 34 60 Q 38 60 38 56 L 36 32 Q 36 30 34 29 L 28 28 L 24 30 L 20 28 L 14 29 Q 12 30 12 32 Z'
      case 'Molar':
        return 'M 8 34 L 6 56 Q 6 60 10 60 L 38 60 Q 42 60 42 56 L 40 34 Q 40 32 38 31 L 32 29 L 28 28 L 24 29 L 20 28 L 16 29 L 10 31 Q 8 32 8 34 Z'
      default:
        return 'M 12 32 L 12 56 Q 12 60 16 60 L 32 60 Q 36 60 36 56 L 36 32 Q 36 28 32 28 L 16 28 Q 12 28 12 32 Z'
    }
  }
}

// Paths para las raíces de los dientes
const getRootPath = (numeroDiente: number, arcada: 'superior' | 'inferior') => {
  const tipo = obtenerTipoDiente(numeroDiente)
  
  if (arcada === 'superior') {
    switch (tipo) {
      case 'Incisivo':
        return 'M 16 36 L 18 50 Q 20 56 24 56 Q 28 56 30 50 L 32 36'
      case 'Canino':
        return 'M 14 36 L 18 52 Q 20 58 24 58 Q 28 58 30 52 L 34 36'
      case 'Premolar':
        return 'M 14 36 L 16 48 Q 18 54 22 54 M 24 54 Q 28 54 30 50 L 34 36'
      case 'Molar':
        return 'M 10 36 L 12 48 Q 14 54 18 54 M 24 36 L 24 52 M 30 54 Q 32 52 36 48 L 38 36'
      default:
        return 'M 16 36 L 18 50 Q 20 56 24 56 Q 28 56 30 50 L 32 36'
    }
  } else {
    // Raíces inferiores (en la parte superior del diente)
    switch (tipo) {
      case 'Incisivo':
        return 'M 16 28 L 18 14 Q 20 8 24 8 Q 28 8 30 14 L 32 28'
      case 'Canino':
        return 'M 14 28 L 18 12 Q 20 6 24 6 Q 28 6 30 12 L 34 28'
      case 'Premolar':
        return 'M 14 28 L 16 16 Q 18 10 22 10 M 24 10 Q 28 10 30 14 L 34 28'
      case 'Molar':
        return 'M 10 28 L 12 16 Q 14 10 18 10 M 24 28 L 24 12 M 30 10 Q 32 12 36 16 L 38 28'
      default:
        return 'M 16 28 L 18 14 Q 20 8 24 8 Q 28 8 30 14 L 32 28'
    }
  }
}

// Editar diente
const editarDiente = (pieza: string) => {
  if (props.soloLectura) return // No permitir edición en modo solo lectura

  dienteSeleccionado.value = pieza

  // Cargar datos existentes si hay
  const registro = props.piezasDentales.find(p => p.pieza === pieza)
  if (registro) {
    formDiente.estado_pieza = registro.estado_pieza
    formDiente.tratamiento_asociado = registro.tratamiento_asociado || ''
    formDiente.comentario = registro.comentario || ''
  } else {
    formDiente.estado_pieza = 'sano'
    formDiente.tratamiento_asociado = ''
    formDiente.comentario = ''
  }

  modalAbierto.value = true
}

const cerrarModal = () => {
  modalAbierto.value = false
  dienteSeleccionado.value = ''
}

const guardarDiente = async () => {
  guardando.value = true
  try {
    await apiClient.post('/clinica/odontograma', {
      id_historial: props.idHistorial,
      pieza: dienteSeleccionado.value,
      ...formDiente
    })

    Swal.fire('Éxito', 'Registro del diente guardado', 'success')
    emit('actualizado')
    cerrarModal()
  } catch (error) {
    console.error('Error al guardar diente:', error)
    Swal.fire('Error', 'No se pudo guardar el registro', 'error')
  } finally {
    guardando.value = false
  }
}
</script>

<style scoped>
.grid-cols-16 {
  grid-template-columns: repeat(16, minmax(0, 1fr));
}

/* Realistic tooth SVG styling */
.tooth-hover {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
  transition: all 0.2s ease;
}
.tooth-hover:hover {
  filter: drop-shadow(0 4px 8px rgba(14, 111, 181, 0.2));
}
</style>