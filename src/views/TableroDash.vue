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

<style>
/* Estilos globales de layout para Dashboard */
.charts-side-container {
  display: flex;
  gap: 30px;
  max-width: 1400px;
  margin: 2rem auto;
  align-items: flex-start;
  justify-content: flex-start;
}

/* Tabla de Departamentos */
.card-table {
  background-color: #ffffff;
  padding: 1.5rem;
  border-radius: 16px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 1400px;
  margin: 2rem auto;
  overflow-x: auto;
}

.card-table h3 {
  margin-bottom: 1.2rem;
  font-size: 1.3rem;
  color: #0074D9;
  text-align: center;
  font-weight: bold;
}

.card-table table {
  width: 100%;
  border-collapse: collapse;
}

.card-table th,
.card-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #eaeaea;
  text-align: left;
  font-size: 0.95rem;
}

.card-table th {
  background-color: #f4f8fc;
  font-weight: 600;
  color: #333;
}

.card-table tbody tr:nth-child(odd) {
  background-color: #f9fbfd;
}

.card-table tbody tr:hover {
  background-color: #eef6fc;
}

.card-table .seguimiento-box {
  background-color: #0074D9;
  color: #fff;
  padding: 6px 12px;
  border-radius: 8px;
  font-weight: bold;
  text-align: center;
  display: inline-block;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 116, 217, 0.3);
}

.card-table tbody tr:hover .seguimiento-box {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.4);
}

@media (max-width: 768px) {
  .charts-side-container {
    flex-direction: column;
    gap: 20px;
  }
}
</style>
<style src="@/assets/css/Dashboard.css"></style>
