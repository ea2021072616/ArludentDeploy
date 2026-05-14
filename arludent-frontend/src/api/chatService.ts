/**
 * Servicio para interactuar con el microservicio de IA (Chat)
 *
 * Este servicio maneja la comunicación con el agente conversacional
 * para consultas, agendamiento de citas y asistencia general.
 */

import axios from 'axios'

// URL del microservicio de IA
const AI_API_URL = import.meta.env.VITE_AI_MICROSERVICE_URL || 'http://localhost:8001/api/v1'

// ============================================
// TIPOS
// ============================================

export interface ChatMessage {
  role: 'user' | 'assistant'
  content: string
  timestamp?: string
  metadata?: any  // Metadata adicional para renderizado rico (ej: CitasCard)
}

export interface ChatRequest {
  message: string
  user_id: number
  session_id?: string
  context?: Record<string, any>
}

export interface ChatResponse {
  message: string
  session_id: string
  timestamp: string
  metadata?: any  // Puede contener datos estructurados para renderizado rico
  suggested_actions?: string[] | null
}

export interface SessionInfo {
  session_id: string
  user_id: number
  message_count: number
  created_at: string
}

export interface HealthStatus {
  status: string
  services: {
    backend: boolean
    openai: boolean
    agent: boolean
  }
}

// ============================================
// SERVICIO DE CHAT
// ============================================

class ChatService {
  private sessionId: string | null = null

  /**
   * Envía un mensaje al agente conversacional
   */
  async sendMessage(message: string, userId: number, context?: Record<string, any>): Promise<ChatResponse> {
    try {
      const payload: ChatRequest = {
        message,
        user_id: userId,
        context
      }

      // Si ya hay una sesión activa, incluir el ID
      if (this.sessionId) {
        payload.session_id = this.sessionId
      }

      const response = await axios.post<ChatResponse>(`${AI_API_URL}/chat`, payload, {
        headers: {
          'Content-Type': 'application/json'
        },
        timeout: 30000 // 30 segundos de timeout
      })

      // Guardar el session_id para futuras conversaciones
      if (response.data.session_id) {
        this.sessionId = response.data.session_id
        this.saveSessionToStorage(response.data.session_id)
      }

      return response.data
    } catch (error: any) {
      console.error('Error al enviar mensaje al chat:', error)

      if (error.response) {
        throw new Error(error.response.data?.detail || 'Error al comunicarse con el asistente')
      } else if (error.request) {
        throw new Error('No se pudo conectar con el asistente. Verifica tu conexión.')
      } else {
        throw new Error('Error inesperado al enviar el mensaje')
      }
    }
  }

  /**
   * Verifica el estado de salud del microservicio
   */
  async checkHealth(): Promise<HealthStatus> {
    try {
      const response = await axios.get<HealthStatus>(`${AI_API_URL}/health`)
      return response.data
    } catch (error) {
      console.error('Error al verificar salud del servicio:', error)
      throw new Error('Servicio de chat no disponible')
    }
  }

  /**
   * Obtiene información de la sesión actual
   */
  async getSessionInfo(sessionId: string): Promise<SessionInfo> {
    try {
      const response = await axios.get<SessionInfo>(`${AI_API_URL}/sessions/${sessionId}`)
      return response.data
    } catch (error) {
      console.error('Error al obtener información de sesión:', error)
      throw new Error('No se pudo obtener información de la sesión')
    }
  }

  /**
   * Limpia la sesión actual (inicia nueva conversación)
   */
  clearSession(): void {
    this.sessionId = null
    this.removeSessionFromStorage()
  }

  /**
   * Obtiene el ID de sesión actual
   */
  getCurrentSessionId(): string | null {
    if (!this.sessionId) {
      this.sessionId = this.loadSessionFromStorage()
    }
    return this.sessionId
  }

  /**
   * Establece manualmente un ID de sesión
   */
  setSessionId(sessionId: string): void {
    this.sessionId = sessionId
    this.saveSessionToStorage(sessionId)
  }

  // ============================================
  // MÉTODOS PRIVADOS - PERSISTENCIA LOCAL
  // ============================================

  private saveSessionToStorage(sessionId: string): void {
    try {
      localStorage.setItem('chat_session_id', sessionId)
    } catch (error) {
      console.warn('No se pudo guardar session_id en localStorage:', error)
    }
  }

  private loadSessionFromStorage(): string | null {
    try {
      return localStorage.getItem('chat_session_id')
    } catch (error) {
      console.warn('No se pudo cargar session_id desde localStorage:', error)
      return null
    }
  }

  private removeSessionFromStorage(): void {
    try {
      localStorage.removeItem('chat_session_id')
    } catch (error) {
      console.warn('No se pudo eliminar session_id de localStorage:', error)
    }
  }
}

// Exportar instancia única del servicio
export default new ChatService()
