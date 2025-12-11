import * as echarts from "echarts"

let charts = {
  area: null,
  bar: null,
  pie: null
}

export default function useDashboardCharts(allData = null, visibleStatus = null) {

  const destroyChart = (name) => {
    if (charts[name]) {
      charts[name].dispose()
      charts[name] = null
    }
  }

  const initAreaChart = (element) => {
    destroyChart("area")
    charts.area = echarts.init(element.value)
    charts.area.setOption({
      /* EL OPTION COMPLETO que ya tienes */
    })
  }

  const initBarChart = (element) => {
    destroyChart("bar")

    const filtered = allData.value.filter(d => visibleStatus.value.includes(d.estatus))

    charts.bar = echarts.init(element.value)
    charts.bar.setOption({
      xAxis: { type: "category", data: filtered.map(d => d.estatus) },
      yAxis: { type: "value" },
      series: [{
        type: "bar",
        data: filtered.map(d => d.cantidad),
        itemStyle: {
          color: (p) => filtered[p.dataIndex].color
        }
      }]
    })
  }

  const initPieChart = (element) => {
    destroyChart("pie")

    charts.pie = echarts.init(element.value)
    charts.pie.setOption({
      series: [
        {
          type: "pie",
          radius: "60%",
          data: [
            { value: 1240, name: "Reportes Totales" },
            { value: 872, name: "Atendidos" },
            { value: 312, name: "Pendientes" },
            { value: 56, name: "En Proceso" }
          ]
        }
      ]
    })
  }

  return {
    initAreaChart,
    initBarChart,
    initPieChart,
    destroyChart
  }
}
