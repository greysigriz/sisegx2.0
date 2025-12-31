from fastapi import APIRouter, HTTPException
from models.peticion import ClasificacionRequest, FeedbackClasificacion
from services.clasificador import ClasificadorDependencias
import csv
import os
from datetime import datetime

router = APIRouter(tags=["clasificacion"])
clasificador = ClasificadorDependencias()

# Path al CSV de entrenamiento
CSV_PATH = os.path.join(os.path.dirname(os.path.dirname(__file__)), "ejemplos_dependencias.csv")


@router.post("/clasificar")
async def clasificar_varias_dependencias(request: ClasificacionRequest):
    """
    Clasifica una petición usando el modelo de IA entrenado o sistema de reglas
    """
    if not request.texto or len(request.texto.strip()) < 5:
        raise HTTPException(status_code=400, detail="Texto demasiado corto")

    try:
        # Intentar usar el modelo de IA primero
        resultados_ia = clasificador.clasificar_con_ia(request.texto)
        
        if resultados_ia:
            # Si el modelo de IA funcionó, usarlo
            return {
                "resultado": resultados_ia,
                "total_encontradas": len(resultados_ia),
                "metodo": "ia"
            }
        else:
            # Fallback al sistema de reglas
            resultados = clasificador.clasificar_multiple(request.texto)
            return {
                "resultado": resultados,
                "total_encontradas": len(resultados),
                "metodo": "reglas"
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


@router.post("/feedback")
async def guardar_feedback(feedback: FeedbackClasificacion):
    """
    Guarda el feedback del usuario para mejorar el modelo.
    Evita duplicados exactos y muy similares.
    """
    try:
        import pandas as pd
        from difflib import SequenceMatcher
        
        # Normalizar el texto para comparación
        texto_normalizado = feedback.texto.lower().strip()
        
        # Leer el CSV existente
        if os.path.exists(CSV_PATH):
            df_existente = pd.read_csv(CSV_PATH)
            
            # Verificar duplicados exactos
            duplicado_exacto = (
                (df_existente['texto'].str.lower().str.strip() == texto_normalizado) &
                (df_existente['dependencia'] == feedback.dependencia_confirmada)
            ).any()
            
            if duplicado_exacto:
                return {
                    "success": True,
                    "message": "Ejemplo ya existe en el dataset (no se agregó duplicado)",
                    "duplicado": True,
                    "timestamp": datetime.now().isoformat()
                }
            
            # Verificar similares (más del 90% de similitud)
            for idx, row in df_existente.iterrows():
                similitud = SequenceMatcher(
                    None, 
                    texto_normalizado, 
                    row['texto'].lower().strip()
                ).ratio()
                
                if similitud > 0.90 and row['dependencia'] == feedback.dependencia_confirmada:
                    return {
                        "success": True,
                        "message": "Ejemplo muy similar ya existe (no se agregó)",
                        "similar": True,
                        "similitud": round(similitud * 100, 1),
                        "ejemplo_existente": row['texto'],
                        "timestamp": datetime.now().isoformat()
                    }
        
        # Agregar el nuevo ejemplo al CSV
        with open(CSV_PATH, 'a', newline='', encoding='utf-8') as f:
            writer = csv.writer(f)
            writer.writerow([feedback.texto, feedback.dependencia_confirmada])
        
        return {
            "success": True,
            "message": "Feedback guardado correctamente",
            "nuevo": True,
            "timestamp": datetime.now().isoformat()
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error al guardar feedback: {str(e)}")


@router.post("/reentrenar")
async def reentrenar_modelo():
    """
    Reentrena el modelo con los datos actualizados
    """
    try:
        import subprocess
        import sys
        
        # Ejecutar el script de reentrenamiento
        script_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "reentrenar_modelo.py")
        
        # Ejecutar en background
        result = subprocess.run(
            [sys.executable, script_path],
            capture_output=True,
            text=True,
            timeout=120  # 2 minutos máximo
        )
        
        if result.returncode == 0:
            # Recargar el modelo en el clasificador
            clasificador._cargar_modelo_ia()
            
            return {
                "success": True,
                "message": "Modelo reentrenado exitosamente",
                "output": result.stdout,
                "timestamp": datetime.now().isoformat()
            }
        else:
            raise Exception(f"Error en entrenamiento: {result.stderr}")
            
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error al reentrenar: {str(e)}")
