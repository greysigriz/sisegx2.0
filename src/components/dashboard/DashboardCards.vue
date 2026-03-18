<template>
  <div class="dashboard-metrics">
    <div v-if="isLoading && !kpis" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando...</p>
    </div>

    <div class="resumen-header" v-else-if="kpis">
      <div v-for="card in cards" :key="card.key" class="resumen-item">
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
      </div>
    </div>
  </div>
</template>

<script>
import '@/assets/css/cards_dashboard.css'
import { computed, defineComponent, h, ref, watch, onUnmounted, toRef } from 'vue'
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

export default {
  name: "DashboardCards",
  components: { AnimNum },
  setup() {
    const { kpis, kpisPrev, isLoading } = useDashboardStore()

    function calcTrend(current, previous) {
      const c = Number(current || 0)
      const p = Number(previous || 0)
      if (p === 0 && c === 0) return { val: 0, label: 'sin cambio' }
      if (p === 0) return { val: 1, label: '+' + c }
      const pct = Math.round(((c - p) / p) * 100)
      if (pct === 0) return { val: 0, label: 'sin cambio' }
      return { val: pct, label: (pct > 0 ? '+' : '') + pct + '%' }
    }

    // Para criticas/retrasadas: subir es malo (rojo), bajar es bueno (verde)
    // Para completadas/en_proceso: subir es bueno (verde), bajar es malo (rojo)
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
          colorClass: '',
          trend: t_total.val, trendLabel: t_total.label,
          trendClass: trendClass(t_total.val, true)
        },
        {
          key: 'criticas', label: 'Criticas',
          value: kpis.value.criticas || 0,
          colorClass: 'danger',
          trend: t_criticas.val, trendLabel: t_criticas.label,
          trendClass: trendClass(t_criticas.val, true) // subir criticas = malo
        },
        {
          key: 'proceso', label: 'En Proceso',
          value: kpis.value.en_proceso || 0,
          colorClass: 'info',
          trend: t_proceso.val, trendLabel: t_proceso.label,
          trendClass: trendClass(t_proceso.val, false) // subir proceso = bueno
        },
        {
          key: 'completadas', label: 'Completadas',
          value: kpis.value.completadas || 0,
          colorClass: 'success',
          trend: t_completadas.val, trendLabel: t_completadas.label,
          trendClass: trendClass(t_completadas.val, false)
        },
        {
          key: 'retrasadas', label: 'Retrasadas (+30d)',
          value: kpis.value.retrasadas || 0,
          colorClass: 'warning',
          trend: t_retrasadas.val, trendLabel: t_retrasadas.label,
          trendClass: trendClass(t_retrasadas.val, true) // subir retrasadas = malo
        },
        {
          key: 'resolucion', label: 'Prom. Resolucion',
          value: kpis.value.promedio_dias_resolucion ? kpis.value.promedio_dias_resolucion + 'd' : 'N/A',
          colorClass: '',
          trend: null, trendLabel: '',
          trendClass: ''
        }
      ]
    })

    return { kpis, isLoading, cards }
  }
}
</script>
