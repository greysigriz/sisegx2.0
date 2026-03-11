<template>
  <div class="tarjetas-container">
    <!-- Grid de métricas (4 cards) -->
    <div class="metrics-container" v-if="!isLoading && !error">
      <div class="metrics-grid">
        <!-- Card 1: Reportes Totales -->
        <div class="metric-card">
          <div class="metric-label">Reportes Totales</div>
          <div class="metric-value-row">
            <div class="metric-value">{{ Math.floor(cards[0].displayValue).toLocaleString() }}</div>
          </div>
        </div>

        <!-- Card 2: Pendientes -->
        <div class="metric-card">
          <div class="metric-label">Pendientes</div>
          <div class="metric-value-row">
            <div class="metric-value">{{ Math.floor(cards[1].displayValue).toLocaleString() }}</div>
          </div>
        </div>

        <!-- Card 3: Atendidos -->
        <div class="metric-card">
          <div class="metric-label">Atendidos</div>
          <div class="metric-value-row">
            <div class="metric-value">{{ Math.floor(cards[2].displayValue).toLocaleString() }}</div>
          </div>
        </div>

        <!-- Card 4: En Proceso -->
        <div class="metric-card">
          <div class="metric-label">En Proceso</div>
          <div class="metric-value-row">
            <div class="metric-value">{{ Math.floor(cards[3].displayValue).toLocaleString() }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando departamentos...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-container">
      <p class="error-message">❌ {{ error }}</p>
    </div>

    <!-- Grid de tarjetas de departamentos -->
    <div v-else class="department-grid">
      <div
        v-for="dept in departamentos"
        :key="dept.departamento_id"
        class="dept-card"
        :class="{
          'has-criticas': dept.criticas > 0,
          'no-peticiones': dept.total_peticiones == 0
        }"
      >
        <div class="dept-header">
          <h4 class="dept-nombre">{{ dept.departamento_nombre }}</h4>
          <span
            v-if="dept.criticas > 0"
            class="badge-criticas"
            :title="`${dept.criticas} peticiones críticas`"
          >
            🚨 {{ dept.criticas }}
          </span>
          <span
            v-else-if="dept.total_peticiones == 0"
            class="badge-sin-peticiones"
          >
            Sin asignaciones
          </span>
        </div>

        <div class="dept-stats">
          <div class="stat-row">
            <span class="stat-label">Total:</span>
            <span class="stat-value total">{{ dept.total_peticiones }}</span>
          </div>
          <div class="stat-row">
            <span class="stat-label">Pendientes:</span>
            <span class="stat-value pendientes">{{ dept.pendientes }}</span>
          </div>
          <div class="stat-row">
            <span class="stat-label">En Proceso:</span>
            <span class="stat-value proceso">{{ dept.en_proceso }}</span>
          </div>
          <div class="stat-row">
            <span class="stat-label">Completadas:</span>
            <span class="stat-value completadas">{{ dept.completadas }}</span>
          </div>
          <div class="stat-row" v-if="dept.devueltas > 0">
            <span class="stat-label">Devueltas/Rechazadas:</span>
            <span class="stat-value devueltas">{{ dept.devueltas }}</span>
          </div>
        </div>

        <div class="dept-footer">
          <span class="ultima-asignacion" v-if="dept.ultima_asignacion">
            Última: {{ formatFecha(dept.ultima_asignacion) }}
          </span>
          <span class="ultima-asignacion" v-else>
            Sin asignaciones previas
          </span>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="!isLoading && !error && departamentos.length === 0" class="empty-state">
      <p>No hay departamentos registrados</p>
    </div>
  </div>
</template>

<script>
import '@/assets/css/tarjetas_depto_dashboard.css'
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

export default {
  name: 'TarjetasDepto',
  setup() {
    const departamentos = ref([])
    const totales = ref(null)
    const isLoading = ref(false)
    const error = ref(null)

    // Cards para métricas generales
    const cards = ref([
      { title: 'Reportes Totales', value: 0, displayValue: 0, trend: 0, badgeColor: 'badge-green' },
      { title: 'Pendientes', value: 0, displayValue: 0, trend: 0, badgeColor: 'badge-yellow' },
      { title: 'Atendidos', value: 0, displayValue: 0, trend: 0, badgeColor: 'badge-green' },
      { title: 'En Proceso', value: 0, displayValue: 0, trend: 0, badgeColor: 'badge-blue' },
    ])

    const animationIntervals = ref([])

    const API_URL = `${import.meta.env.VITE_API_URL || '/api'}/tarjetas-depto.php`
    const DASHBOARD_API = `${import.meta.env.VITE_API_URL || '/api'}/dashboard-user.php`

    const animateNumber = (index, targetValue, duration = 1500) => {
      if (document.hidden) {
        cards.value[index].displayValue = targetValue
        return
      }

      const frameRate = 1000 / 60
      const totalFrames = Math.round(duration / frameRate)
      let frame = 0
      const increment = targetValue / totalFrames

      const counter = setInterval(() => {
        if (document.hidden) {
          clearInterval(counter)
          return
        }

        frame++
        cards.value[index].displayValue += increment
        if (frame >= totalFrames) {
          cards.value[index].displayValue = targetValue
          clearInterval(counter)
        }
      }, frameRate)

      animationIntervals.value.push(counter)
    }

    const cargarMetricas = async () => {
      try {
        const response = await axios.get(DASHBOARD_API, {
          params: { source: 'tarjetas-depto' }
        })

        if (response.data.success) {
          const stats = response.data.statistics

          if (!stats) {
            console.error('⚠️ No se encontró el objeto statistics')
            return
          }

          cards.value[0].value = stats.total_peticiones || 0

          let pendientes = 0
          let atendidos = 0
          let enProceso = 0

          if (stats.por_estado && Array.isArray(stats.por_estado)) {
            stats.por_estado.forEach(item => {
              const estado = item.estado?.toLowerCase() || ''
              const cantidad = parseInt(item.cantidad) || 0

              if (estado.includes('sin revisar') ||
                  estado.includes('pendiente') ||
                  estado.includes('por asignar') ||
                  estado.includes('esperando')) {
                pendientes += cantidad
              }
              else if (estado.includes('completad') ||
                       estado.includes('atendid') ||
                       estado.includes('cerrad') ||
                       estado.includes('resuelto') ||
                       estado.includes('finalizado')) {
                atendidos += cantidad
              }
              else if (estado.includes('proceso') ||
                       estado.includes('aceptad') ||
                       estado.includes('asignad') ||
                       estado.includes('devuelto') ||
                       estado.includes('en curso')) {
                enProceso += cantidad
              }
            })
          }

          cards.value[1].value = pendientes
          cards.value[2].value = atendidos
          cards.value[3].value = enProceso

          cards.value[0].badgeColor = cards.value[0].value >= 50 ? 'badge-green' : 'badge-red'
          cards.value[1].badgeColor = 'badge-yellow'
          cards.value[2].badgeColor = 'badge-green'
          cards.value[3].badgeColor = 'badge-blue'

          cards.value.forEach((c, index) => animateNumber(index, c.value))
        }
      } catch (err) {
        console.error('❌ Error cargando métricas:', err)
      }
    }

    const cargarDepartamentos = async () => {
      try {
        isLoading.value = true
        error.value = null

        const response = await axios.get(API_URL, {
          params: { source: 'tarjetas-depto' }
        })

        if (response.data.success) {
          departamentos.value = response.data.departamentos
          totales.value = response.data.totales
        } else {
          throw new Error(response.data.message || 'Error al cargar departamentos')
        }
      } catch (err) {
        console.error('❌ Error cargando departamentos:', err)
        error.value = err.message || 'Error al cargar datos'
      } finally {
        isLoading.value = false
      }
    }

    const formatFecha = (fecha) => {
      if (!fecha) return 'N/A'

      const date = new Date(fecha)
      const ahora = new Date()
      const diff = Math.floor((ahora - date) / (1000 * 60 * 60 * 24))

      if (diff === 0) return 'Hoy'
      if (diff === 1) return 'Ayer'
      if (diff < 7) return `Hace ${diff} días`
      if (diff < 30) return `Hace ${Math.floor(diff / 7)} semanas`
      return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' })
    }

    onMounted(() => {
      cargarMetricas()
      cargarDepartamentos()
    })

    onUnmounted(() => {
      animationIntervals.value.forEach(interval => clearInterval(interval))
      animationIntervals.value = []
    })

    return {
      departamentos,
      totales,
      cards,
      isLoading,
      error,
      formatFecha
    }
  }
}
</script>
