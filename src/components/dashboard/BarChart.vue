<template>
  <div class="chart-wrapper chart1-derecha">
    <div class="chart-header">
      <div class="chart-header-left">
        <h3 class="chart-title">Top Departamentos</h3>
        <p class="chart-description">Departamentos con más seguimientos</p>
      </div>
      <div class="chart-header-right">
        <select v-model="topFilter" class="top-filter-select">
          <option value="10">Top 10</option>
          <option value="15">Top 15</option>
          <option value="all">Todos</option>
        </select>
      </div>
    </div>

    <div ref="barChart" class="bar-chart"></div>
  </div>
</template>
<script setup>
import '@/assets/css/bar_dashboard.css'
import * as echarts from 'echarts'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import useDashboardCharts from '@/composables/useDashboardCharts.js'

const barChart = ref(null)
let barChartInstance = null

// Datos de ejemplo (simula reportes agrupados por departamento)
const allReports = ref([
  { category: 'Obras Públicas', count: 245 },
  { category: 'Servicios Urbanos', count: 198 },
  { category: 'Seguridad', count: 167 },
  { category: 'Medio Ambiente', count: 143 },
  { category: 'Tránsito', count: 128 },
  { category: 'Alumbrado', count: 115 },
  { category: 'Parques y Jardines', count: 98 },
  { category: 'Agua Potable', count: 87 },
  { category: 'Alcantarillado', count: 76 },
  { category: 'Limpieza', count: 65 },
  { category: 'Desarrollo Social', count: 54 },
  { category: 'Salud', count: 48 },
  { category: 'Cultura', count: 42 },
  { category: 'Deporte', count: 38 },
  { category: 'Educación', count: 35 },
  { category: 'Turismo', count: 28 }
])

const topFilter = ref('10')

const colors = ['#1e40af', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe']

const filteredData = computed(() => {
  const sorted = [...allReports.value].sort((a, b) => b.count - a.count)

  if (topFilter.value === '10') return sorted.slice(0, 10)
  if (topFilter.value === '15') return sorted.slice(0, 15)
  return sorted
})

const renderBarChart = () => {
  if (!barChartInstance) return

  const data = filteredData.value

  const option = {

    tooltip: {
      trigger: 'axis',
      backgroundColor: '#ffffff',
      borderColor: '#E5E7EB',
      borderWidth: 1,
      borderRadius: 12,
      textStyle: {
        color: '#1F2937',
        fontSize: 13,
        fontFamily: '"Inter", "Segoe UI", sans-serif'
      },
      axisPointer: {
        type: 'shadow',
        shadowStyle: {
          color: 'rgba(216, 227, 240, 0.5)'
        }
      },
      formatter: (params) => {
        const param = params[0]
        return `<div style="padding: 8px;">
          <strong style="color: #1e293b; font-weight: 600;">${param.name}</strong><br/>
          <span style="color: #64748b;">Total: ${param.value} seguimientos</span>
        </div>`
      }
    },
    grid: {
      left: '8%',
      right: '8%',
      bottom: '12%',
      top: '22%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      data: data.map(d => d.category),
      position: 'top',
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: {
        color: '#1e293b',
        fontSize: 10,
        fontWeight: 500,
        interval: 0,
        rotate: 45,
        margin: 12
      }
    },
    yAxis: {
      type: 'value',
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: { show: false },
      splitLine: { show: false }
    },
    series: [
      {
        type: 'bar',
        data: data.map((d, index) => ({
          value: d.count,
          itemStyle: {
            color: colors[index % colors.length],
            borderRadius: [10, 10, 0, 0]
          }
        })),
        barWidth: '35%',
        label: {
          show: true,
          position: 'bottom',
          formatter: '{c}',
          color: '#304758',
          fontSize: 11,
          fontWeight: 600,
          distance: 10
        }
      }
    ]
  }

  barChartInstance.setOption(option, true)
}

const initBarChart = () => {
  barChartInstance = echarts.init(barChart.value)
  renderBarChart()
}

const { resizeHandler } = useDashboardCharts(() => barChartInstance)

// Watch for filter changes
watch(topFilter, () => {
  renderBarChart()
})

onMounted(() => {
  initBarChart()
  window.addEventListener('resize', resizeHandler)
})

onUnmounted(() => {
  window.removeEventListener('resize', resizeHandler)
  if (barChartInstance) barChartInstance.dispose()
})
</script>
