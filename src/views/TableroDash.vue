<template>
  <div>
    <DashboardCards />
    <AreaChart />
    <div class="charts-side-container">
      <BarChart />
      <PieChart />
    </div>

    <!-- Componentes ya existentes -->
    <Reportes />
    <MapaProblemas />
    <TablaDeps />
  </div>
</template>

<script>
import DashboardCards from "@/components/dashboard/DashboardCards.vue"
import AreaChart from "@/components/dashboard/AreaChartt.vue"
import BarChart from "@/components/dashboard/BarChart.vue"
import PieChart from "@/components/dashboard/PieChart.vue"
import '@/assets/css/Dashboard.css'

import Reportes from "@/components/Reportes.vue"
import MapaProblemas from "@/components/MapaProblemas.vue"
import TablaDeps from "@/components/TablaDeps.vue"

export default {
  name: "DashboardReportes",
  components: {
    DashboardCards,
    AreaChart,
    BarChart,
    PieChart,
    Reportes,
    MapaProblemas,
    TablaDeps
  }
}
</script>
