<!-- AreaChartt component - Tendencia de peticiones conectado al store -->
<template>
  <div class="area-chart-card">
    <div class="area-chart-card-header">
      <div class="card-header-content">
        <div class="card-title-wrapper">
          <h3 class="card-title">Tendencia de Peticiones</h3>
        </div>
        <p class="card-description">
          Evolucion de peticiones registradas en el periodo seleccionado
        </p>
      </div>
    </div>

    <div class="area-chart-card-content">
      <!-- Loading state -->
      <div v-if="isLoading && (!timeline || timeline.length === 0)" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Cargando datos...</p>
      </div>

      <!-- Empty state -->
      <div v-else-if="!isLoading && (!timeline || timeline.length === 0)" class="error-state">
        <div class="error-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3v18h18"/>
            <path d="m19 9-5 5-4-4-3 3"/>
          </svg>
        </div>
        <p class="error-title">Sin datos para este periodo</p>
        <p class="error-message">Ajusta los filtros para ver la tendencia</p>
      </div>

      <!-- Chart container (siempre en el DOM para que el ref funcione) -->
      <div ref="chart" class="chart-container" :style="{ display: timeline && timeline.length > 0 ? 'block' : 'none' }"></div>
    </div>
  </div>
</template>

<script setup>
import '@/assets/css/areachartt_dashboard.css'
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import * as echarts from 'echarts'
import { useDashboardStore } from '@/composables/useDashboardStore.js'
import { useVisibilityReflow } from '@/composables/useVisibilityReflow.js'

const chart = ref(null)
let chartInstance = null

const { timeline, isLoading } = useDashboardStore()

function formatLabel(fecha) {
  if (!fecha) return ''
  const parts = fecha.split('-')
  if (parts.length < 3) return fecha
  const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
  return `${meses[parseInt(parts[1]) - 1]} ${parts[0].slice(2)}`
}

function renderChart() {
  if (!chartInstance || !timeline.value || timeline.value.length === 0) return

  const datos = timeline.value
  const labels = datos.map(d => formatLabel(d.fecha))

  // Calcular acumulados
  let acumTotal = 0
  let acumResueltas = 0
  const backlogData = []
  const acumTotalData = []
  const acumResueltasData = []

  datos.forEach(d => {
    acumTotal += Number(d.total || 0)
    acumResueltas += Number(d.completadas || 0) + Number(d.cerradas || 0)
    acumTotalData.push(acumTotal)
    acumResueltasData.push(acumResueltas)
    backlogData.push(acumTotal - acumResueltas)
  })

  const option = {
    tooltip: {
      trigger: 'axis',
      backgroundColor: document.documentElement.classList.contains('dark-mode') ? 'rgba(30,41,59,0.95)' : 'rgba(255,255,255,0.95)',
      borderColor: document.documentElement.classList.contains('dark-mode') ? '#475569' : '#e5e7eb', borderWidth: 1, borderRadius: 8,
      textStyle: { color: document.documentElement.classList.contains('dark-mode') ? '#e2e8f0' : '#1F2937', fontSize: 12 },
      axisPointer: { type: 'cross', crossStyle: { color: document.documentElement.classList.contains('dark-mode') ? '#475569' : '#e5e7eb' } },
      formatter: function (params) {
        if (!params || params.length === 0) return ''
        const _dk = document.documentElement.classList.contains('dark-mode')
        let r = `<div style="min-width:160px;color:${_dk ? '#e2e8f0' : '#1f2937'};">
          <div style="font-weight:600;color:${_dk ? '#94a3b8' : '#64748b'};margin-bottom:6px;font-size:12px;padding-bottom:5px;border-bottom:1px solid ${_dk ? '#475569' : '#e5e7eb'};">${params[0].axisValue}</div>`
        params.forEach(item => {
          const dash = item.seriesType === 'line' ? 'border-radius:1px;' : 'border-radius:2px;'
          r += `<div style="display:flex;align-items:center;justify-content:space-between;margin:3px 0;font-size:12px;">
            <span style="display:flex;align-items:center;gap:6px;">
              <span style="display:inline-block;width:10px;height:10px;background:${item.color};${dash}"></span>
              ${item.seriesName}
            </span>
            <strong style="margin-left:12px;">${item.value}</strong>
          </div>`
        })
        r += '</div>'
        return r
      }
    },
    legend: {
      data: ['Nuevas', 'Resueltas', 'Backlog acumulado'],
      bottom: '3%', left: 'center',
      icon: 'circle', itemWidth: 10, itemHeight: 10, itemGap: 20,
      textStyle: { color: '#475569', fontSize: 11, fontWeight: 500 },
      selectedMode: true
    },
    grid: { left: '3%', right: '4%', bottom: '16%', top: '8%', containLabel: true },
    xAxis: [{
      type: 'category',
      data: labels,
      axisLine: { show: false },
      axisLabel: { color: '#94a3b8', fontSize: 11, hideOverlap: true, margin: 10 },
      axisTick: { show: false }
    }],
    yAxis: [
      {
        type: 'value',
        name: 'Por periodo',
        nameTextStyle: { color: '#94a3b8', fontSize: 10 },
        axisLine: { show: false },
        axisLabel: { color: '#94a3b8', fontSize: 11 },
        splitLine: { lineStyle: { color: document.documentElement.classList.contains('dark-mode') ? '#334155' : '#f1f5f9' } }
      },
      {
        type: 'value',
        name: 'Acumulado',
        nameTextStyle: { color: '#94a3b8', fontSize: 10 },
        axisLine: { show: false },
        axisLabel: { color: '#94a3b8', fontSize: 11 },
        splitLine: { show: false }
      }
    ],
    series: [
      {
        name: 'Nuevas',
        type: 'bar',
        yAxisIndex: 0,
        data: datos.map(d => Number(d.total || 0)),
        barWidth: 16,
        itemStyle: { color: '#3b82f6', borderRadius: [4, 4, 0, 0] }
      },
      {
        name: 'Resueltas',
        type: 'bar',
        yAxisIndex: 0,
        data: datos.map(d => Number(d.completadas || 0) + Number(d.cerradas || 0)),
        barWidth: 16,
        itemStyle: { color: '#10b981', borderRadius: [4, 4, 0, 0] }
      },
      {
        name: 'Backlog acumulado',
        type: 'line',
        yAxisIndex: 1,
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        lineStyle: { width: 2.5, color: '#ef4444' },
        itemStyle: { color: '#ef4444', borderWidth: 2, borderColor: '#fff' },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(239,68,68,0.15)' },
            { offset: 1, color: 'rgba(239,68,68,0.02)' }
          ])
        },
        data: backlogData
      }
    ]
  }

  chartInstance.setOption(option, true)
}

function initChart() {
  if (!chart.value || chart.value.clientWidth === 0 || chart.value.clientHeight === 0) return
  chartInstance = echarts.init(chart.value)
  renderChart()
}

const onResize = () => {
  if (chartInstance) chartInstance.resize()
}

watch(timeline, async () => {
  await nextTick()
  if (!chart.value) return
  if (!chartInstance) {
    chartInstance = echarts.init(chart.value)
  }
  renderChart()
}, { deep: true })

function onThemeChange() {
  if (chartInstance) renderChart()
}

onMounted(() => {
  useVisibilityReflow()
  if (timeline.value && timeline.value.length > 0) {
    initChart()
  }
  window.addEventListener('resize', onResize)
  window.addEventListener('dashboard-theme-change', onThemeChange)
})

onUnmounted(() => {
  window.removeEventListener('resize', onResize)
  window.removeEventListener('dashboard-theme-change', onThemeChange)
  if (chartInstance) chartInstance.dispose()
})
</script>
