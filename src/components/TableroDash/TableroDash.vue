<!-- src/components/GradientAreaChart.vue -->
<template>
  <div ref="chart" style="width: 100%; height: 500px;"></div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import * as echarts from 'echarts'

const chart = ref(null)

onMounted(() => {
  const myChart = echarts.init(chart.value)

  const option = {
    color: ['#2ECC40', '#0074D9', '#FF851B', '#FF851B', '#FF4136', '#FF4136'],
    title: {
      text: 'Gráfico de Estado de peticiones'
    },
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'cross',
        label: {
          backgroundColor: '#6a7985'
        }
      }
    },
    legend: {
      data: ['Completado', 'Sin revisar', 'Esperando revision', 'Rechazado por el departamento', 'No completado']
    },
    toolbox: {
      feature: {
        saveAsImage: {}
      }
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true
    },
    xAxis: [
      {
        type: 'category',
        boundaryGap: false,
        data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
      }
    ],
    yAxis: [
      {
        type: 'value'
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
          opacity: 0.8,
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgb(128, 255, 165)' },
            { offset: 1, color: '#2ECC40' }
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
          opacity: 0.8,
          color:new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: '#0074D9' },            // azul base
            { offset: 1, color: 'rgb(0, 45, 102)' }      // azul oscuro
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
          opacity: 0.8,
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
          { offset: 0, color: 'rgb(255, 133, 122)' }, // rojo claro
          { offset: 1, color: '#FF4136' }             // rojo base
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
          opacity: 0.8,
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
          { offset: 0, color: 'rgb(255, 200, 128)' }, // naranja claro
          { offset: 1, color: '#FF851B' }             // naranja base
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
        label: { show: true, position: 'top' },
        areaStyle: {
          opacity: 0.8,
          color:new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: '#FF4136' },            // rojo base
            { offset: 1, color: 'rgb(153, 28, 22)' }     // rojo oscuro
          ])
        },
        emphasis: { focus: 'series' },
        data: [220, 302, 181, 234, 210, 290, 150]
      }
    ]
  }

  myChart.setOption(option)
})
</script>

