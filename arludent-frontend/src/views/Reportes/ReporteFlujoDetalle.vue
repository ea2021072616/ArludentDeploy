<template>
  <div>
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>
    <div v-else-if="datos">
      <!-- Resumen -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <Card>
          <p class="text-sm text-gray-600">Total Pacientes</p>
          <p class="text-2xl font-bold text-blue-600">{{ datos.resumen.total_pacientes }}</p>
        </Card>
        <Card>
          <p class="text-sm text-gray-600">Nuevos Pacientes</p>
          <p class="text-2xl font-bold text-green-600">{{ datos.resumen.nuevos_pacientes }}</p>
        </Card>
        <Card>
          <p class="text-sm text-gray-600">Pacientes Activos</p>
          <p class="text-2xl font-bold">{{ datos.resumen.pacientes_activos }}</p>
        </Card>
      </div>

      <!-- Distribución por Género -->
      <Card class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Distribución por Género</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div
            v-for="item in datos.distribucion_genero"
            :key="item.genero"
            class="text-center p-4 bg-blue-50 rounded-lg"
          >
            <p class="text-2xl font-bold text-blue-600">{{ item.total }}</p>
            <p class="text-sm text-gray-600 capitalize">{{ item.genero || 'No especificado' }}</p>
          </div>
        </div>
      </Card>

      <!-- Distribución por Edad -->
      <Card>
        <h3 class="text-lg font-semibold mb-4">Distribución por Rango de Edad</h3>
        <div class="space-y-2">
          <div
            v-for="item in datos.distribucion_edad"
            :key="item.rango"
            class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
          >
            <span class="font-medium">{{ item.rango }}</span>
            <span class="text-lg font-bold text-blue-600">{{ item.total }}</span>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import Card from '@/components/common/Card.vue'
import * as reportesService from '@/api/reportesService'
import type { ReporteFlujoClientes } from '@/api/reportesService'

interface Props {
  filtros: {
    fecha_inicio: string
    fecha_fin: string
  }
}

const props = defineProps<Props>()
const loading = ref(false)
const datos = ref<ReporteFlujoClientes | null>(null)

const cargarDatos = async () => {
  loading.value = true
  try {
    const filtrosLimpios: any = {}
    if (props.filtros.fecha_inicio) filtrosLimpios.fecha_inicio = props.filtros.fecha_inicio
    if (props.filtros.fecha_fin) filtrosLimpios.fecha_fin = props.filtros.fecha_fin

    datos.value = await reportesService.obtenerReporteFlujoClientes(filtrosLimpios)
  } catch (error) {
    console.error('Error al cargar reporte:', error)
  } finally {
    loading.value = false
  }
}

onMounted(cargarDatos)
watch(() => props.filtros, cargarDatos, { deep: true })
</script>
