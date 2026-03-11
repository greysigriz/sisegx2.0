<template>

  <div class="dashboard-container">
    <!-- Header de Yucatán -->
    <YucatanHeader />
    <!-- Dashboard Cards (KPIs principales) -->
    <DashboardCards />
        <!-- Línea de Reportes (Tendencia por Estado) -->
    <AreaChart />
    <!-- Sección de Análisis Principal: 3 columnas -->
    <div class="analytics-grid">
      <!-- Columna 1: Top Departamentos (Filtrable) -->
      <div class="analytics-card">
        <BarChart />
      </div>
      <!-- Columna 2: Distribución por Categoría -->
      <div class="analytics-card2">
        <PieChart />
      </div>
    </div>
    <!-- Tabs para Vistas Alternativas de Departamentos -->
    <div class="department-views">
      <div class="view-tabs">
        <button
          class="tab-btn"
          :class="{ active: activeView === 'map' }"
          @click="activeView = 'map'"
        >
        Mapa de Problemas
        </button>
        <button
          class="tab-btn"
          :class="{ active: activeView === 'table' }"
          @click="activeView = 'table'"
        >
        Tabla Completa (48 Deps)
        </button>
        <button
          class="tab-btn"
          :class="{ active: activeView === 'grid' }"
          @click="activeView = 'grid'"
        >
        Vista en Tarjetas
        </button>
      </div>

      <!-- Contenido según la vista activa -->
      <div class="view-content">
        <MapaProblemas v-if="activeView === 'map'" />
        <TablaDeps v-else-if="activeView === 'table'" />
        <TarjetasDepto v-else-if="activeView === 'grid'" />
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import YucatanHeader from "@/components/dashboard/YucatanHeader.vue"
import DashboardCards from "@/components/dashboard/DashboardCards.vue"
import AreaChart from "@/components/dashboard/AreaChartt.vue"
import BarChart from "@/components/dashboard/BarChart.vue"
import PieChart from "@/components/dashboard/PieChart.vue"
import MapaProblemas from "@/components/dashboard/MapaProblemas.vue"
import TablaDeps from "@/components/TableroDash/ReportsTable.vue"
import TarjetasDepto from "@/components/dashboard/Tarjetas_depto.vue"

export default {
  name: "DashboardReportes",
  components: {
    YucatanHeader,
    DashboardCards,
    AreaChart,
    BarChart,
    PieChart,
    MapaProblemas,
    TablaDeps,
    TarjetasDepto
  },
  setup() {
    const activeView = ref('map')

    return {
      activeView
    }
  }
}
</script>

<style src="@/assets/css/Dashboard.css"></style>

