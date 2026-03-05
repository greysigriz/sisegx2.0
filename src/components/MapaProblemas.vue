<template>
  <div class="map-wrapper">
    <div class="map-header">
      <h2>
        Mapa Interactivo de Reportes - {{ mapScope }}
        <span v-if="isLoadingMunicipios" class="loading-badge">🔄 Cargando municipios...</span>
      </h2>
    </div>

    <div class="map-container">
      <l-map
        ref="map"
        :zoom="8.5"
        :center="[20.7099, -89.0943]"
        :options="mapOptions"
        style="height: 100%; width: 100%;"
        @ready="onMapReady"
      >
        <l-tile-layer
          :url="tileUrl"
          :attribution="attribution"
        />

        <l-control-zoom position="topleft" />
      </l-map>

      <!-- Leyenda -->
      <div class="map-legend-bubble">
        <h4 class="legend-bubble-title">Reportes por Municipio</h4>

        <div class="legend-bubble-section">
          <div class="legend-bubble-item">
            <span class="legend-bubble-dot" style="background-color: #ef4444"></span>
            <span class="legend-bubble-label">Rechazado</span>
          </div>
          <div class="legend-bubble-item">
            <span class="legend-bubble-dot" style="background-color: #10b981"></span>
            <span class="legend-bubble-label">Atendido</span>
          </div>
          <div class="legend-bubble-item">
            <span class="legend-bubble-dot" style="background-color: #f59e0b"></span>
            <span class="legend-bubble-label">Pendiente</span>
          </div>
          <div class="legend-bubble-item">
            <span class="legend-bubble-dot" style="background-color: #3b82f6"></span>
            <span class="legend-bubble-label">En Proceso</span>
          </div>
        </div>

        <div class="legend-bubble-divider"></div>

        <div class="legend-bubble-size">
          <div class="bubble-size-visual">
            <span class="bubble-demo" style="width: 12px; height: 12px; opacity: 0.4"></span>
            <span class="bubble-demo" style="width: 20px; height: 20px; opacity: 0.5"></span>
            <span class="bubble-demo" style="width: 28px; height: 28px; opacity: 0.6"></span>
          </div>
          <span class="bubble-size-label">= Volumen de reportes</span>
        </div>

        <div class="legend-bubble-total">
          Total: <b>{{ totalGeneral }}</b> reportes{{ mapScope === 'Nacional' ? '' : ' en Yucatán' }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { LMap, LTileLayer, LControlZoom } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import '@/assets/css/mapaproblemas_dashboard.css'
import L from 'leaflet'

// Fix de íconos
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})

// Referencias
const map = ref(null)
const isLoadingMunicipios = ref(true)
const mapScope = ref('Yucatán') // 'Yucatán' o 'Nacional'

// Configuración del mapa
const tileUrl = 'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}.png'
const tileUrlLabels = 'https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}.png'
const attribution = '©OpenStreetMap, ©CartoDB'

// API URL
const API_URL = import.meta.env.VITE_API_URL || '/api'

// Colores por intensidad de reportes
const INTENSITY_COLORS = {
  'Crítico': { color: '#dc2626', min: 90 },
  'Alto': { color: '#f97316', min: 61 },
  'Medio': { color: '#f59e0b', min: 31 },
  'Bajo': { color: '#10b981', min: 1 },
  'Sin reportes': { color: '#94a3b8', min: 0 }
}

// Colores por estado (para popup)
const STATUS_COLORS = {
  'Rechazado': '#ef4444',
  'Atendido': '#10b981',
  'Pendiente': '#f59e0b',
  'En Proceso': '#3b82f6'
}

// Datos de municipios con reportes (cargados desde API)
const municipiosData = ref([])
const estadisticasAPI = ref(null)

// Función para cargar datos desde la API
const loadMunicipiosData = async () => {
  try {
    isLoadingMunicipios.value = true
    console.log('🔄 Cargando datos de peticiones desde API...')

    const response = await fetch(`${API_URL}/peticiones-mapa.php`)

    if (!response.ok) {
      throw new Error('Error al cargar datos del servidor')
    }

    const data = await response.json()

    if (data.success && data.municipios) {
      municipiosData.value = data.municipios
      estadisticasAPI.value = data.estadisticas

      console.log(`✅ ${data.municipios.length} municipios cargados desde la base de datos`)
      console.log('📊 Estadísticas:')
      if (data.estadisticas) {
        console.log(`  - Total reportes: ${data.estadisticas.totalReportes}`)
        console.log(`  - Municipios en Yucatán: ${data.estadisticas.municipiosYucatan} (${data.estadisticas.reportesYucatan} reportes)`)
        console.log(`  - Municipios fuera: ${data.estadisticas.municipiosFueraYucatan} (${data.estadisticas.reportesFuera} reportes)`)
      }
    } else {
      throw new Error(data.message || 'No se pudieron cargar los datos')
    }
  } catch (error) {
    console.error('❌ Error cargando datos:', error)
    // Datos de respaldo en caso de error
    municipiosData.value = []
    estadisticasAPI.value = null
  } finally {
    isLoadingMunicipios.value = false
  }
}

// Límites geográficos del estado de Yucatán (INEGI 2020)
const yucatanBounds = [
  [19.551111, -90.407222], // Suroeste (Sur: 19° 33′ 04″, Oeste: 90° 24′ 26″)
  [21.586111, -87.533333]  // Noreste (Norte: 21° 35′ 10″, Este: 87° 32′ 00″)
]

// Función para verificar si un punto está en Yucatán
const isInYucatan = (lat, lng) => {
  return lat >= yucatanBounds[0][0] && lat <= yucatanBounds[1][0] &&
         lng >= yucatanBounds[0][1] && lng <= yucatanBounds[1][1]
}

const mapOptions = {
  zoomControl: false,
  attributionControl: true,
  preferCanvas: true,
  minZoom: 6,
  maxZoom: 18,
  maxBounds: null, // Se ajustará dinámicamente
  maxBoundsViscosity: 1.0
}

const onMapReady = async (mapInstance) => {
  console.log('✅ Mapa cargado')

  // Crear un pane para las etiquetas encima de todo
  const labelsPane = mapInstance.createPane('labels')
  labelsPane.style.zIndex = 650
  labelsPane.style.pointerEvents = 'none'

  // Agregar la capa de etiquetas
  L.tileLayer(tileUrlLabels, {
    attribution: attribution,
    pane: 'labels'
  }).addTo(mapInstance)

  // Cargar datos de municipios desde la API
  await loadMunicipiosData()

  // Verificar que hay datos antes de crear burbujas
  if (municipiosData.value.length === 0) {
    console.warn('⚠️ No hay datos de municipios para mostrar')
    isLoadingMunicipios.value = false
    return
  }

  // Analizar ubicaciones y reportes
  const municipiosEnYucatan = municipiosData.value.filter(m => isInYucatan(m.lat, m.lng))
  const municipiosFueraYucatan = municipiosData.value.filter(m => !isInYucatan(m.lat, m.lng))

  // Usar estadísticas de la API si están disponibles, sino calcular localmente
  const reportesEnYucatan = estadisticasAPI.value?.reportesYucatan ??
                           municipiosEnYucatan.reduce((acc, m) => acc + m.total, 0)
  const ubicacionesFuera = estadisticasAPI.value?.municipiosFueraYucatan ??
                          municipiosFueraYucatan.length

  console.log(`📍 Análisis de ubicaciones:`)
  console.log(`  - Municipios en Yucatán: ${municipiosEnYucatan.length} (${reportesEnYucatan} reportes)`)
  console.log(`  - Municipios fuera de Yucatán: ${ubicacionesFuera}`)

  // Mostrar advertencia si hay municipios fuera de Yucatán
  if (ubicacionesFuera > 0) {
    console.warn(`⚠️ Se encontraron ${ubicacionesFuera} municipios fuera de Yucatán:`)
    municipiosFueraYucatan.forEach(m => {
      console.warn(`  - ${m.municipio} (${m.estado || 'Sin estado'})`)
    })
  }

  // Decidir comportamiento del mapa
  if (reportesEnYucatan > 10 || ubicacionesFuera <= 5) {
    // Centrar en Yucatán
    console.log('🗺️ Centrando mapa en Yucatán')
    mapScope.value = 'Yucatán'
    mapInstance.setMaxBounds(yucatanBounds)
    mapInstance.fitBounds(yucatanBounds)
    mapInstance.setMinZoom(8)
    mapInstance.setMaxZoom(12)
  } else if (ubicacionesFuera > 5) {
    // Mostrar todas las ubicaciones reales
    console.log('🌍 Mostrando todas las ubicaciones (hay más de 5 fuera de Yucatán)')
    mapScope.value = 'Nacional'
    mapInstance.setMaxBounds(null)

    // Calcular bounds que incluyan todas las ubicaciones
    const allCoords = municipiosData.value.map(m => [m.lat, m.lng])
    const bounds = L.latLngBounds(allCoords)
    mapInstance.fitBounds(bounds, {
      padding: [50, 50],
      maxZoom: 10
    })

    mapInstance.setMinZoom(5)
    mapInstance.setMaxZoom(18)
  }

  // Crear burbujas para cada municipio
  const maxTotal = Math.max(...municipiosData.value.map(m => m.total))

  municipiosData.value.forEach(m => {
    // Calcular radio proporcional al total de reportes
    const radius = 10 + (m.total / maxTotal) * 30

    // Determinar color según intensidad de reportes
    let color = '#94a3b8' // Sin reportes
    let intensityLabel = 'Sin reportes'

    if (m.total >= 90) {
      color = INTENSITY_COLORS['Crítico'].color
      intensityLabel = 'Crítico'
    } else if (m.total >= 61) {
      color = INTENSITY_COLORS['Alto'].color
      intensityLabel = 'Alto'
    } else if (m.total >= 31) {
      color = INTENSITY_COLORS['Medio'].color
      intensityLabel = 'Medio'
    } else if (m.total > 0) {
      color = INTENSITY_COLORS['Bajo'].color
      intensityLabel = 'Bajo'
    }

    // Crear círculo (burbuja)
    const circle = L.circleMarker([m.lat, m.lng], {
      radius: radius,
      fillColor: color,
      color: '#1e3a8a',
      weight: 2,
      opacity: 0.9,
      fillOpacity: 0.65
    }).addTo(mapInstance)

    // Tooltip con intensidad
    const municipioNombre = m.municipio
    const estadoInfo = m.estado && m.estado !== 'Yucatán' ? ` (${m.estado})` : ''

    circle.bindTooltip(
      `<b style="color:#1e3a8a">${municipioNombre}${estadoInfo}</b><br/>${m.total} reportes - <span style="color:${color};font-weight:bold">${intensityLabel}</span>`,
      { direction: 'top', offset: [0, -10] }
    )

    // Problemas principales (top 3)
    const topProblemas = Object.entries(m.problemas)
      .sort(([, a], [, b]) => b - a)
      .slice(0, 3)
      .map(([p, c]) => `<p style="font-size:11px;color:#4b5563;margin:2px 0">${p}: <b>${c}</b></p>`)
      .join('')

    // Popup detallado
    const estadoLabel = m.estado && m.estado !== 'Yucatán' ? `<p style="margin:0 0 8px;color:#6b7280;font-size:11px;font-style:italic">${m.estado}</p>` : ''

    const popupContent = `
      <div style="font-family:system-ui,sans-serif;min-width:200px">
        <h3 style="margin:0 0 8px;color:#1e3a8a;font-size:16px;font-weight:700">${m.municipio}</h3>
        ${estadoLabel}
        <p style="margin:0 0 8px;color:#4b5563;font-size:13px">Total de reportes: <b>${m.total}</b></p>
        <div style="display:flex;flex-direction:column;gap:4px">
          ${[
            { label: 'Rechazado', count: m.rechazado, color: STATUS_COLORS['Rechazado'] },
            { label: 'Atendido', count: m.atendido, color: STATUS_COLORS['Atendido'] },
            { label: 'Pendiente', count: m.pendiente, color: STATUS_COLORS['Pendiente'] },
            { label: 'En Proceso', count: m.enProceso, color: STATUS_COLORS['En Proceso'] }
          ]
            .map(s => `
              <div style="display:flex;align-items:center;gap:6px">
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${s.color}"></span>
                <span style="font-size:12px;color:#4b5563">${s.label}: <b style="color:#1e3a8a">${s.count}</b></span>
              </div>
            `)
            .join('')}
        </div>
        ${topProblemas ? `
          <div style="margin-top:8px;padding-top:8px;border-top:1px solid #e5e7eb">
            <p style="font-size:11px;color:#6b7280;margin:0 0 4px;font-weight:600">Problemas principales:</p>
            ${topProblemas}
          </div>
        ` : ''}
      </div>
    `

    circle.bindPopup(popupContent)
  })

  console.log('✅ Burbujas agregadas al mapa')
  isLoadingMunicipios.value = false
}

const totalGeneral = computed(() => {
  return municipiosData.value.reduce((acc, m) => acc + m.total, 0)
})

onMounted(async () => {
  console.log('✅ Componente montado')
})
</script>
