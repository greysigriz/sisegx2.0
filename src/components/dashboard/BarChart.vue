<template>
  <div class="chart-wrapper chart1-derecha">
    <div class="filter-buttons">
      <button
        v-for="item in allData"
        :key="item.estatus"
        :class="{ active: visibleStatus.includes(item.estatus) }"
        :style="{
          backgroundColor: visibleStatus.includes(item.estatus) ? item.color : 'transparent',
          borderColor: item.color,
          color: visibleStatus.includes(item.estatus) ? '#fff' : item.color
        }"
        @click="toggleStatus(item.estatus)"
      >
        {{ item.estatus }}
      </button>
    </div>

    <div ref="barChart" class="bar-chart"></div>
  </div>
</template>

<script setup>
import * as echarts from 'echarts'
import { ref, onMounted, onUnmounted } from 'vue'
import useDashboardCharts from '@/composables/useDashboardCharts.js'

const barChart = ref(null)
let barChartInstance = null

const allData = ref([
  { estatus: 'Completado', cantidad: 120, color: '#2563EB' },
  { estatus: 'Sin revisar', cantidad: 80, color: '#1E40AF' },
  { estatus: 'Esperando revisión', cantidad: 60, color: '#3B82F6' },
  { estatus: 'Rechazado por el departamento', cantidad: 40, color: '#60A5FA' },
  { estatus: 'No completado', cantidad: 20, color: '#93C5FD' }
])

const visibleStatus = ref(allData.value.map(i => i.estatus))

const toggleStatus = (status) => {
  const index = visibleStatus.value.indexOf(status)
  if (index > -1) visibleStatus.value.splice(index, 1)
  else visibleStatus.value.push(status)
  renderBarChart()
}

const renderBarChart = () => {
  if (!barChartInstance) return

  const filtered = allData.value.filter(d => visibleStatus.value.includes(d.estatus))

  const option = {
    title: {
        text: 'Estado de Reportes',
        left: 'center',
        top: 15,
        textStyle: {
          fontSize: 29,
          fontWeight: '700',
          color: '#1E40AF',
          fontFamily: '"Inter", "Segoe UI", sans-serif'
        },
      },
    tooltip: {
      trigger: 'item',
      backgroundColor: '#ffffff',
      borderColor: '#E5E7EB',
      borderWidth: 1,
      borderRadius: 12,
      textStyle: {
        color: '#1F2937',
        fontSize: 13,
        fontFamily: '"Inter", "Segoe UI", sans-serif'
      },
    },

    grid: {
      left: '17%',
      right: '10%',
      bottom: '15%',
      top: '15%'
    },
    xAxis: {
      type: 'value',
      name: 'Cantidad',
      axisLine: { lineStyle: { color: '#E5E7EB' } },
      axisLabel: { color: '#6B7280', fontSize: 12 },
      splitLine: { lineStyle: { color: '#F3F4F6' } }
    },
    yAxis: {
      type: 'category',
      axisLine: { lineStyle: { color: '#E5E7EB' } },
      axisLabel: { color: '#374151', fontSize: 13 },
      data: filtered.map(d => d.estatus)
    },
    series: [
      {
        type: 'bar',
        data: filtered.map(d => ({
          value: d.cantidad,
          itemStyle: {
            color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
              { offset: 0, color: d.color },
              { offset: 1, color: d.color + 'CC' }
            ]),
            borderRadius: [0, 8, 8, 0]
          }
        })),
        barWidth: '75%'
      }
    ]
  }

  barChartInstance.setOption(option)
}

const initBarChart = () => {
  barChartInstance = echarts.init(barChart.value)
  renderBarChart()
}

const { resizeHandler } = useDashboardCharts(() => barChartInstance)

onMounted(() => {
  initBarChart()
  window.addEventListener('resize', resizeHandler)
})

onUnmounted(() => {
  window.removeEventListener('resize', resizeHandler)
  if (barChartInstance) barChartInstance.dispose()
})
</script>
