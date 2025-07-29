<template>
  <div class="container">
    <div class="row">
      <div ref="chart" class="chart1"></div>
    </div>
  </div>
</template>

<script setup>
import * as echarts from 'echarts';
import { onMounted, ref } from 'vue';

const chart = ref(null);

// Categorías reales de problemas
const categorias = [
  'Baches',
  'Alumbrado Público',
  'Robos',
  'Incendios',
  'Basura',
  'Falta de Agua',
  'Vandalismo',
  'Ruido',
  'Árboles Caídos',
  'Obras sin Terminar'
];

// Genera datos iniciales aleatorios
const data = categorias.map(() => Math.round(Math.random() * 200));

onMounted(() => {
  const myChart = echarts.init(chart.value);

  const option = {
    title: {
      text: 'Cantidad de Reportes por Tipo de Problema',
      left: 'center'
    },
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    xAxis: {
      max: 'dataMax',
      type: 'value',
      boundaryGap: [0, 0.01],
      name: 'Número de Reportes'
    },
    yAxis: {
      type: 'category',
      data: categorias,
      inverse: true,
      animationDuration: 300,
      animationDurationUpdate: 300,
      max: 5 // Muestra solo los 5 más altos y ordena dinámicamente
    },
    series: [
      {
        realtimeSort: true,
        name: 'Reportes',
        type: 'bar',
        data: data,
        label: {
          show: true,
          position: 'right',
          valueAnimation: true
        },
        itemStyle: {
          color: '#0074D9'
        }
      }
    ],
    animationDuration: 0,
    animationDurationUpdate: 3000,
    animationEasing: 'linear',
    animationEasingUpdate: 'linear'
  };

  myChart.setOption(option);

  // Función para actualizar datos y simular nuevos reportes
  function run() {
    for (let i = 0; i < data.length; ++i) {
      if (Math.random() > 0.9) {
        data[i] += Math.round(Math.random() * 2000);
      } else {
        data[i] += Math.round(Math.random() * 200);
      }
    }
    myChart.setOption({
      series: [
        {
          data
        }
      ]
    });
  }

  setTimeout(run, 0);
  setInterval(run, 3000);

  window.addEventListener('resize', () => {
    myChart.resize();
  });
});
</script>

<style scoped>
.chart1 {
  width: 100%;
  min-height: 450px;
}
</style>
