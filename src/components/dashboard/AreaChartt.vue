<!-- src/components/GradientAreaChart.vue -->
<template>
  <div class="area-chart-wrapper">
    <div ref="chart" class="chart-canvas"></div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import * as echarts from 'echarts'

const chart = ref(null)

onMounted(() => {
  const myChart = echarts.init(chart.value)

  const option = {
    color: ['#059669', '#2563EB', '#DC2626', '#EA580C', '#B91C1C'],
    title: {
      text: 'Gráfico de Estado de peticiones',
      left: 'center',
      top: 20,
      textStyle: {
        fontSize: 24,
        fontWeight: '700',
        color: '#1E40AF',
        fontFamily: '"Inter", "Segoe UI", sans-serif'
      }
    },
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
              <div class="tooltip-color-dot" style="background: ${item.color};"></div>
              <span class="tooltip-label">${item.seriesName}:</span>
              <strong class="tooltip-value">${item.value}</strong>
            </div>`;
        });

        result += '</div>';
        return result;
      }
    },
    legend: {
      data: ['Completado', 'Sin revisar', 'Esperando revision', 'Rechazado por el departamento', 'No completado'],
      top: '12%',
      left: 'center',
      textStyle: {
        color: '#374151',
        fontSize: 13,
        fontFamily: '"Inter", "Segoe UI", sans-serif',
        fontWeight: 500
      },
      itemGap: 24,
      itemWidth: 12,
      itemHeight: 12,
      icon: 'circle'
    },
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
        data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
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

  window.addEventListener('resize', () => {
    myChart.resize()
  })
})
</script>

<style src="@/assets/css/areachartt_dashboard.css"></style>
