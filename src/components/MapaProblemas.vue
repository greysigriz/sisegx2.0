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

      <!-- Leyenda de intensidad -->
      <div class="map-legend-choropleth">
        <h4 class="legend-choropleth-title">Reportes por Municipio</h4>

        <div class="legend-status-colors">
          <div class="legend-status-item">
            <span class="legend-status-dot" style="background-color: #ef4444;"></span>
            <span class="legend-status-text">Nivel 1 - Crítico</span>
          </div>
          <div class="legend-status-item">
            <span class="legend-status-dot" style="background-color: #f59e0b;"></span>
            <span class="legend-status-text">Nivel 2 - Alto</span>
          </div>
          <div class="legend-status-item">
            <span class="legend-status-dot" style="background-color: #0074D9;"></span>
            <span class="legend-status-text">Nivel 3 - Medio</span>
          </div>
          <div class="legend-status-item">
            <span class="legend-status-dot" style="background-color: #10b981;"></span>
            <span class="legend-status-text">Nivel 4 - Bajo</span>
          </div>
          <div class="legend-status-item">
            <span class="legend-status-dot" style="background-color: #94a3b8;"></span>
            <span class="legend-status-text">Nivel 5 - Muy Bajo</span>
          </div>
        </div>

        <div class="legend-choropleth-divider"></div>

        <div class="legend-bubble-size">
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <div class="bubble-demo" style="width: 20px; height: 20px; background: #10b981; border-radius: 50%; opacity: 0.6;"></div>
            <div class="bubble-demo" style="width: 28px; height: 28px; background: #0074D9; border-radius: 50%; opacity: 0.6;"></div>
            <div class="bubble-demo" style="width: 36px; height: 36px; background: #ef4444; border-radius: 50%; opacity: 0.6;"></div>
            <span style="font-size: 11px; color: #475569; margin-left: 4px;">= Volumen de reportes</span>
          </div>
        </div>

        <div class="legend-choropleth-divider"></div>

        <div class="legend-choropleth-total">
          <div class="total-label">Total:</div>
          <div class="total-value">{{ totalGeneral.toLocaleString() }}</div>
          <div class="total-subtitle">reportes en Yucatán</div>
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
const mapScope = ref('Yucatán')

// Configuración del mapa
const tileUrl = 'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}.png'
const tileUrlLabels = 'https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}.png'
const attribution = '©OpenStreetMap, ©CartoDB'

// API URL
const API_URL = import.meta.env.VITE_API_URL || '/api'

// Colores por intensidad de reportes
const CHOROPLETH_COLORS = {
  'Bajo': '#cbd5e1',
  'Medio': '#cbd5e1',
  'Alto': '#cbd5e1',
  'Muy Alto': '#cbd5e1',
  'Sin datos': '#e2e8f0'
}

// Colores por estado (para popup)
const STATUS_COLORS = {
  'Rechazado': '#ef4444',
  'Atendido': '#10b981',
  'Pendiente': '#f59e0b',
  'En Proceso': '#3b82f6'
}

// Colores por nivel de importancia (para burbujas)
const NIVEL_COLORS = {
  1: '#ef4444',  // Crítico - Rojo
  2: '#f59e0b',  // Alto - Naranja
  3: '#0074D9',  // Medio - Azul
  4: '#10b981',  // Bajo - Verde
  5: '#94a3b8'   // Muy Bajo - Gris
}

const NIVEL_BORDER_COLORS = {
  1: '#dc2626',
  2: '#d97706',
  3: '#0056a6',
  4: '#059669',
  5: '#64748b'
}

// Datos de municipios con reportes
const municipiosData = ref([])
const estadisticasAPI = ref(null)
const rangosMedios = ref([0, 0, 0])

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

    // ============================================================
    // 🔍 DIAGNÓSTICO: Ver estructura completa del primer municipio
    // ============================================================
    if (data.municipios && data.municipios.length > 0) {
      console.log('🔍 DIAGNÓSTICO - Estructura del primer municipio:')
      console.log(JSON.stringify(data.municipios[0], null, 2))
      console.log('🔍 ¿Tiene estados?', !!data.municipios[0].estados)
      console.log('🔍 ¿Tiene problemas?', !!data.municipios[0].problemas)
    }
    // ============================================================

    if (data.success && data.municipios) {
      municipiosData.value = data.municipios
      estadisticasAPI.value = data.estadisticas
      console.log(`✅ ${data.municipios.length} municipios cargados desde la base de datos`)
    } else {
      throw new Error(data.message || 'No se pudieron cargar los datos')
    }
  } catch (error) {
    console.error('❌ Error cargando datos:', error)
    municipiosData.value = []
    estadisticasAPI.value = null
  }
}

// Función para normalizar texto
const normalize = (text) => {
  if (!text) return ''
  return text
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .trim()
}

// Función reutilizable para generar el HTML del popup
const buildPopupHTML = (nombre, total, estados, problemas) => {
  let html = `
    <div style="padding: 16px; min-width: 280px; font-family: system-ui, sans-serif;">
      <h3 style="margin: 0 0 4px 0; color: #1f2937; font-size: 20px; font-weight: 700;">
        ${nombre}
      </h3>
      <div style="margin-bottom: 12px; color: #374151; font-size: 14px;">
        Total de reportes: <strong>${total}</strong>
      </div>
  `

  if (estados && Object.keys(estados).length > 0) {
    Object.entries(estados).forEach(([estado, count]) => {
      const color = STATUS_COLORS[estado] || '#6b7280'
      html += `
        <div style="display: flex; align-items: center; margin: 5px 0; font-size: 14px;">
          <span style="width: 12px; height: 12px; background: ${color}; border-radius: 50%; margin-right: 8px; flex-shrink:0;"></span>
          <span style="color: #374151;">${estado}: <strong>${count}</strong></span>
        </div>
      `
    })
  }

  if (problemas && problemas.length > 0) {
    html += `
      <div style="border-top: 1px solid #e5e7eb; margin-top: 12px; padding-top: 10px;">
        <strong style="color: #374151; font-size: 13px;">Problemas principales:</strong>
        <div style="margin-top: 6px;">
    `
    problemas.forEach(problema => {
      html += `
        <div style="color: #374151; font-size: 13px; margin: 4px 0;">
          ${problema.tipo}: <strong>${problema.cantidad}</strong>
        </div>
      `
    })
    html += `</div></div>`
  }

  html += `</div>`
  return html
}

const mapOptions = {
  zoomControl: false,
  attributionControl: true,
  preferCanvas: false,
  minZoom: 6,
  maxZoom: 18,
  renderer: L.svg()
}

const onMapReady = async (mapInstance) => {
  console.log('✅ Mapa cargado')
  console.log('🚀 INICIANDO CARGA DE DATOS DEL MAPA...')

  await loadMunicipiosData()

  if (municipiosData.value.length === 0) {
    console.warn('⚠️ No hay datos de municipios para mostrar')
    isLoadingMunicipios.value = false
    return
  }

  mapScope.value = 'Yucatán'
  mapInstance.setView([20.7, -89.0], 8.5)

  const totales = municipiosData.value.map(m => m.total).filter(t => t > 0).sort((a, b) => a - b)
  const maxTotal = Math.max(...totales)

  const rango1 = Math.floor(maxTotal * 0.25)
  const rango2 = Math.floor(maxTotal * 0.50)
  const rango3 = Math.floor(maxTotal * 0.75)
  rangosMedios.value = [rango1, rango2, rango3]

  const getColorForTotal = (total) => {
    if (total === 0) return { color: CHOROPLETH_COLORS['Sin datos'], label: 'Sin datos' }
    if (total <= rango1) return { color: CHOROPLETH_COLORS['Bajo'], label: 'Bajo' }
    if (total <= rango2) return { color: CHOROPLETH_COLORS['Medio'], label: 'Medio' }
    if (total <= rango3) return { color: CHOROPLETH_COLORS['Alto'], label: 'Alto' }
    return { color: CHOROPLETH_COLORS['Muy Alto'], label: 'Muy Alto' }
  }

  const municipioDataMap = {}
  const municipioCoordinates = {}

  municipiosData.value.forEach(m => {
    const nombreNormalizado = normalize(m.municipio)
    municipioDataMap[nombreNormalizado] = m
  })

  console.log('🗺️ Cargando GeoJSON...')

  let geojson
  try {
    const response = await fetch('../../municipios-yucatan.geojson')
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    geojson = await response.json()
    console.log('✅ GeoJSON cargado:', geojson.features?.length, 'features')
  } catch (error) {
    console.error('❌ Error cargando GeoJSON:', error)
    isLoadingMunicipios.value = false
    return
  }

  if (!geojson || !geojson.features || geojson.features.length === 0) {
    console.error('❌ GeoJSON inválido')
    isLoadingMunicipios.value = false
    return
  }

  const geojsonLayer = L.geoJSON(geojson, {
    style: (feature) => {
      const nombreMunicipio = normalize(feature.properties.NOMGEO)
      const datos = municipioDataMap[nombreMunicipio]
      const total = datos ? datos.total : 0
      const colorData = getColorForTotal(total)

      return {
        fillColor: colorData.color,
        fillOpacity: 0.40,
        color: '#000000',
        weight: 0.5,
        opacity: 0.5
      }
    },

    onEachFeature: (feature, layer) => {
      const nombreMunicipio = normalize(feature.properties.NOMGEO)
      const datos = municipioDataMap[nombreMunicipio]

      if (layer.getBounds) {
        const center = layer.getBounds().getCenter()
        municipioCoordinates[nombreMunicipio] = center
      }

      if (datos) {
        // Si tiene datos, mantener clic pero sin hover effects
        const popupContent = buildPopupHTML(
          feature.properties.NOMGEO,
          datos.total,
          datos.estados,
          datos.problemas
        )

        layer.bindPopup(popupContent, { maxWidth: 320, className: 'custom-popup' })
      } else {
        // Municipios sin reportes - mantener interactividad y efectos hover
        layer.on({
          mouseover: (e) => {
            e.target.setStyle({ weight: 4, color: '#000000', fillOpacity: 0.95 })
            e.target.bringToFront()
          },
          mouseout: (e) => {
            geojsonLayer.resetStyle(e.target)
          }
        })

        layer.bindPopup(`<div style="padding: 12px;"><strong>${feature.properties.NOMGEO}</strong><br>Sin reportes</div>`, { className: 'custom-popup' })
      }
    }
  })

  geojsonLayer.addTo(mapInstance)
  console.log('✅ Capa GeoJSON agregada al mapa')

  // Crear pane para burbujas con z-index alto
  const bubblesPane = mapInstance.createPane('bubbles')
  bubblesPane.style.zIndex = 600
  bubblesPane.style.pointerEvents = 'auto'

  console.log('🔵 Agregando burbujas al mapa...')

  municipiosData.value.forEach(municipio => {
    const nombreNormalizado = normalize(municipio.municipio)
    const coords = municipioCoordinates[nombreNormalizado]

    if (municipio.total > 0 && coords) {
      const baseRadius = 6
      const scaleFactor = 1.5
      const maxRadius = 30
      const radius = Math.min(baseRadius + (municipio.total * scaleFactor), maxRadius)

      // Obtener color según nivel predominante
      const nivelPredominante = municipio.nivel_predominante || 3
      const bubbleColor = NIVEL_COLORS[nivelPredominante] || NIVEL_COLORS[3]
      const bubbleBorderColor = NIVEL_BORDER_COLORS[nivelPredominante] || NIVEL_BORDER_COLORS[3]

      const circle = L.circleMarker([coords.lat, coords.lng], {
        radius: radius,
        fillColor: bubbleColor,
        color: bubbleBorderColor,
        weight: 2,
        opacity: 0.8,
        fillOpacity: 0.6,
        pane: 'bubbles'
      })

      const bubblePopup = buildPopupHTML(
        municipio.municipio,
        municipio.total,
        municipio.estados,
        municipio.problemas
      )

      circle.bindPopup(bubblePopup, { maxWidth: 320, className: 'custom-popup' })

      circle.addTo(mapInstance)
    }
  })

  console.log('✅ Burbujas agregadas al mapa')

  const labelsPane = mapInstance.createPane('labels')
  labelsPane.style.zIndex = 650
  labelsPane.style.pointerEvents = 'none'

  L.tileLayer(tileUrlLabels, {
    attribution: attribution,
    pane: 'labels'
  }).addTo(mapInstance)

  isLoadingMunicipios.value = false
}

const totalGeneral = computed(() => {
  return municipiosData.value.reduce((acc, m) => acc + m.total, 0)
})

onMounted(() => {
  console.log('✅ Componente montado')
})
</script>
