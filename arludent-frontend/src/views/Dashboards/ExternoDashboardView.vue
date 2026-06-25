<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Welcome Section -->
      <div class="bg-gradient-to-r from-teal-500 to-cyan-600 rounded-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute right-0 top-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20"></div>
        <div class="absolute right-20 bottom-0 w-32 h-32 bg-white/5 rounded-full -mb-10"></div>
        <div class="relative z-10">
          <div class="flex items-center space-x-3 mb-3">
            <div class="bg-white/20 p-2 rounded-lg">
              <Smile class="w-6 h-6" />
            </div>
            <span class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full">Usuario Nuevo</span>
          </div>
          <h1 class="text-3xl font-bold mb-2">¡Bienvenido/a, {{ userName }}!</h1>
          <p class="text-teal-100 text-lg">Estamos encantados de que formes parte de Arludent</p>
        </div>
      </div>

      <!-- Info Card: Qué es un usuario externo -->
      <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-6">
        <div class="flex items-start space-x-4">
          <div class="bg-blue-100 p-3 rounded-xl flex-shrink-0">
            <Info class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-blue-900 mb-2">¿Qué significa ser un usuario externo?</h2>
            <p class="text-blue-800 leading-relaxed">
              Te has registrado exitosamente en nuestro sistema. Actualmente tienes acceso al <strong>chatbot de asistencia</strong> con inteligencia artificial
              y puedes gestionar tu <strong>perfil</strong>. Para acceder a todas las funcionalidades del sistema
              (historial clínico, tratamientos, seguimientos), necesitas convertirte en <strong>paciente</strong> de la clínica.
            </p>
          </div>
        </div>
      </div>

      <!-- Pasos para ser paciente -->
      <Card>
        <div class="p-2">
          <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <ClipboardList class="w-6 h-6 text-teal-600 mr-3" />
            ¿Cómo me convierto en paciente?
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Paso 1 -->
            <div class="relative bg-gradient-to-br from-teal-50 to-emerald-50 rounded-xl p-6 border border-teal-200">
              <div class="absolute -top-3 -left-3 bg-teal-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-lg">
                1
              </div>
              <div class="bg-teal-100 w-12 h-12 rounded-xl flex items-center justify-center mb-4 mt-2">
                <Phone class="w-6 h-6 text-teal-600" />
              </div>
              <h3 class="font-bold text-gray-900 mb-2">Agenda tu primera cita</h3>
              <p class="text-sm text-gray-600 leading-relaxed">
                Comunícate con la clínica por teléfono o presencialmente para agendar tu primera consulta odontológica.
              </p>
            </div>

            <!-- Paso 2 -->
            <div class="relative bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
              <div class="absolute -top-3 -left-3 bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-lg">
                2
              </div>
              <div class="bg-blue-100 w-12 h-12 rounded-xl flex items-center justify-center mb-4 mt-2">
                <Stethoscope class="w-6 h-6 text-blue-600" />
              </div>
              <h3 class="font-bold text-gray-900 mb-2">Consulta con el odontólogo</h3>
              <p class="text-sm text-gray-600 leading-relaxed">
                En tu primera visita, el médico evaluará tu salud dental y completará tu ficha de paciente con todos tus datos clínicos.
              </p>
            </div>

            <!-- Paso 3 -->
            <div class="relative bg-gradient-to-br from-purple-50 to-violet-50 rounded-xl p-6 border border-purple-200">
              <div class="absolute -top-3 -left-3 bg-purple-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-lg">
                3
              </div>
              <div class="bg-purple-100 w-12 h-12 rounded-xl flex items-center justify-center mb-4 mt-2">
                <FileText class="w-6 h-6 text-purple-600" />
              </div>
              <h3 class="font-bold text-gray-900 mb-2">Historial clínico creado</h3>
              <p class="text-sm text-gray-600 leading-relaxed">
                Una vez completada tu evaluación, se creará tu historial clínico y podrás acceder a todas las funcionalidades del sistema como paciente.
              </p>
            </div>
          </div>
        </div>
      </Card>

      <!-- Lo que puedes hacer ahora -->
      <Card title="¿Qué puedes hacer ahora?">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <router-link
            to="/perfil"
            class="flex items-center space-x-4 p-5 bg-gray-50 hover:bg-teal-50 rounded-xl transition-all duration-200 group border border-transparent hover:border-teal-200"
          >
            <div class="bg-teal-100 p-3 rounded-xl group-hover:bg-teal-200 transition-colors">
              <UserCog class="w-6 h-6 text-teal-600" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 group-hover:text-teal-700">Completar mi perfil</h3>
              <p class="text-sm text-gray-500">Actualiza tu información personal, teléfono y contraseña</p>
            </div>
            <ChevronRight class="w-5 h-5 text-gray-400 group-hover:text-teal-600 ml-auto" />
          </router-link>

          <div
            class="flex items-center space-x-4 p-5 bg-gray-50 hover:bg-indigo-50 rounded-xl transition-all duration-200 group border border-transparent hover:border-indigo-200 cursor-pointer"
            @click="abrirChatbot"
          >
            <div class="bg-indigo-100 p-3 rounded-xl group-hover:bg-indigo-200 transition-colors">
              <MessageCircle class="w-6 h-6 text-indigo-600" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700">Chatbot de asistencia</h3>
              <p class="text-sm text-gray-500">Pregunta sobre servicios, horarios y más con nuestro asistente IA</p>
            </div>
            <ChevronRight class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 ml-auto" />
          </div>
        </div>
      </Card>

      <!-- Información de contacto -->
      <div class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-2xl p-6 border border-gray-200">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
          <Building class="w-5 h-5 text-gray-600 mr-2" />
          Información de la clínica
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="flex items-center space-x-3">
            <div class="bg-white p-2 rounded-lg shadow-sm">
              <MapPin class="w-5 h-5 text-red-500" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Dirección</p>
              <p class="text-sm font-medium text-gray-900">Av. Principal 123, Lima</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <div class="bg-white p-2 rounded-lg shadow-sm">
              <Phone class="w-5 h-5 text-green-500" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Teléfono</p>
              <p class="text-sm font-medium text-gray-900">(01) 234-5678</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <div class="bg-white p-2 rounded-lg shadow-sm">
              <Clock class="w-5 h-5 text-blue-500" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Horario de atención</p>
              <p class="text-sm font-medium text-gray-900">Lun - Vie: 8:00 - 20:00</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import {
  Smile, Info, ClipboardList, Phone, Stethoscope, FileText,
  UserCog, MessageCircle, ChevronRight, Building, MapPin, Clock
} from 'lucide-vue-next'

const authStore = useAuthStore()
const userName = computed(() => authStore.user?.username || 'Usuario')

const abrirChatbot = () => {
  // Buscar y hacer clic en el botón del chatbot si existe
  const chatButton = document.querySelector('[data-chat-toggle]') as HTMLElement
  if (chatButton) {
    chatButton.click()
  }
}
</script>
