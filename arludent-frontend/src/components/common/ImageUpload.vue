<script setup lang="ts">
import { ref, watch } from 'vue'
import { Upload, X } from 'lucide-vue-next'

interface Props {
  modelValue?: File | null
  currentImageUrl?: string | null
  label?: string
  accept?: string
  maxSize?: number // en MB
  error?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  currentImageUrl: null,
  label: 'Foto de perfil',
  accept: 'image/png, image/jpeg, image/jpg',
  maxSize: 2
})

const emit = defineEmits<{
  'update:modelValue': [value: File | null]
  'remove': []
}>()

const previewUrl = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const errorMessage = ref<string>('')

// Mostrar imagen actual o preview
watch(() => props.currentImageUrl, (newUrl) => {
  if (!newUrl) {
    previewUrl.value = null
    return
  }

  // Solo actualizar si no hay un preview temporal de una nueva imagen
  // Si previewUrl es una data URL (starts with 'data:'), significa que el usuario
  // está seleccionando una nueva imagen, no la sobrescribimos
  const isPreviewDataUrl = previewUrl.value?.startsWith('data:')

  if (!isPreviewDataUrl) {
    previewUrl.value = newUrl
  }
}, { immediate: true })

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (!file) return

  // Validar tipo de archivo
  const validExtensions = ['image/jpeg', 'image/jpg', 'image/png']
  if (!validExtensions.includes(file.type)) {
    errorMessage.value = 'Formato de imagen no válido. Use JPG, JPEG o PNG.'
    if (fileInput.value) {
      fileInput.value.value = ''
    }
    return
  }

  // Validar tamaño
  const maxBytes = props.maxSize * 1024 * 1024
  if (file.size > maxBytes) {
    errorMessage.value = `La imagen no debe superar ${props.maxSize}MB`
    if (fileInput.value) {
      fileInput.value.value = ''
    }
    return
  }

  errorMessage.value = ''

  // Crear preview
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target?.result as string
  }
  reader.readAsDataURL(file)

  emit('update:modelValue', file)
}

const removeImage = () => {
  previewUrl.value = null
  errorMessage.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  emit('update:modelValue', null)
  emit('remove')
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleImageError = (event: Event) => {
  console.error('❌ Error al cargar la imagen:', previewUrl.value)
  const img = event.target as HTMLImageElement
  console.error('❌ Detalles del error:', {
    src: img.src,
    naturalWidth: img.naturalWidth,
    naturalHeight: img.naturalHeight,
    complete: img.complete
  })
}
</script>

<template>
  <div class="space-y-2">
    <label class="block text-sm font-medium text-gray-700">
      {{ label }}
    </label>

    <!-- Preview de imagen -->
    <div v-if="previewUrl" class="relative inline-block">
      <img
        :src="previewUrl"
        alt="Foto de perfil"
        class="w-32 h-32 object-cover rounded-lg border-2 border-gray-200"
        @error="handleImageError"
        @load="() => console.log('✅ Imagen cargada correctamente:', previewUrl)"
      />
      <button
        type="button"
        @click="removeImage"
        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors"
      >
        <X :size="16" />
      </button>
    </div>

    <!-- Botón de upload -->
    <div v-else>
      <button
        type="button"
        @click="triggerFileInput"
        class="flex items-center justify-center gap-2 px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors"
      >
        <Upload :size="20" class="text-gray-400" />
        <span class="text-sm text-gray-600">Seleccionar imagen</span>
      </button>
    </div>

    <!-- Input oculto -->
    <input
      ref="fileInput"
      type="file"
      :accept="accept"
      @change="handleFileSelect"
      class="hidden"
    />

    <!-- Mensaje de ayuda -->
    <p class="text-xs text-gray-500">
      Formatos: JPG, JPEG, PNG. Máximo {{ maxSize }}MB
    </p>

    <!-- Errores -->
    <p v-if="errorMessage || error" class="text-sm text-red-600">
      {{ errorMessage || error }}
    </p>
  </div>
</template>
