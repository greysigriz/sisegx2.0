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

      <!-- Sin seleccion: mostrar ranking -->
      <div v-if="!detalleDepartamento" class="ranking-view">
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
    </div>

    <!-- MUNICIPIO -->
    <div v-if="tab === 'muni'" class="analysis-content">
      <div class="selector-row">
        <select v-model="muniId" class="analysis-select" @change="onSelectMuni">
          <option :value="null">Selecciona un municipio...</option>
          <option v-for="m in municipiosList" :key="m.Id" :value="m.Id">{{ m.Municipio }}</option>
        </select>
      </div>

      <!-- Sin seleccion: mostrar ranking -->
      <div v-if="!detalleMunicipio" class="ranking-view">
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
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { useDashboardStore } from '@/composables/useDashboardStore.js'

const {
  departamentosList, municipiosList,
  topDepartamentos, topMunicipios,
  detalleDepartamento, detalleMunicipio,
  selectDepartamento, selectMunicipio
} = useDashboardStore()

const tab = ref('dept')
const deptId = ref(null)
const muniId = ref(null)
const deptChartEl = ref(null)
const muniChartEl = ref(null)

let deptChart = null
let muniChart = null

function onSelectDept() {
  selectDepartamento(deptId.value)
}
function onSelectMuni() {
  selectMunicipio(muniId.value)
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

function renderMiniTimeline(el, chartRef, timelineData, label1, label2) {
  if (!el) return null
  if (chartRef) chartRef.dispose()

  const instance = echarts.init(el)
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']

  const labels = timelineData.map(d => {
    const parts = d.fecha.split('-')
    return meses[parseInt(parts[1]) - 1] + ' ' + parts[0].slice(2)
  })

  instance.setOption({
    tooltip: {
      trigger: 'axis',
      backgroundColor: 'rgba(255,255,255,0.95)',
      borderColor: '#e5e7eb', borderWidth: 1, borderRadius: 8,
      textStyle: { color: '#1f2937', fontSize: 12 }
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
      splitLine: { lineStyle: { color: '#f1f5f9' } }
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

onUnmounted(() => {
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

@media (max-width: 768px) {
  .detail-kpis { grid-template-columns: repeat(2, 1fr); }
}
</style>
