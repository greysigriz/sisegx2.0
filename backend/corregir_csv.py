import csv
import re

# Leer el archivo con manejo manual de líneas
with open('backend/ejemplos_dependencias.csv', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Procesar cada línea
corrected_lines = []
for i, line in enumerate(lines):
    # Si es el header, dejarlo como está
    if i == 0:
        corrected_lines.append(line)
        continue
    
    # Limpiar la línea
    line = line.strip()
    
    if not line:
        continue
    
    # Si la línea contiene "Secretaría de Ciencia, Humanidades"
    # y la dependencia NO está entre comillas, corregirla
    if 'Secretaría de Ciencia, Humanidades' in line:
        # Verificar si la dependencia ya está entre comillas
        if line.count('"') == 2:
            # Solo el texto está entre comillas, la dependencia NO
            # Formato: "texto",Secretaría de Ciencia, Humanidades...
            parts = line.split('",', 1)
            if len(parts) == 2:
                texto = parts[0] + '"'
                dependencia = parts[1].strip()
                corrected_lines.append(f'{texto},"{dependencia}"\n')
            else:
                corrected_lines.append(line + '\n')
        else:
            # Ya está correctamente formateada
            corrected_lines.append(line + '\n')
    else:
        # Línea normal, dejarla como está
        corrected_lines.append(line + '\n')

# Escribir el archivo corregido
with open('backend/ejemplos_dependencias.csv', 'w', encoding='utf-8', newline='') as f:
    f.writelines(corrected_lines)

print(f"✅ Archivo corregido. Total de líneas: {len(corrected_lines)}")
