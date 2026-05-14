<template>
  <div :class="['card', { 'card-hover': hover }]">
    <div v-if="title || $slots.header" class="mb-4 pb-4 border-b border-gray-200">
      <slot name="header">
        <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
      </slot>
    </div>

    <div class="card-content">
      <slot />
    </div>

    <div v-if="$slots.footer" class="mt-4 pt-4 border-t border-gray-200">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title?: string
  hover?: boolean
}

withDefaults(defineProps<Props>(), {
  hover: false
})
</script>
