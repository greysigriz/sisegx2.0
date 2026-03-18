<template>
  <div class="dashboard-filters">
    <div class="filters-row">
      <!-- Rango de tiempo -->
      <div class="filter-group">
        <label class="filter-label">Periodo</label>
        <div class="filter-chips">
          <button
            v-for="opt in periodoOpciones"
            :key="opt.value"
            class="chip"
            :class="{ active: periodoActivo === opt.value }"
            @click="seleccionarPeriodo(opt.value)"
          >
            {{ opt.label }}
          </button>
        </div>
      </div>

      <!-- Municipio -->
      <div class="filter-group">
        <label class="filter-label">Municipio</label>
        <select v-model="municipioLocal" class="filter-select" @change="aplicarFiltro('filtroMunicipio', municipioLocal)">
          <option :value="null">Todos</option>
          <option v-for="m in municipiosList" :key="m.Id" :value="m.Id">{{ m.Municipio }}</option>
        </select>
      </div>

      <!-- Estado -->
      <div class="filter-group">
        <label class="filter-label">Estado</label>
        <select v-model="estadoLocal" class="filter-select" @change="aplicarFiltro('filtroEstado', estadoLocal)">
          <option :value="null">Todos</option>
          <option v-for="e in estados" :key="e" :value="e">{{ e }}</option>
        </select>
      </div>

      <!-- Importancia -->
      <div class="filter-group">
        <label class="filter-label">Prioridad</label>
        <select v-model="importanciaLocal" class="filter-select" @change="aplicarFiltro('filtroImportancia', importanciaLocal)">
          <option :value="null">Todas</option>
          <option :value="1">Critica</option>
          <option :value="2">Alta</option>
          <option :value="3">Media</option>
          <option :value="4">Baja</option>
          <option :value="5">Muy Baja</option>
        </select>
      </div>

      <!-- Reset -->
      <div class="filter-group filter-actions">
        <button class="reset-btn" @click="limpiarFiltros" title="Limpiar filtros">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
            <path d="M3 3v5h5"/>
          </svg>
          Limpiar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useDashboardStore } from '@/composables/useDashboardStore.js'

const {
  municipiosList,
  filtroMunicipio,
  filtroEstado,
  filtroImportancia,
  filtroDias,
  setFiltro,
  resetFiltros,
  fetchDashboard
} = useDashboardStore()

const municipioLocal = ref(filtroMunicipio.value)
const estadoLocal = ref(filtroEstado.value)
const importanciaLocal = ref(filtroImportancia.value)
const periodoActivo = ref(filtroDias.value)

const periodoOpciones = [
  { label: '7d', value: 7 },
  { label: '30d', value: 30 },
  { label: '90d', value: 90 },
  { label: '1a', value: 365 },
  { label: 'Todo', value: 9999 }
]

const estados = [
  'Sin revisar',
  'Por asignar departamento',
  'Esperando recepción',
  'Aceptada en proceso',
  'Completado',
  'Devuelto',
  'Rechazado por departamento',
  'Improcedente',
  'Cancelada'
]

function seleccionarPeriodo(dias) {
  periodoActivo.value = dias
  setFiltro('filtroDias', dias)
  fetchDashboard()
}

function aplicarFiltro(key, value) {
  setFiltro(key, value || null)
  fetchDashboard()
}

function limpiarFiltros() {
  municipioLocal.value = null
  estadoLocal.value = null
  importanciaLocal.value = null
  periodoActivo.value = 365
  resetFiltros()
  fetchDashboard()
}

// Sync external changes
watch(filtroMunicipio, v => { municipioLocal.value = v })
watch(filtroEstado, v => { estadoLocal.value = v })
watch(filtroImportancia, v => { importanciaLocal.value = v })
</script>

<style scoped>
.dashboard-filters {
  background: white;
  border-radius: 14px;
  padding: 1rem 1.5rem;
  margin: 1rem auto;
  max-width: 1600px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
}

.filters-row {
  display: flex;
  align-items: flex-end;
  gap: 1.25rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.filter-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.filter-chips {
  display: flex;
  gap: 4px;
}

.chip {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.chip:hover {
  background: #eff6ff;
  border-color: #93c5fd;
  color: #1e40af;
}

.chip.active {
  background: #1e40af;
  border-color: #1e40af;
  color: white;
}

.filter-select {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f8fafc;
  color: #1e293b;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  min-width: 140px;
  outline: none;
  transition: border-color 0.2s;
}

.filter-select:focus {
  border-color: #3b82f6;
}

.filter-actions {
  margin-left: auto;
}

.reset-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.85rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.reset-btn:hover {
  background: #fef2f2;
  border-color: #fca5a5;
  color: #ef4444;
}

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-actions {
    margin-left: 0;
  }

  .filter-chips {
    flex-wrap: wrap;
  }
}
</style>
