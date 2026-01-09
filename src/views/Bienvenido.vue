<template>
  <div class="bienvenido-container">
    <!-- Loading State -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Cargando tu dashboard...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <i class="fas fa-exclamation-circle"></i>
      </div>
      <h3>Error al cargar el dashboard</h3>
      <p>{{ error }}</p>
      <button @click="loadDashboardData" class="retry-btn">
        <i class="fas fa-sync-alt"></i>
        Reintentar
      </button>
    </div>

    <!-- Main Content -->
    <div v-else class="dashboard-content">
      <div class="content-wrapper">
        <!-- System Purpose Banner -->
        <div class="hero-banner">
          <div class="hero-content">
            <div class="hero-icon">
              <i class="fas fa-hands-helping"></i>
            </div>
            <div class="hero-text">
              <h2>Sistema de Gestión de Peticiones Ciudadanas</h2>
              <p>Plataforma integral para direccionar y gestionar las solicitudes de los ciudadanos del Estado de Yucatán hacia los departamentos gubernamentales correspondientes.</p>
            </div>
          </div>
          <div class="hero-features">
            <div class="feature-item">
              <i class="fas fa-file-alt"></i>
              <span>Registro</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-random"></i>
              <span>Asignación</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-tasks"></i>
              <span>Seguimiento</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-chart-line"></i>
              <span>Análisis</span>
            </div>
          </div>
        </div>

        <!-- Welcome Header -->
        <div class="welcome-header">
        <div class="welcome-text">
          <h1>
            ¡Bienvenido de vuelta,
            <span class="user-name">{{ userName }}</span>!
          </h1>
          <p class="welcome-subtitle">
            <i class="fas fa-briefcase"></i>
            {{ userRole }}
            <span v-if="userDivision" class="division-tag">
              <i class="fas fa-map-marker-alt"></i>
              {{ userDivision }}
            </span>
          </p>
        </div>
        <div class="welcome-actions">
          <button @click="refreshData" class="action-btn refresh-btn" :disabled="isRefreshing">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': isRefreshing }"></i>
            <span>Actualizar</span>
          </button>
        </div>
      </div>

      <!-- Quick Actions -->
      <div v-if="filteredQuickActions.length > 0" class="quick-actions-section">
        <div class="quick-actions-grid">
          <button
            v-for="action in filteredQuickActions"
            :key="action.name"
            @click="action.handler"
            class="quick-action-btn"
          >
            <i :class="action.icon"></i>
            <span>{{ action.label }}</span>
          </button>
        </div>
      </div>

      <!-- Quick Stats Cards -->
      <UserMetricsCards
        v-if="dashboardData && dashboardData.statistics"
        :stats="dashboardData.statistics"
        :userRole="userRoleId"
      />

      <!-- Recent Activity -->
      <RecentActivity
        v-if="dashboardData"
        :petitions="dashboardData.recent_petitions || []"
        :alerts="dashboardData.alerts || []"
        @view-petition="viewPetitionDetails"
      />

      <!-- Additional Stats for Admin -->
      <div v-if="isAdmin && dashboardData.statistics" class="admin-section">
        <div class="section-title">
          <i class="fas fa-chart-bar"></i>
          <h2>Análisis General</h2>
        </div>

        <div class="charts-grid">
          <!-- Gráfico de municipios -->
          <div v-if="dashboardData.statistics.top_municipios" class="chart-card municipios-card">
            <div class="chart-header">
              <h3>
                <i class="fas fa-map-marked-alt"></i>
                Top Municipios
              </h3>
              <span class="chart-subtitle">Peticiones por región</span>
            </div>
            <div class="municipios-list">
              <div
                v-for="(municipio, index) in dashboardData.statistics.top_municipios"
                :key="index"
                class="municipio-item"
              >
                <div class="municipio-info">
                  <span class="municipio-rank" :class="'rank-' + (index + 1)">
                    <span class="rank-number">#{{ index + 1 }}</span>
                  </span>
                  <span class="municipio-name">{{ municipio.Municipio || 'Sin municipio' }}</span>
                  <span class="municipio-count-badge">{{ municipio.cantidad }}</span>
                </div>
                <div class="municipio-bar-container">
                  <div class="municipio-bar">
                    <div
                      class="bar-fill"
                      :style="{ width: getMunicipioPercentage(municipio.cantidad) + '%' }"
                    >
                      <span class="bar-percentage">{{ Math.round(getMunicipioPercentage(municipio.cantidad)) }}%</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tendencia últimos 7 días -->
          <div v-if="dashboardData.statistics.ultimos_7_dias" class="chart-card trend-card">
            <div class="chart-header">
              <h3>
                <i class="fas fa-chart-line"></i>
                Tendencia - Últimos 7 Días
              </h3>
              <span class="chart-subtitle">Evolución de peticiones</span>
            </div>
            <div class="trend-chart">
              <svg class="trend-line" :viewBox="`0 0 ${dashboardData.statistics.ultimos_7_dias.length * 100} 140`" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="lineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:#0074D9;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#0056a6;stop-opacity:1" />
                  </linearGradient>
                </defs>
                <polyline
                  :points="dashboardData.statistics.ultimos_7_dias.map((day, i) => `${i * 100 + 50},${140 - getTrendHeight(day.cantidad) * 1.4}`).join(' ')"
                  fill="none"
                  stroke="url(#lineGradient)"
                  stroke-width="3"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <div
                v-for="(day, index) in dashboardData.statistics.ultimos_7_dias"
                :key="index"
                class="trend-day"
              >
                <div class="trend-bar-container">
                  <div
                    class="trend-bar"
                    :style="{ height: getTrendHeight(day.cantidad) + '%' }"
                  >
                    <div class="trend-tooltip">{{ day.cantidad }} peticiones</div>
                  </div>
                  <div class="trend-point" :style="{ bottom: getTrendHeight(day.cantidad) + '%' }">
                    <span class="point-value">{{ day.cantidad }}</span>
                  </div>
                </div>
                <span class="trend-label">{{ formatShortDate(day.fecha) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div><!-- Close content-wrapper -->
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import authService from '@/services/auth.js'
import UserMetricsCards from '@/components/dashboard/UserMetricsCards.vue'
import RecentActivity from '@/components/dashboard/RecentActivity.vue'

export default {
  name: 'BienvenidoDashboard',
  components: {
    UserMetricsCards,
    RecentActivity
  },
  setup() {
    const router = useRouter()
    const isLoading = ref(true)
    const isRefreshing = ref(false)
    const error = ref(null)
    const dashboardData = ref(null)

    // Computed properties
    const userName = computed(() => {
      const user = authService.getCurrentUser()
      if (user && user.usuario) {
        return user.usuario.Nombre || user.usuario.Usuario || 'Usuario'
      }
      return 'Usuario'
    })

    const userRole = computed(() => {
      const user = authService.getCurrentUser()
      return user?.rol?.nombre || 'Usuario'
    })

    const userRoleId = computed(() => {
      const user = authService.getCurrentUser()
      return user?.usuario?.IdRolSistema || null
    })

    const userDivision = computed(() => {
      const user = authService.getCurrentUser()
      return user?.usuario?.NombreDivision || null
    })

    const userPermissions = computed(() => {
      const user = authService.getCurrentUser()
      return user?.permisos || []
    })

    const isAdmin = computed(() => userRoleId.value === 1)
    const isDepartmentUser = computed(() => userRoleId.value === 9)

    // Quick Actions Definition
    const quickActions = [
      {
        name: 'peticiones',
        label: 'Ver Peticiones',
        icon: 'fas fa-list',
        handler: () => router.push('/peticiones'),
        requiredPermission: 'admin_peticiones'
      },
      {
        name: 'petitions',
        label: 'Petitions',
        icon: 'fas fa-user-check',
        handler: () => router.push('/petitions'),
        requiredPermission: 'admin_peticiones'
      },
      {
        name: 'departamentos',
        label: 'Mi Departamento',
        icon: 'fas fa-building',
        handler: () => router.push('/departamentos'),
        requiredPermission: 'ver_departamentos'
      },
      {
        name: 'tablero',
        label: 'Tablero',
        icon: 'fas fa-th-large',
        handler: () => router.push('/tablero'),
        requiredPermission: 'ver_tablero'
      },
      {
        name: 'configuracion',
        label: 'Configuración',
        icon: 'fas fa-cog',
        handler: () => router.push('/configuracion'),
        requiredPermission: 'configuracion_sistema'
      }
    ]

    // Filter quick actions based on user permissions
    const filteredQuickActions = computed(() => {
      const permissions = userPermissions.value
      if (!Array.isArray(permissions)) return []
      
      return quickActions.filter(action => {
        return permissions.includes(action.requiredPermission)
      })
    })

    // Methods
    const loadDashboardData = async () => {
      try {
        isLoading.value = true
        error.value = null

        const response = await axios.get('dashboard-user.php')

        if (!response.data.success) {
          throw new Error(response.data.message || 'Error al cargar datos')
        }

        dashboardData.value = response.data
        console.log('✅ Dashboard data loaded:', response.data)

      } catch (err) {
        console.error('❌ Error loading dashboard:', err)
        error.value = err.response?.data?.message || err.message || 'Error al cargar el dashboard'
      } finally {
        isLoading.value = false
      }
    }

    const refreshData = async () => {
      isRefreshing.value = true
      await loadDashboardData()
      isRefreshing.value = false
    }

    const getMunicipioPercentage = (cantidad) => {
      if (!dashboardData.value?.statistics?.top_municipios) return 0
      const max = Math.max(...dashboardData.value.statistics.top_municipios.map(m => m.cantidad))
      return (cantidad / max) * 100
    }

    const getTrendHeight = (cantidad) => {
      if (!dashboardData.value?.statistics?.ultimos_7_dias) return 0
      const max = Math.max(...dashboardData.value.statistics.ultimos_7_dias.map(d => d.cantidad))
      return max > 0 ? (cantidad / max) * 100 : 0
    }

    const formatShortDate = (dateString) => {
      const date = new Date(dateString)
      const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
      return days[date.getDay()]
    }

    const viewPetitionDetails = (petitionId) => {
      router.push(`/peticiones?id=${petitionId}`)
    }

    // Lifecycle
    onMounted(() => {
      loadDashboardData()
    })

    return {
      isLoading,
      isRefreshing,
      error,
      dashboardData,
      userName,
      userRole,
      userRoleId,
      userDivision,
      userPermissions,
      isAdmin,
      isDepartmentUser,
      filteredQuickActions,
      loadDashboardData,
      refreshData,
      getMunicipioPercentage,
      getTrendHeight,
      formatShortDate,
      viewPetitionDetails
    }
  }
}
</script>

<style scoped>
.inicio-container {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 2rem;
  background-color: #f8fafc;
  min-height: 100vh;
}

/* Sección de gestión (botones superiores) */
.seccion-gestion {
  width: 100%;
  margin-bottom: 0.5rem;
}

/* Sección de contenido principal */
/* Loading State */
.loading-overlay {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
}

.loading-spinner {
  text-align: center;
}

.spinner {
  width: 60px;
  height: 60px;
  margin: 0 auto 1rem;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #0074D9;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-spinner p {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Content Wrapper */
.content-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Hero Banner */
.hero-banner {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.15);
}

.hero-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1.25rem;
}

.hero-icon {
  flex-shrink: 0;
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
}

.hero-icon i {
  font-size: 1.5rem;
  color: white;
}

.hero-text h2 {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  color: white;
}

.hero-text p {
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0;
  color: rgba(255, 255, 255, 0.95);
}

.hero-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.75rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.15);
  padding: 0.625rem 0.875rem;
  border-radius: 8px;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.feature-item:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-1px);
}

.feature-item i {
  font-size: 1.1rem;
  color: white;
}

.feature-item span {
  font-size: 0.8rem;
  font-weight: 600;
  color: white;
}

/* Error State */
.error-state {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  max-width: 500px;
  margin: 2rem auto;
}

.error-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.error-icon i {
  font-size: 2rem;
  color: #dc2626;
}

.error-state h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.error-state p {
  color: #64748b;
  margin-bottom: 1.5rem;
}

.retry-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.retry-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.3);
}

/* Welcome Header */
.welcome-header {
  background: white;
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 116, 217, 0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-left: 4px solid #0074D9;
}

.welcome-text h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.user-name {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.welcome-subtitle {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 500;
}

.welcome-subtitle i {
  color: #0074D9;
}

.division-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  background: #dbeafe;
  color: #0074D9;
  padding: 0.25rem 0.625rem;
  border-radius: 16px;
  font-size: 0.8rem;
  font-weight: 600;
}

.welcome-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.refresh-btn {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  color: white;
}

.refresh-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.3);
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Quick Actions */
.quick-actions-section {
  margin-bottom: 1.5rem;
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
}

.quick-action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: white;
  border: 2px solid #e2e8f0;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.875rem;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.3s ease;
}

.quick-action-btn i {
  font-size: 1.1rem;
  color: #0074D9;
}

.quick-action-btn:hover {
  border-color: #0074D9;
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.05) 0%, rgba(0, 86, 166, 0.05) 100%);
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(0, 116, 217, 0.12);
}

/* Admin Section */
.admin-section {
  margin-top: 2rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.section-title i {
  font-size: 1.5rem;
  color: #0074D9;
}

.section-title h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.chart-card {
  background: white;
  border-radius: 16px;
  padding: 1.75rem;
  box-shadow: 0 2px 12px rgba(0, 116, 217, 0.08);
  border: 1px solid #e2e8f0;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.chart-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #0074D9 0%, #0056a6 100%);
}

.chart-card:hover {
  box-shadow: 0 8px 24px rgba(0, 116, 217, 0.15);
  transform: translateY(-2px);
}

.chart-header {
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.chart-card h3 {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.chart-card h3 i {
  color: #0074D9;
  font-size: 1rem;
}

.chart-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

/* Municipios List */
.municipios-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.municipio-item {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  padding: 0.875rem;
  border-radius: 10px;
  background: #f8fafc;
  transition: all 0.3s ease;
}

.municipio-item:hover {
  background: #f1f5f9;
  transform: translateX(4px);
}

.municipio-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.municipio-rank {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  color: white;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.875rem;
  box-shadow: 0 2px 8px rgba(0, 116, 217, 0.25);
  position: relative;
  transition: all 0.3s ease;
}

.municipio-item:hover .municipio-rank {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.35);
}

.rank-1 {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  box-shadow: 0 2px 8px rgba(251, 191, 36, 0.35);
}

.rank-2 {
  background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
  box-shadow: 0 2px 8px rgba(148, 163, 184, 0.35);
}

.rank-3 {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  box-shadow: 0 2px 8px rgba(251, 146, 60, 0.35);
}

.municipio-name {
  font-weight: 600;
  color: #1e293b;
  flex: 1;
  font-size: 0.9rem;
}

.municipio-count-badge {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.8rem;
  min-width: 40px;
  text-align: center;
  box-shadow: 0 2px 6px rgba(0, 116, 217, 0.2);
}

.municipio-bar-container {
  width: 100%;
}

.municipio-bar {
  position: relative;
  width: 100%;
  height: 36px;
  background: #e2e8f0;
  border-radius: 8px;
  overflow: visible;
  display: flex;
  align-items: center;
}

.bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #0074D9 0%, #0056a6 100%);
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 8px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 116, 217, 0.15);
}

.municipio-item:hover .bar-fill {
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.25);
  filter: brightness(1.05);
}

.bar-percentage {
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  white-space: nowrap;
}

/* Trend Chart */
.trend-chart {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
  justify-content: space-between;
  height: 220px;
  padding: 1rem 0.5rem;
  position: relative;
  background: linear-gradient(to top, rgba(0, 116, 217, 0.03) 0%, transparent 100%);
  border-radius: 8px;
}

.trend-line {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 140px;
  pointer-events: none;
  z-index: 1;
}

.trend-day {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  position: relative;
  z-index: 2;
}

.trend-bar-container {
  width: 100%;
  height: 140px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  position: relative;
}

.trend-bar {
  width: 70%;
  background: linear-gradient(180deg, rgba(0, 116, 217, 0.15) 0%, rgba(0, 116, 217, 0.35) 100%);
  border-radius: 8px 8px 0 0;
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 8px;
  position: relative;
  cursor: pointer;
  border: 2px solid transparent;
}

.trend-bar:hover {
  background: linear-gradient(180deg, #0074D9 0%, #0056a6 100%);
  box-shadow: 0 -4px 16px rgba(0, 116, 217, 0.3);
  width: 85%;
  border-color: #0074D9;
}

.trend-bar:hover .trend-tooltip {
  opacity: 1;
  transform: translateY(-10px);
}

.trend-tooltip {
  position: absolute;
  top: -35px;
  left: 50%;
  transform: translateX(-50%) translateY(0);
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 10;
}

.trend-tooltip::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 5px solid #334155;
}

.trend-point {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 12px;
  height: 12px;
  background: white;
  border: 3px solid #0074D9;
  border-radius: 50%;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 116, 217, 0.3);
  z-index: 3;
}

.trend-bar:hover + .trend-point {
  width: 16px;
  height: 16px;
  border-width: 4px;
  box-shadow: 0 4px 16px rgba(0, 116, 217, 0.5);
}

.point-value {
  display: none;
}

.trend-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  margin-top: 0.375rem;
}

/* Quick Actions */
.quick-actions-section {
  margin-top: 2rem;
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.quick-action-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: white;
  border: 2px solid #e2e8f0;
  padding: 1.25rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.3s ease;
}

.quick-action-btn i {
  font-size: 1.5rem;
  color: #0074D9;
}

.quick-action-btn:hover {
  border-color: #0074D9;
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.05) 0%, rgba(0, 86, 166, 0.05) 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.15);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .bienvenido-container {
    padding: 1rem;
  }

  .hero-banner {
    padding: 1.5rem;
  }

  .hero-content {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }

  .hero-text h2 {
    font-size: 1.5rem;
  }

  .hero-text p {
    font-size: 0.9rem;
  }

  .hero-features {
    grid-template-columns: 1fr;
  }

  .welcome-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.5rem;
    padding: 1.5rem;
  }

  .welcome-text h1 {
    font-size: 1.5rem;
  }

  .welcome-actions {
    width: 100%;
  }

  .action-btn {
    flex: 1;
    justify-content: center;
  }

  .quick-actions-grid {
    grid-template-columns: 1fr;
  }

  .charts-grid {
    grid-template-columns: 1fr;
  }

  .trend-chart {
    height: 160px;
  }

  .trend-bar-container {
    height: 100px;
  }
}

@media (max-width: 480px) {
  .hero-banner {
    padding: 1.25rem;
  }

  .hero-icon {
    width: 60px;
    height: 60px;
  }

  .hero-icon i {
    font-size: 2rem;
  }

  .hero-text h2 {
    font-size: 1.25rem;
  }

  .hero-text p {
    font-size: 0.85rem;
  }

  .feature-item {
    padding: 0.875rem 1rem;
  }

  .feature-item i {
    font-size: 1.25rem;
  }

  .feature-item span {
    font-size: 0.85rem;
  }

  .welcome-text h1 {
    font-size: 1.25rem;
  }

  .welcome-subtitle {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .section-title h2 {
    font-size: 1.25rem;
  }
}
</style>
