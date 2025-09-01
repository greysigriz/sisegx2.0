<template>
  <div>
    <!-- Tarjetas del Dashboard -->
    <div class="dashboard-wrapper">
      <div class="dashboard">
        <div
          v-for="(card, index) in cards"
          :key="index"
          class="card"
          @mousemove="handleMouseMove($event, index)"
          @mouseleave="resetTransform(index)"
          :style="{ transform: card.transform }"
        >
          <div class="card-title">{{ card.title }}</div>
          <div class="card-number">
            {{ Math.floor(card.displayValue).toLocaleString() }}
          </div>
          <div class="card-subtext">{{ card.subtext }}</div>
        </div>
      </div>
    </div>

    <!-- Gráfico de Olas -->
    <div class="area-chart-wrapper">
      <div ref="areaChart" class="chart-canvas"></div>
    </div>

    <!-- Contenedor de gráficos y componentes uniformes -->
    <div class="charts-side-container">
      <!-- Gráfico de Barras -->
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
        <div ref="barChart" class="bar-chart"></div>
      </div>

      <!-- Gráfico Circular (Pie Chart) -->
      <div class="chart-wrapper pie-chart-wrapper">
        <div ref="pieChart" class="pie-chart"></div>
      </div>
    </div>

  <!-- NUEVOS COMPONENTES: Reportes, Mapa y Tabla -->
  <Reportes />
  <MapaProblemas />
  <TablaDeps />
  </div>
</template>

<script>
import { onMounted, ref, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import Reportes from './Reportes.vue'
import MapaProblemas from './MapaProblemas.vue'
import TablaDeps from './TablaDeps.vue'

export default {
  name: 'DashboardReportes',
  components: { Reportes, MapaProblemas, TablaDeps },
  setup() {
    // Referencias reactivas
    const areaChart = ref(null)
    const barChart = ref(null)
    const pieChart = ref(null)
    const timelineChart = ref(null)

    // Variables reactivas para las tarjetas
    const cards = ref([
      { title: 'Reportes Totales', value: 1240, displayValue: 0, subtext: 'Actualizado hoy', transform: '' },
      { title: 'Pendientes', value: 312, displayValue: 0, subtext: 'Requieren seguimiento', transform: '' },
      { title: 'Atendidos', value: 872, displayValue: 0, subtext: 'Última semana', transform: '' },
      { title: 'En Proceso', value: 56, displayValue: 0, subtext: 'Actualmente en curso', transform: '' },
    ])

    // Variables para el gráfico de barras
    const allData = ref([
      { porcentaje: 100, cantidad: 1240, estatus: 'Reportes Totales', color: '#1E40AF' },
      { porcentaje: 70.3, cantidad: 872, estatus: 'Atendidos', color: '#3B82F6' },
      { porcentaje: 25.1, cantidad: 312, estatus: 'Pendientes', color: '#60A5FA' },
      { porcentaje: 4.5, cantidad: 56, estatus: 'En Proceso', color: '#93C5FD' },
    ])

    const visibleStatus = ref(['Reportes Totales', 'Atendidos', 'Pendientes', 'En Proceso'])

    // Variables para los gráficos
    let areaChartInstance = null
    let barChartInstance = null
    let pieChartInstance = null
    let timelineChartInstance = null

    // Métodos para las tarjetas
    const animateNumber = (index, targetValue, duration = 1500) => {
      const frameRate = 1000 / 60
      const totalFrames = Math.round(duration / frameRate)
      let frame = 0
      const increment = targetValue / totalFrames

      const counter = setInterval(() => {
        frame++
        cards.value[index].displayValue += increment
        if (frame >= totalFrames) {
          cards.value[index].displayValue = targetValue
          clearInterval(counter)
        }
      }, frameRate)
    }

    const handleMouseMove = (e, index) => {
      const card = e.currentTarget
      const rect = card.getBoundingClientRect()
      const x = e.clientX - rect.left
      const y = e.clientY - rect.top
      const rotateX = ((y / rect.height) - 0.5) * -10
      const rotateY = ((x / rect.width) - 0.5) * 10
      cards.value[index].transform = `perspective(600px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`
    }

    const resetTransform = (index) => {
      cards.value[index].transform = 'perspective(600px) rotateX(0deg) rotateY(0deg)'
    }

    // Método para inicializar el gráfico de olas
    const initAreaChart = () => {
      if (!areaChart.value) return

      areaChartInstance = echarts.init(areaChart.value)

      const option = {
        color: ['#059669', '#2563EB', '#DC2626', '#EA580C', '#B91C1C'],
        title: {
          text: 'Gráfico de Estado de peticiones',
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
          trigger: 'axis',
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
          axisPointer: {
            type: 'cross',
            lineStyle: {
              color: '#3B82F6',
              width: 1,
              type: 'dashed'
            },
            crossStyle: {
              color: '#3B82F6'
            },
            label: {
              backgroundColor: '#3B82F6',
              color: '#ffffff',
              fontFamily: '"Inter", "Segoe UI", sans-serif',
              fontSize: 12,
              borderRadius: 6,
              padding: [4, 8]
            }
          },
          formatter: function(params) {
            let result = `<div style="padding: 12px;">
              <div style="font-weight: 600;
              color: #1F2937;
              margin-bottom: 12px;
              font-size: 15px;">
                📅 ${params[0].axisValue}
              </div>`

            params.reverse().forEach(item => {
              result += `
                <div style="display: flex;
                align-items: center;
                margin-bottom: 6px;
                color: #6B7280;
                font-size: 13px;">

                <div style="
                width: 10px;
                height: 10px;
                background: ${item.color};
                border-radius: 50%;
                margin-right: 10px;"></div>
                  <span style="flex: 1;">${item.seriesName}:</span>

                <strong style="
                color: #1F2937;
                margin-left: 8px;">${item.value}</strong>
                </div>`
            })

            result += '</div>'
            return result
          }
        },
        legend: {
          data: ['Completado', 'Sin revisar', 'Esperando revision', 'Rechazado por el departamento', 'No completado'],
          top: '12%',
          left: 'center',
          textStyle: {
            color: '#374151',
            fontSize: 13,
            fontFamily: '"Inter", "Segoe UI", sans-serif',
            fontWeight: 500
          },
          itemGap: 24,
          itemWidth: 12,
          itemHeight: 12,
          icon: 'circle'
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
        grid: {
          left: '4%',
          right: '4%',
          bottom: '8%',
          top: '25%',
          containLabel: true
        },
        xAxis: [{
          type: 'category',
          boundaryGap: false,
          data: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
          axisLine: {
            lineStyle: {
              color: '#E5E7EB',
              width: 2
            }
          },
          axisLabel: {
            color: '#6B7280',
            fontSize: 13,
            fontFamily: '"Inter", "Segoe UI", sans-serif',
            fontWeight: 500
          },
          axisTick: {
            show: false
          }
        }],
        yAxis: [{
          type: 'value',
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
        }],
        series: [
          {
            name: 'Completado',
            type: 'line',
            stack: 'Total',
            smooth: true,
            lineStyle: { width: 0 },
            showSymbol: false,
            areaStyle: {
              opacity: 0.85,
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(5, 150, 105, 0.8)' },
                { offset: 1, color: 'rgba(5, 150, 105, 0.2)' }
              ])
            },
            emphasis: { focus: 'series' },
            data: [140, 232, 101, 264, 90, 340, 250]
          },
          {
            name: 'Sin revisar',
            type: 'line',
            stack: 'Total',
            smooth: true,
            lineStyle: { width: 0 },
            showSymbol: false,
            areaStyle: {
              opacity: 0.85,
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(37, 99, 235, 0.8)' },
                { offset: 1, color: 'rgba(37, 99, 235, 0.2)' }
              ])
            },
            emphasis: { focus: 'series' },
            data: [120, 282, 111, 234, 220, 340, 310]
          },
          {
            name: 'Esperando revision',
            type: 'line',
            stack: 'Total',
            smooth: true,
            lineStyle: { width: 0 },
            showSymbol: false,
            areaStyle: {
              opacity: 0.85,
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(220, 38, 38, 0.8)' },
                { offset: 1, color: 'rgba(220, 38, 38, 0.2)' }
              ])
            },
            emphasis: { focus: 'series' },
            data: [320, 132, 201, 334, 190, 130, 220]
          },
          {
            name: 'Rechazado por el departamento',
            type: 'line',
            stack: 'Total',
            smooth: true,
            lineStyle: { width: 0 },
            showSymbol: false,
            areaStyle: {
              opacity: 0.85,
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(234, 88, 12, 0.8)' },
                { offset: 1, color: 'rgba(234, 88, 12, 0.2)' }
              ])
            },
            emphasis: { focus: 'series' },
            data: [220, 402, 231, 134, 190, 230, 120]
          },
          {
            name: 'No completado',
            type: 'line',
            stack: 'Total',
            smooth: true,
            lineStyle: { width: 0 },
            showSymbol: false,
            label: {
              show: true,
              position: 'top',
              color: '#374151',
              fontSize: 11,
              fontFamily: '"Inter", "Segoe UI", sans-serif',
              fontWeight: 600
            },
            areaStyle: {
              opacity: 0.85,
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(185, 28, 28, 0.8)' },
                { offset: 1, color: 'rgba(185, 28, 28, 0.2)' }
              ])
            },
            emphasis: { focus: 'series' },
            data: [220, 302, 181, 234, 210, 290, 150]
          }
        ]
      }

      areaChartInstance.setOption(option)
    }

    // Método para el gráfico de barras
    const renderBarChart = () => {
      if (!barChart.value) return

      const filteredData = allData.value.filter(d => visibleStatus.value.includes(d.estatus))
      const datasetSource = [['porcentaje', 'cantidad', 'estatus'], ...filteredData.map(d => [d.porcentaje, d.cantidad, d.estatus])]

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
            const estatus = params.value[2]
            const cantidad = params.value[1]
            const porcentaje = params.value[0]
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
            `
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
        series: [{
          type: 'bar',
          encode: {
            x: 'cantidad',
            y: 'estatus',
          },
          barWidth: '65%',
          itemStyle: {
            color: (params) => {
              const estatus = params.value[2]
              const item = allData.value.find(d => d.estatus === estatus)
              return new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                { offset: 0, color: item ? item.color : '#3B82F6' },
                { offset: 1, color: item ? item.color + 'CC' : '#3B82F6CC' }
              ])
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
        }]
      }

      barChartInstance.setOption(option)
    }

    const initBarChart = () => {
      if (!barChart.value) return
      barChartInstance = echarts.init(barChart.value)
      renderBarChart()
    }

    const toggleStatus = (status) => {
      const index = visibleStatus.value.indexOf(status)
      if (index > -1) {
        visibleStatus.value.splice(index, 1)
      } else {
        visibleStatus.value.push(status)
      }
      renderBarChart()
    }

    // Método para el gráfico circular (Pie Chart)
    const initPieChart = () => {
      if (!pieChart.value) return

      pieChartInstance = echarts.init(pieChart.value)

      const colorVariants = [
        '#1E40AF',  // Azul marino ejecutivo
        '#3B82F6',  // Azul principal
        '#60A5FA',  // Azul claro
        '#93C5FD',  // Azul muy claro
        '#1D4ED8',  // Azul intermedio
        '#2563EB'   // Azul corporativo
      ]

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
            const percentage = params.percent
            const value = params.value
            const name = params.name
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
            `
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
        series: [{
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
                return params.name + '\n' + params.value
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
        }]
      }

      pieChartInstance.setOption(option)
    }

    // Función para generar datos del gráfico temporal
    const generarDatos = () => {
      const data = []
      const inicio = new Date('2025-05-01')

      for (let i = 0; i < 60; i++) {
        const fecha = new Date(inicio)
        fecha.setDate(fecha.getDate() + i)
        const fechaStr = fecha.toISOString().split('T')[0]

        const cantidad = Math.floor(Math.random() * 300) + 10 // entre 10 y 310 reportes
        const nivel = Math.floor(Math.random() * 5) + 1        // nivel 1 a 5

        data.push([fechaStr, cantidad, nivel])
      }

      return data
    }

    // Método para el gráfico de línea temporal
    const initTimelineChart = () => {
      if (!timelineChart.value) return

      timelineChartInstance = echarts.init(timelineChart.value)
      const data = generarDatos()

      const option = {
        title: {
          text: 'Cantidad de Reportes Ciudadanos por Día (Coloreado por Nivel)',
          left: 'center',
          top: 20,
          textStyle: {
            fontSize: 22,
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
            const punto = params[0].data
            const etiquetas = {
              1: '🔴 Crítico',
              2: '🟠 Alto',
              3: '🟡 Medio',
              4: '🟢 Bajo',
              5: '🔵 Muy Bajo'
            }
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
            `
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
          right: 15,
          top: 70,
          feature: {
            dataZoom: {
              yAxisIndex: 'none'
            },
            restore: {},
            saveAsImage: {
              pixelRatio: 2
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
      }

      timelineChartInstance.setOption(option)
    }

    const resizeCharts = () => {
      if (areaChartInstance) {
        areaChartInstance.resize()
      }
      if (barChartInstance) {
        barChartInstance.resize()
      }
      if (pieChartInstance) {
        pieChartInstance.resize()
      }
      if (timelineChartInstance) {
        timelineChartInstance.resize()
      }
    }

    // Lifecycle hooks
    onMounted(() => {
      // Animar números de las tarjetas
      cards.value.forEach((card, index) => {
        setTimeout(() => {
          animateNumber(index, card.value, 1500)
        }, index * 200)
      })

      // Inicializar gráficos
      setTimeout(() => {
        initAreaChart()
        initTimelineChart()
        initBarChart()
        initPieChart()
      }, 100)

      // Listener para redimensionar
      window.addEventListener('resize', resizeCharts)
    })

    onUnmounted(() => {
      window.removeEventListener('resize', resizeCharts)
      if (areaChartInstance) {
        areaChartInstance.dispose()
      }
      if (barChartInstance) {
        barChartInstance.dispose()
      }
      if (pieChartInstance) {
        pieChartInstance.dispose()
      }
      if (timelineChartInstance) {
        timelineChartInstance.dispose()
      }
    })

    return {
      cards,
      allData,
      visibleStatus,
      areaChart,
      barChart,
      pieChart,
      timelineChart,
      animateNumber,
      handleMouseMove,
      resetTransform,
      toggleStatus
    }
  }
}
import '@/assets/css/Dashboard.css'
</script>

