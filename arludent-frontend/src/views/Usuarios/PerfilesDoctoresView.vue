<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Stethoscope, Search, Edit } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import userAdminService, { type UsuarioListItem } from '@/api/userAdminService'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

// Estado
const medicos = ref<UsuarioListItem[]>([])
const loading = ref(false)
const busqueda = ref('')

// Computadas
const medicosFiltrados = computed(() => {
  if (!busqueda.value) return medicos.value

  const query = busqueda.value.toLowerCase()
  return medicos.value.filter(m =>
    m.username.toLowerCase().includes(query) ||
    m.correo.toLowerCase().includes(query) ||
    m.medico?.especialidad?.toLowerCase().includes(query) ||
    m.medico?.nro_colegiado.toLowerCase().includes(query)
  )
})

const hayMedicos = computed(() => medicosFiltrados.value.length > 0)

// Calcular completitud del perfil
const calcularCompletitud = (medico: UsuarioListItem): number => {
  if (!medico.medico) return 0

  const camposRequeridos = [
    medico.medico.nombres,
    medico.medico.apellidos,
    medico.medico.nro_colegiado,
    medico.medico.especialidad,
  ]

  const camposOpcionales = [
    medico.medico.anios_experiencia,
    medico.medico.foto_url
  ]

  const requeridosCompletos = camposRequeridos.filter(c => c && c.trim()).length
  const opcionalesCompletos = camposOpcionales.filter(c => c).length

  // 80% campos requeridos, 20% campos opcionales
  const porcentajeRequeridos = (requeridosCompletos / camposRequeridos.length) * 80
  const porcentajeOpcionales = (opcionalesCompletos / camposOpcionales.length) * 20

  return Math.round(porcentajeRequeridos + porcentajeOpcionales)
}

const getColorCompletitud = (porcentaje: number): string => {
  if (porcentaje >= 80) return 'text-green-600'
  if (porcentaje >= 50) return 'text-yellow-600'
  return 'text-red-600'
}

const getEstadoPerfilTexto = (porcentaje: number): string => {
  if (porcentaje >= 80) return 'Completo'
  if (porcentaje >= 50) return 'Incompleto'
  return 'Sin completar'
}

// Métodos
const cargarMedicos = async () => {
  loading.value = true
  try {
    // Buscar solo usuarios con rol médico
    const response = await userAdminService.listarUsuarios({
      rolNombre: 'medico',
      page: 1,
      per_page: 100
    })

    // Filtrar solo los que tienen datos de médico
    medicos.value = response.data.filter(u => u.medico !== undefined)
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al cargar médicos'
    })
  } finally {
    loading.value = false
  }
}

const editarMedico = async (medico: UsuarioListItem) => {
  const { value: formValues } = await Swal.fire({
    title: 'Editar Médico',
    html: `
      <div class="text-left space-y-4">
        <div>
          <p class="text-sm font-medium text-gray-700 mb-2">Médico:</p>
          <p class="text-base font-semibold text-gray-900">${medico.medico?.nombres && medico.medico?.apellidos ? `${medico.medico.nombres} ${medico.medico.apellidos}` : medico.username}</p>
          <p class="text-sm text-gray-500">${medico.correo}</p>
        </div>
        <div class="border-t pt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tipo de Médico
          </label>
          <select id="tipo_medico" class="swal2-input w-full">
            <option value="cabecera_manana" ${medico.medico?.tipo_medico === 'cabecera_manana' ? 'selected' : ''}>Médico Cabecera - Turno Mañana (9:00-13:00)</option>
            <option value="cabecera_tarde" ${medico.medico?.tipo_medico === 'cabecera_tarde' ? 'selected' : ''}>Médico Cabecera - Turno Tarde (13:00-20:00)</option>
            <option value="especialista" ${medico.medico?.tipo_medico === 'especialista' ? 'selected' : ''}>Especialista</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Estado de Disponibilidad
          </label>
          <select id="estado_disponibilidad" class="swal2-input w-full">
            <option value="disponible" ${medico.medico?.estado_disponibilidad === 'disponible' ? 'selected' : ''}>Disponible</option>
            <option value="no_disponible" ${medico.medico?.estado_disponibilidad === 'no_disponible' ? 'selected' : ''}>No Disponible</option>
          </select>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
          <p class="font-medium mb-1">ℹ️ Información</p>
          <p>El médico completa su propio perfil profesional (nombres, colegiado, especialidad, etc.)</p>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Guardar Cambios',
    cancelButtonText: 'Cancelar',
    width: '500px',
    preConfirm: () => {
      const tipoMedico = (document.getElementById('tipo_medico') as HTMLSelectElement).value
      const estadoDisponibilidad = (document.getElementById('estado_disponibilidad') as HTMLSelectElement).value

      return {
        tipo_medico: tipoMedico as 'cabecera_manana' | 'cabecera_tarde' | 'especialista',
        estado_disponibilidad: estadoDisponibilidad as 'disponible' | 'no_disponible'
      }
    }
  })

  if (formValues) {
    try {
      await userAdminService.actualizarUsuario(medico.id_usuario, {
        medico: {
          tipo_medico: formValues.tipo_medico,
          estado_disponibilidad: formValues.estado_disponibilidad
        }
      })

      await Swal.fire({
        icon: 'success',
        title: 'Médico actualizado',
        text: 'Los cambios han sido guardados correctamente',
        timer: 2000
      })

      cargarMedicos()
    } catch (error: any) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.response?.data?.message || 'No se pudo actualizar el médico'
      })
    }
  }
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

const getTipoMedicoBadgeClass = (tipo?: string): string => {
  if (tipo === 'cabecera_manana') {
    return 'bg-blue-100 text-blue-800'
  } else if (tipo === 'cabecera_tarde') {
    return 'bg-indigo-100 text-indigo-800'
  } else {
    return 'bg-purple-100 text-purple-800'
  }
}

const getDisponibilidadBadgeClass = (disponibilidad: string): string => {
  return disponibilidad === 'disponible'
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800'
}

onMounted(() => {
  cargarMedicos()
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
            <h1 class="text-2xl font-bold text-gray-900">Perfiles de Doctores</h1>
            <p class="text-sm text-gray-600">Gestión de perfiles profesionales médicos</p>
          </div>
        </div>
      </div>

      <!-- Búsqueda -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="relative max-w-md">
          <Search :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre, correo, especialidad o colegiado..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
      </div>

      <!-- Tabla de médicos -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
          <p class="mt-2 text-gray-600">Cargando médicos...</p>
        </div>

        <div v-else-if="!hayMedicos" class="p-8 text-center text-gray-500">
          <Stethoscope :size="48" class="mx-auto mb-3 text-gray-400" />
          <p>No se encontraron médicos</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Médico
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Nro. Colegiado
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Especialidad
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Perfil
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tipo
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
                v-for="medico in medicosFiltrados"
                :key="medico.id_usuario"
                class="hover:bg-gray-50 transition-colors"
              >
                <!-- Médico -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div>
                      <div class="font-medium text-gray-900">
                        {{ medico.medico?.nombres && medico.medico?.apellidos
                          ? `${medico.medico.nombres} ${medico.medico.apellidos}`
                          : medico.username
                        }}
                      </div>
                      <div class="text-sm text-gray-500">{{ medico.correo }}</div>
                    </div>
                  </div>
                </td>

                <!-- Nro. Colegiado -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ medico.medico?.nro_colegiado || '-' }}
                </td>

                <!-- Especialidad -->
                <td class="px-6 py-4 text-sm text-gray-900">
                  {{ medico.medico?.especialidad || '-' }}
                </td>

                <!-- Completitud del Perfil -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="w-24 bg-gray-200 rounded-full h-2">
                      <div
                        :class="getColorCompletitud(calcularCompletitud(medico)).replace('text-', 'bg-')"
                        class="h-2 rounded-full transition-all"
                        :style="{ width: `${calcularCompletitud(medico)}%` }"
                      ></div>
                    </div>
                    <span :class="getColorCompletitud(calcularCompletitud(medico))" class="text-xs font-medium">
                      {{ calcularCompletitud(medico) }}%
                    </span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ getEstadoPerfilTexto(calcularCompletitud(medico)) }}
                  </div>
                </td>

                <!-- Tipo de Médico -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getTipoMedicoBadgeClass(medico.medico?.tipo_medico)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getTipoMedicoText(medico.medico?.tipo_medico) }}
                  </span>
                </td>

                <!-- Estado Disponibilidad -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getDisponibilidadBadgeClass(medico.medico?.estado_disponibilidad || 'no_disponible')"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ medico.medico?.estado_disponibilidad === 'disponible' ? 'Disponible' : 'No Disponible' }}
                  </span>
                </td>

                <!-- Acciones -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    @click="editarMedico(medico)"
                    class="text-blue-600 hover:text-blue-900 inline-flex items-center gap-1"
                    title="Editar tipo y disponibilidad"
                  >
                    <Edit :size="16" />
                    Editar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
