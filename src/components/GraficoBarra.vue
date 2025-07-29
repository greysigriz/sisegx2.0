<template>
  <Bar :data="data" :options="options" />
</template>

<script>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
  components: { Bar },
  data() {
    return {
      data: {
        labels: ['Enero', 'Febrero', 'Marzo', 'Abril'],
        datasets: [
          {
            label: 'Trámites completados',
            backgroundColor: '#1abc9c',
            data: [40, 60, 80, 20]
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top'
          },
          title: {
            display: true,
            text: 'Trámites por mes'
          }
        }
      }
    }
  }
}
</script>
