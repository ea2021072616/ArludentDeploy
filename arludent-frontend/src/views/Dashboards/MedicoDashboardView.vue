<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Welcome Section -->
      <div class="bg-gradient-to-r from-blue-600 to-dental-600 rounded-2xl p-8 text-white">
        <h1 class="text-3xl font-bold mb-2">Panel del Médico</h1>
        <p class="text-primary-100">Gestiona tus pacientes, citas y perfil profesional</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Pacientes Activos</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">48</p>
            </div>
            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
              <Users class="w-6 h-6 text-primary-600" />
            </div>
          </div>
        </Card>
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Citas Hoy</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">7</p>
            </div>
            <div class="w-12 h-12 bg-dental-100 rounded-lg flex items-center justify-center">
              <Calendar class="w-6 h-6 text-dental-600" />
            </div>
          </div>
        </Card>
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Historiales Actualizados</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">12</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <FileText class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>
        <Card hover>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Disponibilidad</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">OK</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <Stethoscope class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Quick Actions -->
      <Card title="Accesos Rápidos">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            to="/pacientes"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <Users class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Pacientes</span>
          </router-link>
          <router-link
            to="/medico/mis-citas/listado"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <Calendar class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Mis Citas</span>
          </router-link>
          <router-link
            to="/citas"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <CalendarDays class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Agendar Cita</span>
          </router-link>
          <router-link
            to="/historial"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <FileText class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Historial</span>
          </router-link>
          <router-link
            to="/medico/perfil-profesional"
            class="flex flex-col items-center justify-center p-6 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group"
          >
            <Stethoscope class="w-8 h-8 text-gray-600 group-hover:text-primary-600 mb-2" />
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">Perfil Profesional</span>
          </router-link>
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import { Stethoscope, Calendar, CalendarDays, Users, FileText } from 'lucide-vue-next'
</script>
