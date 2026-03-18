<template>
  <div class="side-panel">
    <!-- Donut de importancia -->
    <div class="side-panel__section">
      <h3 class="side-panel__title">Por Prioridad</h3>
      <div ref="donutEl" class="side-panel__donut"></div>
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

const donutEl = ref(null)
let donutInstance = null

const { porImportancia, recientes } = useDashboardStore()

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

  donutInstance.setOption({
    tooltip: {
      trigger: 'item',
      backgroundColor: 'rgba(255,255,255,0.95)',
      borderColor: '#e5e7eb', borderWidth: 1, borderRadius: 8,
      textStyle: { color: '#1f2937', fontSize: 12 },
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
        fill: '#1e293b',
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
      itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
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
  renderDonut()
}

const resize = () => { if (donutInstance) donutInstance.resize() }

watch(porImportancia, async () => {
  await nextTick()
  if (!donutInstance && donutEl.value) donutInstance = echarts.init(donutEl.value)
  renderDonut()
}, { deep: true })

onMounted(() => {
  useVisibilityReflow()
  if (porImportancia.value && porImportancia.value.length > 0) initDonut()
  window.addEventListener('resize', resize)
})

onUnmounted(() => {
  window.removeEventListener('resize', resize)
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
</style>
