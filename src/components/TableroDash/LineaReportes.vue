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
import '@/assets/css/lineareportes_dashboard.css'

const chart = ref(null);

// Genera 60 días de datos agregados por estado: Pendiente, En Proceso, Atendido
function generarTimelineDias(days = 60) {
  const result = [];
  const inicio = new Date();
  inicio.setDate(inicio.getDate() - (days - 1));

  for (let i = 0; i < days; i++) {
    const fecha = new Date(inicio);
    fecha.setDate(fecha.getDate() + i);
    const fechaStr = fecha.toISOString().split('T')[0];

    // valores aleatorios por estado (puedes reemplazar con datos reales)
    const pendiente = Math.floor(Math.random() * 120) + 10;
    const enProceso = Math.floor(Math.random() * 140) + 5;
    const atendido = Math.floor(Math.random() * 180) + 2;

    result.push({ date: fechaStr, Pendiente: pendiente, 'En Proceso': enProceso, Atendido: atendido });
  }

  return result;
}

let myChart = null

onMounted(() => {
  myChart = echarts.init(chart.value);
  const timelineData = generarTimelineDias(60).slice(-30); // últimos 30 días

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
        let html = `<div style="padding:12px"><strong style="color:#1F2937">📅 ${date}</strong><br/>`;
        params.forEach(p => {
          html += `<div style="color:#6B7280;margin-top:6px"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${p.color};margin-right:8px;"></span>${p.seriesName}: <strong style="color:#111">${p.value}</strong></div>`;
        });
        html += '</div>';
        return html;
      }
    },
    legend: { top: 60, left: 'center' },
    grid: { left: '6%', right: '8%', bottom: '12%', top: 110, containLabel: true },
    xAxis: { type: 'category', data: timelineData.map(d => d.date), axisLabel: { rotate: 45 } },
    yAxis: { type: 'value' },
    toolbox: { feature: { saveAsImage: {} }, right: 20, top: 20 },
    series: [
      {
        name: 'Pendiente',
        type: 'line',
        data: timelineData.map(d => d.Pendiente),
        smooth: true,
        lineStyle: { width: 2, type: 'dashed' },
        itemStyle: { color: '#93c5fd' }
      },
      {
        name: 'En Proceso',
        type: 'line',
        data: timelineData.map(d => d['En Proceso']),
        smooth: true,
        lineStyle: { width: 2 },
        itemStyle: { color: '#3b82f6' }
      },
      {
        name: 'Atendido',
        type: 'line',
        data: timelineData.map(d => d.Atendido),
        smooth: true,
        lineStyle: { width: 3 },
        itemStyle: { color: '#1e40af' }
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
