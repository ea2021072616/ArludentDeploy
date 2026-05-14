<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <Card>
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Mi Historial Clínico</h1>
            <p class="text-gray-600">Consulta tu historial clínico y tratamientos</p>
          </div>
        </div>
      </Card>

      <!-- Loading -->
      <Card v-if="loading">
        <div class="flex justify-center py-12">
          <Loading :show="true" />
        </div>
      </Card>

      <!-- Sin historial -->
      <Card v-else-if="!tieneHistorial">
        <div class="text-center py-12">
          <FileText class="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Aún no tienes historial clínico</h3>
          <p class="text-gray-600 mb-6">
            {{ mensajeSinHistorial }}
          </p>
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-md mx-auto">
            <div class="flex">
              <Info class="w-5 h-5 text-blue-400 mt-0.5 mr-3" />
              <div class="text-sm text-blue-700">
                <p>Tu historial clínico será creado por tu médico durante tu primera consulta.</p>
              </div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Historial existente -->
      <div v-else-if="historial" class="space-y-6">
        
        <!-- Header con información básica -->
        <Card>
          <div class="bg-gradient-to-r from-blue-50 to-cyan-50 -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="bg-blue-600 p-2 rounded-lg">
                  <FileText class="w-5 h-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900">Información del Historial</h3>
                  <p class="text-sm text-gray-600">Código: {{ historial.codigo_historial }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Médico Responsable</label>
              <p class="text-gray-900">{{ historial.medicoResponsable?.nombres }} {{ historial.medicoResponsable?.apellidos }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Fecha de Creación</label>
              <p class="text-gray-900">{{ formatDate(historial.created_at) }}</p>
            </div>
          </div>
        </Card>

        <!-- Tabs Navigation -->
        <Card class="overflow-hidden">
          <div class="border-b border-gray-200 bg-gray-50 -mx-6 -mt-6">
            <nav class="flex space-x-1 p-2">
              <button
                @click="tabActivo = 'informacion'"
                :class="[
                  'flex-1 py-3 px-4 rounded-lg font-medium text-sm transition-all duration-200 flex items-center justify-center space-x-2',
                  tabActivo === 'informacion'
                    ? 'bg-white text-blue-600 shadow-md ring-2 ring-blue-500/20 transform scale-105'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'
                ]"
              >
                <FileText :class="['w-5 h-5', tabActivo === 'informacion' ? 'text-blue-600' : 'text-gray-400']" />
                <span>Información General</span>
              </button>
              <button
                @click="tabActivo = 'odontograma'"
                :class="[
                  'flex-1 py-3 px-4 rounded-lg font-medium text-sm transition-all duration-200 flex items-center justify-center space-x-2',
                  tabActivo === 'odontograma'
                    ? 'bg-white text-blue-600 shadow-md ring-2 ring-blue-500/20 transform scale-105'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'
                ]"
              >
                <Info :class="['w-5 h-5', tabActivo === 'odontograma' ? 'text-blue-600' : 'text-gray-400']" />
                <span>Odontograma</span>
              </button>
            </nav>
          </div>
        </Card>

        <!-- TAB: Información General -->
        <div v-if="tabActivo === 'informacion'" class="space-y-6">

        <!-- FASE I. DATOS PERSONALES -->
        <Card>
          <div class="bg-gradient-to-r from-blue-50 to-cyan-50 -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
              <div class="bg-cyan-600 p-2 rounded-lg">
                <Info class="w-5 h-5 text-white" />
              </div>
              <h3 class="text-lg font-bold text-gray-900">FASE I. DATOS PERSONALES</h3>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Nombres</label>
              <p class="text-gray-900">{{ historial.paciente.nombres }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Apellidos</label>
              <p class="text-gray-900">{{ historial.paciente.apellidos }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">DNI</label>
              <p class="text-gray-900">{{ historial.paciente.dni || 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Fecha de Nacimiento</label>
              <p class="text-gray-900">{{ historial.paciente.fecha_nacimiento ? formatDate(historial.paciente.fecha_nacimiento) : 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Sexo</label>
              <p class="text-gray-900">{{ historial.paciente.sexo || 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Domicilio</label>
              <p class="text-gray-900">{{ historial.paciente.domicilio || 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Persona Responsable</label>
              <p class="text-gray-900">{{ historial.paciente.persona_responsable || 'No especificado' }}</p>
            </div>
            <div class="space-y-1 bg-blue-50 p-3 rounded-lg border border-blue-200">
              <label class="text-xs font-semibold text-blue-700 uppercase">Teléfono Principal</label>
              <p class="text-blue-900 font-semibold">{{ historial.paciente.telefono || 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Teléfono de Contacto</label>
              <p class="text-gray-900">{{ historial.paciente.telefono_responsable || 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Correo Electrónico</label>
              <p class="text-gray-900">{{ historial.paciente.correo || 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Grupo Sanguíneo</label>
              <p class="text-gray-900">{{ historial.paciente.grupo_sanguineo || 'No especificado' }}</p>
            </div>
            <div class="md:col-span-3 space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Alergias</label>
              <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-red-900">{{ historial.paciente.alergias || 'No especificado' }}</p>
              </div>
            </div>
          </div>
        </Card>

        <!-- FASE II. MOTIVO DE CONSULTA -->
        <Card>
          <div class="bg-gradient-to-r from-purple-50 to-pink-50 -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
              <div class="bg-purple-600 p-2 rounded-lg">
                <FileText class="w-5 h-5 text-white" />
              </div>
              <h3 class="text-lg font-bold text-gray-900">FASE II. MOTIVO DE CONSULTA</h3>
            </div>
          </div>
          <div class="space-y-4 text-sm">
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Motivo de Consulta</label>
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <p class="text-gray-900">{{ historial.motivo_consulta || 'No especificado' }}</p>
              </div>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Síntoma Principal</label>
              <p class="text-gray-900">{{ historial.sintoma_principal || 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Tiempo de Inicio de Síntomas</label>
              <p class="text-gray-900">{{ historial.tiempo_inicio_sintomas || 'No especificado' }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-semibold text-gray-500 uppercase">Tratamiento Previo</label>
              <p class="text-gray-900">{{ historial.tratamiento_previo || 'No especificado' }}</p>
            </div>
          </div>
        </Card>

        <!-- FASE III. ANTECEDENTES -->
        <Card>
          <div class="bg-gradient-to-r from-orange-50 to-amber-50 -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
              <div class="bg-orange-600 p-2 rounded-lg">
                <Info class="w-5 h-5 text-white" />
              </div>
              <h3 class="text-lg font-bold text-gray-900">FASE III. ANTECEDENTES</h3>
            </div>
          </div>

          <div class="space-y-6">
            <!-- Antecedentes Médicos -->
            <div>
              <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                <div class="w-1 h-5 bg-orange-500 mr-2 rounded"></div>
                Antecedentes Médicos
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Enfermedades Actuales</label>
                  <p class="text-gray-900">{{ historial.enfermedades_actuales || 'No especificado' }}</p>
                </div>
                <div class="space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Bajo Tratamiento Médico</label>
                  <p class="text-gray-900">{{ historial.bajo_tratamiento_medico ? 'Sí' : 'No' }}</p>
                </div>
                <div v-if="historial.bajo_tratamiento_medico" class="md:col-span-2 space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Detalle del Tratamiento Actual</label>
                  <p class="text-gray-900">{{ historial.detalle_tratamiento_actual || 'No especificado' }}</p>
                </div>
                <div class="md:col-span-2 space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Alergias del Paciente</label>
                  <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <p class="text-red-900">{{ historial.alergias_paciente || 'No especificado' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Antecedentes Quirúrgicos -->
            <div>
              <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                <div class="w-1 h-5 bg-orange-500 mr-2 rounded"></div>
                Antecedentes Quirúrgicos
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Intervenciones Quirúrgicas Previas</label>
                  <p class="text-gray-900">{{ historial.intervenciones_quirurgicas_previas ? 'Sí' : 'No' }}</p>
                </div>
                <div v-if="historial.intervenciones_quirurgicas_previas" class="space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Detalle de Intervenciones</label>
                  <p class="text-gray-900">{{ historial.detalle_intervenciones || 'No especificado' }}</p>
                </div>
              </div>
            </div>

            <!-- Problemas Específicos -->
            <div>
              <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                <div class="w-1 h-5 bg-orange-500 mr-2 rounded"></div>
                Problemas Específicos
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded-lg">
                  <input
                    :checked="historial.hemorragia_post_tratamiento"
                    type="checkbox"
                    disabled
                    class="rounded border-gray-300"
                  />
                  <label class="text-gray-700">Hemorragia post-tratamiento</label>
                </div>
                <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded-lg">
                  <input
                    :checked="historial.problema_anestesia"
                    type="checkbox"
                    disabled
                    class="rounded border-gray-300"
                  />
                  <label class="text-gray-700">Problemas con anestesia</label>
                </div>
                <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded-lg">
                  <input
                    :checked="historial.dificultad_abrir_masticar"
                    type="checkbox"
                    disabled
                    class="rounded border-gray-300"
                  />
                  <label class="text-gray-700">Dificultad para abrir/masticar</label>
                </div>
                <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded-lg">
                  <input
                    :checked="historial.sensibilidad_dental"
                    type="checkbox"
                    disabled
                    class="rounded border-gray-300"
                  />
                  <label class="text-gray-700">Sensibilidad dental</label>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Detalles del Historial (Consultas) -->
        <Card v-if="historial.detalles && historial.detalles.length > 0">
          <div class="bg-gradient-to-r from-indigo-50 to-blue-50 -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
              <div class="bg-indigo-600 p-2 rounded-lg">
                <FileText class="w-5 h-5 text-white" />
              </div>
              <h3 class="text-lg font-bold text-gray-900">CONSULTAS Y PROCEDIMIENTOS</h3>
            </div>
          </div>
          <div class="space-y-4">
            <div v-for="(detalle, index) in historial.detalles" :key="detalle.id_detalle" class="bg-gradient-to-r from-gray-50 to-slate-50 p-5 rounded-lg border border-gray-200">
              <div class="flex justify-between items-start mb-3">
                <div class="flex items-center space-x-3">
                  <div class="bg-indigo-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                    #{{ index + 1 }}
                  </div>
                  <h4 class="font-semibold text-gray-900">{{ formatDate(detalle.fecha) }}</h4>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div v-if="detalle.diagnostico" class="space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Diagnóstico</label>
                  <div class="bg-white border border-gray-200 rounded-lg p-3">
                    <p class="text-gray-900">{{ detalle.diagnostico }}</p>
                  </div>
                </div>
                <div v-if="detalle.procedimiento_realizado" class="space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Procedimiento Realizado</label>
                  <div class="bg-white border border-gray-200 rounded-lg p-3">
                    <p class="text-gray-900">{{ detalle.procedimiento_realizado }}</p>
                  </div>
                </div>
                <div v-if="detalle.notas_medicas" class="md:col-span-2 space-y-1">
                  <label class="text-xs font-semibold text-gray-500 uppercase">Notas Médicas</label>
                  <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-blue-900">{{ detalle.notas_medicas }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Tratamientos -->
        <Card v-if="historial.tratamientos && historial.tratamientos.length > 0">
          <div class="bg-gradient-to-r from-teal-50 to-cyan-50 -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
              <div class="bg-teal-600 p-2 rounded-lg">
                <FileText class="w-5 h-5 text-white" />
              </div>
              <h3 class="text-lg font-bold text-gray-900">PLAN DE TRATAMIENTO</h3>
            </div>
          </div>
          <div class="space-y-3">
            <div v-for="tratamiento in historial.tratamientos" :key="tratamiento.id" class="flex justify-between items-center p-4 bg-gradient-to-r from-gray-50 to-slate-50 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
              <div class="flex-1">
                <div class="flex items-center space-x-2">
                  <span class="font-semibold text-gray-900">{{ tratamiento.tratamiento.nombre }}</span>
                  <span
                    class="px-3 py-1 text-xs font-semibold rounded-full"
                    :class="{
                      'bg-yellow-100 text-yellow-800 border border-yellow-300': tratamiento.estado === 'sugerido',
                      'bg-blue-100 text-blue-800 border border-blue-300': tratamiento.estado === 'en_curso',
                      'bg-green-100 text-green-800 border border-green-300': tratamiento.estado === 'completado',
                      'bg-red-100 text-red-800 border border-red-300': tratamiento.estado === 'cancelado'
                    }"
                  >
                    {{ tratamiento.estado === 'sugerido' ? 'Sugerido' : tratamiento.estado === 'en_curso' ? 'En Curso' : tratamiento.estado === 'completado' ? 'Completado' : 'Cancelado' }}
                  </span>
                </div>
                <p v-if="tratamiento.descripcion" class="text-sm text-gray-600 mt-1">{{ tratamiento.descripcion }}</p>
              </div>
            </div>
          </div>
        </Card>
        </div>

        <!-- TAB: Odontograma -->
        <div v-if="tabActivo === 'odontograma'" class="space-y-6">
          <!-- Evaluación Diagnóstica -->
          <Card>
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200">
              <div class="flex items-center space-x-3">
                <div class="bg-green-600 p-2 rounded-lg">
                  <FileText class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-lg font-bold text-gray-900">EVALUACIÓN DIAGNÓSTICA</h3>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div class="space-y-1">
                <label class="text-xs font-semibold text-gray-500 uppercase">Diagnóstico Presuntivo</label>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                  <p class="text-yellow-900">{{ historial.diagnostico_presuntivo || 'No especificado' }}</p>
                </div>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-gray-500 uppercase">Diagnóstico Principal</label>
                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                  <p class="text-green-900">{{ historial.diagnostico_principal || 'No especificado' }}</p>
                </div>
              </div>
              <div class="md:col-span-2 space-y-1">
                <label class="text-xs font-semibold text-gray-500 uppercase">Higiene Bucal</label>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                  <p class="text-blue-900">{{ historial.higiene_bucal || 'No especificado' }}</p>
                </div>
              </div>
            </div>
          </Card>

          <!-- Odontograma -->
          <Card>
            <OdontogramaInteractivo
              :id-historial="historial.id_historial"
              :piezas-dentales="historial.odontograma || []"
              :solo-lectura="true"
            />
          </Card>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Info, FileText } from 'lucide-vue-next'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Loading from '@/components/common/Loading.vue'
import OdontogramaInteractivo from '@/components/historial/OdontogramaInteractivo.vue'
import historialClinicoService, { type HistorialClinico } from '@/api/historialClinicoService'
import Swal from 'sweetalert2'

// Stores
// const toast = useToastStore()

// Estado
const loading = ref(true)
const tieneHistorial = ref(false)
const mensajeSinHistorial = ref('')
const historial = ref<HistorialClinico | null>(null)
const tabActivo = ref<'informacion' | 'odontograma'>('informacion')

// Funciones
const cargarMiHistorial = async () => {
  loading.value = true
  try {
    const data = await historialClinicoService.obtenerMiHistorial()
    tieneHistorial.value = data.tiene_historial
    mensajeSinHistorial.value = data.mensaje || ''
    historial.value = data.historial || null
  } catch (error) {
    console.error('Error al cargar historial:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al cargar tu historial clínico'
    })
  } finally {
    loading.value = false
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  cargarMiHistorial()
})
</script>
