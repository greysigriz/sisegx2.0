"""
Script para reentrenar el modelo automáticamente sin interacción del usuario.
Se ejecuta cuando hay nuevos datos de feedback.
"""

import pandas as pd
import unicodedata
import re
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import LinearSVC
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report
import joblib

def limpiar_texto(texto):
    texto = texto.lower()
    texto = ''.join((c for c in unicodedata.normalize('NFD', texto) if unicodedata.category(c) != 'Mn'))
    texto = re.sub(r'[^a-z0-9\s]', '', texto)
    return texto

def reentrenar():
    print("🔄 Iniciando reentrenamiento del modelo...")
    
    try:
        # Cargar datos
        df = pd.read_csv('ejemplos_dependencias.csv')
        print(f"📊 Dataset cargado: {len(df)} ejemplos")
        
        # Aplicar limpieza
        df['texto_limpio'] = df['texto'].apply(limpiar_texto)
        
        # Dividir datos
        X_train, X_test, y_train, y_test = train_test_split(
            df['texto_limpio'], 
            df['dependencia'], 
            test_size=0.2, 
            random_state=42,
            stratify=df['dependencia']
        )
        
        # Crear pipeline
        pipeline = Pipeline([
            ('tfidf', TfidfVectorizer(ngram_range=(1, 2))),
            ('clf', LinearSVC())
        ])
        
        # Entrenar
        print("⚙️ Entrenando modelo...")
        pipeline.fit(X_train, y_train)
        
        # Evaluar
        predicciones = pipeline.predict(X_test)
        reporte = classification_report(y_test, predicciones, output_dict=True)
        accuracy = reporte['accuracy']
        print(f"✅ Modelo entrenado. Precisión: {accuracy:.2%}")
        
        # Guardar
        modelo_data = {
            'pipeline': pipeline,
            'modelo': pipeline.named_steps['clf'],
            'vectorizador': pipeline.named_steps['tfidf'],
            'version': '1.0',
            'accuracy': accuracy,
            'total_ejemplos': len(df)
        }
        
        joblib.dump(modelo_data, 'modelo_dependencias.joblib')
        print("💾 Modelo guardado exitosamente")
        
        return {
            'success': True,
            'accuracy': accuracy,
            'total_ejemplos': len(df)
        }
        
    except Exception as e:
        print(f"❌ Error durante el reentrenamiento: {e}")
        return {
            'success': False,
            'error': str(e)
        }

if __name__ == "__main__":
    resultado = reentrenar()
    if not resultado['success']:
        exit(1)
