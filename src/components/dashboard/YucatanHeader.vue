<template>
  <header class="dash-header">
    <!-- Izquierda: marca -->
    <div class="dash-header__brand">
      <svg class="dash-header__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      <div class="dash-header__titles">
        <span class="dash-header__name">Reportes Ciudadanos</span>
        <span class="dash-header__sub">Yucatan</span>
      </div>
    </div>

    <!-- Centro: alertas como badges -->
    <div class="dash-header__alerts" v-if="alerts && alerts.length > 0">
      <div v-for="(alert, i) in alerts" :key="i"
        class="dash-alert-badge"
        :class="alert.type === 'critical' ? 'dash-alert--critical' : 'dash-alert--warning'">
        <span class="dash-alert-badge__count">{{ alert.count }}</span>
        <span class="dash-alert-badge__text">{{ shortMessage(alert.message) }}</span>
      </div>
    </div>
    <div v-else class="dash-header__alerts">
      <span class="dash-header__ok">Sin alertas</span>
    </div>

    <!-- Derecha: fecha + dark mode toggle -->
    <div class="dash-header__right">
      <span class="dash-header__date">{{ currentDate }}</span>
      <button class="dark-toggle" @click="toggleDark" :title="isDark ? 'Modo claro' : 'Modo oscuro'">
        <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
      </button>
    </div>
  </header>
</template>

<script>
import { computed, ref } from 'vue'
import { useDashboardStore } from '@/composables/useDashboardStore.js'

export default {
  name: "YucatanHeader",
  setup() {
    const { alerts } = useDashboardStore()

    const isDark = ref(localStorage.getItem('dashboard-dark') === '1')

    function toggleDark() {
      isDark.value = !isDark.value
      localStorage.setItem('dashboard-dark', isDark.value ? '1' : '0')
      document.documentElement.classList.toggle('dark-mode', isDark.value)
    }

    // Aplicar al montar
    if (isDark.value) {
      document.documentElement.classList.add('dark-mode')
    }

    const currentDate = computed(() => {
      const d = new Date()
      return d.toLocaleDateString('es-MX', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      })
    })

    function shortMessage(msg) {
      // Quitar el numero del texto (ya se muestra en el badge count)
      // "Hay 26 peticiones críticas pendientes" → "criticas pendientes"
      return msg
        .replace(/^Hay\s+/i, '')
        .replace(/^\d+\s+/, '')
        .replace('peticiones llevan más de 30 días sin resolver', 'retrasadas (+30d)')
        .replace('peticiones críticas pendientes', 'criticas pendientes')
        .replace('peticiones críticas pendientes', 'criticas pendientes')
        .replace('asignaciones a departamentos sin respuesta (+15 días)', 'deptos sin respuesta')
    }

    return { alerts, currentDate, shortMessage, isDark, toggleDark }
  }
}
</script>

<style scoped>
.dash-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.7rem 1.5rem;
  background: white;
  border-bottom: 2px solid #2563eb;
}

.dash-header__brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.dash-header__icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #2563eb;
}

.dash-header__titles {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
}

.dash-header__name {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e3a8a;
}

.dash-header__sub {
  font-size: 0.65rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* Alertas */
.dash-header__alerts {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  justify-content: center;
  flex-wrap: wrap;
}

.dash-header__ok {
  font-size: 0.78rem;
  color: #10b981;
  font-weight: 600;
}

.dash-alert-badge {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 600;
  white-space: nowrap;
}

.dash-alert--critical {
  background: #fef2f2;
  color: #991b1b;
}

.dash-alert--warning {
  background: #fffbeb;
  color: #92400e;
}

.dash-alert-badge__count {
  background: white;
  padding: 1px 6px;
  border-radius: 10px;
  font-weight: 800;
  font-size: 0.7rem;
}

.dash-alert-badge__text {
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Derecha */
.dash-header__right {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.dash-header__date {
  font-size: 0.78rem;
  color: #64748b;
  font-weight: 500;
}

.dark-toggle {
  padding: 6px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.dark-toggle:hover {
  background: #f1f5f9;
  color: #1e293b;
}

@media (max-width: 768px) {
  .dash-header {
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .dash-header__alerts {
    order: 3;
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
