<template>
  <div class="reportes-container">
    <div class="card">
      <div class="card-header">
        <h3>Registro de Solicitudes</h3>
      </div>
      <div class="card-body">
        <div class="table-container">
          <table class="solicitudes-table">
            <thead>
              <tr>
                <th>Fecha Solicitud</th>
                <th>Fecha estimada de resolución</th>
                <th>Fecha de Registro</th>
                <th>Período</th>
                <th>Folio</th>
                <th>Turno</th>
                <th>Estado</th>
                <th>Evento</th>
                <th>Solicitante</th>
                <th>Datos del Trámite</th>
                <th>Datos de la Solicitud</th>
                <th>Problemática</th>
                <th>Solución</th>
                <th>Dependencia Asignada</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(solicitud, index) in solicitudes" :key="index">
                <td>{{ formatDate(solicitud.fechaSolicitud) }}</td>
                <td>{{ formatDate(solicitud.fechaEstimada) }}</td>
                <td>{{ formatDate(solicitud.fechaRegistro) }}</td>
                <td>{{ solicitud.periodo }}</td>
                <td>{{ solicitud.folio }}</td>
                <td>{{ solicitud.turno }}</td>
                <td>
                  <span class="estado-badge" :class="getEstadoClass(solicitud.estado)">
                    {{ solicitud.estado }}
                  </span>
                </td>
                <td>{{ solicitud.evento }}</td>
                <td>{{ solicitud.solicitante }}</td>
                <td>{{ solicitud.datosTramite }}</td>
                <td>{{ solicitud.datosSolicitud }}</td>
                <td>{{ solicitud.problematica }}</td>
                <td>{{ solicitud.solucion }}</td>
                <td>{{ solicitud.dependenciaAsignada }}</td>
                <td>
                  <div class="acciones-container">
                    <button class="btn-accion btn-ver" @click="verDetalles(solicitud.id)">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-accion btn-editar" @click="editarSolicitud(solicitud.id)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-accion btn-eliminar" @click="confirmarEliminar(solicitud.id)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="pagination-container">
          <button class="pagination-btn" @click="prevPage" :disabled="currentPage === 1">
            <i class="fas fa-chevron-left"></i>
          </button>
          <span class="pagination-info">Página {{ currentPage }} de {{ totalPages }}</span>
          <button class="pagination-btn" @click="nextPage" :disabled="currentPage === totalPages">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Segunda tabla de trámites -->
    <div class="card mt-4">
      <div class="card-header">
        <h3>Lista de Trámites</h3>
      </div>
      <div class="card-body">
        <div class="tramites-list card-container">
          <table class="tramites-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Responsable</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tramite in tramites" :key="tramite.id">
                <td>{{ tramite.id }}</td>
                <td>{{ tramite.titulo }}</td>
                <td>{{ formatDate(tramite.fecha) }}</td>
                <td>
                  <span :class="'status-badge ' + tramite.estado">
                    {{ getStatusLabelTramite(tramite.estado) }}
                  </span>
                </td>
                <td>{{ tramite.responsable }}</td>
                <td class="actions-cell">
                  <button class="action-icon" @click="verDetallesTramite(tramite.id)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-icon" @click="editarTramite(tramite.id)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-icon" @click="confirmarEliminarTramite(tramite.id)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div class="pagination-container">
            <button class="pagination-btn" @click="prevPageTramites" :disabled="currentPageTramites === 1">
              <i class="fas fa-chevron-left"></i>
            </button>
            <span class="pagination-info">Página {{ currentPageTramites }} de {{ totalPagesTramites }}</span>
            <button class="pagination-btn" @click="nextPageTramites" :disabled="currentPageTramites === totalPagesTramites">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TablaSolicitudes',
  data() {
    return {
      solicitudes: [
        {
          id: 1,
          fechaSolicitud: new Date(2025, 3, 1),
          fechaEstimada: new Date(2025, 3, 10),
          fechaRegistro: new Date(2025, 3, 1),
          periodo: '2025-Q2',
          folio: 'SOL-2025-001',
          turno: 'Matutino',
          estado: 'Pendiente',
          evento: 'Creación de solicitud',
          solicitante: 'Juan Pérez',
          datosTramite: 'Registro de nuevo proyecto',
          datosSolicitud: 'Asignación de recursos',
          problematica: 'Falta de acceso al sistema',
          solucion: 'Pendiente',
          dependenciaAsignada: 'Departamento de TI'
        },
        {
          id: 2,
          fechaSolicitud: new Date(2025, 3, 2),
          fechaEstimada: new Date(2025, 3, 8),
          fechaRegistro: new Date(2025, 3, 2),
          periodo: '2025-Q2',
          folio: 'SOL-2025-002',
          turno: 'Vespertino',
          estado: 'En proceso',
          evento: 'Actualización de datos',
          solicitante: 'María González',
          datosTramite: 'Modificación de registro',
          datosSolicitud: 'Cambio de información fiscal',
          problematica: 'Datos incorrectos en sistema',
          solucion: 'En revisión',
          dependenciaAsignada: 'Departamento Fiscal'
        },
        {
          id: 3,
          fechaSolicitud: new Date(2025, 2, 28),
          fechaEstimada: new Date(2025, 3, 5),
          fechaRegistro: new Date(2025, 2, 28),
          periodo: '2025-Q1',
          folio: 'SOL-2025-003',
          turno: 'Matutino',
          estado: 'Completado',
          evento: 'Cierre de solicitud',
          solicitante: 'Roberto Sánchez',
          datosTramite: 'Autorización de acceso',
          datosSolicitud: 'Acceso a sistema de nómina',
          problematica: 'Sin permisos necesarios',
          solucion: 'Asignación de permisos completada',
          dependenciaAsignada: 'Departamento de Recursos Humanos'
        }
      ],
      currentPage: 1,
      itemsPerPage: 10,
      // Datos para la tabla de trámites
      tramites: [
        {
          id: 1,
          titulo: 'Solicitud de permiso',
          fecha: new Date(2025, 3, 3),
          estado: 'pendiente',
          responsable: 'Carlos Méndez'
        },
        {
          id: 2,
          titulo: 'Renovación de licencia',
          fecha: new Date(2025, 3, 2),
          estado: 'en-proceso',
          responsable: 'Ana Torres'
        },
        {
          id: 3,
          titulo: 'Registro de proveedor',
          fecha: new Date(2025, 3, 1),
          estado: 'completado',
          responsable: 'Luis Ramírez'
        },
        {
          id: 4,
          titulo: 'Baja de empleado',
          fecha: new Date(2025, 2, 28),
          estado: 'rechazado',
          responsable: 'Elena Gutiérrez'
        }
      ],
      currentPageTramites: 1,
      itemsPerPageTramites: 10
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.solicitudes.length / this.itemsPerPage);
    },
    paginatedSolicitudes() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.solicitudes.slice(start, end);
    },
    // Computados para la tabla de trámites
    totalPagesTramites() {
      return Math.ceil(this.tramites.length / this.itemsPerPageTramites);
    },
    paginatedTramites() {
      const start = (this.currentPageTramites - 1) * this.itemsPerPageTramites;
      const end = start + this.itemsPerPageTramites;
      return this.tramites.slice(start, end);
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return '';
      return new Date(date).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    },
    getEstadoClass(estado) {
      switch (estado.toLowerCase()) {
        case 'pendiente':
          return 'estado-pendiente';
        case 'en proceso':
          return 'estado-proceso';
        case 'completado':
          return 'estado-completado';
        case 'cancelado':
          return 'estado-cancelado';
        default:
          return '';
      }
    },
    verDetalles(id) {
      console.log('Ver detalles de solicitud:', id);
      // Aquí iría la lógica para mostrar los detalles
    },
    editarSolicitud(id) {
      console.log('Editar solicitud:', id);
      // Aquí iría la lógica para editar la solicitud
    },
    confirmarEliminar(id) {
      if (confirm('¿Está seguro que desea eliminar esta solicitud?')) {
        this.eliminarSolicitud(id);
      }
    },
    eliminarSolicitud(id) {
      console.log('Eliminar solicitud:', id);
      // Aquí iría la lógica para eliminar la solicitud
      this.solicitudes = this.solicitudes.filter(sol => sol.id !== id);
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    },
    
    // Métodos para la tabla de trámites
    getStatusLabelTramite(status) {
      const statusMap = {
        'pendiente': 'Pendiente',
        'en-proceso': 'En Proceso',
        'completado': 'Completado',
        'rechazado': 'Rechazado'
      };
      return statusMap[status] || status;
    },
    verDetallesTramite(id) {
      console.log('Ver detalles de trámite:', id);
      // Aquí iría la lógica para mostrar los detalles del trámite
    },
    editarTramite(id) {
      console.log('Editar trámite:', id);
      // Aquí iría la lógica para editar el trámite
    },
    confirmarEliminarTramite(id) {
      if (confirm('¿Está seguro que desea eliminar este trámite?')) {
        this.eliminarTramite(id);
      }
    },
    eliminarTramite(id) {
      console.log('Eliminar trámite:', id);
      // Aquí iría la lógica para eliminar el trámite
      this.tramites = this.tramites.filter(t => t.id !== id);
    },
    prevPageTramites() {
      if (this.currentPageTramites > 1) {
        this.currentPageTramites--;
      }
    },
    nextPageTramites() {
      if (this.currentPageTramites < this.totalPagesTramites) {
        this.currentPageTramites++;
      }
    }
  }
};
</script>