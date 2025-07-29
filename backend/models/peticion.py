from pydantic import BaseModel, Field
from typing import Optional
from datetime import datetime
from enum import Enum

class EstadoPeticion(str, Enum):
    PENDIENTE = "pendiente"
    EN_PROCESO = "en_proceso" 
    RESUELTA = "resuelta"
    RECHAZADA = "rechazada"

class PeticionCreate(BaseModel):
    nombre: str = Field(..., min_length=2, max_length=100, description="Nombre del ciudadano")
    email: str = Field(..., description="Correo electrónico")
    telefono: Optional[str] = Field(None, max_length=15, description="Teléfono de contacto")
    descripcion: str = Field(..., min_length=10, max_length=1000, description="Descripción de la petición")
    
    class Config:
        json_schema_extra = {
            "example": {
                "nombre": "Juan Pérez",
                "email": "juan.perez@email.com", 
                "telefono": "9981234567",
                "descripcion": "Hay un bache muy grande en la calle principal que necesita ser reparado urgentemente"
            }
        }

class PeticionResponse(BaseModel):
    id: int
    nombre: str
    email: str
    telefono: Optional[str]
    descripcion: str
    categoria: str
    dependencia: str
    tipo_peticion: str
    estado: EstadoPeticion
    fecha_creacion: datetime
    fecha_actualizacion: Optional[datetime]
    
    class Config:
        from_attributes = True

class ClasificacionRequest(BaseModel):
    texto: str = Field(..., min_length=10, description="Texto de la petición a clasificar")
    
    class Config:
        json_schema_extra = {
            "example": {
                "texto": "Necesito que reparen el bache que está en mi calle"
            }
        }

class ClasificacionResponse(BaseModel):
    categoria: str
    dependencia: str
    tipo_peticion: str
    puntuacion: float
    palabras_encontradas: list[str]
    
    class Config:
        json_schema_extra = {
            "example": {
                "categoria": "obras_servicios_publicos",
                "dependencia": "Dirección de Bacheo y Pipas",
                "tipo_peticion": "Reparación de calles y banquetas",
                "puntuacion": 0.95,
                "palabras_encontradas": ["bache", "calle", "reparar"]
            }
        }