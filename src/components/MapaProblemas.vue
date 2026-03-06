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
            <span class="legend-status-text">Rechazado</span>
          </div>
          <div class="legend-status-item">
            <span class="legend-status-dot" style="background-color: #3b82f6;"></span>
            <span class="legend-status-text">Atendido</span>
          </div>
          <div class="legend-status-item">
            <span class="legend-status-dot" style="background-color: #cbd5e1;"></span>
            <span class="legend-status-text">Pendiente</span>
          </div>
          <div class="legend-status-item">
            <span class="legend-status-dot" style="background-color: #3b82f6;"></span>
            <span class="legend-status-text">En Proceso</span>
          </div>
        </div>

        <div class="legend-choropleth-divider"></div>

        <div class="legend-bubble-size">
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <div class="bubble-demo" style="width: 20px; height: 20px; background: #3b82f6; border-radius: 50%; opacity: 0.6;"></div>
            <div class="bubble-demo" style="width: 28px; height: 28px; background: #3b82f6; border-radius: 50%; opacity: 0.6;"></div>
            <div class="bubble-demo" style="width: 36px; height: 36px; background: #3b82f6; border-radius: 50%; opacity: 0.6;"></div>
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

const mapOptions = {
  zoomControl: false,
  attributionControl: true,
  preferCanvas: false, // CAMBIADO A FALSE - importante para SVG
  minZoom: 6,
  maxZoom: 18,
  renderer: L.svg() // FORZAR renderer SVG
}

const onMapReady = async (mapInstance) => {
  console.log('✅ Mapa cargado')

  // Cargar datos de municipios desde la API
  await loadMunicipiosData()

  // Verificar que hay datos
  if (municipiosData.value.length === 0) {
    console.warn('⚠️ No hay datos de municipios para mostrar')
    isLoadingMunicipios.value = false
    return
  }

  // Centrar en Yucatán
  mapScope.value = 'Yucatán'
  mapInstance.setView([20.7, -89.0], 8.5)

  // Calcular rangos de intensidad
  const totales = municipiosData.value.map(m => m.total).filter(t => t > 0).sort((a, b) => a - b)
  const maxTotal = Math.max(...totales)

  const rango1 = Math.floor(maxTotal * 0.25)
  const rango2 = Math.floor(maxTotal * 0.50)
  const rango3 = Math.floor(maxTotal * 0.75)
  rangosMedios.value = [rango1, rango2, rango3]

  console.log(`📊 Rangos: Bajo (0-${rango1}), Medio (${rango1+1}-${rango2}), Alto (${rango2+1}-${rango3}), Muy Alto (${rango3+1}+)`)

  // Función para obtener color según total de reportes
  const getColorForTotal = (total) => {
    if (total === 0) return { color: CHOROPLETH_COLORS['Sin datos'], label: 'Sin datos' }
    if (total <= rango1) return { color: CHOROPLETH_COLORS['Bajo'], label: 'Bajo' }
    if (total <= rango2) return { color: CHOROPLETH_COLORS['Medio'], label: 'Medio' }
    if (total <= rango3) return { color: CHOROPLETH_COLORS['Alto'], label: 'Alto' }
    return { color: CHOROPLETH_COLORS['Muy Alto'], label: 'Muy Alto' }
  }

  // Crear mapa de datos
  const municipioDataMap = {}
  const municipioCoordinates = {} // Para guardar las coordenadas de cada municipio

  municipiosData.value.forEach(m => {
    const nombreNormalizado = normalize(m.municipio)
    municipioDataMap[nombreNormalizado] = m
  })

  // Cargar GeoJSON
  console.log('🗺️ Cargando GeoJSON...')

  let geojson
  try {
    const response = await fetch('../../municipios-yucatan.geojson')
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    geojson = await response.json()
    console.log('✅ GeoJSON cargado:', geojson.features?.length, 'features')

    // DEBUG: Ver primer feature
    if (geojson.features && geojson.features[0]) {
      console.log('🔍 Primer feature:', geojson.features[0])
      console.log('🔍 Geometría:', geojson.features[0].geometry?.type)
    }
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

  // Crear capa GeoJSON
  console.log('🎨 Aplicando estilos a polígonos...')

  const geojsonLayer = L.geoJSON(geojson, {
    style: (feature) => {
      const nombreMunicipio = normalize(feature.properties.NOMGEO)
      const datos = municipioDataMap[nombreMunicipio]
      const total = datos ? datos.total : 0
      const colorData = getColorForTotal(total)

      const estilo = {
        fillColor: colorData.color,
        fillOpacity: 0.40,
        color: '#000000',
        weight: 0.5,
        opacity: 0.5
      }

      console.log(`🎨 ${feature.properties.NOMGEO}: ${colorData.color} (${total} reportes)`)

      return estilo
    },

    onEachFeature: (feature, layer) => {
      const nombreMunicipio = normalize(feature.properties.NOMGEO)
      const datos = municipioDataMap[nombreMunicipio]

      // Guardar el centroide del polígono
      if (layer.getBounds) {
        const center = layer.getBounds().getCenter()
        municipioCoordinates[nombreMunicipio] = center
      }

      // Hover
      layer.on({
        mouseover: (e) => {
          e.target.setStyle({
            weight: 4,
            color: '#4a0e2e',
            fillOpacity: 0.95
          })
          e.target.bringToFront()
        },
        mouseout: (e) => {
          geojsonLayer.resetStyle(e.target)
        }
      })

      if (datos) {
        const total = datos.total

        let popupContent = `
          <div style="padding: 12px; min-width: 260px; font-family: system-ui, sans-serif;">
            <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 18px; font-weight: 700;">
              ${feature.properties.NOMGEO}
            </h3>
            <div style="margin-bottom: 10px;">
              <strong style="color: #374151;">Total de reportes: <span style="font-size: 18px; color: #3b82f6;">${total}</span></strong>
            </div>
        `

        if (datos.estados && Object.keys(datos.estados).length > 0) {
          Object.entries(datos.estados).forEach(([estado, count]) => {
            const color = STATUS_COLORS[estado] || '#6b7280'
            popupContent += `
              <div style="display: flex; align-items: center; margin: 6px 0;">
                <span style="width: 12px; height: 12px; background: ${color}; border-radius: 50%; margin-right: 8px;"></span>
                <span style="color: #4b5563;">${estado}: <strong>${count}</strong></span>
              </div>
            `
          })
        }

        // Agregar problemas principales si existen
        if (datos.problemas && datos.problemas.length > 0) {
          popupContent += '<div style="border-top: 1px solid #e5e7eb; margin-top: 12px; padding-top: 10px;">'
          popupContent += '<strong style="color: #374151; font-size: 13px;">Problemas principales:</strong>'
          popupContent += '<div style="margin-top: 6px;">'

          datos.problemas.forEach(problema => {
            popupContent += `
              <div style="color: #6b7280; font-size: 13px; margin: 4px 0;">
                ${problema.tipo}: <strong>${problema.cantidad}</strong>
              </div>
            `
          })

          popupContent += '</div></div>'
        }

        popupContent += '</div>'

        layer.bindPopup(popupContent, {
          maxWidth: 300,
          className: 'custom-popup'
        })
        layer.bindTooltip(`<strong>${feature.properties.NOMGEO}</strong><br>${total} reportes`, {
          sticky: true,
          direction: 'top'
        })
      } else {
        layer.bindTooltip(`<strong>${feature.properties.NOMGEO}</strong><br>Sin reportes`)
      }
    }
  })

  geojsonLayer.addTo(mapInstance)
  console.log('✅ Capa GeoJSON agregada al mapa')

  // Agregar círculos (burbujas) sobre los municipios
  console.log('🔵 Agregando burbujas al mapa...')

  municipiosData.value.forEach(municipio => {
    const nombreNormalizado = normalize(municipio.municipio)
    const coords = municipioCoordinates[nombreNormalizado]

    if (municipio.total > 0 && coords) {
      // Calcular el radio del círculo basado en el total de reportes
      const baseRadius = 6
      const scaleFactor = 1.5
      const maxRadius = 30
      const radius = Math.min(baseRadius + (municipio.total * scaleFactor), maxRadius)

      const circle = L.circleMarker([coords.lat, coords.lng], {
        radius: radius,
        fillColor: '#3b82f6',
        color: '#1e40af',
        weight: 2,
        opacity: 0.8,
        fillOpacity: 0.5
      })

      // Popup para la burbuja
      let bubblePopup = `
        <div style="padding: 12px; min-width: 260px; font-family: system-ui, sans-serif;">
          <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 18px; font-weight: 700;">
            ${municipio.municipio}
          </h3>
          <div style="margin-bottom: 10px;">
            <strong style="color: #374151;">Total de reportes: <span style="font-size: 18px; color: #3b82f6;">${municipio.total}</span></strong>
          </div>
      `

      if (municipio.estados && Object.keys(municipio.estados).length > 0) {
        Object.entries(municipio.estados).forEach(([estado, count]) => {
          const color = STATUS_COLORS[estado] || '#6b7280'
          bubblePopup += `
            <div style="display: flex; align-items: center; margin: 6px 0;">
              <span style="width: 12px; height: 12px; background: ${color}; border-radius: 50%; margin-right: 8px;"></span>
              <span style="color: #4b5563;">${estado}: <strong>${count}</strong></span>
            </div>
          `
        })
      }

      // Agregar problemas principales si existen
      if (municipio.problemas && municipio.problemas.length > 0) {
        bubblePopup += '<div style="border-top: 1px solid #e5e7eb; margin-top: 12px; padding-top: 10px;">'
        bubblePopup += '<strong style="color: #374151; font-size: 13px;">Problemas principales:</strong>'
        bubblePopup += '<div style="margin-top: 6px;">'

        municipio.problemas.forEach(problema => {
          bubblePopup += `
            <div style="color: #6b7280; font-size: 13px; margin: 4px 0;">
              ${problema.tipo}: <strong>${problema.cantidad}</strong>
            </div>
          `
        })

        bubblePopup += '</div></div>'
      }

      bubblePopup += '</div>'

      circle.bindPopup(bubblePopup, {
        maxWidth: 300,
        className: 'custom-popup'
      })

      circle.bindTooltip(`<strong>${municipio.municipio}</strong><br>${municipio.total} reportes`, {
        direction: 'top',
        offset: [0, -10]
      })

      circle.addTo(mapInstance)
    }
  })

  console.log('✅ Burbujas agregadas al mapa')

  // Crear pane para etiquetas
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
