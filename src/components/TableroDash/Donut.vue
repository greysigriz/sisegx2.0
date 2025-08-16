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
    '#1E40AF',  // Azul marino ejecutivo
    '#3B82F6',  // Azul principal
    '#60A5FA',  // Azul claro
    '#93C5FD',  // Azul muy claro
    '#1D4ED8',  // Azul intermedio
    '#2563EB'   // Azul corporativo
  ];

  const option = {
    color: colorVariants,
    title: {
      text: 'Reportes Ciudadanos',
      left: 'center',
      top: 20,
      textStyle: {
        fontSize: 24,
        fontWeight: '700',
        color: '#1E40AF',
        fontFamily: '"Inter", "Segoe UI", sans-serif'
      }
    },
    tooltip: {
      trigger: 'item',
      backgroundColor: '#ffffff',
      borderColor: '#E5E7EB',
      borderWidth: 1,
      borderRadius: 12,
      textStyle: {
        color: '#1F2937',
        fontSize: 14,
        fontFamily: '"Inter", "Segoe UI", sans-serif'
      },
      boxShadow: '0 10px 25px rgba(0, 0, 0, 0.15)',
      formatter: (params) => {
        const percentage = params.percent;
        const value = params.value;
        const name = params.name;
        return `
          <div style="padding: 16px;">
            <div style="display: flex; align-items: center; margin-bottom: 12px;">
              <div style="width: 12px; height: 12px; background: ${params.color}; border-radius: 50%; margin-right: 10px;"></div>
              <strong style="color: #1F2937; font-size: 16px; font-weight: 600;">${name}</strong>
            </div>
            <div style="color: #6B7280; font-size: 14px; line-height: 1.6;">
              <div style="margin-bottom: 4px;">Reportes: <strong style="color: #1F2937;">${value}</strong></div>
              <div>Porcentaje: <strong style="color: #1F2937;">${percentage}%</strong></div>
            </div>
          </div>
        `;
      }
    },
    legend: {
      top: '12%',
      left: 'center',
      textStyle: {
        color: '#374151',
        fontSize: 13,
        fontFamily: '"Inter", "Segoe UI", sans-serif',
        fontWeight: 500
      },
      itemGap: 20,
      itemWidth: 12,
      itemHeight: 12,
      icon: 'circle'
    },
    series: [
      {
        name: 'Reportes Ciudadanos',
        type: 'pie',
        radius: ['45%', '75%'],
        center: ['50%', '60%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 8,
          borderColor: '#ffffff',
          borderWidth: 3,
          shadowColor: 'rgba(0, 0, 0, 0.1)',
          shadowBlur: 8,
          shadowOffsetY: 4
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 28,
            fontWeight: '700',
            color: '#1F2937',
            fontFamily: '"Inter", "Segoe UI", sans-serif',
            formatter: function(params) {
              return params.name + '\n' + params.value;
            }
          },
          itemStyle: {
            shadowColor: 'rgba(0, 0, 0, 0.2)',
            shadowBlur: 15,
            shadowOffsetY: 8,
            borderWidth: 4
          },
          scale: 1.05
        },
        labelLine: {
          show: false
        },
        animationType: 'scale',
        animationEasing: 'elasticOut',
        animationDuration: 1200,
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
.container {
  padding: 32px;
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  margin: 2rem auto;
  max-width: 600px;
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04),
    0 0 0 1px rgba(59, 130, 246, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.6);
  position: relative;
  overflow: hidden;
}

.container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%);
  border-radius: 20px 20px 0 0;
}

.row {
  margin: 0;
  padding: 0;
}

.chart1 {
  width: 100%;
  min-height: 500px;
  padding: 20px 0;
}

@media (max-width: 768px) {
  .container {
    padding: 20px;
    margin: 16px;
  }

  .chart1 {
    min-height: 450px;
  }
}
</style>
