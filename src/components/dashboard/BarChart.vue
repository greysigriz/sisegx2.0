<template>
  <div class="chart-wrapper chart1-derecha">
    <div class="chart-header">
      <div class="chart-header-left">
        <h3 class="chart-title">Top Departamentos</h3>
        <p class="chart-description">Departamentos con más seguimientos</p>
      </div>
      <div class="chart-header-right">
        <select v-model="topFilter" class="top-filter-select">
          <option value="10">Top 10</option>
          <option value="15">Top 15</option>
          <option value="all">Todos</option>
        </select>
      </div>
    </div>

    <div v-if="fetchError" class="api-error">
      <strong>Error cargando dependencias:</strong>
      <pre>{{ prettyError }}</pre>
    </div>

    <div ref="barChart" class="bar-chart"></div>
  </div>
</template>

<script setup>
import '@/assets/css/bar_dashboard.css'
import { ref } from 'vue'
import useDashboardBar from '@/composables/useDashboardBar.js'

const barChart = ref(null)

const {
  topFilter,
  fetchError,
  prettyError
} = useDashboardBar(barChart)
</script>
