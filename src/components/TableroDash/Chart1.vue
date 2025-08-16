<template>
  <div class="chart-wrapper chart1-derecha">
    <div class="filter-buttons">
      <button
        v-for="item in allData"
        :key="item.estatus"
        :class="{ active: visibleStatus.includes(item.estatus) }"
        :style="{
          backgroundColor: visibleStatus.includes(item.estatus) ? item.color : 'transparent',
          borderColor: item.color,
          color: visibleStatus.includes(item.estatus) ? '#fff' : item.color
        }"
        @click="toggleStatus(item.estatus)"
      >
        {{ item.estatus }}
      </button>
    </div>
    <div ref="chart" style="width: 100%; height: 500px;"></div>
  </div>
</template>

<script>
import * as echarts from 'echarts';

export default {
  name: 'ReportesBarChart',
  data() {
    return {
      chart: null,
      allData: [
        { porcentaje: 100, cantidad: 1240, estatus: 'Reportes Totales', color: '#1E40AF' },
        { porcentaje: 70.3, cantidad: 872, estatus: 'Atendidos', color: '#3B82F6' },
        { porcentaje: 25.1, cantidad: 312, estatus: 'Pendientes', color: '#60A5FA' },
        { porcentaje: 4.5, cantidad: 56, estatus: 'En Proceso', color: '#93C5FD' },
      ],
      visibleStatus: ['Reportes Totales', 'Atendidos', 'Pendientes', 'En Proceso'],
    };
  },
  mounted() {
    this.chart = echarts.init(this.$refs.chart);
    this.renderChart();
    window.addEventListener('resize', this.resizeChart);
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.resizeChart);
    if (this.chart) {
      this.chart.dispose();
    }
  },
  methods: {
    toggleStatus(status) {
      const index = this.visibleStatus.indexOf(status);
      if (index > -1) {
        this.visibleStatus.splice(index, 1);
      } else {
        this.visibleStatus.push(status);
      }
      this.renderChart();
    },
    renderChart() {
      const filteredData = this.allData.filter(d => this.visibleStatus.includes(d.estatus));
      const datasetSource = [['porcentaje', 'cantidad', 'estatus'], ...filteredData.map(d => [d.porcentaje, d.cantidad, d.estatus])];

      const option = {
        title: {
          text: 'Estado de Reportes',
          left: 'center',
          top: 15,
          textStyle: {
            fontSize: 24,
            fontWeight: '700',
            color: '#1E40AF',
            fontFamily: '"Inter", "Segoe UI", sans-serif'
          },
        },
        tooltip: {
          trigger: 'item',
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
          formatter: (params) => {
            const estatus = params.value[2];
            const cantidad = params.value[1];
            const porcentaje = params.value[0];
            return `
              <div style="padding: 16px;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                  <div style="width: 10px; height: 10px; background: ${params.color}; border-radius: 50%; margin-right: 10px;"></div>
                  <strong style="color: #1F2937; font-size: 16px; font-weight: 600;">${estatus}</strong>
                </div>
                <div style="color: #6B7280; font-size: 14px; line-height: 1.6;">
                  <div style="margin-bottom: 4px;">Reportes: <strong style="color: #1F2937;">${cantidad.toLocaleString()}</strong></div>
                  <div>Porcentaje: <strong style="color: #1F2937;">${porcentaje}%</strong></div>
                </div>
              </div>
            `;
          }
        },
        dataset: {
          source: datasetSource,
        },
        grid: {
          left: '10%',
          right: '10%',
          bottom: '15%',
          top: '25%',
        },
        xAxis: {
          name: 'Cantidad',
          type: 'value',
          nameTextStyle: {
            color: '#6B7280',
            fontSize: 14,
            fontFamily: '"Inter", "Segoe UI", sans-serif'
          },
          axisLine: {
            lineStyle: {
              color: '#E5E7EB'
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
              width: 1
            }
          }
        },
        yAxis: {
          type: 'category',
          axisLine: {
            lineStyle: {
              color: '#E5E7EB'
            }
          },
          axisLabel: {
            color: '#374151',
            fontSize: 13,
            fontWeight: 500,
            fontFamily: '"Inter", "Segoe UI", sans-serif'
          }
        },
        series: [
          {
            type: 'bar',
            encode: {
              x: 'cantidad',
              y: 'estatus',
            },
            barWidth: '65%',
            itemStyle: {
              color: (params) => {
                const estatus = params.value[2];
                const item = this.allData.find(d => d.estatus === estatus);
                return new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                  { offset: 0, color: item ? item.color : '#3B82F6' },
                  { offset: 1, color: item ? item.color + 'CC' : '#3B82F6CC' }
                ]);
              },
              borderRadius: [0, 8, 8, 0],
              shadowColor: 'rgba(0, 0, 0, 0.1)',
              shadowBlur: 6,
              shadowOffsetY: 3
            },
            animationDuration: 1000,
            animationEasing: 'cubicOut',
            emphasis: {
              itemStyle: {
                shadowColor: 'rgba(0, 0, 0, 0.2)',
                shadowBlur: 12,
                shadowOffsetY: 6
              }
            }
          }
        ]
      };

      this.chart.setOption(option);
    },
    resizeChart() {
      if (this.chart) {
        this.chart.resize();
      }
    }
  }
};
</script>

<style scoped>
/* Contenedor del gráfico específico (Chart1) */

.filter-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 24px;
  justify-content: center;
}

.filter-buttons button {
  padding: 12px 20px;
  border: 2px solid;
  border-radius: 25px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  font-family: "Inter", "Segoe UI", sans-serif;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: transparent;
  position: relative;
  overflow: hidden;
}

.filter-buttons button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.filter-buttons button:hover::before {
  left: 100%;
}

.filter-buttons button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.filter-buttons button.active {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.chart1-derecha {
  margin-left: auto;
  margin-right: 0;
  max-width: 900px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  margin: 2rem auto;
}

.chart-wrapper {
  width: 100%;
  max-width: 850px;
  margin-left: auto;
  padding: 32px;
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04),
    0 0 0 1px rgba(59, 130, 246, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.6);
  position: relative;
  overflow: hidden;
}

.chart-wrapper::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%);
  border-radius: 20px 20px 0 0;
}

@media (max-width: 768px) {
  .chart-wrapper {
    padding: 20px;
    margin: 16px;
  }

  .filter-buttons {
    justify-content: center;
  }

  .chart1-derecha {
    max-width: 100%;
  }
}
</style>
