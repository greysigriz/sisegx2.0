from fastapi import APIRouter
from pydantic import BaseModel
from services.clasificador import ClasificadorDependencias

# Prefijo para organizar bien las rutas
router = APIRouter(
    prefix="/api/clasificacion",
    tags=["clasificacion"]
)

clasificador = ClasificadorDependencias(config_path="dependencias_cancun.json")

# Modelo de entrada para la petición
class PeticionRequest(BaseModel):
    descripcion: str

@router.post("/clasificar")
def clasificar_peticion(request: PeticionRequest):
    resultado = clasificador.clasificar(request.descripcion)
    return resultado
