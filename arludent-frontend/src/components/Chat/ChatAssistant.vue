<template>
  <div class="chat-assistant-container">
    <!-- Botón flotante para abrir/cerrar el chat -->
    <Transition name="fab">
      <button
        v-if="!isOpen"
        @click="toggleChat"
        class="chat-fab"
        aria-label="Abrir asistente virtual"
      >
        <div class="fab-glow"></div>
        <div class="fab-sparkle"></div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
        </svg>
        <span v-if="unreadCount > 0" class="chat-badge">{{ unreadCount }}</span>
      </button>
    </Transition>

    <!-- Ventana del chat -->
    <Transition name="chat">
      <div v-if="isOpen" class="chat-window">
        <!-- Header Premium -->
        <div class="chat-header">
          <div class="header-gradient"></div>
          <div class="header-content">
            <div class="chat-header-info">
              <!-- Avatar animado -->
              <div class="chat-avatar" :class="{ 'talking': isLoading }">
                <div class="avatar-ring"></div>
                <div class="avatar-inner">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                  </svg>
                </div>
                <div class="pulse-ring" v-if="isLoading"></div>
              </div>
              <div>
                <h3 class="chat-title">
                  <span class="title-text">Agente Arludent</span>
                  <span class="premium-badge">IA</span>
                </h3>
                <p class="chat-status">
                  <span class="status-dot" :class="{ 'online': isOnline }"></span>
                  <span v-if="isLoading" class="status-typing">Escribiendo...</span>
                  <span v-else>{{ isOnline ? 'En línea' : 'Desconectado' }}</span>
                </p>
              </div>
            </div>
            <div class="chat-actions">
              <button
                @click="clearConversation"
                class="chat-action-btn"
                title="Nueva conversación"
                :disabled="isLoading"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
              </button>
              <button
                @click="toggleChat"
                class="chat-action-btn"
                aria-label="Cerrar chat"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Mensajes -->
        <div ref="messagesContainer" class="chat-messages">
          <!-- Mensaje de bienvenida mejorado -->
          <div v-if="messages.length === 0" class="welcome-message">
            <!-- Mascota ARLU - Diente Kawaii Animado -->
            <div class="arlu-mascot">
              <div class="arlu-glow"></div>
              <!-- Cuerpo del diente -->
              <div class="arlu-body">
                <!-- Cara kawaii -->
                <div class="arlu-face">
                  <!-- Ojos -->
                  <div class="arlu-eyes">
                    <div class="arlu-eye left">
                      <div class="arlu-pupil"></div>
                      <div class="arlu-shine"></div>
                    </div>
                    <div class="arlu-eye right">
                      <div class="arlu-pupil"></div>
                      <div class="arlu-shine"></div>
                    </div>
                  </div>
                  <!-- Sonrisa -->
                  <div class="arlu-smile"></div>
                  <!-- Mejillas -->
                  <div class="arlu-cheeks">
                    <div class="arlu-cheek left"></div>
                    <div class="arlu-cheek right"></div>
                  </div>
                </div>
                <!-- Brillo del diente -->
                <div class="arlu-sparkle"></div>
              </div>
              <!-- Nombre -->
              <div class="arlu-name">ARLU</div>
            </div>
            <h4 class="welcome-title">¡Hola! Soy ARLU</h4>
            <p class="welcome-subtitle">Tu asistente dental inteligente</p>
            <p class="welcome-prompt">¿En qué puedo ayudarte hoy?</p>

            <!-- Acciones Rápidas -->
            <div class="quick-actions">
              <button
                @click="sendQuickAction('Quiero agendar una cita')"
                class="quick-action-btn"
                :disabled="isLoading || !isOnline"
              >
                <svg class="action-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span>Agendar cita</span>
              </button>

              <button
                @click="sendQuickAction('Muestra mis citas')"
                class="quick-action-btn"
                :disabled="isLoading || !isOnline"
              >
                <svg class="action-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <span>Ver mis citas</span>
              </button>

              <button
                @click="sendQuickAction('Muestra mis pagos')"
                class="quick-action-btn"
                :disabled="isLoading || !isOnline"
              >
                <svg class="action-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                </svg>
                <span>Ver pagos</span>
              </button>

              <button
                @click="sendQuickAction('¿Qué tratamientos tengo?')"
                class="quick-action-btn"
                :disabled="isLoading || !isOnline"
              >
                <svg class="action-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                </svg>
                <span>Mis tratamientos</span>
              </button>

              <button
                @click="sendQuickAction('Muéstrame mi odontograma')"
                class="quick-action-btn"
                :disabled="isLoading || !isOnline"
              >
                <svg class="action-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                </svg>
                <span>Ver odontograma</span>
              </button>

              <button
                @click="sendQuickAction('Información de la clínica')"
                class="quick-action-btn"
                :disabled="isLoading || !isOnline"
              >
                <svg class="action-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <span>Info clínica</span>
              </button>
            </div>
          </div>

          <!-- Mensajes de la conversación -->
          <TransitionGroup name="message">
            <div
              v-for="(msg, index) in messages"
              :key="index"
              class="message-wrapper"
              :class="msg.role"
            >
              <!-- Avatar para mensajes del asistente -->
              <div v-if="msg.role === 'assistant'" class="message-avatar">
                <div class="avatar-small">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                  </svg>
                </div>
              </div>

              <div class="message-bubble">
                <!-- Contenido de mensaje normal -->
                <div class="message-content" v-html="formatMessage(msg.content)"></div>
                <div class="message-time">{{ formatTime(msg.timestamp) }}</div>
              </div>
            </div>
          </TransitionGroup>

          <!-- Indicador de escritura mejorado -->
          <Transition name="typing">
            <div v-if="isLoading" class="message-wrapper assistant">
              <div class="message-avatar">
                <div class="avatar-small talking">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                  </svg>
                </div>
              </div>
              <div class="message-bubble typing-indicator">
                <div class="typing-dots">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Input de mensaje premium -->
        <div class="chat-input-wrapper">
          <form @submit.prevent="sendMessage" class="chat-input-form">
            <input
              v-model="currentMessage"
              type="text"
              placeholder="Escribe tu mensaje..."
              class="chat-input"
              :disabled="isLoading || !isOnline"
              @keypress.enter.prevent="sendMessage"
            />
            <button
              type="submit"
              class="chat-send-btn"
              :disabled="!currentMessage.trim() || isLoading || !isOnline"
              aria-label="Enviar mensaje"
            >
              <div class="btn-glow"></div>
              <svg v-if="!isLoading" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
              </svg>
              <div v-else class="loading-spinner">
                <div class="spinner-ring"></div>
              </div>
            </button>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import chatService, { type ChatMessage } from '@/api/chatService'

// ============================================
// ESTADO
// ============================================

const authStore = useAuthStore()
const isOpen = ref(false)
const isLoading = ref(false)
const isOnline = ref(false)
const currentMessage = ref('')
const messages = ref<ChatMessage[]>([])
const messagesContainer = ref<HTMLElement | null>(null)
const unreadCount = ref(0)

// ============================================
// MÉTODOS
// ============================================

/**
 * Abre/cierra la ventana del chat
 */
const toggleChat = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    unreadCount.value = 0
    nextTick(() => {
      scrollToBottom()
    })
  }
}

/**
 * Envía un mensaje al asistente
 */
const sendMessage = async () => {
  if (!currentMessage.value.trim() || isLoading.value || !isOnline.value) return

  const userMessage = currentMessage.value.trim()
  currentMessage.value = ''

  await processMessage(userMessage)
}

/**
 * Envía un mensaje de acción rápida predefinido
 */
const sendQuickAction = async (message: string) => {
  if (isLoading.value || !isOnline.value) return
  await processMessage(message)
}

/**
 * Procesa un mensaje (ya sea del input o de acción rápida)
 */
const processMessage = async (userMessage: string) => {
  // Agregar mensaje del usuario
  messages.value.push({
    role: 'user',
    content: userMessage,
    timestamp: new Date().toISOString()
  })

  scrollToBottom()
  isLoading.value = true

  try {
    // Enviar al microservicio
    const response = await chatService.sendMessage(userMessage, authStore.user.id_usuario)

    // Agregar respuesta del asistente
    messages.value.push({
      role: 'assistant',
      content: response.message,
      timestamp: response.timestamp,
      metadata: response.metadata
    })

    // Si el chat está cerrado, aumentar contador de no leídos
    if (!isOpen.value) {
      unreadCount.value++
    }

    scrollToBottom()
  } catch (error: any) {
    console.error('Error al enviar mensaje:', error)

    // Mensaje de error para el usuario
    messages.value.push({
      role: 'assistant',
      content: '❌ Lo siento, hubo un error al procesar tu mensaje. Por favor, intenta de nuevo.',
      timestamp: new Date().toISOString()
    })
  } finally {
    isLoading.value = false
  }
}

/**
 * Limpia la conversación y comienza una nueva
 */
const clearConversation = () => {
  if (confirm('¿Seguro que quieres iniciar una nueva conversación? Se perderá el historial actual.')) {
    messages.value = []
    chatService.clearSession()
  }
}

/**
 * Scroll automático al último mensaje
 */
const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

/**
 * Formatea el contenido del mensaje (convierte saltos de línea, etc.)
 */
const formatMessage = (content: string): string => {
  // Convertir saltos de línea a <br>
  let formatted = content.replace(/\n/g, '<br>')

  // Convertir enlaces a <a> tags
  formatted = formatted.replace(
    /(https?:\/\/[^\s]+)/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
  )

  return formatted
}

/**
 * Formatea la hora del mensaje
 */
const formatTime = (timestamp?: string): string => {
  if (!timestamp) return ''

  const date = new Date(timestamp)
  return date.toLocaleTimeString('es-PE', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Verifica el estado del servicio
 */
const checkServiceHealth = async () => {
  try {
    await chatService.checkHealth()
    isOnline.value = true
  } catch (error) {
    isOnline.value = false
    console.error('Servicio de chat no disponible:', error)
  }
}

// ============================================
// LIFECYCLE HOOKS
// ============================================

onMounted(async () => {
  // Verificar salud del servicio
  await checkServiceHealth()

  // Verificar cada 30 segundos
  setInterval(checkServiceHealth, 30000)
})

// Auto-scroll cuando hay nuevos mensajes
watch(() => messages.value.length, () => {
  scrollToBottom()
})
</script>

<style scoped>
/* ========================================
   TIPOGRAFÍA PREMIUM
   ======================================== */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

/* ========================================
   VARIABLES DE COLOR PREMIUM - EN COMPONENTE
   ======================================== */
.chat-assistant-container {
  --gradient-primary: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  --gradient-gold: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
  --gradient-purple-gold: linear-gradient(135deg, #1f2937 0%, #f6d365 50%, #111827 100%);
  --shadow-glow: 0 0 30px rgba(0, 0, 0, 0.3);
  --shadow-gold: 0 0 20px rgba(246, 211, 101, 0.4);
  --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

  /* Aplicar tipografía */
  font-family: var(--font-family);
}/* ========================================
   CONTENEDOR PRINCIPAL
   ======================================== */
.chat-assistant-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1000;
}

/* ========================================
   BOTÓN FLOTANTE (FAB) PREMIUM
   ======================================== */
.chat-fab {
  position: relative;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  color: white;
  border: none;
  box-shadow:
    0 8px 32px rgba(0, 0, 0, 0.4),
    0 4px 8px rgba(0, 0, 0, 0.2),
    inset 0 1px 2px rgba(255, 255, 255, 0.1);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  overflow: hidden;
}

.chat-fab::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(
    45deg,
    transparent 30%,
    rgba(255, 255, 255, 0.3) 50%,
    transparent 70%
  );
  animation: shimmer 3s infinite;
}

.fab-glow {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: var(--gradient-gold);
  opacity: 0;
  filter: blur(12px);
  transition: opacity 0.3s;
  z-index: -1;
}

.chat-fab:hover .fab-glow {
  opacity: 0.6;
  animation: pulse-glow 2s infinite;
}

.fab-sparkle {
  position: absolute;
  top: 10%;
  right: 10%;
  width: 8px;
  height: 8px;
  background: #fda085;
  border-radius: 50%;
  box-shadow: 0 0 8px rgba(253, 160, 133, 0.8);
  animation: sparkle 2s infinite;
}

.chat-fab:hover {
  transform: scale(1.15) rotate(5deg);
  box-shadow:
    0 12px 48px rgba(102, 126, 234, 0.5),
    0 6px 12px rgba(0, 0, 0, 0.2),
    inset 0 1px 2px rgba(255, 255, 255, 0.4);
}

.chat-fab svg {
  width: 32px;
  height: 32px;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
  z-index: 1;
}

.chat-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  border-radius: 14px;
  padding: 3px 8px;
  font-size: 11px;
  font-weight: 700;
  min-width: 22px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.5);
  border: 2px solid white;
  animation: badge-bounce 0.5s ease;
}

/* ========================================
   VENTANA DEL CHAT PREMIUM
   ======================================== */
.chat-window {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 400px;
  height: 640px;
  max-height: calc(100vh - 100px);
  background: white;
  border-radius: 24px;
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.3),
    0 8px 16px rgba(0, 0, 0, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Header Premium */
.chat-header {
  position: relative;
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  color: white;
  padding: 20px;
  overflow: hidden;
}

.header-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100%;
  background: linear-gradient(135deg, #1f2937 0%, #f6d365 50%, #111827 100%);
  opacity: 0.3;
  animation: gradient-shift 6s ease infinite;
}

.header-content {
  position: relative;
  z-index: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-header-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

/* Avatar Premium Animado */
.chat-avatar {
  position: relative;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-ring {
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  background: var(--gradient-gold);
  opacity: 0.6;
  animation: rotate-ring 3s linear infinite;
}

.avatar-inner {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.avatar-inner svg {
  width: 26px;
  height: 26px;
  color: white;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.pulse-ring {
  position: absolute;
  inset: -8px;
  border-radius: 50%;
  border: 3px solid #fda085;
  opacity: 0;
  animation: pulse-wave 1.5s ease-out infinite;
}

.chat-avatar.talking .avatar-inner {
  animation: talk-bounce 0.6s ease-in-out infinite;
}

.chat-avatar.talking .avatar-ring {
  animation: rotate-ring 3s linear infinite, pulse-glow 0.6s ease-in-out infinite;
}

/* Avatar pequeño en mensajes */
.message-avatar {
  margin-right: 10px;
}

.avatar-small {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--gradient-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow:
    0 4px 12px rgba(102, 126, 234, 0.3),
    inset 0 1px 2px rgba(255, 255, 255, 0.3);
  border: 2px solid white;
}

.avatar-small svg {
  width: 18px;
  height: 18px;
  color: white;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

.avatar-small.talking {
  animation: talk-bounce 0.6s ease-in-out infinite;
  box-shadow:
    0 4px 12px rgba(102, 126, 234, 0.5),
    0 0 20px rgba(246, 211, 101, 0.4),
    inset 0 1px 2px rgba(255, 255, 255, 0.3);
}

.chat-title {
  font-size: 17px;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  letter-spacing: -0.3px;
}

.title-text {
  color: #ffffff;
  font-weight: 600;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.premium-badge {
  font-size: 11px;
  background: rgba(246, 211, 101, 0.2);
  padding: 3px 10px;
  border-radius: 8px;
  border: 1px solid rgba(246, 211, 101, 0.3);
  font-weight: 600;
  letter-spacing: 0.5px;
  color: #fbbf24;
}

.chat-status {
  font-size: 12px;
  margin: 4px 0 0 0;
  display: flex;
  align-items: center;
  gap: 6px;
  opacity: 0.95;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.95);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #9ca3af;
  transition: all 0.3s;
}

.status-dot.online {
  background-color: #10b981;
  box-shadow: 0 0 12px rgba(16, 185, 129, 0.8);
  animation: pulse-dot 2s infinite;
}

.status-typing {
  animation: fade-in-out 1.5s ease infinite;
}

.chat-actions {
  display: flex;
  gap: 10px;
}

.chat-action-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.chat-action-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.1);
}

.chat-action-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.chat-action-btn svg {
  width: 18px;
  height: 18px;
  color: white;
  filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.3));
}

/* ========================================
   ÁREA DE MENSAJES
   ======================================== */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  background: linear-gradient(180deg, #f8f9fc 0%, #ffffff 100%);
  scroll-behavior: smooth;
}

/* Barra de scroll personalizada */
.chat-messages::-webkit-scrollbar {
  width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.chat-messages::-webkit-scrollbar-thumb {
  background: var(--gradient-primary);
  border-radius: 10px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: var(--gradient-purple-gold);
}

/* Mensaje de bienvenida premium */
.welcome-message {
  text-align: center;
  padding: 40px 20px;
}

/* ========================================
   MASCOTA ARLU - DIENTE KAWAII
   ======================================== */
.arlu-mascot {
  position: relative;
  width: 100px;
  height: 150px;
  margin: 0 auto 24px;
  animation: arlu-float 3s ease-in-out infinite;
}

@keyframes arlu-float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.arlu-glow {
  position: absolute;
  inset: -10px;
  background: radial-gradient(circle, #fbbf24 0%, #f59e0b 50%, #f6d365 100%);
  filter: blur(25px);
  opacity: 0.6;
  border-radius: 50%;
  animation: pulse-glow 2.5s infinite;
}

.arlu-body {
  position: relative;
  width: 100px;
  height: 120px;
  background: linear-gradient(180deg, #ffffff 0%, #fffef9 40%, #fef9f3 100%);
  border-radius: 50% 50% 45% 45% / 55% 55% 45% 45%;
  border: 3px solid rgba(251, 191, 36, 0.2);
  box-shadow:
    0 20px 50px rgba(251, 191, 36, 0.2),
    inset 0 8px 25px rgba(255, 255, 255, 1),
    inset 0 -5px 20px rgba(251, 191, 36, 0.1),
    0 0 60px rgba(255, 255, 255, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Brillo superior del diente - MÁS SUAVE */
.arlu-body::after {
  content: '';
  position: absolute;
  top: 15px;
  left: 22%;
  width: 45%;
  height: 30%;
  background: radial-gradient(ellipse at center, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.2) 100%);
  border-radius: 50%;
  filter: blur(10px);
  pointer-events: none;
}

.arlu-face {
  position: relative;
  width: 80%;
  height: 70%;
}

.arlu-eyes {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 60%;
  margin: 15px auto 10px;
}

.arlu-eye {
  position: relative;
  width: 18px;
  height: 22px;
  background: #2d3748;
  border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
  animation: arlu-blink 4s infinite;
}

@keyframes arlu-blink {
  0%, 90%, 100% { transform: scaleY(1); }
  95% { transform: scaleY(0.1); }
}

.arlu-pupil {
  position: absolute;
  width: 8px;
  height: 10px;
  background: #1a202c;
  border-radius: 50%;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.arlu-shine {
  position: absolute;
  width: 5px;
  height: 5px;
  background: white;
  border-radius: 50%;
  top: 4px;
  left: 4px;
  box-shadow: 0 0 3px rgba(255, 255, 255, 0.8);
}

.arlu-smile {
  width: 30px;
  height: 15px;
  border: 3px solid #f687b3;
  border-top: none;
  border-radius: 0 0 30px 30px;
  margin: 0 auto;
  position: relative;
  top: 5px;
  animation: arlu-smile 2s ease-in-out infinite;
}

@keyframes arlu-smile {
  0%, 100% { transform: scaleX(1); }
  50% { transform: scaleX(1.1); }
}

.arlu-cheeks {
  position: absolute;
  width: 100%;
  top: 35px;
  display: flex;
  justify-content: space-between;
  padding: 0 5px;
}

.arlu-cheek {
  width: 12px;
  height: 12px;
  background: rgba(246, 135, 179, 0.3);
  border-radius: 50%;
  animation: arlu-blush 3s ease-in-out infinite;
}

@keyframes arlu-blush {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 0.6; }
}

.arlu-sparkle {
  position: absolute;
  top: 10px;
  right: 15px;
  width: 24px;
  height: 24px;
  background: linear-gradient(45deg, #fbbf24, #fef3c7, #fbbf24);
  clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
  animation: arlu-sparkle 1.5s ease-in-out infinite;
  filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.9));
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.8);
}

@keyframes arlu-sparkle {
  0%, 100% { transform: scale(0.9) rotate(0deg); opacity: 0.8; }
  50% { transform: scale(1.1) rotate(20deg); opacity: 1; }
}

.arlu-name {
  margin-top: 12px;
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
  letter-spacing: 2px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* ========================================
   TEXTOS DE BIENVENIDA
   ======================================== */
.welcome-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
}

.welcome-subtitle {
  font-size: 15px;
  color: #4b5563;
  margin: 0 0 20px 0;
  font-weight: 500;
}

.welcome-prompt {
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 20px 0;
  font-size: 15px;
  letter-spacing: -0.3px;
}

/* ========================================
   ACCIONES RÁPIDAS - BOTONES INTERACTIVOS
   ======================================== */
.quick-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  margin: 20px 0;
  max-width: 340px;
  margin-left: auto;
  margin-right: auto;
}

.quick-action-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
  border-radius: 14px;
  border: 2px solid rgba(102, 126, 234, 0.15);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  font-size: 13px;
  color: #4b5563;
  font-weight: 600;
  text-align: left;
  position: relative;
  overflow: hidden;
}

.quick-action-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
  transition: left 0.5s;
}

.quick-action-btn:hover:not(:disabled)::before {
  left: 100%;
}

.quick-action-btn:hover:not(:disabled) {
  transform: translateY(-4px) scale(1.02);
  box-shadow:
    0 8px 20px rgba(102, 126, 234, 0.25),
    0 4px 8px rgba(0, 0, 0, 0.1);
  border-color: rgba(102, 126, 234, 0.4);
  background: linear-gradient(135deg, #ffffff 0%, #f0f4ff 100%);
}

.quick-action-btn:active:not(:disabled) {
  transform: translateY(-2px) scale(0.98);
}

.quick-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.action-icon {
  width: 22px;
  height: 22px;
  color: #667eea;
  flex-shrink: 0;
  transition: transform 0.3s;
}

.quick-action-btn:hover:not(:disabled) .action-icon {
  transform: scale(1.15) rotate(5deg);
}

.quick-action-btn span {
  flex: 1;
  line-height: 1.3;
}

/* Burbujas de mensaje premium */
.message-wrapper {
  display: flex;
  margin-bottom: 16px;
  animation: slide-up 0.4s ease;
}

.message-wrapper.user {
  justify-content: flex-end;
}

.message-wrapper.assistant {
  justify-content: flex-start;
}

.message-bubble {
  max-width: 75%;
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 14px;
  line-height: 1.6;
  position: relative;
}

.message-wrapper.user .message-bubble {
  background: var(--gradient-primary);
  color: white;
  border-bottom-right-radius: 6px;
  box-shadow:
    0 4px 12px rgba(102, 126, 234, 0.3),
    inset 0 1px 2px rgba(255, 255, 255, 0.2);
}

.message-wrapper.assistant .message-bubble {
  background: white;
  color: #1f2937;
  border: 1px solid #e5e7eb;
  border-bottom-left-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.message-content {
  word-wrap: break-word;
  line-height: 1.6;
  font-weight: 400;
  letter-spacing: -0.1px;
}

.message-content :deep(a) {
  color: #667eea;
  text-decoration: underline;
  font-weight: 500;
}

.message-time {
  font-size: 11px;
  margin-top: 6px;
  opacity: 0.7;
  font-weight: 500;
  letter-spacing: 0.3px;
}

/* Indicador de escritura premium */
.typing-indicator {
  background: white !important;
  border: 1px solid #e5e7eb !important;
  padding: 16px 20px !important;
}

.typing-dots {
  display: flex;
  gap: 6px;
  align-items: center;
}

.typing-dots span {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--gradient-primary);
  animation: typing-bounce 1.4s infinite;
  box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.typing-dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
  animation-delay: 0.4s;
}

/* ========================================
   INPUT DE MENSAJE PREMIUM
   ======================================== */
.chat-input-wrapper {
  padding: 16px 20px;
  background: white;
  border-top: 1px solid rgba(102, 126, 234, 0.1);
}

.chat-input-form {
  display: flex;
  gap: 10px;
  align-items: center;
}

.chat-input {
  flex: 1;
  padding: 12px 18px;
  border: 2px solid #e5e7eb;
  border-radius: 24px;
  font-size: 14px;
  outline: none;
  transition: all 0.3s;
  background: #f9fafb;
  font-weight: 400;
  letter-spacing: -0.1px;
  line-height: 1.5;
}

.chat-input::placeholder {
  color: #9ca3af;
  font-weight: 400;
}

.chat-input:focus {
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.chat-input:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
  opacity: 0.6;
}

.chat-send-btn {
  position: relative;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--gradient-primary);
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  box-shadow:
    0 4px 12px rgba(102, 126, 234, 0.3),
    inset 0 1px 2px rgba(255, 255, 255, 0.2);
  overflow: hidden;
}

.btn-glow {
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  background: var(--gradient-gold);
  opacity: 0;
  filter: blur(8px);
  transition: opacity 0.3s;
  z-index: 0;
}

.chat-send-btn:hover:not(:disabled) .btn-glow {
  opacity: 0.6;
}

.chat-send-btn:hover:not(:disabled) {
  transform: scale(1.1);
  box-shadow:
    0 6px 16px rgba(102, 126, 234, 0.4),
    inset 0 1px 2px rgba(255, 255, 255, 0.3);
}

.chat-send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.chat-send-btn svg {
  width: 22px;
  height: 22px;
  z-index: 1;
}

.loading-spinner {
  width: 24px;
  height: 24px;
  position: relative;
  z-index: 1;
}

.spinner-ring {
  width: 100%;
  height: 100%;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* ========================================
   ANIMACIONES
   ======================================== */

@keyframes shimmer {
  0% { transform: translate(-50%, -50%) rotate(0deg); }
  100% { transform: translate(-50%, -50%) rotate(360deg); }
}

@keyframes pulse-glow {
  0%, 100% { opacity: 0.4; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(1.1); }
}

@keyframes sparkle {
  0%, 100% { opacity: 0; transform: scale(0); }
  50% { opacity: 1; transform: scale(1); }
}

@keyframes badge-bounce {
  0% { transform: scale(0); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

@keyframes gradient-shift {
  0%, 100% { transform: translateX(0); opacity: 0.3; }
  50% { transform: translateX(10px); opacity: 0.5; }
}

@keyframes rotate-ring {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes talk-bounce {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

@keyframes pulse-wave {
  0% {
    transform: scale(1);
    opacity: 0.7;
  }
  100% {
    transform: scale(1.6);
    opacity: 0;
  }
}

@keyframes pulse-dot {
  0%, 100% { box-shadow: 0 0 12px rgba(16, 185, 129, 0.8); }
  50% { box-shadow: 0 0 20px rgba(16, 185, 129, 1); }
}

@keyframes fade-in-out {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes typing-bounce {
  0%, 60%, 100% {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
  }
  30% {
    transform: translateY(-10px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.5);
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* FAB transitions */
.fab-enter-active,
.fab-leave-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.fab-enter-from,
.fab-leave-to {
  transform: scale(0) rotate(-180deg);
  opacity: 0;
}

/* Chat window transitions */
.chat-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.chat-leave-active {
  transition: all 0.3s ease;
}

.chat-enter-from {
  transform: translateY(30px) scale(0.95);
  opacity: 0;
}

.chat-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

/* Message transitions */
.message-enter-active {
  transition: all 0.4s ease;
}

.message-enter-from {
  transform: translateY(15px);
  opacity: 0;
}

/* Typing indicator transition */
.typing-enter-active,
.typing-leave-active {
  transition: all 0.3s ease;
}

.typing-enter-from,
.typing-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* ========================================
   RESPONSIVE
   ======================================== */
@media (max-width: 480px) {
  .chat-assistant-container {
    bottom: 16px;
    right: 16px;
  }

  .chat-window {
    width: calc(100vw - 32px);
    height: calc(100vh - 100px);
    max-height: calc(100vh - 100px);
    border-radius: 20px;
  }

  .chat-fab {
    width: 56px;
    height: 56px;
  }

  .quick-actions {
    grid-template-columns: 1fr;
    gap: 8px;
  }
}
</style>
