<template>
  <div class="chart-wrapper pie-chart-wrapper">
    <div ref="pieChart" class="chart1-derecha pie-chart"></div>
  </div>
</template>

<script setup>
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
    title: {
          text: 'Reportes Ciudadanos',
          left: 'center',
          top: 20,
          textStyle: {
            fontSize: 29,
            fontWeight: '700',
            color: '#1E40AF',
            fontFamily: '"Inter", "Segoe UI", sans-serif'
          }
        },
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
    series: [
      {
        type: 'pie',
        radius: ['45%', '75%'],
        center: ['50%', '60%'],
        itemStyle: {
          borderRadius: 8,
          borderColor: '#fff',
          borderWidth: 3
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 28,
            fontWeight: '700'
          }
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
