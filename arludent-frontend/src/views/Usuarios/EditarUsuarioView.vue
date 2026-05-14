// @ts-nocheck
<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { UserCog, ArrowLeft, Save } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import userAdminService, { type UsuarioDetalle, type ActualizarUsuarioData } from '@/api/userAdminService'
import rolService, { type Rol } from '@/api/rolService'
import SelectInput from '@/components/common/SelectInput.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const router = useRouter()
const route = useRoute()

// Estado
const usuario = ref<UsuarioDetalle | null>(null)
const roles = ref<Rol[]>([])
const loading = ref(false)
const errors = ref<Record<string, string>>({})

// Formulario
const form = reactive<ActualizarUsuarioData>({
  username: '',
  correo: '',
  telefono: '',
  password: '',
  password_confirmation: '',
  estado: 'activo',
  mfa_enabled: false
})

// Computadas
const esMedico = computed(() => {
  return usuario.value?.medico !== undefined
})

const esPaciente = computed(() => {
  return usuario.value?.paciente !== undefined
})

const opcionesRoles = computed(() => {
  return roles.value.map(r => ({
    value: r.id_rol,
    label: r.nombre_rol
  }))
})

const opcionesEstado = [
  { value: 'activo', label: 'Activo' },
  { value: 'inactivo', label: 'Inactivo' }
]

const opcionesTipoMedico = [
  { value: 'especialista', label: 'Especialista' },
  { value: 'cabecera_manana', label: 'Cabecera Mañana' },
  { value: 'cabecera_tarde', label: 'Cabecera Tarde' }
]

// Métodos
const cargarRoles = async () => {
  try {
    roles.value = await rolService.listarRoles()
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron cargar los roles'
    })
  }
}

const cargarUsuario = async () => {
  const id = parseInt(route.params.id as string)
  if (isNaN(id)) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'ID de usuario inválido'
    })
    router.push({ name: 'admin-usuarios' })
    return
  }

  loading.value = true
  try {
    usuario.value = await userAdminService.obtenerUsuario(id)

    // Poblar formulario
    form.username = usuario.value.username
    form.correo = usuario.value.correo
    form.telefono = usuario.value.telefono || ''
    form.estado = usuario.value.estado
    form.mfa_enabled = usuario.value.mfa_enabled

    // Datos de médico
    if (usuario.value.medico) {
      form.medico = {
        nro_colegiado: usuario.value.medico.nro_colegiado,
        tipo_medico: usuario.value.medico.tipo_medico,
        especialidad: usuario.value.medico.especialidad || '',
        anios_experiencia: usuario.value.medico.anios_experiencia || 0,
        estado_disponibilidad: usuario.value.medico.estado_disponibilidad
      }
    }

    // Datos de paciente
    if (usuario.value.paciente) {
      form.paciente = {
        nombres: usuario.value.paciente.nombres,
        apellidos: usuario.value.paciente.apellidos,
        fecha_nacimiento: usuario.value.paciente.fecha_nacimiento,
        domicilio: usuario.value.paciente.domicilio || '',
        dni: usuario.value.paciente.dni || '',
        sexo: usuario.value.paciente.sexo || undefined
      }
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'No se pudo cargar el usuario'
    })
    router.push({ name: 'admin-usuarios' })
  } finally {
    loading.value = false
  }
}

const validarFormulario = (): boolean => {
  errors.value = {}

  if (!form.username?.trim()) {
    errors.value.username = 'El nombre de usuario es requerido'
  }

  if (!form.correo?.trim()) {
    errors.value.correo = 'El correo es requerido'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.correo)) {
    errors.value.correo = 'El correo no es válido'
  }

  // Validar contraseña solo si se está cambiando
  if (form.password) {
    if (form.password.length < 8) {
      errors.value.password = 'La contraseña debe tener al menos 8 caracteres'
    } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/.test(form.password)) {
      errors.value.password = 'Debe contener mayúscula, minúscula, número y símbolo'
    }

    if (form.password !== form.password_confirmation) {
      errors.value.password_confirmation = 'Las contraseñas no coinciden'
    }
  }

  // Validaciones específicas de médico
  if (esMedico.value && form.medico) {
    if (!form.medico.nro_colegiado?.trim()) {
      errors.value['medico.nro_colegiado'] = 'El número de colegiado es requerido'
    }
    if (!form.medico.tipo_medico) {
      errors.value['medico.tipo_medico'] = 'El tipo de médico es requerido'
    }
  }

  // Validaciones específicas de paciente
  if (esPaciente.value && form.paciente) {
    if (!form.paciente.nombres?.trim()) {
      errors.value['paciente.nombres'] = 'Los nombres son requeridos'
    }
    if (!form.paciente.apellidos?.trim()) {
      errors.value['paciente.apellidos'] = 'Los apellidos son requeridos'
    }
    if (!form.paciente.fecha_nacimiento) {
      errors.value['paciente.fecha_nacimiento'] = 'La fecha de nacimiento es requerida'
    }
  }

  return Object.keys(errors.value).length === 0
}

const actualizarUsuario = async () => {
  if (!validarFormulario() || !usuario.value) return

  loading.value = true

  try {
    // Limpiar campos vacíos de contraseña
    const dataToSend = { ...form }
    if (!dataToSend.password) {
      delete dataToSend.password
      delete dataToSend.password_confirmation
    }

    await userAdminService.actualizarUsuario(usuario.value.id_usuario, dataToSend)

    await Swal.fire({
      icon: 'success',
      title: 'Usuario actualizado',
      text: 'Los cambios han sido guardados correctamente',
      timer: 2000
    })

    router.push({ name: 'admin-usuarios' })
  } catch (error: any) {
    const message = error.response?.data?.message || 'Error al actualizar el usuario'
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
  router.push({ name: 'admin-usuarios' })
}

onMounted(() => {
  cargarRoles()
  cargarUsuario()
})
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
          <UserCog :size="24" class="text-blue-600" />
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Editar Usuario</h1>
          <p class="text-sm text-gray-600">Modificar información del usuario</p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="!usuario" class="flex justify-center items-center p-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
    </div>

    <!-- Formulario -->
    <form v-else @submit.prevent="actualizarUsuario" class="max-w-4xl">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
        <!-- Información básica -->
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Básica</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Username -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nombre de Usuario <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.username"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.username }"
              />
              <p v-if="errors.username" class="text-sm text-red-600 mt-1">{{ errors.username }}</p>
            </div>

            <!-- Correo -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Correo Electrónico <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.correo"
                type="email"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.correo }"
              />
              <p v-if="errors.correo" class="text-sm text-red-600 mt-1">{{ errors.correo }}</p>
            </div>

            <!-- Teléfono -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Teléfono
              </label>
              <input
                v-model="form.telefono"
                type="tel"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Estado -->
            <SelectInput
              v-model="form.estado"
              :options="opcionesEstado"
              label="Estado"
              required
            />

            <!-- MFA -->
            <div class="flex items-center gap-3 pt-6">
              <input
                v-model="form.mfa_enabled"
                type="checkbox"
                id="mfa"
                class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
              />
              <label for="mfa" class="text-sm font-medium text-gray-700">
                Habilitar autenticación de dos factores (MFA)
              </label>
            </div>
          </div>
        </div>

        <!-- Cambiar contraseña (opcional) -->
        <div class="border-t pt-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-2">Cambiar Contraseña</h2>
          <p class="text-sm text-gray-600 mb-4">Deje en blanco si no desea cambiar la contraseña</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Nueva contraseña -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nueva Contraseña
              </label>
              <input
                v-model="form.password"
                type="password"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.password }"
                placeholder="••••••••"
              />
              <p v-if="errors.password" class="text-sm text-red-600 mt-1">{{ errors.password }}</p>
            </div>

            <!-- Confirmar contraseña -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Confirmar Nueva Contraseña
              </label>
              <input
                v-model="form.password_confirmation"
                type="password"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.password_confirmation }"
                placeholder="••••••••"
              />
              <p v-if="errors.password_confirmation" class="text-sm text-red-600 mt-1">{{ errors.password_confirmation }}</p>
            </div>
          </div>
        </div>

        <!-- Datos de Médico -->
        <div v-if="esMedico && form.medico" class="border-t pt-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Profesional (Médico)</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Nro Colegiado -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Número de Colegiado <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.medico.nro_colegiado"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors['medico.nro_colegiado'] }"
              />
              <p v-if="errors['medico.nro_colegiado']" class="text-sm text-red-600 mt-1">
                {{ errors['medico.nro_colegiado'] }}
              </p>
            </div>

            <!-- Tipo Médico (solo admin puede editar) -->
            <SelectInput
              v-model="form.medico.tipo_medico"
              :options="opcionesTipoMedico"
              label="Tipo de Médico (solo admin puede editar)"
              :error="errors['medico.tipo_medico']"
              required
            />

            <!-- Especialidad -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Especialidad
              </label>
              <input
                v-model="form.medico.especialidad"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Años Experiencia -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Años de Experiencia
              </label>
              <input
                v-model.number="form.medico.anios_experiencia"
                type="number"
                min="0"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Estado Disponibilidad -->
            <SelectInput
              v-model="form.medico.estado_disponibilidad"
              :options="[
                { value: 'disponible', label: 'Disponible' },
                { value: 'no_disponible', label: 'No Disponible' }
              ]"
              label="Estado de Disponibilidad"
            />
          </div>
        </div>

        <!-- Datos de Paciente -->
        <div v-if="esPaciente && form.paciente" class="border-t pt-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Personal (Paciente)</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Nombres -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nombres <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.paciente.nombres"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors['paciente.nombres'] }"
              />
              <p v-if="errors['paciente.nombres']" class="text-sm text-red-600 mt-1">
                {{ errors['paciente.nombres'] }}
              </p>
            </div>

            <!-- Apellidos -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Apellidos <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.paciente.apellidos"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors['paciente.apellidos'] }"
              />
              <p v-if="errors['paciente.apellidos']" class="text-sm text-red-600 mt-1">
                {{ errors['paciente.apellidos'] }}
              </p>
            </div>

            <!-- Fecha Nacimiento -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Fecha de Nacimiento <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.paciente.fecha_nacimiento"
                type="date"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors['paciente.fecha_nacimiento'] }"
              />
              <p v-if="errors['paciente.fecha_nacimiento']" class="text-sm text-red-600 mt-1">
                {{ errors['paciente.fecha_nacimiento'] }}
              </p>
            </div>

            <!-- DNI -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                DNI/Carnet
              </label>
              <input
                v-model="form.paciente.dni"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Número de documento"
              />
            </div>

            <!-- Sexo -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Sexo
              </label>
              <select
                v-model="form.paciente.sexo"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Seleccionar</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
                <option value="Otro">Otro</option>
              </select>
            </div>

            <!-- Domicilio -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Domicilio
              </label>
              <input
                v-model="form.paciente.domicilio"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Dirección completa"
              />
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
            {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
          </button>
        </div>
      </div>
    </form>
  </div>
  </DashboardLayout>
</template>
