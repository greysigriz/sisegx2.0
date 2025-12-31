import joblib

# Cargar modelo y vectorizador entrenados
clf = joblib.load("backend/clasificador_dependencias.joblib")
vectorizer = joblib.load("backend/vectorizador_dependencias.joblib")

def clasificar_peticion(texto):
    X = vectorizer.transform([texto])
    pred = clf.predict(X)[0]
    probas = clf.predict_proba(X)[0]
    # Obtener las 3 dependencias con mayor probabilidad
    top_indices = probas.argsort()[-3:][::-1]
    top_dependencias = [(clf.classes_[i], round(probas[i]*100, 2)) for i in top_indices]
    return {
        "dependencia_predicha": pred,
        "top_dependencias": top_dependencias
    }

if __name__ == "__main__":
    texto = input("Escribe la petición a clasificar: ")
    resultado = clasificar_peticion(texto)
    print("Dependencia sugerida:", resultado["dependencia_predicha"])
    print("Top 3 dependencias y confianza:")
    for dep, conf in resultado["top_dependencias"]:
        print(f"- {dep}: {conf}%")
