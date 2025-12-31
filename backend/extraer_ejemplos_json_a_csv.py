import json
import csv

# Rutas de los archivos
json_path = "backend/dependecias_yucatan.json"
csv_path = "backend/ejemplos_dependencias.csv"

with open(json_path, encoding="utf-8") as f:
    data = json.load(f)

catalogo = data["sistema_clasificacion_dependencias"]["catalogo_dependencias"]

rows = []
for dep_id, dep in catalogo.items():
    nombre_dependencia = dep.get("nombre", dep_id)
    servicios = dep.get("servicios_comunes", [])
    for servicio in servicios:
        tramite = servicio.get("tramite", "")
        ejemplos = servicio.get("ejemplos_peticion", [])
        for ejemplo in ejemplos:
            # Etiqueta: solo el nombre de la dependencia
            etiqueta = nombre_dependencia
            rows.append({"texto": ejemplo, "dependencia": etiqueta})

# Escribir CSV
with open(csv_path, "w", encoding="utf-8", newline='') as f:
    writer = csv.DictWriter(f, fieldnames=["texto", "dependencia"])
    writer.writeheader()
    writer.writerows(rows)

print(f"Se extrajeron {len(rows)} ejemplos a {csv_path}")
