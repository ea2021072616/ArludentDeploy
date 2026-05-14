"""
Context Variables para compartir información entre el agente y las herramientas
"""
from contextvars import ContextVar
from typing import Optional

# Variable de contexto para el user_id actual
current_user_id: ContextVar[Optional[int]] = ContextVar('current_user_id', default=None)

# Variable de contexto para el DNI del paciente actual
current_dni: ContextVar[Optional[str]] = ContextVar('current_dni', default=None)

def set_current_user_id(user_id: Optional[int]) -> None:
    """Establece el user_id en el contexto actual"""
    current_user_id.set(user_id)

def get_current_user_id() -> Optional[int]:
    """Obtiene el user_id del contexto actual"""
    return current_user_id.get()

def set_current_dni(dni: Optional[str]) -> None:
    """Establece el DNI en el contexto actual"""
    current_dni.set(dni)

def get_current_dni() -> Optional[str]:
    """Obtiene el DNI del contexto actual"""
    return current_dni.get()
