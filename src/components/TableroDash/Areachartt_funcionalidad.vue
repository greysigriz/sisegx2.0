<!--
  ════════════════════════════════════════════════════════════════════════════
  NOTA: Funcionalidad Movida a Composable
  ════════════════════════════════════════════════════════════════════════════

  La funcionalidad del componente AreaChartt ha sido extraída siguiendo las
  mejores prácticas de Vue 3 Composition API.

  📁 Ubicación de la funcionalidad:
  └─ src/composables/useAreaChart.js

  🎯 Beneficios de esta arquitectura:
  ✅ Reutilización de lógica entre componentes
  ✅ Mejor separación de responsabilidades (UI vs Lógica)
  ✅ Más fácil de testear
  ✅ Código más limpio y mantenible
  ✅ Permite usar la funcionalidad en múltiples componentes

  📚 Cómo usar el composable en otro componente:

  <script setup>
  import { ref } from 'vue'
  import { useAreaChart } from '@/composables/useAreaChart.js'

  const chart = ref(null)

  const {
    datosEstados,
    fetchError,
    isLoading,
    rangoSeleccionado,
    showDebug,
    cambiarRango
  } = useAreaChart(chart)
  </script>

  <template>
    <div ref="chart" class="chart-container"></div>
  </template>

  ════════════════════════════════════════════════════════════════════════════

  📖 API del Composable:

  ESTADO REACTIVO:
  • datosEstados       - Datos procesados del gráfico
  • fetchError         - Errores en la carga de datos
  • isLoading          - Estado de carga
  • rangoSeleccionado  - Rango de días seleccionado (7, 30, 90, 365)
  • showDebug          - Mostrar información de debug

  MÉTODOS:
  • cambiarRango()     - Cambiar el rango de visualización
  • fetchEstados()     - Obtener datos desde la API
  • initChart()        - Inicializar/actualizar el gráfico

  ════════════════════════════════════════════════════════════════════════════

  🔧 Componente original:
  └─ src/components/dashboard/AreaChartt.vue

  ════════════════════════════════════════════════════════════════════════════
-->

<template>
  <div class="info-container">
    <h2>Funcionalidad Movida</h2>
    <p>
      La funcionalidad del gráfico de área ha sido extraída a un composable
      reutilizable ubicado en:
    </p>
    <code>src/composables/useAreaChart.js</code>

    <p>
      Consulta los comentarios en este archivo para más información sobre
      cómo usar el composable.
    </p>
  </div>
</template>

<style scoped>
.info-container {
  padding: 2rem;
  background: linear-gradient(145deg, #f0f9ff 0%, #e0f2fe 100%);
  border-radius: 12px;
  border: 2px solid #3b82f6;
  max-width: 600px;
  margin: 2rem auto;
  font-family: "Inter", "Segoe UI", sans-serif;
}

.info-container h2 {
  color: #1e40af;
  margin-top: 0;
  font-size: 1.5rem;
}

.info-container p {
  color: #475569;
  line-height: 1.6;
  margin: 1rem 0;
}

.info-container code {
  background: #1e293b;
  color: #fbbf24;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: "Fira Code", monospace;
  display: block;
  margin: 1rem 0;
  text-align: center;
}
</style>
