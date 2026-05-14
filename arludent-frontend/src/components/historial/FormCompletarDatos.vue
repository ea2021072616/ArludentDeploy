<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="cerrar">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-4xl shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h3 class="text-2xl font-bold text-gray-900">Completar Registro de Paciente</h3>
          <p class="text-sm text-gray-600 mt-1">
            Usuario: <span class="font-semibold">{{ persona.correo }}</span>
          </p>
        </div>
        <button @click="cerrar" class="text-gray-400 hover:text-gray-600">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Progress Steps -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div v-for="step in steps" :key="step.number" class="flex-1">
            <div class="flex items-center">
              <div
                :class="[
                  'flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors',
                  currentStep >= step.number
                    ? 'border-blue-600 bg-blue-600 text-white'
                    : 'border-gray-300 bg-white text-gray-500'
                ]"
              >
                <Check v-if="currentStep > step.number" class="w-5 h-5" />
                <span v-else>{{ step.number }}</span>
              </div>
              <div
                v-if="step.number < steps.length"
                :class="[
                  'flex-1 h-1 mx-2 transition-colors',
                  currentStep > step.number ? 'bg-blue-600' : 'bg-gray-300'
                ]"
              ></div>
            </div>
            <div class="mt-2 text-xs text-center font-medium" :class="currentStep >= step.number ? 'text-blue-600' : 'text-gray-500'">
              {{ step.label }}
            </div>
          </div>
        </div>
      </div>

      <!-- Form Steps -->
      <form @submit.prevent="siguiente" class="space-y-6">
        <!-- PASO 1: Datos Personales -->
        <div v-if="currentStep === 1" class="space-y-4">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">
            FASE I. DATOS PERSONALES
          </h4>

          <div class="grid grid-cols-2 gap-4">
            <InputField
              v-model="formData.datos_paciente.nombres"
              label="Nombres *"
              placeholder="Nombres del paciente"
              :error="errors.nombres"
              required
            />
            <InputField
              v-model="formData.datos_paciente.apellidos"
              label="Apellidos *"
              placeholder="Apellidos del paciente"
              :error="errors.apellidos"
              required
            />
          </div>

          <div class="grid grid-cols-3 gap-4">
            <InputField
              v-model="formData.datos_paciente.dni"
              label="DNI"
              placeholder="12345678"
              :error="errors.dni"
            />
            <InputField
              v-model="formData.datos_paciente.fecha_nacimiento"
              label="Fecha de Nacimiento"
              type="date"
              :error="errors.fecha_nacimiento"
            />
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Sexo
              </label>
              <select
                v-model="formData.datos_paciente.sexo"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Seleccionar</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
                <option value="Otro">Otro</option>
              </select>
            </div>
          </div>

          <InputField
            v-model="formData.datos_paciente.domicilio"
            label="Domicilio"
            placeholder="Dirección completa"
            :error="errors.domicilio"
          />

          <div class="grid grid-cols-2 gap-4">
            <InputField
              v-model="formData.datos_paciente.persona_responsable"
              label="Persona Responsable"
              placeholder="Nombre del responsable"
              :error="errors.persona_responsable"
            />
            <div class="bg-blue-50 p-3 rounded-lg border-2 border-blue-300">
              <label class="block text-sm font-semibold text-blue-700 mb-1">
                Teléfono Principal (Paciente) *
              </label>
              <input
                v-model="formData.datos_paciente.telefono_principal"
                type="text"
                :placeholder="persona.telefono || '987654321'"
                class="w-full px-3 py-2 border-2 border-blue-400 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-gray-900"
              />
              <p class="text-xs text-blue-600 mt-1">Teléfono del usuario/paciente</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <InputField
              v-model="formData.datos_paciente.telefono_responsable"
              label="Teléfono de Contacto (Responsable)"
              placeholder="987654321 (Opcional)"
              :error="errors.telefono_responsable"
            />
            <InputField
              v-model="formData.datos_paciente.grupo_sanguineo"
              label="Grupo Sanguíneo (Opcional)"
              placeholder="Ej: O+, A-, AB+"
              :error="errors.grupo_sanguineo"
            />
          </div>

          <InputField
            v-model="formData.datos_paciente.alergias"
            label="Alergias (Opcional)"
            placeholder="Penicilina, látex, etc."
            :error="errors.alergias"
          />
        </div>

        <!-- PASO 2: Motivo de Consulta e Historia de la Enfermedad -->
        <div v-if="currentStep === 2" class="space-y-4">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">
            FASE II. MOTIVO DE CONSULTA E HISTORIA DE LA ENFERMEDAD
          </h4>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Motivo de Consulta *
            </label>
            <textarea
              v-model="formData.motivo_consulta"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="¿Por qué acude a la consulta?"
              required
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Síntoma Principal
            </label>
            <textarea
              v-model="formData.sintoma_principal"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Describa el síntoma principal..."
            ></textarea>
          </div>

          <InputField
            v-model="formData.tiempo_inicio_sintomas"
            label="Tiempo y Forma de Inicio"
            placeholder="Ej: Hace 2 semanas, inicio gradual"
          />

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Tratamiento Recibido Previamente
            </label>
            <textarea
              v-model="formData.tratamiento_previo"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="¿Ha recibido algún tratamiento anteriormente?"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Enfermedades Actuales
            </label>
            <textarea
              v-model="formData.enfermedades_actuales"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Diabetes, hipertensión, etc."
            ></textarea>
          </div>

          <div class="space-y-3">
            <div class="flex items-center">
              <input
                v-model="formData.bajo_tratamiento_medico"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label class="ml-2 text-sm text-gray-700">
                ¿Está bajo tratamiento médico actualmente?
              </label>
            </div>

            <div v-if="formData.bajo_tratamiento_medico">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Detalle del Tratamiento Actual
              </label>
              <textarea
                v-model="formData.detalle_tratamiento_actual"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Especifique el tratamiento..."
              ></textarea>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Alergias del Paciente
            </label>
            <textarea
              v-model="formData.alergias_paciente"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Medicamentos, alimentos, materiales..."
            ></textarea>
          </div>
        </div>

        <!-- PASO 3: Antecedentes Médicos y Odontológicos -->
        <div v-if="currentStep === 3" class="space-y-4">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">
            FASE III. ANTECEDENTES MÉDICOS Y ODONTOLÓGICOS
          </h4>

          <div class="space-y-3">
            <div class="flex items-center">
              <input
                v-model="formData.intervenciones_quirurgicas_previas"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label class="ml-2 text-sm text-gray-700">
                ¿Ha tenido intervenciones quirúrgicas previas?
              </label>
            </div>

            <div v-if="formData.intervenciones_quirurgicas_previas">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Detalle de las Intervenciones
              </label>
              <textarea
                v-model="formData.detalle_intervenciones"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Especifique las intervenciones..."
              ></textarea>
            </div>

            <div class="flex items-center">
              <input
                v-model="formData.hemorragia_post_tratamiento"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label class="ml-2 text-sm text-gray-700">
                ¿Ha experimentado hemorragia después de tratamiento dental?
              </label>
            </div>

            <div class="flex items-center">
              <input
                v-model="formData.problema_anestesia"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label class="ml-2 text-sm text-gray-700">
                ¿Ha tenido problemas con la anestesia dental?
              </label>
            </div>

            <div class="flex items-center">
              <input
                v-model="formData.dificultad_abrir_masticar"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label class="ml-2 text-sm text-gray-700">
                ¿Tiene dificultad para abrir la boca o masticar?
              </label>
            </div>

            <div class="flex items-center">
              <input
                v-model="formData.sensibilidad_dental"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label class="ml-2 text-sm text-gray-700">
                ¿Presenta sensibilidad dental?
              </label>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-6 border-t">
          <Button
            v-if="currentStep > 1"
            type="button"
            variant="secondary"
            @click="anterior"
          >
            <ChevronLeft class="w-4 h-4 mr-1" />
            Anterior
          </Button>
          <div v-else></div>

          <div class="flex space-x-3">
            <Button
              type="button"
              variant="outline"
              @click="cerrar"
            >
              Cancelar
            </Button>
            <Button
              v-if="currentStep < steps.length"
              type="submit"
              variant="primary"
            >
              Siguiente
              <ChevronRight class="w-4 h-4 ml-1" />
            </Button>
            <Button
              v-else
              type="submit"
              variant="primary"
              :loading="guardando"
            >
              <Save class="w-4 h-4 mr-1" />
              Guardar y Crear Historial
            </Button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { X, Check, ChevronLeft, ChevronRight, Save } from 'lucide-vue-next'
import Button from '@/components/common/Button.vue'
import InputField from '@/components/common/InputField.vue'
import { crearHistorialClinico } from '@/api/historialClinicoService'
import Swal from 'sweetalert2'

interface Props {
  persona: any
}

interface Emits {
  (e: 'cerrar'): void
  (e: 'guardado'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const steps = [
  { number: 1, label: 'Datos Personales' },
  { number: 2, label: 'Motivo de Consulta' },
  { number: 3, label: 'Antecedentes Médicos' },
]

const currentStep = ref(1)
const guardando = ref(false)
const errors = reactive<any>({})

const formData = reactive<{
  id_usuario_externo: number
  datos_paciente: {
    nombres: string
    apellidos: string
    dni: string
    fecha_nacimiento: string
    sexo: 'M' | 'F' | 'Otro' | ''
    domicilio: string
    persona_responsable: string
    telefono_principal: string
    telefono_responsable: string
    grupo_sanguineo: string
    alergias: string
  }
  motivo_consulta: string
  sintoma_principal: string
  tiempo_inicio_sintomas: string
  tratamiento_previo: string
  enfermedades_actuales: string
  bajo_tratamiento_medico: boolean
  detalle_tratamiento_actual: string
  alergias_paciente: string
  intervenciones_quirurgicas_previas: boolean
  detalle_intervenciones: string
  hemorragia_post_tratamiento: boolean
  problema_anestesia: boolean
  dificultad_abrir_masticar: boolean
  sensibilidad_dental: boolean
}>({
  id_usuario_externo: props.persona.id_usuario,
  datos_paciente: {
    nombres: '',
    apellidos: '',
    dni: '',
    fecha_nacimiento: '',
    sexo: '',
    domicilio: '',
    persona_responsable: '',
    telefono_principal: props.persona.telefono || '',
    telefono_responsable: '',
    grupo_sanguineo: '',
    alergias: '',
  },
  // Fase II: Motivo de Consulta e Historia de la Enfermedad
  motivo_consulta: '',
  sintoma_principal: '',
  tiempo_inicio_sintomas: '',
  tratamiento_previo: '',
  enfermedades_actuales: '',
  bajo_tratamiento_medico: false,
  detalle_tratamiento_actual: '',
  alergias_paciente: '',
  // Fase III: Antecedentes Médicos y Odontológicos
  intervenciones_quirurgicas_previas: false,
  detalle_intervenciones: '',
  hemorragia_post_tratamiento: false,
  problema_anestesia: false,
  dificultad_abrir_masticar: false,
  sensibilidad_dental: false,
})

const siguiente = () => {
  // Validar paso actual
  if (currentStep.value === 1) {
    if (!formData.datos_paciente.nombres || !formData.datos_paciente.apellidos) {
      Swal.fire('Error', 'Los nombres y apellidos son obligatorios', 'error')
      return
    }
  }

  if (currentStep.value === 2) {
    if (!formData.motivo_consulta) {
      Swal.fire('Error', 'El motivo de consulta es obligatorio', 'error')
      return
    }
  }

  if (currentStep.value === 3) {
    // Última fase, guardar
    guardar()
    return
  }

  currentStep.value++
}

const anterior = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const guardar = async () => {
  guardando.value = true
  try {
    // Preparar datos para enviar, convirtiendo strings vacíos en campos opcionales
    const datosParaEnviar = {
      ...formData,
      datos_paciente: {
        ...formData.datos_paciente,
        sexo: formData.datos_paciente.sexo || undefined,
      }
    }

    await crearHistorialClinico(datosParaEnviar)

    Swal.fire({
      icon: 'success',
      title: '¡Historial Creado!',
      text: 'El paciente ha sido registrado exitosamente y su historial clínico ha sido creado.',
      confirmButtonText: 'Entendido'
    })

    emit('guardado')
    emit('cerrar')
  } catch (error: any) {
    console.error('Error al crear historial:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'No se pudo crear el historial clínico',
    })
  } finally {
    guardando.value = false
  }
}

const cerrar = () => {
  emit('cerrar')
}
</script>
