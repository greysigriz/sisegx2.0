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
