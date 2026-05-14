<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { Stethoscope, Save, Trash2 } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import medicoService, { type PerfilMedico, type ActualizarPerfilMedicoData } from '@/api/medicoService'
import ImageUpload from '@/components/common/ImageUpload.vue'
import SelectInput from '@/components/common/SelectInput.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

// Estado
const perfil = ref<PerfilMedico | null>(null)
const loading = ref(false)
const errors = ref<Record<string, string>>({})
const fotoFile = ref<File | null>(null)

// Formulario
const form = reactive<ActualizarPerfilMedicoData>({
  nombres: '',
  apellidos: '',
  nro_colegiado: '',
  especialidad: '',
  anios_experiencia: 0,
  estado_disponibilidad: 'disponible' as 'disponible' | 'no_disponible'
})

// Computadas
const fotoActual = computed(() => {
  if (!perfil.value?.foto_url) {
    console.log('❌ No hay foto_url en el perfil')
    return null
  }
  const url = medicoService.getFotoUrl(perfil.value.foto_url)
  console.log('📸 Foto actual computada:', {
    foto_url_backend: perfil.value.foto_url,
    url_generada: url
  })
  return url
})

const opcionesDisponibilidad = [
  { value: 'disponible', label: 'Disponible' },
  { value: 'no_disponible', label: 'No Disponible' }
]

const tipoMedicoText = computed(() => {
  if (!perfil.value) return ''
  switch (perfil.value.tipo_medico) {
    case 'cabecera_manana':
      return 'Médico Cabecera - Turno Mañana'
    case 'cabecera_tarde':
      return 'Médico Cabecera - Turno Tarde'
    case 'especialista':
      return 'Especialista'
    default:
      return perfil.value.tipo_medico
  }
})

const completitudPerfil = computed(() => {
  if (!perfil.value) return 0

  const camposRequeridos = [
    perfil.value.nombres,
    perfil.value.apellidos,
    perfil.value.nro_colegiado,
    perfil.value.especialidad,
  ]

  const camposOpcionales = [
    perfil.value.anios_experiencia,
    perfil.value.foto_url
  ]

  const requeridosCompletos = camposRequeridos.filter(c => c && String(c).trim()).length
  const opcionalesCompletos = camposOpcionales.filter(c => c).length

  // 80% campos requeridos, 20% campos opcionales
  const porcentajeRequeridos = (requeridosCompletos / camposRequeridos.length) * 80
  const porcentajeOpcionales = (opcionalesCompletos / camposOpcionales.length) * 20

  return Math.round(porcentajeRequeridos + porcentajeOpcionales)
})

const colorCompletitud = computed(() => {
  const p = completitudPerfil.value
  if (p >= 80) return 'bg-green-500'
  if (p >= 50) return 'bg-yellow-500'
  return 'bg-red-500'
})

const mensajeCompletitud = computed(() => {
  const p = completitudPerfil.value
  if (p >= 80) return '¡Tu perfil está completo! ✅'
  if (p >= 50) return 'Tu perfil está incompleto. Por favor completa todos los campos.'
  return '⚠️ Tu perfil requiere atención urgente. Completa la información requerida.'
})

// Métodos
const cargarPerfil = async () => {
  loading.value = true
  try {
    const perfilData = await medicoService.obtenerPerfil()
    console.log('Perfil cargado:', perfilData) // Para depuración
    perfil.value = perfilData

    if (!perfil.value) {
      throw new Error('No se recibieron datos del perfil')
    }

    // Poblar formulario
    form.nombres = perfil.value.nombres || ''
    form.apellidos = perfil.value.apellidos || ''
    form.nro_colegiado = perfil.value.nro_colegiado || ''
    form.especialidad = perfil.value.especialidad || ''
    form.anios_experiencia = perfil.value.anios_experiencia || 0
    form.estado_disponibilidad = perfil.value.estado_disponibilidad || 'disponible'
  } catch (error: any) {
    console.error('Error al cargar perfil:', error) // Para depuración
    Swal.fire({
      icon: 'error',
      title: 'Error al cargar el perfil',
      text: error.response?.data?.message || error.message || 'No se pudo cargar el perfil'
    })
  } finally {
    loading.value = false
  }
}

const validarFormulario = (): boolean => {
  errors.value = {}

  if (!form.nombres?.trim()) {
    errors.value.nombres = 'Los nombres son requeridos'
  }

  if (!form.apellidos?.trim()) {
    errors.value.apellidos = 'Los apellidos son requeridos'
  }

  if (!form.nro_colegiado?.trim()) {
    errors.value.nro_colegiado = 'El número de colegiado es requerido'
  }

  if (form.anios_experiencia !== undefined && form.anios_experiencia < 0) {
    errors.value.anios_experiencia = 'Los años de experiencia no pueden ser negativos'
  }

  return Object.keys(errors.value).length === 0
}

const actualizarPerfil = async () => {
  if (!validarFormulario()) return

  loading.value = true

  try {
    const dataToSend: ActualizarPerfilMedicoData = { ...form }

    // Agregar foto si hay una nueva
    if (fotoFile.value) {
      dataToSend.foto = fotoFile.value
      console.log('📤 Enviando nueva foto:', fotoFile.value.name)
    }

    const perfilActualizado = await medicoService.actualizarPerfil(dataToSend)
    console.log('✅ Perfil actualizado recibido del backend:', perfilActualizado)

    perfil.value = perfilActualizado
    fotoFile.value = null

    await Swal.fire({
      icon: 'success',
      title: 'Perfil actualizado',
      text: 'Tu perfil profesional ha sido actualizado correctamente',
      timer: 2000
    })

    // Recargar perfil para asegurar que tenemos la última versión
    await cargarPerfil()
  } catch (error: any) {
    console.error('❌ Error al actualizar perfil:', error)
    const message = error.response?.data?.message || 'Error al actualizar el perfil'
    const validationErrors = error.response?.data?.errors

    if (validationErrors) {
      errors.value = validationErrors
    }

    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: message
    })
  } finally {
    loading.value = false
  }
}

const eliminarFoto = async () => {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar foto?',
    text: '¿Está seguro de eliminar su foto de perfil?',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  })

  if (result.isConfirmed) {
    try {
      await medicoService.eliminarFoto()

      Swal.fire({
        icon: 'success',
        title: 'Foto eliminada',
        text: 'Tu foto de perfil ha sido eliminada',
        timer: 2000
      })

      await cargarPerfil()
    } catch (error: any) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.response?.data?.message || 'No se pudo eliminar la foto'
      })
    }
  }
}

const handleFotoRemove = () => {
  fotoFile.value = null
}

onMounted(() => {
  cargarPerfil()
})
</script>

<template>
  <DashboardLayout>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <div class="bg-blue-100 p-3 rounded-lg">
        <Stethoscope :size="24" class="text-blue-600" />
      </div>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Mi Perfil Profesional</h1>
        <p class="text-sm text-gray-600">Administra tu información profesional como médico</p>
      </div>
    </div>

    <!-- Indicador de Completitud del Perfil -->
    <div v-if="perfil" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="text-sm font-semibold text-gray-700">Completitud del Perfil</h3>
          <p class="text-xs text-gray-500 mt-1">{{ mensajeCompletitud }}</p>
        </div>
        <div class="text-right">
          <span class="text-2xl font-bold" :class="completitudPerfil >= 80 ? 'text-green-600' : completitudPerfil >= 50 ? 'text-yellow-600' : 'text-red-600'">
            {{ completitudPerfil }}%
          </span>
        </div>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-3">
        <div
          :class="colorCompletitud"
          class="h-3 rounded-full transition-all duration-500"
          :style="{ width: `${completitudPerfil}%` }"
        ></div>
      </div>
      <div v-if="completitudPerfil < 80" class="mt-3 text-xs text-gray-600">
        <p class="font-medium mb-1">Campos pendientes:</p>
        <ul class="list-disc list-inside space-y-1">
          <li v-if="!perfil.nombres">Nombres (requerido)</li>
          <li v-if="!perfil.apellidos">Apellidos (requerido)</li>
          <li v-if="!perfil.nro_colegiado">Número de colegiado (requerido)</li>
          <li v-if="!perfil.especialidad">Especialidad (requerido)</li>
          <li v-if="!perfil.anios_experiencia">Años de experiencia (opcional)</li>
          <li v-if="!perfil.foto_url">Foto profesional (opcional)</li>
        </ul>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="!perfil" class="flex justify-center items-center p-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
    </div>

    <!-- Contenido -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Información del usuario (no editable) -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Información de Cuenta</h2>

          <div>
            <label class="block text-sm font-medium text-gray-500">Usuario</label>
            <p class="text-gray-900">{{ perfil.usuario?.username || '—' }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-500">Correo</label>
            <p class="text-gray-900">{{ perfil.usuario?.correo || '—' }}</p>
          </div>

          <div v-if="perfil.usuario.telefono">
            <label class="block text-sm font-medium text-gray-500">Teléfono</label>
            <p class="text-gray-900">{{ perfil.usuario.telefono }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-500">Tipo de Médico</label>
            <div class="mt-1">
              <span
                :class="[
                  'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                  perfil.tipo_medico === 'cabecera_manana'
                    ? 'bg-blue-100 text-blue-800'
                    : perfil.tipo_medico === 'cabecera_tarde'
                    ? 'bg-indigo-100 text-indigo-800'
                    : 'bg-purple-100 text-purple-800'
                ]"
              >
                {{ tipoMedicoText }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Solo el administrador puede modificar este campo</p>
          </div>

          <div class="pt-4 border-t">
            <p class="text-xs text-gray-500">
              <strong>Nota:</strong> Para cambiar tu correo, teléfono o contraseña, ve a la sección "Mi Perfil" en el menú principal.
            </p>
          </div>
        </div>
      </div>

      <!-- Formulario editable -->
      <div class="lg:col-span-2">
        <form @submit.prevent="actualizarPerfil" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
          <h2 class="text-lg font-semibold text-gray-900">Datos Profesionales</h2>

          <!-- Foto de perfil -->
          <div>
            <ImageUpload
              v-model="fotoFile"
              :currentImageUrl="fotoActual"
              label="Foto de Perfil Profesional"
              :error="errors.foto"
              @remove="handleFotoRemove"
            />

            <button
              v-if="perfil.foto_url && !fotoFile"
              type="button"
              @click="eliminarFoto"
              class="mt-2 flex items-center gap-2 text-sm text-red-600 hover:text-red-700"
            >
              <Trash2 :size="16" />
              Eliminar foto actual
            </button>
          </div>

          <!-- Grid de Nombres y Apellidos -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Nombres -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nombres <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.nombres"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.nombres }"
                placeholder="Carlos Alberto"
              />
              <p v-if="errors.nombres" class="text-sm text-red-600 mt-1">
                {{ errors.nombres }}
              </p>
            </div>

            <!-- Apellidos -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Apellidos <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.apellidos"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.apellidos }"
                placeholder="Ramírez González"
              />
              <p v-if="errors.apellidos" class="text-sm text-red-600 mt-1">
                {{ errors.apellidos }}
              </p>
            </div>
          </div>

          <!-- Número de Colegiado -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Número de Colegiado <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.nro_colegiado"
              type="text"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.nro_colegiado }"
              placeholder="COL-12345"
            />
            <p v-if="errors.nro_colegiado" class="text-sm text-red-600 mt-1">
              {{ errors.nro_colegiado }}
            </p>
          </div>

          <!-- Especialidad -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Especialidad
            </label>
            <input
              v-model="form.especialidad"
              type="text"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Odontología General, Ortodoncia, Endodoncia, etc."
            />
            <p class="text-xs text-gray-500 mt-1">
              Tu área de especialización dentro de la odontología
            </p>
          </div>

          <!-- Años de Experiencia -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Años de Experiencia
            </label>
            <input
              v-model.number="form.anios_experiencia"
              type="number"
              min="0"
              max="60"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.anios_experiencia }"
            />
            <p v-if="errors.anios_experiencia" class="text-sm text-red-600 mt-1">
              {{ errors.anios_experiencia }}
            </p>
          </div>

          <!-- Estado de Disponibilidad -->
          <SelectInput
            :model-value="form.estado_disponibilidad || 'disponible'"
            @update:model-value="form.estado_disponibilidad = $event as 'disponible' | 'no_disponible'"
            :options="opcionesDisponibilidad"
            label="Estado de Disponibilidad"
          />
          <p class="text-xs text-gray-500 -mt-3">
            Indica si estás disponible para atender pacientes
          </p>

          <!-- Botones -->
          <div class="flex justify-end gap-3 pt-6 border-t">
            <button
              type="submit"
              class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loading"
            >
              <Save :size="20" />
              {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>

        <!-- Información adicional -->
        <div class="mt-4 bg-blue-50 rounded-lg border border-blue-200 p-4">
          <h3 class="text-sm font-semibold text-blue-900 mb-2">💡 Consejos para tu perfil</h3>
          <ul class="text-sm text-blue-800 space-y-1">
            <li>• Mantén actualizada tu especialidad para que los pacientes sepan tu área de expertise</li>
            <li>• Una foto profesional genera confianza en tus pacientes</li>
            <li>• Actualiza tu estado de disponibilidad si vas a tomar vacaciones o licencia</li>
            <li>• El número de colegiado debe coincidir con tu registro profesional oficial</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  </DashboardLayout>
</template>
