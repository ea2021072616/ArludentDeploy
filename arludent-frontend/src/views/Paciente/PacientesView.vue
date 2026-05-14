<template>
  <DashboardLayout>
    <Card title="Gestión de Pacientes">
      <div class="text-center py-12">
        <Users class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Módulo de Pacientes</h3>
        <p class="text-gray-600">
          Este módulo está en desarrollo. Aquí podrás gestionar todos los pacientes.
        </p>
      </div>
    </Card>
  </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import { Users } from 'lucide-vue-next'
</script>
