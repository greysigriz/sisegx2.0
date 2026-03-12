<!-- AreaChartt component mejorado - estilo integrado como en la imagen -->
<template>
  <div class="area-chart-card">
    <div class="area-chart-card-header">
      <div class="card-header-content">
        <div class="card-title-wrapper">
          <h3 class="card-title">Tendencia Mensual</h3>

        </div>
        <p class="card-description">
          Evolución de peticiones asignadas por mes
        </p>
      </div>

      <!-- Controles de vista -->
      <div class="card-actions">
        <select v-model="rangoSeleccionado" @change="cambiarRango" class="range-selector">
          <option value="7">Última semana</option>
          <option value="30">Último mes</option>
          <option value="90">Últimos 3 meses</option>
          <option value="365">Último año</option>
        </select>
      </div>
    </div>

    <div class="area-chart-card-content">
      <!-- Loading state -->
      <div v-if="isLoading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Cargando datos...</p>
      </div>

      <!-- Error state -->
      <div v-else-if="fetchError" class="error-state">
        <div class="error-icon">⚠️</div>
        <p class="error-title">Error cargando datos</p>
        <p class="error-message">{{ fetchError }}</p>
      </div>

      <!-- Chart container -->
      <template v-else>
        <!-- Debug info (colapsable) -->
        <div v-if="datosEstados && datosEstados.debug && showDebug" class="debug-panel">
          <details>
            <summary>🔍 Información de debug</summary>
            <div class="debug-content">
              <div class="debug-row">
                <span>Total registros:</span>
                <strong>{{ datosEstados.debug.total_registros }}</strong>
              </div>
              <div class="debug-row">
                <span>Rango en BD:</span>
                <strong>{{ datosEstados.debug.fecha_min }} → {{ datosEstados.debug.fecha_max }}</strong>
              </div>
              <div class="debug-row">
                <span>Buscando desde:</span>
                <strong>{{ datosEstados.debug.fecha_desde }}</strong>
              </div>
              <div class="debug-row">
                <span>Resultados:</span>
                <strong>{{ datosEstados.debug.resultados_query }}</strong>
              </div>

              <div v-if="datosEstados.debug.total_registros === 0 || datosEstados.debug.total_registros === '0'"
                  class="debug-alert">
                <p><strong>⚠️ No hay datos en la tabla</strong></p>
                <p>Necesitas insertar datos en peticion_departamento para visualizar el gráfico.</p>
              </div>

              <div v-if="datosEstados.debug.estados_existentes && datosEstados.debug.estados_existentes.length > 0"
                  class="debug-estados">
                <strong>Estados en BD:</strong>
                <ul>
                  <li v-for="estado in datosEstados.debug.estados_existentes" :key="estado.estado">
                    {{ estado.estado }}: <strong>{{ estado.count }}</strong>
                  </li>
                </ul>
              </div>
            </div>
          </details>
        </div>
        <!-- Chart -->
        <div ref="chart" class="chart-container"></div>
      </template>
    </div>
  </div>
</template>

<script setup>
import '@/assets/css/areachartt_dashboard.css'
import { ref } from 'vue'
import { useAreaChart } from '@/composables/useAreaChart.js'

// Referencia al elemento del gráfico
const chart = ref(null)

// Usar el composable con la funcionalidad del gráfico
const {
  datosEstados,
  fetchError,
  isLoading,
  rangoSeleccionado,
  showDebug,
  cambiarRango
} = useAreaChart(chart)
</script>
