<template>
  <Transition name="bv-slide-detail">
    <div v-if="visible" class="bv-drill-panel bv-fade-in" :class="{ 'bv-drill-panel--inline': inline }">
      <div v-if="loading" class="bv-drill-loading">
        <div class="bv-loading-spinner" style="width:32px;height:32px;border-width:2px;"></div>
        <span>{{ loadingMessage }}</span>
      </div>
      <template v-else-if="peticiones.length > 0">
        <div class="bv-drill-header">
          <span class="bv-drill-title">{{ title }}</span>
          <span class="bv-drill-count">
            {{ totalCount > peticiones.length ? `Mostrando ${peticiones.length} de ${totalCount}` : `${peticiones.length} peticiones` }}
          </span>
          <button class="bv-drill-csv" @click.stop="$emit('download-csv')" title="Descargar CSV">
            <i class="fas fa-download"></i> CSV
          </button>
          <button class="bv-drill-close" @click.stop="$emit('close')" title="Cerrar">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="bv-drill-table-wrap">
          <table class="bv-drill-table">
            <thead>
              <tr>
                <th>Folio</th><th>Peticionario</th><th>Descripción</th>
                <th>Municipio</th><th>Estado</th><th>Importancia</th><th>Días</th><th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pet in peticiones" :key="pet.id" @click="$emit('view-petition', pet.folio)" class="bv-drill-row">
                <td class="bv-td-folio">{{ pet.folio || '-' }}</td>
                <td>{{ pet.nombre || 'Anónimo' }}</td>
                <td class="bv-td-desc">{{ truncateText(pet.descripcion, 50) }}</td>
                <td>{{ pet.Municipio || '-' }}</td>
                <td><span class="bv-estado-badge" :class="getEstadoBadgeClass(pet.estado)">{{ pet.estado }}</span></td>
                <td><span class="bv-imp-badge" :class="getImpClass(pet.NivelImportancia)">{{ getImpLabel(pet.NivelImportancia) }}</span></td>
                <td class="bv-td-dias" :class="{ 'bv-td-dias--alerta': pet.dias_transcurridos > 30 }">{{ pet.dias_transcurridos }}d</td>
                <td class="bv-td-fecha">{{ formatFullDate(pet.fecha_registro) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Paginación -->
        <div v-if="totalPages > 1" class="bv-drill-pagination">
          <button :disabled="page <= 1" @click.stop="$emit('page-change', page - 1)" class="bv-drill-page-btn">
            <i class="fas fa-chevron-left"></i>
          </button>
          <span class="bv-drill-page-info">Página {{ page }} de {{ totalPages }}</span>
          <button :disabled="page >= totalPages" @click.stop="$emit('page-change', page + 1)" class="bv-drill-page-btn">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </template>
      <div v-else class="bv-drill-empty">{{ emptyMessage }}</div>
    </div>
  </Transition>
</template>

<script setup>
import { usePeticionUtils } from '@/composables/usePeticionUtils'

const { getImpLabel, getImpClass, getEstadoBadgeClass, truncateText, formatFullDate } = usePeticionUtils()

defineProps({
  visible: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  peticiones: { type: Array, default: () => [] },
  title: { type: String, default: '' },
  totalCount: { type: Number, default: 0 },
  page: { type: Number, default: 1 },
  totalPages: { type: Number, default: 1 },
  inline: { type: Boolean, default: false },
  emptyMessage: { type: String, default: 'No hay peticiones para esta categoría.' },
  loadingMessage: { type: String, default: 'Cargando peticiones...' }
})

defineEmits(['close', 'download-csv', 'view-petition', 'page-change'])
</script>

<style scoped>
.bv-drill-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px 0 4px;
}

.bv-drill-page-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
}

.bv-drill-page-btn:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #165CB1;
  color: #165CB1;
}

.bv-drill-page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.bv-drill-page-info {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}
</style>
