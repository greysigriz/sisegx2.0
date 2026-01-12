<!-- AreaChartt component removed -->
<template>
  <div class="area-chart-wrapper">
    <div class="area-chart-header">
      <h2 class="area-chart-title">Gráfico de Estado de peticiones</h2>
      <div class="area-chart-legend">
        <template v-for="(item, idx) in legendItems" :key="idx">
          <div class="legend-item" :class="{ active: item.active }" @click="toggleSeries(idx)">
            <span class="legend-dot" :style="{ '--legend-color': item.color }"></span>
            <span class="legend-label">{{ item.name }}</span>
          </div>
        </template>
      </div>
    </div>
    <div ref="chart" class="chart-canvas"></div>
  </div>
</template>

<script setup>
import '@/assets/css/areachartt_dashboard.css'
import { onMounted, onUnmounted, ref } from 'vue'
import * as echarts from 'echarts'

const chart = ref(null)
const legendItems = ref([])
const myChartRef = ref(null)
const originalSeries = ref([])

// legend DOM removed — ECharts internal legend or other components can be used instead

onMounted(() => {
  const myChart = echarts.init(chart.value)
  myChartRef.value = myChart

  const option = {
    color: ['#059669', '#2563EB', '#DC2626', '#EA580C', '#B91C1C'],
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
      boxShadow: '0 10px 25px rgba(0, 0, 0, 0.15)',
      axisPointer: {
        type: 'cross',
        lineStyle: {
          color: '#3B82F6',
          width: 1,
          type: 'dashed'
        },
        crossStyle: {
          color: '#3B82F6'
        },
        label: {
          backgroundColor: '#3B82F6',
          color: '#ffffff',
          fontFamily: '"Inter", "Segoe UI", sans-serif',
          fontSize: 12,
          borderRadius: 6,
          padding: [4, 8]
        }
      },
      formatter: function(params) {
        let result = `<div class="tooltip-container">
          <div class="tooltip-title">
            📅 ${params[0].axisValue}
          </div>`;

        params.reverse().forEach(item => {
          result += `
            <div class="tooltip-item">
              <div class="tooltip-color-dot" style="--dot: ${item.color};"></div>
              <span class="tooltip-label">${item.seriesName}:</span>
              <strong class="tooltip-value">${item.value}</strong>
            </div>`;
        });

        result += '</div>';
        return result;
      }
    },
    // legend moved to DOM header; keep no legend in option
    toolbox: {
      right: 20,
      top: 20,
      feature: {
        saveAsImage: {
          pixelRatio: 2,
          backgroundColor: '#ffffff'
        }
      },
      iconStyle: {
        borderColor: '#3B82F6',
        borderWidth: 2
      },
      emphasis: {
        iconStyle: {
          borderColor: '#1E40AF'
        }
      }
    },
    grid: {
      left: '2%',
      right: '2%',
      bottom: '8%',
      top: '25%',
      containLabel: true
    },
    xAxis: [
      {
        type: 'category',
        boundaryGap: false,
        data: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
        axisLine: {
          lineStyle: {
            color: '#E5E7EB',
            width: 2
          }
        },
        axisLabel: {
          color: '#6B7280',
          fontSize: 13,
          fontFamily: '"Inter", "Segoe UI", sans-serif',
          fontWeight: 500
        },
        axisTick: {
          show: false
        }
      }
    ],
    yAxis: [
      {
        type: 'value',
        axisLine: {
          lineStyle: {
            color: '#E5E7EB',
            width: 2
          }
        },
        axisLabel: {
          color: '#6B7280',
          fontSize: 12,
          fontFamily: '"Inter", "Segoe UI", sans-serif'
        },
        splitLine: {
          lineStyle: {
            color: '#F3F4F6',
            width: 1,
            type: 'dashed'
          }
        }
      }
    ],
    series: [
      {
        name: 'Completado',
        type: 'line',
        stack: 'Total',
        smooth: true,
        lineStyle: { width: 0 },
        showSymbol: false,
        areaStyle: {
          opacity: 0.85,
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(5, 150, 105, 0.8)' },
            { offset: 1, color: 'rgba(5, 150, 105, 0.2)' }
          ])
        },
        emphasis: { focus: 'series' },
        data: [140, 232, 101, 264, 90, 340, 250]
      },
      {
        name: 'Sin revisar',
        type: 'line',
        stack: 'Total',
        smooth: true,
        lineStyle: { width: 0 },
        showSymbol: false,
        areaStyle: {
          opacity: 0.85,
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(37, 99, 235, 0.8)' },
            { offset: 1, color: 'rgba(37, 99, 235, 0.2)' }
          ])
        },
        emphasis: { focus: 'series' },
        data: [120, 282, 111, 234, 220, 340, 310]
      },
      {
        name: 'Esperando revision',
        type: 'line',
        stack: 'Total',
        smooth: true,
        lineStyle: { width: 0 },
        showSymbol: false,
        areaStyle: {
          opacity: 0.85,
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(220, 38, 38, 0.8)' },
            { offset: 1, color: 'rgba(220, 38, 38, 0.2)' }
          ])
        },
        emphasis: { focus: 'series' },
        data: [320, 132, 201, 334, 190, 130, 220]
      },
      {
        name: 'Rechazado por el departamento',
        type: 'line',
        stack: 'Total',
        smooth: true,
        lineStyle: { width: 0 },
        showSymbol: false,
        areaStyle: {
          opacity: 0.85,
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(234, 88, 12, 0.8)' },
            { offset: 1, color: 'rgba(234, 88, 12, 0.2)' }
          ])
        },
        emphasis: { focus: 'series' },
        data: [220, 402, 231, 134, 190, 230, 120]
      },
      {
        name: 'No completado',
        type: 'line',
        stack: 'Total',
        smooth: true,
        lineStyle: { width: 0 },
        showSymbol: false,
        label: {
          show: true,
          position: 'top',
          color: '#374151',
          fontSize: 11,
          fontFamily: '"Inter", "Segoe UI", sans-serif',
          fontWeight: 600
        },
        areaStyle: {
          opacity: 0.85,
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(185, 28, 28, 0.8)' },
            { offset: 1, color: 'rgba(185, 28, 28, 0.2)' }
          ])
        },
        emphasis: { focus: 'series' },
        data: [220, 302, 181, 234, 210, 290, 150]
      }
    ]
  }

  myChart.setOption(option)

  // store original series (keep references to gradients/data)
  originalSeries.value = option.series.map(s => ({ ...s }))

  // populate legend items for DOM legend (use option.color palette)
  legendItems.value = originalSeries.value.map((s, i) => ({
    name: s.name,
    color: (option.color && option.color[i]) || '#9CA3AF',
    active: true
  }))

  // (legend DOM removed) — keep series as-is

  const onResize = () => myChart.resize()
  window.addEventListener('resize', onResize)

  onUnmounted(() => {
    window.removeEventListener('resize', onResize)
    myChart.dispose()
  })
})

function toggleSeries(idx) {
  const item = legendItems.value[idx]
  if (!item) return
  item.active = !item.active

  // build series array preserving original data; only modify visual props
  const seriesUpdates = originalSeries.value.map((s, i) => {
    const active = legendItems.value[i].active
    if (active) return s

    const clone = { ...s }
    clone.areaStyle = { ...(s.areaStyle || {}), opacity: 0 }
    clone.lineStyle = { ...(s.lineStyle || {}), opacity: 0 }
    clone.label = { ...((s.label) || {}), show: false }
    clone.showSymbol = false
    return clone
  })

  if (myChartRef.value) myChartRef.value.setOption({ series: seriesUpdates })
}
</script>
