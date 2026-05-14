<template>
  <div class="calendario-mensual">
    <!-- Header con navegación -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-medium text-gray-900">
        {{ nombreMes }} {{ anioActual }}
      </h3>
      <div class="flex space-x-2">
        <button
          @click="mesAnterior"
          class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors"
        >
          <i class="fas fa-chevron-left"></i>
        </button>
        <button
          @click="hoySiguiente"
          class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          Hoy
        </button>
        <button
          @click="mesSiguiente"
          class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors"
        >
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Días de la semana -->
    <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-lg overflow-hidden mb-4">
      <div
        v-for="dia in diasSemana"
        :key="dia"
        class="bg-gray-50 p-3 text-center text-sm font-medium text-gray-700"
      >
        {{ dia }}
      </div>
    </div>

    <!-- Calendario -->
    <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-lg overflow-hidden">
      <div
        v-for="dia in diasCalendario"
        :key="`${dia.fecha}-${dia.mes}`"
        :class="[
          'bg-white min-h-[120px] p-2 relative',
          dia.esOtroMes ? 'bg-gray-50 text-gray-400' : '',
          dia.esHoy ? 'bg-blue-50' : '',
        ]"
      >
        <!-- Número del día -->
        <div class="flex items-center justify-between mb-2">
          <span
            :class="[
              'text-sm font-medium',
              dia.esHoy ? 'bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs' : '',
              dia.esOtroMes ? 'text-gray-400' : 'text-gray-900'
            ]"
          >
            {{ dia.numero }}
          </span>
          
          <!-- Indicador de fin de semana -->
          <span
            v-if="dia.esFinDeSemana"
            class="text-xs text-orange-600 font-medium"
          >
            <i class="fas fa-sun"></i>
          </span>
        </div>

        <!-- Disponibilidades del día -->
        <div class="space-y-1">
          <div
            v-for="disponibilidad in dia.disponibilidades"
            :key="disponibilidad.id"
            :class="[
              'text-xs p-1 rounded border cursor-pointer hover:opacity-80 transition-opacity',
              disponibilidad.tipo === 'horario'
                ? 'bg-green-100 border-green-200 text-green-800'
                : 'bg-red-100 border-red-200 text-red-800'
            ]"
            @click="$emit('editar-disponibilidad', disponibilidad.original)"
            :title="disponibilidad.titulo"
          >
            <div class="font-medium truncate">
              {{ disponibilidad.horas }}
            </div>
            <div v-if="disponibilidad.motivo" class="truncate opacity-75">
              {{ disponibilidad.motivo }}
            </div>
          </div>
        </div>

        <!-- Indicador de que hay más elementos -->
        <div
          v-if="dia.disponibilidades.length > 3"
          class="absolute bottom-1 right-1 text-xs text-gray-500 bg-gray-200 rounded px-1"
        >
          +{{ dia.disponibilidades.length - 3 }}
        </div>
      </div>
    </div>

    <!-- Leyenda -->
    <div class="mt-4 flex flex-wrap gap-4 text-sm">
      <div class="flex items-center">
        <div class="w-3 h-3 bg-green-100 border border-green-200 rounded mr-2"></div>
        <span>Horarios disponibles</span>
      </div>
      <div class="flex items-center">
        <div class="w-3 h-3 bg-red-100 border border-red-200 rounded mr-2"></div>
        <span>Bloqueos</span>
      </div>
      <div class="flex items-center">
        <div class="w-3 h-3 bg-blue-50 border border-blue-200 rounded mr-2"></div>
        <span>Día actual</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import type { DisponibilidadMedico } from '@/api/disponibilidadMedicoService'

interface DisponibilidadCalendario {
  id: number
  tipo: 'horario' | 'bloqueo'
  horas: string
  motivo: string | null
  titulo: string
  original: DisponibilidadMedico
}

interface DiaCalendario {
  numero: number
  fecha: string
  mes: number
  esOtroMes: boolean
  esHoy: boolean
  esFinDeSemana: boolean
  disponibilidades: DisponibilidadCalendario[]
}

// Props
const props = defineProps<{
  disponibilidades: DisponibilidadMedico[]
}>()

// Emits
const emit = defineEmits<{
  'editar-disponibilidad': [disponibilidad: DisponibilidadMedico]
  'mes-cambio': [fecha: Date]
}>()

// Estado
const fechaActual = ref(new Date())
const hoy = new Date()

// Computed
const anioActual = computed(() => fechaActual.value.getFullYear())
const mesActual = computed(() => fechaActual.value.getMonth())

const nombreMes = computed(() => {
  const meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ]
  return meses[mesActual.value]
})

const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const diasCalendario = computed((): DiaCalendario[] => {
  const dias: DiaCalendario[] = []
  const primerDia = new Date(anioActual.value, mesActual.value, 1)
  const ultimoDia = new Date(anioActual.value, mesActual.value + 1, 0)
  
  // Días del mes anterior para completar la primera semana
  const primerDiaSemana = primerDia.getDay()
  for (let i = primerDiaSemana - 1; i >= 0; i--) {
    const fecha = new Date(primerDia)
    fecha.setDate(fecha.getDate() - (i + 1))
    dias.push(crearDiaCalendario(fecha, true))
  }
  
  // Días del mes actual
  for (let dia = 1; dia <= ultimoDia.getDate(); dia++) {
    const fecha = new Date(anioActual.value, mesActual.value, dia)
    dias.push(crearDiaCalendario(fecha, false))
  }
  
  // Días del mes siguiente para completar la última semana
  const totalDias = dias.length
  const diasFaltantes = 42 - totalDias // 6 semanas × 7 días
  for (let i = 1; i <= diasFaltantes; i++) {
    const fecha = new Date(anioActual.value, mesActual.value + 1, i)
    dias.push(crearDiaCalendario(fecha, true))
  }
  
  return dias.slice(0, 42) // Máximo 6 semanas
})

// Métodos
const crearDiaCalendario = (fecha: Date, esOtroMes: boolean): DiaCalendario => {
  const fechaStr = fecha.toISOString().split('T')[0] || ''
  const diaSemana = fecha.getDay()
  
  return {
    numero: fecha.getDate(),
    fecha: fechaStr,
    mes: fecha.getMonth(),
    esOtroMes,
    esHoy: esMismaFecha(fecha, hoy),
    esFinDeSemana: diaSemana === 0 || diaSemana === 6,
    disponibilidades: obtenerDisponibilidadesDia(fecha)
  }
}

const esMismaFecha = (fecha1: Date, fecha2: Date): boolean => {
  return fecha1.getFullYear() === fecha2.getFullYear() &&
         fecha1.getMonth() === fecha2.getMonth() &&
         fecha1.getDate() === fecha2.getDate()
}

const obtenerDisponibilidadesDia = (fecha: Date): DisponibilidadCalendario[] => {
  const fechaStr = fecha.toISOString().split('T')[0]
  const diaSemana = fecha.getDay()
  const disponibilidadesDia: DisponibilidadCalendario[] = []
  
  props.disponibilidades.forEach(disp => {
    let incluir = false
    
    // Horarios semanales recurrentes
    if (disp.dia_semana === diaSemana && !disp.fecha_inicio && !disp.fecha_fin) {
      incluir = true
    }
    
    // Horarios específicos por fechas
    if (disp.fecha_inicio && disp.fecha_fin) {
      // Ahora las fechas vienen como YYYY-MM-DD desde el backend
      const fechaActualStr = fechaStr
      
      if (fechaActualStr && fechaActualStr >= disp.fecha_inicio && fechaActualStr <= disp.fecha_fin) {
        incluir = true
      }
    }
    
    if (incluir && disp.hora_inicio && disp.hora_fin) {
      disponibilidadesDia.push({
        id: disp.id_disp,
        tipo: disp.tipo,
        horas: `${disp.hora_inicio.substring(0, 5)} - ${disp.hora_fin.substring(0, 5)}`,
        motivo: disp.motivo,
        titulo: `${disp.tipo === 'horario' ? 'Horario' : 'Bloqueo'}: ${disp.hora_inicio.substring(0, 5)} - ${disp.hora_fin.substring(0, 5)}${disp.motivo ? ` (${disp.motivo})` : ''}`,
        original: disp
      })
    }
  })
  
  return disponibilidadesDia.sort((a, b) => a.horas.localeCompare(b.horas))
}

const mesAnterior = () => {
  fechaActual.value = new Date(anioActual.value, mesActual.value - 1, 1)
  emit('mes-cambio', fechaActual.value)
}

const mesSiguiente = () => {
  fechaActual.value = new Date(anioActual.value, mesActual.value + 1, 1)
  emit('mes-cambio', fechaActual.value)
}

const hoySiguiente = () => {
  fechaActual.value = new Date()
  emit('mes-cambio', fechaActual.value)
}

// Watchers
watch(() => props.disponibilidades, () => {
  // Re-calcular cuando cambien las disponibilidades
}, { deep: true })

// Lifecycle
onMounted(() => {
  emit('mes-cambio', fechaActual.value)
})
</script>

<style scoped>
.calendario-mensual {
  width: 100%;
}

/* Mejorar la visualización en pantallas pequeñas */
@media (max-width: 768px) {
  .calendario-mensual .grid-cols-7 > div {
    min-height: 80px;
  }
  
  .calendario-mensual .text-xs {
    font-size: 10px;
  }
}
</style>