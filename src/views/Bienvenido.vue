<template>
  <div class="bv-page">
    <!-- Loading -->
    <div v-if="isLoading" class="bv-loading">
      <div class="bv-loading-box">
        <div class="bv-spinner"></div>
        <p>Cargando tu panel...</p>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bv-error">
      <div class="bv-error-icon">
        <i class="fas fa-exclamation-circle"></i>
      </div>
      <h3>Error al cargar el dashboard</h3>
      <p>{{ error }}</p>
      <button @click="loadDashboardData" class="bv-retry-btn">
        <i class="fas fa-sync-alt"></i>
        Reintentar
      </button>
    </div>

    <!-- Main Content -->
    <div v-else class="bv-content">
      <!-- Welcome Hero -->
      <div class="bv-hero">
        <div class="bv-hero-bg"></div>
        <div class="bv-hero-content">
          <div class="bv-hero-left">
            <div class="bv-avatar">{{ userInitial }}</div>
            <div class="bv-hero-info">
              <p class="bv-greeting">Bienvenido de vuelta</p>
              <h1 class="bv-name">{{ userName }}</h1>
              <div class="bv-role-tags">
                <span class="bv-role-tag">
                  <i class="fas fa-briefcase"></i>
                  {{ userRole }}
                </span>
                <span v-if="userDivision" class="bv-division-tag">
                  <i class="fas fa-map-marker-alt"></i>
                  {{ userDivision }}
                </span>
              </div>
            </div>
          </div>
          <button @click="refreshData" class="bv-refresh-btn" :disabled="isRefreshing">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': isRefreshing }"></i>
            <span>Actualizar</span>
          </button>
        </div>
      </div>

      <!-- Alertas (todos los roles) -->
      <div v-if="dashboardData && dashboardData.alerts && dashboardData.alerts.length > 0" class="bv-alerts">
        <div
          v-for="(alert, index) in dashboardData.alerts"
          :key="index"
          class="bv-alert"
          :class="'bv-alert--' + alert.type"
        >
          <div class="bv-alert-icon">
            <i :class="alert.type === 'critical' ? 'fas fa-exclamation-circle' : 'fas fa-clock'"></i>
          </div>
          <div class="bv-alert-text">
            <span class="bv-alert-msg">{{ alert.message }}</span>
            <span class="bv-alert-count">{{ alert.count }} peticiones</span>
          </div>
        </div>
      </div>

      <!-- Accesos Directos (SOLO Director y Super Usuario) -->
      <div v-if="isAdmin && filteredQuickActions.length > 0" class="bv-shortcuts bv-fade-in">
        <h2 class="bv-section-title">
          <span class="bv-section-icon"><i class="fas fa-th-large"></i></span>
          <span>Accesos directos</span>
          <span class="bv-section-line"></span>
        </h2>
        <div class="bv-shortcuts-grid">
          <button
            v-for="action in filteredQuickActions"
            :key="action.name"
            @click="action.handler"
            class="bv-shortcut"
          >
            <div class="bv-shortcut-icon">
              <i :class="action.icon"></i>
            </div>
            <span class="bv-shortcut-label">{{ action.label }}</span>
          </button>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- ADMIN (Director / Super Usuario)             -->
      <!-- ============================================ -->
      <template v-if="isAdmin && dashboardData && dashboardData.statistics">
        <h2 class="bv-section-title bv-fade-in">
          <span class="bv-section-icon"><i class="fas fa-chart-bar"></i></span>
          <span>Vista general</span>
          <span class="bv-section-line"></span>
        </h2>

        <!-- Métricas de estados -->
        <div v-if="dashboardData.statistics.por_estado" class="bv-states-grid bv-fade-in">
          <div
            v-for="(estado, index) in dashboardData.statistics.por_estado"
            :key="index"
            class="bv-state-card"
            :class="'bv-state--' + slugify(estado.estado)"
          >
            <div class="bv-state-icon">
              <i :class="getEstadoIcon(estado.estado)"></i>
            </div>
            <div class="bv-state-data">
              <div class="bv-state-value">{{ estado.cantidad }}</div>
              <div class="bv-state-label">{{ estado.estado }}</div>
            </div>
          </div>
        </div>

        <!-- Importancia -->
        <div v-if="dashboardData.statistics.por_importancia" class="bv-card">
          <h3 class="bv-card-title">
            <i class="fas fa-flag"></i>
            Peticiones por importancia
          </h3>
          <div class="bv-importance-grid">
            <div
              v-for="imp in dashboardData.statistics.por_importancia"
              :key="imp.NivelImportancia"
              class="bv-importance-item"
              :class="'bv-nivel--' + imp.NivelImportancia"
            >
              <div class="bv-importance-head">
                <span class="bv-nivel-badge">Nivel {{ imp.NivelImportancia }}</span>
                <span class="bv-nivel-label">{{ getNivelLabel(imp.NivelImportancia) }}</span>
              </div>
              <div class="bv-importance-value">{{ imp.cantidad }}</div>
              <div class="bv-importance-bar">
                <div class="bv-importance-fill" :style="{ width: getPercentage(imp.cantidad, dashboardData.statistics.por_importancia.map(i => i.cantidad)) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <RecentActivity
          v-if="dashboardData"
          :petitions="dashboardData.recent_petitions || []"
          :alerts="dashboardData.alerts || []"
          @view-petition="viewPetitionDetails"
        /><br>

        <!-- Grid de charts: Municipios + Tendencia -->
        <div class="bv-charts-row bv-fade-in">
          <div v-if="dashboardData.statistics.top_municipios" class="bv-card">
            <h3 class="bv-card-title">
              <i class="fas fa-map-marked-alt"></i>
              Top municipios
            </h3>
            <div class="bv-ranking-list">
              <div
                v-for="(mun, index) in dashboardData.statistics.top_municipios"
                :key="index"
                class="bv-ranking-item"
              >
                <span class="bv-rank" :class="'bv-rank--' + (index + 1)">{{ index + 1 }}</span>
                <span class="bv-ranking-name">{{ mun.Municipio || 'Sin municipio' }}</span>
                <span class="bv-ranking-count">{{ mun.cantidad }}</span>
                <div class="bv-ranking-bar">
                  <div class="bv-ranking-fill" :style="{ width: getPercentage(mun.cantidad, dashboardData.statistics.top_municipios.map(m => m.cantidad)) + '%' }"></div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="dashboardData.statistics.ultimos_7_dias" class="bv-card">
            <h3 class="bv-card-title">
              <i class="fas fa-chart-line"></i>
              Tendencia - 7 dias
            </h3>
            <div class="bv-trend">
              <div
                v-for="(day, index) in dashboardData.statistics.ultimos_7_dias"
                :key="index"
                class="bv-trend-col"
              >
                <div class="bv-trend-value">{{ day.cantidad }}</div>
                <div class="bv-trend-bar-wrap">
                  <div class="bv-trend-bar" :style="{ height: getTrendHeight(day.cantidad) + '%' }"></div>
                </div>
                <div class="bv-trend-label">{{ formatShortDate(day.fecha) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actividad reciente -->

      </template>

      <!-- ============================================ -->
      <!-- CANALIZADOR (Municipal / Estatal)            -->
      <!-- ============================================ -->
      <template v-if="(isCanalizadorMunicipal || isCanalizadorEstatal) && !isAdmin && dashboardData && dashboardData.statistics">
        <h2 class="bv-section-title bv-fade-in">
          <span class="bv-section-icon"><i class="fas fa-broadcast-tower"></i></span>
          <span>{{ isCanalizadorMunicipal ? 'Panel de canalización municipal' : 'Panel de canalización estatal' }}</span>
          <span class="bv-section-line"></span>
        </h2>

        <!-- Métricas -->
        <div class="bv-metrics-row bv-fade-in">
          <div class="bv-metric bv-metric--blue">
            <div class="bv-metric-icon"><i class="fas fa-inbox"></i></div>
            <div class="bv-metric-data">
              <div class="bv-metric-value">{{ dashboardData.statistics.total_peticiones || 0 }}</div>
              <div class="bv-metric-label">Total peticiones</div>
            </div>
          </div>
          <div class="bv-metric bv-metric--amber">
            <div class="bv-metric-icon"><i class="fas fa-hourglass-end"></i></div>
            <div class="bv-metric-data">
              <div class="bv-metric-value">{{ dashboardData.statistics.peticiones_retrasadas || 0 }}</div>
              <div class="bv-metric-label">Retrasadas (+30 dias)</div>
            </div>
          </div>
        </div>

        <!-- Estados -->
        <div v-if="dashboardData.statistics.por_estado" class="bv-states-grid bv-fade-in">
          <div
            v-for="(estado, index) in dashboardData.statistics.por_estado"
            :key="index"
            class="bv-state-card"
            :class="'bv-state--' + slugify(estado.estado)"
          >
            <div class="bv-state-icon">
              <i :class="getEstadoIcon(estado.estado)"></i>
            </div>
            <div class="bv-state-data">
              <div class="bv-state-value">{{ estado.cantidad }}</div>
              <div class="bv-state-label">{{ estado.estado }}</div>
            </div>
          </div>
        </div>

        <!-- Top Departamentos -->
        <div v-if="dashboardData.statistics.departamentos_top && dashboardData.statistics.departamentos_top.length > 0" class="bv-card">
          <h3 class="bv-card-title">
            <i class="fas fa-building"></i>
            Departamentos con mas peticiones
          </h3>
          <div class="bv-ranking-list">
            <div
              v-for="(dept, index) in dashboardData.statistics.departamentos_top"
              :key="index"
              class="bv-ranking-item"
            >
              <span class="bv-rank" :class="'bv-rank--' + (index + 1)">{{ index + 1 }}</span>
              <span class="bv-ranking-name">{{ dept.departamento }}</span>
              <span class="bv-ranking-count">{{ dept.cantidad }}</span>
              <div class="bv-ranking-bar">
                <div class="bv-ranking-fill" :style="{ width: getPercentage(dept.cantidad, dashboardData.statistics.departamentos_top.map(d => d.cantidad)) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Municipios (Estatal) -->
        <div v-if="isCanalizadorEstatal && dashboardData.statistics.top_municipios" class="bv-card">
          <h3 class="bv-card-title">
            <i class="fas fa-map-marked-alt"></i>
            Municipios con mas peticiones
          </h3>
          <div class="bv-ranking-list">
            <div
              v-for="(mun, index) in dashboardData.statistics.top_municipios"
              :key="index"
              class="bv-ranking-item"
            >
              <span class="bv-rank" :class="'bv-rank--' + (index + 1)">{{ index + 1 }}</span>
              <span class="bv-ranking-name">{{ mun.Municipio || 'Sin municipio' }}</span>
              <span class="bv-ranking-count">{{ mun.cantidad }}</span>
              <div class="bv-ranking-bar">
                <div class="bv-ranking-fill" :style="{ width: getPercentage(mun.cantidad, dashboardData.statistics.top_municipios.map(m => m.cantidad)) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Peticiones urgentes -->
        <div v-if="dashboardData.recent_petitions && dashboardData.recent_petitions.length > 0" class="bv-card bv-card--carousel">
          <h3 class="bv-card-title">
            <i class="fas fa-exclamation-triangle"></i>
            Peticiones que requieren atencion
            <span class="bv-card-count">{{ dashboardData.recent_petitions.length }}</span>
          </h3>
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
            class="bv-swiper"
          >
            <SwiperSlide
              v-for="petition in dashboardData.recent_petitions"
              :key="petition.id"
            >
              <div
                class="bv-petition-card"
                :class="'bv-nivel--' + petition.NivelImportancia"
                @click="viewPetitionDetails(petition.folio)"
              >
                <div class="bv-petition-top">
                  <span class="bv-petition-folio">{{ petition.folio }}</span>
                  <span class="bv-petition-nivel">Nivel {{ petition.NivelImportancia }}</span>
                  <span v-if="petition.dias_transcurridos" class="bv-petition-days">
                    <i class="fas fa-clock"></i> {{ petition.dias_transcurridos }} dias
                  </span>
                </div>
                <div class="bv-petition-name">{{ petition.nombre }}</div>
                <div class="bv-petition-desc">{{ petition.descripcion }}</div>
                <div class="bv-petition-images" @click.stop>
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
                <div class="bv-petition-bottom">
                  <span class="bv-petition-mun">
                    <i class="fas fa-map-marker-alt"></i>
                    {{ petition.Municipio || 'Sin municipio' }}
                  </span>
                  <span class="bv-petition-status">{{ petition.estado }}</span>
                </div>
              </div>
            </SwiperSlide>
          </Swiper>
        </div>
      </template>

      <!-- ============================================ -->
      <!-- DEPARTAMENTO                                 -->
      <!-- ============================================ -->
      <template v-if="isDepartmentUser && !isAdmin && dashboardData && dashboardData.statistics">
        <h2 class="bv-section-title bv-fade-in">
          <span class="bv-section-icon"><i class="fas fa-building"></i></span>
          <span>{{ dashboardData.statistics.nombre_departamento || 'Tu departamento' }}</span>
          <span class="bv-section-line"></span>
        </h2>

        <!-- Métricas del depto -->
        <div class="bv-metrics-row bv-fade-in">
          <div class="bv-metric bv-metric--blue">
            <div class="bv-metric-icon"><i class="fas fa-tasks"></i></div>
            <div class="bv-metric-data">
              <div class="bv-metric-value">{{ dashboardData.statistics.total_peticiones || 0 }}</div>
              <div class="bv-metric-label">Asignadas</div>
            </div>
          </div>
          <div class="bv-metric bv-metric--amber">
            <div class="bv-metric-icon"><i class="fas fa-spinner"></i></div>
            <div class="bv-metric-data">
              <div class="bv-metric-value">{{ dashboardData.statistics.peticiones_pendientes || 0 }}</div>
              <div class="bv-metric-label">Pendientes</div>
            </div>
          </div>
          <div class="bv-metric bv-metric--red">
            <div class="bv-metric-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="bv-metric-data">
              <div class="bv-metric-value">{{ dashboardData.statistics.peticiones_retrasadas || 0 }}</div>
              <div class="bv-metric-label">Retrasadas (+15 dias)</div>
            </div>
          </div>
        </div>

        <!-- Estados -->
        <div v-if="dashboardData.statistics.por_estado && dashboardData.statistics.por_estado.length > 0" class="bv-states-grid bv-fade-in">
          <div
            v-for="(estado, index) in dashboardData.statistics.por_estado"
            :key="index"
            class="bv-state-card"
            :class="'bv-state--' + slugify(estado.estado)"
          >
            <div class="bv-state-icon">
              <i :class="getEstadoIcon(estado.estado)"></i>
            </div>
            <div class="bv-state-data">
              <div class="bv-state-value">{{ estado.cantidad }}</div>
              <div class="bv-state-label">{{ estado.estado }}</div>
            </div>
          </div>
        </div>

        <!-- Importancia -->
        <div v-if="dashboardData.statistics.por_importancia && dashboardData.statistics.por_importancia.length > 0" class="bv-card">
          <h3 class="bv-card-title">
            <i class="fas fa-flag"></i>
            Por nivel de importancia
          </h3>
          <div class="bv-importance-grid">
            <div
              v-for="imp in dashboardData.statistics.por_importancia"
              :key="imp.NivelImportancia"
              class="bv-importance-item"
              :class="'bv-nivel--' + imp.NivelImportancia"
            >
              <div class="bv-importance-head">
                <span class="bv-nivel-badge">Nivel {{ imp.NivelImportancia }}</span>
                <span class="bv-nivel-label">{{ getNivelLabel(imp.NivelImportancia) }}</span>
              </div>
              <div class="bv-importance-value">{{ imp.cantidad }}</div>
              <div class="bv-importance-bar">
                <div class="bv-importance-fill" :style="{ width: getPercentage(imp.cantidad, dashboardData.statistics.por_importancia.map(i => i.cantidad)) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Peticiones asignadas o empty state -->
        <div v-if="dashboardData.recent_petitions && dashboardData.recent_petitions.length > 0" class="bv-card bv-card--carousel">
          <h3 class="bv-card-title">
            <i class="fas fa-clipboard-list"></i>
            Peticiones asignadas
            <span class="bv-card-count">{{ dashboardData.recent_petitions.length }}</span>
          </h3>
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
            class="bv-swiper"
          >
            <SwiperSlide
              v-for="petition in dashboardData.recent_petitions"
              :key="petition.id"
            >
              <div
                class="bv-petition-card"
                :class="[
                  'bv-nivel--' + petition.NivelImportancia,
                  petition.dias_asignacion > 15 ? 'bv-petition--late' : ''
                ]"
                @click="viewPetitionDetails(petition.folio)"
              >
                <div class="bv-petition-top">
                  <span class="bv-petition-folio">{{ petition.folio }}</span>
                  <span class="bv-petition-nivel">Nivel {{ petition.NivelImportancia }}</span>
                  <span v-if="petition.dias_asignacion" class="bv-petition-days" :class="{ 'bv-petition-days--late': petition.dias_asignacion > 15 }">
                    <i class="fas fa-calendar-day"></i> {{ petition.dias_asignacion }} dias
                  </span>
                </div>
                <div class="bv-petition-name">{{ petition.nombre }}</div>
                <div class="bv-petition-desc">{{ petition.descripcion }}</div>
                <div class="bv-petition-images" @click.stop>
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
                <div class="bv-petition-bottom">
                  <span class="bv-petition-mun">
                    <i class="fas fa-map-marker-alt"></i>
                    {{ petition.Municipio || 'Sin municipio' }}
                  </span>
                  <span class="bv-petition-status">{{ petition.estado_departamento }}</span>
                </div>
              </div>
            </SwiperSlide>
          </Swiper>
        </div>
        <div v-else class="bv-empty">
          <i class="fas fa-inbox"></i>
          <h3>Sin peticiones asignadas</h3>
          <p>Tu departamento no tiene peticiones pendientes.</p>
        </div>
      </template>

      <!-- ============================================ -->
      <!-- OTROS ROLES (Formulario, etc)                -->
      <!-- ============================================ -->
      <template v-if="!isAdmin && !isCanalizadorMunicipal && !isCanalizadorEstatal && !isDepartmentUser && dashboardData">
        <UserMetricsCards
          v-if="dashboardData.statistics"
          :stats="dashboardData.statistics"
          :userRole="userRoleId"
        />
        <RecentActivity
          :petitions="dashboardData.recent_petitions || []"
          :alerts="dashboardData.alerts || []"
          @view-petition="viewPetitionDetails"
        />
      </template>
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
import ImageGallery from '@/components/ImageGallery.vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation, Pagination } from 'swiper/modules'

export default {
  name: 'BienvenidoDashboard',
  components: { UserMetricsCards, RecentActivity, ImageGallery, Swiper, SwiperSlide },
  setup() {
    const router = useRouter()
    const isLoading = ref(true)
    const isRefreshing = ref(false)
    const error = ref(null)
    const dashboardData = ref(null)

    // --- User data ---
    const currentUser = computed(() => authService.getCurrentUser())
    const usuario = computed(() => currentUser.value?.usuario || null)

    const userName = computed(() => {
      return usuario.value?.Nombre || usuario.value?.Usuario || 'Usuario'
    })

    const userInitial = computed(() => {
      const name = userName.value
      return name ? name.charAt(0).toUpperCase() : 'U'
    })

    const userRole = computed(() => {
      const roles = usuario.value?.RolesNombres
      if (Array.isArray(roles) && roles.length > 0) return roles.join(', ')
      return currentUser.value?.rol?.nombre || 'Usuario'
    })

    const userRoleId = computed(() => usuario.value?.IdRolSistema || null)
    const userDivision = computed(() => usuario.value?.NombreDivision || null)

    const userPermissions = computed(() => {
      return usuario.value?.Permisos || currentUser.value?.permisos || []
    })

    const userRoles = computed(() => {
      const roles = usuario.value?.RolesNombres
      if (Array.isArray(roles) && roles.length > 0) return roles
      if (currentUser.value?.rol?.nombre) return [currentUser.value.rol.nombre]
      return []
    })

    // --- Permission helpers ---
    const hasPermission = (p) => userPermissions.value.includes(p)
    const hasAnyPermission = (ps) => ps.some(p => userPermissions.value.includes(p))
    const hasRole = (name) => userRoles.value.some(r => r.toLowerCase() === name.toLowerCase())

    // --- Role checks ---
    const isAdmin = computed(() => {
      return hasRole('Super Usuario') || hasRole('Director') ||
        hasAnyPermission(['admin_peticiones', 'configuracion_sistema', 'gestionar_usuarios', 'ver_reportes'])
    })

    const isDepartmentUser = computed(() => hasRole('Departamento') || hasPermission('gestion_peticiones_departamento'))
    const isCanalizadorMunicipal = computed(() => hasRole('Canalizador Municipal') || hasPermission('peticiones_municipio'))
    const isCanalizadorEstatal = computed(() => hasRole('Canalizador Estatal') || hasPermission('peticiones_estatal'))

    // --- Quick actions (SOLO para admin) ---
    const quickActions = [
      { name: 'peticiones', label: 'Peticiones', icon: 'fas fa-tasks', handler: () => router.push('/peticiones'), requiredPermission: 'admin_peticiones' },
      { name: 'configuracion', label: 'Configuracion', icon: 'fas fa-cog', handler: () => router.push('/configuracion'), requiredPermission: 'acceder_configuracion' },
      { name: 'reportes', label: 'Reportes', icon: 'fas fa-chart-bar', handler: () => router.push('/reportes'), requiredPermission: 'ver_reportes' },
      { name: 'tablero', label: 'Tablero', icon: 'fas fa-th-large', handler: () => router.push('/tablero'), requiredPermission: 'ver_tablero' },
      { name: 'usuarios', label: 'Usuarios', icon: 'fas fa-users', handler: () => router.push('/configuracion/usuarios'), requiredPermission: 'ver_usuarios' },
    ]

    const filteredQuickActions = computed(() => {
      if (!Array.isArray(userPermissions.value) || userPermissions.value.length === 0) return []
      return quickActions.filter(a => userPermissions.value.includes(a.requiredPermission))
    })

    // --- Data loading ---
    const loadDashboardData = async () => {
      try {
        isLoading.value = true
        error.value = null
        const response = await axios.get('dashboard-user.php')
        if (!response.data.success) throw new Error(response.data.message || 'Error al cargar datos')
        dashboardData.value = response.data
      } catch (err) {
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

    // --- Helpers ---
    const slugify = (str) => str.toLowerCase().replace(/ /g, '-').replace(/[^a-z0-9-]/g, '')

    const getPercentage = (value, allValues) => {
      const total = allValues.reduce((sum, v) => sum + v, 0)
      return total > 0 ? (value / total) * 100 : 0
    }

    const getTrendHeight = (cantidad) => {
      if (!dashboardData.value?.statistics?.ultimos_7_dias) return 0
      const max = Math.max(...dashboardData.value.statistics.ultimos_7_dias.map(d => d.cantidad))
      return max > 0 ? (cantidad / max) * 100 : 0
    }

    const formatShortDate = (dateString) => {
      const date = new Date(dateString)
      return ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'][date.getDay()]
    }

    const viewPetitionDetails = (folio) => {
      if (isDepartmentUser.value && !isAdmin.value) {
        router.push(`/departamentos?folio=${folio}`)
      } else {
        router.push(`/peticiones?folio=${folio}`)
      }
    }

    const getEstadoIcon = (estado) => {
      const map = {
        'Esperando recepcion': 'fas fa-clock',
        'Esperando recepción': 'fas fa-clock',
        'Aceptado en proceso': 'fas fa-cog',
        'Devuelto a seguimiento': 'fas fa-undo',
        'Rechazado': 'fas fa-times-circle',
        'Completado': 'fas fa-check-circle'
      }
      return map[estado] || 'fas fa-file-alt'
    }

    const getNivelLabel = (nivel) => {
      return { 1: 'Muy Alta', 2: 'Alta', 3: 'Media', 4: 'Baja', 5: 'Muy Baja' }[nivel] || 'N/D'
    }

    const swiperModules = [Navigation, Pagination]

    onMounted(() => loadDashboardData())

    return {
      isLoading, isRefreshing, error, dashboardData,
      userName, userInitial, userRole, userRoleId, userDivision,
      isAdmin, isDepartmentUser, isCanalizadorMunicipal, isCanalizadorEstatal,
      filteredQuickActions, swiperModules,
      loadDashboardData, refreshData,
      slugify, getPercentage, getTrendHeight, formatShortDate,
      viewPetitionDetails, getEstadoIcon, getNivelLabel
    }
  }
}
</script>

<style scoped>
/* =============================================
   BIENVENIDO - Rediseño premium (prefijo bv-)
   ============================================= */

/* --- Animations --- */
@keyframes bv-spin {
  to { transform: rotate(360deg); }
}

@keyframes bv-fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes bv-heroShine {
  0% { transform: translateX(-100%) rotate(15deg); }
  100% { transform: translateX(200%) rotate(15deg); }
}

.bv-fade-in {
  animation: bv-fadeUp 0.5s ease-out both;
}

.bv-page {
  padding: 1.5rem 1.75rem;
  max-width: 1340px;
  margin: 0 auto;
  min-height: 80vh;
}

/* --- Loading --- */
.bv-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
}

.bv-loading-box {
  text-align: center;
}

.bv-spinner {
  width: 52px;
  height: 52px;
  margin: 0 auto 1.25rem;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #0074D9;
  border-radius: 50%;
  animation: bv-spin 0.8s linear infinite;
}

.bv-loading-box p {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
}

/* --- Error --- */
.bv-error {
  text-align: center;
  padding: 3rem 2rem;
  background: white;
  border-radius: 20px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.06);
  max-width: 440px;
  margin: 3rem auto;
}

.bv-error-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 1.5rem;
  background: #fef2f2;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bv-error-icon i {
  font-size: 2rem;
  color: #dc2626;
}

.bv-error h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem;
}

.bv-error p {
  color: #64748b;
  margin-bottom: 1.75rem;
  font-size: 0.9rem;
}

.bv-retry-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #0074D9, #0056a6);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.bv-retry-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,116,217,0.35);
}

/* --- Hero Welcome --- */
.bv-hero {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  margin-bottom: 1.75rem;
  animation: bv-fadeUp 0.4s ease-out both;
}

.bv-hero-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 50%, #003d80 100%);
  z-index: 0;
}

.bv-hero-bg::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.bv-hero-bg::after {
  content: '';
  position: absolute;
  top: -50%;
  left: -30%;
  width: 60%;
  height: 200%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
  animation: bv-heroShine 6s ease-in-out infinite;
}

.bv-hero-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.75rem 2rem;
  gap: 1.25rem;
}

.bv-hero-left {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  min-width: 0;
}

.bv-avatar {
  flex-shrink: 0;
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: rgba(255,255,255,0.2);
  backdrop-filter: blur(8px);
  border: 2px solid rgba(255,255,255,0.3);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.35rem;
  text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.bv-hero-info {
  min-width: 0;
}

.bv-greeting {
  font-size: 0.8rem;
  color: rgba(255,255,255,0.7);
  font-weight: 500;
  margin: 0 0 0.15rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.bv-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  margin: 0 0 0.5rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.bv-role-tags {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.bv-role-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.775rem;
  color: rgba(255,255,255,0.9);
  font-weight: 500;
  background: rgba(255,255,255,0.15);
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  backdrop-filter: blur(4px);
}

.bv-role-tag i {
  font-size: 0.7rem;
  opacity: 0.8;
}

.bv-division-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
  color: white;
  font-weight: 600;
  background: rgba(255,255,255,0.2);
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  backdrop-filter: blur(4px);
}

.bv-division-tag i {
  font-size: 0.65rem;
}

.bv-refresh-btn {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.15rem;
  background: rgba(255,255,255,0.2);
  backdrop-filter: blur(8px);
  color: white;
  border: 1.5px solid rgba(255,255,255,0.3);
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.25s ease;
}

.bv-refresh-btn:hover:not(:disabled) {
  background: rgba(255,255,255,0.3);
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.bv-refresh-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* --- Alerts --- */
.bv-alerts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 0.875rem;
  margin-bottom: 1.75rem;
  animation: bv-fadeUp 0.45s ease-out both;
}

.bv-alert {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.15rem;
  border-radius: 12px;
  border-left: 4px solid;
  transition: all 0.25s ease;
}

.bv-alert:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

.bv-alert--critical {
  background: linear-gradient(135deg, #fef2f2, #fee2e2);
  border-left-color: #ef4444;
}

.bv-alert--warning {
  background: linear-gradient(135deg, #fffbeb, #fef3c7);
  border-left-color: #f59e0b;
}

.bv-alert-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.bv-alert--critical .bv-alert-icon {
  background: rgba(239,68,68,0.15);
  color: #ef4444;
}

.bv-alert--warning .bv-alert-icon {
  background: rgba(245,158,11,0.15);
  color: #f59e0b;
}

.bv-alert-text {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.bv-alert-msg {
  font-weight: 600;
  font-size: 0.875rem;
  color: #1e293b;
}

.bv-alert-count {
  font-size: 0.775rem;
  color: #64748b;
}

/* --- Section title --- */
.bv-section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin: 2rem 0 1.25rem;
}

.bv-section-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: linear-gradient(135deg, #0074D9, #0056a6);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bv-section-icon i {
  color: white;
  font-size: 0.85rem;
}

.bv-section-line {
  flex: 1;
  height: 2px;
  background: linear-gradient(90deg, #e2e8f0, transparent);
  border-radius: 1px;
}

/* --- Shortcuts (solo admin) --- */
.bv-shortcuts {
  margin-bottom: 0.75rem;
}

.bv-shortcuts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
  gap: 0.875rem;
}

.bv-shortcut {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  background: white;
  border: 1.5px solid #e2e8f0;
  padding: 1.25rem 0.75rem;
  border-radius: 14px;
  font-weight: 600;
  font-size: 0.85rem;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
}

.bv-shortcut-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(0,116,217,0.08), rgba(0,86,166,0.12));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.bv-shortcut-icon i {
  font-size: 1.15rem;
  color: #0074D9;
  transition: all 0.3s ease;
}

.bv-shortcut-label {
  font-size: 0.8rem;
  color: #475569;
  font-weight: 600;
}

.bv-shortcut:hover {
  border-color: #0074D9;
  background: white;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,116,217,0.15);
}

.bv-shortcut:hover .bv-shortcut-icon {
  background: linear-gradient(135deg, #0074D9, #0056a6);
}

.bv-shortcut:hover .bv-shortcut-icon i {
  color: white;
}

/* --- States grid --- */
.bv-states-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 0.875rem;
  margin-bottom: 1.5rem;
}

.bv-state-card {
  background: white;
  border-radius: 14px;
  padding: 1.15rem;
  display: flex;
  align-items: center;
  gap: 0.875rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  border-top: 3px solid #cbd5e1;
  border-left: none;
  transition: all 0.3s ease;
}

.bv-state-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.bv-state-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
}

.bv-state-value {
  font-size: 1.6rem;
  font-weight: 800;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 0.2rem;
}

.bv-state-label {
  font-size: 0.775rem;
  color: #64748b;
  font-weight: 500;
}

/* State colors — now top border */
.bv-state--esperando-recepcion,
.bv-state--esperando-recepción {
  border-top-color: #f59e0b;
}
.bv-state--esperando-recepcion .bv-state-icon,
.bv-state--esperando-recepción .bv-state-icon {
  background: rgba(245,158,11,0.1);
  color: #f59e0b;
}

.bv-state--aceptado-en-proceso {
  border-top-color: #0074D9;
}
.bv-state--aceptado-en-proceso .bv-state-icon {
  background: rgba(0,116,217,0.1);
  color: #0074D9;
}

.bv-state--devuelto-a-seguimiento {
  border-top-color: #8b5cf6;
}
.bv-state--devuelto-a-seguimiento .bv-state-icon {
  background: rgba(139,92,246,0.1);
  color: #8b5cf6;
}

.bv-state--rechazado {
  border-top-color: #ef4444;
}
.bv-state--rechazado .bv-state-icon {
  background: rgba(239,68,68,0.1);
  color: #ef4444;
}

.bv-state--completado {
  border-top-color: #10b981;
}
.bv-state--completado .bv-state-icon {
  background: rgba(16,185,129,0.1);
  color: #10b981;
}

/* --- Metrics row --- */
.bv-metrics-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0.875rem;
  margin-bottom: 1.5rem;
}

.bv-metric {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.35rem;
  background: white;
  border-radius: 14px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  border-top: 3px solid;
  border-left: none;
  transition: all 0.3s ease;
}

.bv-metric:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.bv-metric--blue { border-top-color: #0074D9; }
.bv-metric--amber { border-top-color: #f59e0b; }
.bv-metric--red { border-top-color: #ef4444; }

.bv-metric-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}

.bv-metric--blue .bv-metric-icon {
  background: linear-gradient(135deg, rgba(0,116,217,0.1), rgba(0,116,217,0.05));
  color: #0074D9;
}

.bv-metric--amber .bv-metric-icon {
  background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.05));
  color: #f59e0b;
}

.bv-metric--red .bv-metric-icon {
  background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(239,68,68,0.05));
  color: #ef4444;
}

.bv-metric-value {
  font-size: 1.85rem;
  font-weight: 800;
  color: #1e293b;
  line-height: 1;
}

.bv-metric-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
  margin-top: 0.25rem;
}

/* --- Card generic --- */
.bv-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  border: 1px solid rgba(0,0,0,0.04);
  transition: box-shadow 0.3s ease;
  animation: bv-fadeUp 0.5s ease-out both;
}

.bv-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.07);
}

.bv-card-title {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 1.15rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.bv-card-title i {
  color: #0074D9;
  font-size: 0.95rem;
}

/* --- Importance --- */
.bv-importance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
  gap: 0.875rem;
}

.bv-importance-item {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1rem;
  border: 1.5px solid;
  transition: all 0.3s ease;
}

.bv-importance-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.bv-nivel--1 { border-color: #ef4444; background: rgba(239,68,68,0.03); }
.bv-nivel--2 { border-color: #f59e0b; background: rgba(245,158,11,0.03); }
.bv-nivel--3 { border-color: #0074D9; background: rgba(0,116,217,0.03); }
.bv-nivel--4 { border-color: #10b981; background: rgba(16,185,129,0.03); }
.bv-nivel--5 { border-color: #94a3b8; background: rgba(148,163,184,0.03); }

.bv-importance-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.6rem;
}

.bv-nivel-badge {
  font-size: 0.65rem;
  font-weight: 700;
  color: white;
  padding: 0.2rem 0.5rem;
  border-radius: 8px;
}

.bv-nivel--1 .bv-nivel-badge { background: #ef4444; }
.bv-nivel--2 .bv-nivel-badge { background: #f59e0b; }
.bv-nivel--3 .bv-nivel-badge { background: #0074D9; }
.bv-nivel--4 .bv-nivel-badge { background: #10b981; }
.bv-nivel--5 .bv-nivel-badge { background: #94a3b8; }

.bv-nivel-label {
  font-size: 0.7rem;
  color: #64748b;
  font-weight: 600;
}

.bv-importance-value {
  font-size: 1.6rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.bv-importance-bar {
  height: 6px;
  background: rgba(0,0,0,0.06);
  border-radius: 3px;
  overflow: hidden;
}

.bv-importance-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.bv-nivel--1 .bv-importance-fill { background: linear-gradient(90deg, #ef4444, #dc2626); }
.bv-nivel--2 .bv-importance-fill { background: linear-gradient(90deg, #f59e0b, #d97706); }
.bv-nivel--3 .bv-importance-fill { background: linear-gradient(90deg, #0074D9, #0056a6); }
.bv-nivel--4 .bv-importance-fill { background: linear-gradient(90deg, #10b981, #059669); }
.bv-nivel--5 .bv-importance-fill { background: linear-gradient(90deg, #94a3b8, #64748b); }

/* --- Charts row --- */
.bv-charts-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

/* --- Ranking list (municipios, departamentos) --- */
.bv-ranking-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.bv-ranking-item {
  display: grid;
  grid-template-columns: 32px 1fr auto 100px;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0.875rem;
  background: #f8fafc;
  border-radius: 10px;
  transition: all 0.25s ease;
}

.bv-ranking-item:hover {
  background: #f1f5f9;
  transform: translateX(3px);
}

.bv-rank {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.75rem;
  background: #e2e8f0;
  color: #475569;
}

.bv-rank--1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; box-shadow: 0 2px 8px rgba(245,158,11,0.3); }
.bv-rank--2 { background: linear-gradient(135deg, #94a3b8, #64748b); color: white; }
.bv-rank--3 { background: linear-gradient(135deg, #fb923c, #ea580c); color: white; }

.bv-ranking-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.85rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bv-ranking-count {
  background: linear-gradient(135deg, #0074D9, #0056a6);
  color: white;
  padding: 0.25rem 0.6rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.75rem;
  text-align: center;
  min-width: 36px;
}

.bv-ranking-bar {
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.bv-ranking-fill {
  height: 100%;
  background: linear-gradient(90deg, #0074D9, #0056a6);
  border-radius: 3px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

/* --- Trend chart --- */
.bv-trend {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
  height: 200px;
  padding: 0.75rem 0;
}

.bv-trend-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  height: 100%;
}

.bv-trend-value {
  font-size: 0.75rem;
  font-weight: 700;
  color: #0074D9;
}

.bv-trend-bar-wrap {
  flex: 1;
  width: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.bv-trend-bar {
  width: 60%;
  background: linear-gradient(180deg, #0074D9, rgba(0,116,217,0.15));
  border-radius: 8px 8px 0 0;
  min-height: 4px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.bv-trend-col:hover .bv-trend-bar {
  width: 78%;
  background: linear-gradient(180deg, #0074D9, #0056a6);
  box-shadow: 0 -4px 16px rgba(0,116,217,0.25);
}

.bv-trend-label {
  font-size: 0.7rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

/* --- Petitions list --- */
.bv-petitions-list {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.bv-petition {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.15rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border-left: 4px solid #cbd5e1;
}

.bv-petition:hover {
  background: #f1f5f9;
  transform: translateX(4px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}

.bv-petition.bv-nivel--1 { border-left-color: #ef4444; }
.bv-petition.bv-nivel--2 { border-left-color: #f59e0b; }
.bv-petition.bv-nivel--3 { border-left-color: #0074D9; }

.bv-petition--late {
  background: linear-gradient(135deg, #fef2f2, #fff1f2);
}

.bv-petition-top {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.6rem;
  flex-wrap: wrap;
}

.bv-petition-folio {
  font-weight: 700;
  color: #1e293b;
  font-size: 0.9rem;
}

.bv-petition-nivel {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 8px;
  background: #eff6ff;
  color: #0074D9;
}

.bv-nivel--1 .bv-petition-nivel { background: #fef2f2; color: #ef4444; }
.bv-nivel--2 .bv-petition-nivel { background: #fffbeb; color: #f59e0b; }

.bv-petition-days {
  margin-left: auto;
  font-size: 0.775rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.bv-petition-days--late {
  background: #fef2f2;
  color: #ef4444;
  padding: 0.2rem 0.55rem;
  border-radius: 8px;
  font-weight: 600;
}

.bv-petition-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.875rem;
  margin-bottom: 0.3rem;
}

.bv-petition-desc {
  font-size: 0.8rem;
  color: #64748b;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 0.6rem;
}

.bv-petition-images {
  margin: 0.5rem 0;
  border-radius: 10px;
  overflow: hidden;
  height: 180px;
  max-height: 180px;
  position: relative;
}

.bv-petition-images :deep(.image-gallery),
.bv-petition-images :deep(.gallery-carousel),
.bv-petition-images :deep(.carousel-container) {
  height: 100%;
  max-height: 180px;
}

.bv-petition-images :deep(.carousel-slide) {
  height: 155px;
  max-height: 155px;
  background: #f1f5f9;
  overflow: hidden;
}

.bv-petition-images :deep(.carousel-image) {
  max-height: 155px;
  width: auto;
  max-width: 100%;
  object-fit: contain;
}

.bv-petition-images :deep(.carousel-btn) {
  width: 28px;
  height: 28px;
  font-size: 0.7rem;
}

.bv-petition-images :deep(.carousel-indicators) {
  gap: 4px;
  margin-top: 4px;
}

.bv-petition-images :deep(.indicator) {
  width: 7px;
  height: 7px;
}

.bv-petition-images :deep(.gallery-empty) {
  padding: 0.75rem;
  font-size: 0.8rem;
  height: 100%;
}

.bv-petition-images :deep(.gallery-empty .empty-icon) {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.bv-petition-images :deep(.gallery-empty h4) {
  font-size: 0.8rem;
  margin: 0;
}

.bv-petition-images :deep(.gallery-empty p) {
  display: none;
}

.bv-petition-images :deep(.gallery-loading) {
  padding: 0.5rem;
  height: 100%;
}

.bv-petition-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 0.6rem;
  border-top: 1px solid #e2e8f0;
}

.bv-petition-mun {
  font-size: 0.775rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.bv-petition-mun i {
  color: #0074D9;
}

.bv-petition-status {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 8px;
  background: #f1f5f9;
  color: #475569;
}

/* --- Petition carousel (Swiper) --- */
.bv-card--carousel {
  padding-bottom: 2.5rem;
}

.bv-card-count {
  margin-left: auto;
  background: #eff6ff;
  color: #0074D9;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 8px;
}

.bv-swiper {
  padding-bottom: 2rem;
}

.bv-swiper :deep(.swiper-pagination) {
  bottom: 0;
}

.bv-swiper :deep(.swiper-pagination-bullet) {
  background: #0074D9;
  opacity: 0.3;
  width: 8px;
  height: 8px;
}

.bv-swiper :deep(.swiper-pagination-bullet-active) {
  opacity: 1;
  width: 20px;
  border-radius: 4px;
}

.bv-swiper :deep(.swiper-button-prev),
.bv-swiper :deep(.swiper-button-next) {
  width: 34px;
  height: 34px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  top: calc(50% - 1rem);
}

.bv-swiper :deep(.swiper-button-prev)::after,
.bv-swiper :deep(.swiper-button-next)::after {
  font-size: 0.75rem;
  font-weight: 700;
  color: #0074D9;
}

.bv-swiper :deep(.swiper-button-prev) {
  left: 4px;
}

.bv-swiper :deep(.swiper-button-next) {
  right: 4px;
}

.bv-petition-card {
  background: #f8fafc;
  border-radius: 14px;
  padding: 1.15rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border-top: 3px solid #cbd5e1;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.bv-petition-card:hover {
  background: #f1f5f9;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.bv-petition-card.bv-nivel--1 { border-top-color: #ef4444; }
.bv-petition-card.bv-nivel--2 { border-top-color: #f59e0b; }
.bv-petition-card.bv-nivel--3 { border-top-color: #0074D9; }
.bv-petition-card.bv-nivel--4 { border-top-color: #10b981; }
.bv-petition-card.bv-nivel--5 { border-top-color: #94a3b8; }

.bv-petition-card.bv-petition--late {
  background: linear-gradient(135deg, #fef2f2, #fff1f2);
}

.bv-petition-card .bv-petition-desc {
  flex: 1;
}

.bv-petition-card .bv-petition-bottom {
  margin-top: auto;
}

/* --- Empty state --- */
.bv-empty {
  text-align: center;
  padding: 3.5rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.bv-empty i {
  font-size: 3rem;
  color: #cbd5e1;
  margin-bottom: 1rem;
}

.bv-empty h3 {
  font-size: 1.15rem;
  color: #475569;
  margin: 0 0 0.5rem;
  font-weight: 700;
}

.bv-empty p {
  font-size: 0.875rem;
  color: #94a3b8;
  margin: 0;
}

/* --- Responsive --- */
@media (max-width: 768px) {
  .bv-page {
    padding: 0.75rem;
  }

  .bv-hero-content {
    flex-direction: column;
    align-items: stretch;
    padding: 1.25rem 1.25rem;
    gap: 0.75rem;
  }

  .bv-name {
    font-size: 1.2rem;
    white-space: normal;
  }

  .bv-refresh-btn {
    align-self: flex-end;
  }

  .bv-states-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .bv-charts-row {
    grid-template-columns: 1fr;
  }

  .bv-shortcuts-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .bv-shortcut {
    padding: 1rem 0.5rem;
  }

  .bv-ranking-item {
    grid-template-columns: 32px 1fr auto;
  }

  .bv-ranking-bar {
    display: none;
  }

  .bv-trend {
    height: 150px;
  }
}

@media (max-width: 480px) {
  .bv-states-grid {
    grid-template-columns: 1fr;
  }

  .bv-shortcuts-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .bv-importance-grid {
    grid-template-columns: 1fr;
  }

  .bv-metrics-row {
    grid-template-columns: 1fr;
  }

  .bv-hero-content {
    padding: 1rem;
  }

  .bv-avatar {
    width: 48px;
    height: 48px;
    font-size: 1.15rem;
  }
}
</style>
