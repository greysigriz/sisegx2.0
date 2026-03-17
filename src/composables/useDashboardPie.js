import * as echarts from 'echarts'
import { onMounted, onUnmounted, ref } from 'vue'
import axios from '@/services/axios-config.js'
import { useVisibilityReflow } from '@/composables/useVisibilityReflow.js'

export default function useDashboardPie(pieChart) {
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
          padAngle: 0,
          itemStyle: { borderRadius: 10, borderColor: 'transparent', borderWidth: 0 },
          label: { show: false, position: 'center' },
          emphasis: { label: { show: true, fontSize: 40, fontWeight: 'bold' } },
          labelLine: { show: false },
          data
        }
      ]
    }

    pieChartInstance.setOption(option)
  }

  const fetchCategorias = async () => {
    try {
      const res = await axios.get('peticiones_categorias.php')
      if (res.data && Array.isArray(res.data.records)) {
        categories.value = res.data.records.map((r) => ({ value: Number(r.value || 0), name: r.name }))
        updatePieChart(categories.value)
      }
    } catch (err) {
      console.error('Error cargando categorias:', err)
    }
  }

  const initPieChart = () => {
    if (!pieChart.value || pieChart.value.clientWidth === 0 || pieChart.value.clientHeight === 0) {
      console.warn('[PieChart] Container has invalid dimensions, skipping init')
      return
    }
    pieChartInstance = echarts.init(pieChart.value)
    if (categories.value && categories.value.length) updatePieChart(categories.value)
  }

  const resizeHandler = () => {
    if (pieChartInstance) {
      pieChartInstance.resize()
    }
  }

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

  return {
    categories
  }
}
