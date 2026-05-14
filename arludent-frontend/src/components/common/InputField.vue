<template>
  <div class="space-y-1">
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :autocomplete="autocomplete"
        :class="[
          'input-field',
          { 'input-error': error },
          { 'pl-10': icon }
        ]"
      />
      <div v-if="icon" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
        <component :is="icon" class="w-5 h-5" />
      </div>
    </div>
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    <p v-else-if="helpText" class="text-sm text-gray-500">{{ helpText }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  id?: string
  label?: string
  type?: string
  modelValue: string
  placeholder?: string
  disabled?: boolean
  required?: boolean
  error?: string
  helpText?: string
  icon?: any
  autocomplete?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  placeholder: '',
  disabled: false,
  required: false,
  autocomplete: 'off'
})

defineEmits(['update:modelValue'])

const id = computed(() => props.id || `input-${Math.random().toString(36).substr(2, 9)}`)
</script>
