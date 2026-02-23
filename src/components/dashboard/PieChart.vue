<template>
  <div class="chart-wrapper pie-chart-wrapper">
    <div class="chart-header">
      <div class="chart-header-left">
        <h3 class="chart-title">Reportes Ciudadanos</h3>
        <p class="chart-description">Distribución por categoría de reportes</p>
      </div>
    </div>

    <div ref="pieChart" class="pie-chart"></div>
  </div>
</template>

<script setup>
// 🚫 ELIMINADO: import CSS global - ahora está en <style> scoped
import * as echarts from 'echarts'
import { ref, onMounted, onUnmounted } from 'vue'
import useDashboardCharts from '@/composables/useDashboardCharts.js'
import axios from '@/services/axios-config.js'
import { useVisibilityReflow } from '@/composables/useVisibilityReflow.js'

const pieChart = ref(null)
let pieChartInstance = null
const categories = ref([])

const colorVariants = ['#1E40AF', '#3B82F6', '#60A5FA', '#93C5FD', '#1D4ED8', '#2563EB']

const updatePieChart = (data) => {
  if (!pieChartInstance) return
  const option = {
    color: colorVariants,
    tooltip: {
      show: false,
      trigger: 'item'
    },
    legend: { top: '5%', left: 'center' },
    series: [
      {
        name: 'Reportes Ciudadanoss',
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['50%', '60%'],
        avoidLabelOverlap: false,
        padAngle: 5,
        itemStyle: { borderRadius: 10, borderColor: '#fff', borderWidth: 3 },
        label: { show: false, position: 'center' },
        emphasis: { label: { show: true, fontSize: 40, fontWeight: 'bold' } },
        labelLine: { show: false },
        data: data
      }
    ]
  }

  pieChartInstance.setOption(option)
}

const fetchCategorias = async () => {
  try {
    const res = await axios.get('peticiones_categorias.php')
    if (res.data && Array.isArray(res.data.records)) {
      categories.value = res.data.records.map(r => ({ value: Number(r.value || 0), name: r.name }))
      updatePieChart(categories.value)
    }
  } catch (err) {
    console.error('Error cargando categorías:', err)
  }
}

const initPieChart = () => {
  // Defensive: Validate container dimensions before init
  if (!pieChart.value || pieChart.value.clientWidth === 0 || pieChart.value.clientHeight === 0) {
    console.warn('[PieChart] Container has invalid dimensions, skipping init')
    return
  }
  pieChartInstance = echarts.init(pieChart.value)
  if (categories.value && categories.value.length) updatePieChart(categories.value)
}

const { resizeHandler } = useDashboardCharts(() => pieChartInstance)

onMounted(() => {
  useVisibilityReflow()
  initPieChart()
  fetchCategorias()
  window.addEventListener('resize', resizeHandler)
})
onUnmounted(() => {
  window.removeEventListener('resize', resizeHandler)
  if (pieChartInstance) pieChartInstance.dispose()
})
</script>

<style src="@/assets/css/pie_dashboard.css" scoped></style>
