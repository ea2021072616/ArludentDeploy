<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Welcome Section -->
      <div class="bg-gradient-to-r from-dental-600 to-primary-600 rounded-2xl p-8 text-white">
        <h1 class="text-3xl font-bold mb-2">Tu Salud Dental</h1>
        <p class="text-primary-100">Consulta tus citas, historial y perfil</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Citas Próximas</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">2</p>
            </div>
            <div class="w-12 h-12 bg-dental-100 rounded-lg flex items-center justify-center">
              <Calendar class="w-6 h-6 text-dental-600" />
            </div>
          </div>
        </Card>
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Tratamientos Activos</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">1</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <FileText class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Citas Pasadas</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">5</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <Calendar class="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </Card>
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Saldo Pendiente</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">S/ 0</p>
            </div>
            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
              <DollarSign class="w-6 h-6 text-primary-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Quick Actions -->
      <Card title="Accesos Rápidos">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            to="/citas"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <Calendar class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Agendar Cita</span>
          </router-link>
          <router-link
            to="/historial"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <FileText class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Ver Historial</span>
          </router-link>
          <router-link
            to="/perfil"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <Settings class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Mi Perfil</span>
          </router-link>
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import { Calendar, FileText, DollarSign, Settings } from 'lucide-vue-next'
</script>
