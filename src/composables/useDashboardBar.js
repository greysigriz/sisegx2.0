import * as echarts from 'echarts'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import axios from '@/services/axios-config.js'
import { useVisibilityReflow } from '@/composables/useVisibilityReflow.js'

export default function useDashboardBar(barChart) {
  let barChartInstance = null

  const allReports = ref([])
  const fetchError = ref(null)
  const topFilter = ref('10')

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

  const fetchDependencias = async () => {
    fetchError.value = null
    try {
      const res = await axios.get('dependencias.php')
      if (res.data && Array.isArray(res.data.records)) {
        allReports.value = res.data.records.map((r) => ({
          category: r.Nombre,
          display: truncateName(r.Nombre, 35),
          count: Number(r.cantidad || 0)
        }))

        if (res.data.warning) {
          fetchError.value = { message: res.data.warning }
        } else {
          fetchError.value = null
        }
      } else {
        allReports.value = []
        fetchError.value = { message: 'No se obtuvieron dependencias desde la API.' }
      }
    } catch (err) {
      const serverBody = err?.response?.data || null
      console.error('Error cargando dependencias - server body:', serverBody)
      console.error('Error cargando dependencias - error object:', err)
      fetchError.value = serverBody || { message: err.message }
      allReports.value = []
    }
  }

  const filteredData = computed(() => {
    const sorted = [...allReports.value].sort((a, b) => b.count - a.count)

    if (topFilter.value === '10') return sorted.slice(0, 10)
    if (topFilter.value === '15') return sorted.slice(0, 15)
    return sorted
  })

  const renderBarChart = () => {
    if (!barChartInstance) return
    const data = filteredData.value

    if (!data || data.length === 0) {
      try {
        barChartInstance.clear()
      } catch (err) {
        console.debug('barChart clear error', err)
      }
      return
    }

    const minHeight = 700
    const heightPerItem = 28
    const calculatedHeight = Math.max(minHeight, data.length * heightPerItem + 100)

    if (barChart.value) {
      barChart.value.style.height = `${calculatedHeight}px`
      barChartInstance.resize()
    }

    const maxValue = Math.max(...data.map((d) => d.count), 1)

    const option = {
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        confine: true,
        extraCssText: `
        max-width: 220px;
        white-space: normal;
        word-break: break-word;
        `,
        position: (pos, params, dom, rect, size) => {
          const chartWidth =
            (barChart.value && barChart.value.clientWidth) ||
            (size && size.viewSize && size.viewSize[0]) ||
            400
          const tooltipApproxWidth = 240
          const gap = 15
          const x =
            pos[0] + tooltipApproxWidth + gap > chartWidth
              ? Math.max(gap, pos[0] - tooltipApproxWidth - gap)
              : pos[0] + gap
          const y = pos[1]
          return [x, y]
        },
        backgroundColor: '#ffffff',
        borderColor: '#E5E7EB',
        borderWidth: 1,
        borderRadius: 12,
        textStyle: { color: '#1F2937', fontSize: 13, fontFamily: chartFont },
        formatter: (params) => {
          const param = params[0]
          const percent = Math.round((param.value / maxValue) * 100)
          const fullName = param?.data?.originalName || param.name
          return `<div class="echarts-tooltip" style="--count:${param.value}; --percent:${percent}%">
            <strong class="echarts-tooltip-title">${fullName}</strong><br/>
            <span class="echarts-tooltip-value">Total: ${param.value} seguimientos</span>
          </div>`
        }
      },
      grid: {
        left: '40%',
        right: '4%',
        bottom: '5%',
        top: '2%',
        containLabel: false
      },
      xAxis: {
        type: 'value',
        name: 'Numero de casos',
        nameLocation: 'middle',
        nameGap: 25,
        nameTextStyle: {
          color: '#64748b',
          fontSize: 12,
          fontFamily: chartFont
        },
        axisLine: { lineStyle: { color: '#E5E7EB' } },
        axisLabel: {
          color: '#6B7280',
          fontFamily: chartFont,
          fontSize: 11
        },
        splitLine: {
          lineStyle: {
            color: '#F3F4F6',
            type: 'dashed'
          }
        }
      },
      yAxis: {
        type: 'category',
        data: data.map((d) => d.display || d.category),
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
          lineHeight: 20,
          interval: 0,
          formatter: (value) => String(value)
        }
      },
      series: [
        {
          type: 'bar',
          data: data.map((d) => ({
            value: d.count,
            originalName: d.category
          })),
          barWidth: 18,
          barGap: '20%',
          itemStyle: {
            borderRadius: [0, 6, 6, 0],
            color: (params) => colors[params.dataIndex % colors.length]
          },
          label: {
            show: true,
            position: 'right',
            formatter: '{c}',
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

  const initBarChart = () => {
    if (!barChart.value || barChart.value.clientWidth === 0 || barChart.value.clientHeight === 0) {
      console.warn('[BarChart] Container has invalid dimensions, skipping init')
      return
    }
    barChartInstance = echarts.init(barChart.value)
    renderBarChart()
  }

  const resizeHandler = () => {
    if (barChartInstance) {
      barChartInstance.resize()
    }
  }

  const prettyError = computed(() => {
    if (!fetchError.value) return ''
    if (typeof fetchError.value === 'string') return fetchError.value
    try {
      return JSON.stringify(fetchError.value, null, 2)
    } catch (err) {
      console.debug('prettyError stringify failed', err)
      return String(fetchError.value)
    }
  })

  watch(topFilter, () => {
    renderBarChart()
  })

  onMounted(() => {
    useVisibilityReflow()
    fetchDependencias().then(() => {
      initBarChart()
      window.addEventListener('resize', resizeHandler)
    })
  })

  onUnmounted(() => {
    window.removeEventListener('resize', resizeHandler)
    if (barChartInstance) barChartInstance.dispose()
  })

  return {
    topFilter,
    fetchError,
    prettyError
  }
}
