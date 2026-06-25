<template>
  <div class="space-y-6">
    <!-- Header profesional -->
    <div class="relative bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 rounded-2xl p-5 text-white overflow-hidden shadow-primary">
      <div class="absolute inset-0 opacity-[0.07]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;20&quot; height=&quot;20&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Ccircle cx=&quot;2&quot; cy=&quot;2&quot; r=&quot;1&quot; fill=&quot;white&quot;/%3E%3C/svg%3E'); background-size: 20px 20px;"></div>
      <div class="relative flex justify-between items-center">
        <div>
          <h3 class="text-lg font-semibold tracking-tight">Odontograma Avanzado</h3>
          <p class="text-sm text-primary-100 mt-0.5">Estado interactivo de la salud dental</p>
        </div>
        <div class="text-right">
          <Button v-if="!soloLectura" @click="solicitarGuardado" variant="outline" class="text-primary-700 hover:bg-gray-50 bg-white border-white" size="sm" :loading="guardando">
            <Save class="w-4 h-4 mr-2" />
            Guardar Odontograma
          </Button>
        </div>
      </div>
    </div>

    <!-- Odontograma React Iframe -->
    <div class="bg-white border border-gray-100 rounded-2xl shadow-soft overflow-hidden h-[800px] relative">
      <div v-if="cargando" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600 mb-3"></div>
        <span class="text-sm font-medium text-gray-500">Cargando módulo dental...</span>
      </div>

      <div v-if="guardando" class="absolute inset-0 bg-white/90 backdrop-blur-md z-20 flex flex-col items-center justify-center">
        <div class="relative">
          <div class="animate-spin rounded-full h-14 w-14 border-4 border-primary-100 border-b-primary-600"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <Save class="w-5 h-5 text-primary-600 animate-pulse" />
          </div>
        </div>
        <span class="text-base font-bold text-primary-800 mt-4">Guardando Odontograma...</span>
        <span class="text-sm text-gray-500 mt-1">Por favor espere, procesando datos.</span>
      </div>
      
      <iframe 
        ref="iframeRef"
        :src="`/odontogram-react/index.html?v=${cacheBuster}`" 
        class="w-full h-full border-0"
        title="Odontograma Interactivo"
      ></iframe>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Save } from 'lucide-vue-next'
import Button from '@/components/common/Button.vue'
import historialClinicoService from '@/api/historialClinicoService'
import Swal from 'sweetalert2'

const cacheBuster = Date.now()

interface Props {
  idHistorial: number
  odontogramaState?: any
  soloLectura?: boolean
}

interface Emits {
  (e: 'actualizado'): void
}

const props = withDefaults(defineProps<Props>(), {
  soloLectura: false
})
const emit = defineEmits<Emits>()

const iframeRef = ref<HTMLIFrameElement | null>(null)
const cargando = ref(true)
const guardando = ref(false)
const iframeReady = ref(false)

const enviarEstadoAlIframe = () => {
  if (iframeRef.value?.contentWindow && iframeReady.value) {
    // Deep clone the object to strip Vue's reactive Proxies, 
    // because postMessage cannot clone Proxy objects
    const rawPayload = props.odontogramaState ? JSON.parse(JSON.stringify(props.odontogramaState)) : null;

    iframeRef.value.contentWindow.postMessage({
      type: 'IMPORT_STATE',
      payload: rawPayload,
      readOnly: props.soloLectura
    }, '*')
  }
}

const solicitarGuardado = () => {
  if (!iframeRef.value?.contentWindow) return
  guardando.value = true
  // Pedir al iframe que exporte JSON e imagen Base64
  iframeRef.value.contentWindow.postMessage({
    type: 'REQUEST_EXPORT'
  }, '*')
}

const handleMessage = async (event: MessageEvent) => {
  const data = event.data
  if (!data || !data.type) return

  if (data.type === 'ODONTOGRAM_READY') {
    cargando.value = false
    iframeReady.value = true
    enviarEstadoAlIframe()
  } 
  else if (data.type === 'EXPORT_RESULT') {
    try {
      await historialClinicoService.guardarOdontograma({
        id_historial: props.idHistorial,
        odontograma_state: data.payload,
        odontograma_image: data.image
      })
      Swal.fire({
        icon: 'success',
        title: '¡Guardado!',
        text: 'El odontograma ha sido guardado exitosamente.',
        timer: 2000,
        showConfirmButton: false
      })
      emit('actualizado')
    } catch (error) {
      console.error(error)
      Swal.fire('Error', 'No se pudo guardar el odontograma', 'error')
    } finally {
      guardando.value = false
    }
  }
}

watch(() => props.odontogramaState, () => {
  enviarEstadoAlIframe()
}, { deep: true })

onMounted(() => {
  window.addEventListener('message', handleMessage)
})

onUnmounted(() => {
  window.removeEventListener('message', handleMessage)
})
</script>