"""
Cliente HTTP para comunicación con el Backend Laravel
Maneja todas las solicitudes HTTP de forma centralizada
"""
import httpx
from typing import Optional, Dict, Any
from app.core import settings, logger


class BackendClient:
    """
    Cliente para comunicarse con el backend Laravel
    
    Este cliente se usa para llamar a los endpoints internos del backend
    que NO requieren autenticación JWT (comunicación interna segura)
    """
    
    def __init__(self):
        self.base_url = settings.BACKEND_URL
        self.timeout = settings.BACKEND_TIMEOUT
        self.api_key = settings.BACKEND_INTERNAL_API_KEY
        self.headers = {
            "X-Internal-API-Key": self.api_key,
            "Content-Type": "application/json",
            "Accept": "application/json"
        }
        
        logger.info(f"🔌 BackendClient inicializado - URL: {self.base_url}")
    
    async def _make_request(
        self,
        method: str,
        endpoint: str,
        data: Optional[Dict[str, Any]] = None,
        params: Optional[Dict[str, Any]] = None
    ) -> Dict[str, Any]:
        """
        Realiza una solicitud HTTP al backend
        
        Args:
            method: Método HTTP (GET, POST, PUT, DELETE)
            endpoint: Endpoint relativo (ej: /api/internal/pacientes)
            data: Datos para el body (POST/PUT)
            params: Parámetros de query string
        
        Returns:
            Dict con la respuesta JSON
        
        Raises:
            Exception: Si hay error en la comunicación
        """
        url = f"{self.base_url}{endpoint}"
        
        try:
            async with httpx.AsyncClient(timeout=self.timeout) as client:
                logger.debug(f"📡 {method} {url}")
                
                response = await client.request(
                    method=method,
                    url=url,
                    headers=self.headers,
                    json=data,
                    params=params
                )
                
                response.raise_for_status()
                result = response.json()
                
                logger.debug(f"✅ Response: {response.status_code}")
                return result
                
        except httpx.HTTPStatusError as e:
            logger.error(f"❌ HTTP Error {e.response.status_code}: {e.response.text}")
            raise Exception(f"Error del backend: {e.response.status_code}")
        except httpx.RequestError as e:
            logger.error(f"❌ Request Error: {str(e)}")
            raise Exception(f"Error de conexión con el backend: {str(e)}")
        except Exception as e:
            logger.error(f"❌ Unexpected Error: {str(e)}")
            raise
    
    # ========================================
    # Endpoints de Pacientes
    # ========================================
    
    async def get_paciente(self, paciente_id: int) -> Dict[str, Any]:
        """Obtiene información de un paciente por ID"""
        return await self._make_request("GET", f"/api/internal/pacientes/{paciente_id}")
    
    async def get_pacientes(
        self,
        limit: int = 10,
        search: Optional[str] = None
    ) -> Dict[str, Any]:
        """Lista pacientes con filtros opcionales"""
        params = {"limit": limit}
        if search:
            params["search"] = search
        return await self._make_request("GET", "/api/internal/pacientes", params=params)
    
    async def buscar_paciente_por_dni(self, dni: str) -> Dict[str, Any]:
        """Busca un paciente por DNI"""
        return await self._make_request("GET", f"/api/internal/pacientes/dni/{dni}")
    
    # ========================================
    # Endpoints de Citas
    # ========================================
    
    async def get_cita(self, cita_id: int) -> Dict[str, Any]:
        """Obtiene información de una cita por ID"""
        return await self._make_request("GET", f"/api/internal/citas/{cita_id}")
    
    async def get_citas_paciente(
        self,
        paciente_id: int,
        estado: Optional[str] = None
    ) -> Dict[str, Any]:
        """Obtiene las citas de un paciente"""
        params = {}
        if estado:
            params["estado"] = estado
        return await self._make_request(
            "GET",
            f"/api/internal/pacientes/{paciente_id}/citas",
            params=params
        )
    
    async def get_citas_medico(
        self,
        medico_id: int,
        fecha: Optional[str] = None
    ) -> Dict[str, Any]:
        """Obtiene las citas de un médico"""
        params = {}
        if fecha:
            params["fecha"] = fecha
        return await self._make_request(
            "GET",
            f"/api/internal/medicos/{medico_id}/citas",
            params=params
        )
    
    async def get_disponibilidad_medico(
        self,
        medico_id: int,
        fecha: str
    ) -> Dict[str, Any]:
        """Obtiene la disponibilidad de un médico en una fecha"""
        return await self._make_request(
            "GET",
            f"/api/internal/medicos/{medico_id}/disponibilidad",
            params={"fecha": fecha}
        )
    
    # ========================================
    # Endpoints de Historial Clínico
    # ========================================
    
    async def get_historial_paciente(self, paciente_id: int) -> Dict[str, Any]:
        """Obtiene el historial clínico de un paciente"""
        return await self._make_request(
            "GET",
            f"/api/internal/pacientes/{paciente_id}/historial"
        )
    
    async def get_historial_resumen(self, paciente_id: int) -> Dict[str, Any]:
        """Obtiene un resumen del historial clínico"""
        return await self._make_request(
            "GET",
            f"/api/internal/pacientes/{paciente_id}/historial-resumen"
        )
    
    # ========================================
    # Endpoints de Médicos
    # ========================================
    
    async def get_medico(self, medico_id: int) -> Dict[str, Any]:
        """Obtiene información de un médico por ID"""
        return await self._make_request("GET", f"/api/internal/medicos/{medico_id}")
    
    async def get_medicos(
        self,
        especialidad: Optional[str] = None
    ) -> Dict[str, Any]:
        """Lista médicos con filtros opcionales"""
        params = {}
        if especialidad:
            params["especialidad"] = especialidad
        return await self._make_request("GET", "/api/internal/medicos", params=params)
    
    # ========================================
    # Endpoints de Tratamientos
    # ========================================
    
    async def get_tratamientos_paciente(self, paciente_id: int) -> Dict[str, Any]:
        """Obtiene los tratamientos de un paciente"""
        return await self._make_request(
            "GET",
            f"/api/internal/pacientes/{paciente_id}/tratamientos"
        )
    
    # ========================================
    # Agendamiento de Citas
    # ========================================
    
    async def determinar_tipo_usuario(self, id_usuario: int) -> Dict[str, Any]:
        """Determina si un usuario es paciente activo o externo"""
        return await self._make_request(
            "GET",
            f"/api/internal/agendamiento/tipo-usuario/{id_usuario}"
        )
    
    async def sugerir_horarios(
        self,
        id_medico: int,
        fecha_inicio: str,
        fecha_fin: Optional[str] = None,
        duracion_minutos: Optional[int] = 60,
        limite: Optional[int] = 3
    ) -> Dict[str, Any]:
        """Sugiere horarios disponibles para un médico"""
        return await self._make_request(
            "POST",
            "/api/internal/agendamiento/sugerir-horarios",
            data={
                "id_medico": id_medico,
                "fecha_inicio": fecha_inicio,
                "fecha_fin": fecha_fin,
                "duracion_minutos": duracion_minutos,
                "limite": limite
            }
        )
    
    async def registrar_cita(
        self,
        id_usuario: int,
        id_medico: int,
        fecha_hora_inicio: str,
        fecha_hora_fin: str,
        motivo: Optional[str] = None,
        tipo_cita: Optional[str] = None,
        notas: Optional[str] = None
    ) -> Dict[str, Any]:
        """Registra una nueva cita médica"""
        return await self._make_request(
            "POST",
            "/api/internal/agendamiento/registrar-cita",
            data={
                "id_usuario": id_usuario,
                "id_medico": id_medico,
                "fecha_hora_inicio": fecha_hora_inicio,
                "fecha_hora_fin": fecha_hora_fin,
                "motivo": motivo,
                "tipo_cita": tipo_cita,
                "notas": notas
            }
        )
    
    async def confirmar_cita(self, id_cita: int) -> Dict[str, Any]:
        """Confirma una cita existente (cambia estado a confirmada)"""
        return await self._make_request(
            "PATCH",
            f"/api/internal/agendamiento/confirmar-cita/{id_cita}"
        )
    
    async def registrar_interaccion(
        self,
        id_usuario: int,
        tipo_intencion: Optional[str] = None,
        entrada_usuario: Optional[str] = None,
        respuesta_ia: Optional[str] = None,
        estado_resultado: Optional[str] = None,
        contexto: Optional[Dict[str, Any]] = None
    ) -> Dict[str, Any]:
        """Registra una interacción del usuario con la IA"""
        return await self._make_request(
            "POST",
            "/api/internal/interacciones",
            data={
                "id_usuario": id_usuario,
                "tipo_intencion": tipo_intencion,
                "entrada_usuario": entrada_usuario,
                "respuesta_ia": respuesta_ia,
                "estado_resultado": estado_resultado,
                "contexto": contexto
            }
        )
    
    # ========================================
    # Gestión de Citas con Verificación DNI - NUEVO
    # ========================================
    
    # ========================================
    # Gestión de Citas con SOLO DNI (Simplificado) - NUEVO
    # ========================================
    
    async def buscar_citas_por_dni(self, dni: str, user_id: Optional[int] = None) -> Dict[str, Any]:
        """
        Busca las citas de un paciente usando solo su DNI
        
        Args:
            dni: DNI del paciente (8 dígitos)
            user_id: ID del usuario logueado (opcional, para validación de seguridad)
            
        Returns:
            Lista de citas del paciente con sus detalles
        """
        data = {"dni": dni}
        if user_id:
            data["user_id"] = user_id
            
        return await self._make_request(
            "POST",
            "/api/internal/citas-dni/buscar",
            data=data
        )
    
    async def cancelar_cita_con_dni(
        self,
        dni: str,
        id_cita: int,
        motivo_cancelacion: Optional[str] = None,
        user_id: Optional[int] = None
    ) -> Dict[str, Any]:
        """
        Cancela una cita usando solo el DNI (sin verificar nombre)
        
        Args:
            dni: DNI del paciente (8 dígitos)
            id_cita: ID de la cita a cancelar
            motivo_cancelacion: Motivo de la cancelación (opcional)
            user_id: ID del usuario logueado (opcional, para validación de seguridad)
        """
        data = {
            "dni": dni,
            "id_cita": id_cita
        }
        if motivo_cancelacion:
            data["motivo_cancelacion"] = motivo_cancelacion
        if user_id:
            data["user_id"] = user_id
            
        return await self._make_request(
            "POST",
            "/api/internal/citas-dni/cancelar",
            data=data
        )
    
    async def reprogramar_cita_con_dni(
        self,
        dni: str,
        id_cita: int,
        nueva_fecha: str,
        nueva_hora_inicio: str,
        nueva_hora_fin: str,
        motivo_reprogramacion: Optional[str] = None,
        user_id: Optional[int] = None
    ) -> Dict[str, Any]:
        """
        Reprograma una cita usando solo el DNI (sin verificar nombre)
        Valida disponibilidad del médico en la nueva fecha/hora
        
        Args:
            dni: DNI del paciente (8 dígitos)
            id_cita: ID de la cita a reprogramar
            nueva_fecha: Nueva fecha en formato YYYY-MM-DD
            nueva_hora_inicio: Nueva hora inicio en formato HH:MM
            nueva_hora_fin: Nueva hora fin en formato HH:MM
            motivo_reprogramacion: Motivo de la reprogramación (opcional)
            user_id: ID del usuario logueado (opcional, para validación de seguridad)
        """
        data = {
            "dni": dni,
            "id_cita": id_cita,
            "nueva_fecha": nueva_fecha,
            "nueva_hora_inicio": nueva_hora_inicio,
            "nueva_hora_fin": nueva_hora_fin
        }
        if motivo_reprogramacion:
            data["motivo_reprogramacion"] = motivo_reprogramacion
        if user_id:
            data["user_id"] = user_id
            
        return await self._make_request(
            "POST",
            "/api/internal/citas-dni/reprogramar",
            data=data
        )
    
    # ========================================
    # Gestión de Citas con Verificación DNI + Nombre - ORIGINAL
    # ========================================
    
    async def cancelar_cita_verificada(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        motivo_cancelacion: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Cancela una cita con verificación de identidad del paciente
        
        Args:
            dni: DNI del paciente (8 dígitos)
            nombre_parcial: Al menos 2 caracteres del nombre del paciente
            id_cita: ID de la cita a cancelar
            motivo_cancelacion: Motivo de la cancelación (opcional)
        """
        return await self._make_request(
            "POST",
            "/api/internal/citas-verificadas/cancelar",
            data={
                "dni": dni,
                "nombre_parcial": nombre_parcial,
                "id_cita": id_cita,
                "motivo_cancelacion": motivo_cancelacion
            }
        )
    
    async def reprogramar_cita_verificada(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        nueva_fecha: str,
        nueva_hora_inicio: str,
        nueva_hora_fin: str,
        motivo_reprogramacion: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Reprograma una cita con verificación de identidad del paciente
        
        Args:
            dni: DNI del paciente (8 dígitos)
            nombre_parcial: Al menos 2 caracteres del nombre del paciente
            id_cita: ID de la cita a reprogramar
            nueva_fecha: Nueva fecha en formato YYYY-MM-DD
            nueva_hora_inicio: Nueva hora de inicio en formato HH:MM
            nueva_hora_fin: Nueva hora de fin en formato HH:MM
            motivo_reprogramacion: Motivo de la reprogramación (opcional)
        """
        return await self._make_request(
            "POST",
            "/api/internal/citas-verificadas/reprogramar",
            data={
                "dni": dni,
                "nombre_parcial": nombre_parcial,
                "id_cita": id_cita,
                "nueva_fecha": nueva_fecha,
                "nueva_hora_inicio": nueva_hora_inicio,
                "nueva_hora_fin": nueva_hora_fin,
                "motivo_reprogramacion": motivo_reprogramacion
            }
        )
    
    async def cambiar_medico_cita(
        self,
        dni: str,
        nombre_parcial: str,
        id_cita: int,
        id_nuevo_medico: int,
        motivo_cambio: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Cambia el médico de una cita con verificación de identidad del paciente
        
        Args:
            dni: DNI del paciente (8 dígitos)
            nombre_parcial: Al menos 2 caracteres del nombre del paciente
            id_cita: ID de la cita
            id_nuevo_medico: ID del nuevo médico
            motivo_cambio: Motivo del cambio (opcional)
        """
        return await self._make_request(
            "POST",
            "/api/internal/citas-verificadas/cambiar-medico",
            data={
                "dni": dni,
                "nombre_parcial": nombre_parcial,
                "id_cita": id_cita,
                "id_nuevo_medico": id_nuevo_medico,
                "motivo_cambio": motivo_cambio
            }
        )
    
    # ========================================
    # Consultas de Información del Paciente - NUEVO
    # ========================================
    
    async def get_odontograma_paciente(self, id_paciente: int) -> Dict[str, Any]:
        """
        Obtiene el odontograma (diagrama dental) de un paciente
        
        Args:
            id_paciente: ID del paciente
        
        Returns:
            Dict con resumen del odontograma y URL para visualización completa
        """
        return await self._make_request(
            "GET",
            f"/api/internal/consultas/paciente/{id_paciente}/odontograma"
        )
    
    async def get_historial_pagos(
        self,
        id_paciente: int,
        tipo: str = "todos",
        limite: int = 10
    ) -> Dict[str, Any]:
        """
        Obtiene el historial de pagos de un paciente
        
        Args:
            id_paciente: ID del paciente
            tipo: Tipo de consulta ('ultimo', 'año_actual', 'todos')
            limite: Cantidad máxima de pagos a retornar
        
        Returns:
            Dict con historial de pagos y resumen financiero
        """
        return await self._make_request(
            "GET",
            f"/api/internal/consultas/paciente/{id_paciente}/pagos",
            params={"tipo": tipo, "limite": limite}
        )
    
    async def get_estado_tratamientos(self, id_paciente: int) -> Dict[str, Any]:
        """
        Obtiene el estado de los tratamientos de un paciente
        
        Args:
            id_paciente: ID del paciente
        
        Returns:
            Dict con tratamientos activos y completados recientes
        """
        return await self._make_request(
            "GET",
            f"/api/internal/consultas/paciente/{id_paciente}/tratamientos"
        )
    
    # ========================================
    # Health Check
    # ========================================
    
    async def health_check(self) -> bool:
        """Verifica si el backend está disponible"""
        try:
            await self._make_request("GET", "/api/internal/health")
            return True
        except Exception:
            return False


# Instancia global del cliente
backend_client = BackendClient()
