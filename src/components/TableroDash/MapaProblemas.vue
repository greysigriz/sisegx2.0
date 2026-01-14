<template>
  <Card class="map-card-wrapper">
    <h3 class="map-card-title">Mapa Interactivo de Reportes</h3>

    <div v-if="isLoading" class="map-loading">
      <p class="map-loading-text">Cargando mapa...</p>
    </div>

    <div v-show="!isLoading" class="map-container">
      <!-- Leyenda de distribución -->
      <div class="map-legend">
        <h3 class="legend-title">Distribución de Reportes</h3>
        <div class="legend-grid">
          <div
            v-for="(count, status) in statusCounts"
            :key="status"
            class="legend-item"
          >
            <div
              class="legend-dot"
              :style="{ backgroundColor: statusColors[status] }"
            />
            <div class="legend-text">
              <p class="legend-status">{{ status }}</p>
              <p class="legend-count">{{ count }}</p>
            </div>
          </div>
        </div>
        <p class="legend-total">Total de {{ reports.length }} reportes en Yucatán</p>
      </div>

      <!-- Mapa -->
      <div ref="mapRef" class="map-canvas"></div>
    </div>
  </Card>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import '@/assets/css/mapaproblemas_dashboard.css'

const props = defineProps({
  reports: {
    type: Array,
    default: () => [
      { id: 64, municipality: 'Valladolid', category: 'Baches', status: 'Rechazado', date: '2026-01-14', coordinates: { lat: 20.6896, lng: -88.2011 } },
      { id: 116, municipality: 'Kanasín', category: 'Limpieza', status: 'Atendido', date: '2026-01-13', coordinates: { lat: 20.9431, lng: -89.5411 } },
      { id: 63, municipality: 'Tekax', category: 'Limpieza', status: 'Pendiente', date: '2026-01-12', coordinates: { lat: 20.2028, lng: -89.2833 } },
      { id: 71, municipality: 'Mérida', category: 'Alumbrado', status: 'En Proceso', date: '2026-01-12', coordinates: { lat: 20.9674, lng: -89.6243 } },
      { id: 105, municipality: 'Tizimín', category: 'Alumbrado', status: 'Pendiente', date: '2026-01-12', coordinates: { lat: 21.1444, lng: -88.1678 } },
      { id: 126, municipality: 'Tixkokob', category: 'Limpieza', status: 'Atendido', date: '2026-01-12', coordinates: { lat: 21.0167, lng: -89.4167 } },
      { id: 129, municipality: 'Progreso', category: 'Baches', status: 'Rechazado', date: '2026-01-12', coordinates: { lat: 21.2817, lng: -89.6650 } },
      { id: 52, municipality: 'Mérida', category: 'Alumbrado', status: 'Rechazado', date: '2026-01-11', coordinates: { lat: 20.9737, lng: -89.6186 } },
      { id: 155, municipality: 'Umán', category: 'Limpieza', status: 'Atendido', date: '2026-01-11', coordinates: { lat: 20.8833, lng: -89.7500 } },
      { id: 62, municipality: 'Ticul', category: 'Seguridad', status: 'Atendido', date: '2026-01-09', coordinates: { lat: 20.3978, lng: -89.5378 } }
    ]
  }
})

const mapRef = ref(null)
const mapInstanceRef = ref(null)
const isLoading = ref(true)

const statusColors = {
  'Pendiente': '#93c5fd',
  'En Proceso': '#3b82f6',
  'Atendido': '#1e40af',
  'Rechazado': '#1e3a8a'
}

const statusCounts = computed(() => {
  return props.reports.reduce((acc, report) => {
    acc[report.status] = (acc[report.status] || 0) + 1
    return acc
  }, {})
})

const loadMap = async () => {
  if (!mapRef.value || mapInstanceRef.value) return

  try {
    const L = (await import('leaflet')).default
    await import('leaflet/dist/leaflet.css')

    // Fix para íconos por defecto en Leaflet
    delete L.Icon.Default.prototype._getIconUrl
    L.Icon.Default.mergeOptions({
      iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
      iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
      shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png'
    })

    // Ocultar loading inmediatamente antes de crear el mapa
    isLoading.value = false

    // Centro de Yucatán con zoom óptimo para ver todo el estado
    const map = L.map(mapRef.value, {
      center: [20.7099, -89.0943],
      zoom: 8,
      zoomControl: true,
      preferCanvas: true // Mejora el rendimiento
    })
    mapInstanceRef.value = map

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19,
      minZoom: 7
    }).addTo(map)

    const createCustomIcon = (status) => {
      return L.divIcon({
        className: 'custom-marker',
        html: `<div style="background-color: ${statusColors[status] || '#3b82f6'}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12]
      })
    }

    props.reports.forEach((report) => {
      const marker = L.marker([report.coordinates.lat, report.coordinates.lng], {
        icon: createCustomIcon(report.status)
      }).addTo(map)

      marker.bindPopup(`
        <div style="padding: 8px;">
          <div style="background-color: #1d4ed8; color: white; padding: 8px 12px; margin: -8px -8px 8px -8px; font-weight: 600;">
            Reporte #${report.id}
          </div>
          <div style="margin-top: 8px; font-size: 14px;">
            <p><strong>Municipio:</strong> ${report.municipality}</p>
            <p><strong>Categoría:</strong> ${report.category}</p>
            <p><strong>Estado:</strong> ${report.status}</p>
            <p><strong>Fecha:</strong> ${report.date}</p>
          </div>
        </div>
      `)
    })

    // Ajustar vista para mostrar todos los marcadores de Yucatán
    if (props.reports.length > 0) {
      const bounds = L.latLngBounds(props.reports.map(r => [r.coordinates.lat, r.coordinates.lng]))
      map.fitBounds(bounds, { padding: [50, 50], maxZoom: 9 })
    }
  } catch (error) {
    console.error('Error loading map:', error)
    isLoading.value = false
  }
}

onMounted(() => {
  loadMap()
})

onUnmounted(() => {
  if (mapInstanceRef.value) {
    mapInstanceRef.value.remove()
    mapInstanceRef.value = null
  }
})
</script>
