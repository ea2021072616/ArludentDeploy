<template>
  <DashboardLayout>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
        <Shield class="w-8 h-8 text-blue-600" />
        Auditoría del Sistema
      </h1>
      <p class="text-sm text-gray-600">Registro de actividades del sistema</p>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Logs</p>
            <p class="text-2xl font-bold">{{ estadisticas?.total_logs || 0 }}</p>
          </div>
          <Activity class="w-10 h-10 text-blue-500" />
        </div>
      </Card>
      <Card>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Hoy</p>
            <p class="text-2xl font-bold">{{ logsHoy }}</p>
          </div>
          <Calendar class="w-10 h-10 text-green-500" />
        </div>
      </Card>
      <Card>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Módulos</p>
            <p class="text-2xl font-bold">{{ estadisticas?.modulos_afectados?.length || 0 }}</p>
          </div>
          <Database class="w-10 h-10 text-purple-500" />
        </div>
      </Card>
      <Card>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Usuarios</p>
            <p class="text-2xl font-bold">{{ estadisticas?.usuarios_activos?.length || 0 }}</p>
          </div>
          <Users class="w-10 h-10 text-orange-500" />
        </div>
      </Card>
    </div>

    <!-- Filtros -->
    <Card class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-2">
          <label class="block text-sm font-medium mb-2">Buscar</label>
          <input
            v-model="filtros.search"
            type="text"
            placeholder="Buscar..."
            class="w-full px-3 py-2 border rounded-lg"
            @input="buscarConDebounce"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Acción</label>
          <select v-model="filtros.accion" class="w-full px-3 py-2 border rounded-lg" @change="cargarLogs">
            <option value="">Todas</option>
            <option v-for="a in acciones" :key="a" :value="a">{{ formatearAccion(a) }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Módulo</label>
          <select v-model="filtros.modulo" class="w-full px-3 py-2 border rounded-lg" @change="cargarLogs">
            <option value="">Todos</option>
            <option v-for="m in modulos" :key="m" :value="m">{{ formatearModulo(m) }}</option>
          </select>
        </div>
      </div>
    </Card>

    <!-- Tabla -->
    <Card>
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      </div>
      <div v-else-if="logs.length === 0" class="text-center py-12">
        <FileText class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <p class="text-gray-600">No hay logs</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="log in logs" :key="log.id_log" class="hover:bg-gray-50">
              <td class="px-4 py-3 whitespace-nowrap text-sm">{{ formatearFecha(log.fecha_hora) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-sm">
                <div v-if="log.usuario">
                  <p class="font-medium">{{ log.usuario.username }}</p>
                  <p class="text-gray-500 text-xs">{{ log.usuario.correo }}</p>
                </div>
                <span v-else class="text-gray-400 italic">Sistema</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span :class="getAccionClass(log.accion)">{{ formatearAccion(log.accion) }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm">{{ formatearModulo(log.modulo_afectado) }}</td>
              <td class="px-4 py-3 text-sm max-w-md truncate">{{ log.descripcion }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-4 flex justify-between border-t pt-4">
        <div class="text-sm text-gray-700">
          {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }}
        </div>
        <div class="flex gap-2">
          <button
            :disabled="pagination.current_page === 1"
            @click="cambiarPagina(pagination.current_page - 1)"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Anterior
          </button>
          <button
            :disabled="pagination.current_page === pagination.last_page"
            @click="cambiarPagina(pagination.current_page + 1)"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Siguiente
          </button>
        </div>
      </div>
    </Card>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import { Shield, Activity, Calendar, Database, Users, FileText } from 'lucide-vue-next'
import * as auditoriaService from '@/api/auditoriaService'
import type { LogActividad, PaginacionLogs, FiltrosAuditoria, EstadisticasAuditoria } from '@/api/auditoriaService'
import Swal from 'sweetalert2'

const logs = ref<LogActividad[]>([])
const pagination = ref<PaginacionLogs | null>(null)
const loading = ref(false)
const acciones = ref<string[]>([])
const modulos = ref<string[]>([])
const estadisticas = ref<EstadisticasAuditoria | null>(null)
const filtros = ref<FiltrosAuditoria>({ page: 1, per_page: 25, search: '', accion: '', modulo: '' })
let debounceTimer: ReturnType<typeof setTimeout> | null = null

const logsHoy = computed(() => {
  const hoy = new Date().toISOString().split('T')[0]
  return estadisticas.value?.actividad_diaria?.find((d) => d.fecha === hoy)?.total || 0
})

const cargarLogs = async () => {
  loading.value = true
  try {
    // Limpiar filtros vacíos antes de enviar
    const filtrosLimpios: FiltrosAuditoria = {
      page: filtros.value.page,
      per_page: filtros.value.per_page
    }

    if (filtros.value.search && filtros.value.search.trim()) {
      filtrosLimpios.search = filtros.value.search.trim()
    }
    if (filtros.value.accion && filtros.value.accion.trim()) {
      filtrosLimpios.accion = filtros.value.accion
    }
    if (filtros.value.modulo && filtros.value.modulo.trim()) {
      filtrosLimpios.modulo = filtros.value.modulo
    }

    const response = await auditoriaService.obtenerLogs(filtrosLimpios)
    logs.value = response.logs
    pagination.value = response.pagination
  } catch (error: any) {
    Swal.fire({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'Error al cargar logs' })
  } finally {
    loading.value = false
  }
}

const cargarFiltros = async () => {
  try {
    const [accionesData, modulosData] = await Promise.all([
      auditoriaService.obtenerAcciones(),
      auditoriaService.obtenerModulos()
    ])
    acciones.value = accionesData
    modulos.value = modulosData
  } catch (error) {
    console.error('Error al cargar filtros:', error)
  }
}

const cargarEstadisticas = async () => {
  try {
    estadisticas.value = await auditoriaService.obtenerEstadisticas()
  } catch (error) {
    console.error('Error al cargar estadísticas:', error)
  }
}

const buscarConDebounce = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    filtros.value.page = 1
    cargarLogs()
  }, 500)
}

const cambiarPagina = (pagina: number) => {
  filtros.value.page = pagina
  cargarLogs()
}

const formatearFecha = (fecha: string) => {
  return new Date(fecha).toLocaleString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatearAccion = (accion: string | null) => {
  if (!accion) return '-'
  return accion.replace(/_/g, ' ').split(' ').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const formatearModulo = (modulo: string | null) => {
  if (!modulo) return '-'
  return modulo.replace(/_/g, ' ').split(' ').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const getAccionClass = (accion: string) => {
  const clases: Record<string, string> = {
    creado: 'bg-green-100 text-green-800',
    actualizado: 'bg-blue-100 text-blue-800',
    eliminado: 'bg-red-100 text-red-800',
    login: 'bg-purple-100 text-purple-800',
    logout: 'bg-gray-100 text-gray-800',
    mfa: 'bg-yellow-100 text-yellow-800',
    password: 'bg-orange-100 text-orange-800'
  }
  for (const [key, value] of Object.entries(clases)) {
    if (accion.toLowerCase().includes(key)) {
      return `px-2 py-1 text-xs font-medium rounded-full ${value}`
    }
  }
  return 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800'
}

onMounted(() => {
  cargarLogs()
  cargarFiltros()
  cargarEstadisticas()
})
</script>
