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
      left: 'center',
      top: 20,
      textStyle: {
        fontSize: 29,
        fontWeight: '700',
        color: '#1E40AF',
        fontFamily: '"Inter", "Segoe UI", sans-serif'
      }
    },
    tooltip: {
      trigger: 'axis',
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
          <div style="padding: 16px;">
            <div style="font-weight: 600; color: #1F2937; margin-bottom: 12px; font-size: 15px;">
              📅 ${punto[0]}
            </div>
            <div style="color: #6B7280; font-size: 14px; line-height: 1.6;">
              <div style="margin-bottom: 6px;">📈 Reportes: <strong style="color: #1F2937;">${punto[1]}</strong></div>
              <div>🧭 Nivel: <strong style="color: #1F2937;">${etiquetas[punto[2]]} (${punto[2]})</strong></div>
            </div>
          </div>
        `;
      }
    },
    grid: {
      left: '6%',
      right: '18%',
      bottom: '15%',
      top: '20%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      data: data.map(item => item[0]),
      axisLine: {
        lineStyle: {
          color: '#E5E7EB',
          width: 2
        }
      },
      axisLabel: {
        color: '#6B7280',
        fontSize: 12,
        fontFamily: '"Inter", "Segoe UI", sans-serif',
        rotate: 45
      },
      axisTick: {
        show: false
      }
    },
    yAxis: {
      type: 'value',
      name: 'Cantidad de Reportes',
      nameTextStyle: {
        color: '#374151',
        fontSize: 14,
        fontFamily: '"Inter", "Segoe UI", sans-serif',
        fontWeight: 500
      },
      axisLine: {
        lineStyle: {
          color: '#E5E7EB',
          width: 2
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
          width: 1,
          type: 'dashed'
        }
      }
    },
    toolbox: {
      right: 20,
      top: 20,
      feature: {
        saveAsImage: {
          pixelRatio: 2,
          backgroundColor: '#ffffff'
        }
      },
      iconStyle: {
        borderColor: '#3B82F6',
        borderWidth: 2
      },
      emphasis: {
        iconStyle: {
          borderColor: '#1E40AF'
        }
      }
    },
    dataZoom: [
      {
        startValue: data[0][0],
        backgroundColor: '#F8FAFC',
        fillerColor: 'rgba(59, 130, 246, 0.2)',
        borderColor: '#3B82F6',
        handleStyle: {
          color: '#3B82F6',
          borderColor: '#1E40AF'
        },
        textStyle: {
          color: '#374151',
          fontFamily: '"Inter", "Segoe UI", sans-serif'
        }
      },
      {
        type: 'inside'
      }
    ],
    visualMap: {
      top: 80,
      right: 15,
      dimension: 2, // usamos el índice del nivel para colorear
      pieces: [
        { value: 1, color: '#DC2626', label: '🔴 Crítico' },
        { value: 2, color: '#EA580C', label: '🟠 Alto' },
        { value: 3, color: '#D97706', label: '🟡 Medio' },
        { value: 4, color: '#059669', label: '🟢 Bajo' },
        { value: 5, color: '#2563EB', label: '🔵 Muy Bajo' }
      ],
      outOfRange: {
        color: '#9CA3AF'
      },
      textStyle: {
        color: '#374151',
        fontSize: 12,
        fontFamily: '"Inter", "Segoe UI", sans-serif'
      },
      itemWidth: 14,
      itemHeight: 14,
      itemGap: 8,
      backgroundColor: '#F8FAFC',
      borderColor: '#E5E7EB',
      borderWidth: 1,
      borderRadius: 8,
      padding: 12
    },
    series: {
      name: 'Cantidad de Reportes',
      type: 'line',
      showSymbol: true,
      symbolSize: 6,
      data: data.map(item => [item[0], item[1], item[2]]),
      lineStyle: {
        width: 3,
        shadowColor: 'rgba(0, 0, 0, 0.1)',
        shadowBlur: 4,
        shadowOffsetY: 2
      },
      itemStyle: {
        borderWidth: 2,
        borderColor: '#ffffff',
        shadowColor: 'rgba(0, 0, 0, 0.1)',
        shadowBlur: 4,
        shadowOffsetY: 2
      },
      emphasis: {
        symbolSize: 10,
        itemStyle: {
          borderWidth: 3,
          shadowBlur: 8,
          shadowOffsetY: 4
        }
      },
      encode: {
        x: 0, // fecha
        y: 1  // cantidad
      },
      smooth: 0.3,
      animationDuration: 1200,
      animationEasing: 'cubicOut',
      markLine: {
        silent: true,
        lineStyle: {
          color: '#D1D5DB',
          width: 1,
          type: 'dashed',
          opacity: 0.6
        },
        label: {
          color: '#9CA3AF',
          fontSize: 11,
          fontFamily: '"Inter", "Segoe UI", sans-serif'
        },
        data: [
          { yAxis: 50, name: '50' },
          { yAxis: 100, name: '100' },
          { yAxis: 200, name: '200' },
          { yAxis: 300, name: '300' }
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
.container {
  padding: 32px;
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  margin: 2rem auto;
  max-width: 1400px;
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
