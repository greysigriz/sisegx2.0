<template>
  <div class="map-wrapper">
    <div class="map-header">
      <h2>Mapa de Peticiones por Municipio</h2>
      <span v-if="isLoadingMap" class="loading-badge">Cargando...</span>
      <div v-if="filtroMunicipio" class="map-filter-active">
        <span class="map-filter-label">Filtrado: <strong>{{ filteredMuniName }}</strong></span>
        <button class="map-filter-clear" @click="clearFilter">Quitar filtro</button>
      </div>
    </div>

    <div class="map-container">
      <l-map
        ref="mapRef"
        :zoom="8.3"
        :center="[20.7099, -89.0943]"
        :options="mapOptions"
        style="height: 100%; width: 100%;"
        @ready="onMapReady"
      >
        <l-tile-layer :url="tileUrl" :attribution="attribution" />
        <l-control-zoom position="topleft" />
      </l-map>

      <!-- Leyenda con gradiente visual -->
      <div class="map-legend-choropleth">
        <h4 class="legend-choropleth-title">Peticiones por municipio</h4>

        <!-- Barra de gradiente -->
        <div class="legend-gradient-bar">
          <div class="gradient-track"></div>
          <div class="gradient-labels">
            <span>0</span>
            <span>{{ Math.round(maxPeticiones / 2) }}</span>
            <span>{{ maxPeticiones }}+</span>
          </div>
        </div>

        <div class="legend-choropleth-divider"></div>

        <!-- Resumen rapido -->
        <div class="legend-summary">
          <div class="legend-summary-item">
            <span class="legend-summary-val">{{ totalGeneral }}</span>
            <span class="legend-summary-label">Peticiones</span>
          </div>
          <div class="legend-summary-item">
            <span class="legend-summary-val">{{ municipiosConDatos }}</span>
            <span class="legend-summary-label">Municipios</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { LMap, LTileLayer, LControlZoom } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import '@/assets/css/mapaproblemas_dashboard.css'
import L from 'leaflet'
import { useDashboardStore } from '@/composables/useDashboardStore.js'

import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIcon2x, iconUrl: markerIcon, shadowUrl: markerShadow })

const { mapData, filtroMunicipio, setFiltro, fetchDashboard } = useDashboardStore()

const mapRef = ref(null)
const isLoadingMap = ref(true)
let mapInstance = null
let geojsonLayer = null
let labelsAdded = false
let cachedGeoJSON = null

const tileUrl = 'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}.png'
const tileUrlLabels = 'https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}.png'
const attribution = '&copy;OpenStreetMap, &copy;CartoDB'

const mapOptions = {
  zoomControl: false,
  attributionControl: true,
  minZoom: 7,
  maxZoom: 14,
  renderer: L.svg()
}

const normalize = (text) => {
  if (!text) return ''
  return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim()
}

const filteredMuniName = computed(() => {
  if (!filtroMunicipio.value || !mapData.value) return ''
  const m = mapData.value.find(x => Number(x.municipio_id) === Number(filtroMunicipio.value))
  return m ? m.municipio : 'Municipio ' + filtroMunicipio.value
})

function clearFilter() {
  setFiltro('filtroMunicipio', null)
  fetchDashboard()
}

const totalGeneral = computed(() => {
  if (!mapData.value) return 0
  return mapData.value.reduce((s, m) => s + Number(m.total || 0), 0)
})

const municipiosConDatos = computed(() => {
  if (!mapData.value) return 0
  return mapData.value.filter(m => Number(m.total) > 0).length
})

const maxPeticiones = computed(() => {
  if (!mapData.value || mapData.value.length === 0) return 10
  return Math.max(...mapData.value.map(m => Number(m.total || 0)))
})

// Escala de color continua: blanco-azul basada en el maximo real
function getColor(total, max) {
  if (total === 0) return '#f1f5f9'
  const ratio = Math.min(total / Math.max(max, 1), 1)
  // Interpolar de #bfdbfe (azul claro) a #1e3a8a (azul oscuro)
  const r = Math.round(191 - ratio * (191 - 30))
  const g = Math.round(219 - ratio * (219 - 58))
  const b = Math.round(254 - ratio * (254 - 138))
  return `rgb(${r},${g},${b})`
}

function buildPopup(nombre, data) {
  const total = data ? Number(data.total) : 0

  if (!data || total === 0) {
    return `<div style="padding:10px 14px;font-family:system-ui,sans-serif;">
      <strong style="font-size:14px;color:#1e293b;">${nombre}</strong>
      <div style="color:#94a3b8;margin-top:3px;font-size:12px;">Sin peticiones registradas</div>
    </div>`
  }

  const activas = Number(data.activas || 0)
  const completadas = Number(data.completadas || 0)
  const urgentes = Number(data.urgentes || 0)
  const porAsignar = Number(data.por_asignar || 0)
  const enProceso = Number(data.en_proceso || 0)
  const topDepts = data.top_departamentos || []
  const completadoPct = total > 0 ? Math.round((completadas / total) * 100) : 0

  // Popup compacto: header + barra + 1 fila de stats + depts inline
  let html = `<div style="padding:12px 14px;width:240px;font-family:system-ui,sans-serif;">
    <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:8px;padding-right:18px;">
      <strong style="font-size:14px;color:#1e293b;">${nombre}</strong>
      <span style="font-size:16px;font-weight:800;color:#1e3a8a;">${total}</span>
    </div>
    <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;margin-bottom:6px;">
      <div style="height:100%;width:${completadoPct}%;background:#10b981;border-radius:3px;"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:11px;color:#64748b;margin-bottom:${urgentes > 0 || topDepts.length > 0 ? '8' : '0'}px;">
      <span><span style="color:#f59e0b;font-weight:700;">${porAsignar}</span> por asignar</span>
      <span><span style="color:#3b82f6;font-weight:700;">${enProceso}</span> en proceso</span>
      <span><span style="color:#10b981;font-weight:700;">${completadas}</span> compl.</span>
    </div>`

  if (urgentes > 0) {
    html += `<div style="font-size:11px;color:#dc2626;font-weight:600;margin-bottom:${topDepts.length > 0 ? '8' : '0'}px;">${urgentes} urgente${urgentes > 1 ? 's' : ''}</div>`
  }

  if (topDepts.length > 0) {
    html += `<div style="border-top:1px solid #f1f5f9;padding-top:6px;font-size:11px;color:#64748b;">
      <div style="font-weight:600;margin-bottom:3px;">Departamentos</div>`
    topDepts.forEach(d => {
      const maxW = Math.round((Number(d.total) / total) * 100)
      html += `<div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
        <div style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#1e293b;">${d.departamento}</div>
        <div style="width:40px;height:4px;background:#f1f5f9;border-radius:2px;flex-shrink:0;">
          <div style="height:100%;width:${maxW}%;background:#3b82f6;border-radius:2px;"></div>
        </div>
        <span style="font-weight:700;color:#1e293b;width:20px;text-align:right;">${d.total}</span>
      </div>`
    })
    html += `</div>`
  }

  // Boton filtrar
  html += `<div style="border-top:1px solid #f1f5f9;padding-top:8px;margin-top:8px;">
    <button onclick="window.__filterByMunicipio(${data.municipio_id})" style="width:100%;padding:6px;border:none;background:#1e40af;color:white;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">Filtrar dashboard por ${nombre}</button>
  </div>`

  html += `</div>`
  return html
}

// Funcion global para el boton del popup
window.__filterByMunicipio = (id) => {
  setFiltro('filtroMunicipio', id)
  fetchDashboard()
  if (mapInstance) mapInstance.closePopup()
}

async function renderMap() {
  if (!mapInstance) return

  const max = maxPeticiones.value
  const dataMap = {}
  if (mapData.value) {
    mapData.value.forEach(m => {
      dataMap[normalize(m.municipio)] = m
    })
  }

  // Cachear GeoJSON (2.9MB, solo descargar una vez)
  if (!cachedGeoJSON) {
    try {
      const resp = await fetch('../../municipios-yucatan.geojson')
      if (!resp.ok) throw new Error('GeoJSON no encontrado')
      cachedGeoJSON = await resp.json()
    } catch (e) {
      console.error('Error cargando GeoJSON:', e)
      isLoadingMap.value = false
      return
    }
  }
  const geojson = cachedGeoJSON

  if (geojsonLayer) {
    mapInstance.removeLayer(geojsonLayer)
    geojsonLayer = null
  }

  geojsonLayer = L.geoJSON(geojson, {
    style: (feature) => {
      const key = normalize(feature.properties.NOMGEO)
      const data = dataMap[key]
      const total = data ? Number(data.total) : 0

      return {
        fillColor: getColor(total, max),
        fillOpacity: 0.8,
        color: total > 0 ? '#64748b' : '#cbd5e1',
        weight: total > 0 ? 1.2 : 0.6,
        opacity: 0.7
      }
    },
    onEachFeature: (feature, layer) => {
      const key = normalize(feature.properties.NOMGEO)
      const data = dataMap[key]
      const nombre = feature.properties.NOMGEO

      layer.bindPopup(buildPopup(nombre, data), {
        maxWidth: 340,
        className: 'custom-popup'
      })

      layer.on({
        mouseover: (e) => {
          e.target.setStyle({
            weight: 3,
            color: '#1e40af',
            fillOpacity: 0.95
          })
          e.target.bringToFront()
        },
        mouseout: (e) => {
          geojsonLayer.resetStyle(e.target)
        }
      })
    }
  })

  geojsonLayer.addTo(mapInstance)

  if (!labelsAdded) {
    const labelsPane = mapInstance.getPane('labels') || mapInstance.createPane('labels')
    labelsPane.style.zIndex = 650
    labelsPane.style.pointerEvents = 'none'
    L.tileLayer(tileUrlLabels, { pane: 'labels' }).addTo(mapInstance)
    labelsAdded = true
  }

  isLoadingMap.value = false
}

const onMapReady = async (instance) => {
  mapInstance = instance
  instance.setView([20.7, -89.0], 8.3)
  await renderMap()
}

watch(mapData, () => {
  if (mapInstance) renderMap()
}, { deep: true })
</script>
