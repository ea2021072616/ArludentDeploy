<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
  >
    <component v-if="loading" :is="Loader2" class="w-5 h-5 animate-spin" />
    <component v-else-if="icon" :is="icon" class="w-5 h-5" />
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'

interface Props {
  type?: 'button' | 'submit' | 'reset'
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  icon?: any
  fullWidth?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'button',
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  fullWidth: false
})

const buttonClasses = computed(() => {
  const classes = ['btn flex items-center justify-center space-x-2']

  // Variant
  switch (props.variant) {
    case 'primary':
      classes.push('btn-primary')
      break
    case 'secondary':
      classes.push('btn-secondary')
      break
    case 'success':
      classes.push('btn-success')
      break
    case 'danger':
      classes.push('btn-danger')
      break
    case 'outline':
      classes.push('btn-outline')
      break
  }

  // Size
  switch (props.size) {
    case 'sm':
      classes.push('text-sm px-3 py-1.5')
      break
    case 'lg':
      classes.push('text-lg px-6 py-3')
      break
  }

  // Full width
  if (props.fullWidth) {
    classes.push('w-full')
  }

  return classes.join(' ')
})
</script>
