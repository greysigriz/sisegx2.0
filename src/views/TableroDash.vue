<template>
  <div>
    <!-- Header de Yucatán -->
    <YucatanHeader />
    
    <!-- Dashboard Cards y Gráficos -->
    <DashboardCards />
    <AreaChart />
    <div class="charts-side-container">
      <BarChart />
      <PieChart />
    </div>

    <!-- Sección de Municipios de Yucatán -->
    <MunicipiosYucatan />

    <!-- Componentes ya existentes -->
    <Reportes />
    <MapaProblemas />
    <TablaDeps />
  </div>
</template>

<script>
import YucatanHeader from "@/components/dashboard/YucatanHeader.vue"
import MunicipiosYucatan from "@/components/dashboard/MunicipiosYucatan.vue"
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
    YucatanHeader,
    MunicipiosYucatan,
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
