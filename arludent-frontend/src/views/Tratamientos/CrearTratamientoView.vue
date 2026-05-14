<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, ArrowLeft, Save } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import tratamientoService, { type CrearTratamientoData } from '@/api/tratamientoService'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const router = useRouter()

// Estado
const loading = ref(false)
const errors = ref<Record<string, string>>({})

// Categorías predefinidas de tratamientos odontológicos
const categoriasDisponibles = [
  'Odontología General',
  'Endodoncia',
  'Periodoncia',
  'Ortodoncia',
  'Implantología',
  'Cirugía Oral',
  'Estética Dental',
  'Odontopediatría',
  'Rehabilitación Oral',
  'Radiología Dental'
]

// Formulario
const form = reactive<CrearTratamientoData>({
  nombre: '',
  categoria: '',
  descripcion: '',
  precio_actual: undefined,
  estado: 'activo'
})

// Métodos
const validarFormulario = (): boolean => {
  errors.value = {}

  if (!form.nombre.trim()) {
    errors.value.nombre = 'El nombre es requerido'
  }

  if (form.precio_actual !== undefined && form.precio_actual < 0) {
    errors.value.precio_actual = 'El precio no puede ser negativo'
  }

  return Object.keys(errors.value).length === 0
}

const crearTratamiento = async () => {
  if (!validarFormulario()) {
    Swal.fire({
      icon: 'error',
      title: 'Errores en el formulario',
      text: 'Por favor corrija los errores antes de continuar'
    })
    return
  }

  loading.value = true

  try {
    // Limpiar campos vacíos opcionales
    const dataToSend = { ...form }
    if (!dataToSend.categoria?.trim()) delete dataToSend.categoria
    if (!dataToSend.descripcion?.trim()) delete dataToSend.descripcion
    if (dataToSend.precio_actual === undefined || dataToSend.precio_actual === null) {
      delete dataToSend.precio_actual
    }

    await tratamientoService.crearTratamiento(dataToSend)

    await Swal.fire({
      icon: 'success',
      title: 'Tratamiento creado',
      text: 'El tratamiento ha sido creado correctamente',
      timer: 2000
    })

    router.push({ name: 'admin-tratamientos' })
  } catch (error: any) {
    const message = error.response?.data?.message || 'Error al crear el tratamiento'
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

const volver = () => {
  router.push({ name: 'admin-tratamientos' })
}
</script>

<template>
  <DashboardLayout>
    <div class="p-6 space-y-6 max-w-4xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button
            @click="volver"
            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <ArrowLeft :size="20" />
          </button>
          <div class="bg-blue-100 p-3 rounded-lg">
            <Plus :size="24" class="text-blue-600" />
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Nuevo Tratamiento</h1>
            <p class="text-sm text-gray-600">Agregar nuevo servicio al catálogo</p>
          </div>
        </div>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="crearTratamiento" class="w-full">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
          <!-- Información básica -->
          <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Información del Tratamiento</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Nombre -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Nombre del Tratamiento <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.nombre"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.nombre }"
                  placeholder="Ej: Limpieza dental, Ortodoncia, etc."
                />
                <p v-if="errors.nombre" class="text-sm text-red-600 mt-1">{{ errors.nombre }}</p>
              </div>

              <!-- Categoría -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Categoría
                </label>
                <select
                  v-model="form.categoria"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Seleccione una categoría</option>
                  <option v-for="cat in categoriasDisponibles" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Opcional: Categoría del tratamiento odontológico</p>
              </div>

              <!-- Precio -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Precio (S/)
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">S/</span>
                  <input
                    v-model.number="form.precio_actual"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="{ 'border-red-500': errors.precio_actual }"
                    placeholder="0.00"
                  />
                </div>
                <p v-if="errors.precio_actual" class="text-sm text-red-600 mt-1">{{ errors.precio_actual }}</p>
                <p class="text-xs text-gray-500 mt-1">Precio en soles peruanos</p>
              </div>

              <!-- Descripción -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Descripción
                </label>
                <textarea
                  v-model="form.descripcion"
                  rows="4"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Describe el tratamiento, qué incluye, duración estimada, etc."
                ></textarea>
              </div>

              <!-- Estado -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Estado inicial
                </label>
                <select
                  v-model="form.estado"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="activo">Activo</option>
                  <option value="inactivo">Inactivo</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-3 pt-6 border-t">
            <button
              type="button"
              @click="volver"
              class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              :disabled="loading"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loading"
            >
              <Save :size="20" />
              {{ loading ? 'Creando...' : 'Crear Tratamiento' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>
