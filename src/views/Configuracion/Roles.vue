<template>
  <div class="usuarios-container">
    <!-- Estadísticas -->
    <div class="usuarios-stats-grid">
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-icon total">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div class="usuarios-stat-info">
          <p class="usuarios-stat-value">{{ roles.length }}</p>
          <p class="usuarios-stat-label">Total Roles</p>
        </div>
      </div>
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-icon active">
          <i class="fas fa-users"></i>
        </div>
        <div class="usuarios-stat-info">
          <p class="usuarios-stat-value">{{ rolesConUsuarios }}</p>
          <p class="usuarios-stat-label">Con Usuarios</p>
        </div>
      </div>
    </div>

    <div class="usuarios-card">
      <div class="usuarios-card-header">
        <h3>Gestión de Roles</h3>
        <div class="usuarios-header-actions">
          <button class="usuarios-btn-export" @click="exportarRoles" title="Exportar a Excel">
            <i class="fas fa-file-excel"></i> Exportar
          </button>
          <button class="usuarios-btn-primary" @click="crearNuevoRol">
            <i class="fas fa-plus"></i> Nuevo Rol
          </button>
        </div>
      </div>

      <div class="usuarios-card-body">
        <!-- Barra de búsqueda y filtros -->
        <div class="usuarios-search-filter-container">
          <div class="usuarios-search-box">
            <i class="fas fa-search"></i>
            <input
              type="text"
              v-model="filtros.busqueda"
              placeholder="Buscar por nombre o descripción..."
              @input="aplicarFiltros"
            />
            <button v-if="filtros.busqueda" class="usuarios-clear-search" @click="limpiarBusqueda">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="usuarios-filtros-refresh-container">
            <button class="usuarios-btn-refresh" @click="cargarDatos" title="Actualizar">
              <i class="fas fa-sync-alt" :class="{ 'usuarios-spinning': loading }"></i>
            </button>
          </div>
        </div>

        <!-- Resultados -->
        <div class="usuarios-results-info">
          <p>
            Mostrando <span>{{ rolesPaginados.length }}</span> de <span>{{ rolesFiltrados.length }}</span> roles
          </p>
        </div>

        <!-- Lista de roles -->
        <div class="usuarios-usuarios-list">
          <div class="usuarios-list-header">
            <div>
              <input
                type="checkbox"
                v-model="seleccionarTodos"
                @change="toggleSeleccionTodos"
              />
            </div>
            <div @click="ordenarPor('Nombre')" class="sortable" style="cursor: pointer;">
              Nombre del Rol
              <i class="fas" :class="getSortIcon('Nombre')"></i>
            </div>
            <div>Descripción</div>
            <div style="text-align: center;">Usuarios</div>
            <div style="text-align: center;">Acciones</div>
          </div>

          <div v-if="loading" class="usuarios-loading-message">
            <i class="fas fa-spinner fa-spin"></i> Cargando roles...
          </div>

          <div v-else-if="rolesPaginados.length === 0" class="usuarios-empty-message">
            <i class="fas fa-shield-alt"></i>
            <p>{{ rolesFiltrados.length === 0 ? 'No hay roles registrados' : 'No se encontraron roles con los filtros aplicados' }}</p>
          </div>

          <div v-else v-for="rol in rolesPaginados" :key="rol.Id" class="usuarios-usuario-item">
            <div>
              <input
                type="checkbox"
                :value="rol.Id"
                v-model="rolesSeleccionados"
              />
            </div>
            <div class="usuarios-usuario-info">
              <p class="usuarios-usuario-nombre">{{ rol.Nombre }}</p>
            </div>
            <div class="usuarios-usuario-info">
              <p v-html="rol.Descripcion || 'Sin descripción'"></p>
            </div>
            <div style="text-align: center;">
              <span class="usuarios-badge-rol" :class="{ 'usuarios-badge-sin-rol': rol.CantidadUsuarios === 0 }">
                <i class="fas fa-users"></i>
                {{ rol.CantidadUsuarios || 0 }}
              </span>
            </div>
            <div class="usuarios-usuario-actions">
              <button class="usuarios-action-btn view" @click="verDetalles(rol)" title="Ver detalles">
                <i class="fas fa-eye"></i>
              </button>
              <button class="usuarios-action-btn edit" @click="editarRol(rol)" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <button class="usuarios-action-btn delete" @click="confirmarEliminar(rol)" title="Eliminar">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Acciones masivas -->
        <transition name="fade">
          <div v-if="rolesSeleccionados.length > 0" class="usuarios-acciones-masivas">
            <div class="usuarios-acciones-info">
              <i class="fas fa-check-square"></i>
              <span>{{ rolesSeleccionados.length }} seleccionado(s)</span>
            </div>
            <div class="usuarios-acciones-buttons">
              <button class="usuarios-btn-mass-action cancel" @click="deseleccionarTodos" title="Cancelar selección">
                <i class="fas fa-times-circle"></i>
              </button>
              <button class="usuarios-btn-mass-action danger" @click="eliminarSeleccionados" title="Eliminar">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </transition>

        <!-- Paginación -->
        <div v-if="rolesFiltrados.length > itemsPorPagina" class="usuarios-pagination">
          <button
            class="usuarios-pagination-btn"
            @click="cambiarPagina(paginaActual - 1)"
            :disabled="paginaActual === 1"
          >
            <i class="fas fa-chevron-left"></i>
          </button>

          <button
            v-for="pagina in paginasVisibles"
            :key="pagina"
            class="usuarios-pagination-btn"
            :class="{ active: pagina === paginaActual }"
            @click="cambiarPagina(pagina)"
          >
            {{ pagina }}
          </button>

          <button
            class="usuarios-pagination-btn"
            @click="cambiarPagina(paginaActual + 1)"
            :disabled="paginaActual === totalPaginas"
          >
            <i class="fas fa-chevron-right"></i>
          </button>

          <select v-model.number="itemsPorPagina" @change="cambiarItemsPorPagina" class="usuarios-items-per-page">
            <option :value="10">10 / página</option>
            <option :value="25">25 / página</option>
            <option :value="50">50 / página</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar rol -->
    <div v-if="showModal" class="usuarios-modal-overlay" @click.self="cancelarAccion">
      <div class="usuarios-modal-content">
        <div class="usuarios-modal-header">
          <h3>{{ modoEdicion ? 'Editar Rol' : 'Nuevo Rol' }}</h3>
          <button class="usuarios-close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="usuarios-modal-body">
          <form @submit.prevent="guardarRol">
            <div class="usuarios-form-group">
              <label for="nombre">Nombre del Rol: *</label>
              <input
                type="text"
                id="nombre"
                v-model="rolForm.Nombre"
                required
              />
            </div>

            <div class="usuarios-form-group">
              <label for="descripcion">Descripción:</label>
              <textarea
                id="descripcion"
                v-model="rolForm.Descripcion"
                rows="4"
              ></textarea>
            </div>

            <div class="usuarios-form-actions">
              <button type="button" class="usuarios-btn-secondary" @click="cancelarAccion">
                Cancelar
              </button>
              <button type="submit" class="usuarios-btn-primary">
                <i class="fas" :class="modoEdicion ? 'fa-save' : 'fa-plus'"></i>
                {{ modoEdicion ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de detalles -->
    <div v-if="showDetallesModal" class="usuarios-modal-overlay" @click.self="showDetallesModal = false">
      <div class="usuarios-modal-content usuarios-modal-detalles">
        <div class="usuarios-modal-header">
          <h3>Detalles del Rol</h3>
          <button class="usuarios-close-btn" @click="showDetallesModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="usuarios-modal-body">
          <div class="usuarios-detalles-grid" v-if="rolDetalle">
            <div class="usuarios-detalle-item">
              <label>Nombre:</label>
              <p>{{ rolDetalle.Nombre }}</p>
            </div>
            <div class="usuarios-detalle-item">
              <label>Descripción:</label>
              <p>{{ rolDetalle.Descripcion || 'Sin descripción' }}</p>
            </div>
            <div class="usuarios-detalle-item" style="grid-column: 1 / -1;">
              <label>Usuarios con este rol ({{ rolUsuarios.length }}):</label>
              <div v-if="loadingDetalles" class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i> Cargando...
              </div>
              <div v-else-if="rolUsuarios.length > 0" class="roles-usuarios-list-detail">
                <div v-for="usuario in rolUsuarios" :key="usuario.IdUsuario" class="roles-usuario-chip">
                  <i class="fas fa-user"></i>
                  {{ usuario.NombreCompleto }}
                </div>
              </div>
              <p v-else class="empty-text">No hay usuarios asignados</p>
            </div>
            <div class="usuarios-detalle-item" style="grid-column: 1 / -1;">
              <label>Permisos asignados ({{ rolPermisos.length }}):</label>
              <div v-if="loadingDetalles" class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i> Cargando...
              </div>
              <div v-else-if="rolPermisos.length > 0" class="roles-permisos-list-detail">
                <div v-for="permiso in rolPermisos" :key="permiso.Codigo" class="roles-permiso-chip">
                  <i class="fas fa-shield-alt"></i>
                  {{ permiso.Nombre }}
                  <span class="permiso-modulo">{{ permiso.Modulo }}</span>
                </div>
              </div>
              <p v-else class="empty-text">No hay permisos asignados</p>
            </div>
          </div>
          <div class="usuarios-form-actions">
            <button class="usuarios-btn-secondary" @click="showDetallesModal = false">
              Cerrar
            </button>
            <button class="usuarios-btn-primary" @click="editarDesdeDetalles">
              <i class="fas fa-edit"></i> Editar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación -->
    <div v-if="showConfirmModal" class="usuarios-modal-overlay">
      <div class="usuarios-modal-content confirm-modal">
        <div class="usuarios-modal-header">
          <h3>Confirmar {{ tituloAccionMasiva }}</h3>
        </div>
        <div class="usuarios-modal-body">
          <div v-if="accionMasiva === 'eliminar'">
            <p>¿Está seguro de que desea <strong>eliminar</strong> {{ rolesSeleccionados.length }} rol(es)?</p>
            <div class="usuarios-usuarios-preview">
              <p class="usuarios-preview-title">Roles a eliminar:</p>
              <ul class="usuarios-usuarios-list-preview">
                <li v-for="id in rolesSeleccionados.slice(0, 5)" :key="id">
                  {{ obtenerNombreRol(id) }}
                </li>
                <li v-if="rolesSeleccionados.length > 5" class="usuarios-more-items">
                  ... y {{ rolesSeleccionados.length - 5 }} rol(es) más
                </li>
              </ul>
            </div>
            <p class="usuarios-warning-text">
              <i class="fas fa-exclamation-triangle"></i>
              Esta acción no se puede deshacer. Los roles serán eliminados permanentemente del sistema.
            </p>
          </div>

          <div v-else>
            <p>¿Está seguro de que desea eliminar el rol <strong>{{ rolEliminar?.Nombre }}</strong>?</p>
            <p class="usuarios-warning-text" v-if="tieneUsuarios">
              <i class="fas fa-exclamation-triangle"></i>
              <span v-if="tieneUsuarios">Este rol está asignado a uno o más usuarios. </span>
              <span>La eliminación puede afectar la operación del sistema.</span>
            </p>
          </div>

          <div class="usuarios-form-actions">
            <button type="button" class="usuarios-btn-secondary" @click="cancelarAccionMasiva">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="usuarios-btn-danger" @click="ejecutarAccionMasiva">
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
      rolPermisos: [],
      rolUsuarios: [],
      loadingDetalles: false,
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
      return this.roles.filter(r => r.CantidadUsuarios && r.CantidadUsuarios > 0).length;
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
        // Consultar cuántos usuarios tienen este rol en UsuarioRol
        const response = await axios.get(
          `${this.backendUrl}/usuario-roles.php?action=countByRole&idRol=${rolId}`
        );
        this.tieneUsuarios = (response.data.count && response.data.count > 0);
      } catch (error) {
        console.error('Error al verificar usuarios con rol:', error);
        this.tieneUsuarios = false;
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
    async verDetalles(rol) {
      this.rolDetalle = rol;
      this.rolPermisos = [];
      this.rolUsuarios = [];
      this.loadingDetalles = true;
      this.showDetallesModal = true;

      // Cargar datos en paralelo para mejorar rendimiento
      await Promise.all([
        this.cargarPermisosRol(rol.Id),
        this.cargarUsuariosRol(rol.Id)
      ]);

      this.loadingDetalles = false;
    },
    async cargarPermisosRol(rolId) {
      try {
        // Obtener permisos del rol desde RolPermiso
        const response = await axios.get(
          `${this.backendUrl}/usuario-roles.php?action=getPermisosByRole&idRol=${rolId}`
        );
        this.rolPermisos = response.data.permisos || [];
      } catch (error) {
        console.error('Error al cargar permisos del rol:', error);
        this.rolPermisos = [];
        // No mostrar error toast si la tabla no existe aún
      }
    },
    async cargarUsuariosRol(rolId) {
      try {
        // Obtener usuarios que tienen este rol desde UsuarioRol
        const response = await axios.get(
          `${this.backendUrl}/usuario-roles.php?action=getUsersByRole&idRol=${rolId}&_t=${Date.now()}`
        );
        this.rolUsuarios = response.data.usuarios || [];
      } catch (error) {
        console.error('Error al cargar usuarios del rol:', error);

        // Si hay error de conexión a la base de datos, mostrar mensaje amigable
        if (error.response?.status === 500) {
          const errorMsg = error.response?.data?.message || 'Error de conexión a la base de datos';
          console.warn('⚠️ No se pudieron cargar los usuarios del rol:', errorMsg);

          // Mostrar mensaje en la UI pero no bloquear el modal
          this.$nextTick(() => {
            const modalContent = document.querySelector('.roles-modal-detalles .roles-modal-content');
            if (modalContent) {
              const warningDiv = document.createElement('div');
              warningDiv.className = 'roles-db-warning';
              warningDiv.innerHTML = '⚠️ No se pudo conectar a la base de datos para cargar los usuarios';
              warningDiv.style.cssText = 'background:#fff3cd;color:#856404;padding:10px;border-radius:5px;margin:10px 0;';
              modalContent.insertBefore(warningDiv, modalContent.firstChild);
            }
          });
        }

        this.rolUsuarios = [];
      }
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
/* Contenedor principal con márgenes */
.usuarios-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1rem;
  background: #f8fafc;
}

/* Modal y formularios */
.usuarios-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.usuarios-modal-content {
  background: white;
  border-radius: 12px;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.usuarios-modal-header {
  padding: 20px;
  background: linear-gradient(135deg, #165CB1 0%, #1976d2 100%);
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.usuarios-modal-header h3 {
  margin: 0;
  color: white;
  font-size: 18px;
  font-weight: 600;
}

.usuarios-close-btn {
  background: none;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.usuarios-close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.usuarios-modal-body {
  padding: 24px;
}

/* Formularios */
.usuarios-form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.usuarios-form-group {
  display: flex;
  flex-direction: column;
}

.usuarios-form-group label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  font-size: 14px;
  display: block;
}

.usuarios-form-group input,
.usuarios-form-group select,
.usuarios-form-group textarea {
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s;
  background: white;
  color: #374151;
}

.usuarios-form-group input:focus,
.usuarios-form-group select:focus,
.usuarios-form-group textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.usuarios-form-group input:disabled {
  background-color: #f3f4f6;
  color: #6b7280;
  cursor: not-allowed;
}

.usuarios-form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.usuarios-btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 2px solid #e5e7eb;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s;
}

.usuarios-btn-secondary:hover {
  background: #e5e7eb;
  border-color: #d1d5db;
}

.usuarios-btn-primary {
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 12px 20px;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
}

.usuarios-btn-primary:hover {
  background-color: #1e40af;
}

.usuarios-btn-danger {
  background-color: #dc2626;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 12px 20px;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
}

.usuarios-btn-danger:hover {
  background-color: #b91c1c;
}

.usuarios-card-header {
  padding: 20px;
  background: linear-gradient(135deg, #165CB1 0%, #1976d2 100%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 12px 12px 0 0;
}

.usuarios-card-header h3 {
  margin: 0;
  color: white;
  font-size: 18px;
}

.usuarios-modal-header h3 {
  margin: 0;
  color: white;
  font-size: 18px;
}

/* Tarjeta principal con bordes redondeados */
.usuarios-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 1.5rem;
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}

.usuarios-card-body {
  padding: 20px;
}

/* Estadísticas */
.usuarios-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin: 2rem 0 1.5rem 0;
}

.usuarios-stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: transform 0.2s;
}

.usuarios-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.usuarios-stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.usuarios-stat-icon.total {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.usuarios-stat-icon.active {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.usuarios-stat-icon.inactive {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.usuarios-stat-icon.roles {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.usuarios-stat-info {
  flex: 1;
}

.usuarios-stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.usuarios-stat-label {
  font-size: 14px;
  color: #666;
  margin: 5px 0 0 0;
}

/* Header actions */
.usuarios-header-actions {
  display: flex;
  gap: 10px;
}

.usuarios-btn-export {
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

.usuarios-btn-export:hover {
  background: #059669;
}

.usuarios-btn-primary {
  background-color: #2563eb;
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

.usuarios-btn-primary:hover {
  background-color: #1e40af;
}

/* Contenedor para filtros y botón refrescar */
.usuarios-filtros-refresh-container {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-shrink: 0;
}

/* búsqueda, filtros y refrescar en una línea horizontal */
.usuarios-search-filter-container {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.usuarios-search-box {
  flex: 2;
  position: relative;
  display: flex;
  align-items: center;
  min-width: 300px;
}

.usuarios-search-box i {
  position: absolute;
  left: 15px;
  color: #999;
}

.usuarios-search-box input {
  width: 100%;
  padding: 12px 45px 12px 45px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s;
}

.usuarios-search-box input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.usuarios-clear-search {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  padding: 5px;
}

.usuarios-btn-filter {
  background: white;
  border: 2px solid #e5e7eb;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  transition: all 0.3s;
  position: relative;
  font-size: 14px;
  color: rgb(151, 148, 148);
  white-space: nowrap;
  min-width: fit-content;
}

.usuarios-btn-filter:hover {
  border-color: #1e293b;
  color: #1e293b;
}

.usuarios-filter-badge {
  background: #1e293b;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
}

.usuarios-btn-refresh {
  background: white;
  border: 2px solid #e5e7eb;
  padding: 12px 15px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  min-width: fit-content;
}

.usuarios-btn-refresh:hover {
  border-color: #2563eb;
  color: #2563eb;
}

.usuarios-spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  100% { transform: rotate(360deg); }
}

/* Resultados */
.usuarios-results-info {
  margin-bottom: 15px;
  font-size: 14px;
  color: #666;
}

.usuarios-results-info span {
  color: #1e293b;
  font-weight: 500;
}

.usuarios-usuario-info {
  display: flex;
  align-items: center;
}

.usuarios-usuario-info p {
  margin: 0;
  color: #1e293b;
}

.usuarios-usuario-nombre {
  font-weight: 600;
  color: #1e293b;
}

.usuarios-badge-rol {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
}

.usuarios-badge-sin-rol {
  background: #f3f4f6;
  color: #6b7280;
}

.usuarios-badge-status {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  display: inline-block;
}

.usuarios-badge-status.active {
  background: #d1fae5;
  color: #065f46;
}

.usuarios-badge-status.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.usuarios-usuario-actions {
  display: flex;
  justify-content: center;
  gap: 10px;
}

.usuarios-action-btn {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
}

.usuarios-action-btn.view {
  background-color: #8b5cf6;
  color: white;
}

.usuarios-action-btn.edit {
  background-color: #f0ad4e;
  color: white;
}

.usuarios-action-btn.toggle {
  background-color: #06b6d4;
  color: white;
}

.usuarios-action-btn.hierarchy {
  background-color: #5bc0de;
  color: white;
}

.usuarios-action-btn.delete {
  background-color: #d9534f;
  color: white;
}

.usuarios-action-btn:hover {
  opacity: 0.8;
}

.usuarios-loading-message,
.usuarios-empty-message {
  padding: 40px 20px;
  text-align: center;
  color: #64748b;
}

.usuarios-empty-message i {
  font-size: 48px;
  color: #ccc;
  margin-bottom: 10px;
}

/* Lista de usuarios - Tabla principal */
.usuarios-usuarios-list {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.usuarios-list-header {
  display: grid;
  grid-template-columns: 60px 1fr 1.5fr 120px 140px;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.875rem;
  align-items: center;
}

.usuarios-usuario-item {
  display: grid;
  grid-template-columns: 60px 1fr 1.5fr 120px 140px;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  align-items: center;
  transition: background-color 0.2s;
  font-size: 0.875rem;
}

.usuarios-usuario-item:last-child {
  border-bottom: none;
}

.usuarios-usuario-item:hover {
  background-color: #f8fafc;
}

/* Acciones masivas */
.usuarios-acciones-masivas {
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

.usuarios-acciones-info {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 8px;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  border-right: 1px solid rgba(0,0,0,0.1);
}

.usuarios-acciones-info i {
  color: #2563eb;
  font-size: 14px;
}

.usuarios-acciones-info span {
  white-space: nowrap;
}

.usuarios-acciones-buttons {
  display: flex;
  gap: 6px;
}

.usuarios-btn-mass-action {
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
  position: relative;
}

.usuarios-btn-mass-action::before {
  content: attr(title);
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%) scale(0);
  background: rgba(0,0,0,0.8);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: all 0.2s;
  font-weight: 500;
}

.usuarios-btn-mass-action:hover::before {
  transform: translateX(-50%) scale(1);
  opacity: 1;
}

.usuarios-btn-mass-action i {
  color: white;
}

.usuarios-btn-mass-action.cancel {
  background: #6b7280;
}

.usuarios-btn-mass-action.cancel:hover {
  background: #4b5563;
  transform: scale(1.1);
}

.usuarios-btn-mass-action.success {
  background: #10b981;
}

.usuarios-btn-mass-action.success:hover {
  background: #059669;
  transform: scale(1.1);
}

.usuarios-btn-mass-action.warning {
  background: #f59e0b;
}

.usuarios-btn-mass-action.warning:hover {
  background: #d97706;
  transform: scale(1.1);
}

.usuarios-btn-mass-action.danger {
  background: #ef4444;
}

.usuarios-btn-mass-action.danger:hover {
  background: #dc2626;
  transform: scale(1.1);
}

/* paginación */
.usuarios-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-top: 30px;
}

.usuarios-pagination-btn {
  padding: 8px 12px;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
  min-width: 40px;
}

.usuarios-pagination-btn:hover:not(:disabled) {
  border-color: #2563eb;
  color: #2563eb;
}

.usuarios-pagination-btn.active {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

.usuarios-pagination-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.usuarios-items-per-page {
  margin-left: 20px;
  padding: 8px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
}

/* Modales */
.usuarios-modal-overlay {
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

.usuarios-modal-content {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.usuarios-modal-detalles {
  max-width: 700px;
}

.confirm-modal {
  max-width: 500px;
}

.usuarios-modal-header {
  padding: 15px 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.usuarios-modal-header h3 {
  margin: 0;
  color: white;
}

.usuarios-close-btn {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: white;
}

.usuarios-modal-body {
  padding: 20px;
}

.usuarios-form-group {
  margin-bottom: 15px;
}

.usuarios-form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #374151;
}

.usuarios-form-group input,
.usuarios-form-group select,
.usuarios-form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  font-size: 14px;
}

.usuarios-form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.usuarios-form-actions {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.usuarios-btn-secondary {
  background-color: #f8f9fa;
  color: #374151;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  padding: 10px 15px;
  cursor: pointer;
  transition: all 0.3s;
}

.usuarios-btn-danger {
  background-color: #d9534f;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 10px 15px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.usuarios-detalles-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.usuarios-detalle-item {
  padding: 15px;
  background: #f9fafb;
  border-radius: 8px;
}

.usuarios-detalle-item label {
  display: block;
  font-size: 12px;
  color: #666;
  margin-bottom: 5px;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.usuarios-detalle-item p {
  margin: 0;
  font-size: 16px;
  color: #1e293b;
  font-weight: 500;
}

.usuarios-warning-text {
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

.usuarios-warning-text i {
  color: #f59e0b;
  font-size: 18px;
}

.usuarios-usuarios-preview {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  margin: 15px 0;
  border: 1px solid #e5e7eb;
}

.usuarios-preview-title {
  font-weight: 600;
  color: #374151;
  margin: 0 0 10px 0;
  font-size: 14px;
}

.usuarios-usuarios-list-preview {
  list-style: none;
  padding: 0;
  margin: 0;
  max-height: 200px;
  overflow-y: auto;
}

.usuarios-usuarios-list-preview li {
  padding: 8px 12px;
  margin: 4px 0;
  background: white;
  border-radius: 4px;
  border-left: 3px solid #2563eb;
  font-size: 13px;
  color: #1e293b;
}

.usuarios-usuarios-list-preview li.usuarios-more-items {
  background: #e5e7eb;
  border-left-color: #6b7280;
  font-style: italic;
  color: #6b7280;
  text-align: center;
}

/* Detalles de usuarios y permisos */
.roles-usuarios-list-detail,
.roles-permisos-list-detail {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.roles-usuario-chip,
.roles-permiso-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 20px;
  font-size: 13px;
  color: #374151;
  transition: all 0.2s;
}

.roles-usuario-chip:hover {
  background: #eff6ff;
  border-color: #2563eb;
}

.roles-usuario-chip i {
  color: #2563eb;
  font-size: 12px;
}

.roles-permiso-chip {
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.roles-permiso-chip:hover {
  background: #f0fdf4;
  border-color: #10b981;
}

.roles-permiso-chip i {
  color: #10b981;
  font-size: 12px;
}

.permiso-modulo {
  font-size: 11px;
  color: #6b7280;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.loading-spinner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  color: #165CB1;
  font-size: 14px;
}

.loading-spinner i {
  font-size: 18px;
}

.empty-text {
  margin: 10px 0 0 0;
  color: #9ca3af;
  font-style: italic;
  font-size: 14px;
}

/* Animaciones */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Responsive */
@media (max-width: 1024px) {
  .usuarios-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .usuarios-list-header,
  .usuarios-usuario-item {
    grid-template-columns: 50px 1fr 100px 120px;
    font-size: 0.75rem;
  }

  .usuarios-list-header > div:nth-child(3),
  .usuarios-usuario-item > div:nth-child(3) {
    display: none;
  }
}

@media (max-width: 480px) {
  .usuarios-stats-grid {
    grid-template-columns: 1fr;
  }

  .usuarios-search-filter-container {
    flex-direction: column;
    align-items: stretch;
  }

  .usuarios-search-box {
    min-width: unset;
  }

  .usuarios-list-header,
  .usuarios-usuario-item {
    grid-template-columns: 40px 1fr 100px;
    font-size: 0.75rem;
  }

  .usuarios-list-header > div:nth-child(3),
  .usuarios-usuario-item > div:nth-child(3),
  .usuarios-list-header > div:nth-child(4),
  .usuarios-usuario-item > div:nth-child(4) {
    display: none;
  }

  .usuarios-action-btn {
    width: 28px;
    height: 28px;
  }
}
</style>




