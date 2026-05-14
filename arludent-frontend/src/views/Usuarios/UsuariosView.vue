<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Users, Plus, Search, Filter, Edit, Trash2, UserCog, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import userAdminService, { type UsuarioListItem, type FiltrosUsuarios } from '@/api/userAdminService'
import rolService, { type Rol } from '@/api/rolService'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const router = useRouter()

// Estado
const usuarios = ref<UsuarioListItem[]>([])
const roles = ref<Rol[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(10)

// Filtros
const filtros = ref<FiltrosUsuarios>({
  rol: undefined,
  estado: undefined,
  busqueda: '',
  page: 1,
  per_page: 10
})

// Computadas
const hayUsuarios = computed(() => usuarios.value.length > 0)

// Métodos
const cargarRoles = async () => {
  try {
    roles.value = await rolService.listarRoles()
  } catch (error: any) {
    console.error('Error al cargar roles:', error)
  }
}

const cargarUsuarios = async () => {
  loading.value = true
  try {
    // Convertir id de rol seleccionado a nombre_rol para el backend
    const rolSeleccionado = roles.value.find(r => r.id_rol === filtros.value.rol)
    const response = await userAdminService.listarUsuarios({
      ...filtros.value,
      rolNombre: rolSeleccionado?.nombre_rol,
      page: currentPage.value
    })

    usuarios.value = response.data
    currentPage.value = response.current_page
    lastPage.value = response.last_page
    total.value = response.total
    perPage.value = response.per_page
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al cargar usuarios'
    })
  } finally {
    loading.value = false
  }
}

const aplicarFiltros = () => {
  currentPage.value = 1
  cargarUsuarios()
}

const limpiarFiltros = () => {
  filtros.value = {
    rol: undefined,
    estado: undefined,
    busqueda: '',
    page: 1,
    per_page: 10
  }
  currentPage.value = 1
  cargarUsuarios()
}

const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= lastPage.value) {
    currentPage.value = pagina
    cargarUsuarios()
  }
}

const editarUsuario = (id: number) => {
  router.push({ name: 'admin-usuarios-editar', params: { id } })
}

const eliminarUsuario = async (usuario: UsuarioListItem) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar usuario?',
    html: `¿Está seguro de eliminar a <strong>${usuario.username}</strong>?<br>Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  })

  if (result.isConfirmed) {
    try {
      await userAdminService.eliminarUsuario(usuario.id_usuario)

      Swal.fire({
        icon: 'success',
        title: 'Usuario eliminado',
        text: 'El usuario ha sido eliminado correctamente',
        timer: 2000
      })

      cargarUsuarios()
    } catch (error: any) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.response?.data?.message || 'No se pudo eliminar el usuario'
      })
    }
  }
}

const getRolBadgeClass = (nombreRol: string): string => {
  const rolLower = nombreRol.toLowerCase()
  if (rolLower.includes('admin')) return 'bg-purple-100 text-purple-800'
  if (rolLower.includes('medico') || rolLower.includes('doctor')) return 'bg-blue-100 text-blue-800'
  if (rolLower.includes('paciente')) return 'bg-green-100 text-green-800'
  return 'bg-gray-100 text-gray-800'
}

const getEstadoBadgeClass = (estado: string): string => {
  return estado === 'activo'
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800'
}

const getTipoMedicoText = (tipo?: string): string => {
  if (!tipo) return '-'
  switch (tipo) {
    case 'cabecera_manana':
      return 'Cabecera Mañana'
    case 'cabecera_tarde':
      return 'Cabecera Tarde'
    case 'especialista':
      return 'Especialista'
    default:
      return tipo
  }
}

onMounted(() => {
  cargarRoles()
  cargarUsuarios()
})
</script>

<template>
  <DashboardLayout>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="bg-blue-100 p-3 rounded-lg">
          <Users :size="24" class="text-blue-600" />
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h1>
          <p class="text-sm text-gray-600">Administrar usuarios del sistema</p>
        </div>
      </div>

      <button
        @click="router.push({ name: 'admin-usuarios-crear' })"
        class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
      >
        <Plus :size="20" />
        Nuevo Usuario
      </button>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="flex items-center gap-2 mb-4">
        <Filter :size="20" class="text-gray-600" />
        <h2 class="font-semibold text-gray-900">Filtros</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Búsqueda -->
        <div class="md:col-span-2">
          <div class="relative">
            <Search :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              v-model="filtros.busqueda"
              type="text"
              placeholder="Buscar por nombre, correo o teléfono..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @keyup.enter="aplicarFiltros"
            />
          </div>
        </div>

        <!-- Rol -->
        <div>
          <select
            v-model="filtros.rol"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option :value="undefined">Todos los roles</option>
            <option
              v-for="rol in roles"
              :key="rol.id_rol"
              :value="rol.id_rol"
            >
              {{ rol.nombre_rol }}
            </option>
          </select>
        </div>

        <!-- Estado -->
        <div>
          <select
            v-model="filtros.estado"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option :value="undefined">Todos los estados</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
          </select>
        </div>
      </div>

      <div class="flex gap-2 mt-4">
        <button
          @click="aplicarFiltros"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Aplicar Filtros
        </button>
        <button
          @click="limpiarFiltros"
          class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
        >
          Limpiar
        </button>
      </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
        <p class="mt-2 text-gray-600">Cargando usuarios...</p>
      </div>

      <div v-else-if="!hayUsuarios" class="p-8 text-center text-gray-500">
        <Users :size="48" class="mx-auto mb-3 text-gray-400" />
        <p>No se encontraron usuarios</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Usuario
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Contacto
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Rol
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Verificado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                MFA
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="usuario in usuarios"
              :key="usuario.id_usuario"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="font-medium text-gray-900">{{ usuario.username }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ usuario.correo }}</div>
                <div v-if="usuario.telefono" class="text-sm text-gray-500">{{ usuario.telefono }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  v-for="rol in usuario.roles"
                  :key="rol.id_rol"
                  :class="getRolBadgeClass(rol.nombre_rol)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ rol.nombre_rol }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getEstadoBadgeClass(usuario.estado)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                >
                  {{ usuario.estado }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span v-if="usuario.email_verified_at" class="text-green-600">✓ Sí</span>
                <span v-else class="text-red-600">✗ No</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span v-if="usuario.mfa_enabled" class="text-blue-600">✓ Activado</span>
                <span v-else class="text-gray-400">✗ Desactivado</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="editarUsuario(usuario.id_usuario)"
                    class="text-blue-600 hover:text-blue-900 p-1"
                    title="Editar"
                  >
                    <Edit :size="18" />
                  </button>
                  <button
                    @click="eliminarUsuario(usuario)"
                    class="text-red-600 hover:text-red-900 p-1"
                    title="Eliminar"
                  >
                    <Trash2 :size="18" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="hayUsuarios" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ (currentPage - 1) * perPage + 1 }} a {{ Math.min(currentPage * perPage, total) }} de {{ total }} usuarios
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="cambiarPagina(currentPage - 1)"
              :disabled="currentPage === 1"
              class="p-2 rounded-lg border border-gray-300 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeft :size="20" />
            </button>

            <div class="flex gap-1">
              <button
                v-for="page in Math.min(lastPage, 5)"
                :key="page"
                @click="cambiarPagina(page)"
                :class="[
                  'px-3 py-1 rounded-lg',
                  page === currentPage
                    ? 'bg-blue-600 text-white'
                    : 'border border-gray-300 hover:bg-gray-100'
                ]"
              >
                {{ page }}
              </button>
            </div>

            <button
              @click="cambiarPagina(currentPage + 1)"
              :disabled="currentPage === lastPage"
              class="p-2 rounded-lg border border-gray-300 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronRight :size="20" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  </DashboardLayout>
</template>
