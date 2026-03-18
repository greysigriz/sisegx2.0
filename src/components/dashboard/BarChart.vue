<template>
  <div class="chart-wrapper chart1-derecha">
    <div class="chart-header">
      <div class="chart-header-left">
        <h3 class="chart-title">Top Departamentos</h3>
        <p class="chart-description">Departamentos con mas asignaciones</p>
      </div>
      <div class="chart-header-right">
        <select v-model="viewMode" class="top-filter-select">
          <option value="total">Total Asignaciones</option>
          <option value="resolucion">Tasa Resolucion</option>
          <option value="pendientes">Pendientes</option>
        </select>
      </div>
    </div>

    <div ref="barChart" class="bar-chart"></div>
  </div>
</template>

<script setup>
import '@/assets/css/bar_dashboard.css'
import { ref, watch, onMounted, onUnmounted, computed, nextTick } from 'vue'
import * as echarts from 'echarts'
import { useDashboardStore } from '@/composables/useDashboardStore.js'
import { useVisibilityReflow } from '@/composables/useVisibilityReflow.js'

const barChart = ref(null)
let barChartInstance = null
const viewMode = ref('total')

const { topDepartamentos } = useDashboardStore()

const colors = ['#1e40af', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe']
const chartFont = '"Inter", "Segoe UI", sans-serif'

const truncateName = (name, max = 35) => {
  if (!name) return ''
  if (name.length <= max) return name
  const truncated = name.slice(0, max)
  const lastSpace = truncated.lastIndexOf(' ')
  if (lastSpace > Math.floor(max * 0.6)) return truncated.slice(0, lastSpace) + '...'
  return truncated.slice(0, max - 3) + '...'
}

const chartData = computed(() => {
  if (!topDepartamentos.value || topDepartamentos.value.length === 0) return []

  return topDepartamentos.value.map(d => ({
    name: d.departamento,
    display: truncateName(d.departamento, 35),
    total: Number(d.total_asignaciones || 0),
    esperando: Number(d.esperando || 0),
    en_proceso: Number(d.en_proceso || 0),
    completadas: Number(d.completadas || 0),
    devueltas: Number(d.devueltas || 0),
    tasa: Number(d.tasa_resolucion || 0)
  }))
})

function renderChart() {
  if (!barChartInstance || chartData.value.length === 0) return

  const data = chartData.value
  const minHeight = 400
  const heightPerItem = 32
  const calculatedHeight = Math.max(minHeight, data.length * heightPerItem + 100)

  if (barChart.value) {
    barChart.value.style.height = `${calculatedHeight}px`
    barChartInstance.resize()
  }

  let seriesData, maxValue, tooltipFormatter, valueLabel

  if (viewMode.value === 'resolucion') {
    seriesData = data.map(d => ({ value: d.tasa, originalName: d.name }))
    maxValue = 100
    valueLabel = '%'
    tooltipFormatter = (params) => {
      const d = data[params[0].dataIndex]
      return `<strong>${d.name}</strong><br/>
        Tasa: <strong>${d.tasa}%</strong><br/>
        Completadas: ${d.completadas} / ${d.total}`
    }
  } else if (viewMode.value === 'pendientes') {
    seriesData = data.map(d => ({ value: d.esperando + d.en_proceso, originalName: d.name }))
    maxValue = Math.max(...data.map(d => d.esperando + d.en_proceso), 1)
    valueLabel = ''
    tooltipFormatter = (params) => {
      const d = data[params[0].dataIndex]
      return `<strong>${d.name}</strong><br/>
        Esperando: ${d.esperando}<br/>
        En proceso: ${d.en_proceso}<br/>
        Total pendientes: <strong>${d.esperando + d.en_proceso}</strong>`
    }
  } else {
    seriesData = data.map(d => ({ value: d.total, originalName: d.name }))
    maxValue = Math.max(...data.map(d => d.total), 1)
    valueLabel = ''
    tooltipFormatter = (params) => {
      const d = data[params[0].dataIndex]
      return `<strong>${d.name}</strong><br/>
        Total: <strong>${d.total}</strong><br/>
        Completadas: ${d.completadas} | En proceso: ${d.en_proceso}<br/>
        Esperando: ${d.esperando} | Devueltas: ${d.devueltas}`
    }
  }

  const option = {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      confine: true,
      backgroundColor: '#ffffff',
      borderColor: '#E5E7EB',
      borderWidth: 1,
      borderRadius: 12,
      textStyle: { color: '#1F2937', fontSize: 13, fontFamily: chartFont },
      formatter: tooltipFormatter
    },
    grid: {
      left: '40%',
      right: '6%',
      bottom: '5%',
      top: '2%',
      containLabel: false
    },
    xAxis: {
      type: 'value',
      max: viewMode.value === 'resolucion' ? 100 : undefined,
      axisLine: { lineStyle: { color: '#E5E7EB' } },
      axisLabel: {
        color: '#6B7280',
        fontFamily: chartFont,
        fontSize: 11,
        formatter: viewMode.value === 'resolucion' ? '{value}%' : '{value}'
      },
      splitLine: { lineStyle: { color: '#F3F4F6', type: 'dashed' } }
    },
    yAxis: {
      type: 'category',
      data: data.map(d => d.display),
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: {
        color: '#1e293b',
        fontSize: 11,
        fontFamily: chartFont,
        fontWeight: 500,
        width: 250,
        overflow: 'truncate',
        align: 'right',
        padding: [0, 12, 0, 0],
        interval: 0
      }
    },
    series: [
      {
        type: 'bar',
        data: seriesData,
        barWidth: 18,
        itemStyle: {
          borderRadius: [0, 6, 6, 0],
          color: (params) => {
            if (viewMode.value === 'resolucion') {
              const val = params.value
              if (val >= 70) return '#10b981'
              if (val >= 40) return '#f59e0b'
              return '#ef4444'
            }
            return colors[params.dataIndex % colors.length]
          }
        },
        label: {
          show: true,
          position: 'right',
          formatter: viewMode.value === 'resolucion' ? '{c}%' : '{c}',
          color: '#1e293b',
          fontSize: 12,
          fontWeight: 700,
          fontFamily: chartFont,
          distance: 8
        }
      }
    ]
  }

  barChartInstance.setOption(option, true)
}

function initChart() {
  if (!barChart.value || barChart.value.clientWidth === 0) return
  barChartInstance = echarts.init(barChart.value)
  renderChart()
}

const resizeHandler = () => {
  if (barChartInstance) barChartInstance.resize()
}

watch(topDepartamentos, async () => {
  await nextTick()
  if (!barChart.value) return
  if (!barChartInstance) {
    barChartInstance = echarts.init(barChart.value)
  }
  renderChart()
}, { deep: true })

watch(viewMode, () => {
  renderChart()
})

onMounted(() => {
  useVisibilityReflow()
  if (topDepartamentos.value && topDepartamentos.value.length > 0) {
    initChart()
  }
  window.addEventListener('resize', resizeHandler)
})

onUnmounted(() => {
  window.removeEventListener('resize', resizeHandler)
  if (barChartInstance) barChartInstance.dispose()
})
</script>
