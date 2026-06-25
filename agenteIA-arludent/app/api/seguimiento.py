"""
Endpoint para analizar respuestas de seguimiento post-tratamiento
Utiliza GPT-4o-mini para análisis de sentimiento y detección de urgencias
"""

from fastapi import APIRouter, HTTPException, Depends, BackgroundTasks
from pydantic import BaseModel, Field
from typing import Optional, Dict, Any, List
import logging
import httpx
from datetime import datetime
from app.core.config import settings
from app.services.openai_service import OpenAIService

logger = logging.getLogger(__name__)
router = APIRouter()


class RespuestaPaciente(BaseModel):
    """Modelo para la respuesta del paciente"""
    estado_paciente: str = Field(..., description="Estado general: excelente, bien, regular, mal")
    sintomas_reportados: Optional[str] = Field(None, description="Síntomas o molestias reportadas")
    observaciones_paciente: Optional[str] = Field(None, description="Observaciones adicionales")
    necesita_revision: bool = Field(False, description="Solicita cita de revisión")


class SeguimientoData(BaseModel):
    """Modelo para datos del seguimiento"""
    seguimiento_id: int = Field(..., description="ID del seguimiento")
    paciente_nombre: str = Field(..., description="Nombre del paciente")
    tipo_tratamiento: str = Field(..., description="Tipo de tratamiento realizado")
    dias_desde_tratamiento: int = Field(..., description="Días transcurridos desde el tratamiento")
    respuesta: RespuestaPaciente = Field(..., description="Respuesta del paciente")


class AnalisisResultado(BaseModel):
    """Modelo para el resultado del análisis"""
    nivel_urgencia: str = Field(..., description="bajo, medio, alto")
    requiere_atencion: bool = Field(..., description="Si requiere atención de secretaria")
    sentimiento_general: str = Field(..., description="positivo, neutral, negativo")
    sintomas_detectados: List[str] = Field(default_factory=list, description="Síntomas identificados")
    recomendacion: str = Field(..., description="Recomendación para la secretaria")
    resumen: str = Field(..., description="Resumen ejecutivo del estado")
    probabilidad_complicacion: float = Field(..., ge=0, le=1, description="0-1")
    necesita_cita_urgente: bool = Field(..., description="Si necesita cita urgente")


class WebhookPayload(BaseModel):
    """Payload que se enviará al webhook de Laravel"""
    seguimiento_id: int
    analisis: AnalisisResultado
    timestamp: datetime


async def enviar_webhook_laravel(payload: WebhookPayload):
    """
    Envía el resultado del análisis al webhook de Laravel
    """
    webhook_url = f"{settings.LARAVEL_API_URL}/api/seguimiento/webhook-ia"
    
    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.post(
                webhook_url,
                content=payload.json(),
                headers={
                    "Content-Type": "application/json",
                    "X-Internal-Key": settings.INTERNAL_API_KEY
                }
            )
            
            if response.status_code == 200:
                logger.info(f"✅ Webhook enviado exitosamente para seguimiento {payload.seguimiento_id}")
            else:
                logger.error(
                    f"❌ Error en webhook para seguimiento {payload.seguimiento_id}: "
                    f"Status {response.status_code}, Response: {response.text}"
                )
                
    except Exception as e:
        logger.error(f"❌ Excepción al enviar webhook: {str(e)}")


@router.post("/analizar-respuesta", response_model=AnalisisResultado)
async def analizar_respuesta_seguimiento(
    data: SeguimientoData,
    background_tasks: BackgroundTasks,
    openai_service: OpenAIService = Depends()
):
    """
    Analiza la respuesta del paciente usando GPT-4o-mini
    
    **Flujo:**
    1. Recibe datos del seguimiento con respuesta del paciente
    2. Construye prompt especializado para análisis médico
    3. Consulta GPT-4o-mini con configuración optimizada
    4. Procesa respuesta y detecta urgencias
    5. Envía resultado al webhook de Laravel (background)
    6. Retorna análisis estructurado
    
    **Parámetros de análisis:**
    - Sentimiento del paciente (positivo/neutral/negativo)
    - Detección de síntomas de alarma
    - Nivel de urgencia (bajo/medio/alto)
    - Probabilidad de complicación
    - Recomendaciones para secretaria
    """
    
    logger.info(f"📊 Analizando seguimiento #{data.seguimiento_id} - Paciente: {data.paciente_nombre}")
    
    try:
        # Construir prompt especializado
        prompt = construir_prompt_analisis(data)
        
        # Llamar a OpenAI con configuración optimizada
        resultado_ia = await openai_service.analizar_seguimiento_post_tratamiento(
            prompt=prompt,
            paciente_nombre=data.paciente_nombre,
            tipo_tratamiento=data.tipo_tratamiento
        )
        
        # Parsear resultado
        analisis = parsear_respuesta_ia(resultado_ia, data)
        
        # Log del resultado
        logger.info(
            f"✅ Análisis completado - Seguimiento #{data.seguimiento_id}: "
            f"Urgencia={analisis.nivel_urgencia}, "
            f"Requiere atención={analisis.requiere_atencion}, "
            f"Sentimiento={analisis.sentimiento_general}"
        )
        
        # Enviar webhook al backend Laravel (en background)
        webhook_payload = WebhookPayload(
            seguimiento_id=data.seguimiento_id,
            analisis=analisis,
            timestamp=datetime.utcnow()
        )
        background_tasks.add_task(enviar_webhook_laravel, webhook_payload)
        
        return analisis
        
    except Exception as e:
        logger.error(f"❌ Error al analizar seguimiento #{data.seguimiento_id}: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=f"Error al procesar análisis: {str(e)}"
        )


def construir_prompt_analisis(data: SeguimientoData) -> str:
    """
    Construye prompt optimizado para análisis médico dental
    """
    return f"""Eres un asistente médico especializado en odontología. Analiza la siguiente respuesta de seguimiento post-tratamiento.

**INFORMACIÓN DEL PACIENTE:**
- Nombre: {data.paciente_nombre}
- Tratamiento: {data.tipo_tratamiento}
- Días desde tratamiento: {data.dias_desde_tratamiento}

**RESPUESTA DEL PACIENTE:**
- Estado general reportado: {data.respuesta.estado_paciente}
- Síntomas/molestias: {data.respuesta.sintomas_reportados or "Ninguno reportado"}
- Observaciones adicionales: {data.respuesta.observaciones_paciente or "Ninguna"}
- Solicita cita de revisión: {"Sí" if data.respuesta.necesita_revision else "No"}

**INSTRUCCIONES DE ANÁLISIS:**

1. **Evaluación de urgencia**: Determina si hay signos de alarma (infección, sangrado excesivo, dolor severo, inflamación anormal).

2. **Sentimiento del paciente**: Clasifica el sentimiento general como positivo, neutral o negativo basándote en su estado y comentarios.

3. **Síntomas de riesgo**: Identifica síntomas específicos que requieran seguimiento:
   - Dolor intenso o que empeora
   - Inflamación progresiva
   - Sangrado persistente
   - Fiebre o malestar general
   - Sensibilidad extrema
   - Problemas de cicatrización

4. **Nivel de urgencia**: Clasifica como:
   - **ALTO**: Signos de complicación grave, requiere atención inmediata
   - **MEDIO**: Molestias moderadas, requiere seguimiento en 24-48h
   - **BAJO**: Recuperación normal, no requiere intervención

5. **Recomendación**: Proporciona una recomendación clara para la secretaria sobre qué acción tomar.

**IMPORTANTE**: 
- Considera que algunos síntomas son normales en los primeros días (leve molestia, sensibilidad leve).
- Prioriza la seguridad del paciente: ante la duda, recomienda contacto o revisión.
- Sé específico en las recomendaciones.

Responde en formato JSON con esta estructura exacta:
{{
    "nivel_urgencia": "bajo|medio|alto",
    "requiere_atencion": true|false,
    "sentimiento_general": "positivo|neutral|negativo",
    "sintomas_detectados": ["lista", "de", "sintomas"],
    "recomendacion": "texto con recomendación clara",
    "resumen": "resumen ejecutivo en 1-2 líneas",
    "probabilidad_complicacion": 0.0-1.0,
    "necesita_cita_urgente": true|false
}}"""


def parsear_respuesta_ia(respuesta_ia: str, data: SeguimientoData) -> AnalisisResultado:
    """
    Parsea la respuesta de OpenAI y construye el objeto de análisis
    
    Incluye lógica de fallback si GPT no retorna JSON válido
    """
    import json
    
    try:
        # Intentar parsear JSON
        analisis_dict = json.loads(respuesta_ia)
        
        # Validación adicional: si el paciente reportó "mal" o síntomas graves, escalar urgencia
        if data.respuesta.estado_paciente == "mal":
            if analisis_dict.get("nivel_urgencia") == "bajo":
                analisis_dict["nivel_urgencia"] = "medio"
                analisis_dict["requiere_atencion"] = True
        
        # Si reportó síntomas pero el análisis dice "bajo", revisar
        if data.respuesta.sintomas_reportados and len(data.respuesta.sintomas_reportados) > 50:
            if analisis_dict.get("nivel_urgencia") == "bajo":
                analisis_dict["requiere_atencion"] = True
        
        return AnalisisResultado(**analisis_dict)
        
    except json.JSONDecodeError:
        # Fallback: análisis conservador
        logger.warning(f"⚠️ GPT no retornó JSON válido, usando fallback para seguimiento #{data.seguimiento_id}")
        
        return AnalisisResultado(
            nivel_urgencia="medio",
            requiere_atencion=True,
            sentimiento_general="neutral",
            sintomas_detectados=["Requiere revisión manual"],
            recomendacion="Por favor, revisa manualmente esta respuesta. El análisis automático no pudo completarse.",
            resumen=f"Paciente {data.paciente_nombre} reportó estado: {data.respuesta.estado_paciente}",
            probabilidad_complicacion=0.5,
            necesita_cita_urgente=data.respuesta.necesita_revision
        )
