<template>
  <div class="bg-white border-2 border-blue-600 shadow-lg shadow-blue-100 p-6 rounded">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-bold text-blue-900">Reportes Recientes</h3>

      <div class="flex items-center gap-3">
        <input
          type="text"
          placeholder="Buscar..."
          v-model="searchTerm"
          class="px-4 py-2 border-2 border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />

        <select
          v-model="filterStatus"
          class="px-4 py-2 border-2 border-blue-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="Todos">Todos</option>
          <option value="Pendiente">Pendiente</option>
          <option value="En Proceso">En Proceso</option>
          <option value="Atendido">Atendido</option>
          <option value="Rechazado">Rechazado</option>
        </select>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="bg-blue-700 text-white">
            <th class="px-4 py-3 text-left font-semibold">ID</th>
            <th class="px-4 py-3 text-left font-semibold">Municipio</th>
            <th class="px-4 py-3 text-left font-semibold">Categoría</th>
            <th class="px-4 py-3 text-left font-semibold">Estado</th>
            <th class="px-4 py-3 text-left font-semibold">Fecha</th>
            <th class="px-4 py-3 text-left font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="paginated.length === 0">
            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No se encontraron reportes</td>
          </tr>

          <tr v-for="(report, index) in paginated" :key="report.id" :class="index % 2 === 0 ? 'bg-white' : 'bg-blue-50'">
            <td class="px-4 py-3 text-blue-900 font-semibold">#{{ report.id }}</td>
            <td class="px-4 py-3 text-blue-900">{{ report.municipality }}</td>
            <td class="px-4 py-3 text-blue-900">{{ report.category }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded" :style="getStatusStyle(report.status)">{{ report.status }}</span>
            </td>
            <td class="px-4 py-3 text-blue-900">{{ report.date }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded">
                  <!-- Eye icon -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded">
                  <!-- Edit icon -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h6M6 21l9-9 3 3-9 9H6v-3z" />
                  </svg>
                </button>
                <button class="px-2 py-1 bg-red-700 hover:bg-red-800 text-white rounded">
                  <!-- Trash icon -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V4a1 1 0 011-1h6a1 1 0 011 1v3" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between mt-6">
      <p class="text-sm text-blue-600">
        <span v-if="filtered.length === 0">Mostrando 0 reportes</span>
        <span v-else>Mostrando {{ startIndex + 1 }} - {{ Math.min(startIndex + itemsPerPage, filtered.length) }} de {{ filtered.length }} reportes</span>
      </p>

      <div class="flex items-center gap-2">
        <button @click="prevPage" :disabled="currentPage === 1" class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded disabled:opacity-50">
          <!-- Chevron Left -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <span class="text-sm text-blue-900 font-semibold px-3">Página {{ currentPage }} de {{ totalPages }}</span>

        <button @click="nextPage" :disabled="currentPage === totalPages" class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded disabled:opacity-50">
          <!-- Chevron Right -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  reports: { type: Array, default: () => [] }
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

function getStatusStyle(status) {
  // Palette aligned with charts: Pendiente (light blue), En Proceso (blue), Atendido (dark blue), Rechazado (red)
  const palette = {
    Pendiente: { bg: '#93C5FD', color: '#1E3A8A' },
    'En Proceso': { bg: '#3B82F6', color: '#ffffff' },
    Atendido: { bg: '#1E40AF', color: '#ffffff' },
    Rechazado: { bg: '#DC2626', color: '#ffffff' }
  }
  const p = palette[status] || { bg: '#60A5FA', color: '#07204A' }
  return { backgroundColor: p.bg, color: p.color, fontWeight: 600 }
}

function prevPage() {
  currentPage.value = Math.max(1, currentPage.value - 1)
}

function nextPage() {
  currentPage.value = Math.min(totalPages.value, currentPage.value + 1)
}
</script>
