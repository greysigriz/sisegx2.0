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

// Simula 60 días de datos: [fecha, cantidadReportes, nivel]
function generarDatos() {
  const data = [];
  const inicio = new Date('2025-05-01');

  for (let i = 0; i < 60; i++) {
    const fecha = new Date(inicio);
    fecha.setDate(fecha.getDate() + i);
    const fechaStr = fecha.toISOString().split('T')[0];

    const cantidad = Math.floor(Math.random() * 300) + 10; // entre 10 y 310 reportes
    const nivel = Math.floor(Math.random() * 5) + 1;        // nivel 1 a 5

    data.push([fechaStr, cantidad, nivel]);
  }

  return data;
}

onMounted(() => {
  const myChart = echarts.init(chart.value);
  const data = generarDatos();

  const option = {
    title: {
      text: 'Cantidad de Reportes Ciudadanos por Día (Coloreado por Nivel)',
      left: '1%'
    },
    tooltip: {
      trigger: 'axis',
      formatter: function (params) {
        const punto = params[0].data;
        const etiquetas = {
          1: '🔴 Crítico',
          2: '🟠 Alto',
          3: '🟡 Medio',
          4: '🟢 Bajo',
          5: '🔵 Muy Bajo'
        };
        return `
          📅 ${punto[0]}<br/>
          📈 Reportes: ${punto[1]}<br/>
          🧭 Nivel: ${etiquetas[punto[2]]} (${punto[2]})
        `;
      }
    },
    grid: {
      left: '5%',
      right: '15%',
      bottom: '10%'
    },
    xAxis: {
      type: 'category',
      data: data.map(item => item[0])
    },
    yAxis: {
      type: 'value',
      name: 'Cantidad de Reportes'
    },
    toolbox: {
      right: 10,
      feature: {
        dataZoom: { yAxisIndex: 'none' },
        restore: {},
        saveAsImage: {}
      }
    },
    dataZoom: [
      {
        startValue: data[0][0]
      },
      {
        type: 'inside'
      }
    ],
    visualMap: {
      top: 50,
      right: 10,
      dimension: 2, // usamos el índice del nivel para colorear
      pieces: [
        { value: 1, color: '#FF4136', label: '🔴 Crítico' },
        { value: 2, color: '#FF851B', label: '🟠 Alto' },
        { value: 3, color: '#FFDC00', label: '🟡 Medio' },
        { value: 4, color: '#2ECC40', label: '🟢 Bajo' },
        { value: 5, color: '#0074D9', label: '🔵 Muy Bajo' }
      ],
      outOfRange: {
        color: '#999'
      }
    },
    series: {
      name: 'Cantidad de Reportes',
      type: 'line',
      showSymbol: true,
      symbolSize: 8,
      data: data.map(item => [item[0], item[1], item[2]]),
      lineStyle: {
        width: 3
      },
      encode: {
        x: 0, // fecha
        y: 1  // cantidad
      },
      markLine: {
        silent: true,
        lineStyle: {
          color: '#ccc',
          type: 'dashed'
        },
        data: [
          { yAxis: 50 },
          { yAxis: 100 },
          { yAxis: 200 },
          { yAxis: 300 }
        ]
      }
    }
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
