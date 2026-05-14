<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Mis Pagos</h1>
          <p class="text-gray-600 mt-1">Historial de tus pagos realizados</p>
        </div>
      </div>

      <!-- Filtros -->
      <Card class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select
              v-model="filtros.estado"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              @change="aplicarFiltros"
            >
              <option value="">Todos</option>
              <option value="pagado">Pagado</option>
              <option value="pendiente">Pendiente</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Método</label>
            <select
              v-model="filtros.metodo"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              @change="aplicarFiltros"
            >
              <option value="">Todos</option>
              <option value="efectivo">Efectivo</option>
              <option value="tarjeta">Tarjeta</option>
              <option value="transferencia">Transferencia</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
            <input
              v-model="filtros.desde"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              @change="aplicarFiltros"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
            <input
              v-model="filtros.hasta"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              @change="aplicarFiltros"
            >
          </div>
        </div>

        <div class="flex items-center justify-between mt-4">
          <div class="flex items-center space-x-2">
            <InputField
              v-model="filtros.concepto"
              placeholder="Buscar por concepto..."
              class="w-64"
              @input="debouncedAplicarFiltros"
            />
            <Button
              v-if="hayFiltrosActivos"
              variant="outline"
              size="sm"
              @click="limpiarFiltros"
            >
              <X class="w-4 h-4" />
              Limpiar
            </Button>
          </div>

          <div class="text-sm text-gray-600">
            {{ pagosFiltrados.length }} de {{ pagos.length }} pagos
          </div>
        </div>
      </Card>

      <!-- Estadísticas -->
      <div v-if="estadisticas" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Pagos</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.total }}</p>
            </div>
            <div class="p-3 bg-blue-100 rounded-lg">
              <Receipt class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Monto Total</p>
              <p class="text-2xl font-bold text-green-600">S/ {{ estadisticas.totalMonto.toFixed(2) }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-lg">
              <DollarSign class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>

        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Pagados</p>
              <p class="text-2xl font-bold text-green-600">{{ estadisticas.pagados }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-lg">
              <CheckCircle class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>

        <Card class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Pendientes</p>
              <p class="text-2xl font-bold text-orange-600">{{ estadisticas.pendientes }}</p>
            </div>
            <div class="p-3 bg-orange-100 rounded-lg">
              <Clock class="w-6 h-6 text-orange-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Loading State -->
      <Card v-if="cargando" class="p-12">
        <div class="text-center">
          <div class="spinner w-8 h-8 mx-auto mb-4"></div>
          <p class="text-gray-600">Cargando pagos...</p>
        </div>
      </Card>

      <!-- Lista de Pagos -->
      <div v-else-if="pagosFiltrados.length > 0" class="space-y-4">
        <Card
          v-for="pago in pagosFiltrados"
          :key="pago.id_pago"
          class="p-6 hover:shadow-md transition-shadow"
        >
          <div>
            <div class="flex items-start space-x-4 mb-4">
              <div
                :class="[
                  'p-2 rounded-lg flex-shrink-0',
                  pago.estado_pago === 'pagado' ? 'bg-green-100' : 'bg-orange-100'
                ]"
              >
                <CheckCircle
                  v-if="pago.estado_pago === 'pagado'"
                  class="w-5 h-5 text-green-600"
                />
                <Clock
                  v-else
                  class="w-5 h-5 text-orange-600"
                />
              </div>
              <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-lg font-semibold text-gray-900">{{ pago.concepto }}</h3>
                  <div class="flex items-center space-x-2">
                    <span
                      :class="[
                        'px-3 py-1 rounded-full text-sm font-medium',
                        pago.estado_pago === 'pagado'
                          ? 'bg-green-100 text-green-700'
                          : 'bg-orange-100 text-orange-700'
                      ]"
                    >
                      {{ pago.estado_pago === 'pagado' ? 'Pagado' : 'Pendiente' }}
                    </span>
                    <Button
                      v-if="pago.estado_pago === 'pagado'"
                      variant="outline"
                      size="sm"
                      @click="descargarPDFPago(pago)"
                    >
                      <FileText class="w-4 h-4 mr-1" />
                      PDF
                    </Button>
                  </div>
                </div>

                <!-- Información en 3 columnas -->
                <div class="grid grid-cols-3 gap-4 mb-4">
                  <div class="text-center">
                    <div class="flex items-center justify-center space-x-2 text-sm text-gray-600 mb-1">
                      <Calendar class="w-4 h-4 text-gray-400" />
                      <span class="font-medium">Fecha</span>
                    </div>
                    <div class="text-sm text-gray-900">{{ formatearFecha(pago.fecha_pago || pago.created_at) }}</div>
                  </div>

                  <div class="text-center">
                    <div class="flex items-center justify-center space-x-2 text-sm text-gray-600 mb-1">
                      <CreditCard class="w-4 h-4 text-gray-400" />
                      <span class="font-medium">Método</span>
                    </div>
                    <div class="text-sm text-gray-900 capitalize">{{ pago.metodo_pago }}</div>
                  </div>

                  <div class="text-center">
                    <div class="flex items-center justify-center space-x-2 text-sm text-gray-600 mb-1">
                      <DollarSign class="w-4 h-4 text-gray-400" />
                      <span class="font-medium">Monto</span>
                    </div>
                    <div class="text-lg font-bold text-green-600">S/ {{ (pago.monto || 0).toFixed(2) }}</div>
                  </div>
                </div>

                <!-- Notas centradas -->
                <div v-if="pago.notas" class="text-center">
                  <div class="inline-block p-3 bg-gray-50 rounded-lg max-w-md">
                    <div class="flex items-start space-x-2">
                      <FileText class="w-4 h-4 text-gray-600 flex-shrink-0 mt-0.5" />
                      <div class="text-left">
                        <p class="text-sm font-medium text-gray-900">Notas:</p>
                        <p class="text-sm text-gray-700 mt-1">{{ pago.notas }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Empty State -->
      <Card v-else class="p-12 text-center">
        <Receipt class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          {{ hayFiltrosActivos ? 'No se encontraron pagos' : 'No hay pagos' }}
        </h3>
        <p class="text-gray-600">
          {{ hayFiltrosActivos ? 'Prueba ajustando los filtros de búsqueda' : 'Aún no has realizado ningún pago' }}
        </p>
        <Button
          v-if="hayFiltrosActivos"
          variant="outline"
          class="mt-4"
          @click="limpiarFiltros"
        >
          Limpiar filtros
        </Button>
      </Card>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import {
  Receipt,
  DollarSign,
  CheckCircle,
  Clock,
  Calendar,
  CreditCard,
  FileText,
  X
} from 'lucide-vue-next'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import InputField from '@/components/common/InputField.vue'
import pagosService, { type Pago } from '@/api/pagosService'

const cargando = ref(true)
const pagos = ref<Pago[]>([])

// Filtros
const filtros = ref({
  estado: '',
  metodo: '',
  desde: '',
  hasta: '',
  concepto: ''
})

// Pagos filtrados
const pagosFiltrados = computed(() => {
  let filtrados = pagos.value

  // Filtro por estado
  if (filtros.value.estado) {
    filtrados = filtrados.filter(p => p.estado_pago === filtros.value.estado)
  }

  // Filtro por método
  if (filtros.value.metodo) {
    filtrados = filtrados.filter(p => p.metodo_pago === filtros.value.metodo)
  }

  // Filtro por fecha desde
  if (filtros.value.desde) {
    const fechaDesde = new Date(filtros.value.desde)
    filtrados = filtrados.filter(p => {
      const fechaStr = p.fecha_pago || p.created_at || ''
      if (!fechaStr) return false
      const fechaPago = new Date(fechaStr)
      return fechaPago >= fechaDesde
    })
  }

  // Filtro por fecha hasta
  if (filtros.value.hasta) {
    const fechaHasta = new Date(filtros.value.hasta)
    fechaHasta.setHours(23, 59, 59, 999) // Fin del día
    filtrados = filtrados.filter(p => {
      const fechaStr = p.fecha_pago || p.created_at || ''
      if (!fechaStr) return false
      const fechaPago = new Date(fechaStr)
      return fechaPago <= fechaHasta
    })
  }

  // Filtro por concepto
  if (filtros.value.concepto.trim()) {
    const termino = filtros.value.concepto.toLowerCase()
    filtrados = filtrados.filter(p =>
      p.concepto.toLowerCase().includes(termino)
    )
  }

  return filtrados
})

// Estadísticas computadas (debe estar DESPUÉS de pagosFiltrados)
const estadisticas = computed(() => {
  const pagosParaStats = pagosFiltrados.value
  if (!pagosParaStats || pagosParaStats.length === 0) return null

  const total = pagosParaStats.length
  const totalMonto = pagosParaStats.reduce((sum, p) => sum + (p.monto || 0), 0)
  const pagados = pagosParaStats.filter(p => p.estado_pago === 'pagado').length
  const pendientes = total - pagados

  return {
    total,
    totalMonto,
    pagados,
    pendientes
  }
})

// Verificar si hay filtros activos
const hayFiltrosActivos = computed(() => {
  return filtros.value.estado ||
         filtros.value.metodo ||
         filtros.value.desde ||
         filtros.value.hasta ||
         filtros.value.concepto.trim()
})

// Debounce para búsqueda
let debounceTimer: NodeJS.Timeout | null = null
const debouncedAplicarFiltros = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    // La búsqueda se actualiza automáticamente por el computed
  }, 300)
}

const aplicarFiltros = () => {
  // Los filtros se aplican automáticamente por el computed
}

const limpiarFiltros = () => {
  filtros.value = {
    estado: '',
    metodo: '',
    desde: '',
    hasta: '',
    concepto: ''
  }
}

const cargarPagos = async () => {
  cargando.value = true
  try {
    const res = await pagosService.obtenerMisPagos({ per_page: 50 })

    if (res.success && res.data.pagos) {
      pagos.value = res.data.pagos
    } else {
      pagos.value = []
    }
  } catch (err: any) {
    console.error('❌ Error cargando pagos:', err)
    pagos.value = []
  } finally {
    cargando.value = false
  }
}

const formatearFecha = (fecha?: string) => {
  if (!fecha) return 'N/A'
  return format(new Date(fecha), "d 'de' MMMM 'de' yyyy", { locale: es })
}

/**
 * Descargar PDF de un pago individual (boleta o factura)
 * Usa la misma lógica que CajaPagosView
 */
const descargarPDFPago = async (pago: Pago) => {
  try {
    // Determinar tipo de comprobante (usar el tipo guardado en el pago si existe, sino usar boleta por defecto)
    const tipo = pago.tipo_comprobante === 'factura' ? 'factura' : 'boleta'

    // Solicitar al backend que genere/stream el PDF y devolver blob
    const resp = await pagosService.descargarPDFPago(pago.id_pago, { tipo })

    if (resp && resp.data) {
      const blob = new Blob([resp.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      window.open(url, '_blank')
    } else {
      alert('Error al generar/descargar el PDF')
    }
  } catch (error: any) {
    console.error('Error al generar PDF:', error)
    const errorMsg = error?.response?.data?.message || 'Error al generar el PDF'
    alert(errorMsg)
  }
}

onMounted(() => {
  cargarPagos()
})
</script>
