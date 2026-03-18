<template>
  <div class="dashboard-container">
    <!-- Header -->
    <YucatanHeader />

    <!-- KPIs compactos -->
    <DashboardCards />

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

    <!-- Mapa Choropleth -->
    <div class="department-views">
      <MapaProblemas />
    </div>
  </div>
</template>

<script>
import { onMounted } from 'vue'
import YucatanHeader from "@/components/dashboard/YucatanHeader.vue"
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
