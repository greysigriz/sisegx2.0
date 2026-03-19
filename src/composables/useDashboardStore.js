// src/composables/useDashboardStore.js
// Store reactivo compartido para el dashboard del director
import { reactive, toRefs } from 'vue'
import axios from '@/services/axios-config.js'

const state = reactive({
  // Data general
  kpis: null,
  kpisPrev: null,
  porEstado: [],
  porImportancia: [],
  timeline: [],
  topMunicipios: [],
  topDepartamentos: [],
  recientes: [],
  alerts: [],
  municipiosList: [],
  departamentosList: [],
  mapData: [],

  // Detalle por departamento
  detalleDepartamento: null,
  selectedDeptId: null,
  detalleLoading: false,

  // Detalle por municipio
  detalleMunicipio: null,
  selectedMuniId: null,

  // Filtros
  filtroMunicipio: null,
  filtroEstado: null,
  filtroImportancia: null,
  filtroDias: 9999,
  filtroFechaInicio: null,
  filtroFechaFin: null,

  // Drill-down de tarjetas
  cardDetalle: null,
  cardDetalleLoading: false,
  selectedCard: null,

  // UI
  isLoading: false,
  error: null,
  lastUpdate: null
})

// Debounce para evitar rafagas de requests
let debounceTimer = null
let requestId = 0

async function fetchDashboard() {
  // Cancelar debounce previo
  if (debounceTimer) clearTimeout(debounceTimer)

  return new Promise((resolve) => {
    debounceTimer = setTimeout(async () => {
      await _doFetch()
      resolve()
    }, 80) // 80ms debounce
  })
}

async function _doFetch() {
  state.isLoading = true
  state.error = null
  const myId = ++requestId

  const params = {}
  if (state.filtroMunicipio) params.municipio_id = state.filtroMunicipio
  if (state.filtroEstado) params.estado = state.filtroEstado
  if (state.filtroImportancia) params.importancia = state.filtroImportancia
  if (state.filtroFechaInicio) params.fecha_inicio = state.filtroFechaInicio
  if (state.filtroFechaFin) params.fecha_fin = state.filtroFechaFin
  if (!state.filtroFechaInicio && !state.filtroFechaFin) params.dias = state.filtroDias
  if (state.selectedDeptId) params.departamento_id = state.selectedDeptId
  if (state.selectedMuniId) params.municipio_detalle_id = state.selectedMuniId
  // Si ya tenemos listas estaticas, no pedirlas de nuevo
  if (state.municipiosList.length > 0) params.skip_lists = 1

  try {
    params._nonce = Date.now()
    const res = await axios.get('dashboard-director.php', { params })

    // Ignorar si ya hay un request mas nuevo
    if (myId !== requestId) return

    if (res.data && res.data.success) {
      state.kpis = res.data.kpis
      state.kpisPrev = res.data.kpis_prev || null
      state.porEstado = res.data.por_estado || []
      state.porImportancia = res.data.por_importancia || []
      state.timeline = res.data.timeline || []
      state.topMunicipios = res.data.top_municipios || []
      state.topDepartamentos = res.data.top_departamentos || []
      state.recientes = res.data.recientes || []
      state.alerts = res.data.alerts || []
      state.mapData = res.data.map_data || []
      state.detalleDepartamento = res.data.detalle_departamento || null
      state.detalleMunicipio = res.data.detalle_municipio || null
      // Listas estaticas: solo actualizar si vienen
      if (res.data.municipios_list) state.municipiosList = res.data.municipios_list
      if (res.data.departamentos_list) state.departamentosList = res.data.departamentos_list
      state.lastUpdate = new Date()
    }
  } catch (err) {
    if (myId !== requestId) return
    if (err && err.cancelled) return
    if (axios.isCancel && axios.isCancel(err)) return
    console.error('Error dashboard:', err)
    state.error = err.message || 'Error de conexion'
  } finally {
    if (myId === requestId) state.isLoading = false
  }
}

async function selectDepartamento(id) {
  state.selectedDeptId = id || null
  state.detalleDepartamento = null
  if (!id) return

  state.detalleLoading = true
  try {
    const res = await axios.get('dashboard-detalle.php', { params: { tipo: 'departamento', id, _nonce: Date.now() } })
    if (res.data && res.data.success) {
      state.detalleDepartamento = res.data
    }
  } catch (err) {
    console.error('Error detalle departamento:', err)
  } finally {
    state.detalleLoading = false
  }
}

async function selectMunicipio(id) {
  state.selectedMuniId = id || null
  state.detalleMunicipio = null
  if (!id) return

  state.detalleLoading = true
  try {
    const res = await axios.get('dashboard-detalle.php', { params: { tipo: 'municipio', id, _nonce: Date.now() } })
    if (res.data && res.data.success) {
      state.detalleMunicipio = res.data
    }
  } catch (err) {
    console.error('Error detalle municipio:', err)
  } finally {
    state.detalleLoading = false
  }
}

async function fetchCardDetalle(tipo) {
  // Toggle: si ya está seleccionada, cerrar
  if (state.selectedCard === tipo) {
    state.selectedCard = null
    state.cardDetalle = null
    return
  }

  state.selectedCard = tipo
  state.cardDetalleLoading = true
  state.cardDetalle = null

  const params = { card_detalle: tipo, skip_lists: 1 }
  if (state.filtroMunicipio) params.municipio_id = state.filtroMunicipio
  if (state.filtroFechaInicio) params.fecha_inicio = state.filtroFechaInicio
  if (state.filtroFechaFin) params.fecha_fin = state.filtroFechaFin
  if (!state.filtroFechaInicio && !state.filtroFechaFin) params.dias = state.filtroDias

  try {
    params._nonce = Date.now()
    const res = await axios.get('dashboard-director.php', { params })
    if (res.data && res.data.success && res.data.card_detalle) {
      state.cardDetalle = res.data.card_detalle
    }
  } catch (err) {
    console.error('Error card detalle:', err)
    state.cardDetalle = null
  } finally {
    state.cardDetalleLoading = false
  }
}

function setFiltro(key, value) {
  if (state[key] !== value) {
    state[key] = value
  }
}

function resetFiltros() {
  state.filtroMunicipio = null
  state.filtroEstado = null
  state.filtroImportancia = null
  state.filtroDias = 9999
  state.filtroFechaInicio = null
  state.filtroFechaFin = null
}

export function useDashboardStore() {
  return {
    ...toRefs(state),
    fetchDashboard,
    fetchCardDetalle,
    selectDepartamento,
    selectMunicipio,
    setFiltro,
    resetFiltros
  }
}
