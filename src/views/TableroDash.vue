<template>
  <div class="dashboard-container">
    <!-- Loading overlay -->
    <Transition name="fade-loading">
      <div v-if="isLoading" class="dashboard-loading-overlay">
        <div class="dashboard-loading-bar"></div>
      </div>
    </Transition>

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
import { onMounted, onUnmounted } from 'vue'
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
    const { fetchDashboard, isLoading } = useDashboardStore()

    onMounted(() => {
      // Restore dark mode if it was active
      if (localStorage.getItem('dashboard-dark') === '1') {
        document.documentElement.classList.add('dark-mode')
      }
      fetchDashboard()
    })

    onUnmounted(() => {
      // Remove dark mode when leaving the dashboard
      document.documentElement.classList.remove('dark-mode')
    })

    return { isLoading }
  }
}
</script>

<style scoped src="@/assets/css/Dashboard.css"></style>
<style>
/* Global dark mode base - only html/body rules */
html.dark-mode { color-scheme: dark; }
html.dark-mode body { background: #0f172a; color: #e2e8f0; }
</style>
