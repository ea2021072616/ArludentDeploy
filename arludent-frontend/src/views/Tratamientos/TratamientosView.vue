<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Stethoscope, Search, Plus, Edit, ToggleLeft, ToggleRight } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import tratamientoService, { type Tratamiento } from '@/api/tratamientoService'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const router = useRouter()

// Estado
const tratamientos = ref<Tratamiento[]>([])
const loading = ref(false)
const busqueda = ref('')
const categoriaFiltro = ref('')
const estadoFiltro = ref<'' | 'activo' | 'inactivo'>('')

// Computadas
const tratamientosFiltrados = computed(() => {
  let resultado = tratamientos.value

  // Filtro local adicional por búsqueda
  if (busqueda.value) {
    const query = busqueda.value.toLowerCase()
    resultado = resultado.filter(t =>
      t.nombre.toLowerCase().includes(query) ||
      t.categoria?.toLowerCase().includes(query) ||
      t.descripcion?.toLowerCase().includes(query)
    )
  }

  return resultado
})

const hayTratamientos = computed(() => tratamientosFiltrados.value.length > 0)

// Métodos
const cargarTratamientos = async () => {
  loading.value = true
  try {
    const response = await tratamientoService.listarTratamientos({
      categoria: categoriaFiltro.value || undefined,
      estado: estadoFiltro.value || undefined,
      per_page: 'all'
    })

    tratamientos.value = response.data
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al cargar tratamientos'
    })
  } finally {
    loading.value = false
  }
}

const crearTratamiento = () => {
  router.push({ name: 'admin-tratamientos-crear' })
}

const editarTratamiento = (id: number) => {
  router.push({ name: 'admin-tratamientos-editar', params: { id } })
}

const cambiarEstado = async (tratamiento: Tratamiento) => {
  const nuevoEstado = tratamiento.estado === 'activo' ? 'inactivo' : 'activo'
  const accion = nuevoEstado === 'activo' ? 'activar' : 'inactivar'

  const result = await Swal.fire({
    title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} tratamiento?`,
    html: `
      <p class="text-sm text-gray-600 mb-2">Tratamiento: <strong>${tratamiento.nombre}</strong></p>
      <p class="text-sm text-gray-500">Estado actual: <strong>${tratamiento.estado}</strong></p>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: `Sí, ${accion}`,
    cancelButtonText: 'Cancelar',
    confirmButtonColor: nuevoEstado === 'activo' ? '#10b981' : '#ef4444'
  })

  if (result.isConfirmed) {
    try {
      await tratamientoService.cambiarEstado(tratamiento.id_tratamiento, nuevoEstado)

      await Swal.fire({
        icon: 'success',
        title: 'Estado actualizado',
        text: `El tratamiento ha sido ${nuevoEstado === 'activo' ? 'activado' : 'inactivado'} correctamente`,
        timer: 2000
      })

      cargarTratamientos()
    } catch (error: any) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.response?.data?.message || 'No se pudo cambiar el estado'
      })
    }
  }
}

const formatoPrecio = (precio: number | null): string => {
  if (!precio) return '-'
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: 'PEN'
  }).format(precio)
}

const getEstadoBadgeClass = (estado: string): string => {
  return estado === 'activo'
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800'
}

onMounted(() => {
  cargarTratamientos()
})
</script>

<template>
  <DashboardLayout>
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="bg-blue-100 p-3 rounded-lg">
            <Stethoscope :size="24" class="text-blue-600" />
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Catálogo de Tratamientos</h1>
            <p class="text-sm text-gray-600">Gestión de servicios que ofrece la clínica</p>
          </div>
        </div>
        <button
          @click="crearTratamiento"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <Plus :size="20" />
          Nuevo Tratamiento
        </button>
      </div>

      <!-- Filtros y Búsqueda -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Búsqueda -->
          <div class="md:col-span-2">
            <div class="relative">
              <Search :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="busqueda"
                type="text"
                placeholder="Buscar por nombre, categoría o descripción..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>

          <!-- Filtro por Estado -->
          <div>
            <select
              v-model="estadoFiltro"
              @change="cargarTratamientos"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos los estados</option>
              <option value="activo">Activos</option>
              <option value="inactivo">Inactivos</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tabla de tratamientos -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
          <p class="mt-2 text-gray-600">Cargando tratamientos...</p>
        </div>

        <div v-else-if="!hayTratamientos" class="p-8 text-center text-gray-500">
          <Stethoscope :size="48" class="mx-auto mb-3 text-gray-400" />
          <p>No se encontraron tratamientos</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Nombre
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Categoría
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Descripción
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Precio
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Estado
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="tratamiento in tratamientosFiltrados"
                :key="tratamiento.id_tratamiento"
                class="hover:bg-gray-50 transition-colors"
              >
                <!-- Nombre -->
                <td class="px-6 py-4">
                  <div class="font-medium text-gray-900">{{ tratamiento.nombre }}</div>
                </td>

                <!-- Categoría -->
                <td class="px-6 py-4 text-sm text-gray-900">
                  <span v-if="tratamiento.categoria" class="px-2 py-1 bg-blue-50 text-blue-700 rounded-md text-xs font-medium">
                    {{ tratamiento.categoria }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>

                <!-- Descripción -->
                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                  {{ tratamiento.descripcion || '-' }}
                </td>

                <!-- Precio -->
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ formatoPrecio(tratamiento.precio_actual) }}
                </td>

                <!-- Estado -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getEstadoBadgeClass(tratamiento.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ tratamiento.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>

                <!-- Acciones -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="editarTratamiento(tratamiento.id_tratamiento)"
                      class="text-blue-600 hover:text-blue-900 p-1"
                      title="Editar"
                    >
                      <Edit :size="18" />
                    </button>
                    <button
                      @click="cambiarEstado(tratamiento)"
                      :class="tratamiento.estado === 'activo' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                      class="p-1"
                      :title="tratamiento.estado === 'activo' ? 'Inactivar' : 'Activar'"
                    >
                      <ToggleRight v-if="tratamiento.estado === 'activo'" :size="18" />
                      <ToggleLeft v-else :size="18" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
