<template>
  <div class="municipios-wrapper">
    <div class="municipios-header">
      <h2 class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
          <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        Municipios de Yucatán
      </h2>
      <div class="filter-buttons">
        <button
          v-for="region in regiones"
          :key="region.id"
          :class="['filter-btn', { active: filtroActivo === region.id }]"
          @click="filtrarPorRegion(region.id)"
        >
          {{ region.nombre }}
        </button>
      </div>
    </div>

    <div class="municipios-stats">
      <div class="stat-card">
        <div class="stat-icon">🏛️</div>
        <div class="stat-content">
          <div class="stat-value">{{ municipiosFiltrados.length }}</div>
          <div class="stat-label">Municipios</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-content">
          <div class="stat-value">{{ totalReportes }}</div>
          <div class="stat-label">Reportes Totales</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-content">
          <div class="stat-value">{{ reportesPendientes }}</div>
          <div class="stat-label">Pendientes</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-content">
          <div class="stat-value">{{ reportesAtendidos }}</div>
          <div class="stat-label">Atendidos</div>
        </div>
      </div>
    </div>

    <div class="municipios-grid">
      <div
        v-for="municipio in municipiosFiltrados"
        :key="municipio.id"
        class="municipio-card"
        @click="seleccionarMunicipio(municipio)"
      >
        <div class="municipio-header">
          <h3 class="municipio-nombre">{{ municipio.nombre }}</h3>
          <span class="municipio-region">{{ municipio.region }}</span>
        </div>
        <div class="municipio-info">
          <div class="info-item">
            <span class="info-label">Población:</span>
            <span class="info-value">{{ formatNumber(municipio.poblacion) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Reportes:</span>
            <span class="info-value reportes-count">{{ municipio.reportes }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Estado:</span>
            <span :class="['status-badge', municipio.estado]">
              {{ estadoTexto(municipio.estado) }}
            </span>
          </div>
        </div>
        <div class="municipio-footer">
          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: `${(municipio.atendidos / municipio.reportes * 100)}%` }"
            ></div>
          </div>
          <span class="progress-text">
            {{ municipio.atendidos }}/{{ municipio.reportes }} atendidos
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "MunicipiosYucatan",
  data() {
    return {
      filtroActivo: 'todos',
      regiones: [
        { id: 'todos', nombre: 'Todos' },
        { id: 'poniente', nombre: 'Poniente' },
        { id: 'litoral-centro', nombre: 'Litoral Centro' },
        { id: 'centro', nombre: 'Centro' },
        { id: 'sur', nombre: 'Sur' },
        { id: 'oriente', nombre: 'Oriente' }
      ],
      municipios: [
        // Municipios principales de cada región (muestra representativa)
        { id: 1, nombre: 'Mérida', region: 'Centro', poblacion: 921770, reportes: 324, atendidos: 287, estado: 'activo' },
        { id: 2, nombre: 'Kanasín', region: 'Centro', poblacion: 139753, reportes: 89, atendidos: 76, estado: 'activo' },
        { id: 3, nombre: 'Umán', region: 'Centro', poblacion: 71096, reportes: 45, atendidos: 39, estado: 'activo' },
        { id: 4, nombre: 'Progreso', region: 'Litoral Centro', poblacion: 53958, reportes: 67, atendidos: 61, estado: 'activo' },
        { id: 5, nombre: 'Valladolid', region: 'Oriente', poblacion: 85357, reportes: 52, atendidos: 44, estado: 'activo' },
        { id: 6, nombre: 'Tizimín', region: 'Oriente', poblacion: 74863, reportes: 48, atendidos: 41, estado: 'activo' },
        { id: 7, nombre: 'Motul', region: 'Litoral Centro', poblacion: 36046, reportes: 31, atendidos: 28, estado: 'normal' },
        { id: 8, nombre: 'Ticul', region: 'Sur', poblacion: 40161, reportes: 38, atendidos: 32, estado: 'activo' },
        { id: 9, nombre: 'Oxkutzcab', region: 'Sur', poblacion: 30731, reportes: 29, atendidos: 24, estado: 'normal' },
        { id: 10, nombre: 'Tekax', region: 'Sur', poblacion: 39451, reportes: 35, atendidos: 30, estado: 'normal' },
        { id: 11, nombre: 'Hunucmá', region: 'Poniente', poblacion: 33527, reportes: 27, atendidos: 23, estado: 'normal' },
        { id: 12, nombre: 'Maxcanú', region: 'Poniente', poblacion: 23051, reportes: 22, atendidos: 19, estado: 'normal' },
        { id: 13, nombre: 'Conkal', region: 'Centro', poblacion: 12987, reportes: 18, atendidos: 16, estado: 'normal' },
        { id: 14, nombre: 'Tixkokob', region: 'Centro', poblacion: 22716, reportes: 21, atendidos: 18, estado: 'normal' },
        { id: 15, nombre: 'Izamal', region: 'Litoral Centro', poblacion: 28216, reportes: 26, atendidos: 22, estado: 'normal' },
        { id: 16, nombre: 'Temozón', region: 'Oriente', poblacion: 13916, reportes: 15, atendidos: 13, estado: 'bajo' },
        { id: 17, nombre: 'Peto', region: 'Sur', poblacion: 25264, reportes: 23, atendidos: 20, estado: 'normal' },
        { id: 18, nombre: 'Celestún', region: 'Poniente', poblacion: 7591, reportes: 12, atendidos: 11, estado: 'bajo' },
        { id: 19, nombre: 'Dzilam González', region: 'Litoral Centro', poblacion: 5538, reportes: 8, atendidos: 7, estado: 'bajo' },
        { id: 20, nombre: 'Río Lagartos', region: 'Oriente', poblacion: 3932, reportes: 6, atendidos: 5, estado: 'bajo' }
      ]
    }
  },
  computed: {
    municipiosFiltrados() {
      if (this.filtroActivo === 'todos') {
        return this.municipios
      }
      return this.municipios.filter(m => m.region === this.regionNombre(this.filtroActivo))
    },
    totalReportes() {
      return this.municipiosFiltrados.reduce((sum, m) => sum + m.reportes, 0)
    },
    reportesPendientes() {
      return this.municipiosFiltrados.reduce((sum, m) => sum + (m.reportes - m.atendidos), 0)
    },
    reportesAtendidos() {
      return this.municipiosFiltrados.reduce((sum, m) => sum + m.atendidos, 0)
    }
  },
  methods: {
    filtrarPorRegion(regionId) {
      this.filtroActivo = regionId
    },
    regionNombre(id) {
      const region = this.regiones.find(r => r.id === id)
      return region ? region.nombre : ''
    },
    formatNumber(num) {
      return num.toLocaleString('es-MX')
    },
    estadoTexto(estado) {
      const estados = {
        'activo': 'Alta demanda',
        'normal': 'Normal',
        'bajo': 'Baja demanda'
      }
      return estados[estado] || estado
    },
    seleccionarMunicipio(municipio) {
      console.log('Municipio seleccionado:', municipio)
      // Aquí se podría abrir un modal o navegar a una vista detallada
    }
  }
}
</script>

<style scoped>
.municipios-wrapper {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  margin: 2rem auto;
  max-width: 1400px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.municipios-header {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 1.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.section-title svg {
  color: #006341;
}

.filter-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  border-color: #006341;
  color: #006341;
}

.filter-btn.active {
  background: #006341;
  border-color: #006341;
  color: white;
}

.municipios-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #006341;
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.municipios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
}

.municipio-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.municipio-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
  border-color: #006341;
}

.municipio-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #f1f5f9;
}

.municipio-nombre {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.municipio-region {
  font-size: 0.75rem;
  padding: 0.25rem 0.625rem;
  background: #f1f5f9;
  color: #64748b;
  border-radius: 6px;
  font-weight: 600;
}

.municipio-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.info-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

.reportes-count {
  color: #0074D9;
  font-weight: 700;
}

.status-badge {
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.activo {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.normal {
  background: #dbeafe;
  color: #2563eb;
}

.status-badge.bajo {
  background: #d1fae5;
  color: #059669;
}

.municipio-footer {
  margin-top: 1rem;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #006341 0%, #059669 100%);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.75rem;
  color: #64748b;
}

@media (max-width: 768px) {
  .municipios-grid {
    grid-template-columns: 1fr;
  }

  .municipios-stats {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
