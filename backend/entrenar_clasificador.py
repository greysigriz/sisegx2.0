import pandas as pd
import unicodedata
import re
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import LinearSVC
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report

# 1. Función de limpieza de texto (Normalización)
def limpiar_texto(texto):
    # Convertir a minúsculas
    texto = texto.lower()
    # Quitar acentos (á -> a, ñ -> n) para generalizar mejor
    texto = ''.join((c for c in unicodedata.normalize('NFD', texto) if unicodedata.category(c) != 'Mn'))
    # Quitar caracteres especiales
    texto = re.sub(r'[^a-z0-9\s]', '', texto)
    return texto

# 2. Cargar datos (Asegúrate de guardar tu excel/texto como 'dataset_yucatan.csv')
try:
    df = pd.read_csv('backend/ejemplos_dependencias.csv')
except FileNotFoundError:
    print("Error: No se encuentra el archivo 'backend/ejemplos_dependencias.csv'. Asegúrate de crearlo con los datos.")
    exit()

# Aplicar limpieza
df['texto_limpio'] = df['texto'].apply(limpiar_texto)

# 3. Dividir datos para entrenamiento y prueba (80% entrenar, 20% validar)
X_train, X_test, y_train, y_test = train_test_split(
    df['texto_limpio'], 
    df['dependencia'], 
    test_size=0.2, 
    random_state=42,
    stratify=df['dependencia'] # Asegura que haya ejemplos de todas las clases en ambos sets
)

# 4. Crear el Pipeline del Modelo
# TfidfVectorizer: Convierte texto a números (frecuencia de palabras)
# LinearSVC: El algoritmo de clasificación (rápido y eficiente para texto)
pipeline = Pipeline([
    ('tfidf', TfidfVectorizer(ngram_range=(1, 2))), # Mira palabras individuales y pares de palabras
    ('clf', LinearSVC())
])

# 5. Entrenar
print("Entrenando modelo...")
pipeline.fit(X_train, y_train)
print("Modelo entrenado.")

# 6. Evaluar precisión
print("\n--- Reporte de Clasificación ---")
predicciones = pipeline.predict(X_test)
print(classification_report(y_test, predicciones))

# 7. Función para probar en vivo
def clasificar_peticion(peticion):
    peticion_limpia = limpiar_texto(peticion)
    prediccion = pipeline.predict([peticion_limpia])[0]
    return prediccion


# 8. Guardar el modelo entrenado para uso en API/frontend
import joblib

# Guardar el modelo completo (pipeline) y el vectorizador por separado para compatibilidad
modelo_data = {
    'pipeline': pipeline,
    'modelo': pipeline.named_steps['clf'],
    'vectorizador': pipeline.named_steps['tfidf'],
    'version': '1.0'
}

joblib.dump(modelo_data, 'backend/modelo_dependencias.joblib')
print("\nModelo guardado en backend/modelo_dependencias.joblib")

# 9. Prueba rápida (opcional)
def prueba_rapida():
    print("\n--- Prueba rápida del modelo ---")
    ejemplos = [
        "Necesito ayuda para mi hijo en la escuela",
        "Me mordió un perro callejero",
        "Quiero renovar mi licencia de conducir",
        "Busco apoyo para mi negocio",
        "Hay muchos mosquitos en mi colonia, vengan a fumigar"
    ]
    for texto in ejemplos:
        resultado = clasificar_peticion(texto)
        print(f"Petición: {texto}\n--> Canalizar a: {resultado}\n")

prueba_rapida()

# Bucle interactivo (solo si se ejecuta directamente)
if __name__ == "__main__":
    print("\n--- Sistema Listo (Escribe 'salir' para terminar) ---")
    while True:
        usuario = input("\nEscribe una petición: ")
        if usuario.lower() == 'salir':
            break
        resultado = clasificar_peticion(usuario)
        print(f"--> Canalizar a: {resultado}")