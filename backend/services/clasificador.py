import os
import json
import re
from typing import Dict, List
import joblib
import numpy as np

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CONFIG_PATH = os.path.join(BASE_DIR, "dependecias_yucatan.json")
MODELO_PATH = os.path.join(BASE_DIR, "modelo_dependencias.joblib")


class ClasificadorDependencias:
    def __init__(self, config_path: str = CONFIG_PATH):
        self.config = self._cargar_configuracion(config_path)
        self.categorias = self.config["sistema_clasificacion_dependencias"]["catalogo_dependencias"]
        self.reglas_especiales = self.config["sistema_clasificacion_dependencias"].get("algoritmo_clasificacion", {}).get("reglas_especiales", {})
        
        # Intentar cargar el modelo de IA
        self.pipeline = None
        self.modelo = None
        self.vectorizador = None
        self.modelo_disponible = False
        self._cargar_modelo_ia()

    def _cargar_configuracion(self, path: str) -> dict:
        with open(path, 'r', encoding='utf-8') as f:
            return json.load(f)

    def _normalizar_texto(self, texto: str) -> str:
        texto = texto.lower()
        reemplazos = {'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u', 'ñ': 'n', 'ü': 'u'}
        for acento, sin_acento in reemplazos.items():
            texto = texto.replace(acento, sin_acento)
        texto = re.sub(r'[^\w\s]', ' ', texto)
        return re.sub(r'\s+', ' ', texto).strip()
    
    def _calcular_puntuacion_palabra(self, palabra_norm: str, texto_set: set, texto_norm: str) -> float:
        """Calcula la puntuación de coincidencia de una palabra clave con el texto."""
        palabras = palabra_norm.split()
        
        # Palabra simple (1 palabra)
        if len(palabras) == 1:
            if len(palabra_norm) < 3:  # Ignorar palabras muy cortas
                return 0.0
            # Búsqueda exacta en el set para palabras individuales
            if palabra_norm in texto_set:
                # Mayor peso si la palabra es más larga (más específica)
                return 1.0 + (len(palabra_norm) * 0.1)
            # Búsqueda parcial si contiene la palabra (para plurales, derivados)
            if any(palabra_norm in palabra_texto for palabra_texto in texto_set):
                return 0.8
            return 0.0
        
        # Frase (múltiples palabras)
        else:
            coincidencias = sum(1 for p in palabras if p in texto_set and len(p) >= 3)
            if coincidencias == 0:
                return 0.0
            
            ratio = coincidencias / len(palabras)
            
            # Frase completa
            if ratio == 1.0:
                return 2.0
            # Mayoría de palabras
            elif ratio >= 0.6:
                return 1.5 * ratio
            # Algunas coincidencias
            elif coincidencias >= 2:
                return 1.0 * ratio
            
            return 0.0


    def clasificar_multiple(self, texto: str) -> List[Dict]:
        texto_norm = self._normalizar_texto(texto)
        if not texto_norm or len(texto_norm.strip()) < 5:
            return [{
                "categoria": "texto_insuficiente",
                "dependencia": "Dirección de Atención Ciudadana",
                "tipo_peticion": "Consulta general",
                "confianza": 0.0,
                "palabras_encontradas": []
            }]

        resultados = {}
        texto_set = set(texto_norm.split())

        # Procesar cada dependencia
        for categoria_id, categoria in self.categorias.items():
            nombre_dependencia = categoria.get("nombre", categoria_id)
            categoria_sugerida = categoria.get("categoria_sugerida", categoria_id)
            palabras_globales = categoria.get("palabras_clave_globales", [])
            servicios = categoria.get("servicios_comunes", [])
            
            # Buscar coincidencias en palabras clave globales
            puntuacion_global = 0.0
            palabras_encontradas_globales = []
            
            for palabra in palabras_globales:
                palabra_norm = self._normalizar_texto(palabra)
                puntos = self._calcular_puntuacion_palabra(palabra_norm, texto_set, texto_norm)
                if puntos > 0:
                    puntuacion_global += puntos
                    palabras_encontradas_globales.append(palabra_norm)
            
            # Si hay match con palabras globales, agregar resultado genérico
            if puntuacion_global > 0:
                key = f"{nombre_dependencia}_global"
                resultados[key] = {
                    "categoria": categoria_sugerida,
                    "dependencia": nombre_dependencia,
                    "tipo_peticion": "Consulta general",
                    "puntuacion": puntuacion_global * 0.5,  # Peso menor para globales
                    "palabras_encontradas": palabras_encontradas_globales
                }
            
            # Buscar coincidencias en servicios específicos
            for servicio in servicios:
                tramite = servicio.get("tramite", "")
                palabras_especificas = servicio.get("palabras_clave_especificas", [])
                
                puntuacion_especifica = 0.0
                palabras_encontradas_especificas = []
                
                for palabra in palabras_especificas:
                    palabra_norm = self._normalizar_texto(palabra)
                    puntos = self._calcular_puntuacion_palabra(palabra_norm, texto_set, texto_norm)
                    if puntos > 0:
                        puntuacion_especifica += puntos
                        palabras_encontradas_especificas.append(palabra_norm)
                
                # Si hay match específico, agregar con mayor peso
                if puntuacion_especifica > 0:
                    key = f"{nombre_dependencia}_{tramite}"
                    # Sumar puntos globales también para reforzar la coincidencia
                    puntuacion_total = puntuacion_especifica + (puntuacion_global * 0.3)
                    
                    if key not in resultados or puntuacion_total > resultados[key]["puntuacion"]:
                        resultados[key] = {
                            "categoria": categoria_sugerida,
                            "dependencia": nombre_dependencia,
                            "tipo_peticion": tramite,
                            "puntuacion": puntuacion_total,
                            "palabras_encontradas": palabras_encontradas_especificas + palabras_encontradas_globales
                        }

        # Filtrar, ordenar y normalizar confianza
        sugerencias = list(resultados.values())
        sugerencias = [s for s in sugerencias if s["puntuacion"] > 0]
        
        if not sugerencias:
            return [{
                "categoria": "sin_coincidencias",
                "dependencia": "Dirección de Atención Ciudadana",
                "tipo_peticion": "Consulta general",
                "confianza": 0.0,
                "palabras_encontradas": []
            }]
        
        # Ordenar por puntuación
        sugerencias.sort(key=lambda x: x["puntuacion"], reverse=True)
        
        # Convertir puntuación a porcentaje de confianza (0-100)
        max_puntuacion = sugerencias[0]["puntuacion"]
        for s in sugerencias:
            # Normalizar a escala 0-100
            confianza = min(100, (s["puntuacion"] / max(max_puntuacion, 1)) * 100)
            s["confianza"] = round(confianza, 1)
            del s["puntuacion"]
        
        # Retornar top 3 resultados
        return sugerencias[:3]

    def obtener_categorias_disponibles(self) -> List[str]:
        """Retorna la lista de nombres de dependencias disponibles"""
        dependencias = []
        for categoria_id, categoria in self.categorias.items():
            nombre = categoria.get("nombre", categoria_id)
            if nombre not in dependencias:
                dependencias.append(nombre)
        return sorted(dependencias)

    def _cargar_modelo_ia(self):
        """Carga el modelo de IA si está disponible"""
        try:
            if os.path.exists(MODELO_PATH):
                data = joblib.load(MODELO_PATH)
                
                # Intentar cargar el pipeline completo primero
                if 'pipeline' in data:
                    self.pipeline = data['pipeline']
                    self.modelo = data.get('modelo')
                    self.vectorizador = data.get('vectorizador')
                else:
                    # Fallback: el archivo es el pipeline directamente (formato antiguo)
                    self.pipeline = data
                    self.modelo = data.named_steps.get('clf')
                    self.vectorizador = data.named_steps.get('tfidf')
                
                self.modelo_disponible = True
                print("✅ Modelo de IA cargado correctamente")
            else:
                print("⚠️ Modelo de IA no encontrado, usando sistema de reglas")
        except Exception as e:
            print(f"⚠️ Error al cargar modelo de IA: {e}. Usando sistema de reglas")
            self.modelo_disponible = False

    def _limpiar_texto_ia(self, texto: str) -> str:
        """Limpia el texto para el modelo de IA (igual que en el entrenamiento)"""
        texto = texto.lower()
        texto = re.sub(r'[^\w\s]', ' ', texto)
        texto = re.sub(r'\s+', ' ', texto)
        return texto.strip()

    def clasificar_con_ia(self, texto: str, top_n: int = 3) -> List[Dict]:
        """Clasifica usando el modelo de IA entrenado"""
        if not self.modelo_disponible:
            return []

        try:
            # Limpiar texto
            texto_limpio = self._limpiar_texto_ia(texto)
            
            # Usar el pipeline completo si está disponible
            if hasattr(self, 'pipeline') and self.pipeline:
                prediccion = self.pipeline.predict([texto_limpio])[0]
                
                # Intentar obtener decision_function para confianza
                try:
                    probabilidades = self.pipeline.decision_function([texto_limpio])[0]
                    top_indices = np.argsort(probabilidades)[-top_n:][::-1]
                    clases = self.pipeline.classes_
                except:
                    # Si no hay decision_function, solo retornar la predicción principal
                    return [{
                        "categoria": "Inteligencia Artificial",
                        "dependencia": prediccion,
                        "tipo_peticion": "Clasificación automática",
                        "puntuacion": 0.95,
                        "palabras_encontradas": []
                    }]
            else:
                # Método alternativo con vectorizador y modelo separados
                X = self.vectorizador.transform([texto_limpio])
                prediccion = self.modelo.predict(X)[0]
                probabilidades = self.modelo.decision_function(X)[0]
                top_indices = np.argsort(probabilidades)[-top_n:][::-1]
                clases = self.modelo.classes_
            
            resultados = []
            for idx in top_indices:
                dependencia = clases[idx]
                confianza = float(probabilidades[idx])
                
                # Normalizar confianza a 0-1
                confianza_normalizada = 1 / (1 + np.exp(-confianza))
                
                resultados.append({
                    "categoria": "Inteligencia Artificial",
                    "dependencia": dependencia,
                    "tipo_peticion": "Clasificación automática",
                    "puntuacion": round(confianza_normalizada, 3),
                    "palabras_encontradas": []
                })
            
            return resultados
            
        except Exception as e:
            print(f"❌ Error en clasificación con IA: {e}")
            return []
