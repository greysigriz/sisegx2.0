<template>
  <div class="chart-wrapper chart1-derecha">
    <div class="filter-buttons">
      <button
        v-for="item in allData"
        :key="item.estatus"
        :class="{ active: visibleStatus.includes(item.estatus) }"
        :style="{
          backgroundColor: visibleStatus.includes(item.estatus) ? item.color : '#e0e0e0',
          color: visibleStatus.includes(item.estatus) ? '#fff' : '#333'
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
        { porcentaje: 100, cantidad: 1240, estatus: 'Reportes Totales', color: '#0074D9' },
        { porcentaje: 70.3, cantidad: 872, estatus: 'Atendidos', color: '#3398DC' },
        { porcentaje: 25.1, cantidad: 312, estatus: 'Pendientes', color: '#66ACE0' },
        { porcentaje: 4.5, cantidad: 56, estatus: 'En Proceso', color: '#99C7E6' },
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
          top: 10,
          textStyle: {
            fontSize: 18,
            fontWeight: 'bold',
          },
        },
      tooltip: {
        trigger: 'item',
        backgroundColor: '#fff',
        borderColor: '#0074D9',
        borderWidth: 1,
        textStyle: {
          color: '#333',
        },
        formatter: (params) => {
          const estatus = params.value[2];
          const cantidad = params.value[1];
          const porcentaje = params.value[0];
          return `
            <div style="padding: 5px 10px;">
              <strong style="color: ${params.color}">${estatus}</strong><br>
              Reportes: <b>${cantidad}</b><br>
              Porcentaje: <b>${porcentaje}%</b>
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
          bottom: '10%',
          top: '20%',
        },
        xAxis: {
          name: 'Cantidad',
          type: 'value',
        },
        yAxis: {
          type: 'category',
        },
        series: [
          {
            type: 'bar',
            encode: {
              x: 'cantidad',
              y: 'estatus',
            },
            itemStyle: {
              color: (params) => {
                const estatus = params.value[2];
                const item = this.allData.find(d => d.estatus === estatus);
                return item ? item.color : '#0074D9';
              }
            },
            animationDuration: 800,     // duración de entrada
            animationEasing: 'cubicOut' // curva suave
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
  gap: 10px;
  margin-bottom: 16px;
}

.filter-buttons button {
  padding: 6px 12px;
  border: none;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

.chart1-derecha {
  margin-left: auto;        /* lo empuja hacia la derecha */
  margin-right: 0;
  max-width: 800px;         /* ajusta el tamaño si quieres */
  display: flex;
  flex-direction: column;
  align-items: flex-end;    /* alinea botones y gráfico a la derecha dentro */
  margin: 2rem auto;

}

.chart-wrapper {
  width: 600px;
  margin-left: auto; /* Alineado a la derecha */
  padding: 1rem;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
