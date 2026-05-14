<template>
  <div>
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
    </div>
    <div v-else-if="datos">
      <!-- Resumen -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <Card>
          <p class="text-sm text-gray-600">Total Ingresos</p>
          <p class="text-2xl font-bold text-green-600">S/ {{ Number(datos.resumen.total_ingresos || 0).toFixed(2) }}</p>
        </Card>
        <Card>
          <p class="text-sm text-gray-600">Cantidad de Pagos</p>
          <p class="text-2xl font-bold">{{ datos.resumen.cantidad_pagos || 0 }}</p>
        </Card>
        <Card>
          <p class="text-sm text-gray-600">Promedio por Pago</p>
          <p class="text-2xl font-bold">S/ {{ Number(datos.resumen.promedio_monto || 0).toFixed(2) }}</p>
        </Card>
      </div>

      <!-- Ingresos por Forma de Pago -->
      <Card class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Ingresos por Forma de Pago</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">Forma de Pago</th>
                <th class="px-4 py-2 text-right">Total</th>
                <th class="px-4 py-2 text-right">Cantidad</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in datos.por_forma_pago" :key="item.forma_pago">
                <td class="px-4 py-2 capitalize">{{ item.forma_pago }}</td>
                <td class="px-4 py-2 text-right font-semibold">S/ {{ Number(item.total || 0).toFixed(2) }}</td>
                <td class="px-4 py-2 text-right">{{ item.cantidad || 0 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Top Pacientes -->
      <Card>
        <h3 class="text-lg font-semibold mb-4">Top 5 Pacientes</h3>
        <div class="space-y-3">
          <div
            v-for="(item, index) in datos.top_pacientes"
            :key="item.id_paciente"
            class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center gap-3">
              <span class="text-lg font-bold text-gray-400">#{{ index + 1 }}</span>
              <div>
                <p class="font-medium">
                  {{ item.paciente.nombres }} {{ item.paciente.apellidos }}
                </p>
              </div>
            </div>
            <span class="text-lg font-bold text-green-600">S/ {{ Number(item.total_pagado || 0).toFixed(2) }}</span>
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
import type { ReporteIngresos } from '@/api/reportesService'

interface Props {
  filtros: {
    fecha_inicio: string
    fecha_fin: string
  }
}

const props = defineProps<Props>()
const loading = ref(false)
const datos = ref<ReporteIngresos | null>(null)

const cargarDatos = async () => {
  loading.value = true
  try {
    const filtrosLimpios: any = {}
    if (props.filtros.fecha_inicio) filtrosLimpios.fecha_inicio = props.filtros.fecha_inicio
    if (props.filtros.fecha_fin) filtrosLimpios.fecha_fin = props.filtros.fecha_fin

    datos.value = await reportesService.obtenerReporteIngresos(filtrosLimpios)
  } catch (error) {
    console.error('Error al cargar reporte:', error)
  } finally {
    loading.value = false
  }
}

onMounted(cargarDatos)
watch(() => props.filtros, cargarDatos, { deep: true })
</script>
