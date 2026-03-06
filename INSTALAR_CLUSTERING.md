# Instalación de Clustering para el Mapa

## 📦 Paquete Requerido

Para que funcione el clustering en el mapa, necesitas instalar el paquete `leaflet.markercluster`.

## 🚀 Instalación

Ejecuta el siguiente comando en la terminal desde la raíz del proyecto:

```bash
npm install leaflet.markercluster
```

## ✅ Verificación

Después de la instalación, reinicia el servidor de desarrollo:

```bash
npm run dev
```

## 🎯 Características del Clustering

- **Agrupación automática**: Los marcadores cercanos se agrupan en clusters
- **Colores por estado**: Los clusters muestran el color del estado predominante
- **Información del cluster**: Muestra el número de municipios y total de reportes
- **Tamaños dinámicos**: Los clusters crecen según la cantidad de marcadores
- **Animaciones suaves**: Transiciones y efectos hover elegantes
- **Spiderfy**: Al hacer zoom máximo o click, los marcadores se expanden en forma de araña

## 🎨 Personalización

Los clusters están configurados con:
- Radio máximo de agrupación: 60px
- Tamaños: pequeño (40px), mediano (50px), grande (60px)
- Colores basados en el estado predominante de los reportes agrupados
- Animación de pulso continuo

## 📊 Información en Clusters

Cada cluster muestra:
- **Número grande**: Cantidad de municipios agrupados
- **Número pequeño**: Total de reportes sumados
- **Color**: Estado predominante (Rechazado, Atendido, Pendiente, En Proceso)
