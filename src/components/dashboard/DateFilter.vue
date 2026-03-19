<template>
  <div class="date-filter">
    <div class="date-filter__presets">
      <button v-for="p in presets" :key="p.key"
        class="date-filter__btn"
        :class="{ 'date-filter__btn--active': activePreset === p.key }"
        @click="applyPreset(p)">
        {{ p.label }}
      </button>
    </div>
    <div class="date-filter__custom">
      <div class="date-filter__field">
        <label>Desde</label>
        <input type="date" v-model="fechaInicio" @change="applyCustom" :max="fechaFin || today" />
      </div>
      <div class="date-filter__field">
        <label>Hasta</label>
        <input type="date" v-model="fechaFin" @change="applyCustom" :min="fechaInicio" :max="today" />
      </div>
    </div>
    <div v-if="activeLabel" class="date-filter__active">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
      <span>{{ activeLabel }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDashboardStore } from '@/composables/useDashboardStore.js'

const { filtroFechaInicio, filtroFechaFin, filtroDias, setFiltro, fetchDashboard } = useDashboardStore()

const today = new Date().toISOString().slice(0, 10)
const activePreset = ref('all')
const fechaInicio = ref('')
const fechaFin = ref('')

const presets = [
  { key: 'all', label: 'Todo', dias: 9999 },
  { key: '7d', label: '7 dias', dias: 7 },
  { key: '30d', label: '30 dias', dias: 30 },
  { key: '90d', label: '3 meses', dias: 90 },
  { key: '180d', label: '6 meses', dias: 180 },
  { key: '365d', label: '1 año', dias: 365 }
]

function applyPreset(p) {
  activePreset.value = p.key
  fechaInicio.value = ''
  fechaFin.value = ''
  setFiltro('filtroFechaInicio', null)
  setFiltro('filtroFechaFin', null)
  setFiltro('filtroDias', p.dias)
  fetchDashboard()
}

function applyCustom() {
  if (!fechaInicio.value && !fechaFin.value) return
  activePreset.value = 'custom'
  if (fechaInicio.value) setFiltro('filtroFechaInicio', fechaInicio.value)
  if (fechaFin.value) setFiltro('filtroFechaFin', fechaFin.value)
  // Si solo hay inicio, quitar filtroDias para que no interfiera
  if (fechaInicio.value || fechaFin.value) {
    setFiltro('filtroDias', 9999)
  }
  fetchDashboard()
}

const activeLabel = computed(() => {
  if (activePreset.value === 'custom') {
    const parts = []
    if (fechaInicio.value) parts.push('desde ' + fechaInicio.value)
    if (fechaFin.value) parts.push('hasta ' + fechaFin.value)
    return parts.join(' ')
  }
  const p = presets.find(x => x.key === activePreset.value)
  if (p && p.key !== 'all') return 'Ultimos ' + p.label
  return ''
})

onMounted(() => {
  // Sync from store if already set
  if (filtroFechaInicio.value) {
    fechaInicio.value = filtroFechaInicio.value
    activePreset.value = 'custom'
  }
  if (filtroFechaFin.value) {
    fechaFin.value = filtroFechaFin.value
    activePreset.value = 'custom'
  }
})
</script>

<style scoped>
.date-filter {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
  max-width: 1400px;
  margin: 0 auto;
  flex-wrap: wrap;
}

.date-filter__presets {
  display: flex;
  gap: 4px;
  background: #f1f5f9;
  border-radius: 10px;
  padding: 3px;
}

.date-filter__btn {
  padding: 6px 14px;
  border: none;
  border-radius: 8px;
  background: transparent;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
  white-space: nowrap;
}

.date-filter__btn:hover {
  color: #1e293b;
  background: rgba(255, 255, 255, 0.6);
}

.date-filter__btn--active {
  background: white;
  color: #1e40af;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.date-filter__custom {
  display: flex;
  align-items: center;
  gap: 8px;
}

.date-filter__field {
  display: flex;
  align-items: center;
  gap: 5px;
}

.date-filter__field label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.date-filter__field input {
  padding: 5px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.78rem;
  color: #1e293b;
  background: white;
  outline: none;
  font-family: inherit;
  transition: border-color 0.15s;
}

.date-filter__field input:focus {
  border-color: #3b82f6;
}

.date-filter__active {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-left: auto;
  font-size: 0.75rem;
  font-weight: 600;
  color: #3b82f6;
  background: #eff6ff;
  padding: 4px 12px;
  border-radius: 8px;
}

/* Dark mode */
:global(.dark-mode) .date-filter__presets { background: #1e293b; }
:global(.dark-mode) .date-filter__btn { color: #94a3b8; }
:global(.dark-mode) .date-filter__btn:hover { color: #e2e8f0; background: rgba(255,255,255,0.05); }
:global(.dark-mode) .date-filter__btn--active { background: #334155; color: #93c5fd; }
:global(.dark-mode) .date-filter__field label { color: #64748b; }
:global(.dark-mode) .date-filter__field input { background: #1e293b; border-color: #334155; color: #e2e8f0; }
:global(.dark-mode) .date-filter__field input:focus { border-color: #3b82f6; }
:global(.dark-mode) .date-filter__active { background: #1e3a5f; color: #93c5fd; }

@media (max-width: 768px) {
  .date-filter {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  .date-filter__presets {
    overflow-x: auto;
  }
  .date-filter__active {
    margin-left: 0;
  }
}
</style>
