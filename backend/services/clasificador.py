import os
import json
import re
from typing import Dict, List

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CONFIG_PATH = os.path.join(BASE_DIR, "dependencias_cancun.json")


class ClasificadorDependencias:
    def __init__(self, config_path: str = CONFIG_PATH):
        self.config = self._cargar_configuracion(config_path)
        self.categorias = self.config["sistema_clasificacion_dependencias"]["categorias"]
        self.reglas_especiales = self.config["sistema_clasificacion_dependencias"].get("algoritmo_clasificacion", {}).get("reglas_especiales", {})

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

    def _buscar_coincidencias_frases(self, texto: str, palabra_clave: str) -> bool:
        texto_norm = self._normalizar_texto(texto)
        palabra_norm = self._normalizar_texto(palabra_clave)

        if len(palabra_norm.split()) == 1:
            return palabra_norm in texto_norm and len(palabra_norm) >= 4

        palabras_clave = palabra_norm.split()
        palabras_texto = texto_norm.split()

        coincidencias = sum(1 for pc in palabras_clave if pc in palabras_texto)
        ratio = coincidencias / len(palabras_clave)

        return ratio >= 0.7 and coincidencias >= 2

    def clasificar_multiple(self, texto: str) -> List[Dict]:
        texto = self._normalizar_texto(texto)

        if not texto or len(texto.strip()) < 5:
            return [{
                "categoria": "texto_insuficiente",
                "dependencia": "Dirección de Atención Ciudadana",
                "tipo_peticion": "Consulta general",
                "puntuacion": 0.0,
                "palabras_encontradas": []
            }]

        resultados = {}

        # Reglas especiales
        for regla_nombre, regla in self.reglas_especiales.items():
            palabras_encontradas = [p for p in regla["palabras"] if self._buscar_coincidencias_frases(texto, p)]
            if palabras_encontradas:
                clave = regla["dependencia_directa"]
                if clave not in resultados:
                    resultados[clave] = {
                        "categoria": "especial",
                        "dependencia": clave,
                        "tipo_peticion": f"Caso especial: {regla_nombre}",
                        "puntuacion": 1.0,
                        "palabras_encontradas": palabras_encontradas
                    }

        # Palabras clave por dependencia
        dependencias_detectadas = set()
        for categoria_id, categoria in self.categorias.items():
            for palabra_data in categoria.get("palabras_clave", []):
                palabra = palabra_data["palabra_clave"]
                dependencia = palabra_data["dependencia"]
                tipo = palabra_data["tipo_peticion"]

                if dependencia in dependencias_detectadas:
                    continue

                if self._buscar_coincidencias_frases(texto, palabra):
                    resultados[dependencia] = {
                        "categoria": categoria_id,
                        "dependencia": dependencia,
                        "tipo_peticion": tipo,
                        "puntuacion": 1.0,
                        "palabras_encontradas": [palabra]
                    }
                    dependencias_detectadas.add(dependencia)

        sugerencias = list(resultados.values())
        sugerencias = [s for s in sugerencias if s["puntuacion"] >= 0.5]

        return sugerencias if sugerencias else [{
            "categoria": "sin_coincidencias",
            "dependencia": "Dirección de Atención Ciudadana",
            "tipo_peticion": "Consulta general",
            "puntuacion": 0.0,
            "palabras_encontradas": []
        }]

    def obtener_categorias_disponibles(self) -> List[str]:
        return list(self.categorias.keys())
