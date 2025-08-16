<template>
    <div>
      <DashboardCards />
    </div>
    <div class="container">
      <div class="row my-4">
        <div class="col-md-6"> <GradientAreaChart /></div>
      </div>
    </div>
  <div class="parent">
    <div class="div3"> <BarChart /> </div>
    <div class="div4"> <DonutChart /> </div>
    <div class="div5"> <LineaReportes /> </div>
  </div>
    <div>
    <MapaProblemas/>
    </div>
    <div>
      <DepartamentosTabla />
    </div>


</template>

<script setup>
import DashboardCards from '@/components/TableroDash/Tarjetas.vue'
import GradientAreaChart from '@/components/TableroDash/TableroDash.vue'
import BarChart from '@/components/TableroDash/Chart1.vue'
import DepartamentosTabla from '@/components/TableroDash/TablaDeps.vue'
import '@/assets/css/TableroDash.css'
import DonutChart from '@/components/TableroDash/Donut.vue';
import LineaReportes from '@/components/TableroDash/Reportes.vue';
import ProblemasReportados from '@/components/TableroDash/Problemas.vue';
import MapaProblemas from '@/components/TableroDash/MapaProblemas.vue'


</script>
