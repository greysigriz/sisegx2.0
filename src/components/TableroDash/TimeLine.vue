<template>
  <div class="grafico-container">
    <canvas ref="chartRef" class="chart1"></canvas>
    <button @click="randomize">Randomize</button>
  </div>
</template>


<script setup>
import {
  Chart,
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  TimeScale,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import 'chartjs-adapter-date-fns';
import { onMounted, ref } from 'vue';

Chart.register(LineController, LineElement, PointElement, LinearScale, TimeScale, Title, Tooltip, Legend, Filler);

// Funciones utilitarias (puedes mover esto a un archivo utils si quieres)
const Utils = {
  rand: (min, max) => Math.floor(Math.random() * (max - min) + min),
  newDate: days => new Date(Date.now() + days * 24 * 60 * 60 * 1000),
  newDateString: days => new Date(Date.now() + days * 24 * 60 * 60 * 1000).toISOString(),
  numbers: ({ count, min, max }) => Array.from({ length: count }, () => Utils.rand(min, max)),
  transparentize: (color, opacity) => {
    const alpha = Math.round(opacity * 255).toString(16).padStart(2, '0');
    return color + alpha;
  },
  CHART_COLORS: {
    red: '#ff6384',
    blue: '#36a2eb',
    green: '#4caf50'
  }
};

const chartRef = ref(null);
let myChart;

const DATA_COUNT = 7;
const NUMBER_CFG = { count: DATA_COUNT, min: 0, max: 100 };

const config = {
  type: 'line',
  data: {
    labels: [
      Utils.newDate(0),
      Utils.newDate(1),
      Utils.newDate(2),
      Utils.newDate(3),
      Utils.newDate(4),
      Utils.newDate(5),
      Utils.newDate(6)
    ],
    datasets: [
      {
        label: 'My First dataset',
        backgroundColor: Utils.transparentize(Utils.CHART_COLORS.red, 0.5),
        borderColor: Utils.CHART_COLORS.red,
        fill: false,
        data: Utils.numbers(NUMBER_CFG)
      },
      {
        label: 'My Second dataset',
        backgroundColor: Utils.transparentize(Utils.CHART_COLORS.blue, 0.5),
        borderColor: Utils.CHART_COLORS.blue,
        fill: false,
        data: Utils.numbers(NUMBER_CFG)
      },
      {
        label: 'Dataset with point data',
        backgroundColor: Utils.transparentize(Utils.CHART_COLORS.green, 0.5),
        borderColor: Utils.CHART_COLORS.green,
        fill: false,
        data: [
          { x: Utils.newDateString(0), y: Utils.rand(0, 100) },
          { x: Utils.newDateString(5), y: Utils.rand(0, 100) },
          { x: Utils.newDateString(7), y: Utils.rand(0, 100) },
          { x: Utils.newDateString(15), y: Utils.rand(0, 100) }
        ]
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      title: {
        display: true,
        text: 'Chart.js Time Scale'
      }
    },
    scales: {
      x: {
        type: 'time',
        time: {
          tooltipFormat: 'PPpp'
        },
        title: {
          display: true,
          text: 'Date'
        }
      },
      y: {
        title: {
          display: true,
          text: 'Value'
        }
      }
    }
  }
};

onMounted(() => {
  const ctx = chartRef.value.getContext('2d');
  myChart = new Chart(ctx, config);
});

function randomize() {
  config.data.datasets.forEach(dataset => {
    dataset.data = dataset.data.map(dataObj => {
      if (typeof dataObj === 'object' && dataObj.y !== undefined) {
        return { ...dataObj, y: Utils.rand(0, 100) };
      }
      return Utils.rand(0, 100);
    });
  });
  myChart.update();
}
</script>

<style scoped>
.grafico-container {
  background-color: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

canvas {
  max-width: 100%;
}

button {
  margin-top: 10px;
  padding: 6px 12px;
}

.chart1 {
  width: 100%;
  min-height: 450px;
}
</style>
