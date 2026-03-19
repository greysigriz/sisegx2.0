<template>
  <div class="dashboard-container">
    <!-- Header -->
    <YucatanHeader />

    <!-- Filtro de fecha -->
    <DateFilter />

    <!-- KPIs compactos -->
    <DashboardCards />

    <!-- Mapa Choropleth -->
    <div class="department-views">
      <MapaProblemas />
    </div>

    <!-- Panel de Analisis (Dept/Muni) + Distribucion por Estado -->
    <div class="analytics-grid">
      <div class="analytics-card">
        <AnalysisPanel />
      </div>
      <div class="analytics-card2">
        <PieChart />
      </div>
    </div>

    <!-- Timeline de progreso -->
    <AreaChart />
  </div>
</template>

<script>
import { onMounted } from 'vue'
import YucatanHeader from "@/components/dashboard/YucatanHeader.vue"
import DateFilter from "@/components/dashboard/DateFilter.vue"
import DashboardCards from "@/components/dashboard/DashboardCards.vue"
import AnalysisPanel from "@/components/dashboard/AnalysisPanel.vue"
import AreaChart from "@/components/dashboard/AreaChartt.vue"
import PieChart from "@/components/dashboard/PieChart.vue"
import MapaProblemas from "@/components/dashboard/MapaProblemas.vue"
import { useDashboardStore } from '@/composables/useDashboardStore.js'

export default {
  name: "DashboardReportes",
  components: {
    YucatanHeader,
    DateFilter,
    DashboardCards,
    AnalysisPanel,
    AreaChart,
    PieChart,
    MapaProblemas
  },
  setup() {
    const { fetchDashboard } = useDashboardStore()

    onMounted(() => {
      fetchDashboard()
    })

    return {}
  }
}
</script>

<style src="@/assets/css/Dashboard.css"></style>
