<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50/80 via-white to-dental-50/60 relative overflow-hidden">
    <!-- Decorative Background Pattern -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-100/40 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-dental-100/30 rounded-full blur-3xl"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-accent-50/20 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 bg-white/70 backdrop-blur-xl border-b border-gray-200/50 shadow-sm">
      <div class="container mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <!-- Logo de Arludent (clicable -> home) -->
            <router-link to="/" class="inline-flex items-center group" aria-label="Ir al inicio">
              <img
                src="/images/logo-arludent.png"
                alt="Arludent - Odontología Integral"
                class="h-11 w-auto group-hover:scale-105 transition-transform duration-300"
              />
            </router-link>
          </div>
          <p class="text-sm text-gray-500 hidden md:block font-medium">Sistema de Gestión Odontológica</p>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 container mx-auto px-4 py-10">
      <div class="max-w-md mx-auto">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 fixed bottom-0 left-0 right-0 bg-white/60 backdrop-blur-xl border-t border-gray-200/50 py-3">
      <div class="container mx-auto px-4">
        <p class="text-center text-xs text-gray-400 font-medium">
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
