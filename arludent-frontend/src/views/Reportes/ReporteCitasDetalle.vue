<template>
  <div>
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
    </div>
    <div v-else-if="datos">
      <!-- Resumen -->
      <div class="mb-6">
        <Card>
          <p class="text-sm text-gray-600">Total de Citas</p>
          <p class="text-3xl font-bold text-purple-600">{{ datos.resumen.total_citas }}</p>
        </Card>
      </div>

      <!-- Citas por Estado -->
      <Card class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Citas por Estado</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div
            v-for="item in datos.por_estado"
            :key="item.estado"
            class="text-center p-4 rounded-lg"
            :class="getEstadoClass(item.estado)"
          >
            <p class="text-2xl font-bold">{{ item.total }}</p>
            <p class="text-sm capitalize">{{ formatearEstado(item.estado) }}</p>
          </div>
        </div>
      </Card>

      <!-- Citas por Médico -->
      <Card>
        <h3 class="text-lg font-semibold mb-4">Citas por Médico</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">Médico</th>
                <th class="px-4 py-2 text-left">Especialidad</th>
                <th class="px-4 py-2 text-right">Total Citas</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in datos.por_medico" :key="item.id_medico">
                <td class="px-4 py-2">
                  {{ item.medico.nombres }} {{ item.medico.apellidos }}
                </td>
                <td class="px-4 py-2">{{ item.medico.especialidad }}</td>
                <td class="px-4 py-2 text-right font-semibold">{{ item.total }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import Card from '@/components/common/Card.vue'
import * as reportesService from '@/api/reportesService'
import type { ReporteCitas } from '@/api/reportesService'

interface Props {
  filtros: {
    fecha_inicio: string
    fecha_fin: string
  }
}

const props = defineProps<Props>()
const loading = ref(false)
const datos = ref<ReporteCitas | null>(null)

const cargarDatos = async () => {
  loading.value = true
  try {
    const filtrosLimpios: any = {}
    if (props.filtros.fecha_inicio) filtrosLimpios.fecha_inicio = props.filtros.fecha_inicio
    if (props.filtros.fecha_fin) filtrosLimpios.fecha_fin = props.filtros.fecha_fin

    datos.value = await reportesService.obtenerReporteCitas(filtrosLimpios)
  } catch (error) {
    console.error('Error al cargar reporte:', error)
  } finally {
    loading.value = false
  }
}

const getEstadoClass = (estado: string) => {
  const clases: Record<string, string> = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    confirmada: 'bg-blue-100 text-blue-800',
    completada: 'bg-green-100 text-green-800',
    cancelada: 'bg-red-100 text-red-800',
    no_asistio: 'bg-gray-100 text-gray-800'
  }
  return clases[estado] || 'bg-gray-100 text-gray-800'
}

const formatearEstado = (estado: string) => {
  return estado.replace(/_/g, ' ')
}

onMounted(cargarDatos)
watch(() => props.filtros, cargarDatos, { deep: true })
</script>
