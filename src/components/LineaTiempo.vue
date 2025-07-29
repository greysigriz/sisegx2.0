<!-- src/components/GraficoLinea.vue -->
<template>
  <div class="linea-container">
    <h3>Reportes en el tiempo</h3>
    <canvas ref="lineChart" class="linea-canvas"></canvas>
  </div>
</template>

<script>
import axios from 'axios'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

export default {
  name: 'GraficoLinea',
  data() {
    return {
      backendUrl: import.meta.env.VITE_API_URL,
      chartInstance: null
    }
  },
  mounted() {
    this.cargarDatos()
  },
  methods: {
    async cargarDatos() {
      try {
        const res = await axios.get(`${this.backendUrl}/peticiones.php`)
        const peticiones = res.data.records

        // Agrupar por fecha
        const conteoPorFecha = {}
        peticiones.forEach(p => {
          const fecha_registro = p.fecha_registro?.slice(0, 10) || 'Sin fecha'
          conteoPorFecha[fecha_registro] = (conteoPorFecha[fecha_registro] || 0) + 1
        })

        const labels = Object.keys(conteoPorFecha).sort()
        const data = labels.map(fecha => conteoPorFecha[fecha])

        this.generarGrafico(labels, data)
      } catch (error) {
        console.error('Error cargando datos:', error)
      }
    },
    generarGrafico(labels, data) {
      if (this.chartInstance) this.chartInstance.destroy()

      this.chartInstance = new Chart(this.$refs.lineChart, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Total de reportes',
            data,
            fill: false,
            borderColor: '#0074D9',
            backgroundColor: '#0074D9',
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              labels: {
                color: '#1A4C87',
                font: { size: 14 }
              }
            }
          },
          scales: {
            x: {
              title: {
                display: true,
                text: 'Fecha'
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Cantidad de reportes'
              }
            }
          }
        }
      })
    }
  }
}
</script>


<style scoped>
.linea-container {
  width: 100%;
  max-width: 800px;
  padding: 1.5rem;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  margin-bottom: 4rem;
}

.linea-canvas {
  width: 100% !important;
  height: 300px !important;
}
</style>
