<template>
  <div class="usuarios-container">
    <!-- Estadísticas -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
          <p class="stat-value">{{ usuarios.length }}</p>
          <p class="stat-label">Total Usuarios</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon active">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-info">
          <p class="stat-value">{{ usuariosActivos }}</p>
          <p class="stat-label">Activos</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon inactive">
          <i class="fas fa-user-times"></i>
        </div>
        <div class="stat-info">
          <p class="stat-value">{{ usuariosInactivos }}</p>
          <p class="stat-label">Inactivos</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon roles">
          <i class="fas fa-user-tag"></i>
        </div>
        <div class="stat-info">
          <p class="stat-value">{{ rolesUnicos }}</p>
          <p class="stat-label">Roles Diferentes</p>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Gestión de Usuarios</h3>
        <div class="header-actions">
          <button class="btn-export" @click="exportarUsuarios" title="Exportar a Excel">
            <i class="fas fa-file-excel"></i> Exportar
          </button>
          <button class="btn-primary" @click="crearNuevoUsuario">
            <i class="fas fa-plus"></i> Nuevo Usuario
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
              placeholder="Buscar por usuario, nombre, apellido..."
              @input="aplicarFiltros"
            />
            <button v-if="filtros.busqueda" class="clear-search" @click="limpiarBusqueda">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <button class="btn-filter" @click="toggleFiltros">
            <i class="fas fa-filter"></i>
            Filtros
            <span v-if="filtrosActivos > 0" class="filter-badge">{{ filtrosActivos }}</span>
          </button>

          <button class="btn-refresh" @click="cargarUsuarios" title="Actualizar">
            <i class="fas fa-sync-alt" :class="{ 'spinning': loading }"></i>
          </button>
        </div>

        <!-- Panel de filtros expandible -->
        <transition name="slide">
          <div v-if="mostrarFiltros" class="filtros-panel">
            <div class="filtros-grid">
              <div class="filter-group">
                <label>Estatus</label>
                <select v-model="filtros.estatus" @change="aplicarFiltros">
                  <option value="">Todos</option>
                  <option value="ACTIVO">Activo</option>
                  <option value="INACTIVO">Inactivo</option>
                </select>
              </div>

              <div class="filter-group">
                <label>Rol</label>
                <select v-model="filtros.rol" @change="aplicarFiltros">
                  <option value="">Todos</option>
                  <option v-for="rol in roles" :key="rol.Id" :value="rol.Id">
                    {{ rol.Nombre }}
                  </option>
                </select>
              </div>

              <div class="filter-group">
                <label>División</label>
                <select v-model="filtros.division" @change="aplicarFiltros">
                  <option value="">Todas</option>
                  <option v-for="division in divisiones" :key="division.Id" :value="division.Id">
                    {{ division.Nombre }}
                  </option>
                </select>
              </div>

              <div class="filter-group">
                <label>Unidad</label>
                <select v-model="filtros.unidad" @change="aplicarFiltros">
                  <option value="">Todas</option>
                  <option v-for="unidad in unidades" :key="unidad.id" :value="unidad.id">
                    {{ unidad.nombre_unidad }}
                  </option>
                </select>
              </div>
            </div>

            <div class="filtros-actions">
              <button class="btn-clear-filters" @click="limpiarFiltros">
                <i class="fas fa-times"></i> Limpiar Filtros
              </button>
            </div>
          </div>
        </transition>

        <!-- Resultados -->
        <div class="results-info">
          <p>
            Mostrando {{ usuariosPaginados.length }} de {{ usuariosFiltrados.length }} usuarios
            <span v-if="filtrosActivos > 0">({{ filtrosActivos }} filtro(s) aplicado(s))</span>
          </p>
        </div>

        <!-- Lista de usuarios -->
        <div class="usuarios-list">
          <div class="list-header">
            <div class="header-check">
              <input
                type="checkbox"
                v-model="seleccionarTodos"
                @change="toggleSeleccionTodos"
              />
            </div>
            <div @click="ordenarPor('Usuario')" class="sortable">
              Usuario
              <i class="fas" :class="getSortIcon('Usuario')"></i>
            </div>
            <div @click="ordenarPor('Nombre')" class="sortable">
              Nombre completo
              <i class="fas" :class="getSortIcon('Nombre')"></i>
            </div>
            <div @click="ordenarPor('NombreRol')" class="sortable">
              Rol
              <i class="fas" :class="getSortIcon('NombreRol')"></i>
            </div>
            <div @click="ordenarPor('Estatus')" class="sortable">
              Estatus
              <i class="fas" :class="getSortIcon('Estatus')"></i>
            </div>
            <div>Subordinados</div>
            <div>Acciones</div>
          </div>

          <div v-if="loading" class="loading-message">
            <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
          </div>

          <div v-else-if="usuariosPaginados.length === 0" class="empty-message">
            <i class="fas fa-users-slash"></i>
            <p>{{ usuariosFiltrados.length === 0 ? 'No hay usuarios registrados' : 'No se encontraron usuarios con los filtros aplicados' }}</p>
          </div>

          <div v-else v-for="usuario in usuariosPaginados" :key="usuario.Id" class="usuario-item">
            <div class="usuario-check">
              <input
                type="checkbox"
                :value="usuario.Id"
                v-model="usuariosSeleccionados"
              />
            </div>
            <div class="usuario-info">
              <p class="usuario-nombre">{{ usuario.Usuario }}</p>
            </div>
            <div class="usuario-info">
              <p>{{ usuario.Nombre }} {{ usuario.ApellidoP }} {{ usuario.ApellidoM }}</p>
            </div>
            <div class="usuario-info">
              <span class="badge-rol">{{ usuario.NombreRol || 'Sin rol' }}</span>
            </div>
            <div class="usuario-info">
              <span
                class="badge-status"
                :class="{ 'active': usuario.Estatus === 'ACTIVO', 'inactive': usuario.Estatus === 'INACTIVO' }"
              >
                {{ usuario.Estatus }}
              </span>
            </div>
            <div class="usuario-info">
              <span class="badge-subordinados">{{ obtenerSubordinados(usuario.Id) }}</span>
            </div>
            <div class="usuario-actions">
              <button class="action-btn view" @click="verDetalles(usuario)" title="Ver detalles">
                <i class="fas fa-eye"></i>
              </button>
              <button class="action-btn edit" @click="editarUsuario(usuario)" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <button
                class="action-btn toggle"
                @click="toggleEstatus(usuario)"
                :title="usuario.Estatus === 'ACTIVO' ? 'Desactivar' : 'Activar'"
              >
                <i class="fas" :class="usuario.Estatus === 'ACTIVO' ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
              </button>
              <button class="action-btn hierarchy" @click="gestionarJerarquia(usuario)" title="Gestionar jerarquía">
                <i class="fas fa-sitemap"></i>
              </button>
              <button class="action-btn delete" @click="confirmarEliminar(usuario)" title="Eliminar">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Acciones masivas -->
        <transition name="fade">
          <div v-if="usuariosSeleccionados.length > 0" class="acciones-masivas">
            <div class="acciones-info">
              <i class="fas fa-check-square"></i>
              <span>{{ usuariosSeleccionados.length }} seleccionado(s)</span>
            </div>
            <div class="acciones-buttons">
              <button class="btn-mass-action cancel" @click="deseleccionarTodos" title="Cancelar selección">
                <i class="fas fa-times-circle"></i>
              </button>
              <button class="btn-mass-action success" @click="activarSeleccionados" title="Activar">
                <i class="fas fa-check-circle"></i>
              </button>
              <button class="btn-mass-action warning" @click="desactivarSeleccionados" title="Desactivar">
                <i class="fas fa-ban"></i>
              </button>
              <button class="btn-mass-action danger" @click="eliminarSeleccionados" title="Eliminar">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </transition>

        <!-- Paginación -->
        <div v-if="usuariosFiltrados.length > itemsPorPagina" class="pagination">
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
            <option :value="100">100 / página</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar usuario -->
    <div v-if="showModal" class="modal-overlay" @click.self="cancelarAccion">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ modoEdicion ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
          <button class="close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="guardarUsuario">
            <div class="form-row">
              <div class="form-group">
                <label for="usuario">Usuario: *</label>
                <input
                  type="text"
                  id="usuario"
                  v-model="usuarioForm.Usuario"
                  required
                  :disabled="modoEdicion"
                />
              </div>

              <div class="form-group">
                <label for="password">Contraseña: {{ modoEdicion ? '' : '*' }}</label>
                <input
                  type="password"
                  id="password"
                  v-model="usuarioForm.Password"
                  :required="!modoEdicion"
                  :placeholder="modoEdicion ? 'Dejar en blanco para mantener la actual' : ''"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="nombre">Nombre: *</label>
                <input
                  type="text"
                  id="nombre"
                  v-model="usuarioForm.Nombre"
                  required
                />
              </div>

              <div class="form-group">
                <label for="apellidoP">Apellido Paterno:</label>
                <input
                  type="text"
                  id="apellidoP"
                  v-model="usuarioForm.ApellidoP"
                />
              </div>

              <div class="form-group">
                <label for="apellidoM">Apellido Materno:</label>
                <input
                  type="text"
                  id="apellidoM"
                  v-model="usuarioForm.ApellidoM"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="puesto">Puesto:</label>
                <input
                  type="text"
                  id="puesto"
                  v-model="usuarioForm.Puesto"
                />
              </div>

              <div class="form-group">
                <label for="estatus">Estatus: *</label>
                <select id="estatus" v-model="usuarioForm.Estatus">
                  <option value="ACTIVO">Activo</option>
                  <option value="INACTIVO">Inactivo</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="division">División Administrativa:</label>
                <select id="division" v-model="usuarioForm.IdDivisionAdm">
                  <option :value="null">Sin división</option>
                  <option v-for="division in divisiones" :key="division.Id" :value="division.Id">
                    {{ division.Nombre }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label for="unidad">Unidad:</label>
                <select id="unidad" v-model="usuarioForm.IdUnidad">
                  <option :value="null">Sin unidad</option>
                  <option v-for="unidad in unidades" :key="unidad.id" :value="unidad.id">
                    {{ unidad.nombre_unidad }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="rol">Rol del Sistema: *</label>
              <select id="rol" v-model="usuarioForm.IdRolSistema" required>
                <option value="">Seleccione un rol</option>
                <option v-for="rol in roles" :key="rol.Id" :value="rol.Id">
                  {{ rol.Nombre }}
                </option>
              </select>
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

    <!-- Modal de detalles de usuario -->
    <div v-if="showDetallesModal" class="modal-overlay" @click.self="showDetallesModal = false">
      <div class="modal-content modal-detalles">
        <div class="modal-header">
          <h3>Detalles del Usuario</h3>
          <button class="close-btn" @click="showDetallesModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="detalles-grid" v-if="usuarioDetalle">
            <div class="detalle-item">
              <label>Usuario:</label>
              <p>{{ usuarioDetalle.Usuario }}</p>
            </div>
            <div class="detalle-item">
              <label>Nombre Completo:</label>
              <p>{{ usuarioDetalle.Nombre }} {{ usuarioDetalle.ApellidoP }} {{ usuarioDetalle.ApellidoM }}</p>
            </div>
            <div class="detalle-item">
              <label>Puesto:</label>
              <p>{{ usuarioDetalle.Puesto || 'No especificado' }}</p>
            </div>
            <div class="detalle-item">
              <label>Rol:</label>
              <p><span class="badge-rol">{{ usuarioDetalle.NombreRol || 'Sin rol' }}</span></p>
            </div>
            <div class="detalle-item">
              <label>Estatus:</label>
              <p>
                <span
                  class="badge-status"
                  :class="{ 'active': usuarioDetalle.Estatus === 'ACTIVO', 'inactive': usuarioDetalle.Estatus === 'INACTIVO' }"
                >
                  {{ usuarioDetalle.Estatus }}
                </span>
              </p>
            </div>
            <div class="detalle-item">
              <label>División:</label>
              <p>{{ usuarioDetalle.NombreDivision || 'Sin división' }}</p>
            </div>
            <div class="detalle-item">
              <label>Unidad:</label>
              <p>{{ obtenerNombreUnidad(usuarioDetalle.IdUnidad) }}</p>
            </div>
            <div class="detalle-item">
              <label>Usuarios Superiores:</label>
              <p>{{ obtenerSuperiores(usuarioDetalle.Id) }}</p>
            </div>
            <div class="detalle-item">
              <label>Usuarios Subordinados:</label>
              <p>{{ obtenerSubordinados(usuarioDetalle.Id) }}</p>
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

    <!-- Modal para gestionar jerarquía -->
    <div v-if="showJerarquiaModal" class="modal-overlay" @click.self="cancelarAccion">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Gestionar Jerarquía: {{ usuarioSeleccionado?.Usuario }}</h3>
          <button class="close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="hierarchy-type-selector">
            <div class="hierarchy-selector">
              <button
                :class="['hierarchy-btn', { 'active': jerarquiaTab === 'superior' }]"
                @click="jerarquiaTab = 'superior'"
              >
                Usuarios Superiores
              </button>
              <button
                :class="['hierarchy-btn', { 'active': jerarquiaTab === 'subordinado' }]"
                @click="jerarquiaTab = 'subordinado'"
              >
                Usuarios Subordinados
              </button>
            </div>
          </div>

          <div v-if="jerarquiaTab === 'superior'" class="hierarchy-container">
            <h4>Usuarios Superiores a "{{ usuarioSeleccionado?.Usuario }}"</h4>
            <div class="role-selection">
              <div v-for="usuario in usuariosDisponiblesSuperiores" :key="usuario.Id" class="role-item">
                <input
                  type="checkbox"
                  :id="'sup-' + usuario.Id"
                  :value="usuario.Id"
                  v-model="usuariosSuperioresSeleccionados"
                />
                <label :for="'sup-' + usuario.Id">{{ usuario.Usuario }} - {{ usuario.Nombre }} {{ usuario.ApellidoP }}</label>
              </div>
              <div v-if="usuariosDisponiblesSuperiores.length === 0" class="empty-roles">
                No hay usuarios disponibles para seleccionar
              </div>
            </div>
          </div>

          <div v-if="jerarquiaTab === 'subordinado'" class="hierarchy-container">
            <h4>Usuarios Subordinados a "{{ usuarioSeleccionado?.Usuario }}"</h4>
            <div class="role-selection">
              <div v-for="usuario in usuariosDisponiblesSubordinados" :key="usuario.Id" class="role-item">
                <input
                  type="checkbox"
                  :id="'sub-' + usuario.Id"
                  :value="usuario.Id"
                  v-model="usuariosSubordinadosSeleccionados"
                />
                <label :for="'sub-' + usuario.Id">{{ usuario.Usuario }} - {{ usuario.Nombre }} {{ usuario.ApellidoP }}</label>
              </div>
              <div v-if="usuariosDisponiblesSubordinados.length === 0" class="empty-roles">
                No hay usuarios disponibles para seleccionar
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="cancelarAccion">
              Cancelar
            </button>
            <button type="button" class="btn-primary" @click="guardarJerarquia">
              Guardar Jerarquía
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div v-if="showConfirmModal" class="modal-overlay">
      <div class="modal-content confirm-modal">
        <div class="modal-header">
          <h3>Confirmar {{ tituloAccionMasiva }}</h3>
        </div>
        <div class="modal-body">
          <div v-if="accionMasiva === 'eliminar'">
            <p>¿Está seguro de que desea <strong>eliminar</strong> {{ usuariosSeleccionados.length }} usuario(s)?</p>
            <div class="usuarios-preview">
              <p class="preview-title">Usuarios a eliminar:</p>
              <ul class="usuarios-list-preview">
                <li v-for="id in usuariosSeleccionados.slice(0, 5)" :key="id">
                  {{ obtenerNombreUsuario(id) }}
                </li>
                <li v-if="usuariosSeleccionados.length > 5" class="more-items">
                  ... y {{ usuariosSeleccionados.length - 5 }} usuario(s) más
                </li>
              </ul>
            </div>
            <p class="warning-text">
              <i class="fas fa-exclamation-triangle"></i>
              Esta acción no se puede deshacer. Los usuarios serán eliminados permanentemente del sistema.
            </p>
          </div>

          <div v-else-if="accionMasiva === 'activar'">
            <p>¿Está seguro de que desea <strong>activar</strong> {{ usuariosSeleccionados.length }} usuario(s)?</p>
            <div class="usuarios-preview">
              <p class="preview-title">Usuarios a activar:</p>
              <ul class="usuarios-list-preview">
                <li v-for="id in usuariosSeleccionados.slice(0, 5)" :key="id">
                  {{ obtenerNombreUsuario(id) }}
                </li>
                <li v-if="usuariosSeleccionados.length > 5" class="more-items">
                  ... y {{ usuariosSeleccionados.length - 5 }} usuario(s) más
                </li>
              </ul>
            </div>
            <p class="info-text">
              <i class="fas fa-info-circle"></i>
              Los usuarios podrán acceder nuevamente al sistema.
            </p>
          </div>

          <div v-else-if="accionMasiva === 'desactivar'">
            <p>¿Está seguro de que desea <strong>desactivar</strong> {{ usuariosSeleccionados.length }} usuario(s)?</p>
            <div class="usuarios-preview">
              <p class="preview-title">Usuarios a desactivar:</p>
              <ul class="usuarios-list-preview">
                <li v-for="id in usuariosSeleccionados.slice(0, 5)" :key="id">
                  {{ obtenerNombreUsuario(id) }}
                </li>
                <li v-if="usuariosSeleccionados.length > 5" class="more-items">
                  ... y {{ usuariosSeleccionados.length - 5 }} usuario(s) más
                </li>
              </ul>
            </div>
            <p class="info-text">
              <i class="fas fa-info-circle"></i>
              Los usuarios no podrán acceder al sistema hasta que sean activados nuevamente.
            </p>
          </div>

          <div v-else>
            <p>¿Está seguro de que desea eliminar al usuario <strong>{{ usuarioEliminar?.Usuario }}</strong>?</p>
            <p class="warning-text">
              <i class="fas fa-exclamation-triangle"></i>
              Esta acción no se puede deshacer.
            </p>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="cancelarAccionMasiva">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button
              type="button"
              :class="accionMasiva === 'eliminar' || (!accionMasiva && usuarioEliminar) ? 'btn-danger' : 'btn-primary'"
              @click="ejecutarAccionMasiva"
            >
              <i class="fas" :class="getIconoAccion"></i>
              {{ getTextoBotonAccion }}
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
  name: 'UsuariosView',
  data() {
    return {
      loading: true,
      usuarios: [],
      usuariosFiltrados: [],
      jerarquias: [],
      roles: [],
      divisiones: [],
      unidades: [],
      showModal: false,
      showJerarquiaModal: false,
      showDetallesModal: false,
      showConfirmModal: false,
      modoEdicion: false,
      usuarioForm: {
        Id: null,
        Usuario: '',
        Nombre: '',
        ApellidoP: '',
        ApellidoM: '',
        Puesto: '',
        Estatus: 'ACTIVO',
        IdDivisionAdm: null,
        IdUnidad: null,
        IdRolSistema: null,
        Password: ''
      },
      usuarioEliminar: null,
      usuarioDetalle: null,
      usuarioSeleccionado: null,
      jerarquiaTab: 'subordinado',
      usuariosSuperioresSeleccionados: [],
      usuariosSubordinadosSeleccionados: [],
      backendUrl: import.meta.env.VITE_API_URL,

      // Filtros y búsqueda
      filtros: {
        busqueda: '',
        estatus: '',
        rol: '',
        division: '',
        unidad: ''
      },
      mostrarFiltros: false,

      // Ordenamiento
      ordenamiento: {
        campo: 'Usuario',
        direccion: 'asc'
      },

      // Paginación
      paginaActual: 1,
      itemsPorPagina: 25,

      // Selección múltiple
      usuariosSeleccionados: [],
      seleccionarTodos: false,
      accionMasiva: null // Puede ser 'eliminar', 'activar', 'desactivar', o null
    };
  },
  computed: {
    usuariosActivos() {
      return this.usuarios.filter(u => u.Estatus === 'ACTIVO').length;
    },
    usuariosInactivos() {
      return this.usuarios.filter(u => u.Estatus === 'INACTIVO').length;
    },
    rolesUnicos() {
      return new Set(this.usuarios.map(u => u.IdRolSistema)).size;
    },
    filtrosActivos() {
      let count = 0;
      if (this.filtros.busqueda) count++;
      if (this.filtros.estatus) count++;
      if (this.filtros.rol) count++;
      if (this.filtros.division) count++;
      if (this.filtros.unidad) count++;
      return count;
    },
    usuariosPaginados() {
      const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
      const fin = inicio + this.itemsPorPagina;
      return this.usuariosFiltrados.slice(inicio, fin);
    },
    totalPaginas() {
      return Math.ceil(this.usuariosFiltrados.length / this.itemsPorPagina);
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
      if (this.accionMasiva === 'activar') return 'Activación Masiva';
      if (this.accionMasiva === 'desactivar') return 'Desactivación Masiva';
      return 'Confirmar eliminación';
    },
    usuariosDisponiblesSuperiores() {
      if (!this.usuarioSeleccionado) return [];
      return this.usuarios.filter(usuario =>
        usuario.Id !== this.usuarioSeleccionado.Id &&
        !this.esParteDeCiclo(this.usuarioSeleccionado.Id, usuario.Id)
      );
    },
    usuariosDisponiblesSubordinados() {
      if (!this.usuarioSeleccionado) return [];
      return this.usuarios.filter(usuario =>
        usuario.Id !== this.usuarioSeleccionado.Id &&
        !this.esParteDeCiclo(usuario.Id, this.usuarioSeleccionado.Id)
      );
    },
    getIconoAccion() {
      if (this.accionMasiva === 'eliminar' || (!this.accionMasiva && this.usuarioEliminar)) return 'fa-trash';
      if (this.accionMasiva === 'activar') return 'fa-check-circle';
      if (this.accionMasiva === 'desactivar') return 'fa-ban';
      return 'fa-check';
    },
    getTextoBotonAccion() {
      if (this.accionMasiva === 'eliminar' || (!this.accionMasiva && this.usuarioEliminar)) return 'Eliminar';
      if (this.accionMasiva === 'activar') return 'Activar';
      if (this.accionMasiva === 'desactivar') return 'Desactivar';
      return 'Confirmar';
    }
  },
  created() {
    this.cargarDatos();
  },
  methods: {
    async cargarDatos() {
      await Promise.all([
        this.cargarUsuarios(),
        this.cargarJerarquias(),
        this.cargarRoles(),
        this.cargarDivisiones(),
        this.cargarUnidades()
      ]);
    },
    async cargarUsuarios() {
      try {
        this.loading = true;
        const response = await axios.get(`${this.backendUrl}/usuarios.php`);
        this.usuarios = response.data.records || [];
        this.aplicarFiltros();
        this.loading = false;
      } catch (error) {
        console.error('Error al cargar usuarios:', error);
        this.loading = false;
        if (this.$toast) {
          this.$toast.error('Error al cargar usuarios');
        }
      }
    },
    async cargarRoles() {
      try {
        const response = await axios.get(`${this.backendUrl}/roles.php`);
        this.roles = response.data.records || [];
      } catch (error) {
        console.error('Error al cargar roles:', error);
        if (this.$toast) {
          this.$toast.error('Error al cargar roles');
        }
      }
    },
    async cargarDivisiones() {
      try {
        const response = await axios.get(`${this.backendUrl}/divisiones.php`);
        // ✅ Manejar tanto 'records' como respuesta directa
        this.divisiones = response.data.records || response.data || [];
      } catch (error) {
        console.error('Error al cargar divisiones:', error);
        this.divisiones = [];
      }
    },
    async cargarUnidades() {
      try {
        const response = await axios.get(`${this.backendUrl}/unidades.php`);
        this.unidades = response.data.records || [];
      } catch (error) {
        console.error('Error al cargar unidades:', error);
        if (this.$toast) {
          this.$toast.error('Error al cargar unidades');
        }
      }
    },
    async cargarJerarquias() {
      try {
        const response = await axios.get(`${this.backendUrl}/jerarquias.php`);
        this.jerarquias = response.data.records || [];
      } catch (error) {
        console.error('Error al cargar jerarquías:', error);
        if (this.$toast) {
          this.$toast.error('Error al cargar jerarquías');
        }
      }
    },
    crearNuevoUsuario() {
      this.modoEdicion = false;
      this.usuarioForm = {
        Id: null,
        Usuario: '',
        Nombre: '',
        ApellidoP: '',
        ApellidoM: '',
        Puesto: '',
        Estatus: 'ACTIVO',
        IdDivisionAdm: null,
        IdUnidad: null,
        IdRolSistema: null,
        Password: ''
      };
      this.showModal = true;
    },
    editarUsuario(usuario) {
      this.modoEdicion = true;
      // Asegurarse de que los valores nulos se preserven como nulos
      this.usuarioForm = {
        ...usuario,
        Password: '',
        IdDivisionAdm: usuario.IdDivisionAdm || null,
        IdUnidad: usuario.IdUnidad || null
      };
      this.showModal = true;
    },
    async guardarUsuario() {
      try {
        // Crear una copia del formulario para enviar
        const formData = {...this.usuarioForm};

        // Convertir valores "null" (string) a null (valor)
        if (formData.IdDivisionAdm === "null") formData.IdDivisionAdm = null;
        if (formData.IdUnidad === "null") formData.IdUnidad = null;

        // Verificar si es una actualización o creación
        if (this.modoEdicion) {
          // Actualizar usuario existente
          await axios.put(`${this.backendUrl}/usuarios.php`, formData);
          if (this.$toast) {
            this.$toast.success('Usuario actualizado correctamente');
          } else {
            alert('Usuario actualizado correctamente');
          }
        } else {
          // Crear nuevo usuario
          await axios.post(`${this.backendUrl}/usuarios.php`, formData);
          if (this.$toast) {
            this.$toast.success('Usuario creado correctamente');
          } else {
            alert('Usuario creado correctamente');
          }
        }

        // Recargar usuarios y cerrar modal
        this.showModal = false;
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al guardar usuario:', error);
        if (this.$toast) {
          this.$toast.error('Error al guardar el usuario');
        } else {
          alert('Error al guardar el usuario');
        }
      }
    },
    confirmarEliminar(usuario) {
      this.usuarioEliminar = usuario;
      this.accionMasiva = null;
      this.showConfirmModal = true;
    },
    async eliminarUsuario() {
      try {
        await axios.delete(`${this.backendUrl}/usuarios.php`, {
          data: { Id: this.usuarioEliminar.Id }
        });

        if (this.$toast) {
          this.$toast.success('Usuario eliminado correctamente');
        } else {
          alert('Usuario eliminado correctamente');
        }
        this.showConfirmModal = false;
        this.accionMasiva = null;
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al eliminar usuario:', error);
        if (this.$toast) {
          this.$toast.error('Error al eliminar el usuario');
        } else {
          alert('Error al eliminar el usuario');
        }
      }
    },
    aplicarFiltros() {
      let resultado = [...this.usuarios];

      // Filtro de búsqueda
      if (this.filtros.busqueda) {
        const busqueda = this.filtros.busqueda.toLowerCase();
        resultado = resultado.filter(u =>
          u.Usuario.toLowerCase().includes(busqueda) ||
          u.Nombre.toLowerCase().includes(busqueda) ||
          (u.ApellidoP && u.ApellidoP.toLowerCase().includes(busqueda)) ||
          (u.ApellidoM && u.ApellidoM.toLowerCase().includes(busqueda)) ||
          (u.NombreRol && u.NombreRol.toLowerCase().includes(busqueda))
        );
      }

      // Filtro de estatus
      if (this.filtros.estatus) {
        resultado = resultado.filter(u => u.Estatus === this.filtros.estatus);
      }

      // Filtro de rol
      if (this.filtros.rol) {
        resultado = resultado.filter(u => u.IdRolSistema === parseInt(this.filtros.rol));
      }

      // Filtro de división
      if (this.filtros.division) {
        resultado = resultado.filter(u => u.IdDivisionAdm === parseInt(this.filtros.division));
      }

      // Filtro de unidad
      if (this.filtros.unidad) {
        resultado = resultado.filter(u => u.IdUnidad === parseInt(this.filtros.unidad));
      }

      // Aplicar ordenamiento
      resultado.sort((a, b) => {
        const valorA = a[this.ordenamiento.campo] || '';
        const valorB = b[this.ordenamiento.campo] || '';

        if (this.ordenamiento.direccion === 'asc') {
          return valorA > valorB ? 1 : -1;
        } else {
          return valorA < valorB ? 1 : -1;
        }
      });

      this.usuariosFiltrados = resultado;
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
    toggleFiltros() {
      this.mostrarFiltros = !this.mostrarFiltros;
    },
    limpiarBusqueda() {
      this.filtros.busqueda = '';
      this.aplicarFiltros();
    },
    limpiarFiltros() {
      this.filtros = {
        busqueda: '',
        estatus: '',
        rol: '',
        division: '',
        unidad: ''
      };
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
        this.usuariosSeleccionados = this.usuariosPaginados.map(u => u.Id);
      } else {
        this.usuariosSeleccionados = [];
      }
    },
    verDetalles(usuario) {
      this.usuarioDetalle = usuario;
      this.showDetallesModal = true;
    },
    editarDesdeDetalles() {
      this.showDetallesModal = false;
      this.editarUsuario(this.usuarioDetalle);
    },
    obtenerNombreUnidad(idUnidad) {
      if (!idUnidad) return 'Sin unidad';
      const unidad = this.unidades.find(u => u.id === idUnidad);
      return unidad ? unidad.nombre_unidad : 'Sin unidad';
    },
    async toggleEstatus(usuario) {
      try {
        const nuevoEstatus = usuario.Estatus === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        await axios.put(`${this.backendUrl}/usuarios.php`, {
          ...usuario,
          Estatus: nuevoEstatus,
          Password: undefined
        });

        if (this.$toast) {
          this.$toast.success(`Usuario ${nuevoEstatus === 'ACTIVO' ? 'activado' : 'desactivado'} correctamente`);
        }
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al cambiar estatus:', error);
        if (this.$toast) {
          this.$toast.error('Error al cambiar el estatus del usuario');
        }
      }
    },
    async activarSeleccionados() {
      this.accionMasiva = 'activar';
      this.showConfirmModal = true;
    },
    async desactivarSeleccionados() {
      this.accionMasiva = 'desactivar';
      this.showConfirmModal = true;
    },
    eliminarSeleccionados() {
      this.accionMasiva = 'eliminar';
      this.showConfirmModal = true;
    },
    ejecutarAccionMasiva() {
      if (this.accionMasiva === 'eliminar') {
        this.eliminarUsuariosMasivo();
      } else if (this.accionMasiva === 'activar') {
        this.cambiarEstatusSeleccionados('ACTIVO');
      } else if (this.accionMasiva === 'desactivar') {
        this.cambiarEstatusSeleccionados('INACTIVO');
      } else if (this.usuarioEliminar) {
        this.eliminarUsuario();
      }
    },
    cancelarAccionMasiva() {
      this.showConfirmModal = false;
      this.accionMasiva = null;
      this.usuarioEliminar = null;
    },
    obtenerNombreUsuario(id) {
      const usuario = this.usuarios.find(u => u.Id === id);
      if (!usuario) return 'Usuario desconocido';
      return `${usuario.Usuario} - ${usuario.Nombre} ${usuario.ApellidoP || ''}`.trim();
    },
    async cambiarEstatusSeleccionados(nuevoEstatus) {
      try {
        const promesas = this.usuariosSeleccionados.map(id => {
          const usuario = this.usuarios.find(u => u.Id === id);
          return axios.put(`${this.backendUrl}/usuarios.php`, {
            ...usuario,
            Estatus: nuevoEstatus,
            Password: undefined
          });
        });

        await Promise.all(promesas);

        if (this.$toast) {
          this.$toast.success(`${this.usuariosSeleccionados.length} usuario(s) ${nuevoEstatus === 'ACTIVO' ? 'activados' : 'desactivados'} correctamente`);
        }

        this.showConfirmModal = false;
        this.accionMasiva = null;
        this.usuariosSeleccionados = [];
        this.seleccionarTodos = false;
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al cambiar estatus:', error);
        if (this.$toast) {
          this.$toast.error('Error al cambiar el estatus de los usuarios');
        }
      }
    },
    async eliminarUsuariosMasivo() {
      try {
        const promesas = this.usuariosSeleccionados.map(id =>
          axios.delete(`${this.backendUrl}/usuarios.php`, {
            data: { Id: id }
          })
        );

        await Promise.all(promesas);

        if (this.$toast) {
          this.$toast.success(`${this.usuariosSeleccionados.length} usuario(s) eliminados correctamente`);
        }

        this.showConfirmModal = false;
        this.usuariosSeleccionados = [];
        this.seleccionarTodos = false;
        this.accionMasiva = null;
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al eliminar usuarios:', error);
        if (this.$toast) {
          this.$toast.error('Error al eliminar los usuarios');
        }
      }
    },
    exportarUsuarios() {
      // Preparar datos para exportar
      const datosExportar = this.usuariosFiltrados.map(u => ({
        'Usuario': u.Usuario,
        'Nombre': u.Nombre,
        'Apellido Paterno': u.ApellidoP || '',
        'Apellido Materno': u.ApellidoM || '',
        'Puesto': u.Puesto || '',
        'Rol': u.NombreRol || '',
        'División': u.NombreDivision || '',
        'Unidad': this.obtenerNombreUnidad(u.IdUnidad),
        'Estatus': u.Estatus
      }));

      // Convertir a CSV
      const headers = Object.keys(datosExportar[0]);
      const csvContent = [
        headers.join(','),
        ...datosExportar.map(row =>
          headers.map(header => `"${row[header]}"`).join(',')
        )
      ].join('\n');

      // Descargar archivo
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `usuarios_${new Date().toISOString().split('T')[0]}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      if (this.$toast) {
        this.$toast.success('Usuarios exportados correctamente');
      }
    },
    gestionarJerarquia(usuario) {
      this.usuarioSeleccionado = usuario;
      this.showJerarquiaModal = true;

      // Cargar los usuarios superiores seleccionados
      this.usuariosSuperioresSeleccionados = this.jerarquias
        .filter(j => j.IdUsuarioSubordinado === usuario.Id)
        .map(j => j.IdUsuarioSuperior);

      // Cargar los usuarios subordinados seleccionados
      this.usuariosSubordinadosSeleccionados = this.jerarquias
        .filter(j => j.IdUsuarioSuperior === usuario.Id)
        .map(j => j.IdUsuarioSubordinado);

      // Por defecto mostrar la pestaña de subordinados
      this.jerarquiaTab = 'subordinado';
    },
    async guardarJerarquia() {
      try {
        // Preparar datos para enviar
        const data = {
          usuarioId: this.usuarioSeleccionado.Id,
          superiores: this.usuariosSuperioresSeleccionados,
          subordinados: this.usuariosSubordinadosSeleccionados
        };

        // Guardar jerarquías
        await axios.post(`${this.backendUrl}/jerarquias.php`, data);

        if (this.$toast) {
          this.$toast.success('Jerarquía actualizada correctamente');
        } else {
          alert('Jerarquía actualizada correctamente');
        }

        // Recargar jerarquías y cerrar modal
        this.showJerarquiaModal = false;
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al guardar jerarquía:', error);
        if (this.$toast) {
          this.$toast.error('Error al guardar la jerarquía');
        } else {
          alert('Error al guardar la jerarquía');
        }
      }
    },
    esParteDeCiclo(usuarioIdInicio, usuarioIdPotencial) {
      // Verifica si añadir usuarioIdPotencial como superior de usuarioIdInicio crearía un ciclo
      const visitados = new Set();
      const porVisitar = [usuarioIdPotencial];

      while (porVisitar.length > 0) {
        const usuarioActual = porVisitar.pop();

        if (usuarioActual === usuarioIdInicio) {
          return true; // Se encontró un ciclo
        }

        if (!visitados.has(usuarioActual)) {
          visitados.add(usuarioActual);

          // Añadir todos los usuarios subordinados para seguir buscando
          const subordinados = this.jerarquias
            .filter(j => j.IdUsuarioSuperior === usuarioActual)
            .map(j => j.IdUsuarioSubordinado);

          porVisitar.push(...subordinados);
        }
      }

      return false; // No se encontró ciclo
    },
    cancelarAccion() {
      this.showModal = false;
      this.showJerarquiaModal = false;
      this.showDetallesModal = false;
      this.cancelarAccionMasiva();
    },
    deseleccionarTodos() {
      this.usuariosSeleccionados = [];
      this.seleccionarTodos = false;
    },
    obtenerSubordinados(usuarioId) {
      // Obtener IDs de usuarios subordinados
      const subordinadosIds = this.jerarquias
        .filter(j => j.IdUsuarioSuperior === usuarioId)
        .map(j => j.IdUsuarioSubordinado);

      // Obtener nombres de usuarios subordinados
      const subordinadosNombres = this.usuarios
        .filter(u => subordinadosIds.includes(u.Id))
        .map(u => u.Usuario);

      return subordinadosNombres.length > 0 ? subordinadosNombres.join(', ') : 'Ninguno';
    },
    obtenerSuperiores(usuarioId) {
      // Obtener IDs de usuarios superiores
      const superioresIds = this.jerarquias
        .filter(j => j.IdUsuarioSubordinado === usuarioId)
        .map(j => j.IdUsuarioSuperior);

      // Obtener nombres de usuarios superiores
      const superioresNombres = this.usuarios
        .filter(u => superioresIds.includes(u.Id))
        .map(u => u.Usuario);

      return superioresNombres.length > 0 ? superioresNombres.join(', ') : 'Ninguno';
    }
  }
}
</script>

<style scoped>
.usuarios-container {
  padding: 20px;
}

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
  color: white;
  font-size: 18px;
}

.card-body {
  padding: 20px;
}

/* Estadísticas */
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
.stat-icon.active { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
.stat-icon.inactive { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.stat-icon.roles { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }

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

.btn-filter {
  background: white;
  border: 2px solid #e5e7eb;
  padding: 10px 20px;
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
}

.btn-filter:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.filter-badge {
  background: var(--primary-color);
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

/* Panel de filtros */
.filtros-panel {
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.filtros-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.filter-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  font-size: 14px;
  color: var(--secondary-color);
}

.filter-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
}

.filtros-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-clear-filters {
  background: #ef4444;
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  transition: all 0.3s;
}

.btn-clear-filters:hover {
  background: #dc2626;
}

/* Resultados */
.results-info {
  margin-bottom: 15px;
  font-size: 14px;
  color: #666;
}

.results-info span {
  color: var(--primary-color);
  font-weight: 500;
}

/* Lista */
.usuarios-list {
  margin-top: 20px;
  border-radius: 8px;
  overflow: hidden;
  background-color: var(--white-color);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.list-header {
  display: grid;
  grid-template-columns: 40px 1fr 1.5fr 1fr 0.8fr 1fr 0.8fr;
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

.usuario-item {
  display: grid;
  grid-template-columns: 40px 1fr 1.5fr 1fr 0.8fr 1fr 0.8fr;
  padding: 15px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  transition: var(--transition);
}

.usuario-item:hover {
  background-color: rgba(22, 84, 177, 0.05);
}

.usuario-check {
  display: flex;
  align-items: center;
  justify-content: center;
}

.usuario-info {
  display: flex;
  align-items: center;
}

.usuario-info p {
  margin: 0;
  color: var(--secondary-color);
}

.usuario-nombre {
  font-weight: 600;
  color: var(--primary-color);
}

.badge-rol {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
}

.badge-status {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  display: inline-block;
}

.badge-status.active {
  background: #d1fae5;
  color: #065f46;
}

.badge-status.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.badge-subordinados {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.usuario-actions {
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

.action-btn.toggle {
  background-color: #06b6d4;
  color: white;
}

.action-btn.hierarchy {
  background-color: #5bc0de;
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

/* Acciones masivas - Versión más delgada */
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

.acciones-info span {
  white-space: nowrap;
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
  position: relative;
}

.btn-mass-action::before {
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

.btn-mass-action:hover::before {
  transform: translateX(-50%) scale(1);
  opacity: 1;
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

.btn-mass-action.success {
  background: #10b981;
}

.btn-mass-action.success:hover {
  background: #059669;
  transform: scale(1.1);
}

.btn-mass-action.warning {
  background: #f59e0b;
}

.btn-mass-action.warning:hover {
  background: #d97706;
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

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
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

/* Modal de confirmación mejorado */
.confirm-modal {
  max-width: 500px;
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

.info-text {
  background: #dbeafe;
  border-left: 4px solid #3b82f6;
  padding: 12px;
  margin: 15px 0;
  color: #1e40af;
  display: flex;
  align-items: center;
  gap: 10px;
  border-radius: 4px;
  font-size: 14px;
}

.info-text i {
  color: #3b82f6;
  font-size: 18px;
}

.btn-primary i,
.btn-danger i,
.btn-secondary i {
  margin-right: 5px;
}

/* Animaciones */
.slide-enter-active, .slide-leave-active {
  transition: all 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}

.slide-enter-from, .slide-leave-to {
  max-height: 0;
  opacity: 0;
}

.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Responsive */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .list-header, .usuario-item {
    grid-template-columns: 40px 1fr 1fr 100px;
  }

  .list-header div:nth-child(3),
  .usuario-item > div:nth-child(3),
  .list-header div:nth-child(4),
  .usuario-item > div:nth-child(4),
  .list-header div:nth-child(6),
  .usuario-item > div:nth-child(6) {
    display: none;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .search-filter-container {
    flex-direction: column;
  }

  .header-actions {
    flex-direction: column;
  }

  .filtros-grid {
    grid-template-columns: 1fr;
  }

  /* Responsive para acciones masivas */
  .acciones-masivas {
    bottom: 20px;
    padding: 6px 12px;
    border-radius: 40px;
  }

  .acciones-info {
    font-size: 12px;
    padding: 0 6px;
  }

  .acciones-info i {
    font-size: 13px;
  }

  .btn-mass-action {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }

  .acciones-buttons {
    gap: 4px;
  }
}

@media (max-width: 480px) {
  .acciones-masivas {
    width: calc(100% - 32px);
    max-width: 400px;
  }

  .acciones-info span {
    display: none;
  }

  .btn-mass-action {
    width: 28px;
    height: 28px;
    font-size: 12px;
  }
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
  border: 1px solid rgba(0,  0, 0, 0.1);
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
</style>
