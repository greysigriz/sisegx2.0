<template>
  <div class="recent-activity-container">
    <div class="section-header">
      <div class="header-left">
        <span class="header-icon"><i class="fas fa-history"></i></span>
        <h2>Actividad Reciente</h2>
        <span v-if="petitions && petitions.length > 0" class="header-count">{{ petitions.length }}</span>
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

    <!-- Carrusel de peticiones con Swiper -->
    <div v-if="petitions && petitions.length > 0" class="ra-swiper-wrap">
      <Swiper
        :modules="swiperModules"
        :slides-per-view="1"
        :space-between="16"
        :navigation="true"
        :pagination="{ clickable: true }"
        :breakpoints="{
          640: { slidesPerView: 1.5 },
          900: { slidesPerView: 2 },
          1200: { slidesPerView: 2.5 }
        }"
        class="ra-swiper"
      >
        <SwiperSlide
          v-for="petition in petitions"
          :key="petition.id"
        >
          <div class="petition-card" :class="'nivel--' + petition.NivelImportancia">
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

              <div class="petition-images" @click.stop>
                <ImageGallery
                  :entidad-tipo="'peticion'"
                  :entidad-id="petition.id"
                  :readonly="true"
                  :show-upload="false"
                  :show-info="false"
                  layout="carousel"
                  :compact="true"
                />
              </div>

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
        </SwiperSlide>
      </Swiper>
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
import ImageGallery from '@/components/ImageGallery.vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation, Pagination } from 'swiper/modules'

export default {
  name: 'RecentActivity',
  components: { ImageGallery, Swiper, SwiperSlide },
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
      swiperModules: [Navigation, Pagination]
    }
  },
  methods: {
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
        1: 'Critico',
        2: 'Alto',
        3: 'Medio',
        4: 'Bajo'
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
      this.$emit('view-petition', petition.folio)
    }
  }
}
</script>

<style scoped>
.recent-activity-container {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(0, 0, 0, 0.04);
  padding-bottom: 2.5rem;
}

/* Header */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: linear-gradient(135deg, #0074D9, #0056a6);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon i {
  color: white;
  font-size: 0.85rem;
}

.header-left h2 {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.header-count {
  background: #eff6ff;
  color: #0074D9;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 8px;
}

/* Alertas */
.alerts-section {
  margin-bottom: 1.25rem;
}

.alert-card {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  border-radius: 12px;
  margin-bottom: 0.625rem;
  border-left: 4px solid;
}

.alert-critical {
  background: linear-gradient(135deg, #fef2f2, #fee2e2);
  border-left-color: #ef4444;
}

.alert-warning {
  background: linear-gradient(135deg, #fffbeb, #fef3c7);
  border-left-color: #f59e0b;
}

.alert-icon {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.alert-critical .alert-icon {
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
}

.alert-warning .alert-icon {
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
}

.alert-content {
  flex: 1;
}

.alert-content p {
  margin: 0;
  font-weight: 600;
  font-size: 0.85rem;
  color: #1e293b;
}

.alert-badge {
  background: white;
  color: #dc2626;
  font-weight: 700;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8rem;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

/* Swiper carrusel */
.ra-swiper-wrap {
  position: relative;
}

.ra-swiper {
  padding-bottom: 2rem;
}

.ra-swiper :deep(.swiper-pagination) {
  bottom: 0;
}

.ra-swiper :deep(.swiper-pagination-bullet) {
  background: #0074D9;
  opacity: 0.3;
  width: 8px;
  height: 8px;
}

.ra-swiper :deep(.swiper-pagination-bullet-active) {
  opacity: 1;
  width: 20px;
  border-radius: 4px;
}

.ra-swiper :deep(.swiper-button-prev),
.ra-swiper :deep(.swiper-button-next) {
  width: 34px;
  height: 34px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  top: calc(50% - 1rem);
}

.ra-swiper :deep(.swiper-button-prev)::after,
.ra-swiper :deep(.swiper-button-next)::after {
  font-size: 0.75rem;
  font-weight: 700;
  color: #0074D9;
}

.ra-swiper :deep(.swiper-button-prev) {
  left: 4px;
}

.ra-swiper :deep(.swiper-button-next) {
  right: 4px;
}

/* Petition card */
.petition-card {
  background: #f8fafc;
  border-radius: 14px;
  padding: 1.15rem;
  transition: all 0.3s ease;
  border-top: 3px solid #cbd5e1;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.petition-card:hover {
  background: #f1f5f9;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

.petition-card.nivel--1 { border-top-color: #ef4444; }
.petition-card.nivel--2 { border-top-color: #f59e0b; }
.petition-card.nivel--3 { border-top-color: #0074D9; }
.petition-card.nivel--4 { border-top-color: #10b981; }
.petition-card.nivel--5 { border-top-color: #94a3b8; }

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
  gap: 0.4rem;
  background: linear-gradient(135deg, #0074D9, #0056a6);
  color: white;
  padding: 0.3rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
}

.folio-badge i {
  font-size: 0.65rem;
}

.priority-badge {
  padding: 0.25rem 0.65rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
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
  margin-bottom: 0.75rem;
  flex: 1;
}

.petition-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.35rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.petition-description {
  font-size: 0.8rem;
  color: #64748b;
  line-height: 1.5;
  margin: 0 0 0.6rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.petition-images {
  margin: 0.4rem 0 0.6rem 0;
  border-radius: 10px;
  overflow: hidden;
  height: 170px;
  max-height: 170px;
  position: relative;
}

.petition-images :deep(*) {
  max-height: inherit;
}

.petition-images :deep(.image-gallery),
.petition-images :deep(.gallery-carousel),
.petition-images :deep(.carousel-container) {
  height: 100%;
  max-height: 170px;
}

.petition-images :deep(.carousel-slide) {
  height: 145px;
  max-height: 145px;
  background: #f1f5f9;
  overflow: hidden;
}

.petition-images :deep(.carousel-image) {
  max-height: 145px;
  width: auto;
  max-width: 100%;
  object-fit: contain;
}

.petition-images :deep(.carousel-btn) {
  width: 28px;
  height: 28px;
  font-size: 0.7rem;
}

.petition-images :deep(.carousel-indicators) {
  gap: 4px;
  margin-top: 4px;
}

.petition-images :deep(.indicator) {
  width: 7px;
  height: 7px;
}

.petition-images :deep(.gallery-empty) {
  padding: 0.75rem;
  font-size: 0.8rem;
  height: 100%;
}

.petition-images :deep(.gallery-empty .empty-icon) {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.petition-images :deep(.gallery-empty h4) {
  font-size: 0.8rem;
  margin: 0;
}

.petition-images :deep(.gallery-empty p) {
  display: none;
}

.petition-images :deep(.gallery-loading) {
  padding: 0.5rem;
  height: 100%;
}

.petition-meta {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.775rem;
  color: #64748b;
}

.meta-item i {
  color: #0074D9;
  font-size: 0.7rem;
}

/* Petition Footer */
.petition-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 0.75rem;
  border-top: 1px solid #e2e8f0;
  margin-top: auto;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.65rem;
  border-radius: 8px;
  font-size: 0.75rem;
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
  gap: 0.4rem;
  background: linear-gradient(135deg, #0074D9, #0056a6);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.view-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.3);
}

.view-btn i {
  font-size: 0.7rem;
  transition: transform 0.3s ease;
}

.view-btn:hover i {
  transform: translateX(3px);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.empty-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 1.25rem;
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.08), rgba(0, 86, 166, 0.08));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon i {
  font-size: 1.75rem;
  color: #0074D9;
}

.empty-state h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.4rem 0;
}

.empty-state p {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .recent-activity-container {
    padding: 1.15rem;
    padding-bottom: 2.25rem;
  }

  .petition-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.4rem;
  }

  .petition-footer {
    flex-direction: column;
    gap: 0.6rem;
    align-items: stretch;
  }

  .view-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
