<template>
  <div class="map-wrapper">
    <h2>🗺️ Mapa de Problemas Reportados en Cancún</h2>
    <div class="map-container">
      <l-map
        :zoom="13"
        :center="[21.1619, -86.8515]"
        style="height: 100%; width: 100%"
      >
        <l-tile-layer
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          attribution="&copy; OpenStreetMap contributors"
        />
        <l-marker
          v-for="(punto, index) in puntos"
          :key="index"
          :lat-lng="[punto.lat, punto.lng]"
        >
          <l-popup>
            <strong>{{ punto.mz }}</strong><br />
            🛠️ Problema: <b>{{ punto.problema }}</b><br />
            📈 Reportes: {{ punto.cantidad }}
          </l-popup>
        </l-marker>
      </l-map>
    </div>
  </div>
</template>

<script setup>
import { LMap, LTileLayer, LMarker, LPopup } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'

// 🔥 Fix de los íconos de Leaflet (para que sí se vean los marcadores)
import L from 'leaflet'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})

const puntos = [
  { mz: 'MZ 32', lat: 21.1619, lng: -86.8515, problema: 'Baches', cantidad: 12 },
  { mz: 'MZ 45', lat: 21.1505, lng: -86.8452, problema: 'Basura', cantidad: 7 },
  { mz: 'MZ 12', lat: 21.1571, lng: -86.857, problema: 'Robos', cantidad: 15 },
  { mz: 'MZ 90', lat: 21.1702, lng: -86.842, problema: 'Falta de Agua', cantidad: 9 },
  { mz: 'MZ 24', lat: 21.1653, lng: -86.8533, problema: 'Alumbrado Público', cantidad: 6 },
  { mz: 'MZ 10', lat: 21.1558, lng: -86.8479, problema: 'Incendios', cantidad: 3 },
  { mz: 'MZ 65', lat: 21.1499, lng: -86.8588, problema: 'Ruido', cantidad: 8 },
  { mz: 'MZ 76', lat: 21.16, lng: -86.8432, problema: 'Vandalismo', cantidad: 4 },
  { mz: 'MZ 58', lat: 21.1699, lng: -86.8566, problema: 'Obras sin terminar', cantidad: 11 },
  { mz: 'MZ 81', lat: 21.1644, lng: -86.8494, problema: 'Árboles Caídos', cantidad: 5 }
]
</script>

<style scoped>
.map-wrapper {
  max-width: 1000px;
  margin: 2rem auto;
  padding: 32px;
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04),
    0 0 0 1px rgba(59, 130, 246, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.6);
  position: relative;
  overflow: hidden;
}

.map-wrapper::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%);
  border-radius: 20px 20px 0 0;
}

.map-wrapper h2 {
  text-align: center;
  font-size: 24px;
  font-weight: 700;
  color: #1E40AF;
  margin: 0 0 24px 0;
  font-family: "Inter", "Segoe UI", sans-serif;
  letter-spacing: -0.025em;
  padding-bottom: 16px;
  border-bottom: 2px solid #E5E7EB;
}

.map-container {
  width: 100%;
  height: 500px;
  border-radius: 16px;
  overflow: hidden;
  box-shadow:
    0 10px 15px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05),
    inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
  margin-top: 24px;
  border: 2px solid rgba(59, 130, 246, 0.1);
  position: relative;
}

.map-container::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border-radius: 14px;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
  pointer-events: none;
  z-index: 1000;
}

/* Estilos para los popups de Leaflet */
:deep(.leaflet-popup-content-wrapper) {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  border: 1px solid #E5E7EB;
  font-family: "Inter", "Segoe UI", sans-serif;
}

:deep(.leaflet-popup-content) {
  margin: 16px 20px;
  font-size: 14px;
  line-height: 1.6;
  color: #374151;
}

:deep(.leaflet-popup-content strong) {
  color: #1E40AF;
  font-weight: 600;
  font-size: 15px;
}

:deep(.leaflet-popup-content b) {
  color: #1F2937;
  font-weight: 600;
}

:deep(.leaflet-popup-tip) {
  background: #ffffff;
  border: 1px solid #E5E7EB;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

:deep(.leaflet-popup-close-button) {
  color: #6B7280;
  font-size: 18px;
  font-weight: bold;
  padding: 8px;
  border-radius: 50%;
  transition: all 0.2s ease;
}

:deep(.leaflet-popup-close-button:hover) {
  color: #1E40AF;
  background-color: #F3F4F6;
}

/* Estilos para los controles de zoom */
:deep(.leaflet-control-zoom) {
  border: none;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

:deep(.leaflet-control-zoom a) {
  background-color: #ffffff;
  border: 1px solid #E5E7EB;
  color: #374151;
  font-family: "Inter", "Segoe UI", sans-serif;
  font-weight: 600;
  transition: all 0.2s ease;
}

:deep(.leaflet-control-zoom a:hover) {
  background-color: #3B82F6;
  color: #ffffff;
  border-color: #3B82F6;
}

/* Estilos para la atribución */
:deep(.leaflet-control-attribution) {
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 8px;
  font-family: "Inter", "Segoe UI", sans-serif;
  font-size: 11px;
  color: #6B7280;
  backdrop-filter: blur(4px);
  border: 1px solid rgba(229, 231, 235, 0.8);
}

:deep(.leaflet-control-attribution a) {
  color: #3B82F6;
  text-decoration: none;
  font-weight: 500;
}

:deep(.leaflet-control-attribution a:hover) {
  color: #1E40AF;
  text-decoration: underline;
}

@media (max-width: 768px) {
  .map-wrapper {
    padding: 20px;
    margin: 16px;
  }

  .map-wrapper h2 {
    font-size: 20px;
  }

  .map-container {
    height: 400px;
  }
}
</style>
