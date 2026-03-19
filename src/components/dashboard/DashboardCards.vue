<template>
  <div class="dashboard-metrics">
    <div v-if="isLoading && !kpis" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando...</p>
    </div>

    <div v-else-if="kpis">
      <div class="resumen-header">
        <div v-for="card in cards" :key="card.key"
          class="resumen-item"
          :class="{ 'resumen-item--active': selectedCard === card.key, 'resumen-item--clickable': card.clickable }"
          @click="card.clickable ? onCardClick(card.key) : null">
          <span class="resumen-label">{{ card.label }}</span>
          <span class="resumen-valor" :class="card.colorClass">
            <AnimNum v-if="typeof card.value === 'number'" :value="card.value" />
            <template v-else>{{ card.value }}</template>
          </span>
          <span v-if="card.trend !== null" class="resumen-trend" :class="card.trendClass">
            <svg v-if="card.trend > 0" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
            <svg v-else-if="card.trend < 0" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            <span v-else class="trend-dash">—</span>
            {{ card.trendLabel }}
          </span>
          <svg v-if="card.clickable" class="resumen-chevron" :class="{ 'resumen-chevron--open': selectedCard === card.key }" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </div>
      </div>

      <!-- Panel desplegable con detalle -->
      <Transition name="slide-detail">
        <div v-if="selectedCard" class="card-detalle-panel">
          <div v-if="cardDetalleLoading" class="card-detalle-loading">
            <div class="loading-spinner-sm"></div>
            <span>Cargando peticiones...</span>
          </div>
          <div v-else-if="cardDetalle && cardDetalle.peticiones.length > 0" class="card-detalle-content">
            <div class="card-detalle-header">
              <span class="card-detalle-title">{{ detalleTitle }}</span>
              <span class="card-detalle-count">{{ cardDetalle.peticiones.length }} peticiones</span>
              <button class="card-detalle-csv" @click="downloadCSV" title="Descargar CSV">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                <span>CSV</span>
              </button>
              <button class="card-detalle-close" @click="onCardClick(selectedCard)" title="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
              </button>
            </div>
            <div class="card-detalle-table-wrap">
              <table class="card-detalle-table">
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
                  <tr v-for="pet in cardDetalle.peticiones" :key="pet.id">
                    <td class="td-folio">{{ pet.folio || '-' }}</td>
                    <td class="td-nombre">{{ pet.nombre || 'Anonimo' }}</td>
                    <td class="td-desc">{{ truncate(pet.descripcion, 60) }}</td>
                    <td>{{ pet.Municipio || '-' }}</td>
                    <td><span class="estado-badge" :class="estadoClass(pet.estado)">{{ pet.estado }}</span></td>
                    <td><span class="imp-badge" :class="impClass(pet.NivelImportancia)">{{ impLabel(pet.NivelImportancia) }}</span></td>
                    <td class="td-dias" :class="{ 'td-dias--alerta': pet.dias_transcurridos > 30 }">{{ pet.dias_transcurridos }}d</td>
                    <td class="td-fecha">{{ formatDate(pet.fecha_registro) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else class="card-detalle-empty">
            No hay peticiones para esta categoria.
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script>
import '@/assets/css/cards_dashboard.css'
import { computed, defineComponent, h, ref, watch, onUnmounted } from 'vue'
import { useDashboardStore } from '@/composables/useDashboardStore.js'

const AnimNum = defineComponent({
  props: { value: { type: Number, default: 0 }, duration: { type: Number, default: 600 } },
  setup(props) {
    const display = ref(0)
    let raf = null

    function animate(from, to) {
      if (raf) cancelAnimationFrame(raf)
      const start = performance.now()
      const diff = to - from
      function step(now) {
        const p = Math.min((now - start) / props.duration, 1)
        const e = 1 - (1 - p) * (1 - p)
        display.value = Math.round(from + diff * e)
        if (p < 1) raf = requestAnimationFrame(step)
        else display.value = to
      }
      raf = requestAnimationFrame(step)
    }

    watch(() => props.value, (n, o) => {
      if (n !== o) animate(o || 0, n)
      else display.value = n
    }, { immediate: true })

    onUnmounted(() => { if (raf) cancelAnimationFrame(raf) })

    return () => h('span', display.value)
  }
})

const CARD_TITLES = {
  total: 'Todas las Peticiones',
  criticas: 'Peticiones Criticas',
  proceso: 'Peticiones En Proceso',
  completadas: 'Peticiones Completadas',
  retrasadas: 'Peticiones Retrasadas (+30 dias)'
}

export default {
  name: "DashboardCards",
  components: { AnimNum },
  setup() {
    const { kpis, kpisPrev, isLoading, selectedCard, cardDetalle, cardDetalleLoading, fetchCardDetalle } = useDashboardStore()

    function calcTrend(current, previous) {
      const c = Number(current || 0)
      const p = Number(previous || 0)
      if (p === 0 && c === 0) return { val: 0, label: 'sin cambio' }
      if (p === 0) return { val: 1, label: '+' + c }
      const pct = Math.round(((c - p) / p) * 100)
      if (pct === 0) return { val: 0, label: 'sin cambio' }
      return { val: pct, label: (pct > 0 ? '+' : '') + pct + '%' }
    }

    function trendClass(trendVal, invertido = false) {
      if (trendVal === 0) return 'trend-neutral'
      if (invertido) {
        return trendVal > 0 ? 'trend-bad' : 'trend-good'
      }
      return trendVal > 0 ? 'trend-good' : 'trend-bad'
    }

    const cards = computed(() => {
      if (!kpis.value) return []
      const prev = kpisPrev.value || {}

      const t_total = calcTrend(kpis.value.total_peticiones, prev.total_peticiones)
      const t_criticas = calcTrend(kpis.value.criticas, prev.criticas)
      const t_proceso = calcTrend(kpis.value.en_proceso, prev.en_proceso)
      const t_completadas = calcTrend(kpis.value.completadas, prev.completadas)
      const t_retrasadas = calcTrend(kpis.value.retrasadas, prev.retrasadas)

      return [
        {
          key: 'total', label: 'Total Peticiones',
          value: kpis.value.total_peticiones || 0,
          colorClass: '', clickable: true,
          trend: t_total.val, trendLabel: t_total.label,
          trendClass: trendClass(t_total.val, true)
        },
        {
          key: 'criticas', label: 'Criticas',
          value: kpis.value.criticas || 0,
          colorClass: 'danger', clickable: true,
          trend: t_criticas.val, trendLabel: t_criticas.label,
          trendClass: trendClass(t_criticas.val, true)
        },
        {
          key: 'proceso', label: 'En Proceso',
          value: kpis.value.en_proceso || 0,
          colorClass: 'info', clickable: true,
          trend: t_proceso.val, trendLabel: t_proceso.label,
          trendClass: trendClass(t_proceso.val, false)
        },
        {
          key: 'completadas', label: 'Completadas',
          value: kpis.value.completadas || 0,
          colorClass: 'success', clickable: true,
          trend: t_completadas.val, trendLabel: t_completadas.label,
          trendClass: trendClass(t_completadas.val, false)
        },
        {
          key: 'retrasadas', label: 'Retrasadas (+30d)',
          value: kpis.value.retrasadas || 0,
          colorClass: 'warning', clickable: true,
          trend: t_retrasadas.val, trendLabel: t_retrasadas.label,
          trendClass: trendClass(t_retrasadas.val, true)
        },
        {
          key: 'resolucion', label: 'Prom. Resolucion',
          value: kpis.value.promedio_dias_resolucion ? kpis.value.promedio_dias_resolucion + 'd' : 'N/A',
          colorClass: '', clickable: false,
          trend: null, trendLabel: '',
          trendClass: ''
        }
      ]
    })

    function onCardClick(key) {
      fetchCardDetalle(key)
    }

    const detalleTitle = computed(() => {
      return CARD_TITLES[selectedCard.value] || ''
    })

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
        'Sin revisar': 'estado--pendiente',
        'Por asignar departamento': 'estado--pendiente',
        'Esperando recepción': 'estado--pendiente',
        'Aceptada en proceso': 'estado--proceso',
        'Completado': 'estado--completado',
        'Devuelto': 'estado--devuelto',
        'Rechazado por departamento': 'estado--devuelto',
        'Improcedente': 'estado--cerrado',
        'Cancelada': 'estado--cerrado'
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
      if (nivel == 1) return 'Critica'
      if (nivel == 2) return 'Alta'
      if (nivel == 3) return 'Media'
      return 'Baja'
    }

    function downloadCSV() {
      if (!cardDetalle.value || !cardDetalle.value.peticiones.length) return

      const IMP_LABELS = { 1: 'Critica', 2: 'Alta', 3: 'Media' }
      const headers = ['Folio', 'Peticionario', 'Descripcion', 'Municipio', 'Estado', 'Importancia', 'Dias', 'Fecha']
      const rows = cardDetalle.value.peticiones.map(p => [
        p.folio || '',
        p.nombre || 'Anonimo',
        (p.descripcion || '').replace(/[\r\n]+/g, ' '),
        p.Municipio || '',
        p.estado || '',
        IMP_LABELS[p.NivelImportancia] || 'Baja',
        p.dias_transcurridos || 0,
        p.fecha_registro || ''
      ])

      const escape = v => '"' + String(v).replace(/"/g, '""') + '"'
      const csv = '\uFEFF' + [headers.map(escape).join(','), ...rows.map(r => r.map(escape).join(','))].join('\r\n')

      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = (CARD_TITLES[selectedCard.value] || 'peticiones').replace(/\s+/g, '_') + '.csv'
      a.click()
      URL.revokeObjectURL(url)
    }

    return {
      kpis, isLoading, cards, selectedCard, cardDetalle, cardDetalleLoading,
      onCardClick, detalleTitle, truncate, formatDate, estadoClass, impClass, impLabel, downloadCSV
    }
  }
}
</script>

<style scoped>
/* Clickable cards */
.resumen-item--clickable {
  cursor: pointer;
}
.resumen-item--clickable:hover {
  border-color: #93c5fd;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
  transform: translateY(-2px);
}
.resumen-item--clickable:hover::before {
  background: #3b82f6;
  width: 60px;
}
.resumen-item--active {
  border-color: #3b82f6 !important;
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.15);
  background: #f8faff !important;
}
.resumen-item--active::before {
  background: #2563eb !important;
  width: 100% !important;
}

.resumen-chevron {
  color: #cbd5e1;
  transition: transform 0.3s ease, color 0.2s;
  margin-top: 2px;
}
.resumen-item--clickable:hover .resumen-chevron {
  color: #3b82f6;
}
.resumen-chevron--open {
  transform: rotate(180deg);
  color: #2563eb;
}

/* Transition */
.slide-detail-enter-active,
.slide-detail-leave-active {
  transition: all 0.35s ease;
  overflow: hidden;
}
.slide-detail-enter-from,
.slide-detail-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}
.slide-detail-enter-to,
.slide-detail-leave-from {
  opacity: 1;
  max-height: 600px;
  transform: translateY(0);
}

/* Detail panel */
.card-detalle-panel {
  max-width: 1400px;
  margin: 0 auto;
  background: white;
  border-radius: 0 0 16px 16px;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.12);
  border: 1px solid #dbeafe;
  border-top: 3px solid #2563eb;
  overflow: hidden;
}

.card-detalle-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 32px;
  color: #6b7280;
  font-size: 0.85rem;
}

.loading-spinner-sm {
  width: 20px;
  height: 20px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.card-detalle-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  background: #f0f6ff;
  border-bottom: 1px solid #dbeafe;
}

.card-detalle-title {
  font-weight: 700;
  font-size: 0.85rem;
  color: #1e3a8a;
}

.card-detalle-count {
  font-size: 0.75rem;
  color: #6b7280;
  background: white;
  padding: 2px 10px;
  border-radius: 12px;
  font-weight: 600;
}

.card-detalle-csv {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 4px;
  background: #f0fdf4;
  border: 1px solid #86efac;
  border-radius: 6px;
  padding: 4px 10px;
  cursor: pointer;
  color: #166534;
  font-size: 0.72rem;
  font-weight: 600;
  transition: all 0.15s;
}
.card-detalle-csv:hover {
  background: #dcfce7;
  border-color: #4ade80;
}

.card-detalle-close {
  background: none;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 4px;
  cursor: pointer;
  color: #64748b;
  display: flex;
  align-items: center;
  transition: all 0.15s;
}
.card-detalle-close:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fca5a5;
}

.card-detalle-table-wrap {
  overflow-x: auto;
  max-height: 400px;
  overflow-y: auto;
}

.card-detalle-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.78rem;
}

.card-detalle-table thead {
  position: sticky;
  top: 0;
  z-index: 1;
}

.card-detalle-table th {
  background: #f8fafc;
  padding: 8px 12px;
  text-align: left;
  font-weight: 600;
  color: #475569;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.card-detalle-table td {
  padding: 8px 12px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.card-detalle-table tbody tr:hover {
  background: #f8fafc;
}

.td-folio {
  font-weight: 600;
  color: #2563eb;
  white-space: nowrap;
}

.td-nombre {
  font-weight: 500;
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.td-desc {
  max-width: 200px;
  color: #64748b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.td-fecha {
  white-space: nowrap;
  color: #64748b;
}

.td-dias {
  font-weight: 600;
  white-space: nowrap;
}
.td-dias--alerta {
  color: #dc2626;
}

/* Badges */
.estado-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 0.7rem;
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
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 600;
  white-space: nowrap;
}
.imp--critica { background: #fee2e2; color: #991b1b; }
.imp--alta { background: #ffedd5; color: #9a3412; }
.imp--media { background: #fef3c7; color: #92400e; }
.imp--baja { background: #f1f5f9; color: #475569; }

.card-detalle-empty {
  padding: 32px;
  text-align: center;
  color: #9ca3af;
  font-size: 0.85rem;
}

/* Dark mode — KPI card interactions (scoped classes) */
:global(.dark-mode) .resumen-item--clickable:hover { border-color: #3b82f6; background: #1a2744 !important; }
:global(.dark-mode) .resumen-item--clickable:hover::before { background: #3b82f6; }
:global(.dark-mode) .resumen-item--active { background: #1a2744 !important; border-color: #3b82f6 !important; }
:global(.dark-mode) .resumen-item--active::before { background: #2563eb !important; }
:global(.dark-mode) .resumen-chevron { color: #475569; }
:global(.dark-mode) .resumen-item--clickable:hover .resumen-chevron { color: #60a5fa; }
:global(.dark-mode) .resumen-chevron--open { color: #60a5fa; }

/* Dark mode — detail panel (scoped classes) */
:global(.dark-mode) .card-detalle-panel { background: #1e293b; border-color: #334155; border-top-color: #3b82f6; }
:global(.dark-mode) .card-detalle-header { background: #1a2332; border-color: #334155; }
:global(.dark-mode) .card-detalle-title { color: #93c5fd; }
:global(.dark-mode) .card-detalle-count { background: #334155; color: #94a3b8; }
:global(.dark-mode) .card-detalle-loading { color: #94a3b8; }
:global(.dark-mode) .card-detalle-empty { color: #64748b; }
:global(.dark-mode) .card-detalle-table th { background: #1a2332; color: #94a3b8; border-color: #334155; }
:global(.dark-mode) .card-detalle-table td { color: #cbd5e1; border-color: #1e293b; }
:global(.dark-mode) .card-detalle-table tbody tr:hover { background: #1a2332; }
:global(.dark-mode) .card-detalle-csv { background: #14532d; color: #86efac; border-color: #166534; }
:global(.dark-mode) .card-detalle-csv:hover { background: #166534; border-color: #22c55e; }
:global(.dark-mode) .card-detalle-close { color: #94a3b8; border-color: #475569; }
:global(.dark-mode) .card-detalle-close:hover { background: #7f1d1d; color: #fca5a5; border-color: #991b1b; }
</style>
