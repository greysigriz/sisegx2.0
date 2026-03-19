<template>
  <div class="map-wrapper">
    <div class="map-header">
      <h2>Mapa de Peticiones por Municipio</h2>
      <span v-if="isLoadingMap" class="loading-badge">Cargando...</span>
    </div>
    <div v-if="filtroMunicipio" class="map-filter-banner">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
      <span>El dashboard completo esta filtrado por <strong>{{ filteredMuniName }}</strong> — solo se muestran datos de este municipio en todas las graficas y tarjetas.</span>
      <button class="map-filter-clear" @click="clearFilter">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        Quitar filtro
      </button>
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

    <!-- Panel desplegable de peticiones del municipio -->
    <Transition name="slide-map">
      <div v-if="mapPanelVisible" class="map-detalle-panel">
        <div v-if="mapPanelLoading" class="map-detalle-loading">
          <div class="map-detalle-spinner"></div>
          <span>Cargando peticiones...</span>
        </div>
        <div v-else-if="mapPanelData && mapPanelData.peticiones && mapPanelData.peticiones.length > 0">
          <div class="map-detalle-header">
            <span class="map-detalle-title">{{ mapPanelTitle }}</span>
            <span class="map-detalle-count">{{ mapPanelData.peticiones.length }} peticiones</span>
            <button class="map-csv-btn" @click="downloadMapCSV" title="Descargar CSV">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
              CSV
            </button>
            <button class="map-close-btn" @click="closeMapPanel" title="Cerrar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
          </div>
          <div class="map-detalle-table-wrap">
            <table class="map-detalle-table">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Peticionario</th>
                  <th>Descripcion</th>
                  <th>Municipio</th>
                  <th>Estado</th>
                  <th>Importancia</th>
                  <th>Dias</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in mapPanelData.peticiones" :key="p.id">
                  <td class="mp-td-folio">{{ p.folio || '-' }}</td>
                  <td class="mp-td-nombre">{{ p.nombre || 'Anonimo' }}</td>
                  <td class="mp-td-desc">{{ truncText(p.descripcion, 50) }}</td>
                  <td>{{ p.Municipio || '-' }}</td>
                  <td><span class="mp-estado" :class="mpEstadoClass(p.estado)">{{ p.estado }}</span></td>
                  <td><span class="mp-imp" :class="mpImpClass(p.NivelImportancia)">{{ mpImpLabel(p.NivelImportancia) }}</span></td>
                  <td class="mp-td-dias" :class="{ 'mp-td-dias--alerta': (p.dias_transcurridos || p.dias_asignado) > 30 }">{{ p.dias_transcurridos || p.dias_asignado || 0 }}d</td>
                  <td class="mp-td-fecha">{{ fmtDate(p.fecha_registro || p.fecha_asignacion) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-else class="map-detalle-empty">Sin peticiones para mostrar.</div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { LMap, LTileLayer, LControlZoom } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import '@/assets/css/mapaproblemas_dashboard.css'
import L from 'leaflet'
import { useDashboardStore } from '@/composables/useDashboardStore.js'
import axios from '@/services/axios-config.js'

import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIcon2x, iconUrl: markerIcon, shadowUrl: markerShadow })

const { mapData, filtroMunicipio, setFiltro, fetchDashboard } = useDashboardStore()

// Panel de peticiones del mapa
const mapPanelVisible = ref(false)
const mapPanelLoading = ref(false)
const mapPanelData = ref(null)
const mapPanelTitle = ref('')

async function openMapPanel(muniId, muniName, deptId, deptName) {
  mapPanelVisible.value = true
  mapPanelLoading.value = true
  mapPanelData.value = null

  if (deptId) {
    mapPanelTitle.value = deptName + ' — ' + muniName
  } else {
    mapPanelTitle.value = muniName
  }

  try {
    if (deptId) {
      // Peticiones de un departamento específico en un municipio
      const res = await axios.get('dashboard-detalle.php', { params: { tipo: 'departamento', id: deptId, _nonce: Date.now() } })
      if (res.data && res.data.success) {
        // Filtrar solo las del municipio
        const filtered = res.data.peticiones.filter(p => {
          const pMuni = (p.Municipio || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          const target = muniName.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          return pMuni === target
        })
        mapPanelData.value = { peticiones: filtered }
      }
    } else {
      // Todas las peticiones del municipio
      const res = await axios.get('dashboard-detalle.php', { params: { tipo: 'municipio', id: muniId, _nonce: Date.now() } })
      if (res.data && res.data.success) {
        mapPanelData.value = { peticiones: res.data.peticiones }
      }
    }
  } catch (err) {
    console.error('Error map panel:', err)
  } finally {
    mapPanelLoading.value = false
  }
}

function closeMapPanel() {
  mapPanelVisible.value = false
  mapPanelData.value = null
}

// Globals for popup buttons
window.__openMapPanel = (muniId, muniName, deptId, deptName) => {
  openMapPanel(muniId, muniName, deptId || null, deptName || null)
}

const IMP_MAP = { 1: 'Critica', 2: 'Alta', 3: 'Media' }

function truncText(s, len) {
  if (!s) return '-'
  return s.length > len ? s.substring(0, len) + '...' : s
}
function fmtDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}
function mpEstadoClass(e) {
  const m = { 'Sin revisar': 'mp-e-pend', 'Por asignar departamento': 'mp-e-pend', 'Esperando recepción': 'mp-e-pend',
    'Aceptada en proceso': 'mp-e-proc', 'Completado': 'mp-e-ok', 'Devuelto': 'mp-e-dev',
    'Rechazado por departamento': 'mp-e-dev', 'Improcedente': 'mp-e-cerr', 'Cancelada': 'mp-e-cerr' }
  return m[e] || ''
}
function mpImpClass(n) {
  if (n == 1) return 'mp-i-crit'
  if (n == 2) return 'mp-i-alta'
  if (n == 3) return 'mp-i-med'
  return 'mp-i-baja'
}
function mpImpLabel(n) { return IMP_MAP[n] || 'Baja' }

function downloadMapCSV() {
  if (!mapPanelData.value || !mapPanelData.value.peticiones.length) return
  const esc = v => '"' + String(v).replace(/"/g, '""') + '"'
  const hdr = ['Folio', 'Peticionario', 'Descripcion', 'Municipio', 'Estado', 'Importancia', 'Dias', 'Fecha']
  const rows = mapPanelData.value.peticiones.map(p => [
    p.folio || '', p.nombre || 'Anonimo', (p.descripcion || '').replace(/[\r\n]+/g, ' '),
    p.Municipio || '', p.estado || '', IMP_MAP[p.NivelImportancia] || 'Baja',
    p.dias_transcurridos || p.dias_asignado || 0, p.fecha_registro || p.fecha_asignacion || ''
  ])
  const csv = '\uFEFF' + [hdr.map(esc).join(','), ...rows.map(r => r.map(esc).join(','))].join('\r\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = mapPanelTitle.value.replace(/\s+/g, '_') + '.csv'
  a.click()
  URL.revokeObjectURL(url)
}

const mapRef = ref(null)
const isLoadingMap = ref(true)
let mapInstance = null
let geojsonLayer = null
let labelsLayer = null
let cachedGeoJSON = null

const darkMode = ref(isDark())
const tileUrl = computed(() => darkMode.value
  ? 'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}.png'
  : 'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}.png'
)
const tileUrlLabels = computed(() => darkMode.value
  ? 'https://{s}.basemaps.cartocdn.com/dark_only_labels/{z}/{x}/{y}.png'
  : 'https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}.png'
)

// Watch dark mode class changes
let darkObserver = null
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

// Escala de color continua basada en el maximo real
function getColor(total, max) {
  const dk = isDark()
  if (total === 0) return dk ? '#1e293b' : '#f1f5f9'
  const ratio = Math.min(total / Math.max(max, 1), 1)
  if (dk) {
    // Dark: de #1e3a5f (azul oscuro tenue) a #3b82f6 (azul brillante)
    const r = Math.round(30 + ratio * (59 - 30))
    const g = Math.round(58 + ratio * (130 - 58))
    const b = Math.round(95 + ratio * (246 - 95))
    return `rgb(${r},${g},${b})`
  }
  // Light: de #bfdbfe (azul claro) a #1e3a8a (azul oscuro)
  const r = Math.round(191 - ratio * (191 - 30))
  const g = Math.round(219 - ratio * (219 - 58))
  const b = Math.round(254 - ratio * (254 - 138))
  return `rgb(${r},${g},${b})`
}

function isDark() {
  return document.documentElement.classList.contains('dark-mode')
}

function buildPopup(nombre, data) {
  const total = data ? Number(data.total) : 0
  const dk = isDark()
  // Palette
  const c = {
    text: dk ? '#e2e8f0' : '#1e293b',
    textMuted: dk ? '#94a3b8' : '#64748b',
    accent: dk ? '#93c5fd' : '#1e3a8a',
    border: dk ? '#475569' : '#f1f5f9',
    barBg: dk ? '#334155' : '#f1f5f9',
    hoverBg: dk ? '#334155' : '#f1f5f9',
    btnBg: dk ? '#1e293b' : 'white',
    btnText: dk ? '#93c5fd' : '#1e40af'
  }

  if (!data || total === 0) {
    return `<div style="padding:10px 14px;font-family:system-ui,sans-serif;">
      <strong style="font-size:14px;color:${c.text};">${nombre}</strong>
      <div style="color:${c.textMuted};margin-top:3px;font-size:12px;">Sin peticiones registradas</div>
    </div>`
  }

  const completadas = Number(data.completadas || 0)
  const urgentes = Number(data.urgentes || 0)
  const porAsignar = Number(data.por_asignar || 0)
  const enProceso = Number(data.en_proceso || 0)
  const topDepts = data.top_departamentos || []
  const completadoPct = total > 0 ? Math.round((completadas / total) * 100) : 0

  let html = `<div style="padding:12px 14px;width:240px;font-family:system-ui,sans-serif;">
    <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:8px;padding-right:18px;">
      <strong style="font-size:14px;color:${c.text};">${nombre}</strong>
      <span style="font-size:16px;font-weight:800;color:${c.accent};">${total}</span>
    </div>
    <div style="height:6px;background:${c.barBg};border-radius:3px;overflow:hidden;margin-bottom:6px;">
      <div style="height:100%;width:${completadoPct}%;background:#10b981;border-radius:3px;"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:11px;color:${c.textMuted};margin-bottom:${urgentes > 0 || topDepts.length > 0 ? '8' : '0'}px;">
      <span><span style="color:#f59e0b;font-weight:700;">${porAsignar}</span> por asignar</span>
      <span><span style="color:#3b82f6;font-weight:700;">${enProceso}</span> en proceso</span>
      <span><span style="color:#10b981;font-weight:700;">${completadas}</span> compl.</span>
    </div>`

  if (urgentes > 0) {
    html += `<div style="font-size:11px;color:#f87171;font-weight:600;margin-bottom:${topDepts.length > 0 ? '8' : '0'}px;">${urgentes} urgente${urgentes > 1 ? 's' : ''}</div>`
  }

  if (topDepts.length > 0) {
    html += `<div style="border-top:1px solid ${c.border};padding-top:6px;font-size:11px;color:${c.textMuted};">
      <div style="font-weight:600;margin-bottom:4px;">Departamentos <span style="font-weight:400;font-size:10px;color:${c.textMuted};">— click para ver</span></div>`
    topDepts.forEach(d => {
      const maxW = Math.round((Number(d.total) / total) * 100)
      const deptNameEsc = d.departamento.replace(/'/g, "\\'")
      const nombreEsc = nombre.replace(/'/g, "\\'")
      html += `<div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;padding:3px 4px;border-radius:4px;cursor:pointer;" onmouseover="this.style.background='${c.hoverBg}'" onmouseout="this.style.background='transparent'" onclick="window.__openMapPanel(${data.municipio_id},'${nombreEsc}',${d.departamento_id || 0},'${deptNameEsc}')">
        <div style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:${c.text};">${d.departamento}</div>
        <div style="width:40px;height:4px;background:${c.barBg};border-radius:2px;flex-shrink:0;">
          <div style="height:100%;width:${maxW}%;background:#3b82f6;border-radius:2px;"></div>
        </div>
        <span style="font-weight:700;color:${c.text};width:20px;text-align:right;">${d.total}</span>
      </div>`
    })
    html += `</div>`
  }

  const nombreEsc = nombre.replace(/'/g, "\\'")
  html += `<div style="border-top:1px solid ${c.border};padding-top:8px;margin-top:8px;display:flex;flex-direction:column;gap:6px;">
    <button onclick="window.__openMapPanel(${data.municipio_id},'${nombreEsc}')" style="width:100%;padding:7px;border:1px solid #3b82f6;background:${c.btnBg};color:${c.btnText};border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
      Ver ${total} peticiones de ${nombre}
    </button>
    <button onclick="window.__filterByMunicipio(${data.municipio_id})" style="width:100%;padding:7px;border:none;background:#1e40af;color:white;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
      Filtrar todo el dashboard por ${nombre}
    </button>
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

      const dk = isDark()
      return {
        fillColor: getColor(total, max),
        fillOpacity: dk ? 0.9 : 0.8,
        color: dk
          ? (total > 0 ? '#475569' : '#334155')
          : (total > 0 ? '#64748b' : '#cbd5e1'),
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

  // Labels tile layer (remove old, add new to support dark/light switch)
  if (labelsLayer) {
    mapInstance.removeLayer(labelsLayer)
  }
  const labelsPane = mapInstance.getPane('labels') || mapInstance.createPane('labels')
  labelsPane.style.zIndex = 650
  labelsPane.style.pointerEvents = 'none'
  labelsLayer = L.tileLayer(tileUrlLabels.value, { pane: 'labels' })
  labelsLayer.addTo(mapInstance)

  isLoadingMap.value = false
}

const onMapReady = async (instance) => {
  mapInstance = instance
  instance.setView([20.7, -89.0], 8.3)
  await renderMap()

  // Observe dark mode changes to re-render map with correct colors
  darkObserver = new MutationObserver(() => {
    const newDark = isDark()
    if (newDark !== darkMode.value) {
      darkMode.value = newDark
      if (mapInstance) renderMap()
    }
  })
  darkObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
}

watch(mapData, () => {
  if (mapInstance) renderMap()
}, { deep: true })

onUnmounted(() => {
  if (darkObserver) darkObserver.disconnect()
})
</script>
