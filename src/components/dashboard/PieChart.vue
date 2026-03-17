<template>
  <div class="chart-wrapper pie-chart-wrapper">
    <div class="chart-header">
      <div class="chart-header-left">
        <h3 class="chart-title">Reportes Ciudadanos</h3>
        <p class="chart-description">Distribución por categoría de reportes</p>
      </div>
    </div>

    <div ref="pieChart" class="pie-chart"></div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import useDashboardPie from '@/composables/useDashboardPie.js'

const pieChart = ref(null)

useDashboardPie(pieChart)
</script>

<style src="@/assets/css/pie_dashboard.css" scoped></style>
