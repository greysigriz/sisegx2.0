<template>
  <div class="side-panel">
    <!-- Donut de importancia -->
    <div class="side-panel__section">
      <h3 class="side-panel__title">Por Prioridad</h3>
      <div ref="donutEl" class="side-panel__donut"></div>
      <!-- Legend clickeable -->
      <div class="prio-legend">
        <button v-for="p in porImportancia" :key="p.NivelImportancia"
          class="prio-legend-item"
          :class="{ 'prio-legend-item--active': selectedPrio === Number(p.NivelImportancia) }"
          @click="onPrioClick(Number(p.NivelImportancia))">
          <span class="prio-legend-dot" :style="{ background: prioColor(p.NivelImportancia) }"></span>
          <span class="prio-legend-name">{{ prioName(p.NivelImportancia) }}</span>
          <span class="prio-legend-val">{{ p.cantidad }}</span>
        </button>
      </div>

      <!-- Panel desplegable -->
      <Transition name="slide-prio">
        <div v-if="selectedPrio" class="prio-detalle">
          <div v-if="prioLoading" class="prio-detalle-loading">
            <div class="prio-spinner"></div>
            <span>Cargando...</span>
          </div>
          <div v-else-if="prioData && prioData.peticiones.length > 0">
            <div class="prio-detalle-header">
              <span class="prio-detalle-title">{{ prioName(selectedPrio) }}</span>
              <span class="prio-detalle-count">{{ prioData.peticiones.length }}</span>
              <button class="prio-csv-btn" @click="downloadPrioCSV" title="Descargar CSV">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                CSV
              </button>
              <button class="prio-close-btn" @click="selectedPrio = null" title="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
              </button>
            </div>
            <div class="prio-table-wrap">
              <table class="prio-table">
                <thead>
                  <tr>
                    <th>Folio</th>
                    <th>Peticionario</th>
                    <th>Municipio</th>
                    <th>Estado</th>
                    <th>Dias</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pet in prioData.peticiones" :key="pet.id">
                    <td class="td-folio">{{ pet.folio || '-' }}</td>
                    <td class="td-nombre">{{ pet.nombre || 'Anonimo' }}</td>
                    <td>{{ pet.Municipio || '-' }}</td>
                    <td><span class="estado-badge" :class="estadoClass(pet.estado)">{{ shortStatus(pet.estado) }}</span></td>
                    <td class="td-dias" :class="{ 'td-dias--alerta': pet.dias_transcurridos > 30 }">{{ pet.dias_transcurridos }}d</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else class="prio-detalle-empty">Sin peticiones.</div>
        </div>
      </Transition>
    </div>

    <!-- Feed de actividad reciente -->
    <div class="side-panel__section">
      <h3 class="side-panel__title">Actividad Reciente</h3>
      <div class="feed-list">
        <div v-for="p in recientes.slice(0, 6)" :key="p.id" class="feed-item">
          <span class="feed-dot" :style="{ background: prioColor(p.NivelImportancia) }"></span>
          <div class="feed-content">
            <span class="feed-name">{{ p.nombre }}</span>
            <span class="feed-meta">{{ p.Municipio }} · {{ timeAgo(p.fecha_registro) }}</span>
          </div>
          <span class="feed-status" :class="'feed-status--' + statusKey(p.estado)">{{ shortStatus(p.estado) }}</span>
        </div>
        <div v-if="!recientes || recientes.length === 0" class="feed-empty">Sin actividad reciente</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import * as echarts from 'echarts'
import { useDashboardStore } from '@/composables/useDashboardStore.js'
import { useVisibilityReflow } from '@/composables/useVisibilityReflow.js'
import axios from '@/services/axios-config.js'

const donutEl = ref(null)
let donutInstance = null

const { porImportancia, recientes } = useDashboardStore()

const selectedPrio = ref(null)
const prioLoading = ref(false)
const prioData = ref(null)

const prioConfig = [
  { nivel: 1, name: 'Critica', color: '#ef4444' },
  { nivel: 2, name: 'Alta', color: '#f59e0b' },
  { nivel: 3, name: 'Media', color: '#3b82f6' },
  { nivel: 4, name: 'Baja', color: '#10b981' },
  { nivel: 5, name: 'Muy Baja', color: '#94a3b8' }
]

function prioColor(nivel) {
  return prioConfig.find(p => p.nivel === Number(nivel))?.color || '#94a3b8'
}

function prioName(nivel) {
  return prioConfig.find(p => p.nivel === Number(nivel))?.name || 'Otro'
}

async function onPrioClick(nivel) {
  if (selectedPrio.value === nivel) {
    selectedPrio.value = null
    prioData.value = null
    return
  }
  selectedPrio.value = nivel
  prioLoading.value = true
  prioData.value = null
  try {
    const res = await axios.get('dashboard-director.php', {
      params: { card_detalle: 'importancia_' + nivel, skip_lists: 1, dias: 9999, _nonce: Date.now() }
    })
    if (res.data && res.data.success && res.data.card_detalle) {
      prioData.value = res.data.card_detalle
    }
  } catch (err) {
    console.error('Error prioridad detalle:', err)
  } finally {
    prioLoading.value = false
  }
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

function downloadPrioCSV() {
  if (!prioData.value || !prioData.value.peticiones.length) return
  const escape = v => '"' + String(v).replace(/"/g, '""') + '"'
  const headers = ['Folio', 'Peticionario', 'Descripcion', 'Municipio', 'Estado', 'Dias', 'Fecha']
  const rows = prioData.value.peticiones.map(p => [
    p.folio || '', p.nombre || 'Anonimo',
    (p.descripcion || '').replace(/[\r\n]+/g, ' '),
    p.Municipio || '', p.estado || '',
    p.dias_transcurridos || 0, p.fecha_registro || ''
  ])
  const csv = '\uFEFF' + [headers.map(escape).join(','), ...rows.map(r => r.map(escape).join(','))].join('\r\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'Prioridad_' + prioName(selectedPrio.value) + '.csv'
  a.click()
  URL.revokeObjectURL(url)
}

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 60) return mins + 'min'
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return hrs + 'h'
  const days = Math.floor(hrs / 24)
  if (days < 30) return days + 'd'
  return Math.floor(days / 30) + 'mes'
}

function shortStatus(estado) {
  const map = {
    'Sin revisar': 'Nueva',
    'Por asignar departamento': 'Por asignar',
    'Esperando recepción': 'Esperando',
    'Aceptada en proceso': 'En proceso',
    'Completado': 'Completada',
    'Devuelto': 'Devuelta',
    'Rechazado por departamento': 'Rechazada',
    'Improcedente': 'Improcedente',
    'Cancelada': 'Cancelada'
  }
  return map[estado] || estado
}

function statusKey(estado) {
  if (['Sin revisar'].includes(estado)) return 'new'
  if (['Por asignar departamento', 'Esperando recepción'].includes(estado)) return 'pending'
  if (['Aceptada en proceso'].includes(estado)) return 'progress'
  if (['Completado'].includes(estado)) return 'done'
  return 'other'
}

function renderDonut() {
  if (!donutInstance || !porImportancia.value || porImportancia.value.length === 0) return

  const data = porImportancia.value.map(item => {
    const cfg = prioConfig.find(p => p.nivel === Number(item.NivelImportancia)) || prioConfig[2]
    return {
      value: Number(item.cantidad),
      name: cfg.name,
      itemStyle: { color: cfg.color }
    }
  })

  const total = data.reduce((s, d) => s + d.value, 0)

  const dk = document.documentElement.classList.contains('dark-mode')
  donutInstance.setOption({
    tooltip: {
      trigger: 'item',
      backgroundColor: dk ? 'rgba(30,41,59,0.95)' : 'rgba(255,255,255,0.95)',
      borderColor: dk ? '#475569' : '#e5e7eb', borderWidth: 1, borderRadius: 8,
      textStyle: { color: dk ? '#e2e8f0' : '#1f2937', fontSize: 12 },
      formatter: p => `<strong>${p.name}</strong><br/>${p.value} (${p.percent}%)`
    },
    graphic: [{
      type: 'text',
      left: 'center',
      top: '42%',
      style: {
        text: String(total),
        fontSize: 22,
        fontWeight: 800,
        fill: document.documentElement.classList.contains('dark-mode') ? '#e2e8f0' : '#1e293b',
        textAlign: 'center'
      }
    }, {
      type: 'text',
      left: 'center',
      top: '54%',
      style: {
        text: 'total',
        fontSize: 10,
        fill: '#94a3b8',
        textAlign: 'center',
        textTransform: 'uppercase'
      }
    }],
    series: [{
      type: 'pie',
      radius: ['55%', '78%'],
      center: ['50%', '50%'],
      avoidLabelOverlap: true,
      padAngle: 3,
      itemStyle: { borderRadius: 6, borderColor: document.documentElement.classList.contains('dark-mode') ? '#1e293b' : '#fff', borderWidth: 2 },
      label: { show: false },
      emphasis: {
        label: { show: false },
        itemStyle: { shadowBlur: 8, shadowColor: 'rgba(0,0,0,0.12)' }
      },
      data
    }]
  }, true)
}

function initDonut() {
  if (!donutEl.value || donutEl.value.clientWidth === 0) return
  donutInstance = echarts.init(donutEl.value)
  donutInstance.on('click', (params) => {
    const cfg = prioConfig.find(p => p.name === params.name)
    if (cfg) onPrioClick(cfg.nivel)
  })
  renderDonut()
}

const resize = () => { if (donutInstance) donutInstance.resize() }

watch(porImportancia, async () => {
  await nextTick()
  if (!donutInstance && donutEl.value) donutInstance = echarts.init(donutEl.value)
  renderDonut()
}, { deep: true })

function onThemeChange() {
  if (donutInstance) renderDonut()
}

onMounted(() => {
  useVisibilityReflow()
  if (porImportancia.value && porImportancia.value.length > 0) initDonut()
  window.addEventListener('resize', resize)
  window.addEventListener('dashboard-theme-change', onThemeChange)
})

onUnmounted(() => {
  window.removeEventListener('resize', resize)
  window.removeEventListener('dashboard-theme-change', onThemeChange)
  if (donutInstance) donutInstance.dispose()
})
</script>

<style scoped>
.side-panel {
  background: white;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.side-panel__section {
  padding: 1rem 1.25rem;
}

.side-panel__section + .side-panel__section {
  border-top: 1px solid #f1f5f9;
}

.side-panel__title {
  font-size: 0.78rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.side-panel__donut {
  height: 180px;
}

/* Feed */
.feed-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.feed-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 4px;
  border-radius: 6px;
  transition: background 0.15s;
}

.feed-item:hover {
  background: #f8fafc;
}

.feed-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.feed-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.feed-name {
  font-size: 0.78rem;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.feed-meta {
  font-size: 0.65rem;
  color: #94a3b8;
}

.feed-status {
  font-size: 0.65rem;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
  white-space: nowrap;
  flex-shrink: 0;
}

.feed-status--new { background: #ede9fe; color: #5b21b6; }
.feed-status--pending { background: #fef3c7; color: #92400e; }
.feed-status--progress { background: #dbeafe; color: #1e40af; }
.feed-status--done { background: #dcfce7; color: #166534; }
.feed-status--other { background: #f1f5f9; color: #475569; }

.feed-empty {
  text-align: center;
  padding: 1.5rem;
  color: #94a3b8;
  font-size: 0.82rem;
}

/* Priority legend */
.prio-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 6px;
}
.prio-legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  font-size: 0.72rem;
  transition: all 0.15s;
  flex: 1;
  min-width: 0;
}
.prio-legend-item:hover { background: #f1f5f9; border-color: #cbd5e1; }
.prio-legend-item--active { background: #eff6ff; border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; }
.prio-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.prio-legend-name { font-weight: 600; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prio-legend-val { margin-left: auto; font-weight: 700; color: #1e293b; flex-shrink: 0; }

/* Transition */
.slide-prio-enter-active, .slide-prio-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}
.slide-prio-enter-from, .slide-prio-leave-to {
  opacity: 0;
  max-height: 0;
}
.slide-prio-enter-to, .slide-prio-leave-from {
  opacity: 1;
  max-height: 500px;
}

/* Priority detail panel */
.prio-detalle {
  margin-top: 10px;
  border: 1px solid #dbeafe;
  border-radius: 10px;
  overflow: hidden;
  background: white;
}
.prio-detalle-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px;
  color: #6b7280;
  font-size: 0.8rem;
}
.prio-spinner {
  width: 18px; height: 18px;
  border: 2px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.prio-detalle-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f0f6ff;
  border-bottom: 1px solid #dbeafe;
}
.prio-detalle-title { font-weight: 700; font-size: 0.78rem; color: #1e3a8a; }
.prio-detalle-count {
  font-size: 0.7rem; font-weight: 700;
  background: white; color: #475569;
  padding: 1px 8px; border-radius: 10px;
}
.prio-csv-btn {
  margin-left: auto;
  display: flex; align-items: center; gap: 3px;
  background: #f0fdf4; border: 1px solid #86efac;
  border-radius: 5px; padding: 3px 8px;
  cursor: pointer; color: #166534;
  font-size: 0.68rem; font-weight: 600;
  transition: all 0.15s;
}
.prio-csv-btn:hover { background: #dcfce7; border-color: #4ade80; }
.prio-close-btn {
  background: none; border: 1px solid #cbd5e1;
  border-radius: 5px; padding: 3px;
  cursor: pointer; color: #64748b;
  display: flex; align-items: center;
  transition: all 0.15s;
}
.prio-close-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

.prio-table-wrap {
  overflow-x: auto;
  max-height: 280px;
  overflow-y: auto;
}
.prio-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.73rem;
}
.prio-table thead { position: sticky; top: 0; z-index: 1; }
.prio-table th {
  background: #f8fafc; padding: 6px 10px;
  text-align: left; font-weight: 600; color: #475569;
  font-size: 0.68rem; text-transform: uppercase;
  letter-spacing: 0.03em; border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}
.prio-table td {
  padding: 6px 10px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}
.prio-table tbody tr:hover { background: #f8fafc; }
.td-folio { font-weight: 600; color: #2563eb; white-space: nowrap; }
.td-nombre { font-weight: 500; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.td-dias { font-weight: 600; white-space: nowrap; }
.td-dias--alerta { color: #dc2626; }

.estado-badge {
  display: inline-block; padding: 2px 6px; border-radius: 6px;
  font-size: 0.65rem; font-weight: 600; white-space: nowrap;
}
.estado--pendiente { background: #fef3c7; color: #92400e; }
.estado--proceso { background: #dbeafe; color: #1e40af; }
.estado--completado { background: #d1fae5; color: #065f46; }
.estado--devuelto { background: #fee2e2; color: #991b1b; }
.estado--cerrado { background: #f1f5f9; color: #475569; }

.prio-detalle-empty {
  padding: 20px;
  text-align: center;
  color: #9ca3af;
  font-size: 0.8rem;
}

/* Dark mode — base */
:global(html.dark-mode .side-panel) { background: #1e293b; border-color: #334155; }
:global(html.dark-mode .side-panel__title) { color: #e2e8f0; }
:global(html.dark-mode .side-panel__section + .side-panel__section) { border-top-color: #334155; }
:global(html.dark-mode .feed-item:hover) { background: #0f172a; }
:global(html.dark-mode .feed-name) { color: #e2e8f0; }
:global(html.dark-mode .feed-meta) { color: #64748b; }
:global(html.dark-mode .feed-status--new) { background: rgba(139,92,246,0.15); color: #c4b5fd; }
:global(html.dark-mode .feed-status--pending) { background: rgba(245,158,11,0.15); color: #fcd34d; }
:global(html.dark-mode .feed-status--progress) { background: rgba(59,130,246,0.15); color: #93c5fd; }
:global(html.dark-mode .feed-status--done) { background: rgba(16,185,129,0.15); color: #6ee7b7; }
:global(html.dark-mode .feed-status--other) { background: rgba(148,163,184,0.1); color: #94a3b8; }

/* Dark mode — prio detail */
:global(html.dark-mode .prio-legend-item) { background: #334155; border-color: #475569; }
:global(html.dark-mode .prio-legend-item:hover) { background: #3b4f6b; border-color: #64748b; }
:global(html.dark-mode .prio-legend-item--active) { background: #1e3a5f; border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; }
:global(html.dark-mode .prio-legend-name) { color: #cbd5e1; }
:global(html.dark-mode .prio-legend-val) { color: #e2e8f0; }
:global(html.dark-mode .prio-detalle) { background: #1e293b; border-color: #334155; }
:global(html.dark-mode .prio-detalle-header) { background: #1a2332; border-color: #334155; }
:global(html.dark-mode .prio-detalle-title) { color: #93c5fd; }
:global(html.dark-mode .prio-detalle-count) { background: #334155; color: #94a3b8; }
:global(html.dark-mode .prio-csv-btn) { background: #14532d; color: #86efac; border-color: #166534; }
:global(html.dark-mode .prio-csv-btn:hover) { background: #166534; border-color: #22c55e; }
:global(html.dark-mode .prio-close-btn) { color: #94a3b8; border-color: #475569; }
:global(html.dark-mode .prio-close-btn:hover) { background: #7f1d1d; color: #fca5a5; border-color: #991b1b; }
:global(html.dark-mode .prio-table th) { background: #1a2332; color: #94a3b8; border-color: #334155; }
:global(html.dark-mode .prio-table td) { color: #cbd5e1; border-color: #1e293b; }
:global(html.dark-mode .prio-table tbody tr:hover) { background: #1a2332; }
:global(html.dark-mode .prio-table .td-folio) { color: #60a5fa; }
:global(html.dark-mode .prio-table .td-nombre) { color: #e2e8f0; }
:global(html.dark-mode .prio-table .td-dias) { color: #e2e8f0; }
:global(html.dark-mode .prio-table .td-dias--alerta) { color: #f87171; }
:global(html.dark-mode .prio-detalle-loading) { color: #94a3b8; }
:global(html.dark-mode .prio-detalle-empty) { color: #64748b; }
:global(html.dark-mode .estado-badge) { color: inherit; }
:global(html.dark-mode .estado--pendiente) { background: rgba(245,158,11,0.15); color: #fcd34d; }
:global(html.dark-mode .estado--proceso) { background: rgba(59,130,246,0.15); color: #93c5fd; }
:global(html.dark-mode .estado--completado) { background: rgba(16,185,129,0.15); color: #6ee7b7; }
:global(html.dark-mode .estado--devuelto) { background: rgba(239,68,68,0.15); color: #fca5a5; }
:global(html.dark-mode .estado--cerrado) { background: rgba(148,163,184,0.1); color: #94a3b8; }
</style>
