<template>
  <div class="map-wrapper">
    <h2>🗺️ Mapa de Problemas Reportados en Yucatán</h2>
    <div class="map-container">
      <l-map
        :zoom="9"
        :center="[20.9674, -89.5926]"
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

// Puntos de varios municipios de Yucatán
const puntos = [
  // Mérida
  { mz: 'Centro', lat: 20.9674, lng: -89.5926, problema: 'Baches', cantidad: 45 },
  { mz: 'Norte', lat: 21.0206, lng: -89.6137, problema: 'Alumbrado Público', cantidad: 28 },
  { mz: 'Sur', lat: 20.9342, lng: -89.6173, problema: 'Basura', cantidad: 32 },
  // Progreso
  { mz: 'Progreso', lat: 21.2817, lng: -89.6650, problema: 'Falta de Agua', cantidad: 18 },
  { mz: 'Progreso Centro', lat: 21.2794, lng: -89.6588, problema: 'Obras sin terminar', cantidad: 12 },
  // Valladolid
  { mz: 'Valladolid', lat: 20.6896, lng: -88.2018, problema: 'Baches', cantidad: 22 },
  // Tizimín
  { mz: 'Tizimín', lat: 21.1450, lng: -88.1653, problema: 'Alumbrado Público', cantidad: 15 },
  // Ticul
  { mz: 'Ticul', lat: 20.3992, lng: -89.5342, problema: 'Árboles Caídos', cantidad: 9 },
  // Kanasín
  { mz: 'Kanasín', lat: 20.9337, lng: -89.5533, problema: 'Vandalismo', cantidad: 17 },
  // Umán
  { mz: 'Umán', lat: 20.8867, lng: -89.7517, problema: 'Ruido', cantidad: 11 },
  // Motul
  { mz: 'Motul', lat: 21.0949, lng: -89.2881, problema: 'Basura', cantidad: 14 },
  // Izamal
  { mz: 'Izamal', lat: 20.9306, lng: -89.0172, problema: 'Baches', cantidad: 8 }
]

</script>
