<template>
  <div class="dashboard-metrics">
    <!-- Loading state -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando estadísticas...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-container">
      <p class="error-message">❌ {{ error }}</p>
    </div>

    <!-- Resumen de departamentos -->
    <div class="resumen-header" v-else-if="totales">
      <div class="resumen-item">
        <span class="resumen-label">Total Departamentos</span>
        <span class="resumen-valor">{{ totales.total_departamentos }}</span>
      </div>
      <div class="resumen-item">
        <span class="resumen-label">Total Peticiones</span>
        <span class="resumen-valor">{{ totales.total_peticiones }}</span>
      </div>
      <div class="resumen-item">
        <span class="resumen-label">Pendientes</span>
        <span class="resumen-valor warning">{{ totales.total_pendientes }}</span>
      </div>
      <div class="resumen-item">
        <span class="resumen-label">Completadas</span>
        <span class="resumen-valor success">{{ totales.total_completadas }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import '@/assets/css/cards_dashboard.css'
import { ref, onMounted } from "vue"
import axios from 'axios'

export default {
  name: "DashboardCards",
  setup() {
    const totales = ref(null)
    const isLoading = ref(false)
    const error = ref(null)

    // API endpoint cambiado a tarjetas-depto
    const API_URL = `${import.meta.env.VITE_API_URL || '/api'}/tarjetas-depto.php`

    const cargarTotales = async () => {
      try {
        isLoading.value = true
        error.value = null

        const response = await axios.get(API_URL, {
          params: { source: 'dashboard-cards' }
        })

        if (response.data.success) {
          totales.value = response.data.totales
        } else {
          throw new Error(response.data.message || 'Error al cargar totales')
        }
      } catch (err) {
        console.error('❌ Error cargando totales:', err)
        error.value = err.message || 'Error al cargar estadísticas'
      } finally {
        isLoading.value = false
      }
    }

    onMounted(() => {
      cargarTotales()
    })

    return {
      totales,
      isLoading,
      error
    }
  }
}
</script>


