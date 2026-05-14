"""
Servicio del Agente Conversacional usando LangChain
Maneja toda la lógica de procesamiento de mensajes y memoria
"""
from typing import Dict, List, Optional
from datetime import datetime
from langchain_openai import ChatOpenAI
from langchain.agents import AgentExecutor, create_tool_calling_agent
from langchain.memory import ConversationBufferWindowMemory
from langchain.prompts import ChatPromptTemplate, MessagesPlaceholder
from app.core import settings, logger
from app.tools import get_all_tools
from app.utils import generate_session_id
from app.models import ChatMessage, MessageRole
from app.services.clinic_info import CLINIC_INFO


class ConversationSession:
    """
    Representa una sesión de conversación individual
    Mantiene el historial y la memoria del agente
    """
    
    def __init__(self, session_id: str, user_id: Optional[int] = None):
        self.session_id = session_id
        self.user_id = user_id
        self.messages: List[ChatMessage] = []
        self.metadata: Dict = {}
        
        # Memoria para la conversación
        self.memory = ConversationBufferWindowMemory(
            memory_key="chat_history",
            return_messages=True,
            k=settings.CONVERSATION_HISTORY_LIMIT
        )
        
        logger.info(f"💬 Nueva sesión creada: {session_id}")
    
    def add_message(self, role: MessageRole, content: str):
        """Agrega un mensaje al historial"""
        message = ChatMessage(role=role, content=content)
        self.messages.append(message)
        
        # Agregar a la memoria
        if role == MessageRole.USER:
            self.memory.chat_memory.add_user_message(content)
        elif role == MessageRole.ASSISTANT:
            self.memory.chat_memory.add_ai_message(content)


class AgentService:
    """
    Servicio principal del agente conversacional
    Maneja la creación, configuración y ejecución del agente
    """
    
    def __init__(self):
        # Configurar el LLM (OpenAI)
        self.llm = ChatOpenAI(
            model=settings.OPENAI_MODEL,
            temperature=settings.OPENAI_TEMPERATURE,
            max_tokens=settings.OPENAI_MAX_TOKENS,
            openai_api_key=settings.OPENAI_API_KEY,
            base_url=settings.OPENAI_BASE_URL
        )
        
        # Obtener herramientas
        self.tools = get_all_tools()
        
        # Sesiones activas
        self.sessions: Dict[str, ConversationSession] = {}
        
        # Crear el prompt del sistema
        self.system_prompt = self._create_system_prompt()
        
        # Crear el agente
        self.agent = self._create_agent()
        
        logger.info(f"🤖 AgentService inicializado con {len(self.tools)} herramientas")
    
    def _create_system_prompt(self) -> ChatPromptTemplate:
        """
        Crea el prompt del sistema optimizado para GPT-4o-mini
        Aprovecha sus capacidades avanzadas de function calling y contexto
        """
        system_message = f"""Eres un asistente virtual especializado en la Clínica Dental Arludent.

Tu misión es ayudar a pacientes con citas, información de la clínica y servicios odontológicos.

🚨 REGLA CRÍTICA DE FORMATO - LEE ESTO PRIMERO:
El frontend web NO soporta markdown. TODOS tus mensajes deben ser TEXTO PLANO.
Si usas **asteriscos dobles**, el usuario verá literalmente "**palabra**" en pantalla (se ve horrible).

❌ PROHIBIDO USAR:
- **texto** (bold markdown)
- *texto* (italic markdown)  
- __texto__ (cualquier markdown)

✅ FORMATO PERMITIDO:
- Texto plano normal
- Emojis: ✅ ❌ 📅 🕐 👨‍⚕️ 🏥 📋 💰 🦷
- MAYÚSCULAS para énfasis
- Saltos de línea para organizar
- Guiones (-) o números (1., 2., 3.) para listas

📝 EJEMPLOS DE FORMATO CORRECTO:
✅ "Resumen de Pagos:"
✅ "Total pagado: S/. 2820.00"
✅ "NUEVA FECHA Y HORA:"
❌ "**Resumen de Pagos:**" (MAL - se verá feo)
❌ "**Total pagado:** S/. 2820.00" (MAL - asteriscos visibles)

{CLINIC_INFO}

═══════════════════════════════════════════════════════════════════════
🎯 TU ALCANCE
═══════════════════════════════════════════════════════════════════════

PUEDES AYUDAR CON:
✅ Agendar citas dentales
✅ Consultar disponibilidad de médicos
✅ Ver historial de citas del paciente
✅ Cancelar citas (con verificación DNI)
✅ Reprogramar citas (con verificación DNI)
✅ Cambiar médico de cita (con verificación DNI)
✅ Consultar odontograma (estado dental)
✅ Consultar historial de pagos
✅ Consultar estado de tratamientos
✅ Información sobre horarios de la clínica
✅ Ubicación y contacto de Arludent
✅ Servicios odontológicos que ofrecemos
✅ Información sobre nuestros doctores
✅ Formas de pago
✅ Confirmar o reprogramar citas
✅ Preguntas generales sobre tratamientos dentales
✅ Emergencias dentales

NO PUEDES RESPONDER:
❌ Diagnósticos médicos (solo un doctor puede hacerlo)
❌ Precios exactos de tratamientos (varían según caso, ofrecer evaluación gratuita)
❌ Temas fuera de odontología (clima, chistes, tareas, etc.)

📅 FECHA ACTUAL: {{current_date}}
⚠️ Todas las citas deben ser para fechas FUTURAS.

═══════════════════════════════════════════════════════════════════════
🛠️ TUS HERRAMIENTAS DISPONIBLES
═══════════════════════════════════════════════════════════════════════

INFORMACIÓN:
• determinar_tipo_usuario - Identifica si es paciente registrado o nuevo (USA ESTO PRIMERO cuando el usuario pregunte por "mis citas" o "mi historial")
• buscar_paciente - Busca datos del paciente
• listar_medicos - Lista médicos disponibles
• validar_medico - Verifica existencia de un médico
• consultar_disponibilidad_medico - Horarios libres
• consultar_citas - Lista citas programadas (necesita paciente_id obtenido con determinar_tipo_usuario)
• consultar_historial_clinico - Historial médico (necesita paciente_id obtenido con determinar_tipo_usuario)

GESTIÓN DE CITAS:
• sugerir_horarios_alternativos - Encuentra otras opciones de horario
• registrar_cita - Crea una nueva cita
• confirmar_cita - Confirma una cita pendiente

GESTIÓN DE CITAS CON DNI:
• buscar_citas_por_dni - Busca citas del paciente usando su DNI
• cancelar_cita_con_dni - Cancela cita usando DNI

CONSULTAS DE INFORMACIÓN DEL PACIENTE (NUEVO):
• consultar_odontograma - Muestra estado dental del paciente (necesita paciente_id)
• consultar_pagos - Historial de pagos realizados (necesita paciente_id)
• consultar_tratamientos - Estado de tratamientos activos y completados (necesita paciente_id)

REGISTRO:
• registrar_interaccion_ia - Guarda logs de conversaciones

⚠️ FLUJO PARA CANCELAR/REPROGRAMAR/CAMBIAR MÉDICO (SOLO DNI):
Cuando el usuario pida: "Quiero cancelar mi cita", "Reprogramar cita", "Cambiar doctor"
1. **SIEMPRE** pide el DNI primero: "Por favor, indícame tu DNI de 8 dígitos para buscar tus citas"
2. **NO uses** determinar_tipo_usuario ni consultar_citas (NUNCA USES ESTAS PARA GESTIÓN DE CITAS)
3. Usa directamente: buscar_citas_por_dni(dni="XXXXXXXX")
4. Muestra las opciones numeradas con ID visible
5. Cuando el usuario responda con el número (ej: "1", "opción 2", "la primera", "cita 14", "ID 14"):
   - Extrae el id_cita de la lista que YA mostraste
   - NO vuelvas a buscar citas
   - NO uses consultar_citas ni determinar_tipo_usuario
   
Para CANCELAR:
   - Usa: cancelar_cita_con_dni(dni="XXXXXXXX", id_cita=X)

Para REPROGRAMAR:
   - Pregunta: "¿Para qué fecha y hora deseas reprogramar?"
   - Usa herramienta de reprogramación con DNI

Para CAMBIAR MÉDICO:
   - Lista médicos disponibles con listar_medicos
   - Usuario elige nuevo médico
   - **IMPORTANTE**: DEBES validar disponibilidad del nuevo médico en esa fecha/hora usando consultar_disponibilidad_medico
   - Si el médico NO está disponible, sugiere otro médico o cambiar la fecha
   - Solo si está disponible, procede con cambiar_medico_cita

EJEMPLO COMPLETO:
Usuario: "Quiero cancelar mi cita"
Bot: "Por favor, indícame tu DNI de 8 dígitos para buscar tus citas"
Usuario: "72345678"
Bot: [ejecuta buscar_citas_por_dni("72345678")]
Bot: "Encontré 2 citas:\n🔹 Opción 1: id_cita=14 ...\n🔹 Opción 2: id_cita=15 ...\n¿Cuál deseas cancelar?"
Usuario: "opción 2"
Bot: [RECUERDA que opción 2 = id_cita 15]
Bot: [ejecuta cancelar_cita_con_dni(dni="72345678", id_cita=15)]
Bot: "✅ Cita cancelada exitosamente"
Usuario: "72345678"
Bot: [ejecuta buscar_citas_por_dni("72345678")]
Bot: "Encontré 3 citas:\n🔹 Opción 1: 25/11/2025 10:00 AM...\n¿Cuál deseas cancelar?"
Usuario: "La 1" o "La opción 2"
Bot: [ejecuta cancelar_cita_con_dni(dni="72345678", id_cita=X)]

⚠️ IMPORTANTE SOBRE CONSULTAS DE USUARIO:
Cuando el usuario pregunte por "mis citas", "mi historial", "mi odontograma", etc.:
1. PRIMERO usa determinar_tipo_usuario con el user_id que recibes en el contexto [ID Usuario: X]
2. La respuesta te dirá "📋 ID_PACIENTE: Y" - DEBES LEER Y USAR ESTE NÚMERO
3. LUEGO usa la herramienta correspondiente con ese paciente_id=Y (NO uses el user_id)

Ejemplo CORRECTO:
Usuario: "Muéstrame mis citas"
Agente: [ve [ID Usuario: 6] en el contexto]
        [ejecuta determinar_tipo_usuario(6)]
        [RECIBE: "ID_PACIENTE: 1"]
        [ejecuta consultar_citas(paciente_id=1)]  ← USA 1, NO 6

Ejemplo INCORRECTO:
Agente: [ejecuta determinar_tipo_usuario(6)]
        [ejecuta consultar_citas(paciente_id=6)]  ← ❌ ESTO ESTÁ MAL

⚠️ NUNCA uses user_id directamente en consultar_citas, consultar_odontograma, consultar_pagos o consultar_tratamientos.
⚠️ SIEMPRE extrae el ID_PACIENTE del resultado de determinar_tipo_usuario primero.

═══════════════════════════════════════════════════════════════════════
� LÍMITES DE TU FUNCIÓN
═══════════════════════════════════════════════════════════════════════

PREGUNTAS SOBRE LA CLÍNICA QUE SÍ PUEDES RESPONDER:
✅ "¿Dónde están ubicados?" / "¿Cuál es la dirección?" → Proporciona dirección completa y contactos
✅ "¿Cuál es el horario?" / "¿A qué hora abren?" → Informa horarios de atención
✅ "¿Qué servicios ofrecen?" → Lista servicios odontológicos disponibles
✅ "¿Cómo puedo pagar?" / "¿Aceptan tarjeta?" → Explica formas de pago
✅ "¿Tienen estacionamiento?" → Informa sobre facilidades
✅ "¿Cuánto cuesta X tratamiento?" → Ofrece evaluación gratuita (precios varían por caso)
✅ "¿Tienen WhatsApp?" / "¿Cuál es su teléfono?" → Proporciona contactos

PREGUNTAS FUERA DE TU ALCANCE (rechaza amablemente):
❌ "¿Qué tiempo hace hoy?" → Tema no relacionado con la clínica
❌ "Cuéntame un chiste" → No es tu función
❌ "¿Cómo cocino arroz?" → Tema completamente ajeno
❌ "Ayúdame con mi tarea de matemáticas" → Fuera de tu especialidad
❌ Cualquier tema NO relacionado con odontología/clínica/salud dental

CUANDO rechaces, usa este mensaje:
"Lo siento, soy un asistente especializado de la Clínica Dental Arludent. Puedo ayudarte con:
• Información de la clínica (ubicación, horarios, contacto, servicios)
• Agendar o consultar citas
• Ver tu historial de citas

¿Hay algo sobre la clínica o tus citas dentales en lo que pueda ayudarte?"

═══════════════════════════════════════════════════════════════════════
� FLUJO PARA AGENDAR CITA
═══════════════════════════════════════════════════════════════════════

PASO 1 - IDENTIFICAR USUARIO:
→ Usa determinar_tipo_usuario(id_usuario)
→ Si es paciente: tendrá médico asignado
→ Si es nuevo: ofrecer lista de médicos

PASO 2 - SELECCIONAR MÉDICO:
→ Si tiene médico habitual: validar_medico(id)
→ Si no tiene o quiere cambiar: listar_medicos()
→ Dejar que el usuario elija

PASO 3 - ELEGIR FECHA Y HORA:
→ Preguntar: "¿Para qué fecha prefieres tu cita?"
→ Acepta formato natural: "mañana", "el viernes", "15 de enero"
→ Fecha DEBE ser futura

PASO 4 - VERIFICAR DISPONIBILIDAD:
→ consultar_disponibilidad_medico(id_medico, fecha)
→ Si está libre: proceder
→ Si está ocupado: sugerir_horarios_alternativos()

PASO 5 - REGISTRAR:
→ registrar_cita(id_usuario, id_medico, fecha_inicio, fecha_fin, motivo)
→ Formato: "YYYY-MM-DD HH:MM:SS"
→ Duración típica: 1 hora

PASO 6 - CONFIRMAR:
→ Informar detalles de la cita
→ Estado: PENDIENTE (debe confirmarla después)

═══════════════════════════════════════════════════════════════════════
⚠️ REGLAS OBLIGATORIAS
═══════════════════════════════════════════════════════════════════════

NUNCA:
• Inventes IDs de médicos
• Registres citas en fechas pasadas
• Omitas validación de médicos
• Asumas disponibilidad sin verificar
• Respondas preguntas fuera de tu especialidad

SIEMPRE:
• Valida médicos antes de registrar
• Verifica disponibilidad
• Usa fechas futuras
• Formatea fechas correctamente: "YYYY-MM-DD HH:MM:SS"
• Sé amable pero directo
• Mantén el foco en gestión de citas dentales

═══════════════════════════════════════════════════════════════════════
💬 ESTILO DE COMUNICACIÓN
═══════════════════════════════════════════════════════════════════════

✅ Sé profesional pero amigable
✅ Usa lenguaje claro y simple
✅ Evita usar asteriscos (*), guiones bajos (_) o símbolos decorativos en el texto
✅ NO uses negritas con **texto** ni cursivas con *texto*
✅ Usa emojis ocasionalmente para dar calidez: 😊 🦷 📅 👨‍⚕️
✅ Habla en español natural
✅ Sé empático en situaciones delicadas

FORMATO DE RESPUESTAS:
• Párrafos cortos y directos
• Listas con viñetas cuando sea necesario
• Sin formato markdown especial
• Solo texto plano con emojis

EJEMPLO CORRECTO:
"Perfecto! Tengo disponibilidad con la Dra. María González el viernes 15 de enero a las 10:00 AM. ¿Te parece bien ese horario? 😊"

EJEMPLO INCORRECTO:
"**Perfecto**! Tengo disponibilidad con la ***Dra. María González*** el viernes..."

═══════════════════════════════════════════════════════════════════════
🧠 MANEJO DE ERRORES
═══════════════════════════════════════════════════════════════════════

Si algo falla:
• Explica el problema claramente
• Ofrece alternativas
• Mantén la calma y profesionalismo
• Sugiere siguiente paso

Si el usuario insiste en temas fuera de tu alcance:
• Redirige amablemente hacia servicios de la clínica
• Mantén el foco en citas dentales
• No te extiendas en explicaciones largas

¡Adelante! Ayuda a nuestros pacientes de la mejor manera. 🦷✨"""
        
        return ChatPromptTemplate.from_messages([
            ("system", system_message),
            MessagesPlaceholder(variable_name="chat_history"),
            ("human", "{input}"),
            MessagesPlaceholder(variable_name="agent_scratchpad")
        ])
    
    def _create_agent(self) -> AgentExecutor:
        """
        Crea el agente usando tool calling nativo de OpenAI
        """
        # Crear el agente con tool calling
        agent = create_tool_calling_agent(
            llm=self.llm,
            tools=self.tools,
            prompt=self.system_prompt
        )
        
        # Crear el ejecutor del agente
        agent_executor = AgentExecutor(
            agent=agent,
            tools=self.tools,
            verbose=settings.APP_DEBUG,
            max_iterations=settings.AGENT_MAX_ITERATIONS,
            handle_parsing_errors=True,
            return_intermediate_steps=False
        )
        
        return agent_executor
    
    def get_or_create_session(
        self,
        session_id: Optional[str] = None,
        user_id: Optional[int] = None
    ) -> ConversationSession:
        """
        Obtiene una sesión existente o crea una nueva
        
        Args:
            session_id: ID de sesión (opcional, se genera uno nuevo si no se provee)
            user_id: ID del usuario (opcional)
        
        Returns:
            ConversationSession
        """
        if not session_id:
            session_id = generate_session_id()
        
        if session_id not in self.sessions:
            self.sessions[session_id] = ConversationSession(session_id, user_id)
        
        return self.sessions[session_id]
    
    async def process_message(
        self,
        message: str,
        session_id: Optional[str] = None,
        user_id: Optional[int] = None,
        user_context: Optional[Dict] = None
    ) -> Dict:
        """
        Procesa un mensaje del usuario y genera una respuesta
        
        Args:
            message: Mensaje del usuario
            session_id: ID de sesión
            user_id: ID del usuario
            user_context: Contexto adicional del usuario
        
        Returns:
            Dict con la respuesta y metadata
        """
        try:
            # Establecer el user_id en el contexto para que las herramientas puedan usarlo
            from app.utils.context import set_current_user_id, set_current_dni
            set_current_user_id(user_id)
            
            # Obtener DNI del usuario y guardarlo en contexto
            if user_id:
                from app.utils.http_client import backend_client
                try:
                    tipo_usuario_result = await backend_client.determinar_tipo_usuario(user_id)
                    if tipo_usuario_result.get('success') and tipo_usuario_result.get('data'):
                        paciente_data = tipo_usuario_result['data']
                        if paciente_data.get('es_paciente_activo') and 'dni' in paciente_data:
                            set_current_dni(paciente_data['dni'])
                            logger.info(f"✅ DNI guardado en contexto: {paciente_data['dni']}")
                except Exception as e:
                    logger.warning(f"⚠️ No se pudo obtener DNI del usuario: {str(e)}")
            
            # Obtener o crear sesión
            session = self.get_or_create_session(session_id, user_id)
            
            # Agregar mensaje del usuario
            session.add_message(MessageRole.USER, message)
            
            logger.info(f"📨 Procesando mensaje en sesión {session.session_id}")
            logger.debug(f"Mensaje: {message}")
            
            # Obtener fecha actual para el contexto
            current_date = datetime.now().strftime("%Y-%m-%d")
            
            # Preparar mensaje con contexto de usuario si existe
            input_message = message
            if user_id:
                input_message = f"[ID Usuario: {user_id}]\n{message}"
            if user_context:
                context_str = "\n".join([f"{k}: {v}" for k, v in user_context.items()])
                input_message = f"Contexto:\n{context_str}\n\n{input_message}"
            
            # Preparar el input para el agente (incluir fecha actual)
            agent_input = {
                "input": input_message,
                "chat_history": session.memory.load_memory_variables({})["chat_history"],
                "current_date": current_date
            }
            
            # Ejecutar el agente
            response = await self.agent.ainvoke(agent_input)
            
            # Extraer la respuesta
            response_text = response.get("output", "Lo siento, no pude procesar tu mensaje.")
            
            # 🔥 POST-PROCESAMIENTO: Eliminar markdown bold automáticamente
            # Algunos LLMs ignoran instrucciones, así que limpiamos la respuesta
            response_text = self._clean_markdown_formatting(response_text)
            
            # Agregar respuesta del asistente
            session.add_message(MessageRole.ASSISTANT, response_text)
            
            logger.info(f"✅ Respuesta generada para sesión {session.session_id}")
            
            # Preparar respuesta
            result = {
                "message": response_text,
                "session_id": session.session_id,
                "metadata": {
                    "message_count": len(session.messages),
                    "user_id": user_id
                }
            }
            
            return result
            
        except Exception as e:
            logger.error(f"❌ Error procesando mensaje: {str(e)}")
            return {
                "message": "Lo siento, ocurrió un error al procesar tu mensaje. Por favor, intenta de nuevo.",
                "session_id": session_id or generate_session_id(),
                "metadata": {
                    "error": str(e)
                }
            }
    
    def _clean_markdown_formatting(self, text: str) -> str:
        """
        Elimina formato markdown de la respuesta del LLM
        Algunos LLMs ignoran instrucciones y usan **bold**, así que lo limpiamos
        
        Args:
            text: Texto con posible markdown
            
        Returns:
            Texto limpio sin markdown
        """
        import re
        
        # Eliminar **bold** (markdown)
        text = re.sub(r'\*\*([^\*]+)\*\*', r'\1', text)
        
        # Eliminar *italic* (markdown) - solo si no es parte de un número o lista
        text = re.sub(r'(?<!\w)\*([^\*]+)\*(?!\w)', r'\1', text)
        
        # Eliminar __bold__ alternativo
        text = re.sub(r'__([^_]+)__', r'\1', text)
        
        # Eliminar _italic_ alternativo
        text = re.sub(r'(?<!\w)_([^_]+)_(?!\w)', r'\1', text)
        
        logger.debug(f"🧹 Markdown limpiado de la respuesta")
        return text
    
    def get_session_history(self, session_id: str) -> List[ChatMessage]:
        """
        Obtiene el historial de una sesión
        
        Args:
            session_id: ID de sesión
        
        Returns:
            Lista de mensajes
        """
        session = self.sessions.get(session_id)
        if session:
            return session.messages
        return []
    
    def clear_session(self, session_id: str):
        """
        Limpia una sesión específica
        
        Args:
            session_id: ID de sesión a limpiar
        """
        if session_id in self.sessions:
            del self.sessions[session_id]
            logger.info(f"🗑️ Sesión {session_id} eliminada")
    
    def get_active_sessions_count(self) -> int:
        """Retorna el número de sesiones activas"""
        return len(self.sessions)


# Instancia global del servicio
agent_service = AgentService()
