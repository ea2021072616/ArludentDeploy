<template>
  <DashboardLayout>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
        <FileText class="w-8 h-8 text-indigo-600" />
        Reportes
      </h1>
      <p class="text-sm text-gray-600">Genera reportes predefinidos del sistema</p>
    </div>

    <!-- Filtro de Fechas Global -->
    <Card class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2">Fecha Inicio</label>
          <input
            v-model="filtrosGlobales.fecha_inicio"
            type="date"
            class="w-full px-3 py-2 border rounded-lg"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Fecha Fin</label>
          <input
            v-model="filtrosGlobales.fecha_fin"
            type="date"
            class="w-full px-3 py-2 border rounded-lg"
          />
        </div>
        <div class="flex items-end">
          <button
            @click="limpiarFiltros"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            <RefreshCw class="w-4 h-4 inline mr-2" />
            Limpiar Filtros
          </button>
        </div>
      </div>
    </Card>

    <!-- Grid de Reportes -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Reporte de Ingresos -->
      <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="verReporteIngresos">
        <div class="flex items-start gap-4">
          <div class="p-3 bg-green-100 rounded-lg">
            <DollarSign class="w-8 h-8 text-green-600" />
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold mb-1">Reporte de Ingresos</h3>
            <p class="text-sm text-gray-600 mb-3">
              Analiza ingresos totales, por forma de pago, tendencias diarias y top pacientes
            </p>
            <button class="text-sm text-green-600 font-medium hover:underline">
              Generar Reporte →
            </button>
          </div>
        </div>
      </Card>

      <!-- Reporte de Flujo de Clientes -->
      <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="verReporteFlujo">
        <div class="flex items-start gap-4">
          <div class="p-3 bg-blue-100 rounded-lg">
            <Users class="w-8 h-8 text-blue-600" />
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold mb-1">Flujo de Clientes</h3>
            <p class="text-sm text-gray-600 mb-3">
              Nuevos pacientes, activos, distribución demográfica y tendencias mensuales
            </p>
            <button class="text-sm text-blue-600 font-medium hover:underline">
              Generar Reporte →
            </button>
          </div>
        </div>
      </Card>

      <!-- Reporte de Citas -->
      <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="verReporteCitas">
        <div class="flex items-start gap-4">
          <div class="p-3 bg-purple-100 rounded-lg">
            <Calendar class="w-8 h-8 text-purple-600" />
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold mb-1">Reporte de Citas</h3>
            <p class="text-sm text-gray-600 mb-3">
              Estadísticas por estado, médico, especialidad y tendencias de agendamiento
            </p>
            <button class="text-sm text-purple-600 font-medium hover:underline">
              Generar Reporte →
            </button>
          </div>
        </div>
      </Card>

      <!-- Reporte Personalizado IA - Futuro -->
      <Card class="hover:shadow-lg transition-shadow opacity-60">
        <div class="flex items-start gap-4">
          <div class="p-3 bg-gradient-to-br from-pink-100 to-purple-100 rounded-lg">
            <Sparkles class="w-8 h-8 text-purple-600" />
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold mb-1 flex items-center gap-2">
              Reporte Personalizado IA
              <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">Próximamente</span>
            </h3>
            <p class="text-sm text-gray-600 mb-3">
              Genera reportes personalizados mediante prompts de IA
            </p>
            <button disabled class="text-sm text-gray-400 font-medium cursor-not-allowed">
              En desarrollo...
            </button>
          </div>
        </div>
      </Card>
    </div>

    <!-- Modal de Reporte -->
    <div
      v-if="modalReporte.visible"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="cerrarModal"
    >
      <div class="bg-white rounded-lg max-w-6xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b p-6 flex justify-between items-center">
          <h2 class="text-xl font-bold">{{ modalReporte.titulo }}</h2>
          <button @click="cerrarModal" class="text-gray-500 hover:text-gray-700">
            <X class="w-6 h-6" />
          </button>
        </div>
        <div class="p-6">
          <component :is="modalReporte.componente" :filtros="filtrosGlobales" />
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import { FileText, DollarSign, Users, Calendar, Sparkles, RefreshCw, X } from 'lucide-vue-next'
import ReporteIngresosDetalle from './ReporteIngresosDetalle.vue'
import ReporteFlujoDetalle from './ReporteFlujoDetalle.vue'
import ReporteCitasDetalle from './ReporteCitasDetalle.vue'

const filtrosGlobales = ref({
  fecha_inicio: '',
  fecha_fin: ''
})

const modalReporte = ref<{
  visible: boolean
  titulo: string
  componente: any
}>({
  visible: false,
  titulo: '',
  componente: null
})

const verReporteIngresos = () => {
  modalReporte.value = {
    visible: true,
    titulo: 'Reporte de Ingresos',
    componente: shallowRef(ReporteIngresosDetalle)
  }
}

const verReporteFlujo = () => {
  modalReporte.value = {
    visible: true,
    titulo: 'Reporte de Flujo de Clientes',
    componente: shallowRef(ReporteFlujoDetalle)
  }
}

const verReporteCitas = () => {
  modalReporte.value = {
    visible: true,
    titulo: 'Reporte de Citas',
    componente: shallowRef(ReporteCitasDetalle)
  }
}

const cerrarModal = () => {
  modalReporte.value.visible = false
}

const limpiarFiltros = () => {
  filtrosGlobales.value = {
    fecha_inicio: '',
    fecha_fin: ''
  }
}
</script>
