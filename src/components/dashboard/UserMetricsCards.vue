<template>
  <div class="metrics-container">
    <!-- Tarjeta de Total de Peticiones -->
    <div class="metric-card pulse-card">
      <div class="metric-icon total-icon">
        <i class="fas fa-clipboard-list"></i>
      </div>
      <div class="metric-content">
        <h3 class="metric-value">{{ formatNumber(stats.total_peticiones || 0) }}</h3>
        <p class="metric-label">{{ getTotalLabel }}</p>
      </div>
      <div class="metric-trend positive" v-if="showTrend">
        <i class="fas fa-arrow-up"></i>
        <span>+12%</span>
      </div>
    </div>

    <!-- Tarjetas de Estados -->
    <div
      v-for="estado in getEstadosData"
      :key="estado.estado"
      class="metric-card"
      :class="getCardClass(estado.estado)"
    >
      <div class="metric-icon" :class="getIconClass(estado.estado)">
        <i :class="getIcon(estado.estado)"></i>
      </div>
      <div class="metric-content">
        <h3 class="metric-value">{{ formatNumber(estado.cantidad) }}</h3>
        <p class="metric-label">{{ estado.estado }}</p>
      </div>
      <div class="metric-progress">
        <div
          class="progress-bar"
          :style="{ width: getPercentage(estado.cantidad) + '%' }"
          :class="getProgressClass(estado.estado)"
        ></div>
      </div>
    </div>

    <!-- Tarjeta de Peticiones Críticas (si hay) -->
    <div
      v-if="hasCriticalPetitions"
      class="metric-card critical-card"
    >
      <div class="metric-icon critical-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <div class="metric-content">
        <h3 class="metric-value">{{ getCriticalCount }}</h3>
        <p class="metric-label">Peticiones Críticas</p>
      </div>
      <div class="critical-badge">
        <i class="fas fa-fire"></i>
        <span>¡Urgente!</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserMetricsCards',
  props: {
    stats: {
      type: Object,
      required: true
    },
    userRole: {
      type: Number,
      default: null
    }
  },
  computed: {
    getTotalLabel() {
      if (this.userRole === 1) return 'Total de Peticiones'
      if (this.userRole === 9) return 'Peticiones de tu Municipio'
      return 'Peticiones Asignadas'
    },

    getEstadosData() {
      if (!this.stats.por_estado || !Array.isArray(this.stats.por_estado)) {
        return []
      }
      return this.stats.por_estado
    },

    hasCriticalPetitions() {
      if (!this.stats.por_importancia || !Array.isArray(this.stats.por_importancia)) {
        return false
      }
      const critical = this.stats.por_importancia.find(item => item.NivelImportancia === '1' || item.NivelImportancia === 1)
      return critical && critical.cantidad > 0
    },

    getCriticalCount() {
      if (!this.stats.por_importancia) return 0
      const critical = this.stats.por_importancia.find(item => item.NivelImportancia === '1' || item.NivelImportancia === 1)
      return critical ? critical.cantidad : 0
    },

    showTrend() {
      return this.userRole === 1
    }
  },
  methods: {
    formatNumber(num) {
      return new Intl.NumberFormat('es-MX').format(num)
    },

    getPercentage(cantidad) {
      const total = this.stats.total_peticiones || 1
      return Math.min((cantidad / total) * 100, 100)
    },

    getCardClass(estado) {
      const classes = {
        'Sin revisar': 'new-card',
        'Pendiente': 'pending-card',
        'En proceso': 'progress-card',
        'Completada': 'completed-card',
        'Rechazada': 'rejected-card'
      }
      return classes[estado] || ''
    },

    getIconClass(estado) {
      const classes = {
        'Sin revisar': 'new-icon',
        'Pendiente': 'pending-icon',
        'En proceso': 'progress-icon',
        'Completada': 'completed-icon',
        'Rechazada': 'rejected-icon'
      }
      return classes[estado] || 'default-icon'
    },

    getIcon(estado) {
      const icons = {
        'Sin revisar': 'fas fa-inbox',
        'Pendiente': 'fas fa-clock',
        'En proceso': 'fas fa-cog fa-spin',
        'Completada': 'fas fa-check-circle',
        'Rechazada': 'fas fa-times-circle'
      }
      return icons[estado] || 'fas fa-folder'
    },

    getProgressClass(estado) {
      const classes = {
        'Sin revisar': 'progress-new',
        'Pendiente': 'progress-pending',
        'En proceso': 'progress-active',
        'Completada': 'progress-completed',
        'Rechazada': 'progress-rejected'
      }
      return classes[estado] || ''
    }
  }
}
</script>

<style scoped>
.metrics-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

/* Tarjeta base */
.metric-card {
  background: white;
  border-radius: 16px;
  padding: 1.75rem;
  box-shadow: 0 2px 12px rgba(0, 116, 217, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(0, 116, 217, 0.1);
}

.metric-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #0074D9 0%, #0056a6 100%);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.3s ease;
}

.metric-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 116, 217, 0.15);
}

.metric-card:hover::before {
  transform: scaleX(1);
}

/* Animación de pulso para tarjeta principal */
.pulse-card {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  color: white;
  border: none;
}

.pulse-card .metric-icon {
  background: rgba(255, 255, 255, 0.2);
}

.pulse-card .metric-value,
.pulse-card .metric-label {
  color: white;
}

/* Iconos */
.metric-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  margin-bottom: 1rem;
  transition: all 0.3s ease;
}

.total-icon {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.new-icon {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%);
  color: #3b82f6;
}

.pending-icon {
  background: linear-gradient(135deg, rgba(251, 146, 60, 0.1) 0%, rgba(249, 115, 22, 0.1) 100%);
  color: #f97316;
}

.progress-icon {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
  color: #8b5cf6;
}

.completed-icon {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%);
  color: #22c55e;
}

.rejected-icon {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
  color: #ef4444;
}

.critical-icon {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%);
  color: #dc2626;
  animation: pulse-critical 2s ease-in-out infinite;
}

@keyframes pulse-critical {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

/* Contenido */
.metric-content {
  margin-bottom: 1rem;
}

.metric-value {
  font-size: 2.25rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.metric-label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Barra de progreso */
.metric-progress {
  width: 100%;
  height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
  margin-top: 1rem;
}

.progress-bar {
  height: 100%;
  border-radius: 3px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.progress-new {
  background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
}

.progress-pending {
  background: linear-gradient(90deg, #f97316 0%, #ea580c 100%);
}

.progress-active {
  background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%);
}

.progress-completed {
  background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
}

.progress-rejected {
  background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

/* Tendencias */
.metric-trend {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.metric-trend i {
  font-size: 0.75rem;
}

/* Badge crítico */
.critical-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #fee2e2;
  color: #dc2626;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  animation: shake 0.5s ease-in-out infinite;
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }
  25% {
    transform: translateX(-2px);
  }
  75% {
    transform: translateX(2px);
  }
}

.critical-card {
  border: 2px solid #fecaca;
  background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
}

/* Responsive */
@media (max-width: 768px) {
  .metrics-container {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .metric-card {
    padding: 1.5rem;
  }

  .metric-value {
    font-size: 1.875rem;
  }

  .metric-icon {
    width: 48px;
    height: 48px;
    font-size: 20px;
  }
}
</style>
