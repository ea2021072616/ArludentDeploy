"""
Herramientas de LangChain para el agente
Cada herramienta permite al agente interactuar con el backend
"""
from langchain.tools import BaseTool
from typing import Optional, Type
from pydantic import BaseModel, Field
from app.utils.http_client import backend_client
from app.core import logger


# ========================================
# Schemas de Entrada para Tools
# ========================================

class BuscarPacienteInput(BaseModel):
    """Input para buscar paciente"""
    dni: Optional[str] = Field(None, description="DNI del paciente a buscar")
    paciente_id: Optional[int] = Field(None, description="ID del paciente a buscar")
    nombre: Optional[str] = Field(None, description="Nombre del paciente a buscar")


class ConsultarCitasInput(BaseModel):
    """Input para consultar citas"""
    paciente_id: Optional[int] = Field(None, description="ID del paciente")
    medico_id: Optional[int] = Field(None, description="ID del médico")
    estado: Optional[str] = Field(None, description="Estado de la cita (pendiente, confirmada, etc.)")


class ConsultarHistorialInput(BaseModel):
    """Input para consultar historial clínico"""
    paciente_id: int = Field(..., description="ID del paciente")


class ConsultarDisponibilidadInput(BaseModel):
    """Input para consultar disponibilidad"""
    medico_id: int = Field(..., description="ID del médico")
    fecha: str = Field(..., description="Fecha en formato YYYY-MM-DD")


class ListarMedicosInput(BaseModel):
    """Input para listar médicos"""
    especialidad: Optional[str] = Field(None, description="Especialidad a filtrar")


# ========================================
# INPUTS: Gestión Simplificada con SOLO DNI
# ========================================

class BuscarCitasPorDniInput(BaseModel):
    """Input para buscar citas usando solo DNI"""
    dni: str = Field(..., description="DNI del paciente (8 dígitos)")


class CancelarCitaConDniInput(BaseModel):
    """Input para cancelar cita usando solo DNI"""
    dni: str = Field(..., description="DNI del paciente (8 dígitos)")
    id_cita: int = Field(..., description="ID de la cita a cancelar")
    motivo_cancelacion: Optional[str] = Field(None, description="Motivo de la cancelación")


# ========================================
# INPUTS: Gestión con DNI + Nombre (Original)
# ========================================

class CancelarCitaInput(BaseModel):
    """Input para cancelar cita con verificación DNI + nombre"""
    dni: str = Field(..., description="DNI del paciente (8 dígitos)")
    nombre_parcial: str = Field(..., description="Al menos 2 caracteres del nombre del paciente")
    id_cita: int = Field(..., description="ID de la cita a cancelar")
    motivo_cancelacion: Optional[str] = Field(None, description="Motivo de la cancelación")


class ReprogramarCitaInput(BaseModel):
    """Input para reprogramar cita con verificación DNI"""
    dni: str = Field(..., description="DNI del paciente (8 dígitos)")
    nombre_parcial: str = Field(..., description="Al menos 2 caracteres del nombre del paciente")
    id_cita: int = Field(..., description="ID de la cita a reprogramar")
    nueva_fecha: str = Field(..., description="Nueva fecha en formato YYYY-MM-DD")
    nueva_hora_inicio: str = Field(..., description="Nueva hora de inicio en formato HH:MM")
    nueva_hora_fin: str = Field(..., description="Nueva hora de fin en formato HH:MM")
    motivo_reprogramacion: Optional[str] = Field(None, description="Motivo de la reprogramación")


class CambiarMedicoCitaInput(BaseModel):
    """Input para cambiar médico de cita con verificación DNI"""
    dni: str = Field(..., description="DNI del paciente (8 dígitos)")
    nombre_parcial: str = Field(..., description="Al menos 2 caracteres del nombre del paciente")
    id_cita: int = Field(..., description="ID de la cita")
    id_nuevo_medico: int = Field(..., description="ID del nuevo médico")
    motivo_cambio: Optional[str] = Field(None, description="Motivo del cambio")


class ConsultarOdontogramaInput(BaseModel):
    """Input para consultar odontograma"""
    paciente_id: int = Field(..., description="ID del paciente")


class ConsultarPagosInput(BaseModel):
    """Input para consultar historial de pagos"""
    paciente_id: int = Field(..., description="ID del paciente")
    tipo: Optional[str] = Field("todos", description="Tipo de consulta: 'ultimo', 'año_actual' o 'todos'")
    limite: Optional[int] = Field(10, description="Cantidad máxima de pagos a retornar")


class ConsultarTratamientosInput(BaseModel):
    """Input para consultar estado de tratamientos"""
    paciente_id: int = Field(..., description="ID del paciente")


# ========================================
# Herramientas (Tools)
# ========================================

class BuscarPacienteTool(BaseTool):
    """
    Herramienta para buscar información de pacientes
    """
    name: str = "buscar_paciente"
    description: str = """
    Busca información de un paciente en el sistema.
    Puedes buscar por DNI, ID de paciente, o nombre.
    Retorna datos básicos del paciente como nombre, edad, alergias, etc.
    Usa esta herramienta cuando el usuario pregunte por un paciente específico.
    """
    args_schema: Type[BaseModel] = BuscarPacienteInput
    
    def _run(
        self,
        dni: Optional[str] = None,
        paciente_id: Optional[int] = None,
        nombre: Optional[str] = None
    ) -> str:
        """Ejecuta la búsqueda de paciente (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(dni, paciente_id, nombre))
    
    async def _arun(
        self,
        dni: Optional[str] = None,
        paciente_id: Optional[int] = None,
        nombre: Optional[str] = None
    ) -> str:
        """Ejecuta la búsqueda de paciente (asíncrono)"""
        try:
            logger.info(f"🔍 Buscando paciente - DNI: {dni}, ID: {paciente_id}, Nombre: {nombre}")
            
            if paciente_id:
                result = await backend_client.get_paciente(paciente_id)
            elif dni:
                result = await backend_client.buscar_paciente_por_dni(dni)
            elif nombre:
                result = await backend_client.get_pacientes(limit=5, search=nombre)
            else:
                return "❌ Debes proporcionar al menos un criterio de búsqueda (DNI, ID o nombre)"
            
            if result.get("success") and result.get("data"):
                paciente = result["data"]
                
                # Si es una lista, tomar el primero
                if isinstance(paciente, list):
                    if len(paciente) == 0:
                        return "❌ No se encontró ningún paciente con ese criterio"
                    paciente = paciente[0]
                
                # Formatear respuesta
                info = f"""
✅ Paciente encontrado:
- Nombre: {paciente.get('nombres', '')} {paciente.get('apellidos', '')}
- DNI: {paciente.get('dni', 'No registrado')}
- Edad: {paciente.get('edad', 'No disponible')} años
- Teléfono: {paciente.get('telefono', 'No registrado')}
- Alergias: {paciente.get('alergias', 'Ninguna registrada')}
- Grupo sanguíneo: {paciente.get('grupo_sanguineo', 'No registrado')}
                """
                return info.strip()
            else:
                return "❌ No se encontró el paciente solicitado"
                
        except Exception as e:
            logger.error(f"Error en buscar_paciente: {str(e)}")
            return f"❌ Error al buscar paciente: {str(e)}"


class ConsultarCitasTool(BaseTool):
    """
    Herramienta para consultar citas médicas
    """
    name: str = "consultar_citas"
    description: str = """
    Consulta las citas médicas programadas.
    Puedes filtrar por paciente, médico y estado de la cita.
    Útil cuando el usuario pregunta por sus citas o las citas de un paciente.
    """
    args_schema: Type[BaseModel] = ConsultarCitasInput
    
    def _run(
        self,
        paciente_id: Optional[int] = None,
        medico_id: Optional[int] = None,
        estado: Optional[str] = None
    ) -> str:
        """Ejecuta la consulta de citas (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(paciente_id, medico_id, estado))
    
    async def _arun(
        self,
        paciente_id: Optional[int] = None,
        medico_id: Optional[int] = None,
        estado: Optional[str] = None
    ) -> str:
        """Ejecuta la consulta de citas (asíncrono)"""
        try:
            logger.info(f"📅 Consultando citas - Paciente: {paciente_id}, Médico: {medico_id}, Estado: {estado}")
            
            if paciente_id:
                # Validar que el paciente_id no sea None o 0
                if not paciente_id or paciente_id == 0:
                    return """ℹ️ Aún no tienes un perfil de paciente completo en el sistema.

Para consultar tus citas necesitas:
1. Haber agendado al menos una cita en la clínica
2. O haber sido registrado como paciente por nuestro personal

¿Te gustaría agendar tu primera cita? Puedo ayudarte con eso. 😊"""
                
                result = await backend_client.get_citas_paciente(paciente_id, estado)
            elif medico_id:
                result = await backend_client.get_citas_medico(medico_id)
            else:
                return "❌ Debes proporcionar al menos un ID de paciente o médico"
            
            if result.get("success") and result.get("data"):
                citas = result["data"]
                
                if not citas or len(citas) == 0:
                    return "ℹ️ No hay citas registradas con esos criterios"
                
                # Formatear respuesta
                info = f"✅ Se encontraron {len(citas)} citas:\n\n"
                for i, cita in enumerate(citas[:5], 1):  # Máximo 5 citas
                    info += f"""
{i}. Cita #{cita.get('id_cita')}
   - Fecha: {cita.get('fecha_hora_inicio', 'No disponible')}
   - Médico: Dr(a). {cita.get('medico', {}).get('nombres', '')} {cita.get('medico', {}).get('apellidos', '')}
   - Motivo: {cita.get('motivo', 'No especificado')}
   - Estado: {cita.get('estado', 'pendiente').upper()}
                    """
                
                if len(citas) > 5:
                    info += f"\n... y {len(citas) - 5} citas más"
                
                return info.strip()
            else:
                return "ℹ️ No se encontraron citas"
                
        except Exception as e:
            logger.error(f"Error en consultar_citas: {str(e)}")
            return f"❌ Error al consultar citas: {str(e)}"


class ConsultarHistorialTool(BaseTool):
    """
    Herramienta para consultar el historial clínico
    """
    name: str = "consultar_historial_clinico"
    description: str = """
    Consulta el historial clínico completo de un paciente.
    Incluye diagnósticos, tratamientos realizados, y observaciones médicas.
    Usa esta herramienta cuando necesites información médica histórica del paciente.
    """
    args_schema: Type[BaseModel] = ConsultarHistorialInput
    
    def _run(self, paciente_id: int) -> str:
        """Ejecuta la consulta de historial (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(paciente_id))
    
    async def _arun(self, paciente_id: int) -> str:
        """Ejecuta la consulta de historial (asíncrono)"""
        try:
            logger.info(f"📋 Consultando historial del paciente {paciente_id}")
            
            result = await backend_client.get_historial_resumen(paciente_id)
            
            if result.get("success") and result.get("data"):
                historial = result["data"]
                
                info = f"""
✅ Resumen del Historial Clínico:
- Total de consultas: {historial.get('total_consultas', 0)}
- Última consulta: {historial.get('ultima_consulta', 'No disponible')}
- Tratamientos activos: {historial.get('tratamientos_activos', 0)}
- Alergias conocidas: {historial.get('alergias', 'Ninguna')}

Diagnósticos recientes:
{historial.get('diagnosticos_recientes', 'No hay diagnósticos recientes')}

Notas importantes:
{historial.get('notas_importantes', 'Sin notas especiales')}
                """
                return info.strip()
            else:
                return "ℹ️ No hay historial clínico registrado para este paciente"
                
        except Exception as e:
            logger.error(f"Error en consultar_historial: {str(e)}")
            return f"❌ Error al consultar historial: {str(e)}"


class ConsultarDisponibilidadTool(BaseTool):
    """
    Herramienta para consultar disponibilidad de médicos
    """
    name: str = "consultar_disponibilidad_medico"
    description: str = """
    Consulta la disponibilidad de un médico en una fecha específica.
    Muestra los horarios disponibles para agendar citas.
    Útil cuando el usuario quiere agendar una cita.
    """
    args_schema: Type[BaseModel] = ConsultarDisponibilidadInput
    
    def _run(self, medico_id: int, fecha: str) -> str:
        """Ejecuta la consulta de disponibilidad (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(medico_id, fecha))
    
    async def _arun(self, medico_id: int, fecha: str) -> str:
        """Ejecuta la consulta de disponibilidad (asíncrono)"""
        try:
            logger.info(f"🗓️ Consultando disponibilidad del médico {medico_id} para {fecha}")
            
            result = await backend_client.get_disponibilidad_medico(medico_id, fecha)
            
            if result.get("success") and result.get("data"):
                disponibilidad = result["data"]
                
                horarios = disponibilidad.get("horarios_disponibles", [])
                if not horarios:
                    return f"ℹ️ No hay horarios disponibles para el {fecha}"
                
                info = f"✅ Horarios disponibles para el {fecha}:\n\n"
                for horario in horarios:
                    info += f"- {horario}\n"
                
                return info.strip()
            else:
                return f"ℹ️ No hay disponibilidad para el {fecha}"
                
        except Exception as e:
            logger.error(f"Error en consultar_disponibilidad: {str(e)}")
            return f"❌ Error al consultar disponibilidad: {str(e)}"


class ListarMedicosTool(BaseTool):
    """
    Herramienta para listar médicos disponibles
    """
    name: str = "listar_medicos"
    description: str = """
    Lista todos los médicos disponibles en el consultorio.
    Puedes filtrar por especialidad si es necesario.
    Útil cuando el usuario pregunta qué médicos hay disponibles.
    """
    args_schema: Type[BaseModel] = ListarMedicosInput
    
    def _run(self, especialidad: Optional[str] = None) -> str:
        """Ejecuta el listado de médicos (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(especialidad))
    
    async def _arun(self, especialidad: Optional[str] = None) -> str:
        """Ejecuta el listado de médicos (asíncrono)"""
        try:
            logger.info(f"👨‍⚕️ Listando médicos - Especialidad: {especialidad}")
            
            result = await backend_client.get_medicos(especialidad)
            
            if result.get("success") and result.get("data"):
                medicos = result["data"]
                
                if not medicos or len(medicos) == 0:
                    return "ℹ️ No hay médicos registrados"
                
                info = f"✅ Médicos disponibles ({len(medicos)}):\n\n"
                for i, medico in enumerate(medicos, 1):
                    info += f"""
{i}. Dr(a). {medico.get('nombres', '')} {medico.get('apellidos', '')}
   - Especialidad: {medico.get('especialidad', 'General')}
   - Colegiatura: {medico.get('colegiatura', 'No disponible')}
                    """
                
                return info.strip()
            else:
                return "ℹ️ No se encontraron médicos"
                
        except Exception as e:
            logger.error(f"Error en listar_medicos: {str(e)}")
            return f"❌ Error al listar médicos: {str(e)}"


# ========================================
# HERRAMIENTAS DE AGENDAMIENTO DE CITAS
# ========================================

class DeterminarTipoUsuarioInput(BaseModel):
    """Input para determinar tipo de usuario"""
    id_usuario: int = Field(..., description="ID del usuario a verificar")


class DeterminarTipoUsuarioTool(BaseTool):
    """
    Determina si un usuario es paciente activo o usuario externo (primera vez)
    """
    name: str = "determinar_tipo_usuario"
    description: str = """
    Determina si el usuario es paciente activo con historial o usuario externo (primera vez).
    USAR AL INICIO del flujo de agendamiento para decidir:
    - Paciente activo: asignar último médico o especialista según motivo
    - Usuario externo: asignar médico de cabecera (primera cita)
    
    Retorna si es paciente activo, su último médico (si existe) y datos relevantes.
    """
    args_schema: Type[BaseModel] = DeterminarTipoUsuarioInput
    
    def _run(self, id_usuario: int) -> str:
        """Ejecuta de forma síncrona"""
        import asyncio
        return asyncio.run(self._arun(id_usuario))
    
    async def _arun(self, id_usuario: int) -> str:
        """Ejecuta de forma asíncrona"""
        try:
            logger.info(f"🔍 Determinando tipo de usuario: {id_usuario}")
            result = await backend_client.determinar_tipo_usuario(id_usuario)
            
            if result.get("success") and result.get("data"):
                data = result["data"]
                
                if data["es_paciente_activo"]:
                    id_paciente = data.get("id_paciente")
                    msg = f"✅ Usuario es PACIENTE ACTIVO: {data['nombre_completo']}\n"
                    msg += f"📋 ID_PACIENTE: {id_paciente}\n"
                    msg += f"⚠️ USA ESTE id_paciente={id_paciente} para consultas de citas, odontograma, pagos y tratamientos\n"
                    if data.get("ultimo_medico"):
                        medico = data["ultimo_medico"]
                        msg += f"👨‍⚕️ Último médico: Dr. {medico['nombres']} {medico['apellidos']} ({medico['especialidad']})"
                    return msg
                else:
                    return f"🆕 Usuario es EXTERNO (primera vez): {data['nombre_completo']}\n💡 Debe asignarse médico de cabecera\n❌ NO tiene id_paciente - no puede consultar citas/historial"
            else:
                return f"❌ {result.get('message', 'Error al determinar tipo de usuario')}"
                
        except Exception as e:
            logger.error(f"Error en determinar_tipo_usuario: {str(e)}")
            return f"❌ Error: {str(e)}"


class SugerirHorariosInput(BaseModel):
    """Input para sugerir horarios"""
    id_medico: int = Field(..., description="ID del médico")
    fecha_inicio: str = Field(..., description="Fecha de inicio en formato YYYY-MM-DD")
    fecha_fin: Optional[str] = Field(None, description="Fecha fin (opcional, por defecto +7 días)")
    duracion_minutos: Optional[int] = Field(60, description="Duración de la cita en minutos")
    limite: Optional[int] = Field(3, description="Cantidad de horarios a sugerir")


class SugerirHorariosTool(BaseTool):
    """
    Sugiere horarios disponibles cuando el solicitado no está libre
    """
    name: str = "sugerir_horarios_alternativos"
    description: str = """
    Sugiere horarios ALTERNATIVOS disponibles cuando el horario solicitado NO está libre.
    Busca los próximos horarios disponibles del médico en un rango de fechas.
    
    USAR cuando:
    - El horario solicitado por el usuario está ocupado
    - El usuario pregunta "¿qué horarios hay disponibles?"
    
    Retorna lista de horarios con fecha, hora y día de la semana.
    """
    args_schema: Type[BaseModel] = SugerirHorariosInput
    
    def _run(self, **kwargs) -> str:
        """Ejecuta de forma síncrona"""
        import asyncio
        return asyncio.run(self._arun(**kwargs))
    
    async def _arun(
        self,
        id_medico: int,
        fecha_inicio: str,
        fecha_fin: Optional[str] = None,
        duracion_minutos: Optional[int] = 60,
        limite: Optional[int] = 3
    ) -> str:
        """Ejecuta de forma asíncrona"""
        try:
            logger.info(f"📅 Sugiriendo horarios - Médico: {id_medico}, Fecha inicio: {fecha_inicio}")
            result = await backend_client.sugerir_horarios(
                id_medico=id_medico,
                fecha_inicio=fecha_inicio,
                fecha_fin=fecha_fin,
                duracion_minutos=duracion_minutos,
                limite=limite
            )
            
            if result.get("success") and result.get("data"):
                horarios = result["data"]
                
                if len(horarios) == 0:
                    return "❌ No hay horarios disponibles en el rango de fechas especificado"
                
                msg = f"📅 Horarios disponibles encontrados ({len(horarios)}):\n\n"
                for i, h in enumerate(horarios, 1):
                    msg += f"{i}. {h['dia_semana']} {h['fecha']} a las {h['hora']}\n"
                
                msg += "\n💡 El usuario puede elegir uno de estos horarios"
                return msg
            else:
                return f"❌ {result.get('message', 'Error al sugerir horarios')}"
                
        except Exception as e:
            logger.error(f"Error en sugerir_horarios: {str(e)}")
            return f"❌ Error: {str(e)}"


class RegistrarCitaInput(BaseModel):
    """Input para registrar cita"""
    id_usuario: int = Field(..., description="ID del usuario que agenda")
    id_medico: int = Field(..., description="ID del médico")
    fecha_hora_inicio: str = Field(..., description="Fecha y hora inicio YYYY-MM-DD HH:MM:SS")
    fecha_hora_fin: str = Field(..., description="Fecha y hora fin YYYY-MM-DD HH:MM:SS")
    motivo: Optional[str] = Field(None, description="Motivo de la consulta")
    tipo_cita: Optional[str] = Field(None, description="Tipo: primera_vez, seguimiento, especialidad")
    notas: Optional[str] = Field(None, description="Notas adicionales")


class RegistrarCitaTool(BaseTool):
    """
    Registra una nueva cita médica en el sistema
    """
    name: str = "registrar_cita"
    description: str = """
    Registra una NUEVA CITA médica con estado 'pendiente'.
    
    ⚠️ IMPORTANTE: 
    - Usar SOLO DESPUÉS de verificar disponibilidad del médico
    - La cita queda en estado PENDIENTE (no confirmada)
    
    Parámetros necesarios:
    - id_usuario: ID del usuario que agenda
    - id_medico: ID del médico asignado
    - fecha_hora_inicio: Inicio en formato "YYYY-MM-DD HH:MM:SS"
    - fecha_hora_fin: Fin en formato "YYYY-MM-DD HH:MM:SS"
    - motivo: Motivo de la consulta (opcional)
    
    Retorna confirmación con ID de cita generado.
    """
    args_schema: Type[BaseModel] = RegistrarCitaInput
    
    def _run(self, **kwargs) -> str:
        """Ejecuta de forma síncrona"""
        import asyncio
        return asyncio.run(self._arun(**kwargs))
    
    async def _arun(
        self,
        id_usuario: int,
        id_medico: int,
        fecha_hora_inicio: str,
        fecha_hora_fin: str,
        motivo: Optional[str] = None,
        tipo_cita: Optional[str] = None,
        notas: Optional[str] = None
    ) -> str:
        """Ejecuta de forma asíncrona"""
        try:
            logger.info(f"📝 Registrando cita - Usuario: {id_usuario}, Médico: {id_medico}, Fecha: {fecha_hora_inicio}")
            result = await backend_client.registrar_cita(
                id_usuario=id_usuario,
                id_medico=id_medico,
                fecha_hora_inicio=fecha_hora_inicio,
                fecha_hora_fin=fecha_hora_fin,
                motivo=motivo,
                tipo_cita=tipo_cita,
                notas=notas
            )
            
            if result.get("success") and result.get("data"):
                data = result["data"]
                return f"""✅ Cita registrada exitosamente:

📋 ID Cita: {data['id_cita']}
📅 Fecha/Hora: {data['fecha_hora_inicio']}
⏳ Estado: {data['estado'].upper()} (pendiente de confirmación)
📝 Motivo: {data['motivo']}

💡 La cita está en estado PENDIENTE. El usuario debe confirmarla más adelante."""
            else:
                return f"❌ {result.get('message', 'Error al registrar cita')}"
                
        except Exception as e:
            logger.error(f"Error en registrar_cita: {str(e)}")
            return f"❌ Error: {str(e)}"


class ConfirmarCitaInput(BaseModel):
    """Input para confirmar cita"""
    id_cita: int = Field(..., description="ID de la cita a confirmar")


class ConfirmarCitaTool(BaseTool):
    """
    Confirma una cita existente (cambia estado a confirmada)
    """
    name: str = "confirmar_cita"
    description: str = """
    Confirma una cita que está en estado 'pendiente', cambiándola a 'confirmada'.
    
    USAR cuando:
    - El usuario dice explícitamente "confirmo mi cita"
    - El usuario pregunta "¿cómo confirmo mi cita?"
    
    ⚠️ Solo se pueden confirmar citas en estado PENDIENTE.
    
    Retorna confirmación del cambio de estado exitoso.
    """
    args_schema: Type[BaseModel] = ConfirmarCitaInput
    
    def _run(self, id_cita: int) -> str:
        """Ejecuta de forma síncrona"""
        import asyncio
        return asyncio.run(self._arun(id_cita))
    
    async def _arun(self, id_cita: int) -> str:
        """Ejecuta de forma asíncrona"""
        try:
            logger.info(f"✅ Confirmando cita: {id_cita}")
            result = await backend_client.confirmar_cita(id_cita)
            
            if result.get("success") and result.get("data"):
                data = result["data"]
                return f"""✅ Cita confirmada exitosamente:

📋 ID Cita: {data['id_cita']}
✅ Estado: {data['estado'].upper()}
📅 Fecha/Hora: {data['fecha_hora_inicio']}

🔔 Recibirás un recordatorio antes de tu cita."""
            else:
                return f"❌ {result.get('message', 'Error al confirmar cita')}"
                
        except Exception as e:
            logger.error(f"Error en confirmar_cita: {str(e)}")
            return f"❌ Error: {str(e)}"


class RegistrarInteraccionInput(BaseModel):
    """Input para registrar interacción IA"""
    id_usuario: int = Field(..., description="ID del usuario")
    tipo_intencion: Optional[str] = Field(None, description="Tipo de intención detectada")
    entrada_usuario: Optional[str] = Field(None, description="Mensaje del usuario")
    respuesta_ia: Optional[str] = Field(None, description="Respuesta del agente")
    estado_resultado: Optional[str] = Field(None, description="exitosa, fallida, requiere_revision")
    contexto: Optional[dict] = Field(None, description="Contexto adicional JSON")


class RegistrarInteraccionTool(BaseTool):
    """
    Registra interacciones para trazabilidad y análisis
    """
    name: str = "registrar_interaccion_ia"
    description: str = """
    Registra la interacción del usuario con la IA para trazabilidad.
    
    USAR para:
    - Guardar registro de intenciones importantes (agendar_cita, cancelar_cita, etc.)
    - Análisis posterior de conversaciones
    - Auditoría del sistema
    
    Es opcional, usar solo en interacciones clave.
    """
    args_schema: Type[BaseModel] = RegistrarInteraccionInput
    
    def _run(self, **kwargs) -> str:
        """Ejecuta de forma síncrona"""
        import asyncio
        return asyncio.run(self._arun(**kwargs))
    
    async def _arun(
        self,
        id_usuario: int,
        tipo_intencion: Optional[str] = None,
        entrada_usuario: Optional[str] = None,
        respuesta_ia: Optional[str] = None,
        estado_resultado: Optional[str] = None,
        contexto: Optional[dict] = None
    ) -> str:
        """Ejecuta de forma asíncrona"""
        try:
            logger.info(f"📊 Registrando interacción - Usuario: {id_usuario}, Intención: {tipo_intencion}")
            result = await backend_client.registrar_interaccion(
                id_usuario=id_usuario,
                tipo_intencion=tipo_intencion,
                entrada_usuario=entrada_usuario,
                respuesta_ia=respuesta_ia,
                estado_resultado=estado_resultado,
                contexto=contexto
            )
            
            if result.get("success"):
                return f"✅ Interacción registrada (ID: {result['data']['id_interaccion']})"
            else:
                return f"⚠️ {result.get('message', 'Error al registrar interacción')}"
                
        except Exception as e:
            logger.error(f"Error en registrar_interaccion: {str(e)}")
            return f"⚠️ Error: {str(e)}"


class ValidarMedicoInput(BaseModel):
    """Input para validar médico"""
    id_medico: int = Field(..., description="ID del médico a validar")


class ValidarMedicoTool(BaseTool):
    """
    Valida que un médico existe y está disponible
    """
    name: str = "validar_medico"
    description: str = """
    Valida que un médico existe y está disponible en el sistema.
    
    USAR cuando:
    - Necesites verificar que un ID de médico es válido antes de usarlo
    - El último médico del paciente podría no estar disponible
    - Antes de registrar una cita para confirmar que el médico existe
    
    Retorna información del médico si es válido, o error si no existe.
    """
    args_schema: Type[BaseModel] = ValidarMedicoInput
    
    def _run(self, id_medico: int) -> str:
        """Ejecuta de forma síncrona"""
        import asyncio
        return asyncio.run(self._arun(id_medico))
    
    async def _arun(self, id_medico: int) -> str:
        """Ejecuta de forma asíncrona"""
        try:
            logger.info(f"🔍 Validando médico: {id_medico}")
            result = await backend_client.get_medico(id_medico)
            
            if result.get("success") and result.get("data"):
                medico = result["data"]
                return f"""✅ Médico válido:
- ID: {medico.get('id_medico')}
- Nombre: Dr(a). {medico.get('nombres', '')} {medico.get('apellidos', '')}
- Especialidad: {medico.get('especialidad', 'General')}
- Colegiatura: {medico.get('colegiatura', 'No disponible')}

Este médico puede ser usado para agendar citas."""
            else:
                return f"❌ Médico con ID {id_medico} no existe o no está disponible. Usa listar_medicos para ver médicos válidos."
                
        except Exception as e:
            logger.error(f"Error en validar_medico: {str(e)}")
            return f"❌ Error al validar médico: {str(e)}"


# ========================================
# NUEVAS HERRAMIENTAS: Gestión de Citas con Verificación DNI
# ========================================

class CancelarCitaTool(BaseTool):
    """
    Herramienta para cancelar una cita con verificación de identidad del paciente
    """
    name: str = "cancelar_cita"
    description: str = """
    Cancela una cita médica con verificación de identidad del paciente.
    IMPORTANTE: Debes solicitar al paciente su DNI y al menos 2 caracteres de su nombre para verificar su identidad.
    Solo se pueden cancelar citas en estado: pendiente, confirmado o en_espera.
    Usa esta herramienta cuando el paciente solicite cancelar su cita.
    
    Ejemplo de uso:
    - Usuario: "Quiero cancelar mi cita"
    - Agente: "Para cancelar tu cita necesito verificar tu identidad. ¿Podrías proporcionarme tu DNI?"
    - Usuario: "12345678"
    - Agente: "Gracias. Ahora necesito que me digas al menos las dos primeras letras de tu nombre."
    - Usuario: "Ma"
    - Agente: [usa esta tool con dni=12345678, nombre_parcial=Ma, id_cita=X]
    """
    args_schema: Type[BaseModel] = CancelarCitaInput
    
    def _run(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        motivo_cancelacion: Optional[str] = None
    ) -> str:
        """Ejecuta la cancelación (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(dni, nombre_parcial, id_cita, motivo_cancelacion))
    
    async def _arun(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        motivo_cancelacion: Optional[str] = None
    ) -> str:
        """Ejecuta la cancelación (asíncrono)"""
        try:
            logger.info(f"❌ Cancelando cita {id_cita} - DNI: {dni}, Nombre: {nombre_parcial}")
            
            result = await backend_client.cancelar_cita_verificada(
                dni=dni,
                nombre_parcial=nombre_parcial,
                id_cita=id_cita,
                motivo_cancelacion=motivo_cancelacion
            )
            
            if result.get("success"):
                cita = result["data"]["cita"]
                return f"""✅ Cita cancelada exitosamente:

📅 Información de la cita cancelada:
- Fecha y hora: {cita['fecha_hora']}
- Médico: {cita['medico']['nombre']} ({cita['medico']['especialidad']})
- Motivo original: {cita['motivo']}
- Motivo de cancelación: {result['data'].get('motivo_cancelacion', 'No especificado')}

La cita ha sido cancelada correctamente. Si deseas agendar una nueva cita, házmelo saber."""
            else:
                mensaje_error = result.get("message", "Error desconocido")
                return f"❌ No se pudo cancelar la cita: {mensaje_error}"
                
        except Exception as e:
            logger.error(f"Error en cancelar_cita: {str(e)}")
            return f"❌ Error al intentar cancelar la cita: {str(e)}"


class ReprogramarCitaTool(BaseTool):
    """
    Herramienta para reprogramar una cita con verificación de identidad del paciente
    """
    name: str = "reprogramar_cita"
    description: str = """
    Reprograma una cita médica a una nueva fecha y hora, con verificación de identidad del paciente.
    IMPORTANTE: Debes solicitar DNI y nombre parcial del paciente, además de la nueva fecha y hora deseada.
    La fecha debe ser futura y el médico debe estar disponible en el nuevo horario.
    Usa esta herramienta cuando el paciente solicite cambiar la fecha/hora de su cita.
    
    Pasos recomendados:
    1. Solicitar DNI y nombre parcial para verificación
    2. Preguntar la nueva fecha deseada (formato: YYYY-MM-DD)
    3. Preguntar la nueva hora de inicio (formato: HH:MM)
    4. Preguntar la nueva hora de fin (formato: HH:MM)
    5. Ejecutar la reprogramación
    """
    args_schema: Type[BaseModel] = ReprogramarCitaInput
    
    def _run(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        nueva_fecha: str,
        nueva_hora_inicio: str,
        nueva_hora_fin: str,
        motivo_reprogramacion: Optional[str] = None
    ) -> str:
        """Ejecuta la reprogramación (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(
            dni, nombre_parcial, id_cita,
            nueva_fecha, nueva_hora_inicio, nueva_hora_fin,
            motivo_reprogramacion
        ))
    
    async def _arun(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        nueva_fecha: str,
        nueva_hora_inicio: str,
        nueva_hora_fin: str,
        motivo_reprogramacion: Optional[str] = None
    ) -> str:
        """Ejecuta la reprogramación (asíncrono)"""
        try:
            logger.info(f"📅 Reprogramando cita {id_cita} - Nueva fecha: {nueva_fecha} {nueva_hora_inicio}")
            
            result = await backend_client.reprogramar_cita_verificada(
                dni=dni,
                nombre_parcial=nombre_parcial,
                id_cita=id_cita,
                nueva_fecha=nueva_fecha,
                nueva_hora_inicio=nueva_hora_inicio,
                nueva_hora_fin=nueva_hora_fin,
                motivo_reprogramacion=motivo_reprogramacion
            )
            
            if result.get("success"):
                cita = result["data"]["cita"]
                nuevo_horario = result["data"]["nuevo_horario"]
                
                return f"""✅ Cita reprogramada exitosamente:

📅 Nueva información de la cita:
- Fecha: {nuevo_horario['fecha']}
- Hora: {nuevo_horario['hora_inicio']} - {nuevo_horario['hora_fin']}
- Médico: {cita['medico']['nombre']} ({cita['medico']['especialidad']})
- Motivo: {cita['motivo']}

Estado: {cita['estado']} (el médico debe confirmarla nuevamente)

La cita ha sido reprogramada correctamente. Recibirás una confirmación cuando el médico apruebe el nuevo horario."""
            else:
                mensaje_error = result.get("message", "Error desconocido")
                return f"❌ No se pudo reprogramar la cita: {mensaje_error}"
                
        except Exception as e:
            logger.error(f"Error en reprogramar_cita: {str(e)}")
            return f"❌ Error al intentar reprogramar la cita: {str(e)}"


class CambiarMedicoCitaTool(BaseTool):
    """
    Herramienta para cambiar el médico asignado a una cita
    """
    name: str = "cambiar_medico_cita"
    description: str = """
    Cambia el médico asignado a una cita médica, con verificación de identidad del paciente.
    IMPORTANTE: Debes verificar la identidad del paciente (DNI + nombre) y asegurarte de que el nuevo médico esté disponible.
    Primero usa listar_medicos para mostrar médicos disponibles, luego ejecuta el cambio.
    Usa esta herramienta cuando el paciente solicite cambiar de médico para su cita.
    
    Pasos recomendados:
    1. Solicitar DNI y nombre parcial para verificación
    2. Usar listar_medicos para mostrar opciones de médicos
    3. Paciente elige nuevo médico
    4. Ejecutar el cambio de médico
    """
    args_schema: Type[BaseModel] = CambiarMedicoCitaInput
    
    def _run(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        id_nuevo_medico: int,
        motivo_cambio: Optional[str] = None
    ) -> str:
        """Ejecuta el cambio de médico (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(
            dni, nombre_parcial, id_cita, id_nuevo_medico, motivo_cambio
        ))
    
    async def _arun(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        id_nuevo_medico: int,
        motivo_cambio: Optional[str] = None
    ) -> str:
        """Ejecuta el cambio de médico (asíncrono)"""
        try:
            logger.info(f"👨‍⚕️ Cambiando médico de cita {id_cita} - Nuevo médico ID: {id_nuevo_medico}")
            
            result = await backend_client.cambiar_medico_cita(
                dni=dni,
                nombre_parcial=nombre_parcial,
                id_cita=id_cita,
                id_nuevo_medico=id_nuevo_medico,
                motivo_cambio=motivo_cambio
            )
            
            if result.get("success"):
                cita = result["data"]["cita"]
                medico_anterior = result["data"]["medico_anterior"]
                medico_nuevo = result["data"]["medico_nuevo"]
                
                return f"""✅ Médico cambiado exitosamente:

📋 Información de la cita:
- Fecha y hora: {cita['fecha_hora']}
- Motivo: {cita['motivo']}

👨‍⚕️ Cambio realizado:
- Médico anterior: {medico_anterior['nombre']} ({medico_anterior['especialidad']})
- Nuevo médico: {medico_nuevo['nombre']} ({medico_nuevo['especialidad']})

Estado: {cita['estado']} (el nuevo médico debe confirmarla)

El cambio de médico ha sido registrado exitosamente."""
            else:
                mensaje_error = result.get("message", "Error desconocido")
                return f"❌ No se pudo cambiar el médico: {mensaje_error}"
                
        except Exception as e:
            logger.error(f"Error en cambiar_medico_cita: {str(e)}")
            return f"❌ Error al intentar cambiar el médico: {str(e)}"


# ========================================
# NUEVAS HERRAMIENTAS: Consultas de Información del Paciente
# ========================================

class ConsultarOdontogramaTool(BaseTool):
    """
    Herramienta para consultar el odontograma (diagrama dental) de un paciente
    """
    name: str = "consultar_odontograma"
    description: str = """
    Consulta el odontograma (estado dental) de un paciente.
    Retorna un resumen del estado de sus piezas dentales y un enlace para ver el diagrama visual completo.
    Usa esta herramienta cuando el paciente pregunte por su odontograma o el estado de sus dientes.
    
    Ejemplos de uso:
    - "Muéstrame mi odontograma"
    - "¿Cómo están mis dientes?"
    - "Quiero ver el estado de mi dentadura"
    """
    args_schema: Type[BaseModel] = ConsultarOdontogramaInput
    
    def _run(self, paciente_id: int) -> str:
        """Ejecuta la consulta (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(paciente_id))
    
    async def _arun(self, paciente_id: int) -> str:
        """Ejecuta la consulta (asíncrono)"""
        try:
            logger.info(f"🦷 Consultando odontograma del paciente {paciente_id}")
            
            # Validar que el paciente_id no sea None o 0
            if not paciente_id or paciente_id == 0:
                return """ℹ️ Aún no tienes un perfil de paciente completo en el sistema. 

Para consultar tu odontograma necesitas:
1. Haber tenido al menos una consulta en la clínica
2. Que el médico haya registrado tu historial clínico

¿Te gustaría agendar una primera cita? Puedo ayudarte con eso. 😊"""
            
            result = await backend_client.get_odontograma_paciente(paciente_id)
            
            if result.get("success"):
                data = result["data"]
                
                if not data.get("tiene_odontograma"):
                    return "ℹ️ Aún no tienes un odontograma registrado en el sistema. Tu médico lo creará durante tu próxima consulta."
                
                resumen = data["resumen"]
                
                # Construir respuesta amigable
                respuesta = f"""🦷 Estado de tu Odontograma:

📊 Resumen general:
- Total de piezas registradas: {resumen['total_piezas']}
- Piezas sanas: {resumen['sanas']} ✅
- Piezas con caries: {resumen['cariadas']} ⚠️
- Piezas restauradas: {resumen['restauradas']} 🔧
- Piezas ausentes: {resumen['ausentes']} ❌
- Prótesis: {resumen['con_protesis']} 🦷

"""
                
                # Agregar detalles de piezas problemáticas si existen
                if resumen['cariadas'] > 0:
                    piezas_cariadas = [p['pieza'] for p in data['piezas'] if p['estado'] == 'cariado']
                    respuesta += f"⚠️ Piezas con caries: {', '.join(piezas_cariadas)}\n"
                
                # Enlace para visualización completa
                respuesta += f"\n🔗 Para ver el diagrama visual completo de tu odontograma, visita:\n{data['url_visualizacion_completa']}"
                
                return respuesta
            else:
                return f"❌ {result.get('message', 'Error al consultar odontograma')}"
                
        except Exception as e:
            logger.error(f"Error en consultar_odontograma: {str(e)}")
            return f"❌ Error al consultar odontograma: {str(e)}"


class ConsultarPagosTool(BaseTool):
    """
    Herramienta para consultar el historial de pagos de un paciente
    """
    name: str = "consultar_pagos"
    description: str = """
    Consulta el historial de pagos de un paciente.
    Puedes filtrar por último pago, pagos del año actual o todos los pagos.
    Retorna información de pagos completados (no incluye deudas pendientes).
    
    Tipos de consulta disponibles:
    - 'ultimo': Solo el último pago realizado
    - 'año_actual': Todos los pagos del año en curso
    - 'todos': Historial completo (limitado por parámetro limite)
    
    Ejemplos de uso:
    - "¿Cuál fue mi último pago?"
    - "Muéstrame mi historial de pagos de este año"
    - "¿Cuánto he gastado en total en tratamientos?"
    """
    args_schema: Type[BaseModel] = ConsultarPagosInput
    
    def _run(
        self,
        paciente_id: int,
        tipo: str = "todos",
        limite: int = 10
    ) -> str:
        """Ejecuta la consulta (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(paciente_id, tipo, limite))
    
    async def _arun(
        self,
        paciente_id: int,
        tipo: str = "todos",
        limite: int = 10
    ) -> str:
        """Ejecuta la consulta (asíncrono)"""
        try:
            logger.info(f"💰 Consultando pagos del paciente {paciente_id} - Tipo: {tipo}")
            
            # Validar que el paciente_id no sea None o 0
            if not paciente_id or paciente_id == 0:
                return """ℹ️ Aún no tienes un perfil de paciente completo en el sistema.

Para consultar tu historial de pagos necesitas:
1. Haber realizado pagos por tratamientos en la clínica
2. Tener un perfil de paciente registrado

¿Te gustaría conocer nuestros servicios y precios? 😊"""
            
            result = await backend_client.get_historial_pagos(
                id_paciente=paciente_id,
                tipo=tipo,
                limite=limite
            )
            
            if result.get("success"):
                data = result["data"]
                
                if not data.get("tiene_pagos"):
                    return "ℹ️ No tienes pagos registrados en el sistema aún."
                
                resumen = data["resumen"]
                pagos = data["pagos"]
                
                # Construir respuesta según tipo de consulta
                if tipo == "ultimo" and len(pagos) > 0:
                    pago = pagos[0]
                    return f"""💰 Tu último pago:

📅 Fecha: {pago['fecha_pago']}
💵 Monto: S/. {pago['monto']:.2f}
📝 Concepto: {pago['concepto']}
💳 Método de pago: {pago['metodo_pago']}
🧾 Comprobante: {pago['comprobante'] if pago['comprobante'] else 'Sin comprobante'}"""
                
                # Respuesta completa para múltiples pagos
                respuesta = f"""💰 Historial de Pagos:

📊 Resumen:
- Total pagado: S/. {resumen['total_pagado']:.2f}
- Cantidad de pagos: {resumen['cantidad_pagos']}
- Tipo de consulta: {resumen['tipo_consulta']}

"""
                
                # Resumen por método de pago
                if resumen.get('por_metodo_pago'):
                    respuesta += "💳 Por método de pago:\n"
                    for metodo in resumen['por_metodo_pago']:
                        respuesta += f"  - {metodo['metodo']}: S/. {metodo['total']:.2f} ({metodo['cantidad']} pagos)\n"
                    respuesta += "\n"
                
                # Últimos pagos (máximo 5 para no saturar)
                respuesta += "📋 Últimos pagos:\n"
                for i, pago in enumerate(pagos[:5], 1):
                    respuesta += f"{i}. {pago['fecha_pago']} - S/. {pago['monto']:.2f} - {pago['concepto']}\n"
                
                if len(pagos) > 5:
                    respuesta += f"\n... y {len(pagos) - 5} pagos más"
                
                return respuesta
            else:
                return f"❌ {result.get('message', 'Error al consultar pagos')}"
                
        except Exception as e:
            logger.error(f"Error en consultar_pagos: {str(e)}")
            return f"❌ Error al consultar pagos: {str(e)}"


class ConsultarTratamientosTool(BaseTool):
    """
    Herramienta para consultar el estado de tratamientos de un paciente
    """
    name: str = "consultar_tratamientos"
    description: str = """
    Consulta el estado de los tratamientos dentales de un paciente.
    Muestra tratamientos activos (en curso o pendientes) y tratamientos completados recientemente.
    Usa esta herramienta cuando el paciente pregunte por sus tratamientos o procedimientos.
    
    Ejemplos de uso:
    - "¿Qué tratamientos tengo en curso?"
    - "Muéstrame el estado de mis tratamientos"
    - "¿Cuándo terminan mis procedimientos dentales?"
    """
    args_schema: Type[BaseModel] = ConsultarTratamientosInput
    
    def _run(self, paciente_id: int) -> str:
        """Ejecuta la consulta (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(paciente_id))
    
    async def _arun(self, paciente_id: int) -> str:
        """Ejecuta la consulta (asíncrono)"""
        try:
            logger.info(f"🏥 Consultando tratamientos del paciente {paciente_id}")
            
            # Validar que el paciente_id no sea None o 0
            if not paciente_id or paciente_id == 0:
                return """ℹ️ Aún no tienes un perfil de paciente completo en el sistema.

Para consultar tus tratamientos necesitas:
1. Haber iniciado algún tratamiento dental en la clínica
2. Que el médico haya registrado tu historial clínico

¿Te gustaría agendar una evaluación? Puedo ayudarte a coordinar una cita. 😊"""
            
            result = await backend_client.get_estado_tratamientos(paciente_id)
            
            if result.get("success"):
                data = result["data"]
                
                if not data.get("tiene_tratamientos"):
                    return "ℹ️ No tienes tratamientos registrados en el sistema aún."
                
                resumen = data["resumen"]
                activos = data["tratamientos_activos"]
                completados = data["tratamientos_completados_recientes"]
                
                respuesta = f"""🏥 Estado de tus Tratamientos:

📊 Resumen general:
- Total de tratamientos: {resumen['total_tratamientos']}
- Tratamientos activos: {resumen['activos']}
- Tratamientos completados: {resumen['completados']}
- Tratamientos pendientes: {resumen['pendientes']}
- Inversión total: S/. {resumen['monto_total_tratamientos']:.2f}

"""
                
                # Tratamientos activos
                if len(activos) > 0:
                    respuesta += "🔄 Tratamientos activos:\n\n"
                    for i, trat in enumerate(activos, 1):
                        respuesta += f"{i}. {trat['nombre_tratamiento']}\n"
                        respuesta += f"   Estado: {trat['estado']}\n"
                        respuesta += f"   Médico: {trat['medico_responsable']}\n"
                        respuesta += f"   Inicio: {trat['fecha_inicio']}\n"
                        if trat.get('fecha_fin_estimada'):
                            respuesta += f"   Fin estimado: {trat['fecha_fin_estimada']}\n"
                        respuesta += f"   Precio: S/. {trat['precio']:.2f}\n"
                        if trat.get('ultimo_seguimiento'):
                            respuesta += f"   Último seguimiento: {trat['ultimo_seguimiento']['fecha']}\n"
                        respuesta += "\n"
                
                # Tratamientos completados recientes
                if len(completados) > 0:
                    respuesta += "✅ Tratamientos completados recientemente:\n\n"
                    for i, trat in enumerate(completados, 1):
                        respuesta += f"{i}. {trat['nombre_tratamiento']} - S/. {trat['precio']:.2f}\n"
                        respuesta += f"   Periodo: {trat['fecha_inicio']} a {trat['fecha_fin']}\n"
                        respuesta += f"   Médico: {trat['medico_responsable']}\n\n"
                
                return respuesta.strip()
            else:
                return f"❌ {result.get('message', 'Error al consultar tratamientos')}"
                
        except Exception as e:
            logger.error(f"Error en consultar_tratamientos: {str(e)}")
            return f"❌ Error al consultar tratamientos: {str(e)}"


# ========================================
# NUEVAS HERRAMIENTAS: Gestión Simplificada con SOLO DNI
# ========================================

class BuscarCitasPorDniTool(BaseTool):
    """
    Busca citas de un paciente usando solo su DNI (sin pedir nombre)
    """
    name: str = "buscar_citas_por_dni"
    description: str = """
    Busca las citas programadas de un paciente usando SOLO su DNI.
    
    ⚠️ NUEVO FLUJO SIMPLIFICADO:
    - Usuario: "Quiero cancelar mi cita"
    - Agente: "¿Cuál es tu DNI?"
    - Usuario: "72345678"
    - Agente: [Usa esta herramienta] → Obtiene lista de citas
    - Agente: Muestra lista de citas al usuario
    - Usuario: Elige la cita que desea cancelar
    - Agente: Usa cancelar_cita_con_dni
    
    Esta herramienta retorna:
    - Lista de citas futuras del paciente
    - Solo citas en estado: pendiente, confirmado, en_espera
    - Cada cita con: ID, fecha, hora, médico, motivo
    
    USA ESTA HERRAMIENTA cuando el usuario proporcione su DNI.
    NO pidas nombre adicional, SOLO el DNI es suficiente.
    """
    args_schema: Type[BaseModel] = BuscarCitasPorDniInput
    
    def _run(self, dni: str) -> str:
        """Ejecuta la búsqueda (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(dni))
    
    async def _arun(self, dni: str) -> str:
        """Ejecuta la búsqueda (asíncrono)"""
        try:
            # Si no se proporciona DNI o es placeholder, intentar obtenerlo del contexto
            from app.utils.context import get_current_user_id, get_current_dni
            
            if not dni or dni == "12345678":
                dni_contexto = get_current_dni()
                if dni_contexto:
                    dni = dni_contexto
                    logger.info(f"✅ Usando DNI del contexto: {dni}")
                else:
                    return "❌ No se pudo obtener tu DNI. Por favor, proporciona tu DNI de 8 dígitos."
            
            logger.info(f"🔍 Buscando citas por DNI: {dni}")
            
            # Obtener el user_id del contexto
            user_id = get_current_user_id()
            
            result = await backend_client.buscar_citas_por_dni(dni, user_id=user_id)
            
            if result.get("success"):
                data = result["data"]
                paciente = data["paciente"]
                citas = data["citas"]
                total = data["total_citas"]
                
                # Crear respuesta con metadata para el frontend
                respuesta = f"✅ Hola {paciente['nombre_completo']}, encontré {total} cita(s) programada(s):\n\n"
                
                for i, cita in enumerate(citas, 1):
                    # Formatear fecha de manera más amigable
                    fecha_parts = cita['fecha_hora'].split()
                    fecha = fecha_parts[0] if len(fecha_parts) > 0 else cita['fecha_hora']
                    hora = fecha_parts[1] if len(fecha_parts) > 1 else ''
                    
                    respuesta += f"🔹 Opción {i} (ID: {cita['id_cita']})\n"
                    respuesta += f"   📅 Fecha: {fecha}\n"
                    respuesta += f"   🕐 Hora: {hora}\n"
                    respuesta += f"   👨‍⚕️ Doctor(a): {cita['medico']['nombre']}\n"
                    respuesta += f"   🏥 Especialidad: {cita['medico']['especialidad']}\n"
                    respuesta += f"   📋 Motivo: {cita['motivo']}\n"
                    respuesta += f"   ✓ Estado: {cita['estado'].upper()}\n\n"
                
                respuesta += "💡 Para cancelar una cita, escribe el número de la opción (1, 2, 3, etc.) o el ID de la cita"
                
                return respuesta
            else:
                return f"❌ {result.get('message', 'No se encontraron citas')}"
                
        except Exception as e:
            logger.error(f"Error en buscar_citas_por_dni: {str(e)}")
            return f"❌ Error: {str(e)}"


class CancelarCitaConDniTool(BaseTool):
    """
    Cancela una cita usando solo el DNI (sin verificar nombre)
    """
    name: str = "cancelar_cita_con_dni"
    description: str = """
    Cancela una cita usando SOLO el DNI del paciente (sin verificar nombre).
    
    ⚠️ FLUJO:
    1. Usuario proporciona DNI
    2. Usas buscar_citas_por_dni para mostrar lista
    3. Usuario elige la cita
    4. Usas ESTA herramienta para cancelar
    
    Parámetros:
    - dni: DNI del paciente (8 dígitos)
    - id_cita: ID de la cita a cancelar (obtenido del paso 2)
    - motivo_cancelacion: Opcional, motivo de la cancelación
    
    IMPORTANTE: Esta herramienta se usa DESPUÉS de buscar_citas_por_dni.
    El usuario ya está verificado con su DNI.
    """
    args_schema: Type[BaseModel] = CancelarCitaConDniInput
    
    def _run(
        self,
        dni: str,
        id_cita: int,
        motivo_cancelacion: Optional[str] = None
    ) -> str:
        """Ejecuta la cancelación (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(dni, id_cita, motivo_cancelacion))
    
    async def _arun(
        self,
        dni: str,
        id_cita: int,
        motivo_cancelacion: Optional[str] = None
    ) -> str:
        """Ejecuta la cancelación (asíncrono)"""
        try:
            # Si no se proporciona DNI o es placeholder, intentar obtenerlo del contexto
            from app.utils.context import get_current_user_id, get_current_dni
            
            if not dni or dni == "12345678":
                dni_contexto = get_current_dni()
                if dni_contexto:
                    dni = dni_contexto
                    logger.info(f"✅ Usando DNI del contexto: {dni}")
                else:
                    return "❌ No se pudo obtener tu DNI. Por favor, proporciona tu DNI de 8 dígitos."
            
            logger.info(f"❌ Cancelando cita {id_cita} con DNI: {dni}")
            
            # Obtener el user_id del contexto
            user_id = get_current_user_id()
            
            result = await backend_client.cancelar_cita_con_dni(
                dni=dni,
                id_cita=id_cita,
                motivo_cancelacion=motivo_cancelacion,
                user_id=user_id
            )
            
            if result.get("success"):
                cita = result["data"]["cita"]
                return f"""✅ Cita cancelada exitosamente:

📅 Información de la cita cancelada:
- Fecha y hora: {cita['fecha_hora']}
- Médico: {cita['medico']['nombre']} ({cita['medico']['especialidad']})
- Motivo original: {cita['motivo']}

La cita ha sido cancelada. Si deseas agendar una nueva cita, házmelo saber. 😊"""
            else:
                return f"❌ No se pudo cancelar la cita: {result.get('message', 'Error desconocido')}"
                
        except Exception as e:
            logger.error(f"Error en cancelar_cita_con_dni: {str(e)}")
            return f"❌ Error: {str(e)}"


class ReprogramarCitaConDniInput(BaseModel):
    """Input para reprogramar cita usando solo DNI"""
    dni: str = Field(..., description="DNI del paciente (8 dígitos)")
    id_cita: int = Field(..., description="ID de la cita a reprogramar")
    nueva_fecha: str = Field(..., description="Nueva fecha en formato YYYY-MM-DD")
    nueva_hora_inicio: str = Field(..., description="Nueva hora inicio en formato HH:MM")
    nueva_hora_fin: str = Field(..., description="Nueva hora fin en formato HH:MM")
    motivo_reprogramacion: Optional[str] = Field(None, description="Motivo de la reprogramación")


class ReprogramarCitaConDniTool(BaseTool):
    """
    Herramienta para reprogramar cita usando solo DNI del paciente
    Valida disponibilidad del médico en la nueva fecha/hora
    """
    name: str = "reprogramar_cita_con_dni"
    description: str = """
    Reprograma una cita médica usando SOLO el DNI del paciente (sin nombre).
    
    IMPORTANTE: VALIDA automáticamente la disponibilidad del médico en la nueva fecha/hora.
    Si el horario está ocupado, retorna error.
    
    Parámetros:
    - dni: DNI del paciente (8 dígitos)
    - id_cita: ID de la cita a reprogramar
    - nueva_fecha: Nueva fecha en formato YYYY-MM-DD
    - nueva_hora_inicio: Nueva hora inicio en formato HH:MM (ej: "17:00")
    - nueva_hora_fin: Nueva hora fin en formato HH:MM (ej: "18:00")
    - motivo_reprogramacion: Motivo opcional
    
    USA esta herramienta cuando el usuario:
    - Dice "quiero reprogramar mi cita" y ya la has identificado
    - Proporciona nueva fecha/hora como "para el 24 de diciembre a las 5 pm"
    - NO HACE FALTA que el usuario proporcione su nombre
    
    El usuario ya está verificado con su DNI desde el inicio de la sesión.
    """
    args_schema: Type[BaseModel] = ReprogramarCitaConDniInput
    
    def _run(self, **kwargs) -> str:
        """Ejecuta la reprogramación (síncrono)"""
        import asyncio
        return asyncio.run(self._arun(**kwargs))
    
    async def _arun(
        self,
        dni: str,
        id_cita: int,
        nueva_fecha: str,
        nueva_hora_inicio: str,
        nueva_hora_fin: str,
        motivo_reprogramacion: Optional[str] = None
    ) -> str:
        """Ejecuta la reprogramación (asíncrono)"""
        try:
            # Si no se proporciona DNI o es placeholder, intentar obtenerlo del contexto
            from app.utils.context import get_current_user_id, get_current_dni
            
            if not dni or dni == "12345678":
                dni_contexto = get_current_dni()
                if dni_contexto:
                    dni = dni_contexto
                    logger.info(f"✅ Usando DNI del contexto: {dni}")
                else:
                    return "❌ No se pudo obtener tu DNI. Por favor, proporciona tu DNI de 8 dígitos."
            
            logger.info(f"📅 Reprogramando cita {id_cita} con DNI: {dni} a {nueva_fecha} {nueva_hora_inicio}")
            
            # Obtener el user_id del contexto
            user_id = get_current_user_id()
            
            result = await backend_client.reprogramar_cita_con_dni(
                dni=dni,
                id_cita=id_cita,
                nueva_fecha=nueva_fecha,
                nueva_hora_inicio=nueva_hora_inicio,
                nueva_hora_fin=nueva_hora_fin,
                motivo_reprogramacion=motivo_reprogramacion,
                user_id=user_id
            )
            
            if result.get("success"):
                cita = result["data"]["cita"]
                return f"""✅ Cita reprogramada exitosamente:

📅 NUEVA FECHA Y HORA:
- Fecha: {nueva_fecha}
- Horario: {nueva_hora_inicio} a {nueva_hora_fin}

👨‍⚕️ Médico: {cita['medico']['nombre']} ({cita['medico']['especialidad']})
📝 Motivo: {cita['motivo']}

Tu cita ha sido reprogramada. ¡Te esperamos! 😊"""
            else:
                return f"❌ No se pudo reprogramar la cita: {result.get('message', 'Error desconocido')}"
                
        except Exception as e:
            logger.error(f"Error en reprogramar_cita_con_dni: {str(e)}")
            return f"❌ Error: {str(e)}"


# ========================================
# Lista de todas las herramientas
# ========================================

def get_all_tools():
    """
    Retorna todas las herramientas disponibles para el agente
    """
    return [
        # Herramientas de consulta
        BuscarPacienteTool(),
        ConsultarCitasTool(),
        ConsultarHistorialTool(),
        ConsultarDisponibilidadTool(),
        ListarMedicosTool(),
        ValidarMedicoTool(),
        
        # Herramientas de agendamiento
        DeterminarTipoUsuarioTool(),
        SugerirHorariosTool(),
        RegistrarCitaTool(),
        ConfirmarCitaTool(),
        RegistrarInteraccionTool(),
        
        # Herramientas de gestión de citas con DNI - NUEVO
        BuscarCitasPorDniTool(),
        CancelarCitaConDniTool(),
        ReprogramarCitaConDniTool(),
        
        # Herramientas de consulta de información del paciente - NUEVO
        ConsultarOdontogramaTool(),
        ConsultarPagosTool(),
        ConsultarTratamientosTool(),
    ]
