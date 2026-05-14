// @ts-nocheck
<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { UserPlus, ArrowLeft, Save } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import userAdminService, { type CrearUsuarioData } from '@/api/userAdminService'
import rolService, { type Rol } from '@/api/rolService'
import SelectInput from '@/components/common/SelectInput.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const router = useRouter()

// Estado
const roles = ref<Rol[]>([])
const loading = ref(false)
const errors = ref<Record<string, string>>({})

// Formulario con tipado flexible
const form: any = reactive({
  username: '',
  correo: '',
  password: '',
  password_confirmation: '',
  telefono: '',
  rol: '', // Nombre del rol: 'admin', 'medico', 'paciente'
  estado: 'activo'
})

// Computadas
const rolSeleccionado = computed(() => {
  return roles.value.find(r => r.nombre_rol === form.rol)
})

const esMedico = computed(() => {
  return form.rol === 'medico'
})

const esPaciente = computed(() => {
  return form.rol === 'paciente'
})

const opcionesRoles = computed(() => {
  // Permitir crear todos los roles
  return roles.value
    .map(r => ({
      value: r.nombre_rol,
      label: r.nombre_rol.charAt(0).toUpperCase() + r.nombre_rol.slice(1)
    }))
})

const opcionesEstado = [
  { value: 'activo', label: 'Activo' },
  { value: 'inactivo', label: 'Inactivo' }
]

const opcionesTipoMedico = [
  { value: 'cabecera', label: 'Médico Cabecera' },
  { value: 'especialista', label: 'Especialista' }
]

// Watchers para inicializar campos según rol
watch(() => form.rol, (newRol) => {
  // Limpiar datos previos
  // @ts-ignore
  delete form.medico
  // @ts-ignore
  delete form.paciente

  // Inicializar datos según el rol
  if (newRol === 'paciente') {
    // @ts-ignore
    form.paciente = {
      nombres: '',
      apellidos: '',
      fecha_nacimiento: '',
      domicilio: '',
      dni: '',
      sexo: ''
    }
  } else if (newRol === 'medico') {
    // @ts-ignore
    form.medico = {
      nro_colegiado: '',
      especialidad: '',
      tipo_medico: 'especialista',
      anios_experiencia: 0
    }
  }
})

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

const validarFormulario = (): boolean => {
  errors.value = {}

  if (!form.username.trim()) {
    errors.value.username = 'El nombre de usuario es requerido'
  }

  if (!form.correo.trim()) {
    errors.value.correo = 'El correo es requerido'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.correo)) {
    errors.value.correo = 'El correo no es válido'
  }

  if (!form.password) {
    errors.value.password = 'La contraseña es requerida'
  } else if (form.password.length < 8) {
    errors.value.password = 'La contraseña debe tener al menos 8 caracteres'
  } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/.test(form.password)) {
    errors.value.password = 'Debe contener mayúscula, minúscula, número y símbolo'
  }

  if (form.password !== form.password_confirmation) {
    errors.value.password_confirmation = 'Las contraseñas no coinciden'
  }

  if (!form.rol) {
    errors.value.rol = 'Debe seleccionar un rol'
  }

  // Validaciones específicas de paciente
  // @ts-ignore
  if (esPaciente.value && form.paciente) {
    // @ts-ignore
    if (!form.paciente.nombres.trim()) {
      errors.value['paciente.nombres'] = 'Los nombres son requeridos'
    }
    // @ts-ignore
    if (!form.paciente.apellidos.trim()) {
      errors.value['paciente.apellidos'] = 'Los apellidos son requeridos'
    }
    // @ts-ignore
    if (!form.paciente.fecha_nacimiento) {
      errors.value['paciente.fecha_nacimiento'] = 'La fecha de nacimiento es requerida'
    }
  }

  // Validaciones específicas de médico
  // @ts-ignore
  if (esMedico.value && form.medico) {
    // @ts-ignore
    if (!form.medico.nro_colegiado.trim()) {
      errors.value['medico.nro_colegiado'] = 'El número de colegiado es requerido'
    }
  }

  return Object.keys(errors.value).length === 0
}

const crearUsuario = async () => {
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
    // Crear una copia del formulario sin password_confirmation
    const { password_confirmation, ...dataToSend } = form

    console.log('📤 Datos que se enviarán:', JSON.stringify(dataToSend, null, 2))

    await userAdminService.crearUsuario(dataToSend as any)

    await Swal.fire({
      icon: 'success',
      title: 'Usuario creado',
      text: 'El usuario ha sido creado correctamente',
      timer: 2000
    })

    router.push({ name: 'admin-usuarios' })
  } catch (error: any) {
    console.error('❌ Error completo:', error)
    console.error('❌ Respuesta del servidor:', error.response?.data)
    console.error('❌ Mensaje:', error.response?.data?.message)
    console.error('❌ Errores:', JSON.stringify(error.response?.data?.errors, null, 2))

    const message = error.response?.data?.message || 'Error al crear el usuario'
    const validationErrors = error.response?.data?.errors

    if (validationErrors) {
      console.error('❌ Errores de validación:', validationErrors)
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
          <UserPlus :size="24" class="text-blue-600" />
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Crear Usuario</h1>
          <p class="text-sm text-gray-600">Agregar nuevo usuario al sistema</p>
        </div>
      </div>
    </div>

    <!-- Formulario -->
    <form @submit.prevent="crearUsuario" class="w-full">
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
                placeholder="usuario123"
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
                placeholder="usuario@ejemplo.com"
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
                placeholder="+591 12345678"
              />
            </div>

            <!-- Rol -->
            <SelectInput
              v-model="form.rol"
              :options="opcionesRoles"
              label="Rol"
              placeholder="Seleccione un rol"
              :error="errors.rol"
              required
            />

            <!-- Contraseña -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Contraseña <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.password"
                type="password"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.password }"
                placeholder="••••••••"
              />
              <p v-if="errors.password" class="text-sm text-red-600 mt-1">{{ errors.password }}</p>
              <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres, mayúscula, minúscula, número y símbolo</p>
            </div>

            <!-- Confirmar contraseña -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Confirmar Contraseña <span class="text-red-500">*</span>
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

        <!-- Datos de Paciente -->
                <!-- Información adicional de Paciente -->
        <div v-if="esPaciente && form.paciente" class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Información del Paciente
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombres -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nombres <span class="text-red-500">*</span>
              </label>
              <input
                type="text"
                v-model="form.paciente.nombres"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors['paciente.nombres'] }"
                placeholder="Nombres del paciente"
              />
              <span v-if="errors['paciente.nombres']" class="text-red-500 text-sm">{{
                errors['paciente.nombres']
              }}</span>
            </div>

            <!-- Apellidos -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Apellidos <span class="text-red-500">*</span>
              </label>
              <input
                type="text"
                v-model="form.paciente.apellidos"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors['paciente.apellidos'] }"
                placeholder="Apellidos del paciente"
              />
              <span v-if="errors['paciente.apellidos']" class="text-red-500 text-sm">{{
                errors['paciente.apellidos']
              }}</span>
            </div>

            <!-- Fecha de Nacimiento -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Fecha de Nacimiento <span class="text-red-500">*</span>
              </label>
              <input
                type="date"
                v-model="form.paciente.fecha_nacimiento"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors['paciente.fecha_nacimiento'] }"
              />
              <span v-if="errors['paciente.fecha_nacimiento']" class="text-red-500 text-sm">{{
                errors['paciente.fecha_nacimiento']
              }}</span>
            </div>

            <!-- DNI -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                DNI/Carnet
              </label>
              <input
                type="text"
                v-model="form.paciente.dni"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Número de documento"
              />
            </div>

            <!-- Sexo -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Sexo
              </label>
              <select
                v-model="form.paciente.sexo"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Seleccionar</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
                <option value="Otro">Otro</option>
              </select>
            </div>

            <!-- Domicilio -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Domicilio
              </label>
              <input
                type="text"
                v-model="form.paciente.domicilio"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Dirección completa"
              />
            </div>
          </div>
        </div>

        <!-- Información adicional de Médico -->
        <div v-if="esMedico && form.medico" class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Información del Médico
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Número de Colegiado -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Número de Colegiado <span class="text-red-500">*</span>
              </label>
              <input
                type="text"
                v-model="form.medico.nro_colegiado"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors['medico.nro_colegiado'] }"
                placeholder="Ej: COP12345"
              />
              <span v-if="errors['medico.nro_colegiado']" class="text-red-500 text-sm">{{
                errors['medico.nro_colegiado']
              }}</span>
            </div>

            <!-- Especialidad -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Especialidad
              </label>
              <input
                type="text"
                v-model="form.medico.especialidad"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ej: Ortodoncia, Endodoncia..."
              />
            </div>

            <!-- Tipo de Médico -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Tipo de Médico
              </label>
              <select
                v-model="form.medico.tipo_medico"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Seleccionar tipo</option>
                <option value="especialista">Especialista</option>
                <option value="cabecera_manana">Cabecera Mañana</option>
                <option value="cabecera_tarde">Cabecera Tarde</option>
              </select>
            </div>

            <!-- Años de Experiencia -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Años de Experiencia
              </label>
              <input
                type="number"
                v-model.number="form.medico.anios_experiencia"
                class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ej: 5"
                min="0"
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
            {{ loading ? 'Creando...' : 'Crear Usuario' }}
          </button>
        </div>
      </div>
    </form>
    </div>
  </DashboardLayout>
</template>
