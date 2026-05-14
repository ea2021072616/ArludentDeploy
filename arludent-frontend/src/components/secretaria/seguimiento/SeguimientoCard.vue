<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="getEstadoColor(seguimiento.estado)">
          <component :is="getEstadoIcon(seguimiento.estado)" class="w-5 h-5" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">
            {{ seguimiento.paciente.nombres }} {{ seguimiento.paciente.apellidos }}
          </h3>
          <p class="text-sm text-gray-600">{{ seguimiento.paciente.numero_documento }}</p>
        </div>
      </div>
      <div class="flex items-center space-x-1">
        <Button
          size="sm"
          variant="outline"
          @click="$emit('ver-detalle', seguimiento)"
        >
          <Eye class="w-4 h-4" />
        </Button>
        <Button
          size="sm"
          variant="outline"
          @click="$emit('editar', seguimiento)"
        >
          <Edit class="w-4 h-4" />
        </Button>
      </div>
    </div>

    <!-- Información principal -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700">Tipo:</span>
        <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getTipoColor(seguimiento.tipo_seguimiento)">
          {{ getTipoText(seguimiento.tipo_seguimiento) }}
        </span>
      </div>

      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700">Fecha:</span>
        <span class="text-sm text-gray-900">
          {{ formatearFecha(seguimiento.fecha_seguimiento) }}
        </span>
      </div>

      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700">Estado:</span>
        <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getEstadoBadgeColor(seguimiento.estado)">
          {{ getEstadoText(seguimiento.estado) }}
        </span>
      </div>

      <!-- Próxima fecha si existe -->
      <div v-if="seguimiento.proxima_fecha" class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700">Próxima:</span>
        <span class="text-sm text-gray-900">
          {{ formatearFecha(seguimiento.proxima_fecha) }}
        </span>
      </div>

      <!-- Notas si existen -->
      <div v-if="seguimiento.notas" class="pt-2 border-t border-gray-100">
        <p class="text-sm text-gray-700">
          <span class="font-medium">Notas:</span>
          {{ seguimiento.notas.length > 100 ? seguimiento.notas.substring(0, 100) + '...' : seguimiento.notas }}
        </p>
      </div>

      <!-- Resultado si existe -->
      <div v-if="seguimiento.resultado" class="pt-2 border-t border-gray-100">
        <p class="text-sm text-gray-700">
          <span class="font-medium">Resultado:</span>
          {{ seguimiento.resultado.length > 100 ? seguimiento.resultado.substring(0, 100) + '...' : seguimiento.resultado }}
        </p>
      </div>
    </div>

    <!-- Acciones -->
    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
      <div class="text-xs text-gray-500">
        Creado {{ formatearFechaRelativa(seguimiento.created_at) }}
      </div>
      <div class="flex space-x-2">
        <Button
          v-if="seguimiento.estado === 'pendiente'"
          size="sm"
          variant="outline"
          @click="$emit('marcar-contactado', seguimiento)"
        >
          <Phone class="w-4 h-4 mr-1" />
          Contactar
        </Button>
        <Button
          v-if="seguimiento.estado !== 'completado' && seguimiento.estado !== 'cancelado'"
          size="sm"
          variant="primary"
          @click="$emit('completar', seguimiento)"
        >
          <CheckCircle class="w-4 h-4 mr-1" />
          Completar
        </Button>
      </div>
    </div>

    <!-- Indicador de vencimiento -->
    <div v-if="esVencido" class="mt-3 flex items-center text-red-600">
      <AlertTriangle class="w-4 h-4 mr-1" />
      <span class="text-xs font-medium">Vencido desde {{ getDiasVencido() }} días</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Button from '@/components/common/Button.vue'
import type { SeguimientoTratamiento } from '@/api/seguimientoService'
import {
  Eye, Edit, Phone, CheckCircle, AlertTriangle, Clock, User, Calendar, FileText
} from 'lucide-vue-next'

interface Props {
  seguimiento: SeguimientoTratamiento
}

const props = defineProps<Props>()

// Eventos
defineEmits<{
  'ver-detalle': [seguimiento: SeguimientoTratamiento]
  'marcar-contactado': [seguimiento: SeguimientoTratamiento]
  'completar': [seguimiento: SeguimientoTratamiento]
  'editar': [seguimiento: SeguimientoTratamiento]
}>()

// Computed
const esVencido = computed(() => {
  const fechaSeguimiento = new Date(props.seguimiento.fecha_seguimiento)
  const hoy = new Date()
  return fechaSeguimiento < hoy && props.seguimiento.estado === 'pendiente'
})

// Métodos
const getEstadoColor = (estado: string) => {
  const colores: Record<string, string> = {
    pendiente: 'bg-orange-100 text-orange-600',
    contactado: 'bg-blue-100 text-blue-600',
    completado: 'bg-green-100 text-green-600',
    cancelado: 'bg-red-100 text-red-600'
  }
  return colores[estado] || 'bg-gray-100 text-gray-600'
}

const getEstadoBadgeColor = (estado: string) => {
  const colores: Record<string, string> = {
    pendiente: 'bg-orange-100 text-orange-800',
    contactado: 'bg-blue-100 text-blue-800',
    completado: 'bg-green-100 text-green-800',
    cancelado: 'bg-red-100 text-red-800'
  }
  return colores[estado] || 'bg-gray-100 text-gray-800'
}

const getEstadoIcon = (estado: string) => {
  const iconos: Record<string, any> = {
    pendiente: Clock,
    contactado: Phone,
    completado: CheckCircle,
    cancelado: AlertTriangle
  }
  return iconos[estado] || User
}

const getEstadoText = (estado: string) => {
  const textos: Record<string, string> = {
    pendiente: 'Pendiente',
    contactado: 'Contactado',
    completado: 'Completado',
    cancelado: 'Cancelado'
  }
  return textos[estado] || estado
}

const getTipoColor = (tipo: string) => {
  const colores: Record<string, string> = {
    control: 'bg-blue-100 text-blue-800',
    revision: 'bg-purple-100 text-purple-800',
    medicacion: 'bg-yellow-100 text-yellow-800',
    otro: 'bg-gray-100 text-gray-800'
  }
  return colores[tipo] || 'bg-gray-100 text-gray-800'
}

const getTipoText = (tipo: string) => {
  const textos: Record<string, string> = {
    control: 'Control',
    revision: 'Revisión',
    medicacion: 'Medicación',
    otro: 'Otro'
  }
  return textos[tipo] || tipo
}

const formatearFecha = (fecha: string) => {
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatearFechaRelativa = (fecha: string) => {
  const fechaObj = new Date(fecha)
  const ahora = new Date()
  const diferencia = Math.floor((ahora.getTime() - fechaObj.getTime()) / (1000 * 60 * 60 * 24))
  
  if (diferencia === 0) return 'hoy'
  if (diferencia === 1) return 'ayer'
  if (diferencia < 7) return `hace ${diferencia} días`
  if (diferencia < 30) return `hace ${Math.floor(diferencia / 7)} semanas`
  if (diferencia < 365) return `hace ${Math.floor(diferencia / 30)} meses`
  return `hace ${Math.floor(diferencia / 365)} años`
}

const getDiasVencido = () => {
  const fechaSeguimiento = new Date(props.seguimiento.fecha_seguimiento)
  const hoy = new Date()
  return Math.floor((hoy.getTime() - fechaSeguimiento.getTime()) / (1000 * 60 * 60 * 24))
}
</script>