<script setup lang="ts">
interface Props {
  modelValue?: string | number
  options: Array<{ value: string | number; label: string }>
  label?: string
  placeholder?: string
  error?: string
  required?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  label: '',
  placeholder: 'Seleccione una opción',
  error: '',
  required: false,
  disabled: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const handleChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  const value = target.value

  // Intentar convertir a número si el valor original es número
  const numValue = Number(value)
  emit('update:modelValue', isNaN(numValue) ? value : numValue)
}
</script>

<template>
  <div class="space-y-1">
    <label v-if="label" class="block text-sm font-medium text-gray-700">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <select
      :value="modelValue"
      @change="handleChange"
      :disabled="disabled"
      class="w-full px-3 py-2 bg-white border rounded-xl focus:ring-2 focus:ring-primary-400/30 focus:border-primary-400 transition-all duration-300 placeholder:text-gray-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
      :class="{
        'border-red-300 bg-red-50/40': error,
        'border-gray-200': !error
      }"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>

    <p v-if="error" class="text-sm text-red-600">
      {{ error }}
    </p>
  </div>
</template>
