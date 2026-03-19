<template>
  <div class="analysis-panel">
    <!-- Tabs -->
    <div class="analysis-tabs">
      <button class="analysis-tab" :class="{ active: tab === 'dept' }" @click="tab = 'dept'">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
        Por Departamento
      </button>
      <button class="analysis-tab" :class="{ active: tab === 'muni' }" @click="tab = 'muni'">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
        Por Municipio
      </button>
    </div>

    <!-- DEPARTAMENTO -->
    <div v-if="tab === 'dept'" class="analysis-content">
      <div class="selector-row">
        <select v-model="deptId" class="analysis-select" @change="onSelectDept">
          <option :value="null">Selecciona un departamento...</option>
          <option v-for="d in departamentosList" :key="d.id" :value="d.id">{{ d.nombre_unidad }}</option>
        </select>
      </div>

      <!-- Loading -->
      <div v-if="detalleLoading && deptId" class="detail-loading">
        <div class="detail-spinner"></div>
        <span>Cargando detalle...</span>
      </div>

      <!-- Sin seleccion: mostrar ranking -->
      <div v-else-if="!detalleDepartamento" class="ranking-view">
        <h4 class="ranking-title">Ranking de Departamentos</h4>
        <div class="ranking-list">
          <div v-for="(d, i) in topDepartamentos" :key="d.departamento_id"
            class="ranking-item" @click="deptId = d.departamento_id; onSelectDept()">
            <span class="health-dot" :class="healthClass(d)"></span>
            <div class="ranking-info">
              <span class="ranking-name">{{ d.departamento }}</span>
              <div class="ranking-bar-bg">
                <div class="ranking-bar" :style="{ width: barWidth(d.total_asignaciones, topDepartamentos) }"></div>
              </div>
            </div>
            <div class="ranking-stats">
              <span class="ranking-total">{{ d.total_asignaciones }}</span>
              <span class="ranking-rate" :class="rateClass(d.tasa_resolucion)">{{ d.tasa_resolucion || 0 }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Con seleccion: detalle -->
      <div v-else class="detail-view">
        <div class="detail-header">
          <h4>{{ detalleDepartamento.info.nombre_unidad }}</h4>
          <button class="clear-btn" @click="deptId = null; onSelectDept()">Ver todos</button>
        </div>

        <div class="detail-kpis">
          <div class="detail-kpi">
            <span class="detail-kpi-val">{{ detalleDepartamento.totales.total }}</span>
            <span class="detail-kpi-label">Total</span>
          </div>
          <div class="detail-kpi">
            <span class="detail-kpi-val success">{{ detalleDepartamento.totales.completadas }}</span>
            <span class="detail-kpi-label">Completadas</span>
          </div>
          <div class="detail-kpi">
            <span class="detail-kpi-val warning">{{ detalleDepartamento.totales.pendientes }}</span>
            <span class="detail-kpi-label">Pendientes</span>
          </div>
          <div class="detail-kpi">
            <span class="detail-kpi-val" :class="rateClass(detalleDepartamento.totales.tasa)">{{ detalleDepartamento.totales.tasa || 0 }}%</span>
            <span class="detail-kpi-label">Resolucion</span>
          </div>
        </div>

        <!-- Tabs: Resumen / Lista -->
        <div class="detail-subtabs">
          <button class="detail-subtab" :class="{ active: deptView === 'resumen' }" @click="deptView = 'resumen'">Resumen</button>
          <button class="detail-subtab" :class="{ active: deptView === 'lista' }" @click="deptView = 'lista'">
            Lista de peticiones
            <span class="subtab-count" v-if="detalleDepartamento.peticiones">{{ deptPeticionesFiltradas.length }}</span>
          </button>
        </div>

        <div v-if="deptView === 'resumen'">
          <!-- Mini timeline -->
          <div ref="deptChartEl" class="detail-chart"></div>

          <!-- Municipios donde trabaja -->
          <h5 class="detail-subtitle">Municipios donde interviene</h5>
          <div class="breakdown-list">
            <div v-for="m in detalleDepartamento.municipios" :key="m.municipio_id" class="breakdown-item">
              <span class="breakdown-name">{{ m.municipio }}</span>
              <div class="breakdown-badges">
                <span class="badge">{{ m.total }}</span>
                <span class="badge badge-green">{{ m.completadas }}</span>
                <span class="badge badge-yellow">{{ m.esperando }}</span>
              </div>
            </div>
            <div v-if="!detalleDepartamento.municipios.length" class="empty-msg">Sin asignaciones registradas</div>
          </div>
        </div>

        <div v-else class="detail-lista">
          <div class="detail-lista-header">
            <select v-model="deptFilterMuni" class="detail-subfilter">
              <option :value="null">Todos los municipios</option>
              <option v-for="m in deptMuniOptions" :key="m.municipio_id" :value="m.municipio">{{ m.municipio }} ({{ m.total }})</option>
            </select>
            <span class="detail-lista-info">{{ deptPeticionesFiltradas.length }} peticiones</span>
            <button class="csv-btn" @click="downloadCSV('departamento')" title="Descargar CSV">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
              CSV
            </button>
          </div>
          <div class="detail-table-wrap">
            <table class="detail-table">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Peticionario</th>
                  <th>Municipio</th>
                  <th>Estado peticion</th>
                  <th>Estado asignacion</th>
                  <th>Importancia</th>
                  <th>Dias</th>
                  <th>Fecha asig.</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in deptPeticionesFiltradas" :key="p.id">
                  <td class="td-folio">{{ p.folio || '-' }}</td>
                  <td class="td-nombre">{{ p.nombre || 'Anonimo' }}</td>
                  <td>{{ p.Municipio || '-' }}</td>
                  <td><span class="estado-badge" :class="estadoClass(p.estado)">{{ p.estado }}</span></td>
                  <td><span class="estado-badge" :class="estadoAsigClass(p.estado_asignacion)">{{ p.estado_asignacion }}</span></td>
                  <td><span class="imp-badge" :class="impClass(p.NivelImportancia)">{{ impLabel(p.NivelImportancia) }}</span></td>
                  <td class="td-dias" :class="{ 'td-dias--alerta': p.dias_asignado > 30 }">{{ p.dias_asignado }}d</td>
                  <td class="td-fecha">{{ formatDate(p.fecha_asignacion) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- MUNICIPIO -->
    <div v-if="tab === 'muni'" class="analysis-content">
      <div class="selector-row">
        <select v-model="muniId" class="analysis-select" @change="onSelectMuni">
          <option :value="null">Selecciona un municipio...</option>
          <option v-for="m in municipiosList" :key="m.Id" :value="m.Id">{{ m.Municipio }}</option>
        </select>
      </div>

      <!-- Loading -->
      <div v-if="detalleLoading && muniId" class="detail-loading">
        <div class="detail-spinner"></div>
        <span>Cargando detalle...</span>
      </div>

      <!-- Sin seleccion: mostrar ranking -->
      <div v-else-if="!detalleMunicipio" class="ranking-view">
        <h4 class="ranking-title">Municipios con mas peticiones</h4>
        <div class="ranking-list">
          <div v-for="(m, i) in topMunicipios" :key="m.municipio_id"
            class="ranking-item" @click="muniId = m.municipio_id; onSelectMuni()">
            <span class="ranking-pos">{{ i + 1 }}</span>
            <div class="ranking-info">
              <span class="ranking-name">{{ m.municipio }}</span>
              <div class="ranking-bar-bg">
                <div class="ranking-bar ranking-bar-muni" :style="{ width: barWidth(m.cantidad, topMunicipios, 'cantidad') }"></div>
              </div>
            </div>
            <div class="ranking-stats">
              <span class="ranking-total">{{ m.cantidad }}</span>
              <span class="ranking-urgentes" v-if="m.urgentes > 0">{{ m.urgentes }} urg.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Con seleccion: detalle -->
      <div v-else class="detail-view">
        <div class="detail-header">
          <h4>{{ detalleMunicipio.info.Municipio }}</h4>
          <button class="clear-btn" @click="muniId = null; onSelectMuni()">Ver todos</button>
        </div>

        <div class="detail-kpis">
          <div class="detail-kpi">
            <span class="detail-kpi-val">{{ detalleMunicipio.totales.total }}</span>
            <span class="detail-kpi-label">Total</span>
          </div>
          <div class="detail-kpi">
            <span class="detail-kpi-val success">{{ detalleMunicipio.totales.completadas }}</span>
            <span class="detail-kpi-label">Completadas</span>
          </div>
          <div class="detail-kpi">
            <span class="detail-kpi-val warning">{{ detalleMunicipio.totales.activas }}</span>
            <span class="detail-kpi-label">Activas</span>
          </div>
          <div class="detail-kpi">
            <span class="detail-kpi-val danger">{{ detalleMunicipio.totales.urgentes }}</span>
            <span class="detail-kpi-label">Urgentes</span>
          </div>
        </div>

        <!-- Tabs: Resumen / Lista -->
        <div class="detail-subtabs">
          <button class="detail-subtab" :class="{ active: muniView === 'resumen' }" @click="muniView = 'resumen'">Resumen</button>
          <button class="detail-subtab" :class="{ active: muniView === 'lista' }" @click="muniView = 'lista'">
            Lista de peticiones
            <span class="subtab-count" v-if="detalleMunicipio.peticiones">{{ muniPeticionesFiltradas.length }}</span>
          </button>
        </div>

        <div v-if="muniView === 'resumen'">
          <!-- Mini timeline -->
          <div ref="muniChartEl" class="detail-chart"></div>

          <!-- Departamentos que intervienen -->
          <h5 class="detail-subtitle">Departamentos que intervienen</h5>
          <div class="breakdown-list">
            <div v-for="d in detalleMunicipio.departamentos" :key="d.departamento_id" class="breakdown-item">
              <span class="breakdown-name">{{ d.departamento }}</span>
              <div class="breakdown-badges">
                <span class="badge">{{ d.total }}</span>
                <span class="badge badge-green">{{ d.completadas }}</span>
                <span class="badge badge-blue">{{ d.en_proceso }}</span>
              </div>
            </div>
            <div v-if="!detalleMunicipio.departamentos.length" class="empty-msg">Sin departamentos asignados</div>
          </div>
        </div>

        <div v-else class="detail-lista">
          <div class="detail-lista-header">
            <select v-model="muniFilterDept" class="detail-subfilter">
              <option :value="null">Todos los departamentos</option>
              <option v-for="d in muniDeptOptions" :key="d.departamento_id" :value="d.departamento_id">{{ d.departamento }} ({{ d.total }})</option>
            </select>
            <span class="detail-lista-info">{{ muniPeticionesFiltradas.length }} peticiones</span>
            <button class="csv-btn" @click="downloadCSV('municipio')" title="Descargar CSV">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
              CSV
            </button>
          </div>
          <div class="detail-table-wrap">
            <table class="detail-table">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Peticionario</th>
                  <th>Departamento</th>
                  <th>Estado</th>
                  <th>Importancia</th>
                  <th>Dias</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in muniPeticionesFiltradas" :key="p.id">
                  <td class="td-folio">{{ p.folio || '-' }}</td>
                  <td class="td-nombre">{{ p.nombre || 'Anonimo' }}</td>
                  <td class="td-desc">{{ p.departamentos_asignados || 'Sin asignar' }}</td>
                  <td><span class="estado-badge" :class="estadoClass(p.estado)">{{ p.estado }}</span></td>
                  <td><span class="imp-badge" :class="impClass(p.NivelImportancia)">{{ impLabel(p.NivelImportancia) }}</span></td>
                  <td class="td-dias" :class="{ 'td-dias--alerta': p.dias_transcurridos > 30 }">{{ p.dias_transcurridos }}d</td>
                  <td class="td-fecha">{{ formatDate(p.fecha_registro) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { useDashboardStore } from '@/composables/useDashboardStore.js'

const {
  departamentosList, municipiosList,
  topDepartamentos, topMunicipios,
  detalleDepartamento, detalleMunicipio,
  detalleLoading,
  selectDepartamento, selectMunicipio
} = useDashboardStore()

const tab = ref('dept')
const deptId = ref(null)
const muniId = ref(null)
const deptView = ref('resumen')
const muniView = ref('resumen')

// Sub-filtros cruzados
const deptFilterMuni = ref(null)   // Filtrar departamento por municipio
const muniFilterDept = ref(null)   // Filtrar municipio por departamento

// Municipios disponibles en el departamento seleccionado
const deptMuniOptions = computed(() => {
  if (!detalleDepartamento.value || !detalleDepartamento.value.municipios) return []
  return detalleDepartamento.value.municipios
})

// Departamentos disponibles en el municipio seleccionado
const muniDeptOptions = computed(() => {
  if (!detalleMunicipio.value || !detalleMunicipio.value.departamentos) return []
  return detalleMunicipio.value.departamentos
})

// Peticiones filtradas del departamento (por municipio)
const deptPeticionesFiltradas = computed(() => {
  if (!detalleDepartamento.value || !detalleDepartamento.value.peticiones) return []
  if (!deptFilterMuni.value) return detalleDepartamento.value.peticiones
  return detalleDepartamento.value.peticiones.filter(p => p.Municipio === deptFilterMuni.value)
})

// Peticiones filtradas del municipio (por departamento)
const muniPeticionesFiltradas = computed(() => {
  if (!detalleMunicipio.value || !detalleMunicipio.value.peticiones) return []
  if (!muniFilterDept.value) return detalleMunicipio.value.peticiones
  const deptId = String(muniFilterDept.value)
  return detalleMunicipio.value.peticiones.filter(p => {
    if (!p.departamento_ids) return false
    return p.departamento_ids.split(',').includes(deptId)
  })
})
const deptChartEl = ref(null)
const muniChartEl = ref(null)

let deptChart = null
let muniChart = null

function onSelectDept() {
  deptView.value = 'resumen'
  deptFilterMuni.value = null
  selectDepartamento(deptId.value)
}
function onSelectMuni() {
  muniView.value = 'resumen'
  muniFilterDept.value = null
  selectMunicipio(muniId.value)
}

const IMP_LABELS = { 1: 'Critica', 2: 'Alta', 3: 'Media' }

function truncate(str, len) {
  if (!str) return '-'
  return str.length > len ? str.substring(0, len) + '...' : str
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}

function estadoClass(estado) {
  const map = {
    'Sin revisar': 'estado--pendiente', 'Por asignar departamento': 'estado--pendiente',
    'Esperando recepción': 'estado--pendiente', 'Aceptada en proceso': 'estado--proceso',
    'Completado': 'estado--completado', 'Devuelto': 'estado--devuelto',
    'Rechazado por departamento': 'estado--devuelto',
    'Improcedente': 'estado--cerrado', 'Cancelada': 'estado--cerrado'
  }
  return map[estado] || ''
}

function estadoAsigClass(estado) {
  const map = {
    'Esperando recepción': 'estado--pendiente', 'Aceptado en proceso': 'estado--proceso',
    'Completado': 'estado--completado', 'Devuelto a seguimiento': 'estado--devuelto',
    'Rechazado': 'estado--devuelto'
  }
  return map[estado] || ''
}

function impClass(nivel) {
  if (nivel == 1) return 'imp--critica'
  if (nivel == 2) return 'imp--alta'
  if (nivel == 3) return 'imp--media'
  return 'imp--baja'
}

function impLabel(nivel) {
  return IMP_LABELS[nivel] || 'Baja'
}

function downloadCSV(tipo) {
  let peticiones, filename, headers, rows

  const escape = v => '"' + String(v).replace(/"/g, '""') + '"'

  if (tipo === 'departamento' && detalleDepartamento.value) {
    peticiones = deptPeticionesFiltradas.value
    const suffix = deptFilterMuni.value ? '_' + deptFilterMuni.value.replace(/\s+/g, '_') : ''
    filename = (detalleDepartamento.value.info.nombre_unidad || 'departamento').replace(/\s+/g, '_') + suffix + '.csv'
    headers = ['Folio', 'Peticionario', 'Municipio', 'Estado Peticion', 'Estado Asignacion', 'Importancia', 'Dias Asignado', 'Fecha Asignacion']
    rows = peticiones.map(p => [
      p.folio || '', p.nombre || 'Anonimo', p.Municipio || '',
      p.estado || '', p.estado_asignacion || '',
      IMP_LABELS[p.NivelImportancia] || 'Baja',
      p.dias_asignado || 0, p.fecha_asignacion || ''
    ])
  } else if (tipo === 'municipio' && detalleMunicipio.value) {
    peticiones = muniPeticionesFiltradas.value
    const deptOpt = muniDeptOptions.value.find(d => d.departamento_id === muniFilterDept.value)
    const suffix = deptOpt ? '_' + deptOpt.departamento.replace(/\s+/g, '_') : ''
    filename = (detalleMunicipio.value.info.Municipio || 'municipio').replace(/\s+/g, '_') + suffix + '.csv'
    headers = ['Folio', 'Peticionario', 'Departamento', 'Estado', 'Importancia', 'Dias', 'Fecha']
    rows = peticiones.map(p => [
      p.folio || '', p.nombre || 'Anonimo',
      p.departamentos_asignados || 'Sin asignar',
      p.estado || '', IMP_LABELS[p.NivelImportancia] || 'Baja',
      p.dias_transcurridos || 0, p.fecha_registro || ''
    ])
  } else {
    return
  }

  const csv = '\uFEFF' + [headers.map(escape).join(','), ...rows.map(r => r.map(escape).join(','))].join('\r\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

// Semaforo de salud: verde (>50% resolucion, pocas pendientes), amarillo, rojo
function healthClass(dept) {
  const tasa = Number(dept.tasa_resolucion || 0)
  const esperando = Number(dept.esperando || 0)
  const total = Number(dept.total_asignaciones || 0)
  const pctEsperando = total > 0 ? (esperando / total) * 100 : 0

  if (tasa >= 50 && pctEsperando < 50) return 'health-green'
  if (tasa >= 20 || pctEsperando < 80) return 'health-yellow'
  return 'health-red'
}

function barWidth(val, list, key = 'total_asignaciones') {
  const max = Math.max(...list.map(d => Number(d[key] || 0)), 1)
  return Math.round((Number(val) / max) * 100) + '%'
}

function rateClass(rate) {
  const r = Number(rate || 0)
  if (r >= 70) return 'success'
  if (r >= 30) return 'warning'
  return 'danger'
}

function _isDark() {
  return document.documentElement.classList.contains('dark-mode')
}

function renderMiniTimeline(el, chartRef, timelineData, label1, label2) {
  if (!el) return null
  if (chartRef) chartRef.dispose()

  const instance = echarts.init(el)
  const dk = _isDark()
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']

  const labels = timelineData.map(d => {
    const parts = d.fecha.split('-')
    return meses[parseInt(parts[1]) - 1] + ' ' + parts[0].slice(2)
  })

  instance.setOption({
    tooltip: {
      trigger: 'axis',
      backgroundColor: dk ? 'rgba(30,41,59,0.95)' : 'rgba(255,255,255,0.95)',
      borderColor: dk ? '#475569' : '#e5e7eb', borderWidth: 1, borderRadius: 8,
      textStyle: { color: dk ? '#e2e8f0' : '#1f2937', fontSize: 12 }
    },
    legend: {
      data: [label1, label2],
      bottom: 0, left: 'center',
      icon: 'circle', itemWidth: 8, itemHeight: 8,
      textStyle: { fontSize: 11, color: '#64748b' }
    },
    grid: { left: '8%', right: '4%', top: '8%', bottom: '22%' },
    xAxis: {
      type: 'category', data: labels, boundaryGap: false,
      axisLine: { show: false }, axisTick: { show: false },
      axisLabel: { fontSize: 10, color: '#94a3b8', hideOverlap: true }
    },
    yAxis: {
      type: 'value',
      axisLine: { show: false },
      axisLabel: { fontSize: 10, color: '#94a3b8' },
      splitLine: { lineStyle: { color: dk ? '#334155' : '#f1f5f9' } }
    },
    series: [
      {
        name: label1, type: 'bar', data: timelineData.map(d => Number(d.nuevas || 0)),
        barWidth: 12,
        itemStyle: { color: '#3b82f6', borderRadius: [3, 3, 0, 0] }
      },
      {
        name: label2, type: 'line', data: timelineData.map(d => Number(d.completadas || 0)),
        smooth: true, symbol: 'circle', symbolSize: 6,
        lineStyle: { color: '#10b981', width: 2 },
        itemStyle: { color: '#10b981', borderWidth: 2, borderColor: '#fff' },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(16,185,129,0.25)' },
            { offset: 1, color: 'rgba(16,185,129,0.02)' }
          ])
        }
      }
    ]
  })

  return instance
}

// Watch detalle departamento para renderizar chart
watch(detalleDepartamento, async (val) => {
  if (val && val.timeline && val.timeline.length > 0) {
    await nextTick()
    deptChart = renderMiniTimeline(deptChartEl.value, deptChart, val.timeline, 'Asignadas', 'Completadas')
  }
}, { deep: true })

watch(detalleMunicipio, async (val) => {
  if (val && val.timeline && val.timeline.length > 0) {
    await nextTick()
    muniChart = renderMiniTimeline(muniChartEl.value, muniChart, val.timeline, 'Nuevas', 'Completadas')
  }
}, { deep: true })

function onThemeChange() {
  if (deptChart && detalleDepartamento.value && detalleDepartamento.value.timeline) {
    deptChart = renderMiniTimeline(deptChartEl.value, deptChart, detalleDepartamento.value.timeline, 'Asignadas', 'Completadas')
  }
  if (muniChart && detalleMunicipio.value && detalleMunicipio.value.timeline) {
    muniChart = renderMiniTimeline(muniChartEl.value, muniChart, detalleMunicipio.value.timeline, 'Nuevas', 'Completadas')
  }
}

onMounted(() => {
  window.addEventListener('dashboard-theme-change', onThemeChange)
})

onUnmounted(() => {
  window.removeEventListener('dashboard-theme-change', onThemeChange)
  if (deptChart) deptChart.dispose()
  if (muniChart) muniChart.dispose()
})
</script>

<style scoped>
.analysis-panel {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.analysis-tabs {
  display: flex;
  border-bottom: 2px solid #f1f5f9;
}

.analysis-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 16px;
  border: none;
  background: transparent;
  font-size: 0.9rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
}

.analysis-tab:hover { color: #1e40af; background: #f8fafc; }

.analysis-tab.active {
  color: #1e40af;
  border-bottom-color: #3b82f6;
  background: #fafbff;
}

.analysis-content {
  padding: 1.25rem;
  max-height: 700px;
  overflow-y: auto;
}

.selector-row { margin-bottom: 1rem; }

.analysis-select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 500;
  color: #1e293b;
  background: #f8fafc;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
}

.analysis-select:focus { border-color: #3b82f6; }

/* Ranking */
.ranking-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 0.75rem 0;
}

.ranking-list { display: flex; flex-direction: column; gap: 6px; }

.ranking-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
}

.ranking-item:hover { background: #f1f5f9; }

/* Semaforo de salud */
.health-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.health-green { background: #10b981; box-shadow: 0 0 4px rgba(16,185,129,0.4); }
.health-yellow { background: #f59e0b; box-shadow: 0 0 4px rgba(245,158,11,0.4); }
.health-red { background: #ef4444; box-shadow: 0 0 4px rgba(239,68,68,0.4); }

.ranking-pos {
  width: 24px; height: 24px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.ranking-info { flex: 1; min-width: 0; }

.ranking-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #1e293b;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.ranking-bar-bg {
  height: 4px;
  background: #f1f5f9;
  border-radius: 2px;
  overflow: hidden;
}

.ranking-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #1e40af);
  border-radius: 2px;
  animation: barGrow 0.8s ease-out forwards;
  transform-origin: left;
}

@keyframes barGrow {
  from { transform: scaleX(0); }
  to { transform: scaleX(1); }
}

.ranking-bar-muni {
  background: linear-gradient(90deg, #10b981, #059669);
}

.ranking-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  flex-shrink: 0;
}

.ranking-total {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e293b;
}

.ranking-rate {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 1px 6px;
  border-radius: 4px;
}

.ranking-urgentes {
  font-size: 0.7rem;
  font-weight: 600;
  color: #ef4444;
}

/* Detail view */
.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.detail-header h4 {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.clear-btn {
  padding: 6px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  color: #64748b;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.clear-btn:hover { background: #f1f5f9; color: #1e293b; }

.detail-kpis {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  margin-bottom: 1rem;
}

.detail-kpi {
  text-align: center;
  padding: 10px 6px;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #f1f5f9;
}

.detail-kpi-val {
  display: block;
  font-size: 1.3rem;
  font-weight: 700;
  color: #1e293b;
}

.detail-kpi-val.success { color: #10b981; }
.detail-kpi-val.warning { color: #f59e0b; }
.detail-kpi-val.danger { color: #ef4444; }

.detail-kpi-label {
  font-size: 0.68rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  font-weight: 600;
}

.detail-chart {
  height: 200px;
  margin-bottom: 1rem;
}

.detail-subtitle {
  font-size: 0.78rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 0.6rem 0;
  padding-top: 0.6rem;
  border-top: 1px solid #f1f5f9;
}

.breakdown-list { display: flex; flex-direction: column; gap: 4px; }

.breakdown-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 7px 10px;
  border-radius: 6px;
  transition: background 0.15s;
}

.breakdown-item:hover { background: #f8fafc; }

.breakdown-name {
  font-size: 0.82rem;
  font-weight: 500;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 55%;
}

.breakdown-badges { display: flex; gap: 4px; }

.badge {
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #475569;
}

.badge-green { background: #dcfce7; color: #166534; }
.badge-yellow { background: #fef9c3; color: #854d0e; }
.badge-blue { background: #dbeafe; color: #1e40af; }

.empty-msg {
  text-align: center;
  padding: 1.5rem;
  color: #94a3b8;
  font-size: 0.85rem;
}

.success { color: #10b981; }
.warning { color: #f59e0b; }
.danger { color: #ef4444; }

/* Loading */
.detail-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 40px 20px;
  color: #6b7280;
  font-size: 0.85rem;
}
.detail-spinner {
  width: 22px; height: 22px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Subtabs */
.detail-subtabs {
  display: flex;
  gap: 4px;
  margin-bottom: 1rem;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 3px;
}
.detail-subtab {
  flex: 1;
  padding: 7px 12px;
  border: none;
  border-radius: 6px;
  background: transparent;
  font-size: 0.78rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.detail-subtab:hover { color: #1e293b; }
.detail-subtab.active { background: white; color: #1e40af; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.subtab-count {
  background: #dbeafe;
  color: #1e40af;
  padding: 1px 7px;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 700;
}

/* Detail lista */
.detail-lista-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.detail-lista-info {
  font-size: 0.78rem;
  color: #64748b;
  font-weight: 600;
}
.detail-subfilter {
  padding: 5px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 500;
  color: #1e293b;
  background: #f8fafc;
  cursor: pointer;
  outline: none;
  max-width: 200px;
  transition: border-color 0.15s;
}
.detail-subfilter:focus { border-color: #3b82f6; }

.csv-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  background: #f0fdf4;
  border: 1px solid #86efac;
  border-radius: 6px;
  padding: 5px 12px;
  cursor: pointer;
  color: #166534;
  font-size: 0.75rem;
  font-weight: 600;
  transition: all 0.15s;
}
.csv-btn:hover { background: #dcfce7; border-color: #4ade80; }

.detail-table-wrap {
  overflow-x: auto;
  max-height: 380px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}
.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.76rem;
}
.detail-table thead { position: sticky; top: 0; z-index: 1; }
.detail-table th {
  background: #f8fafc;
  padding: 8px 10px;
  text-align: left;
  font-weight: 600;
  color: #475569;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}
.detail-table td {
  padding: 7px 10px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}
.detail-table tbody tr:hover { background: #f8fafc; }

.td-folio { font-weight: 600; color: #2563eb; white-space: nowrap; }
.td-nombre { font-weight: 500; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.td-desc { max-width: 150px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.td-fecha { white-space: nowrap; color: #64748b; }
.td-dias { font-weight: 600; white-space: nowrap; }
.td-dias--alerta { color: #dc2626; }

.estado-badge {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 8px;
  font-size: 0.68rem;
  font-weight: 600;
  white-space: nowrap;
}
.estado--pendiente { background: #fef3c7; color: #92400e; }
.estado--proceso { background: #dbeafe; color: #1e40af; }
.estado--completado { background: #d1fae5; color: #065f46; }
.estado--devuelto { background: #fee2e2; color: #991b1b; }
.estado--cerrado { background: #f1f5f9; color: #475569; }

.imp-badge {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 8px;
  font-size: 0.68rem;
  font-weight: 600;
  white-space: nowrap;
}
.imp--critica { background: #fee2e2; color: #991b1b; }
.imp--alta { background: #ffedd5; color: #9a3412; }
.imp--media { background: #fef3c7; color: #92400e; }
.imp--baja { background: #f1f5f9; color: #475569; }

/* Dark mode */
:global(.dark-mode) .detail-subtabs { background: #334155; }
:global(.dark-mode) .detail-subtab { color: #94a3b8; }
:global(.dark-mode) .detail-subtab:hover { color: #e2e8f0; }
:global(.dark-mode) .detail-subtab.active { background: #1e293b; color: #60a5fa; box-shadow: none; }
:global(.dark-mode) .subtab-count { background: rgba(59,130,246,0.2); color: #93c5fd; }
:global(.dark-mode) .detail-subfilter { background: #0f172a; border-color: #334155; color: #e2e8f0; }
:global(.dark-mode) .detail-lista-info { color: #94a3b8; }
:global(.dark-mode) .csv-btn { background: #14532d; color: #86efac; border-color: #166534; }
:global(.dark-mode) .csv-btn:hover { background: #166534; border-color: #22c55e; }
:global(.dark-mode) .detail-table-wrap { border-color: #334155; }
:global(.dark-mode) .detail-table th { background: #1a2332; color: #94a3b8; border-color: #334155; }
:global(.dark-mode) .detail-table td { color: #cbd5e1; border-color: #1e293b; }
:global(.dark-mode) .detail-table tbody tr:hover { background: #1a2332; }
:global(.dark-mode) .td-folio { color: #60a5fa; }
:global(.dark-mode) .td-nombre { color: #e2e8f0; }
:global(.dark-mode) .td-desc { color: #94a3b8; }
:global(.dark-mode) .td-fecha { color: #94a3b8; }
:global(.dark-mode) .td-dias { color: #e2e8f0; }
:global(.dark-mode) .td-dias--alerta { color: #f87171; }
:global(.dark-mode) .detail-loading { color: #94a3b8; }
:global(.dark-mode) .detail-chart { background: transparent; }
:global(.dark-mode) .estado-badge { color: inherit; }
:global(.dark-mode) .estado--pendiente { background: rgba(245,158,11,0.15); color: #fcd34d; }
:global(.dark-mode) .estado--proceso { background: rgba(59,130,246,0.15); color: #93c5fd; }
:global(.dark-mode) .estado--completado { background: rgba(16,185,129,0.15); color: #6ee7b7; }
:global(.dark-mode) .estado--devuelto { background: rgba(239,68,68,0.15); color: #fca5a5; }
:global(.dark-mode) .estado--cerrado { background: rgba(148,163,184,0.1); color: #94a3b8; }
:global(.dark-mode) .imp-badge { color: inherit; }
:global(.dark-mode) .imp--critica { background: rgba(239,68,68,0.15); color: #fca5a5; }
:global(.dark-mode) .imp--alta { background: rgba(249,115,22,0.15); color: #fdba74; }
:global(.dark-mode) .imp--media { background: rgba(245,158,11,0.15); color: #fcd34d; }
:global(.dark-mode) .imp--baja { background: rgba(148,163,184,0.1); color: #94a3b8; }
:global(.dark-mode) .analysis-content { color: #e2e8f0; }

@media (max-width: 768px) {
  .detail-kpis { grid-template-columns: repeat(2, 1fr); }
}
</style>
