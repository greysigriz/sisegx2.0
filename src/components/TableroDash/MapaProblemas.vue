<template>
  <div class="map-wrapper">
    <div class="map-header">
      <h2>
        Mapa Interactivo de Reportes - Yucatán
        <span v-if="isLoadingMunicipios" class="loading-badge">🔄 Cargando municipios...</span>
      </h2>
      <div class="header-controls">
        <div class="stats-mini">
          <div class="stat-item">
            <span class="stat-label">Total de reportes</span>
            <span class="stat-number">{{ totalReportes }}</span>
          </div>
        </div>
        <div class="view-toggle">
          <button
            :class="['toggle-btn', { active: vistaActual === 'calor' }]"
            @click="vistaActual = 'calor'"
          >
            🔥 Mapa de Calor
          </button>
          <button
            :class="['toggle-btn', { active: vistaActual === 'municipios' }]"
            @click="vistaActual = 'municipios'"
          >
            🗺️ Municipios
          </button>
        </div>
      </div>
    </div>

    <div class="map-container">
      <l-map
        ref="map"
        :zoom="8"
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

        <!-- VISTA DE MAPA DE CALOR CON POLÍGONOS GRANDES -->
        <template v-if="vistaActual === 'calor'">
          <!-- Polígonos de municipios con efecto de calor -->
          <l-polygon
            v-for="municipio in municipiosYucatan"
            :key="`heat-${municipio.codigo}`"
            :lat-lngs="municipio.poligono"
            :color="getHeatBorderColor(municipio.codigo)"
            :fillColor="getHeatColor(municipio.codigo)"
            :fillOpacity="getHeatOpacity(municipio.codigo)"
            :weight="2"
            @click="selectMunicipio(municipio)"
          >
            <l-tooltip
              :options="{
                permanent: false,
                sticky: true,
                className: 'heat-tooltip',
                direction: 'top'
              }"
            >
              <div class="tooltip-content">
                <strong>{{ municipio.nombre }}</strong><br>
                Reportes: <strong>{{ getMunicipioReportes(municipio.codigo) }}</strong><br>
                <span :style="{ color: getHeatColor(municipio.codigo), fontWeight: 'bold' }">
                  {{ getIntensityLabel(getMunicipioReportes(municipio.codigo)) }}
                </span>
              </div>
            </l-tooltip>
          </l-polygon>

          <!-- Burbujas proporcionales en el centro de cada municipio -->
          <l-circle
            v-for="municipio in municipiosConReportes"
            :key="`bubble-${municipio.codigo}`"
            :lat-lng="municipio.centro"
            :radius="getBubbleRadius(municipio.totalReportes)"
            :color="bubbleStrokeColor"
            :fillColor="bubbleColor"
            :fillOpacity="0.6"
            :weight="2"
            @click="selectMunicipio(municipio)"
          />
        </template>

        <!-- VISTA DE MUNICIPIOS DIVIDIDOS CON CÓDIGOS -->
        <template v-if="vistaActual === 'municipios'">
          <l-polygon
            v-for="municipio in municipiosYucatan"
            :key="`mun-${municipio.codigo}`"
            :lat-lngs="municipio.poligono"
            :color="'#ffffff'"
            :fillColor="getMunicipioColor(municipio.codigo)"
            :fillOpacity="0.7"
            :weight="3"
            @click="selectMunicipio(municipio)"
          >
            <l-tooltip :options="{ permanent: false, sticky: true, className: 'municipio-tooltip' }">
              <div class="tooltip-content">
                <strong>{{ municipio.nombre }}</strong><br>
                Código: {{ municipio.codigo }}<br>
                Reportes: <strong>{{ getMunicipioReportes(municipio.codigo) }}</strong>
              </div>
            </l-tooltip>
          </l-polygon>

          <!-- Etiquetas con códigos -->
          <l-marker
            v-for="municipio in municipiosYucatan"
            :key="`label-${municipio.codigo}`"
            :lat-lng="municipio.centro"
            :icon="getCodigoIcon(municipio.codigo, getMunicipioReportes(municipio.codigo))"
            :interactive="false"
            :z-index-offset="1000"
          />
        </template>

        <!-- Marcadores de problemas individuales -->
        <l-marker
          v-for="(punto, index) in puntosVisibles"
          :key="`marker-${index}`"
          :lat-lng="[punto.lat, punto.lng]"
          :icon="getProblemIcon(punto)"
          :z-index-offset="2000"
        >
          <l-popup :options="{ maxWidth: 320, className: 'custom-popup' }">
            <div class="popup-content">
              <div class="popup-header" :style="{ background: getColorProblema(punto.problema) }">
                <h3>{{ getIconoProblema(punto.problema) }} {{ punto.mz }}</h3>
              </div>
              <div class="popup-body">
                <div class="popup-row">
                  <span class="popup-label">Municipio:</span>
                  <span class="popup-value">{{ punto.municipio }}</span>
                </div>
                <div class="popup-row">
                  <span class="popup-label">Problema:</span>
                  <span class="popup-value problema-tag" :style="{ background: getColorProblema(punto.problema) }">
                    {{ punto.problema }}
                  </span>
                </div>
                <div class="popup-row">
                  <span class="popup-label">Reportes:</span>
                  <span class="popup-value reportes-badge">{{ punto.cantidad }}</span>
                </div>
                <div class="popup-row">
                  <span class="popup-label">Prioridad:</span>
                  <span class="popup-value" :style="{ color: getPrioridad(punto.cantidad).color }">
                    {{ getPrioridad(punto.cantidad).nivel }}
                  </span>
                </div>
              </div>
            </div>
          </l-popup>
        </l-marker>
      </l-map>

      <!-- Leyenda lateral -->
      <div class="map-legend">
        <div class="legend-header">
          <h4>📍 Distribución de Reportes</h4>
        </div>

        <div class="legend-content">
          <!-- Estados de reportes -->
          <div class="status-section">
            <div class="status-list">
              <div class="status-item">
                <div class="status-dot" style="background: #1e3a8a;"></div>
                <div class="status-info">
                  <span class="status-name">Rechazado</span>
                  <span class="status-count">3</span>
                </div>
              </div>
              <div class="status-item">
                <div class="status-dot" style="background: #2563eb;"></div>
                <div class="status-info">
                  <span class="status-name">Atendido</span>
                  <span class="status-count">4</span>
                </div>
              </div>
              <div class="status-item">
                <div class="status-dot" style="background: #93c5fd;"></div>
                <div class="status-info">
                  <span class="status-name">Pendiente</span>
                  <span class="status-count">2</span>
                </div>
              </div>
              <div class="status-item">
                <div class="status-dot" style="background: #60a5fa;"></div>
                <div class="status-info">
                  <span class="status-name">En Proceso</span>
                  <span class="status-count">1</span>
                </div>
              </div>
            </div>
          </div>

          <div class="legend-footer">
            <small>Total: {{ totalReportes }} reportes</small>
          </div>

          <!-- Info del municipio seleccionado -->
          <template v-if="municipioSeleccionado">
            <div class="legend-divider"></div>
            <div class="selected-info">
              <h5>✓ Seleccionado</h5>
              <div class="selected-municipio">
                <strong>{{ municipioSeleccionado.nombre }}</strong>
                <p>Código: {{ municipioSeleccionado.codigo }}</p>
                <p>Reportes: <strong>{{ municipioSeleccionado.totalReportes || getMunicipioReportes(municipioSeleccionado.codigo) }}</strong></p>
                <button class="clear-btn" @click="clearSelection">Limpiar selección</button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { LMap, LTileLayer, LMarker, LPopup, LControlZoom, LPolygon, LCircle, LTooltip } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import '@/assets/css/mapaproblemas_dashboard.css'
import L from 'leaflet'
import municipiosGeoJSON from '@/assets/municipios_yucatan.geojson'

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
const vistaActual = ref('calor')
const municipioSeleccionado = ref(null)
const municipiosYucatan = ref([])
const isLoadingMunicipios = ref(true)

// API URL
const DIVISION_API = `${import.meta.env.VITE_API_URL || '/api'}/division.php`

// Configuración del mapa
const tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
const attribution = '© OpenStreetMap'
const bubbleColor = '#2563eb'
const bubbleStrokeColor = '#1e3a8a'

const mapOptions = {
  zoomControl: false,
  attributionControl: true,
  preferCanvas: true,
  minZoom: 7,
  maxZoom: 11
}

// Datos de reportes
const puntos = [
  { mz: 'Mérida Centro', municipio: 'Mérida', codigo: '050', lat: 20.9674, lng: -89.5926, problema: 'Baches', cantidad: 45 },
  { mz: 'Mérida Norte', municipio: 'Mérida', codigo: '050', lat: 21.0206, lng: -89.6137, problema: 'Alumbrado Público', cantidad: 28 },
  { mz: 'Mérida Sur', municipio: 'Mérida', codigo: '050', lat: 20.9342, lng: -89.6173, problema: 'Basura', cantidad: 32 },
  { mz: 'Progreso Puerto', municipio: 'Progreso', codigo: '067', lat: 21.2817, lng: -89.6650, problema: 'Falta de Agua', cantidad: 18 },
  { mz: 'Progreso Centro', municipio: 'Progreso', codigo: '067', lat: 21.2794, lng: -89.6588, problema: 'Obras sin terminar', cantidad: 12 },
  { mz: 'Valladolid Centro', municipio: 'Valladolid', codigo: '102', lat: 20.6896, lng: -88.2018, problema: 'Baches', cantidad: 22 },
  { mz: 'Tizimín Centro', municipio: 'Tizimín', codigo: '095', lat: 21.1450, lng: -88.1653, problema: 'Alumbrado Público', cantidad: 15 },
  { mz: 'Ticul Centro', municipio: 'Ticul', codigo: '093', lat: 20.3992, lng: -89.5342, problema: 'Árboles Caídos', cantidad: 9 },
  { mz: 'Kanasín Centro', municipio: 'Kanasín', codigo: '041', lat: 20.9337, lng: -89.5533, problema: 'Vandalismo', cantidad: 17 },
  { mz: 'Umán Centro', municipio: 'Umán', codigo: '100', lat: 20.8867, lng: -89.7517, problema: 'Ruido', cantidad: 11 },
  { mz: 'Motul Centro', municipio: 'Motul', codigo: '054', lat: 21.0949, lng: -89.2881, problema: 'Basura', cantidad: 14 },
  { mz: 'Izamal Centro', municipio: 'Izamal', codigo: '040', lat: 20.9306, lng: -89.0172, problema: 'Baches', cantidad: 8 }
]

// Datos de municipios estáticos (fallback)
const municipiosYucatanEstaticos = [
  {
    codigo: '050',
    nombre: 'Mérida',
    centro: [20.97, -89.62],
    poligono: [
      [21.10, -89.75], [21.10, -89.45], [20.85, -89.45], [20.85, -89.75], [21.10, -89.75]
    ]
  },
  {
    codigo: '067',
    nombre: 'Progreso',
    centro: [21.28, -89.67],
    poligono: [
      [21.40, -89.80], [21.40, -89.55], [21.18, -89.55], [21.18, -89.80], [21.40, -89.80]
    ]
  },
  {
    codigo: '102',
    nombre: 'Valladolid',
    centro: [20.69, -88.20],
    poligono: [
      [20.85, -88.05], [20.85, -88.35], [20.52, -88.35], [20.52, -88.05], [20.85, -88.05]
    ]
  },
  {
    codigo: '095',
    nombre: 'Tizimín',
    centro: [21.14, -88.17],
    poligono: [
      [21.30, -88.00], [21.30, -88.30], [20.95, -88.30], [20.95, -88.00], [21.30, -88.00]
    ]
  },
  {
    codigo: '093',
    nombre: 'Ticul',
    centro: [20.40, -89.53],
    poligono: [
      [20.55, -89.40], [20.55, -89.68], [20.25, -89.68], [20.25, -89.40], [20.55, -89.40]
    ]
  },
  {
    codigo: '041',
    nombre: 'Kanasín',
    centro: [20.93, -89.55],
    poligono: [
      [21.05, -89.45], [21.05, -89.65], [20.82, -89.65], [20.82, -89.45], [21.05, -89.45]
    ]
  },
  {
    codigo: '100',
    nombre: 'Umán',
    centro: [20.89, -89.75],
    poligono: [
      [21.00, -89.68], [21.00, -89.85], [20.75, -89.85], [20.75, -89.68], [21.00, -89.68]
    ]
  },
  {
    codigo: '054',
    nombre: 'Motul',
    centro: [21.09, -89.29],
    poligono: [
      [21.25, -89.15], [21.25, -89.42], [20.95, -89.42], [20.95, -89.15], [21.25, -89.15]
    ]
  },
  {
    codigo: '040',
    nombre: 'Izamal',
    centro: [20.93, -89.02],
    poligono: [
      [21.05, -88.90], [21.05, -89.15], [20.80, -89.15], [20.80, -88.90], [21.05, -88.90]
    ]
  },
]

// Función para normalizar nombres (quitar acentos y convertir a minúsculas)
const normalizarNombre = (nombre) => {
  return nombre
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .trim()
}

// Crear un mapa de nombres normalizados a geometrías GeoJSON
const geoJSONMap = new Map()
municipiosGeoJSON.features.forEach(feature => {
  const nombreNormalizado = normalizarNombre(feature.properties.nombre)
  geoJSONMap.set(nombreNormalizado, feature)
})

// Función para calcular el centro de un polígono
const calcularCentro = (coordinates) => {
  const coords = coordinates[0]
  let lat = 0, lng = 0
  coords.forEach(coord => {
    lng += coord[0]
    lat += coord[1]
  })
  return [lat / coords.length, lng / coords.length]
}

// Función para convertir coordenadas GeoJSON a formato Leaflet
const convertirCoordenadasLeaflet = (coordinates) => {
  return coordinates[0].map(coord => [coord[1], coord[0]])
}

// Función para cargar municipios desde la API y combinar con GeoJSON
const loadMunicipios = async () => {
  try {
    isLoadingMunicipios.value = true
    console.log('🔄 Cargando municipios desde API...')

    const response = await fetch(DIVISION_API)

    if (!response.ok) {
      throw new Error('Error al cargar municipios')
    }

    const data = await response.json()

    if (data.success && data.divisions) {
      console.log(`📊 Total de municipios recibidos del API: ${data.divisions.length}`)

      // Mapear los municipios de la API con geometrías GeoJSON
      municipiosYucatan.value = data.divisions.map((mun, index) => {
        const nombreNormalizado = normalizarNombre(mun.Municipio)
        const geoFeature = geoJSONMap.get(nombreNormalizado)

        if (geoFeature && geoFeature.geometry) {
          // Usar geometría real del GeoJSON
          const municipioData = {
            codigo: geoFeature.properties.codigo,
            nombre: mun.Municipio,
            id: mun.Id,
            centro: calcularCentro(geoFeature.geometry.coordinates),
            poligono: convertirCoordenadasLeaflet(geoFeature.geometry.coordinates)
          }
          console.log(`✓ ${mun.Municipio} -> geometría GeoJSON encontrada`)
          return municipioData
        } else {
          // Fallback: generar coordenadas aproximadas
          const row = Math.floor(index / 11)
          const col = index % 11

          const baseLat = 19.5 + (row * 0.25)
          const baseLng = -90.5 + (col * 0.3)

          return {
            codigo: String(index + 1).padStart(3, '0'),
            nombre: mun.Municipio,
            id: mun.Id,
            centro: [baseLat + 0.125, baseLng + 0.15],
            poligono: [
              [baseLat, baseLng],
              [baseLat, baseLng + 0.3],
              [baseLat + 0.25, baseLng + 0.3],
              [baseLat + 0.25, baseLng],
              [baseLat, baseLng]
            ]
          }
        }
      })

      const conGeoJSON = municipiosYucatan.value.filter(m => {
        const nombreNorm = normalizarNombre(m.nombre)
        return geoJSONMap.has(nombreNorm)
      }).length

      console.log(`✅ ${municipiosYucatan.value.length} municipios cargados (${conGeoJSON} con geometría GeoJSON real)`)
      console.log(`📍 Primer municipio:`, municipiosYucatan.value[0])
    } else {
      throw new Error(data.message || 'No se pudieron cargar los municipios')
    }
  } catch (error) {
    console.error('❌ Error cargando municipios:', error)
    // Usar datos estáticos como fallback
    municipiosYucatan.value = municipiosYucatanEstaticos
    console.log('⚠️ Usando municipios estáticos como respaldo')
  } finally {
    isLoadingMunicipios.value = false
  }
}

// Computadas
const totalReportes = computed(() => puntos.reduce((sum, p) => sum + p.cantidad, 0))

const municipiosConReportes = computed(() => {
  const reportesPorCodigo = {}

  puntos.forEach(punto => {
    if (!reportesPorCodigo[punto.codigo]) {
      reportesPorCodigo[punto.codigo] = 0
    }
    reportesPorCodigo[punto.codigo] += punto.cantidad
  })

  return municipiosYucatan.value.map(mun => ({
    ...mun,
    totalReportes: reportesPorCodigo[mun.codigo] || 0
  })).filter(m => m.totalReportes > 0)
})

const puntosVisibles = computed(() => {
  if (municipioSeleccionado.value) {
    return puntos.filter(p => p.codigo === municipioSeleccionado.value.codigo)
  }
  return []
})

// Métodos
const selectMunicipio = (municipio) => {
  const reportes = getMunicipioReportes(municipio.codigo)
  municipioSeleccionado.value = {
    ...municipio,
    totalReportes: reportes
  }
}

const clearSelection = () => {
  municipioSeleccionado.value = null
}

const getMunicipioReportes = (codigo) => {
  return puntos.filter(p => p.codigo === codigo).reduce((sum, p) => sum + p.cantidad, 0)
}

const getHeatColor = (codigo) => {
  const reportes = getMunicipioReportes(codigo)
  if (reportes >= 90) return '#dc2626'
  if (reportes >= 61) return '#f97316'
  if (reportes >= 31) return '#f59e0b'
  if (reportes > 0) return '#10b981'
  return '#e5e7eb'
}

const getHeatBorderColor = (codigo) => {
  const reportes = getMunicipioReportes(codigo)
  if (reportes > 0) return '#ffffff'
  return '#cbd5e1'
}

const getHeatOpacity = (codigo) => {
  const reportes = getMunicipioReportes(codigo)
  if (reportes >= 90) return 0.8
  if (reportes >= 61) return 0.7
  if (reportes >= 31) return 0.6
  if (reportes > 0) return 0.5
  return 0.2
}

const getMunicipioColor = (codigo) => {
  const reportes = getMunicipioReportes(codigo)
  if (reportes >= 90) return '#dc2626'
  if (reportes >= 61) return '#f97316'
  if (reportes >= 31) return '#f59e0b'
  if (reportes > 0) return '#10b981'
  return '#f1f5f9'
}

const getBubbleRadius = (reportes) => {
  if (reportes >= 90) return 12000
  if (reportes >= 61) return 9000
  if (reportes >= 31) return 6000
  return 3500
}

const getIntensityLabel = (reportes) => {
  if (reportes >= 90) return '🔴 Crítica'
  if (reportes >= 61) return '🟠 Alta'
  if (reportes >= 31) return '🟡 Media'
  if (reportes > 0) return '🟢 Baja'
  return ''
}

const getCodigoIcon = (codigo, reportes) => {
  const color = reportes > 0 ? '#1e3a8a' : '#94a3b8'
  return L.divIcon({
    className: 'codigo-label',
    html: `<div style="
      background: white;
      border: 2px solid ${color};
      border-radius: 6px;
      padding: 4px 8px;
      font-size: 13px;
      font-weight: 800;
      color: ${color};
      box-shadow: 0 2px 6px rgba(0,0,0,0.25);
      white-space: nowrap;
      pointer-events: none;
    ">${codigo}</div>`,
    iconSize: [50, 24],
    iconAnchor: [25, 12]
  })
}

const getColorProblema = (problema) => {
  const colores = {
    'Baches': '#ef4444',
    'Alumbrado Público': '#f59e0b',
    'Basura': '#10b981',
    'Falta de Agua': '#3b82f6',
    'Obras sin terminar': '#8b5cf6',
    'Árboles Caídos': '#059669',
    'Vandalismo': '#dc2626',
    'Ruido': '#f97316'
  }
  return colores[problema] || '#6b7280'
}

const getIconoProblema = (problema) => {
  const iconos = {
    'Baches': '🕳️',
    'Alumbrado Público': '💡',
    'Basura': '🗑️',
    'Falta de Agua': '💧',
    'Obras sin terminar': '🚧',
    'Árboles Caídos': '🌳',
    'Vandalismo': '⚠️',
    'Ruido': '🔊'
  }
  return iconos[problema] || '📍'
}

const getPrioridad = (cantidad) => {
  if (cantidad >= 26) return { nivel: 'Alta', color: '#dc2626' }
  if (cantidad >= 11) return { nivel: 'Media', color: '#f59e0b' }
  return { nivel: 'Baja', color: '#10b981' }
}

const getProblemIcon = (punto) => {
  const color = getColorProblema(punto.problema)
  const size = 28

  return L.divIcon({
    className: 'problem-marker',
    html: `
      <div style="
        width: ${size}px;
        height: ${size}px;
        background: ${color};
        border: 3px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: ${size * 0.5}px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.4);
        cursor: pointer;
      ">${getIconoProblema(punto.problema)}</div>
    `,
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2]
  })
}

const onMapReady = () => {
  console.log('✅ Mapa cargado')
}

// Cargar municipios al montar el componente
onMounted(async () => {
  await loadMunicipios()
})
</script>
