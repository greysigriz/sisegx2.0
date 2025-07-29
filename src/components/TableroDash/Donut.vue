<template>
  <div class="container">
    <div class="row">
      <div class="chart1" ref="chart"></div>
    </div>
  </div>
</template>

<script setup>
import * as echarts from 'echarts';
import { onMounted, ref } from 'vue';

const chart = ref(null);

onMounted(() => {
  const myChart = echarts.init(chart.value);

  const colorVariants = [
    '#0074D9',  // Azul fuerte
    '#339CFF',  // Azul claro
    '#66B3FF',
    '#99CCFF',
    '#FF851B',  // Naranja (fuego/incendio)
    '#2ECC40',  // Verde (agua/luz)
    '#FF4136'   // Rojo (robo)
  ];

  const option = {
    color: colorVariants,
    tooltip: {
      trigger: 'item'
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
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 24,
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
  };

  myChart.setOption(option);

  window.addEventListener('resize', () => {
    myChart.resize();
  });
});
</script>

<style scoped>
.chart1 {
  width: 100%;
  min-height: 400px;
}
</style>
