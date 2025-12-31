<template>
  <div class="roles-container">
    <!-- Estadísticas -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div class="stat-info">
          <p class="stat-value">{{ roles.length }}</p>
          <p class="stat-label">Total Roles</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon users">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
          <p class="stat-value">{{ rolesConUsuarios }}</p>
          <p class="stat-label">Con Usuarios</p>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Gestión de Roles</h3>
        <div class="header-actions">
          <button class="btn-export" @click="exportarRoles" title="Exportar a Excel">
            <i class="fas fa-file-excel"></i> Exportar
          </button>
          <button class="btn-primary" @click="crearNuevoRol">
            <i class="fas fa-plus"></i> Nuevo Rol
          </button>
        </div>
      </div>

      <div class="card-body">
        <!-- Barra de búsqueda y filtros -->
        <div class="search-filter-container">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input
              type="text"
              v-model="filtros.busqueda"
              placeholder="Buscar por nombre o descripción..."
              @input="aplicarFiltros"
            />
            <button v-if="filtros.busqueda" class="clear-search" @click="limpiarBusqueda">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <button class="btn-refresh" @click="cargarDatos" title="Actualizar">
            <i class="fas fa-sync-alt" :class="{ 'spinning': loading }"></i>
          </button>
        </div>

        <!-- Resultados -->
        <div class="results-info">
          <p>
            Mostrando {{ rolesPaginados.length }} de {{ rolesFiltrados.length }} roles
          </p>
        </div>

        <!-- Lista de roles -->
        <div class="roles-list">
          <div class="list-header">
            <div class="header-check">
              <input
                type="checkbox"
                v-model="seleccionarTodos"
                @change="toggleSeleccionTodos"
              />
            </div>
            <div @click="ordenarPor('Nombre')" class="sortable">
              Nombre del Rol
              <i class="fas" :class="getSortIcon('Nombre')"></i>
            </div>
            <div>Descripción</div>
            <div>Acciones</div>
          </div>

          <div v-if="loading" class="loading-message">
            <i class="fas fa-spinner fa-spin"></i> Cargando roles...
          </div>

          <div v-else-if="rolesPaginados.length === 0" class="empty-message">
            <i class="fas fa-shield-alt"></i>
            <p>{{ rolesFiltrados.length === 0 ? 'No hay roles registrados' : 'No se encontraron roles con los filtros aplicados' }}</p>
          </div>

          <div v-else v-for="rol in rolesPaginados" :key="rol.Id" class="rol-item">
            <div class="rol-check">
              <input
                type="checkbox"
                :value="rol.Id"
                v-model="rolesSeleccionados"
              />
            </div>
            <div class="rol-info">
              <p class="rol-nombre">{{ rol.Nombre }}</p>
            </div>
            <div class="rol-info description">
              <p>{{ rol.Descripcion || 'Sin descripción' }}</p>
            </div>
            <div class="rol-actions">
              <button class="action-btn view" @click="verDetalles(rol)" title="Ver detalles">
                <i class="fas fa-eye"></i>
              </button>
              <button class="action-btn edit" @click="editarRol(rol)" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn delete" @click="confirmarEliminar(rol)" title="Eliminar">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Acciones masivas -->
        <transition name="fade">
          <div v-if="rolesSeleccionados.length > 0" class="acciones-masivas">
            <div class="acciones-info">
              <i class="fas fa-check-square"></i>
              <span>{{ rolesSeleccionados.length }} seleccionado(s)</span>
            </div>
            <div class="acciones-buttons">
              <button class="btn-mass-action cancel" @click="deseleccionarTodos" title="Cancelar selección">
                <i class="fas fa-times-circle"></i>
              </button>
              <button class="btn-mass-action danger" @click="eliminarSeleccionados" title="Eliminar">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </transition>

        <!-- Paginación -->
        <div v-if="rolesFiltrados.length > itemsPorPagina" class="pagination">
          <button
            class="pagination-btn"
            @click="cambiarPagina(paginaActual - 1)"
            :disabled="paginaActual === 1"
          >
            <i class="fas fa-chevron-left"></i>
          </button>

          <button
            v-for="pagina in paginasVisibles"
            :key="pagina"
            class="pagination-btn"
            :class="{ active: pagina === paginaActual }"
            @click="cambiarPagina(pagina)"
          >
            {{ pagina }}
          </button>

          <button
            class="pagination-btn"
            @click="cambiarPagina(paginaActual + 1)"
            :disabled="paginaActual === totalPaginas"
          >
            <i class="fas fa-chevron-right"></i>
          </button>

          <select v-model.number="itemsPorPagina" @change="cambiarItemsPorPagina" class="items-per-page">
            <option :value="10">10 / página</option>
            <option :value="25">25 / página</option>
            <option :value="50">50 / página</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar rol -->
    <div v-if="showModal" class="modal-overlay" @click.self="cancelarAccion">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ modoEdicion ? 'Editar Rol' : 'Nuevo Rol' }}</h3>
          <button class="close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="guardarRol">
            <div class="form-group">
              <label for="nombre">Nombre del Rol: *</label>
              <input
                type="text"
                id="nombre"
                v-model="rolForm.Nombre"
                required
              />
            </div>

            <div class="form-group">
              <label for="descripcion">Descripción:</label>
              <textarea
                id="descripcion"
                v-model="rolForm.Descripcion"
                rows="4"
              ></textarea>
            </div>

            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="cancelarAccion">
                Cancelar
              </button>
              <button type="submit" class="btn-primary">
                <i class="fas" :class="modoEdicion ? 'fa-save' : 'fa-plus'"></i>
                {{ modoEdicion ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de detalles -->
    <div v-if="showDetallesModal" class="modal-overlay" @click.self="showDetallesModal = false">
      <div class="modal-content modal-detalles">
        <div class="modal-header">
          <h3>Detalles del Rol</h3>
          <button class="close-btn" @click="showDetallesModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="detalles-grid" v-if="rolDetalle">
            <div class="detalle-item">
              <label>Nombre:</label>
              <p>{{ rolDetalle.Nombre }}</p>
            </div>
            <div class="detalle-item">
              <label>Descripción:</label>
              <p>{{ rolDetalle.Descripcion || 'Sin descripción' }}</p>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn-secondary" @click="showDetallesModal = false">
              Cerrar
            </button>
            <button class="btn-primary" @click="editarDesdeDetalles">
              <i class="fas fa-edit"></i> Editar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación -->
    <div v-if="showConfirmModal" class="modal-overlay">
      <div class="modal-content confirm-modal">
        <div class="modal-header">
          <h3>Confirmar {{ tituloAccionMasiva }}</h3>
        </div>
        <div class="modal-body">
          <div v-if="accionMasiva === 'eliminar'">
            <p>¿Está seguro de que desea <strong>eliminar</strong> {{ rolesSeleccionados.length }} rol(es)?</p>
            <div class="usuarios-preview">
              <p class="preview-title">Roles a eliminar:</p>
              <ul class="usuarios-list-preview">
                <li v-for="id in rolesSeleccionados.slice(0, 5)" :key="id">
                  {{ obtenerNombreRol(id) }}
                </li>
                <li v-if="rolesSeleccionados.length > 5" class="more-items">
                  ... y {{ rolesSeleccionados.length - 5 }} rol(es) más
                </li>
              </ul>
            </div>
            <p class="warning-text">
              <i class="fas fa-exclamation-triangle"></i>
              Esta acción no se puede deshacer. Los roles serán eliminados permanentemente del sistema.
            </p>
          </div>

          <div v-else>
            <p>¿Está seguro de que desea eliminar el rol <strong>{{ rolEliminar?.Nombre }}</strong>?</p>
            <p class="warning-text" v-if="tieneUsuarios">
              <i class="fas fa-exclamation-triangle"></i>
              <span v-if="tieneUsuarios">Este rol está asignado a uno o más usuarios. </span>
              <span>La eliminación puede afectar la operación del sistema.</span>
            </p>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="cancelarAccionMasiva">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn-danger" @click="ejecutarAccionMasiva">
              <i class="fas fa-trash"></i> Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'RolesList',
  data() {
    return {
      loading: true,
      roles: [],
      rolesFiltrados: [],
      showModal: false,
      showConfirmModal: false,
      showDetallesModal: false,
      modoEdicion: false,
      rolForm: {
        Id: null,
        Nombre: '',
        Descripcion: ''
      },
      rolEliminar: null,
      rolDetalle: null,
      tieneUsuarios: false,
      backendUrl: import.meta.env.VITE_API_URL,

      // Filtros y búsqueda
      filtros: {
        busqueda: ''
      },

      // Ordenamiento
      ordenamiento: {
        campo: 'Nombre',
        direccion: 'asc'
      },

      // Paginación
      paginaActual: 1,
      itemsPorPagina: 25,

      // Selección múltiple
      rolesSeleccionados: [],
      seleccionarTodos: false,
      accionMasiva: null
    };
  },
  computed: {
    rolesConUsuarios() {
      // Esto debería venir del backend idealmente
      return this.roles.filter(r => r.tieneUsuarios).length;
    },
    rolesPaginados() {
      const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
      const fin = inicio + this.itemsPorPagina;
      return this.rolesFiltrados.slice(inicio, fin);
    },
    totalPaginas() {
      return Math.ceil(this.rolesFiltrados.length / this.itemsPorPagina);
    },
    paginasVisibles() {
      const paginas = [];
      const maxPaginas = 5;
      let inicio = Math.max(1, this.paginaActual - Math.floor(maxPaginas / 2));
      let fin = Math.min(this.totalPaginas, inicio + maxPaginas - 1);

      if (fin - inicio < maxPaginas - 1) {
        inicio = Math.max(1, fin - maxPaginas + 1);
      }

      for (let i = inicio; i <= fin; i++) {
        paginas.push(i);
      }
      return paginas;
    },
    tituloAccionMasiva() {
      if (this.accionMasiva === 'eliminar') return 'Eliminación Masiva';
      return 'Confirmar eliminación';
    }
  },
  created() {
    this.cargarDatos();
  },
  methods: {
    async cargarDatos() {
      await this.cargarRoles();
    },
    async cargarRoles() {
      try {
        this.loading = true;
        const response = await axios.get(`${this.backendUrl}/roles.php`);
        this.roles = response.data.records || [];
        this.aplicarFiltros();
        this.loading = false;
      } catch (error) {
        console.error('Error al cargar roles:', error);
        this.loading = false;
        if (this.$toast) {
          this.$toast.error('Error al cargar roles');
        }
      }
    },
    crearNuevoRol() {
      this.modoEdicion = false;
      this.rolForm = {
        Id: null,
        Nombre: '',
        Descripcion: ''
      };
      this.showModal = true;
    },
    editarRol(rol) {
      this.modoEdicion = true;
      this.rolForm = { ...rol };
      this.showModal = true;
    },
    async guardarRol() {
      try {
        const formData = { ...this.rolForm };

        if (this.modoEdicion) {
          // Actualizar rol existente
          await axios.put(`${this.backendUrl}/roles.php`, formData);
          if (this.$toast) {
            this.$toast.success('Rol actualizado correctamente');
          } else {
            alert('Rol actualizado correctamente');
          }
        } else {
          // Crear nuevo rol
          await axios.post(`${this.backendUrl}/roles.php`, formData);
          if (this.$toast) {
            this.$toast.success('Rol creado correctamente');
          } else {
            alert('Rol creado correctamente');
          }
        }

        // Recargar roles y cerrar modal
        this.showModal = false;
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al guardar rol:', error);
        if (this.$toast) {
          this.$toast.error('Error al guardar el rol');
        } else {
          alert('Error al guardar el rol');
        }
      }
    },
    confirmarEliminar(rol) {
      this.rolEliminar = rol;

      // Verificar si tiene usuarios con este rol
      this.verificarUsuariosConRol(rol.Id);

      this.showConfirmModal = true;
    },
    async verificarUsuariosConRol(rolId) {
      try {
        // Esto debería ser reemplazado por una llamada real a la API
        const response = await axios.get(`${this.backendUrl}/usuarios.php?rolId=${rolId}`);
        this.tieneUsuarios = (response.data.records && response.data.records.length > 0);
      } catch (error) {
        console.error('Error al verificar usuarios con rol:', error);
        this.tieneUsuarios = false; // Por defecto, asumir que no hay usuarios
      }
    },
    async eliminarRol() {
      try {
        await axios.delete(`${this.backendUrl}/roles.php`, {
          data: { Id: this.rolEliminar.Id }
        });
        if (this.$toast) {
          this.$toast.success('Rol eliminado correctamente');
        } else {
          alert('Rol eliminado correctamente');
        }
        this.showConfirmModal = false;

        // Recargar roles y jerarquías
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al eliminar rol:', error);
        if (this.$toast) {
          this.$toast.error('Error al eliminar el rol');
        } else {
          alert('Error al eliminar el rol');
        }
      }
    },
    cancelarAccion() {
      this.showModal = false;
      this.showDetallesModal = false;
      this.cancelarAccionMasiva();
    },
    aplicarFiltros() {
      let resultado = [...this.roles];

      if (this.filtros.busqueda) {
        const busqueda = this.filtros.busqueda.toLowerCase();
        resultado = resultado.filter(r =>
          r.Nombre.toLowerCase().includes(busqueda) ||
          (r.Descripcion && r.Descripcion.toLowerCase().includes(busqueda))
        );
      }

      resultado.sort((a, b) => {
        const valorA = a[this.ordenamiento.campo] || '';
        const valorB = b[this.ordenamiento.campo] || '';

        if (this.ordenamiento.direccion === 'asc') {
          return valorA > valorB ? 1 : -1;
        } else {
          return valorA < valorB ? 1 : -1;
        }
      });

      this.rolesFiltrados = resultado;
      this.paginaActual = 1;
    },
    ordenarPor(campo) {
      if (this.ordenamiento.campo === campo) {
        this.ordenamiento.direccion = this.ordenamiento.direccion === 'asc' ? 'desc' : 'asc';
      } else {
        this.ordenamiento.campo = campo;
        this.ordenamiento.direccion = 'asc';
      }
      this.aplicarFiltros();
    },
    getSortIcon(campo) {
      if (this.ordenamiento.campo !== campo) return 'fa-sort';
      return this.ordenamiento.direccion === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    },
    limpiarBusqueda() {
      this.filtros.busqueda = '';
      this.aplicarFiltros();
    },
    cambiarPagina(pagina) {
      if (pagina >= 1 && pagina <= this.totalPaginas) {
        this.paginaActual = pagina;
      }
    },
    cambiarItemsPorPagina() {
      this.paginaActual = 1;
    },
    toggleSeleccionTodos() {
      if (this.seleccionarTodos) {
        this.rolesSeleccionados = this.rolesPaginados.map(r => r.Id);
      } else {
        this.rolesSeleccionados = [];
      }
    },
    deseleccionarTodos() {
      this.rolesSeleccionados = [];
      this.seleccionarTodos = false;
    },
    verDetalles(rol) {
      this.rolDetalle = rol;
      this.showDetallesModal = true;
    },
    editarDesdeDetalles() {
      this.showDetallesModal = false;
      this.editarRol(this.rolDetalle);
    },
    obtenerNombreRol(id) {
      const rol = this.roles.find(r => r.Id === id);
      return rol ? rol.Nombre : 'Rol desconocido';
    },
    eliminarSeleccionados() {
      this.accionMasiva = 'eliminar';
      this.showConfirmModal = true;
    },
    ejecutarAccionMasiva() {
      if (this.accionMasiva === 'eliminar') {
        this.eliminarRolesMasivo();
      } else if (this.rolEliminar) {
        this.eliminarRol();
      }
    },
    async eliminarRolesMasivo() {
      try {
        const promesas = this.rolesSeleccionados.map(id =>
          axios.delete(`${this.backendUrl}/roles.php`, {
            data: { Id: id }
          })
        );

        await Promise.all(promesas);

        if (this.$toast) {
          this.$toast.success(`${this.rolesSeleccionados.length} rol(es) eliminados correctamente`);
        }

        this.showConfirmModal = false;
        this.rolesSeleccionados = [];
        this.seleccionarTodos = false;
        this.accionMasiva = null;
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al eliminar roles:', error);
        if (this.$toast) {
          this.$toast.error('Error al eliminar los roles');
        }
      }
    },
    cancelarAccionMasiva() {
      this.showConfirmModal = false;
      this.accionMasiva = null;
      this.rolEliminar = null;
    },
    exportarRoles() {
      const datosExportar = this.rolesFiltrados.map(r => ({
        'Nombre': r.Nombre,
        'Descripción': r.Descripcion || ''
      }));

      const headers = Object.keys(datosExportar[0]);
      const csvContent = [
        headers.join(','),
        ...datosExportar.map(row =>
          headers.map(header => `"${row[header]}"`).join(',')
        )
      ].join('\n');

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `roles_${new Date().toISOString().split('T')[0]}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      if (this.$toast) {
        this.$toast.success('Roles exportados correctamente');
      }
    }
  }
}
</script>

<style scoped>
/* Usar los mismos estilos que Usuarios.vue */
.roles-container {
  padding: 20px;
}

/* Stats cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-icon.hierarchy { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.stat-icon.users { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
.stat-icon.subordinates { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--secondary-color);
  margin: 0;
}

.stat-label {
  font-size: 14px;
  color: #666;
  margin: 5px 0 0 0;
}

/* Card */
.card {
  background-color: var(--white-color);
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

.card-header {
  padding: 20px;
  background-color: var(--white-color);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  color: var(--secondary-color);
  font-size: 18px;
}

.card-body {
  padding: 20px;
}

/* Header actions */
.header-actions {
  display: flex;
  gap: 10px;
}

.btn-export {
  background: #10b981;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s;
  font-size: 14px;
}

.btn-export:hover {
  background: #059669;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 10px 15px;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.btn-primary:hover {
  background-color: var(--secondary-color);
}

/* Búsqueda y filtros */
.search-filter-container {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.search-box {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}

.search-box i {
  position: absolute;
  left: 15px;
  color: #999;
}

.search-box input {
  width: 100%;
  padding: 12px 45px 12px 45px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-search {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  padding: 5px;
}

.btn-refresh {
  background: white;
  border: 2px solid #e5e7eb;
  padding: 10px 15px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-refresh:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  100% { transform: rotate(360deg); }
}

/* Resultados */
.results-info {
  margin-bottom: 15px;
  font-size: 14px;
  color: #666;
}

/* Lista */
.roles-list {
  margin-top: 20px;
  border-radius: 8px;
  overflow: hidden;
  background-color: var(--white-color);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.list-header {
  display: grid;
  grid-template-columns: 40px 1fr 1.5fr 0.8fr;
  background-color: rgba(39, 135, 245, 0.926);
  padding: 15px;
  font-weight: 600;
  color: white;
}

.header-check {
  display: flex;
  align-items: center;
  justify-content: center;
}

.sortable {
  cursor: pointer;
  user-select: none;
  display: flex;
  align-items: center;
  gap: 5px;
}

.sortable:hover {
  opacity: 0.7;
}

.rol-item {
  display: grid;
  grid-template-columns: 40px 1fr 1.5fr 0.8fr;
  padding: 15px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  transition: var(--transition);
}

.rol-item:hover {
  background-color: rgba(22, 84, 177, 0.05);
}

.rol-check {
  display: flex;
  align-items: center;
  justify-content: center;
}

.rol-info {
  display: flex;
  align-items: center;
}

.rol-info p {
  margin: 0;
  color: var(--secondary-color);
}

.rol-nombre {
  font-weight: 600;
  color: var(--primary-color);
}

.rol-info.description p {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rol-actions {
  display: flex;
  justify-content: center;
  gap: 10px;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
}

.action-btn.view {
  background-color: #8b5cf6;
  color: white;
}

.action-btn.edit {
  background-color: #f0ad4e;
  color: white;
}

.action-btn.delete {
  background-color: #d9534f;
  color: white;
}

.action-btn:hover {
  opacity: 0.8;
}

.loading-message, .empty-message {
  padding: 40px 20px;
  text-align: center;
  color: var(--secondary-color);
}

.empty-message i {
  font-size: 48px;
  color: #ccc;
  margin-bottom: 10px;
}

/* Acciones masivas */
.acciones-masivas {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border-radius: 50px;
  padding: 8px 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 100;
  border: 1px solid rgba(0,0,0,0.08);
}

.acciones-info {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--secondary-color);
  border-right: 1px solid rgba(0,0,0,0.1);
}

.acciones-info i {
  color: var(--primary-color);
  font-size: 14px;
}

.acciones-buttons {
  display: flex;
  gap: 6px;
}

.btn-mass-action {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s;
}

.btn-mass-action i {
  color: white;
}

.btn-mass-action.cancel {
  background: #6b7280;
}

.btn-mass-action.cancel:hover {
  background: #4b5563;
  transform: scale(1.1);
}

.btn-mass-action.danger {
  background: #ef4444;
}

.btn-mass-action.danger:hover {
  background: #dc2626;
  transform: scale(1.1);
}

/* Paginación */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-top: 30px;
}

.pagination-btn {
  padding: 8px 12px;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
  min-width: 40px;
}

.pagination-btn:hover:not(:disabled) {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.pagination-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.pagination-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.items-per-page {
  margin-left: 20px;
  padding: 8px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
}

/* Modales */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: var(--white-color);
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-detalles {
  max-width: 700px;
}

.confirm-modal {
  max-width: 500px;
}

.modal-header {
  padding: 15px 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: var(--secondary-color);
}

.close-btn {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: var(--secondary-color);
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: var(--secondary-color);
}

.form-group input, .form-group select, .form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  font-size: 14px;
}

.form-actions {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-secondary {
  background-color: #f8f9fa;
  color: var(--secondary-color);
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  padding: 10px 15px;
  cursor: pointer;
  transition: var(--transition);
}

.btn-danger {
  background-color: #d9534f;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 10px 15px;
  cursor: pointer;
  transition: var(--transition);
}

.detalles-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.detalle-item {
  padding: 15px;
  background: #f9fafb;
  border-radius: 8px;
}

.detalle-item.full-width {
  grid-column: 1 / -1;
}

.detalle-item label {
  display: block;
  font-size: 12px;
  color: #666;
  margin-bottom: 5px;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.detalle-item p {
  margin: 0;
  font-size: 16px;
  color: var(--secondary-color);
  font-weight: 500;
}

.warning-text {
  background: #fef3c7;
  border-left: 4px solid #f59e0b;
  padding: 12px;
  margin: 15px 0;
  color: #92400e;
  display: flex;
  align-items: center;
  gap: 10px;
  border-radius: 4px;
  font-size: 14px;
}

.warning-text i {
  color: #f59e0b;
  font-size: 18px;
}

.usuarios-preview {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  margin: 15px 0;
  border: 1px solid #e5e7eb;
}

.preview-title {
  font-weight: 600;
  color: var(--secondary-color);
  margin: 0 0 10px 0;
  font-size: 14px;
}

.usuarios-list-preview {
  list-style: none;
  padding: 0;
  margin: 0;
  max-height: 200px;
  overflow-y: auto;
}

.usuarios-list-preview li {
  padding: 8px 12px;
  margin: 4px 0;
  background: white;
  border-radius: 4px;
  border-left: 3px solid var(--primary-color);
  font-size: 13px;
  color: var(--secondary-color);
}

.usuarios-list-preview li.more-items {
  background: #e5e7eb;
  border-left-color: #6b7280;
  font-style: italic;
  color: #6b7280;
  text-align: center;
}

/* Jerarquía */
.hierarchy-type-selector {
  margin-bottom: 20px;
}

.hierarchy-selector {
  display: flex;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  overflow: hidden;
}

.hierarchy-btn {
  flex: 1;
  padding: 10px;
  background-color: #f8f9fa;
  border: none;
  cursor: pointer;
  transition: var(--transition);
}

.hierarchy-btn.active {
  background-color: var(--primary-color);
  color: white;
}

.hierarchy-container {
  margin-top: 15px;
}

.hierarchy-container h4 {
  margin-top: 0;
  margin-bottom: 15px;
  color: var(--secondary-color);
}

.role-selection {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  padding: 10px;
}

.role-item {
  display: flex;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.role-item:last-child {
  border-bottom: none;
}

.role-item input[type="checkbox"] {
  margin-right: 10px;
}

.empty-roles {
  padding: 15px;
  text-align: center;
  color: var(--secondary-color);
  font-style: italic;
}

/* Animaciones */
.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
