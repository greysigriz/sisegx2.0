<template>
  <div class="reports-wrapper">
    <Card>
      <div class="header">
        <h3 class="title">Reportes Recientes</h3>

      <div class="filters">
        <input
          type="text"
          placeholder="Buscar..."
          v-model="searchTerm"
          class="search-input"
        />

        <select v-model="filterStatus" class="status-select">
          <option value="Todos">Todos</option>
          <option value="Pendiente">Pendiente</option>
          <option value="En Proceso">En Proceso</option>
          <option value="Atendido">Atendido</option>
          <option value="Rechazado">Rechazado</option>
        </select>
      </div>
    </div>

    <div class="table-container">
      <table class="reports-table">
        <thead>
          <tr class="table-header">
            <th class="th">ID</th>
            <th class="th">Municipio</th>
            <th class="th">Categoría</th>
            <th class="th">Estado</th>
            <th class="th">Fecha</th>
            <th class="th">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="paginated.length === 0">
            <td colspan="6" class="empty-message">No se encontraron reportes</td>
          </tr>

          <tr
            v-for="(report, index) in paginated"
            :key="report.id"
            :class="['table-row', index % 2 === 0 ? 'row-white' : 'row-blue']"
          >
            <td class="td td-id">#{{ report.id }}</td>
            <td class="td">{{ report.municipality }}</td>
            <td class="td">{{ report.category }}</td>
            <td class="td">
              <Badge :class="getStatusBadgeClass(report.status)">
                {{ report.status }}
              </Badge>
            </td>
            <td class="td">{{ report.date }}</td>
            <td class="td">
              <div class="actions">
                <Button class="btn btn-view">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </Button>
                <Button class="btn btn-edit">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h6M6 21l9-9 3 3-9 9H6v-3z" />
                  </svg>
                </Button>
                <Button class="btn btn-delete">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V4a1 1 0 011-1h6a1 1 0 011 1v3" />
                  </svg>
                </Button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="pagination">
      <p class="pagination-info">
        <span v-if="filtered.length === 0">Mostrando 0 reportes</span>
        <span v-else>
          Mostrando {{ startIndex + 1 }} - {{ Math.min(startIndex + itemsPerPage, filtered.length) }} de {{ filtered.length }} reportes
        </span>
      </p>

      <div class="pagination-controls">
        <Button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="btn btn-pagination"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </Button>

        <span class="page-number">
          Página {{ currentPage }} de {{ totalPages }}
        </span>

        <Button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="btn btn-pagination"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </Button>
      </div>
    </div>
  </Card>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import '@/assets/css/tablareportes_dasboard.css'

const props = defineProps({
  reports: {
    type: Array,
    default: () => [
      { id: 64, municipality: 'Valladolid', category: 'Baches', status: 'Rechazado', date: '2026-01-14' },
      { id: 116, municipality: 'Kanasín', category: 'Limpieza', status: 'Atendido', date: '2026-01-13' },
      { id: 63, municipality: 'Tekax', category: 'Limpieza', status: 'Pendiente', date: '2026-01-12' },
      { id: 71, municipality: 'Mérida', category: 'Alumbrado', status: 'En Proceso', date: '2026-01-12' },
      { id: 105, municipality: 'Tizimín', category: 'Alumbrado', status: 'Pendiente', date: '2026-01-12' },
      { id: 126, municipality: 'Tixkokob', category: 'Limpieza', status: 'Atendido', date: '2026-01-12' },
      { id: 129, municipality: 'Progreso', category: 'Baches', status: 'Rechazado', date: '2026-01-12' },
      { id: 52, municipality: 'Mérida', category: 'Alumbrado', status: 'Rechazado', date: '2026-01-11' },
      { id: 155, municipality: 'Umán', category: 'Limpieza', status: 'Atendido', date: '2026-01-11' },
      { id: 62, municipality: 'Ticul', category: 'Seguridad', status: 'Atendido', date: '2026-01-09' }
    ]
  }
})

const currentPage = ref(1)
const searchTerm = ref('')
const filterStatus = ref('Todos')
const itemsPerPage = 10

const filtered = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()
  return props.reports.filter((report) => {
    const matchesSearch =
      report.id.toString().includes(term) ||
      (report.municipality || '').toLowerCase().includes(term) ||
      (report.category || '').toLowerCase().includes(term)

    const matchesStatus = filterStatus.value === 'Todos' || report.status === filterStatus.value

    return matchesSearch && matchesStatus
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / itemsPerPage)))

watch([searchTerm, filterStatus], () => {
  currentPage.value = 1
})

watch(totalPages, (v) => {
  if (currentPage.value > v) currentPage.value = v
})

const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage)

const paginated = computed(() => filtered.value.slice(startIndex.value, startIndex.value + itemsPerPage))

function getStatusBadgeClass(status) {
  const classes = {
    Pendiente: 'status-pendiente',
    'En Proceso': 'status-proceso',
    Atendido: 'status-atendido',
    Rechazado: 'status-rechazado'
  }
  return classes[status] || 'status-default'
}

function prevPage() {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

function nextPage() {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}
</script>

