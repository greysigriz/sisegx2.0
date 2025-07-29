from fastapi import APIRouter, HTTPException
from models.peticion import ClasificacionRequest
from services.clasificador import ClasificadorDependencias

router = APIRouter(tags=["clasificacion"])
clasificador = ClasificadorDependencias()


@router.post("/clasificar")
async def clasificar_varias_dependencias(request: ClasificacionRequest):
    """
    Clasifica una petición y devuelve todas las dependencias posibles
    """
    if not request.texto or len(request.texto.strip()) < 5:
        raise HTTPException(status_code=400, detail="Texto demasiado corto")

    try:
        resultados = clasificador.clasificar_multiple(request.texto)
        return {
            "resultado": resultados,
            "total_encontradas": len(resultados)
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error en clasificación: {str(e)}")


@router.get("/categorias")
async def obtener_categorias():
    try:
        return {
            "categorias": clasificador.obtener_categorias_disponibles()
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
