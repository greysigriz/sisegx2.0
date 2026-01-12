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
import '@/assets/css/pie_dashboard.css'
import * as echarts from 'echarts'
import { ref, onMounted, onUnmounted } from 'vue'
import useDashboardCharts from '@/composables/useDashboardCharts.js'

const pieChart = ref(null)
let pieChartInstance = null

const initPieChart = () => {
  pieChartInstance = echarts.init(pieChart.value)

  const colorVariants = [
        '#1E40AF',  // Azul marino ejecutivo
        '#3B82F6',  // Azul principal
        '#60A5FA',  // Azul claro
        '#93C5FD',  // Azul muy claro
        '#1D4ED8',  // Azul intermedio
        '#2563EB'   // Azul corporativo
      ]


  const option = {
    color: colorVariants,
    tooltip: {
      trigger: 'item',
      backgroundColor: '#fff',
      borderColor: '#E5E7EB',
      borderWidth: 1,
      borderRadius: 12,
      formatter: (params) => `
        <div style="padding:16px;">
          <div style="display:flex;align-items:center;margin-bottom:12px;">
            <div style="width:12px;height:12px;border-radius:50%;background:${params.color};margin-right:10px;"></div>
            <strong>${params.name}</strong>
          </div>
          <div>Reportes: <strong>${params.value}</strong></div>
          <div>Porcentaje: <strong>${params.percent}%</strong></div>
        </div>`
    },
    legend: {
      top: '5%',
      left: 'center'
    },
    series: [
      {
        name: 'Reportes Ciudadanos',
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['50%', '60%'],
        avoidLabelOverlap: false,
        padAngle: 5,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 3
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 40,
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: false
        },
        data: [
          { value: 123, name: 'Robos' },
          { value: 98, name: 'Baches' },
          { value: 35, name: 'Incendios' },
          { value: 150, name: 'Alumbrado Público' },
          { value: 110, name: 'Basura' },
          { value: 80, name: 'Falta de Agua' }
        ]
      }
    ]
  }

  pieChartInstance.setOption(option)
}

const { resizeHandler } = useDashboardCharts(() => pieChartInstance)

onMounted(() => {
  initPieChart()
  window.addEventListener('resize', resizeHandler)
})
onUnmounted(() => {
  window.removeEventListener('resize', resizeHandler)
  if (pieChartInstance) pieChartInstance.dispose()
})
</script>
