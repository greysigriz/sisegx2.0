<template>
  <div class="dashboard">
    <!-- Tarjetas de resumen -->
    <div class="summary-cards">
      <div class="summary-card">
        <div class="card-icon" style="background-color: rgba(177, 22, 35, 0.2);">
          <i class="fas fa-file-alt" style="color: #b11623;"></i>
        </div>
        <div class="card-info">
          <h3>325</h3>
          <p>Trámites totales</p>
        </div>
        <div class="card-trend positive">
          <i class="fas fa-arrow-up"></i> 12%
        </div>
      </div>

      <div class="summary-card">
        <div class="card-icon" style="background-color: rgba(41, 44, 55, 0.2);">
          <i class="fas fa-check-circle" style="color: #292c37;"></i>
        </div>
        <div class="card-info">
          <h3>246</h3>
          <p>Trámites completados</p>
        </div>
        <div class="card-trend positive">
          <i class="fas fa-arrow-up"></i> 8%
        </div>
      </div>

      <div class="summary-card">
        <div class="card-icon" style="background-color: rgba(159, 17, 27, 0.2);">
          <i class="fas fa-clock" style="color: #9f111b;"></i>
        </div>
        <div class="card-info">
          <h3>79</h3>
          <p>Trámites pendientes</p>
        </div>
        <div class="card-trend negative">
          <i class="fas fa-arrow-down"></i> 5%
        </div>
      </div>

      <div class="summary-card">
        <div class="card-icon" style="background-color: rgba(0, 0, 0, 0.1);">
          <i class="fas fa-users" style="color: #000000;"></i>
        </div>
        <div class="card-info">
          <h3>92%</h3>
          <p>Satisfacción</p>
        </div>
        <div class="card-trend positive">
          <i class="fas fa-arrow-up"></i> 3%
        </div>
      </div>
    </div>

    <!-- Gráficos -->
    <div class="chart-row">
      <div class="chart-container">
        <div class="chart-header">
          <h3>Trámites por mes</h3>
          <div class="chart-actions">
            <button class="chart-action-button"><i class="fas fa-ellipsis-v"></i></button>
          </div>
        </div>
        <Bar :data="barData" :options="barOptions" />
      </div>

      <div class="chart-container">
        <div class="chart-header">
          <h3>Distribución de trámites</h3>
          <div class="chart-actions">
            <button class="chart-action-button"><i class="fas fa-ellipsis-v"></i></button>
          </div>
        </div>
        <Pie :data="pieData" :options="pieOptions" />
      </div>
    </div>

    <div class="chart-row">
      <div class="chart-container">
        <div class="chart-header">
          <h3>Tendencia anual</h3>
          <div class="chart-actions">
            <button class="chart-action-button"><i class="fas fa-ellipsis-v"></i></button>
          </div>
        </div>
        <Line :data="lineData" :options="lineOptions" />
      </div>
    </div>

    <div class="chart-row">
      <div class="chart-container">
        <div class="chart-header">
          <h3>Comparativa por departamento</h3>
          <div class="chart-actions">
            <button class="chart-action-button"><i class="fas fa-ellipsis-v"></i></button>
          </div>
        </div>
        <Bar :data="horizontalBarData" :options="horizontalBarOptions" />
      </div>

      <div class="chart-container">
        <div class="chart-header">
          <h3>Rendimiento por trimestre</h3>
          <div class="chart-actions">
            <button class="chart-action-button"><i class="fas fa-ellipsis-v"></i></button>
          </div>
        </div>
        <Doughnut :data="doughnutData" :options="doughnutOptions" />
      </div>
    </div>
  </div>
</template>

<script>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement
} from 'chart.js'
import { Bar, Pie, Line, Doughnut } from 'vue-chartjs'

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement
)

export default {
  name:'Dashboard',
  components: {
    Bar,
    Pie,
    Line,
    Doughnut
  },
  data() {
    // Colores del sistema
    const colors = {
      primary: '#b11623',     // Rojo principal
      secondary: '#292c37',   // Gris oscuro
      dark: '#000000',        // Negro
      accent: '#9f111b',      // Rojo oscuro
      light: '#cccccc',       // Gris claro
      white: '#ffffff',       // Blanco
      background: '#f5f7fa'   // Fondo gris claro
    }

    return {
      // 1. Gráfica de barras para trámites por mes
      barData: {
        labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
        datasets: [
          {
            label: 'Trámites completados',
            backgroundColor: colors.primary,
            data: [40, 60, 80, 20, 55, 70]
          },
          {
            label: 'Trámites pendientes',
            backgroundColor: colors.light,
            data: [20, 10, 15, 40, 20, 10]
          }
        ]
      },
      barOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              font: {
                family: "'Poppins', sans-serif"
              }
            }
          },
          tooltip: {
            backgroundColor: colors.secondary,
            titleFont: {
              family: "'Poppins', sans-serif",
              size: 14
            },
            bodyFont: {
              family: "'Poppins', sans-serif",
              size: 13
            },
            padding: 10,
            cornerRadius: 6
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              display: true,
              color: 'rgba(0, 0, 0, 0.05)'
            },
            ticks: {
              font: {
                family: "'Poppins', sans-serif"
              }
            }
          },
          x: {
            grid: {
              display: false
            },
            ticks: {
              font: {
                family: "'Poppins', sans-serif"
              }
            }
          }
        }
      },

      // 2. Gráfica de pastel para distribución
      pieData: {
        labels: ['Categoría A', 'Categoría B', 'Categoría C', 'Categoría D'],
        datasets: [
          {
            backgroundColor: [
              colors.dark,
              colors.accent,
              colors.primary,
              colors.secondary
            ],
            data: [30, 25, 20, 25],
            borderWidth: 0
          }
        ]
      },
      pieOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              font: {
                family: "'Poppins', sans-serif"
              },
              padding: 15
            }
          },
          tooltip: {
            backgroundColor: colors.secondary,
            titleFont: {
              family: "'Poppins', sans-serif",
              size: 14
            },
            bodyFont: {
              family: "'Poppins', sans-serif",
              size: 13
            },
            padding: 10,
            cornerRadius: 6
          }
        }
      },

      // 3. Gráfica de línea para tendencia anual
      lineData: {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        datasets: [
          {
            label: '2023',
            borderColor: colors.accent,
            backgroundColor: 'rgba(159, 17, 27, 0.1)',
            tension: 0.4,
            fill: true,
            data: [30, 40, 35, 50, 49, 60, 70, 91, 85, 80, 95, 100],
            pointBackgroundColor: colors.accent,
            pointBorderColor: colors.white,
            pointBorderWidth: 2,
            pointRadius: 4
          },
          {
            label: '2024',
            borderColor: colors.secondary,
            backgroundColor: 'rgba(41, 44, 55, 0.1)',
            tension: 0.4,
            fill: true,
            data: [40, 55, 45, 60, 65, 75, 85, 95, 90, 100, 110, 120],
            pointBackgroundColor: colors.secondary,
            pointBorderColor: colors.white,
            pointBorderWidth: 2,
            pointRadius: 4
          }
        ]
      },
      lineOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              font: {
                family: "'Poppins', sans-serif"
              }
            }
          },
          tooltip: {
            backgroundColor: colors.secondary,
            titleFont: {
              family: "'Poppins', sans-serif",
              size: 14
            },
            bodyFont: {
              family: "'Poppins', sans-serif",
              size: 13
            },
            padding: 10,
            cornerRadius: 6
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              display: true,
              color: 'rgba(0, 0, 0, 0.05)'
            },
            ticks: {
              font: {
                family: "'Poppins', sans-serif"
              }
            }
          },
          x: {
            grid: {
              display: false
            },
            ticks: {
              font: {
                family: "'Poppins', sans-serif"
              }
            }
          }
        }
      },

      // 4. Gráfica de barras horizontales para comparativa
      horizontalBarData: {
        labels: ['Departamento A', 'Departamento B', 'Departamento C', 'Departamento D', 'Departamento E'],
        datasets: [
          {
            label: 'Eficiencia (%)',
            backgroundColor: colors.primary,
            data: [85, 75, 90, 60, 95],
            borderRadius: 4
          }
        ]
      },
      horizontalBarOptions: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: colors.secondary,
            titleFont: {
              family: "'Poppins', sans-serif",
              size: 14
            },
            bodyFont: {
              family: "'Poppins', sans-serif",
              size: 13
            },
            padding: 10,
            cornerRadius: 6
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            max: 100,
            grid: {
              display: true,
              color: 'rgba(0, 0, 0, 0.05)'
            },
            ticks: {
              font: {
                family: "'Poppins', sans-serif"
              }
            }
          },
          y: {
            grid: {
              display: false
            },
            ticks: {
              font: {
                family: "'Poppins', sans-serif"
              }
            }
          }
        }
      },

      // 5. Gráfica de dona para rendimiento trimestral
      doughnutData: {
        labels: ['Q1', 'Q2', 'Q3', 'Q4'],
        datasets: [
          {
            backgroundColor: [
              colors.dark,
              colors.accent,
              colors.primary,
              colors.secondary
            ],
            data: [25, 30, 20, 25],
            borderWidth: 0,
            cutout: '70%'
          }
        ]
      },
      doughnutOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              font: {
                family: "'Poppins', sans-serif"
              },
              padding: 15
            }
          },
          tooltip: {
            backgroundColor: colors.secondary,
            titleFont: {
              family: "'Poppins', sans-serif",
              size: 14
            },
            bodyFont: {
              family: "'Poppins', sans-serif",
              size: 13
            },
            padding: 10,
            cornerRadius: 6
          }
        }
      }
    }
  }
}
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.summary-card {
  background-color: var(--white-color);
  border-radius: 10px;
  padding: 20px;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
}

.card-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
}

.card-icon i {
  font-size: 24px;
}

.card-info {
  flex: 1;
}

.card-info h3 {
  font-size: 22px;
  font-weight: 600;
  color: var(--secondary-color);
  margin-bottom: 5px;
}

.card-info p {
  font-size: 13px;
  color: var(--light-color);
}

.card-trend {
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
}

.card-trend.positive {
  color: #2ecc71;
}

.card-trend.negative {
  color: #e74c3c;
}

.card-trend i {
  margin-right: 5px;
  font-size: 12px;
}

.chart-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.chart-row:nth-child(3) {
  grid-template-columns: 1fr;
}

.chart-container {
  background-color: var(--white-color);
  border-radius: 10px;
  box-shadow: var(--shadow);
  padding-left: 45px;
  padding-top: 20px;
  padding-bottom: 60px;
  padding-right: 45px;
  height: 350px;
  display: flex;
  flex-direction: column;
}

.chart-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--secondary-color);
}

.chart-action-button {
  width: 30px;
  height: 30px;
  border-radius: 5px;
  background-color: transparent;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
}

.chart-action-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

@media (max-width: 1200px) {
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }

  .chart-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .summary-cards {
    grid-template-columns: 1fr;
  }
}
</style>
