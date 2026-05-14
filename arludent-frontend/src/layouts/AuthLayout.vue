<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-dental-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <!-- Logo de Arludent (clicable -> home) -->
            <router-link to="/" class="inline-flex items-center" aria-label="Ir al inicio">
              <img
                src="/images/logo-arludent.png"
                alt="Arludent - Odontología Integral"
                class="h-12 w-auto"
              />
            </router-link>
          </div>
          <p class="text-sm text-gray-600 hidden md:block">Sistema de Gestión Odontológica</p>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
      <div class="max-w-md mx-auto">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-sm border-t border-gray-200 py-4">
      <div class="container mx-auto px-4">
        <p class="text-center text-sm text-gray-600">
          &copy; {{ currentYear }} Arludent. Todos los derechos reservados.
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>
