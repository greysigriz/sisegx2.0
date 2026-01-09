<template>
  <div class="recent-activity-container">
    <div class="section-header">
      <div class="header-left">
        <i class="fas fa-history"></i>
        <h2>Actividad Reciente</h2>
      </div>
      <div class="header-right">
        <button @click="prevSlide" class="carousel-control" :disabled="currentIndex === 0">
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="carousel-indicator">{{ currentIndex + 1 }} / {{ totalSlides }}</span>
        <button @click="nextSlide" class="carousel-control" :disabled="currentIndex >= totalSlides - 1">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Alertas importantes -->
    <div v-if="alerts && alerts.length > 0" class="alerts-section">
      <div
        v-for="(alert, index) in alerts"
        :key="index"
        class="alert-card"
        :class="getAlertClass(alert.type)"
      >
        <div class="alert-icon">
          <i :class="getAlertIcon(alert.type)"></i>
        </div>
        <div class="alert-content">
          <p>{{ alert.message }}</p>
        </div>
        <div class="alert-badge">
          {{ alert.count }}
        </div>
      </div>
    </div>

    <!-- Carrusel de peticiones -->
    <div v-if="petitions && petitions.length > 0" class="carousel-container">
      <div class="carousel-track" :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
        <div
          v-for="petition in petitions"
          :key="petition.id"
          class="carousel-slide"
        >
          <div class="petition-card">
            <!-- Header de la tarjeta -->
            <div class="petition-header">
              <div class="folio-badge">
                <i class="fas fa-hashtag"></i>
                <span>{{ petition.folio }}</span>
              </div>
              <div class="priority-badge" :class="getPriorityClass(petition.NivelImportancia)">
                {{ getPriorityLabel(petition.NivelImportancia) }}
              </div>
            </div>

            <!-- Contenido -->
            <div class="petition-body">
              <h3 class="petition-title">{{ petition.nombre }}</h3>
              <p class="petition-description">{{ truncateText(petition.descripcion, 150) }}</p>

              <div class="petition-meta">
                <div class="meta-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span>{{ petition.Municipio || 'Sin municipio' }}</span>
                </div>
                <div class="meta-item">
                  <i class="fas fa-calendar-alt"></i>
                  <span>{{ formatDate(petition.fecha_registro || petition.fecha_asignacion) }}</span>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="petition-footer">
              <div class="status-badge" :class="getStatusClass(petition.estado || petition.estado_departamento)">
                <i :class="getStatusIcon(petition.estado || petition.estado_departamento)"></i>
                <span>{{ petition.estado || petition.estado_departamento }}</span>
              </div>
              <button @click="viewDetails(petition)" class="view-btn">
                <span>Ver detalles</span>
                <i class="fas fa-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <i class="fas fa-inbox"></i>
      </div>
      <h3>No hay actividad reciente</h3>
      <p>Las peticiones aparecerán aquí cuando se registren</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RecentActivity',
  props: {
    petitions: {
      type: Array,
      default: () => []
    },
    alerts: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      lastUpdate: new Date(),
      currentIndex: 0
    }
  },
  computed: {
    totalSlides() {
      return this.petitions.length
    }
  },
  methods: {
    nextSlide() {
      if (this.currentIndex < this.totalSlides - 1) {
        this.currentIndex++
      }
    },
    prevSlide() {
      if (this.currentIndex > 0) {
        this.currentIndex--
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'Sin fecha'
      const date = new Date(dateString)
      const now = new Date()
      const diffTime = Math.abs(now - date)
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
      const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
      const diffMinutes = Math.floor(diffTime / (1000 * 60))

      if (diffMinutes < 1) return 'Hace un momento'
      if (diffMinutes < 60) return `Hace ${diffMinutes} minutos`
      if (diffHours < 24) return `Hace ${diffHours} horas`
      if (diffDays < 7) return `Hace ${diffDays} días`

      return date.toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'short',
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
      })
    },

    formatTime(date) {
      const now = new Date()
      const diff = Math.floor((now - date) / 1000)

      if (diff < 60) return 'hace unos segundos'
      if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`
      return date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
    },

    truncateText(text, maxLength) {
      if (!text) return ''
      if (text.length <= maxLength) return text
      return text.substring(0, maxLength) + '...'
    },

    getPriorityClass(nivel) {
      const classes = {
        1: 'priority-critical',
        2: 'priority-high',
        3: 'priority-medium',
        4: 'priority-low'
      }
      return classes[nivel] || 'priority-medium'
    },

    getPriorityLabel(nivel) {
      const labels = {
        1: '🔴 Crítico',
        2: '🟠 Alto',
        3: '🟡 Medio',
        4: '🟢 Bajo'
      }
      return labels[nivel] || 'Medio'
    },

    getStatusClass(estado) {
      const classes = {
        'Sin revisar': 'status-new',
        'Pendiente': 'status-pending',
        'En proceso': 'status-progress',
        'Completada': 'status-completed',
        'Rechazada': 'status-rejected'
      }
      return classes[estado] || 'status-new'
    },

    getStatusIcon(estado) {
      const icons = {
        'Sin revisar': 'fas fa-inbox',
        'Pendiente': 'fas fa-clock',
        'En proceso': 'fas fa-spinner fa-spin',
        'Completada': 'fas fa-check-circle',
        'Rechazada': 'fas fa-times-circle'
      }
      return icons[estado] || 'fas fa-folder'
    },

    getAlertClass(type) {
      return type === 'critical' ? 'alert-critical' : 'alert-warning'
    },

    getAlertIcon(type) {
      return type === 'critical' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle'
    },

    viewDetails(petition) {
      this.$emit('view-petition', petition.id)
    }
  }
}
</script>

<style scoped>
.recent-activity-container {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 2px 12px rgba(0, 116, 217, 0.08);
}

/* Header */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-left i {
  font-size: 1.5rem;
  color: #0074D9;
}

.header-left h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.carousel-control {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #0074D9;
}

.carousel-control:hover:not(:disabled) {
  border-color: #0074D9;
  background: #f0f9ff;
  transform: scale(1.05);
}

.carousel-control:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.carousel-indicator {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 600;
  padding: 0 0.5rem;
}

/* Carrusel */
.carousel-container {
  position: relative;
  overflow: hidden;
  border-radius: 12px;
}

.carousel-track {
  display: flex;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.carousel-slide {
  flex: 0 0 100%;
  min-width: 100%;
}

/* Alertas */
.alerts-section {
  margin-bottom: 1.5rem;
}

.alert-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  margin-bottom: 0.75rem;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.alert-critical {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border: 1px solid #fecaca;
}

.alert-warning {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border: 1px solid #fde68a;
}

.alert-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.alert-critical .alert-icon {
  background: #dc2626;
  color: white;
}

.alert-warning .alert-icon {
  background: #f59e0b;
  color: white;
}

.alert-content {
  flex: 1;
}

.alert-content p {
  margin: 0;
  font-weight: 600;
  color: #1e293b;
}

.alert-badge {
  background: white;
  color: #dc2626;
  font-weight: 700;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
}

/* Peticiones */
.petitions-list {
  display: grid;
  gap: 1rem;
}

.petition-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.petition-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 116, 217, 0.12);
  border-color: #0074D9;
}

/* Petition Header */
.petition-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.folio-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  color: white;
  padding: 0.375rem 0.875rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.folio-badge i {
  font-size: 0.75rem;
}

.priority-badge {
  padding: 0.375rem 0.875rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.priority-critical {
  background: #fee2e2;
  color: #dc2626;
}

.priority-high {
  background: #fed7aa;
  color: #ea580c;
}

.priority-medium {
  background: #fef9c3;
  color: #ca8a04;
}

.priority-low {
  background: #d1fae5;
  color: #059669;
}

/* Petition Body */
.petition-body {
  margin-bottom: 1rem;
}

.petition-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.petition-description {
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.5;
  margin: 0 0 0.75rem 0;
}

.petition-meta {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
}

.meta-item i {
  color: #0074D9;
  font-size: 0.75rem;
}

/* Petition Footer */
.petition-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-new {
  background: #dbeafe;
  color: #1e40af;
}

.status-pending {
  background: #fed7aa;
  color: #9a3412;
}

.status-progress {
  background: #e9d5ff;
  color: #6b21a8;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.view-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  color: white;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.view-btn:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.3);
}

.view-btn i {
  font-size: 0.75rem;
  transition: transform 0.3s ease;
}

.view-btn:hover i {
  transform: translateX(4px);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.1) 0%, rgba(0, 86, 166, 0.1) 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon i {
  font-size: 2rem;
  color: #0074D9;
}

.empty-state h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.empty-state p {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .recent-activity-container {
    padding: 1.5rem;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .petition-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .petition-footer {
    flex-direction: column;
    gap: 0.75rem;
    align-items: stretch;
  }

  .view-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
