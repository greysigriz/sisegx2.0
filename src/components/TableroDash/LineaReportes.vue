<template>
  <div class="container">
    <div class="row">
      <div class="chart1" ref="chart"></div>
    </div>
  </div>
</template>

<script setup>
defineOptions({ name: 'ReportesChart' })
import * as echarts from 'echarts';
import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';
import '@/assets/css/lineareportes_dashboard.css'

const chart = ref(null);
let myChart = null

// API endpoint
const DASHBOARD_API = `${import.meta.env.VITE_API_URL || '/api'}/dashboard-user.php`

// Procesar datos de timeline desde el API
function procesarTimelineData(timelineEstados) {
  // Crear un mapa de fechas con sus estados
  const dateMap = new Map();

  // Procesar cada registro del API
  timelineEstados.forEach(item => {
    if (!dateMap.has(item.fecha)) {
      dateMap.set(item.fecha, {
        fecha: item.fecha,
        Pendiente: 0,
        'En Proceso': 0,
        Atendido: 0
      });
    }

    const dateData = dateMap.get(item.fecha);
    const estado = item.estado.toLowerCase();
    const cantidad = parseInt(item.cantidad) || 0;

    // Clasificar por tipo de estado
    if (estado.includes('sin revisar') ||
        estado.includes('pendiente') ||
        estado.includes('por asignar') ||
        estado.includes('esperando')) {
      dateData.Pendiente += cantidad;
    } else if (estado.includes('completad') ||
               estado.includes('atendid') ||
               estado.includes('cerrad') ||
               estado.includes('resuelto') ||
               estado.includes('finalizado')) {
      dateData.Atendido += cantidad;
    } else if (estado.includes('proceso') ||
               estado.includes('aceptad') ||
               estado.includes('asignad') ||
               estado.includes('devuelto') ||
               estado.includes('en curso')) {
      dateData['En Proceso'] += cantidad;
    }
  });

  // Convertir a array y ordenar por fecha
  return Array.from(dateMap.values()).sort((a, b) =>
    new Date(a.fecha) - new Date(b.fecha)
  );
}

// Cargar datos reales desde el API
async function cargarDatosGrafica() {
  try {
    const response = await axios.get(DASHBOARD_API, {
      params: { source: 'linea-reportes' }
    });

    if (response.data.success && response.data.statistics.timeline_estados) {
      const timelineData = procesarTimelineData(response.data.statistics.timeline_estados);

      if (timelineData.length === 0) {
        console.warn('⚠️ No hay datos de timeline disponibles');
        return [];
      }

      console.log('📊 Datos de timeline cargados:', timelineData.length, 'días');
      return timelineData;
    } else {
      console.warn('⚠️ No se encontraron datos de timeline en la respuesta');
      return [];
    }
  } catch (error) {
    console.error('❌ Error cargando datos de la gráfica:', error);
    return [];
  }
}

onMounted(async () => {
  myChart = echarts.init(chart.value);

  // Cargar datos reales
  const timelineData = await cargarDatosGrafica();

  if (timelineData.length === 0) {
    // Mostrar mensaje si no hay datos
    const emptyOption = {
      title: {
        text: 'Tendencia Temporal de Reportes (Últimos 90 días)',
        subtext: 'No hay datos disponibles',
        left: 'center',
        top: 10,
        textStyle: {
          fontSize: 29,
          fontWeight: 700,
          color: '#1E40AF',
          fontFamily: '"Inter", "Segoe UI", sans-serif'
        }
      }
    };
    myChart.setOption(emptyOption);
    return;
  }

  const option = {
    title: {
      text: 'Tendencia Temporal de Reportes',
      left: 'center',
      top: 10,
      textStyle: {
        fontSize: 29,
        fontWeight: 700,
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
      textStyle: { color: '#1F2937' },
      axisPointer: { type: 'cross' },
      formatter: function (params) {
        const date = params[0]?.axisValue || '';
        let html = `<div style="padding:16px"><strong style="color:#1F2937">📅 ${date}</strong><br/>`;
        params.forEach(p => {
          html += `<div style="color:#6B7280;margin-top:6px"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${p.color};margin-right:8px;"></span>${p.seriesName}: <strong style="color:#111">${p.value}</strong></div>`;
        });
        html += '</div>';
        return html;
      }
    },
    legend: { top: 60, left: 'center' },
    grid: { left: '6%', right: '8%', bottom: '12%', top: 110, containLabel: true },
    xAxis: {
      type: 'category',
      data: timelineData.map(d => d.fecha),
      axisLabel: { rotate: 45 }
    },
    yAxis: { type: 'value' },
    toolbox: {
      feature: { saveAsImage: {} },
      right: 20,
      top: 20
    },
    series: [
      {
        name: 'Pendiente',
        type: 'line',
        data: timelineData.map(d => d.Pendiente),
        smooth: true,
        lineStyle: { width: 2, type: 'dashed' },
        itemStyle: { color: '#82B3FA' }, // Amarillo (consistente con DashboardCards)
        areaStyle: { opacity: 0.1 }
      },
      {
        name: 'En Proceso',
        type: 'line',
        data: timelineData.map(d => d['En Proceso']),
        smooth: true,
        lineStyle: { width: 2 },
        itemStyle: { color: '#287EFA' }, // Azul (consistente con DashboardCards)
        areaStyle: { opacity: 0.1 }
      },
      {
        name: 'Atendido',
        type: 'line',
        data: timelineData.map(d => d.Atendido),
        smooth: true,
        lineStyle: { width: 3 },
        itemStyle: { color: '#0015FF' }, // Verde (consistente con DashboardCards)
        areaStyle: { opacity: 0.1 }
      }
    ]
  };

  myChart.setOption(option);

  const resizeHandler = () => myChart && myChart.resize()
  window.addEventListener('resize', resizeHandler)

  onUnmounted(() => {
    window.removeEventListener('resize', resizeHandler)
    if (myChart) myChart.dispose()
  })
});
</script>
