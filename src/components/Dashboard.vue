<template>
  <div class="grafico-container">
    <h3>Estado de reportes</h3>
    <div class="grafico-layout">
      <div ref="chart" class="grafico-canvas"></div>
      <ul class="leyenda">
        <li
          v-for="item in leyendaDatos"
          :key="item.estado"
          :style="{ opacity: estadosActivos.includes(item.estado) ? 1 : 0.4, cursor: 'pointer' }"
          @click="toggleEstado(item.estado)"
        >
          <span class="color-box" :style="{ backgroundColor: item.color }"></span>
          {{ item.estado }} <b>({{ item.value }})</b>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import * as echarts from 'echarts'
import { onMounted, onUnmounted, ref } from 'vue'

const chart = ref(null)
const myChartInstance = ref(null)
const backendUrl = import.meta.env.VITE_API_URL

const mapaColores = {
  'Sin revisar': '#0074D9',
  'Esperando recepción': '#FF4136',
  'Completado': '#2ECC40',
  'Rechazado por departamento': '#FF851B',
  'Devuelto': '#0891b2',
}

// Variable reactiva para los datos de la leyenda
const leyendaDatos = ref([])

const estadosActivos = ref(Object.keys(mapaColores))

const initChart = async () => {
  try {
    const res = await axios.get(`${backendUrl}/peticiones.php`)
    const peticiones = res.data.records

    const conteo = {}
    peticiones.forEach(p => {
      conteo[p.estado] = (conteo[p.estado] || 0) + 1
    })

    // Construir los datos para la leyenda
    leyendaDatos.value = Object.entries(mapaColores).map(([estado, color]) => ({
      estado,
      color,
      value: conteo[estado] || 0
    }))

    const data = leyendaDatos.value
      .filter(d => estadosActivos.value.includes(d.estado))
      .map(d => ({
        value: d.value,
        name: d.estado
      }))

    // Defensive: Validate container has valid dimensions
    if (!chart.value || chart.value.clientWidth === 0 || chart.value.clientHeight === 0) {
      console.warn('[Dashboard] Chart container has invalid dimensions')
      return
    }

    if (myChartInstance.value) {
      myChartInstance.value.dispose()
    }

    myChartInstance.value = echarts.init(chart.value)
    const option = {
      color: data.map(d => mapaColores[d.name] || '#AAAAAA'),
      tooltip: { trigger: 'item' },
      legend: { show: false }, // Oculta la leyenda de ECharts
      series: [
        {
          name: 'Reportes',
          type: 'pie',
          radius: ['40%', '70%'],
          avoidLabelOverlap: false,
          itemStyle: {
            borderRadius: 10,
            borderColor: '#fff',
            borderWidth: 2
          },
          label: { show: false, position: 'center' },
          emphasis: {
            label: { show: true, fontSize: 24, fontWeight: 'bold' }
          },
          labelLine: { show: false },
          data
        }
      ]
    }

    myChartInstance.value.setOption(option)
  } catch (error) {
    console.error('Error al cargar datos del gráfico:', error)
  }
}

const resizeHandler = () => {
  // Defensive: Only resize if tab is visible and chart is initialized
  if (!document.hidden && myChartInstance.value) {
    myChartInstance.value.resize()
  }
}

onMounted(async () => {
  await initChart()
  window.addEventListener('resize', resizeHandler)
  document.addEventListener('visibilitychange', () => {
    // Reinitialize chart when tab becomes visible
    if (!document.hidden && myChartInstance.value) {
      myChartInstance.value.resize()
    }
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', resizeHandler)
  if (myChartInstance.value) {
    myChartInstance.value.dispose()
    myChartInstance.value = null
  }
})
</script>



<style scoped>
.grafico-container {
  width: 100%;
  max-width: 800px;
  height: 390px;
  padding: 1.5rem;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.grafico-container h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #000000;
  text-align: center;
}

.grafico-layout {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  width: 100%;
}

.grafico-canvas {
  width: 300px !important;
  height: 300px !important;
}

.leyenda {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  font-size: 0.95rem;
  color: #333;
}

.color-box {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 2px;
  margin-right: 8px;
}

.azul    { background-color: #0074D9; }
.rojo    { background-color: #FF4136; }
.verde   { background-color: #2ECC40; }
.naranja { background-color: #FF851B; }
/* Estilos globales para el dashboard */
/* Agregar a tu archivo main.css o como estilos globales */

:root {
  /* Colores principales */
  --color-primary: #3b82f6;
  --color-primary-dark: #1e40af;
  --color-success: #16a34a;
  --color-warning: #ea580c;
  --color-danger: #dc2626;
  --color-info: #0891b2;

  /* Colores de fondo */
  --bg-primary: #ffffff;
  --bg-secondary: #f8fafc;
  --bg-tertiary: #f1f5f9;

  /* Colores de texto */
  --text-primary: #1f2937;
  --text-secondary: #6b7280;
  --text-muted: #9ca3af;

  /* Sombras */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);

  /* Bordes */
  --border-radius: 8px;
  --border-radius-lg: 12px;
  --border-color: #e5e7eb;

  /* Espaciado */
  --spacing-xs: 0.5rem;
  --spacing-sm: 0.75rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
}

/* Reset básico y configuración global */
* {
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  line-height: 1.6;
  color: var(--text-primary);
  background-color: var(--bg-secondary);
  margin: 0;
  padding: 0;
}

/* Clases utilitarias para componentes */
.card {
  background: var(--bg-primary);
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-md);
  padding: var(--spacing-lg);
  border: 1px solid var(--border-color);
}

.card-header {
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  text-align: center;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.btn-primary:hover {
  background-color: var(--color-primary-dark);
  border-color: var(--color-primary-dark);
}

.btn-secondary {
  background-color: var(--bg-tertiary);
  color: var(--text-primary);
  border-color: var(--border-color);
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  border-color: #cbd5e1;
}

/* Estados de badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-success {
  background-color: #dcfce7;
  color: var(--color-success);
}

.badge-warning {
  background-color: #fed7aa;
  color: var(--color-warning);
}

.badge-danger {
  background-color: #fee2e2;
  color: var(--color-danger);
}

.badge-info {
  background-color: #dbeafe;
  color: var(--color-info);
}

.badge-default {
  background-color: var(--bg-tertiary);
  color: var(--text-secondary);
}

/* Estados de carga */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
  color: var(--text-secondary);
  text-align: center;
  gap: var(--spacing-md);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
  color: var(--text-secondary);
  text-align: center;
  background: var(--bg-tertiary);
  border-radius: var(--border-radius);
  border: 2px dashed var(--border-color);
  gap: var(--spacing-md);
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--bg-tertiary);
  border-top: 3px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Tablas responsivas */
.table-responsive {
  overflow-x: auto;
  border-radius: var(--border-radius);
  border: 1px solid var(--border-color);
}

.table {
  width: 100%;
  background: var(--bg-primary);
  border-collapse: collapse;
}

.table th {
  background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%);
  padding: var(--spacing-md) var(--spacing-lg);
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--text-primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid var(--border-color);
}

.table td {
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid #f3f4f6;
  color: var(--text-primary);
  transition: background-color 0.2s ease;
}

.table tr:hover td {
  background-color: var(--bg-secondary);
}

/* Grid layouts */
.grid-2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-lg);
}

.grid-3 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-lg);
}

.grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--spacing-lg);
}

/* Responsive breakpoints */
@media (max-width: 1200px) {
  .grid-4 {
    grid-template-columns: repeat(2, 1fr);
  }

  .grid-3 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .grid-2,
  .grid-3,
  .grid-4 {
    grid-template-columns: 1fr;
  }

  .card {
    padding: var(--spacing-md);
  }

  .card-title {
    font-size: 1.125rem;
  }
}

@media (max-width: 480px) {
  :root {
    --spacing-lg: 1rem;
    --spacing-xl: 1.5rem;
  }

  .card {
    padding: var(--spacing-sm);
  }
}
</style>
