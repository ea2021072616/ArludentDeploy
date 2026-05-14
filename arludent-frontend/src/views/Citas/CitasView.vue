<template>
  <DashboardLayout>
    <Card title="Gestión de Citas">
      <div class="text-center py-12">
        <Calendar class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Módulo de Citas</h3>
        <p class="text-gray-600">
          Este módulo está en desarrollo. Aquí podrás gestionar el calendario de citas.
        </p>
      </div>
    </Card>
  </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import { Calendar } from 'lucide-vue-next'
</script>
