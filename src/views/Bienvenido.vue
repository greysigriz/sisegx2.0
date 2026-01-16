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

      <!-- Para usuarios NO admin: mostrar cards simples y actividad reciente -->
      <template v-if="!isAdmin && !isCanalizadorMunicipal && !isCanalizadorEstatal && !isDepartmentUser">
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
      </template>

      <!-- Para Usuario de Departamento: Dashboard especializado -->
      <div v-if="isDepartmentUser && dashboardData.statistics" class="departamento-section">
        <div class="section-title">
          <i class="fas fa-building"></i>
          <h2>Panel de Departamento - {{ dashboardData.statistics.nombre_departamento || 'Tu Departamento' }}</h2>
          <p class="section-subtitle">Gestión de peticiones asignadas a tu departamento</p>
        </div>

        <!-- Alertas del Departamento -->
        <div v-if="dashboardData.alerts && dashboardData.alerts.length > 0" class="alertas-destacadas">
          <div
            v-for="(alert, index) in dashboardData.alerts"
            :key="index"
            class="alert-card"
            :class="'alert-' + alert.type"
          >
            <div class="alert-icon">
              <i :class="alert.type === 'critical' ? 'fas fa-exclamation-circle' : 'fas fa-clock'"></i>
            </div>
            <div class="alert-content">
              <div class="alert-message">{{ alert.message }}</div>
              <div class="alert-count">{{ alert.count }} peticiones</div>
            </div>
          </div>
        </div>

        <!-- Métricas del Departamento -->
        <div class="departamento-metrics">
          <div class="metric-card total-dept">
            <div class="metric-icon">
              <i class="fas fa-tasks"></i>
            </div>
            <div class="metric-content">
              <div class="metric-label">Peticiones Asignadas</div>
              <div class="metric-value">{{ dashboardData.statistics.total_peticiones || 0 }}</div>
              <div class="metric-subtitle">Total en tu departamento</div>
            </div>
          </div>

          <div class="metric-card pendientes-dept">
            <div class="metric-icon">
              <i class="fas fa-spinner"></i>
            </div>
            <div class="metric-content">
              <div class="metric-label">Pendientes</div>
              <div class="metric-value">{{ dashboardData.statistics.peticiones_pendientes || 0 }}</div>
              <div class="metric-subtitle">En proceso</div>
            </div>
          </div>

          <div class="metric-card retrasadas-dept">
            <div class="metric-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="metric-content">
              <div class="metric-label">Retrasadas</div>
              <div class="metric-value">{{ dashboardData.statistics.peticiones_retrasadas || 0 }}</div>
              <div class="metric-subtitle">Más de 15 días</div>
            </div>
          </div>
        </div>

        <!-- Estados de Peticiones del Departamento -->
        <div v-if="dashboardData.statistics.por_estado && dashboardData.statistics.por_estado.length > 0" class="departamento-estados-section">
          <div class="subsection-title">
            <i class="fas fa-chart-pie"></i>
            <h3>Estado de las Peticiones</h3>
          </div>
          <div class="estados-grid">
            <div
              v-for="(estado, index) in dashboardData.statistics.por_estado"
              :key="index"
              class="estado-card"
              :class="'estado-' + estado.estado.toLowerCase().replace(/ /g, '-')"
            >
              <div class="estado-icon">
                <i :class="getEstadoIcon(estado.estado)"></i>
              </div>
              <div class="estado-info">
                <div class="estado-cantidad">{{ estado.cantidad }}</div>
                <div class="estado-nombre">{{ estado.estado }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Niveles de Importancia del Departamento -->
        <div v-if="dashboardData.statistics.por_importancia && dashboardData.statistics.por_importancia.length > 0" class="importancia-section">
          <div class="subsection-title">
            <i class="fas fa-flag"></i>
            <h3>Peticiones por Nivel de Importancia</h3>
          </div>
          <div class="importancia-grid">
            <div
              v-for="imp in dashboardData.statistics.por_importancia"
              :key="imp.NivelImportancia"
              class="importancia-item"
              :class="'nivel-' + imp.NivelImportancia"
            >
              <div class="importancia-header">
                <span class="nivel-badge">Nivel {{ imp.NivelImportancia }}</span>
                <span class="nivel-label">{{ getNivelLabel(imp.NivelImportancia) }}</span>
              </div>
              <div class="importancia-count">{{ imp.cantidad }}</div>
              <div class="importancia-bar">
                <div
                  class="bar-fill"
                  :style="{ width: getImportanciaPercentage(imp.cantidad) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Peticiones Asignadas (Urgentes Primero) -->
        <div v-if="dashboardData.recent_petitions && dashboardData.recent_petitions.length > 0" class="peticiones-departamento-section">
          <div class="subsection-title">
            <i class="fas fa-clipboard-list"></i>
            <h3>Peticiones Asignadas - Requieren Atención</h3>
            <p>Ordenadas por prioridad y antigüedad</p>
          </div>
          <div class="peticiones-departamento-list">
            <div
              v-for="petition in dashboardData.recent_petitions"
              :key="petition.id"
              class="peticion-dept-card"
              :class="['nivel-' + petition.NivelImportancia, petition.dias_asignacion > 15 ? 'retrasada' : '']"
              @click="viewPetitionDetails(petition.id)"
            >
              <div class="petition-header">
                <span class="petition-folio">{{ petition.folio }}</span>
                <span class="petition-nivel" :class="'nivel-badge-' + petition.NivelImportancia">
                  <i class="fas fa-flag"></i>
                  Nivel {{ petition.NivelImportancia }}
                </span>
                <span v-if="petition.dias_asignacion" class="petition-dias" :class="petition.dias_asignacion > 15 ? 'dias-retrasado' : ''">
                  <i class="fas fa-calendar-day"></i>
                  {{ petition.dias_asignacion }} días asignada
                </span>
              </div>
              <div class="petition-body">
                <div class="petition-nombre">{{ petition.nombre }}</div>
                <div class="petition-descripcion">{{ petition.descripcion }}</div>
              </div>
              <div class="petition-footer">
                <span class="petition-municipio">
                  <i class="fas fa-map-marker-alt"></i>
                  {{ petition.Municipio || 'Sin municipio' }}
                </span>
                <span class="petition-estado-dept" :class="'estado-badge-dept-' + petition.estado_departamento.toLowerCase().replace(/ /g, '-')">
                  {{ petition.estado_departamento }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Mensaje si no hay peticiones -->
        <div v-else class="no-peticiones-message">
          <i class="fas fa-inbox"></i>
          <h3>No hay peticiones asignadas</h3>
          <p>Tu departamento no tiene peticiones pendientes en este momento.</p>
        </div>
      </div>

      <!-- Para Canalizadores (Municipal y Estatal): Dashboard especializado -->
      <div v-if="(isCanalizadorMunicipal || isCanalizadorEstatal) && dashboardData.statistics" class="canalizador-section">
        <div class="section-title">
          <i class="fas fa-broadcast-tower"></i>
          <h2>{{ isCanalizadorMunicipal ? 'Panel de Canalización Municipal' : 'Panel de Canalización Estatal' }}</h2>
          <p class="section-subtitle">
            {{ isCanalizadorMunicipal ? 'Vista de tu municipio' : 'Vista a nivel estatal' }}
          </p>
        </div>

        <!-- Alertas Destacadas -->
        <div v-if="dashboardData.alerts && dashboardData.alerts.length > 0" class="alertas-destacadas">
          <div
            v-for="(alert, index) in dashboardData.alerts"
            :key="index"
            class="alert-card"
            :class="'alert-' + alert.type"
          >
            <div class="alert-icon">
              <i :class="alert.type === 'critical' ? 'fas fa-exclamation-circle' : 'fas fa-clock'"></i>
            </div>
            <div class="alert-content">
              <div class="alert-message">{{ alert.message }}</div>
              <div class="alert-count">{{ alert.count }} peticiones</div>
            </div>
          </div>
        </div>

        <!-- Métricas Principales -->
        <div class="canalizador-metrics">
          <div class="metric-card total">
            <div class="metric-icon">
              <i class="fas fa-inbox"></i>
            </div>
            <div class="metric-content">
              <div class="metric-label">Total de Peticiones</div>
              <div class="metric-value">{{ dashboardData.statistics.total_peticiones || 0 }}</div>
            </div>
          </div>

          <div class="metric-card retrasadas">
            <div class="metric-icon">
              <i class="fas fa-hourglass-end"></i>
            </div>
            <div class="metric-content">
              <div class="metric-label">Peticiones Retrasadas</div>
              <div class="metric-value">{{ dashboardData.statistics.peticiones_retrasadas || 0 }}</div>
              <div class="metric-subtitle">Más de 30 días sin completar</div>
            </div>
          </div>
        </div>

        <!-- Estados de Peticiones -->
        <div v-if="dashboardData.statistics.por_estado" class="estados-grid">
          <div
            v-for="(estado, index) in dashboardData.statistics.por_estado"
            :key="index"
            class="estado-card"
            :class="'estado-' + estado.estado.toLowerCase().replace(/ /g, '-')"
          >
            <div class="estado-icon">
              <i :class="getEstadoIcon(estado.estado)"></i>
            </div>
            <div class="estado-info">
              <div class="estado-cantidad">{{ estado.cantidad }}</div>
              <div class="estado-nombre">{{ estado.estado }}</div>
            </div>
          </div>
        </div>

        <!-- Departamentos con Más Peticiones -->
        <div v-if="dashboardData.statistics.departamentos_top && dashboardData.statistics.departamentos_top.length > 0" class="departamentos-section">
          <div class="subsection-title">
            <i class="fas fa-building"></i>
            <h3>Departamentos con Más Peticiones Asignadas</h3>
            <p>Identifica los departamentos que requieren mayor seguimiento</p>
          </div>
          <div class="departamentos-list">
            <div
              v-for="(dept, index) in dashboardData.statistics.departamentos_top"
              :key="index"
              class="departamento-item"
            >
              <div class="dept-ranking">
                <span class="dept-rank" :class="'rank-' + (index + 1)">#{{ index + 1 }}</span>
              </div>
              <div class="dept-info">
                <div class="dept-name">{{ dept.departamento }}</div>
                <div class="dept-count">{{ dept.cantidad }} peticiones asignadas</div>
              </div>
              <div class="dept-bar-container">
                <div
                  class="dept-bar"
                  :style="{ width: getDepartamentoPercentage(dept.cantidad) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Para Canalizador Estatal: Top Municipios -->
        <div v-if="isCanalizadorEstatal && dashboardData.statistics.top_municipios" class="municipios-section">
          <div class="subsection-title">
            <i class="fas fa-map-marked-alt"></i>
            <h3>Municipios con Más Peticiones</h3>
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

        <!-- Peticiones Urgentes que Requieren Atención -->
        <div v-if="dashboardData.recent_petitions && dashboardData.recent_petitions.length > 0" class="peticiones-urgentes-section">
          <div class="subsection-title">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Peticiones que Requieren Atención</h3>
            <p>Ordenadas por urgencia y antigüedad</p>
          </div>
          <div class="peticiones-urgentes-list">
            <div
              v-for="petition in dashboardData.recent_petitions"
              :key="petition.id"
              class="peticion-urgente-card"
              :class="'nivel-' + petition.NivelImportancia"
              @click="viewPetitionDetails(petition.id)"
            >
              <div class="petition-header">
                <span class="petition-folio">{{ petition.folio }}</span>
                <span class="petition-nivel" :class="'nivel-badge-' + petition.NivelImportancia">
                  Nivel {{ petition.NivelImportancia }}
                </span>
                <span v-if="petition.dias_transcurridos" class="petition-dias">
                  <i class="fas fa-clock"></i> {{ petition.dias_transcurridos }} días
                </span>
              </div>
              <div class="petition-body">
                <div class="petition-nombre">{{ petition.nombre }}</div>
                <div class="petition-descripcion">{{ petition.descripcion }}</div>
              </div>
              <div class="petition-footer">
                <span class="petition-municipio">
                  <i class="fas fa-map-marker-alt"></i>
                  {{ petition.Municipio || 'Sin municipio' }}
                </span>
                <span class="petition-estado" :class="'estado-badge-' + petition.estado.toLowerCase().replace(/ /g, '-')">
                  {{ petition.estado }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Para Admin/Director: Panel administrativo completo (sin duplicados) -->
      <div v-if="isAdmin && dashboardData.statistics" class="admin-section">
        <div class="section-title">
          <i class="fas fa-chart-bar"></i>
          <h2>Panel Administrativo - Vista General</h2>
        </div>

        <!-- Resumen de Estados -->
        <div v-if="dashboardData.statistics.por_estado" class="estados-grid">
          <div
            v-for="(estado, index) in dashboardData.statistics.por_estado"
            :key="index"
            class="estado-card"
            :class="'estado-' + estado.estado.toLowerCase().replace(/ /g, '-')"
          >
            <div class="estado-icon">
              <i :class="getEstadoIcon(estado.estado)"></i>
            </div>
            <div class="estado-info">
              <div class="estado-cantidad">{{ estado.cantidad }}</div>
              <div class="estado-nombre">{{ estado.estado }}</div>
            </div>
          </div>
        </div>

        <!-- Niveles de Importancia -->
        <div v-if="dashboardData.statistics.por_importancia" class="importancia-section">
          <div class="subsection-title">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Peticiones por Nivel de Importancia</h3>
          </div>
          <div class="importancia-grid">
            <div
              v-for="imp in dashboardData.statistics.por_importancia"
              :key="imp.NivelImportancia"
              class="importancia-item"
              :class="'nivel-' + imp.NivelImportancia"
            >
              <div class="importancia-header">
                <span class="nivel-badge">Nivel {{ imp.NivelImportancia }}</span>
                <span class="nivel-label">{{ getNivelLabel(imp.NivelImportancia) }}</span>
              </div>
              <div class="importancia-count">{{ imp.cantidad }}</div>
              <div class="importancia-bar">
                <div
                  class="bar-fill"
                  :style="{ width: getImportanciaPercentage(imp.cantidad) + '%' }"
                ></div>
              </div>
            </div>
          </div>
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

        <!-- Actividad Reciente (dentro del panel admin) -->
        <RecentActivity
          v-if="dashboardData"
          :petitions="dashboardData.recent_petitions || []"
          :alerts="dashboardData.alerts || []"
          @view-petition="viewPetitionDetails"
        />
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
      // Nuevo sistema: mostrar múltiples roles
      if (user?.usuario?.RolesNombres && Array.isArray(user.usuario.RolesNombres)) {
        return user.usuario.RolesNombres.join(', ')
      }
      // Fallback al sistema antiguo
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
      // Nuevo sistema: obtener permisos del array Permisos
      if (user?.usuario?.Permisos && Array.isArray(user.usuario.Permisos)) {
        return user.usuario.Permisos
      }
      // Fallback al sistema antiguo
      return user?.permisos || []
    })

    // Obtener nombres de roles del usuario
    const userRoles = computed(() => {
      const user = authService.getCurrentUser()
      // Nuevo sistema: obtener roles del array RolesNombres
      if (user?.usuario?.RolesNombres && Array.isArray(user.usuario.RolesNombres)) {
        return user.usuario.RolesNombres
      }
      // Fallback al sistema antiguo
      if (user?.rol?.nombre) {
        return [user.rol.nombre]
      }
      return []
    })

    // Helper local para verificar si tiene un permiso
    const hasPermission = (permission) => {
      return userPermissions.value.includes(permission)
    }

    // Helper local para verificar si tiene alguno de varios permisos
    const hasAnyPermission = (permissions) => {
      return permissions.some(p => userPermissions.value.includes(p))
    }

    // Helper local para verificar si tiene un rol específico
    const hasRole = (roleName) => {
      return userRoles.value.some(r => r.toLowerCase() === roleName.toLowerCase())
    }

    // Verificaciones de roles basadas en nombre de rol Y permisos
    const isAdmin = computed(() => {
      // Verificar por nombre de rol (más confiable)
      const isAdminByRole = hasRole('Super Usuario') || hasRole('Director')
      // O verificar por permisos
      const isAdminByPermission = hasAnyPermission(['admin_peticiones', 'configuracion_sistema', 'gestionar_usuarios', 'ver_reportes'])

      return isAdminByRole || isAdminByPermission
    })

    const isDepartmentUser = computed(() => {
      return hasRole('Departamento') || hasPermission('gestion_peticiones_departamento')
    })

    const isFormularioUser = computed(() => {
      return hasRole('Formulario') || hasPermission('peticiones_formulario')
    })

    const isCanalizadorMunicipal = computed(() => {
      return hasRole('Canalizador Municipal') || hasPermission('peticiones_municipio')
    })

    const isCanalizadorEstatal = computed(() => {
      return hasRole('Canalizador Estatal') || hasPermission('peticiones_estatal')
    })

    // Quick Actions Definition - Completo para todos los roles
    const quickActions = [
      // Super Usuario y Director
      {
        name: 'peticiones',
        label: 'Gestión de Peticiones',
        icon: 'fas fa-tasks',
        handler: () => router.push('/peticiones'),
        requiredPermission: 'admin_peticiones'
      },
      {
        name: 'configuracion',
        label: 'Configuración',
        icon: 'fas fa-cog',
        handler: () => router.push('/configuracion'),
        requiredPermission: 'acceder_configuracion'
      },
      {
        name: 'reportes',
        label: 'Reportes',
        icon: 'fas fa-chart-bar',
        handler: () => router.push('/reportes'),
        requiredPermission: 'ver_reportes'
      },
      // Formulario (entrada de peticiones)
      {
        name: 'petitions',
        label: 'Registro de Peticiones',
        icon: 'fas fa-file-alt',
        handler: () => router.push('/petition'),
        requiredPermission: 'peticiones_formulario'
      },
      // Canalizador Municipal (peticiones por municipio)
      {
        name: 'peticiones-municipio',
        label: 'Peticiones de mi Municipio',
        icon: 'fas fa-map-marked-alt',
        handler: () => router.push('/peticiones'),
        requiredPermission: 'peticiones_municipio'
      },
      // Canalizador Estatal (todas las peticiones)
      {
        name: 'peticiones-estatal',
        label: 'Peticiones Estatales',
        icon: 'fas fa-globe-americas',
        handler: () => router.push('/peticiones'),
        requiredPermission: 'peticiones_estatal'
      },
      // Departamento (solo sus peticiones asignadas)
      {
        name: 'mis-peticiones',
        label: 'Mis Peticiones',
        icon: 'fas fa-clipboard-list',
        handler: () => router.push('/departamentos'),
        requiredPermission: 'gestion_peticiones_departamento'
      },
      // Tablero (varios roles)
      {
        name: 'tablero',
        label: 'Tablero',
        icon: 'fas fa-th-large',
        handler: () => router.push('/tablero'),
        requiredPermission: 'ver_tablero'
      },
      // Ver usuarios (Director)
      {
        name: 'usuarios',
        label: 'Usuarios',
        icon: 'fas fa-users',
        handler: () => router.push('/configuracion/usuarios'),
        requiredPermission: 'ver_usuarios'
      }
    ]

    // Filter quick actions based on user permissions
    const filteredQuickActions = computed(() => {
      const permissions = userPermissions.value

      // Verificar que permissions sea un array válido
      if (!Array.isArray(permissions) || permissions.length === 0) {
        console.warn('⚠️ No se encontraron permisos para el usuario')
        return []
      }

      console.log('🔐 Permisos del usuario:', permissions)

      const filtered = quickActions.filter(action => {
        // Verificar si el usuario tiene el permiso requerido
        const hasPermission = permissions.includes(action.requiredPermission)

        if (hasPermission) {
          console.log(`✅ Acción permitida: ${action.label} (${action.requiredPermission})`)
        }

        return hasPermission
      })

      console.log(`📋 Total de acciones rápidas disponibles: ${filtered.length}/${quickActions.length}`)
      return filtered
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
        console.log('👤 Usuario roles:', userRoles.value)
        console.log('🔐 Usuario permisos:', userPermissions.value)
        console.log('👔 Es Admin?:', isAdmin.value)
        console.log('🆔 User Info del backend:', response.data.user_info)
        console.log('🎯 Rol ID del backend:', response.data.user_info?.rol_id)
        console.log('📊 Estadísticas recibidas:', response.data.statistics)
        console.log('📝 Peticiones recientes:', response.data.recent_petitions?.length || 0)
        console.log('🚨 Alertas:', response.data.alerts?.length || 0)

        if (response.data.statistics) {
          console.log('📈 Por estado:', response.data.statistics.por_estado)
          console.log('⚠️ Por importancia:', response.data.statistics.por_importancia)
          console.log('🗓️ Últimos 7 días:', response.data.statistics.ultimos_7_dias)
          console.log('🏙️ Top municipios:', response.data.statistics.top_municipios)
        }

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

    const getEstadoIcon = (estado) => {
      const iconMap = {
        'Esperando recepción': 'fas fa-clock',
        'Aceptado en proceso': 'fas fa-cog fa-spin',
        'Devuelto a seguimiento': 'fas fa-undo',
        'Rechazado': 'fas fa-times-circle',
        'Completado': 'fas fa-check-circle'
      }
      return iconMap[estado] || 'fas fa-file-alt'
    }

    const getNivelLabel = (nivel) => {
      const labels = {
        1: 'Muy Alta',
        2: 'Alta',
        3: 'Media',
        4: 'Baja',
        5: 'Muy Baja'
      }
      return labels[nivel] || 'N/D'
    }

    const getImportanciaPercentage = (cantidad) => {
      if (!dashboardData.value?.statistics?.por_importancia) return 0
      const max = Math.max(...dashboardData.value.statistics.por_importancia.map(i => i.cantidad))
      return max > 0 ? (cantidad / max) * 100 : 0
    }

    const getDepartamentoPercentage = (cantidad) => {
      if (!dashboardData.value?.statistics?.departamentos_top) return 0
      const max = Math.max(...dashboardData.value.statistics.departamentos_top.map(d => d.cantidad))
      return max > 0 ? (cantidad / max) * 100 : 0
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
      isFormularioUser,
      isCanalizadorMunicipal,
      isCanalizadorEstatal,
      filteredQuickActions,
      loadDashboardData,
      refreshData,
      getMunicipioPercentage,
      getTrendHeight,
      formatShortDate,
      viewPetitionDetails,
      getEstadoIcon,
      getNivelLabel,
      getImportanciaPercentage,
      getDepartamentoPercentage
    }
  }
}
</script>

<style>
/* Sin scoped - usando namespace .bienvenido-container para evitar conflictos */

.bienvenido-container .inicio-container {
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
.bienvenido-container .seccion-gestion {
  width: 100%;
  margin-bottom: 0.5rem;
}

/* Sección de contenido principal */
/* Loading State */
.bienvenido-container .loading-overlay {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
}

.bienvenido-container .loading-spinner {
  text-align: center;
}

.bienvenido-container .spinner {
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

.bienvenido-container .loading-spinner p {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Content Wrapper */
.bienvenido-container .content-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Hero Banner */
.bienvenido-container .hero-banner {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.15);
}

.bienvenido-container .hero-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1.25rem;
}

.bienvenido-container .hero-icon {
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

.bienvenido-container .hero-icon i {
  font-size: 1.5rem;
  color: white;
}

.bienvenido-container .hero-text h2 {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  color: white;
}

.bienvenido-container .hero-text p {
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0;
  color: rgba(255, 255, 255, 0.95);
}

.bienvenido-container .hero-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.75rem;
}

.bienvenido-container .feature-item {
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

.bienvenido-container .feature-item:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-1px);
}

.bienvenido-container .feature-item i {
  font-size: 1.1rem;
  color: white;
}

.bienvenido-container .feature-item span {
  font-size: 0.8rem;
  font-weight: 600;
  color: white;
}

/* Error State */
.bienvenido-container .error-state {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  max-width: 500px;
  margin: 2rem auto;
}

.bienvenido-container .error-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bienvenido-container .error-icon i {
  font-size: 2rem;
  color: #dc2626;
}

.bienvenido-container .error-state h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.bienvenido-container .error-state p {
  color: #64748b;
  margin-bottom: 1.5rem;
}

.bienvenido-container .retry-btn {
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

.bienvenido-container .retry-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.3);
}

/* Welcome Header */
.bienvenido-container .welcome-header {
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

.bienvenido-container .welcome-text h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.bienvenido-container .user-name {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.bienvenido-container .welcome-subtitle {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 500;
}

.bienvenido-container .welcome-subtitle i {
  color: #0074D9;
}

.bienvenido-container .division-tag {
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

.bienvenido-container .welcome-actions {
  display: flex;
  gap: 0.5rem;
}

.bienvenido-container .action-btn {
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

.bienvenido-container .refresh-btn {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  color: white;
}

.bienvenido-container .refresh-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.3);
}

.bienvenido-container .refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Quick Actions */
.bienvenido-container .quick-actions-section {
  margin-bottom: 1.5rem;
}

.bienvenido-container .quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
}

.bienvenido-container .quick-action-btn {
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

.bienvenido-container .quick-action-btn i {
  font-size: 1.1rem;
  color: #0074D9;
}

.bienvenido-container .quick-action-btn:hover {
  border-color: #0074D9;
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.05) 0%, rgba(0, 86, 166, 0.05) 100%);
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(0, 116, 217, 0.12);
}

/* Admin Section */
.bienvenido-container .admin-section {
  margin-top: 2rem;
}

.bienvenido-container .section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.bienvenido-container .section-title i {
  font-size: 1.5rem;
  color: #0074D9;
}

.bienvenido-container .section-title h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

/* Estados Grid */
.bienvenido-container .estados-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.bienvenido-container .estado-card {
  background: white;
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-left: 4px solid;
  transition: all 0.3s ease;
}

.bienvenido-container .estado-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.bienvenido-container .estado-esperando-recepción {
  border-left-color: #f59e0b;
}

.bienvenido-container .estado-aceptado-en-proceso {
  border-left-color: #0074D9;
}

.bienvenido-container .estado-devuelto-a-seguimiento {
  border-left-color: #8b5cf6;
}

.bienvenido-container .estado-rechazado {
  border-left-color: #ef4444;
}

.bienvenido-container .estado-completado {
  border-left-color: #10b981;
}

.bienvenido-container .estado-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.bienvenido-container .estado-esperando-recepción .estado-icon {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.bienvenido-container .estado-aceptado-en-proceso .estado-icon {
  background: rgba(0, 116, 217, 0.1);
  color: #0074D9;
}

.bienvenido-container .estado-devuelto-a-seguimiento .estado-icon {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.bienvenido-container .estado-rechazado .estado-icon {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.bienvenido-container .estado-completado .estado-icon {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.bienvenido-container .estado-info {
  flex: 1;
}

.bienvenido-container .estado-cantidad {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.bienvenido-container .estado-nombre {
  font-size: 0.85rem;
  color: #64748b;
  font-weight: 500;
}

/* Importancia Section */
.bienvenido-container .importancia-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.bienvenido-container .subsection-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}

.bienvenido-container .subsection-title i {
  color: #f59e0b;
  font-size: 1.15rem;
}

.bienvenido-container .subsection-title h3 {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.bienvenido-container .importancia-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

.bienvenido-container .importancia-item {
  background: #f8fafc;
  border-radius: 10px;
  padding: 1rem;
  border: 2px solid;
  transition: all 0.3s ease;
}

.bienvenido-container .importancia-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.bienvenido-container .nivel-1 {
  border-color: #ef4444;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.1) 100%);
}

.bienvenido-container .nivel-2 {
  border-color: #f59e0b;
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.1) 100%);
}

.bienvenido-container .nivel-3 {
  border-color: #0074D9;
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.05) 0%, rgba(0, 116, 217, 0.1) 100%);
}

.bienvenido-container .nivel-4 {
  border-color: #10b981;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.1) 100%);
}

.bienvenido-container .nivel-5 {
  border-color: #94a3b8;
  background: linear-gradient(135deg, rgba(148, 163, 184, 0.05) 0%, rgba(148, 163, 184, 0.1) 100%);
}

.bienvenido-container .importancia-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.bienvenido-container .nivel-badge {
  font-size: 0.7rem;
  font-weight: 700;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
}

.bienvenido-container .nivel-1 .nivel-badge {
  background: #ef4444;
}

.bienvenido-container .nivel-2 .nivel-badge {
  background: #f59e0b;
}

.bienvenido-container .nivel-3 .nivel-badge {
  background: #0074D9;
}

.bienvenido-container .nivel-4 .nivel-badge {
  background: #10b981;
}

.bienvenido-container .nivel-5 .nivel-badge {
  background: #94a3b8;
}

.bienvenido-container .nivel-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
}

.bienvenido-container .importancia-count {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.bienvenido-container .importancia-bar {
  height: 6px;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 3px;
  overflow: hidden;
}

.bienvenido-container .importancia-bar .bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.6s ease;
}

.bienvenido-container .nivel-1 .importancia-bar .bar-fill {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.bienvenido-container .nivel-2 .importancia-bar .bar-fill {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.bienvenido-container .nivel-3 .importancia-bar .bar-fill {
  background: linear-gradient(90deg, #0074D9, #0056a6);
}

.bienvenido-container .nivel-4 .importancia-bar .bar-fill {
  background: linear-gradient(90deg, #10b981, #059669);
}

.bienvenido-container .nivel-5 .importancia-bar .bar-fill {
  background: linear-gradient(90deg, #94a3b8, #64748b);
}

.bienvenido-container .charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.bienvenido-container .chart-card {
  background: white;
  border-radius: 16px;
  padding: 1.75rem;
  box-shadow: 0 2px 12px rgba(0, 116, 217, 0.08);
  border: 1px solid #e2e8f0;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.bienvenido-container .chart-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #0074D9 0%, #0056a6 100%);
}

.bienvenido-container .chart-card:hover {
  box-shadow: 0 8px 24px rgba(0, 116, 217, 0.15);
  transform: translateY(-2px);
}

.bienvenido-container .chart-header {
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.bienvenido-container .chart-card h3 {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.bienvenido-container .chart-card h3 i {
  color: #0074D9;
  font-size: 1rem;
}

.bienvenido-container .chart-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

/* Municipios List */
.bienvenido-container .municipios-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.bienvenido-container .municipio-item {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  padding: 0.875rem;
  border-radius: 10px;
  background: #f8fafc;
  transition: all 0.3s ease;
}

.bienvenido-container .municipio-item:hover {
  background: #f1f5f9;
  transform: translateX(4px);
}

.bienvenido-container .municipio-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.bienvenido-container .municipio-rank {
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

.bienvenido-container .municipio-item:hover .municipio-rank {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.35);
}

.bienvenido-container .rank-1 {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  box-shadow: 0 2px 8px rgba(251, 191, 36, 0.35);
}

.bienvenido-container .rank-2 {
  background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
  box-shadow: 0 2px 8px rgba(148, 163, 184, 0.35);
}

.bienvenido-container .rank-3 {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  box-shadow: 0 2px 8px rgba(251, 146, 60, 0.35);
}

.bienvenido-container .municipio-name {
  font-weight: 600;
  color: #1e293b;
  flex: 1;
  font-size: 0.9rem;
}

.bienvenido-container .municipio-count-badge {
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

.bienvenido-container .municipio-bar-container {
  width: 100%;
}

.bienvenido-container .municipio-bar {
  position: relative;
  width: 100%;
  height: 36px;
  background: #e2e8f0;
  border-radius: 8px;
  overflow: visible;
  display: flex;
  align-items: center;
}

.bienvenido-container .bar-fill {
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

.bienvenido-container .municipio-item:hover .bar-fill {
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.25);
  filter: brightness(1.05);
}

.bienvenido-container .bar-percentage {
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  white-space: nowrap;
}

/* Trend Chart */
.bienvenido-container .trend-chart {
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

.bienvenido-container .trend-line {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 140px;
  pointer-events: none;
  z-index: 1;
}

.bienvenido-container .trend-day {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  position: relative;
  z-index: 2;
}

.bienvenido-container .trend-bar-container {
  width: 100%;
  height: 140px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  position: relative;
}

.bienvenido-container .trend-bar {
  width: 70%;
  background: linear-gradient(180deg, rgba(0, 116, 217, 0.15) 0%, rgba(0, 116, 217, 0.35) 100%);
  border-radius: 8px 8px 0 0;
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 8px;
  position: relative;
  cursor: pointer;
  border: 2px solid transparent;
}

.bienvenido-container .trend-bar:hover {
  background: linear-gradient(180deg, #0074D9 0%, #0056a6 100%);
  box-shadow: 0 -4px 16px rgba(0, 116, 217, 0.3);
  width: 85%;
  border-color: #0074D9;
}

.bienvenido-container .trend-bar:hover .trend-tooltip {
  opacity: 1;
  transform: translateY(-10px);
}

.bienvenido-container .trend-tooltip {
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

.bienvenido-container .trend-tooltip::after {
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

.bienvenido-container .trend-point {
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

.bienvenido-container .trend-bar:hover + .trend-point {
  width: 16px;
  height: 16px;
  border-width: 4px;
  box-shadow: 0 4px 16px rgba(0, 116, 217, 0.5);
}

.bienvenido-container .point-value {
  display: none;
}

.bienvenido-container .trend-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  margin-top: 0.375rem;
}

/* Quick Actions */
.bienvenido-container .quick-actions-section {
  margin-top: 2rem;
}

.bienvenido-container .quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.bienvenido-container .quick-action-btn {
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

.bienvenido-container .quick-action-btn i {
  font-size: 1.5rem;
  color: #0074D9;
}

.bienvenido-container .quick-action-btn:hover {
  border-color: #0074D9;
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.05) 0%, rgba(0, 86, 166, 0.05) 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.15);
}

/* Estilos para Sección de Canalizadores */
.bienvenido-container .canalizador-section {
  margin-top: 2rem;
}

.bienvenido-container .alertas-destacadas {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.bienvenido-container .alert-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  border-radius: 12px;
  border-left: 4px solid;
}

.bienvenido-container .alert-card.alert-critical {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
  border-left-color: #ef4444;
}

.bienvenido-container .alert-card.alert-warning {
  background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
  border-left-color: #f59e0b;
}

.bienvenido-container .alert-icon {
  font-size: 2rem;
  color: inherit;
}

.bienvenido-container .alert-critical .alert-icon {
  color: #ef4444;
}

.bienvenido-container .alert-warning .alert-icon {
  color: #f59e0b;
}

.bienvenido-container .alert-content {
  flex: 1;
}

.bienvenido-container .alert-message {
  font-weight: 600;
  font-size: 0.95rem;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.bienvenido-container .alert-count {
  font-size: 0.85rem;
  color: #64748b;
}

.bienvenido-container .canalizador-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.bienvenido-container .metric-card {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.bienvenido-container .metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.bienvenido-container .metric-card.total {
  border-left: 4px solid #0074D9;
}

.bienvenido-container .metric-card.retrasadas {
  border-left: 4px solid #f59e0b;
}

.bienvenido-container .metric-icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1.75rem;
}

.bienvenido-container .metric-card.total .metric-icon {
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.1) 0%, rgba(0, 86, 166, 0.05) 100%);
  color: #0074D9;
}

.bienvenido-container .metric-card.retrasadas .metric-icon {
  background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
  color: #f59e0b;
}

.bienvenido-container .metric-content {
  flex: 1;
}

.bienvenido-container .metric-label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.bienvenido-container .metric-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
}

.bienvenido-container .metric-subtitle {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.25rem;
}

.bienvenido-container .departamentos-section,
.bienvenido-container .peticiones-urgentes-section {
  margin-top: 2rem;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.bienvenido-container .departamentos-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.bienvenido-container .departamento-item {
  display: grid;
  grid-template-columns: 50px 1fr 200px;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.bienvenido-container .departamento-item:hover {
  background: #f1f5f9;
  transform: translateX(4px);
}

.bienvenido-container .dept-ranking {
  display: flex;
  align-items: center;
  justify-content: center;
}

.bienvenido-container .dept-rank {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-weight: 700;
  font-size: 0.875rem;
  background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
  color: #475569;
}

.bienvenido-container .dept-rank.rank-1 {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: white;
}

.bienvenido-container .dept-rank.rank-2 {
  background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
  color: white;
}

.bienvenido-container .dept-rank.rank-3 {
  background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
  color: white;
}

.bienvenido-container .dept-info {
  flex: 1;
}

.bienvenido-container .dept-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.95rem;
}

.bienvenido-container .dept-count {
  font-size: 0.85rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.bienvenido-container .dept-bar-container {
  position: relative;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.bienvenido-container .dept-bar {
  height: 100%;
  background: linear-gradient(90deg, #0074D9 0%, #0056a6 100%);
  border-radius: 4px;
  transition: width 0.6s ease;
}

.bienvenido-container .peticiones-urgentes-list {
  display: grid;
  gap: 1rem;
}

.bienvenido-container .peticion-urgente-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.bienvenido-container .peticion-urgente-card:hover {
  border-color: #0074D9;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.15);
}

.bienvenido-container .peticion-urgente-card.nivel-1 {
  border-left: 4px solid #ef4444;
}

.bienvenido-container .peticion-urgente-card.nivel-2 {
  border-left: 4px solid #f59e0b;
}

.bienvenido-container .peticion-urgente-card.nivel-3 {
  border-left: 4px solid #3b82f6;
}

.bienvenido-container .petition-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

.bienvenido-container .petition-folio {
  font-weight: 700;
  color: #1e293b;
  font-size: 0.95rem;
}

.bienvenido-container .petition-nivel {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.bienvenido-container .petition-nivel.nivel-badge-1 {
  background: #fef2f2;
  color: #ef4444;
}

.bienvenido-container .petition-nivel.nivel-badge-2 {
  background: #fffbeb;
  color: #f59e0b;
}

.bienvenido-container .petition-nivel.nivel-badge-3 {
  background: #eff6ff;
  color: #3b82f6;
}

.bienvenido-container .petition-dias {
  margin-left: auto;
  font-size: 0.85rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.bienvenido-container .petition-body {
  margin-bottom: 0.75rem;
}

.bienvenido-container .petition-nombre {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.375rem;
}

.bienvenido-container .petition-descripcion {
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.bienvenido-container .petition-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-top: 0.75rem;
  border-top: 1px solid #f1f5f9;
}

.bienvenido-container .petition-municipio {
  font-size: 0.85rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.bienvenido-container .petition-estado {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  background: #f1f5f9;
  color: #64748b;
}

/* ========================================
   Estilos para Sección de Departamento
   ======================================== */

.bienvenido-container .departamento-section {
  margin-top: 2rem;
}

.bienvenido-container .departamento-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.bienvenido-container .metric-card.total-dept {
  border-left: 4px solid #0074D9;
}

.bienvenido-container .metric-card.pendientes-dept {
  border-left: 4px solid #f59e0b;
}

.bienvenido-container .metric-card.retrasadas-dept {
  border-left: 4px solid #ef4444;
}

.bienvenido-container .departamento-estados-section {
  margin-bottom: 2rem;
}

.bienvenido-container .peticiones-departamento-section {
  margin-top: 2rem;
}

.bienvenido-container .peticiones-departamento-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.bienvenido-container .peticion-dept-card {
  background: white;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  cursor: pointer;
  border-left: 4px solid #cbd5e1;
}

.bienvenido-container .peticion-dept-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.bienvenido-container .peticion-dept-card.retrasada {
  background: #fef2f2;
}

.bienvenido-container .peticion-dept-card.nivel-1 {
  border-left-color: #ef4444;
}

.bienvenido-container .peticion-dept-card.nivel-2 {
  border-left-color: #f59e0b;
}

.bienvenido-container .peticion-dept-card.nivel-3 {
  border-left-color: #3b82f6;
}

.bienvenido-container .petition-dias.dias-retrasado {
  background: #fef2f2;
  color: #ef4444;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 600;
}

.bienvenido-container .petition-estado-dept {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.bienvenido-container .estado-badge-dept-aceptado-en-proceso,
.bienvenido-container .estado-badge-dept-en-proceso {
  background: #dbeafe;
  color: #1e40af;
}

.bienvenido-container .estado-badge-dept-pendiente {
  background: #fef3c7;
  color: #92400e;
}

.bienvenido-container .estado-badge-dept-completado {
  background: #d1fae5;
  color: #065f46;
}

.bienvenido-container .estado-badge-dept-rechazado {
  background: #fee2e2;
  color: #991b1b;
}

.bienvenido-container .no-peticiones-message {
  text-align: center;
  padding: 3rem 2rem;
  background: #f8fafc;
  border-radius: 12px;
  color: #64748b;
}

.bienvenido-container .no-peticiones-message i {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: #cbd5e1;
}

.bienvenido-container .no-peticiones-message h3 {
  font-size: 1.25rem;
  color: #475569;
  margin-bottom: 0.5rem;
}

.bienvenido-container .no-peticiones-message p {
  font-size: 0.95rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .bienvenido-container .charts-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .bienvenido-container {
    padding: 1rem;
  }

  .bienvenido-container .hero-banner {
    padding: 1.5rem;
  }

  .bienvenido-container .hero-content {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }

  .bienvenido-container .hero-text h2 {
    font-size: 1.5rem;
  }

  .bienvenido-container .hero-text p {
    font-size: 0.9rem;
  }

  .bienvenido-container .hero-features {
    grid-template-columns: 1fr;
  }

  .bienvenido-container .welcome-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.5rem;
    padding: 1.5rem;
  }

  .bienvenido-container .welcome-text h1 {
    font-size: 1.5rem;
  }

  .bienvenido-container .welcome-actions {
    width: 100%;
  }

  .bienvenido-container .action-btn {
    flex: 1;
    justify-content: center;
  }

  .bienvenido-container .quick-actions-grid {
    grid-template-columns: 1fr;
  }

  .bienvenido-container .charts-grid {
    grid-template-columns: 1fr;
  }

  .bienvenido-container .trend-chart {
    height: 160px;
  }

  .bienvenido-container .trend-bar-container {
    height: 100px;
  }
}

@media (max-width: 480px) {
  .bienvenido-container .hero-banner {
    padding: 1.25rem;
  }

  .bienvenido-container .hero-icon {
    width: 60px;
    height: 60px;
  }

  .bienvenido-container .hero-icon i {
    font-size: 2rem;
  }

  .bienvenido-container .hero-text h2 {
    font-size: 1.25rem;
  }

  .bienvenido-container .hero-text p {
    font-size: 0.85rem;
  }

  .bienvenido-container .feature-item {
    padding: 0.875rem 1rem;
  }

  .bienvenido-container .feature-item i {
    font-size: 1.25rem;
  }

  .bienvenido-container .feature-item span {
    font-size: 0.85rem;
  }

  .bienvenido-container .welcome-text h1 {
    font-size: 1.25rem;
  }

  .bienvenido-container .welcome-subtitle {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .bienvenido-container .section-title h2 {
    font-size: 1.25rem;
  }
}
</style>
