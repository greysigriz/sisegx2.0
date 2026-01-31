<template>
  <div class="usuarios-container">
    <!-- Estadísticas -->
    <div class="usuarios-stats-grid">
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-icon total">
          <i class="fas fa-users"></i>
        </div>
        <div class="usuarios-stat-info">
          <p class="usuarios-stat-value">{{ usuarios.length }}</p>
          <p class="usuarios-stat-label">Total Usuarios</p>
        </div>
      </div>
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-icon active">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="usuarios-stat-info">
          <p class="usuarios-stat-value">{{ usuariosActivos }}</p>
          <p class="usuarios-stat-label">Activos</p>
        </div>
      </div>
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-icon inactive">
          <i class="fas fa-user-times"></i>
        </div>
        <div class="usuarios-stat-info">
          <p class="usuarios-stat-value">{{ usuariosInactivos }}</p>
          <p class="usuarios-stat-label">Inactivos</p>
        </div>
      </div>
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-icon roles">
          <i class="fas fa-user-tag"></i>
        </div>
        <div class="usuarios-stat-info">
          <p class="usuarios-stat-value">{{ rolesUnicos }}</p>
          <p class="usuarios-stat-label">Roles Diferentes</p>
        </div>
      </div>
    </div>

    <div class="usuarios-card">
      <div class="usuarios-card-header">
        <h3>Gestión de Usuarios</h3>
        <div class="usuarios-header-actions">
          <button class="usuarios-btn-export" @click="exportarUsuarios" title="Exportar a Excel">
            <i class="fas fa-file-excel"></i> Exportar
          </button>
          <button class="usuarios-btn-primary" @click="crearNuevoUsuario">
            <i class="fas fa-plus"></i> Nuevo Usuario
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
              placeholder="Buscar por usuario, nombre, apellido..."
              @input="aplicarFiltros"
            />
            <button v-if="filtros.busqueda" class="usuarios-clear-search" @click="limpiarBusqueda">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="usuarios-filtros-refresh-container">
            <button class="usuarios-btn-filter" @click="toggleFiltros">
              <i class="fas fa-filter"></i>
              Filtros
              <span v-if="filtrosActivos > 0" class="usuarios-filter-badge">{{ filtrosActivos }}</span>
            </button>

            <button class="usuarios-btn-refresh" @click="cargarUsuarios" title="Actualizar">
              <i class="fas fa-sync-alt" :class="{ 'spinning': loading }"></i>
            </button>
          </div>
        </div>

        <!-- Panel de filtros expandible -->
        <transition name="slide">
          <div v-if="mostrarFiltros" class="usuarios-filtros-panel">
            <div class="usuarios-filtros-grid">
              <div class="usuarios-filter-group">
                <label>Estatus</label>
                <select v-model="filtros.estatus" @change="aplicarFiltros">
                  <option value="">Todos</option>
                  <option value="ACTIVO">Activo</option>
                  <option value="INACTIVO">Inactivo</option>
                </select>
              </div>

              <div class="usuarios-filter-group">
                <label>Rol</label>
                <select v-model="filtros.rol" @change="aplicarFiltros">
                  <option value="">Todos</option>
                  <option v-for="rol in roles" :key="rol.Id" :value="rol.Id">
                    {{ rol.Nombre }}
                  </option>
                </select>
              </div>

              <div class="usuarios-filter-group">
                <label>División</label>
                <select v-model="filtros.division" @change="aplicarFiltros">
                  <option value="">Todas</option>
                  <option v-for="division in divisiones" :key="division.Id" :value="division.Id">
                    {{ division.Nombre }}
                  </option>
                </select>
              </div>

              <div class="usuarios-filter-group">
                <label>Unidad</label>
                <select v-model="filtros.unidad" @change="aplicarFiltros">
                  <option value="">Todas</option>
                  <option v-for="unidad in unidades" :key="unidad.id" :value="unidad.id">
                    {{ unidad.nombre_unidad }}
                  </option>
                </select>
              </div>
            </div>

            <div class="usuarios-filtros-actions">
              <button class="usuarios-btn-clear-filters" @click="limpiarFiltros">
                <i class="fas fa-times"></i> Limpiar Filtros
              </button>
            </div>
          </div>
        </transition>

        <!-- Resultados -->
        <div class="usuarios-results-info">
          <p>
            Mostrando {{ usuariosPaginados.length }} de {{ usuariosFiltrados.length }} usuarios
            <span v-if="filtrosActivos > 0">({{ filtrosActivos }} filtro(s) aplicado(s))</span>
          </p>
        </div>

        <!-- Tabla de usuarios -->
        <div class="table-wrapper">
          <div v-if="loading" class="loading-message">
            <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
          </div>

          <div v-else-if="usuariosPaginados.length === 0" class="empty-message">
            <i class="fas fa-users-slash"></i>
            <p>{{ usuariosFiltrados.length === 0 ? 'No hay usuarios registrados' : 'No se encontraron usuarios con los filtros aplicados' }}</p>
          </div>

          <table v-else class="usuarios-table">
            <thead>
              <tr>
                <th class="checkbox-column">
                  <input
                    type="checkbox"
                    v-model="seleccionarTodos"
                    @change="toggleSeleccionTodos"
                  />
                </th>
                <th @click="ordenarPor('Usuario')" class="sortable">
                  Usuario
                  <i class="fas" :class="getSortIcon('Usuario')"></i>
                </th>
                <th @click="ordenarPor('Nombre')" class="sortable">
                  Nombre completo
                  <i class="fas" :class="getSortIcon('Nombre')"></i>
                </th>
                <th @click="ordenarPor('NombreRol')" class="sortable">
                  Rol
                  <i class="fas" :class="getSortIcon('NombreRol')"></i>
                </th>
                <th @click="ordenarPor('Estatus')" class="sortable">
                  Estatus
                  <i class="fas" :class="getSortIcon('Estatus')"></i>
                </th>
                <th>División</th>
                <th>Unidad</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="usuario in usuariosPaginados" :key="usuario.Id" class="usuario-row">
                <td class="checkbox-column">
                  <input
                    type="checkbox"
                    :value="usuario.Id"
                    v-model="usuariosSeleccionados"
                  />
                </td>
                <td class="usuario-column">
                  <span class="usuario-nombre">{{ usuario.Usuario }}</span>
                </td>
                <td class="nombre-column">
                  {{ usuario.Nombre }} {{ usuario.ApellidoP }} {{ usuario.ApellidoM }}
                </td>
                <td class="rol-column">
                  <div class="roles-container" v-if="usuario.Roles && usuario.Roles.length > 0">
                    <span
                      v-for="rol in usuario.Roles"
                      :key="rol.Id"
                      class="badge-rol"
                      :title="rol.Descripcion"
                    >
                      {{ rol.Nombre }}
                    </span>
                  </div>
                  <span v-else class="badge-rol badge-sin-rol">Sin rol</span>
                </td>
                <td class="estatus-column">
                  <span
                    class="badge-status"
                    :class="{ 'active': usuario.Estatus === 'ACTIVO', 'inactive': usuario.Estatus === 'INACTIVO' }"
                  >
                    {{ usuario.Estatus }}
                  </span>
                </td>
                <td class="division-column">
                  <span class="badge-division">{{ obtenerNombreDivision(usuario.IdDivisionAdm) }}</span>
                </td>
                <td class="unidad-column">
                  <span class="badge-unidad">{{ obtenerNombreUnidad(usuario.IdUnidad) }}</span>
                </td>
                <td class="actions-column">
                  <div class="action-buttons">
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
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Acciones masivas -->
        <transition name="fade">
          <div v-if="usuariosSeleccionados.length > 0" class="usuarios-acciones-masivas">
            <div class="usuarios-acciones-info">
              <i class="fas fa-check-square"></i>
              <span>{{ usuariosSeleccionados.length }} seleccionado(s)</span>
            </div>
            <div class="usuarios-acciones-buttons">
              <button class="usuarios-btn-mass-action cancel" @click="deseleccionarTodos" title="Cancelar Selección">
                <i class="fas fa-times-circle"></i>
              </button>
              <button class="usuarios-btn-mass-action success" @click="activarSeleccionados" title="Activar">
                <i class="fas fa-check-circle"></i>
              </button>
              <button class="usuarios-btn-mass-action warning" @click="desactivarSeleccionados" title="Desactivar">
                <i class="fas fa-ban"></i>
              </button>
              <button class="usuarios-btn-mass-action danger" @click="eliminarSeleccionados" title="Eliminar">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </transition>

        <!-- paginación -->
        <div v-if="usuariosFiltrados.length > itemsPorPagina" class="usuarios-pagination">
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
            <option :value="10">10 / pÃ¡gina</option>
            <option :value="25">25 / pÃ¡gina</option>
            <option :value="50">50 / pÃ¡gina</option>
            <option :value="100">100 / pÃ¡gina</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar usuario -->
    <div v-if="showModal" class="usuarios-modal-overlay" @click.self="cancelarAccion">
      <div class="usuarios-modal-content">
        <div class="usuarios-modal-header">
          <h3>{{ modoEdicion ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
          <button class="usuarios-close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="usuarios-modal-body">
          <form @submit.prevent="guardarUsuario">
            <div class="usuarios-form-row">
              <div class="usuarios-form-group">
                <label for="usuario">Usuario: *</label>
                <input
                  type="text"
                  id="usuario"
                  v-model="usuarioForm.Usuario"
                  required
                  :disabled="modoEdicion"
                />
              </div>

              <div class="usuarios-form-group">
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

            <div class="usuarios-form-row">
              <div class="usuarios-form-group">
                <label for="nombre">Nombre: *</label>
                <input
                  type="text"
                  id="nombre"
                  v-model="usuarioForm.Nombre"
                  required
                />
              </div>

              <div class="usuarios-form-group">
                <label for="apellidoP">Apellido Paterno:</label>
                <input
                  type="text"
                  id="apellidoP"
                  v-model="usuarioForm.ApellidoP"
                />
              </div>

              <div class="usuarios-form-group">
                <label for="apellidoM">Apellido Materno:</label>
                <input
                  type="text"
                  id="apellidoM"
                  v-model="usuarioForm.ApellidoM"
                />
              </div>
            </div>

            <div class="usuarios-form-row">
              <div class="usuarios-form-group">
                <label for="puesto">Puesto:</label>
                <input
                  type="text"
                  id="puesto"
                  v-model="usuarioForm.Puesto"
                />
              </div>

              <div class="usuarios-form-group">
                <label for="estatus">Estatus: *</label>
                <select id="estatus" v-model="usuarioForm.Estatus">
                  <option value="ACTIVO">Activo</option>
                  <option value="INACTIVO">Inactivo</option>
                </select>
              </div>
            </div>

            <div class="usuarios-form-row">
              <div class="usuarios-form-group">
                <label for="division">División Administrativa:</label>
                <select id="division" v-model="usuarioForm.IdDivisionAdm">
                  <option :value="null">Sin División</option>
                  <option v-for="division in divisiones" :key="division.Id" :value="division.Id">
                    {{ division.Nombre }}
                  </option>
                </select>
              </div>

              <div class="usuarios-form-group">
                <label for="unidad">Unidad:</label>
                <select id="unidad" v-model="usuarioForm.IdUnidad">
                  <option :value="null">Sin unidad</option>
                  <option v-for="unidad in unidades" :key="unidad.id" :value="unidad.id">
                    {{ unidad.nombre_unidad }}
                  </option>
                </select>
              </div>
            </div>

            <div class="usuarios-form-group">
              <label>Roles del Sistema: *</label>
              <div class="usuarios-roles-selection">
                <div
                  v-for="rol in roles"
                  :key="rol.Id"
                  class="usuarios-role-checkbox-item"
                >
                  <input
                    type="checkbox"
                    :id="'rol-' + rol.Id"
                    :value="rol.Id"
                    v-model="usuarioForm.RolesSeleccionados"
                  />
                  <label :for="'rol-' + rol.Id">
                    <span class="usuarios-rol-nombre">{{ rol.Nombre }}</span>
                    <span v-if="rol.Descripcion" class="usuarios-rol-descripcion">{{ rol.Descripcion }}</span>
                  </label>
                </div>
              </div>
              <small v-if="usuarioForm.RolesSeleccionados.length === 0" class="usuarios-error-text">
                Debe seleccionar al menos un rol
              </small>
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

    <!-- Modal de detalles de usuario -->
    <div v-if="showDetallesModal" class="usuarios-modal-overlay" @click.self="showDetallesModal = false">
      <div class="usuarios-modal-content usuarios-modal-detalles">
        <div class="usuarios-modal-header">
          <h3>Detalles del Usuario</h3>
          <button class="usuarios-close-btn" @click="showDetallesModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="usuarios-modal-body">
          <div class="usuarios-detalles-grid" v-if="usuarioDetalle">
            <div class="usuarios-detalle-item">
              <label>Usuario:</label>
              <p>{{ usuarioDetalle.Usuario }}</p>
            </div>
            <div class="usuarios-detalle-item">
              <label>Nombre Completo:</label>
              <p>{{ usuarioDetalle.Nombre }} {{ usuarioDetalle.ApellidoP }} {{ usuarioDetalle.ApellidoM }}</p>
            </div>
            <div class="usuarios-detalle-item">
              <label>Puesto:</label>
              <p>{{ usuarioDetalle.Puesto || 'No especificado' }}</p>
            </div>
            <div class="usuarios-detalle-item">
              <label>Roles:</label>
              <div class="usuarios-usuario-roles" v-if="usuarioDetalle.Roles && usuarioDetalle.Roles.length > 0">
                <span
                  v-for="rol in usuarioDetalle.Roles"
                  :key="rol.Id"
                  class="usuarios-badge-rol"
                  :title="rol.Descripcion"
                >
                  {{ rol.Nombre }}
                </span>
              </div>
              <p v-else><span class="usuarios-badge-rol usuarios-badge-sin-rol">Sin roles</span></p>
            </div>
            <div class="usuarios-detalle-item">
              <label>Estatus:</label>
              <p>
                <span
                  class="usuarios-badge-status"
                  :class="{ 'active': usuarioDetalle.Estatus === 'ACTIVO', 'inactive': usuarioDetalle.Estatus === 'INACTIVO' }"
                >
                  {{ usuarioDetalle.Estatus }}
                </span>
              </p>
            </div>
            <div class="usuarios-detalle-item">
              <label>División:</label>
              <p>{{ usuarioDetalle.NombreDivision || 'Sin División' }}</p>
            </div>
            <div class="usuarios-detalle-item">
              <label>Unidad:</label>
              <p>{{ obtenerNombreUnidad(usuarioDetalle.IdUnidad) }}</p>
            </div>
            <div class="usuarios-detalle-item">
              <label>Usuarios Superiores:</label>
              <p>{{ obtenerSuperiores(usuarioDetalle.Id) }}</p>
            </div>
            <div class="usuarios-detalle-item">
              <label>Usuarios Subordinados:</label>
              <p>{{ obtenerSubordinados(usuarioDetalle.Id) }}</p>
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

    <!-- Modal para gestionar jerarquÃ­a -->
    <div v-if="showJerarquiaModal" class="usuarios-modal-overlay" @click.self="cancelarAccion">
      <div class="usuarios-modal-content">
        <div class="usuarios-modal-header">
          <h3>Gestionar Jerarquía: {{ usuarioSeleccionado?.Usuario }}</h3>
          <button class="usuarios-close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="usuarios-modal-body">
          <div class="usuarios-hierarchy-type-selector">
            <div class="usuarios-hierarchy-selector">
              <button
                :class="['usuarios-hierarchy-btn', { 'active': jerarquiaTab === 'superior' }]"
                @click="jerarquiaTab = 'superior'"
              >
                Usuarios Superiores
              </button>
              <button
                :class="['usuarios-hierarchy-btn', { 'active': jerarquiaTab === 'subordinado' }]"
                @click="jerarquiaTab = 'subordinado'"
              >
                Usuarios Subordinados
              </button>
            </div>
          </div>

          <div v-if="jerarquiaTab === 'superior'" class="usuarios-hierarchy-container">
            <h4>Usuarios Superiores a "{{ usuarioSeleccionado?.Usuario }}"</h4>
            <div class="usuarios-role-selection">
              <div v-for="usuario in usuariosDisponiblesSuperiores" :key="usuario.Id" class="usuarios-role-item">
                <input
                  type="checkbox"
                  :id="'sup-' + usuario.Id"
                  :value="usuario.Id"
                  v-model="usuariosSuperioresSeleccionados"
                />
                <label :for="'sup-' + usuario.Id">{{ usuario.Usuario }} - {{ usuario.Nombre }} {{ usuario.ApellidoP }}</label>
              </div>
              <div v-if="usuariosDisponiblesSuperiores.length === 0" class="usuarios-empty-roles">
                No hay usuarios disponibles para seleccionar
              </div>
            </div>
          </div>

          <div v-if="jerarquiaTab === 'subordinado'" class="usuarios-hierarchy-container">
            <h4>Usuarios Subordinados a "{{ usuarioSeleccionado?.Usuario }}"</h4>
            <div class="usuarios-role-selection">
              <div v-for="usuario in usuariosDisponiblesSubordinados" :key="usuario.Id" class="usuarios-role-item">
                <input
                  type="checkbox"
                  :id="'sub-' + usuario.Id"
                  :value="usuario.Id"
                  v-model="usuariosSubordinadosSeleccionados"
                />
                <label :for="'sub-' + usuario.Id">{{ usuario.Usuario }} - {{ usuario.Nombre }} {{ usuario.ApellidoP }}</label>
              </div>
              <div v-if="usuariosDisponiblesSubordinados.length === 0" class="usuarios-empty-roles">
                No hay usuarios disponibles para seleccionar
              </div>
            </div>
          </div>

          <div class="usuarios-form-actions">
            <button type="button" class="usuarios-btn-secondary" @click="cancelarAccion">
              Cancelar
            </button>
            <button type="button" class="usuarios-btn-primary" @click="guardarJerarquia">
              Guardar Jerarquía
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmaciÃ³n para eliminar -->
    <div v-if="showConfirmModal" class="usuarios-modal-overlay">
      <div class="usuarios-modal-content confirm-modal">
        <div class="usuarios-modal-header">
          <h3>Confirmar {{ tituloAccionMasiva }}</h3>
        </div>
        <div class="usuarios-modal-body">
          <div v-if="accionMasiva === 'eliminar'">
            <p>Â¿EstÃ¡ seguro de que desea <strong>eliminar</strong> {{ usuariosSeleccionados.length }} usuario(s)?</p>
            <div class="usuarios-usuarios-preview">
              <p class="usuarios-preview-title">Usuarios a eliminar:</p>
              <ul class="usuarios-usuarios-list-preview">
                <li v-for="id in usuariosSeleccionados.slice(0, 5)" :key="id">
                  {{ obtenerNombreUsuario(id) }}
                </li>
                <li v-if="usuariosSeleccionados.length > 5" class="usuarios-more-items">
                  ... y {{ usuariosSeleccionados.length - 5 }} usuario(s) más
                </li>
              </ul>
            </div>
            <p class="usuarios-warning-text">
              <i class="fas fa-exclamation-triangle"></i>
              Esta acciÃ³n no se puede deshacer. Los usuarios serÃ¡n eliminados permanentemente del sistema.
            </p>
          </div>

          <div v-else-if="accionMasiva === 'activar'">
            <p>Â¿EstÃ¡ seguro de que desea <strong>activar</strong> {{ usuariosSeleccionados.length }} usuario(s)?</p>
            <div class="usuarios-usuarios-preview">
              <p class="usuarios-preview-title">Usuarios a activar:</p>
              <ul class="usuarios-usuarios-list-preview">
                <li v-for="id in usuariosSeleccionados.slice(0, 5)" :key="id">
                  {{ obtenerNombreUsuario(id) }}
                </li>
                <li v-if="usuariosSeleccionados.length > 5" class="usuarios-more-items">
                  ... y {{ usuariosSeleccionados.length - 5 }} usuario(s) más
                </li>
              </ul>
            </div>
            <p class="usuarios-info-text">
              <i class="fas fa-info-circle"></i>
              Los usuarios podrÃ¡n acceder nuevamente al sistema.
            </p>
          </div>

          <div v-else-if="accionMasiva === 'desactivar'">
            <p>Â¿EstÃ¡ seguro de que desea <strong>desactivar</strong> {{ usuariosSeleccionados.length }} usuario(s)?</p>
            <div class="usuarios-usuarios-preview">
              <p class="usuarios-preview-title">Usuarios a desactivar:</p>
              <ul class="usuarios-usuarios-list-preview">
                <li v-for="id in usuariosSeleccionados.slice(0, 5)" :key="id">
                  {{ obtenerNombreUsuario(id) }}
                </li>
                <li v-if="usuariosSeleccionados.length > 5" class="usuarios-more-items">
                  ... y {{ usuariosSeleccionados.length - 5 }} usuario(s) más
                </li>
              </ul>
            </div>
            <p class="usuarios-info-text">
              <i class="fas fa-info-circle"></i>
              Los usuarios no podrÃ¡n acceder al sistema hasta que sean activados nuevamente.
            </p>
          </div>

          <div v-else>
            <p>¿Está seguro de que desea eliminar al usuario <strong>{{ usuarioEliminar?.Usuario }}</strong>?</p>
            <p class="usuarios-warning-text">
              <i class="fas fa-exclamation-triangle"></i>
              Esta acción no se puede deshacer.
            </p>
          </div>

          <div class="usuarios-form-actions">
            <button type="button" class="usuarios-btn-secondary" @click="cancelarAccionMasiva">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button
              type="button"
              :class="accionMasiva === 'eliminar' || (!accionMasiva && usuarioEliminar) ? 'usuarios-btn-danger' : 'usuarios-btn-primary'"
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
        IdRolSistema: null, // Mantener por compatibilidad
        RolesSeleccionados: [], // NUEVO: Array de IDs de roles
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

      // paginación
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
      if (this.accionMasiva === 'desactivar') return 'DesActivación Masiva';
      return 'Confirmar Eliminación';
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
        console.log('Respuesta usuarios:', response.data);
        this.usuarios = response.data.records || [];
        console.log('Usuarios cargados:', this.usuarios.slice(0, 2)); // Mostrar solo los primeros 2 para debug
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
        // âœ… Manejar tanto 'records' como respuesta directa
        this.divisiones = response.data.records || response.data || [];
      } catch (error) {
        console.error('Error al cargar divisiones:', error);
        this.divisiones = [];
      }
    },
    async cargarUnidades() {
      try {
        const response = await axios.get(`${this.backendUrl}/unidades.php`);
        console.log('Respuesta unidades:', response.data);
        this.unidades = response.data.records || response.data || [];
        console.log('Unidades cargadas:', this.unidades);
      } catch (error) {
        console.error('Error al cargar unidades:', error);
        this.unidades = [];
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
        console.error('Error al cargar jerarquÃ­as:', error);
        if (this.$toast) {
          this.$toast.error('Error al cargar jerarquÃ­as');
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
        RolesSeleccionados: [],
        Password: ''
      };
      this.showModal = true;
    },
    async editarUsuario(usuario) {
      this.modoEdicion = true;
      // Asegurarse de que los valores nulos se preserven como nulos
      this.usuarioForm = {
        ...usuario,
        Password: '',
        IdDivisionAdm: usuario.IdDivisionAdm || null,
        IdUnidad: usuario.IdUnidad || null,
        RolesSeleccionados: []
      };

      // Cargar los roles del usuario
      try {
        const response = await axios.get(
          `${this.backendUrl}/usuario-roles.php?idUsuario=${usuario.Id}`
        );
        this.usuarioForm.RolesSeleccionados = response.data.records.map(r => r.IdRolSistema);
      } catch (error) {
        console.error('Error al cargar roles del usuario:', error);
        // Si hay error, usar el rol antiguo como fallback
        if (usuario.IdRolSistema) {
          this.usuarioForm.RolesSeleccionados = [usuario.IdRolSistema];
        }
      }

      this.showModal = true;
    },
    async guardarUsuario() {
      try {
        // Validar que tenga al menos un rol
        if (!this.usuarioForm.RolesSeleccionados || this.usuarioForm.RolesSeleccionados.length === 0) {
          if (this.$toast) {
            this.$toast.error('Debe seleccionar al menos un rol');
          } else {
            alert('Debe seleccionar al menos un rol');
          }
          return;
        }

        // Crear una copia del formulario para enviar
        const formData = {...this.usuarioForm};

        // Convertir valores "null" (string) a null (valor)
        if (formData.IdDivisionAdm === "null") formData.IdDivisionAdm = null;
        if (formData.IdUnidad === "null") formData.IdUnidad = null;

        // Mantener el primer rol seleccionado como IdRolSistema por compatibilidad
        formData.IdRolSistema = this.usuarioForm.RolesSeleccionados[0];

        let userId;
        // Verificar si es una actualizaciÃ³n o creaciÃ³n
        if (this.modoEdicion) {
          // Actualizar usuario existente
          await axios.put(`${this.backendUrl}/usuarios.php`, formData);
          userId = formData.Id;
          if (this.$toast) {
            this.$toast.success('Usuario actualizado correctamente');
          } else {
            alert('Usuario actualizado correctamente');
          }
        } else {
          // Crear nuevo usuario
          const response = await axios.post(`${this.backendUrl}/usuarios.php`, formData);
          userId = response.data.userId || response.data.id;
          if (this.$toast) {
            this.$toast.success('Usuario creado correctamente');
          } else {
            alert('Usuario creado correctamente');
          }
        }

        // Guardar los roles del usuario en la tabla intermedia
        if (userId) {
          await axios.post(`${this.backendUrl}/usuario-roles.php`, {
            idUsuario: userId,
            roles: this.usuarioForm.RolesSeleccionados
          });
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

      // Filtro de División
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
      const unidad = this.unidades.find(u => u.id === idUnidad || u.Id === idUnidad);
      return unidad ? (unidad.nombre_unidad || unidad.Nombre || 'Sin unidad') : 'Sin unidad';
    },
    obtenerNombreDivision(idDivision) {
      if (!idDivision) return 'Sin división';
      const division = this.divisiones.find(d => d.Id === idDivision || d.id === idDivision);
      return division ? (division.Nombre || division.nombre || 'Sin división') : 'Sin división';
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

      // Por defecto mostrar la pestaÃ±a de subordinados
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

        // Guardar jerarquÃ­as
        await axios.post(`${this.backendUrl}/jerarquias.php`, data);

        if (this.$toast) {
          this.$toast.success('JerarquÃ­a actualizada correctamente');
        } else {
          alert('JerarquÃ­a actualizada correctamente');
        }

        // Recargar jerarquÃ­as y cerrar modal
        this.showJerarquiaModal = false;
        await this.cargarDatos();
      } catch (error) {
        console.error('Error al guardar jerarquÃ­a:', error);
        if (this.$toast) {
          this.$toast.error('Error al guardar la jerarquÃ­a');
        } else {
          alert('Error al guardar la jerarquÃ­a');
        }
      }
    },
    esParteDeCiclo(usuarioIdInicio, usuarioIdPotencial) {
      // Verifica si aÃ±adir usuarioIdPotencial como superior de usuarioIdInicio crearÃ­a un ciclo
      const visitados = new Set();
      const porVisitar = [usuarioIdPotencial];

      while (porVisitar.length > 0) {
        const usuarioActual = porVisitar.pop();

        if (usuarioActual === usuarioIdInicio) {
          return true; // Se encontró un ciclo
        }

        if (!visitados.has(usuarioActual)) {
          visitados.add(usuarioActual);

          // AÃ±adir todos los usuarios subordinados para seguir buscando
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
};

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

/* Selección de roles */
.usuarios-roles-selection {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  background: #f9fafb;
}

.usuarios-role-checkbox-item {
  display: flex;
  align-items: flex-start;
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
  border-radius: 6px;
  margin-bottom: 8px;
}

.usuarios-role-checkbox-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.usuarios-role-checkbox-item:hover {
  background-color: #f3f4f6;
}

.usuarios-role-checkbox-item input[type="checkbox"] {
  margin-right: 12px;
  margin-top: 3px;
  cursor: pointer;
  width: 16px;
  height: 16px;
}

.usuarios-role-checkbox-item label {
  flex: 1;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin: 0;
  font-weight: 500;
  color: #374151;
}

.usuarios-rol-nombre {
  font-weight: 600;
  color: #1e293b;
  font-size: 14px;
}

.usuarios-rol-descripcion {
  font-size: 12px;
  color: #6b7280;
  font-weight: normal;
}

.usuarios-error-text {
  color: #dc2626;
  font-size: 12px;
  margin-top: 5px;
  display: block;
  font-weight: 500;
}

/* Detalles del usuario */
.usuarios-modal-detalles {
  max-width: 900px;
}

.usuarios-detalles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.usuarios-detalle-item {
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 12px;
}

.usuarios-detalle-item label {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
  display: block;
  margin-bottom: 4px;
}

.usuarios-detalle-item p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.usuarios-usuario-roles {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 4px;
}

.usuarios-badge-rol {
  display: inline-block;
  padding: 4px 8px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 12px;
  background: #dbeafe;
  color: #1e40af;
  white-space: nowrap;
}

.usuarios-badge-sin-rol {
  background: #f3f4f6;
  color: #6b7280;
}

.usuarios-badge-status {
  display: inline-block;
  padding: 4px 12px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 16px;
  text-transform: uppercase;
}

.usuarios-badge-status.active {
  background: #d1fae5;
  color: #065f46;
}

.usuarios-badge-status.inactive {
  background: #fee2e2;
  color: #991b1b;
}

/* Jerarquía */
.usuarios-hierarchy-type-selector {
  margin-bottom: 20px;
}

.usuarios-hierarchy-selector {
  display: flex;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.usuarios-hierarchy-btn {
  flex: 1;
  padding: 12px 16px;
  background-color: #f8f9fa;
  border: none;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
  color: #374151;
}

.usuarios-hierarchy-btn.active {
  background-color: #2563eb;
  color: white;
}

.usuarios-hierarchy-container {
  margin-top: 15px;
}

.usuarios-hierarchy-container h4 {
  margin-top: 0;
  margin-bottom: 15px;
  color: #374151;
  font-weight: 600;
}

.usuarios-role-selection {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  background: #f9fafb;
}

.usuarios-role-item {
  display: flex;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #e5e7eb;
}

.usuarios-role-item:last-child {
  border-bottom: none;
}

.usuarios-role-item input[type="checkbox"] {
  margin-right: 10px;
  cursor: pointer;
}

.usuarios-role-item label {
  color: #374151;
  cursor: pointer;
  font-size: 14px;
  margin: 0;
}

.usuarios-empty-roles {
  padding: 20px;
  text-align: center;
  color: #6b7280;
  font-style: italic;
}

/* Modal de confirmación */
.confirm-modal {
  max-width: 500px;
}

.usuarios-usuarios-preview {
  margin: 16px 0;
  padding: 12px;
  background: #f3f4f6;
  border-radius: 6px;
}

.usuarios-preview-title {
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  margin-top: 0;
}

.usuarios-usuarios-list-preview {
  list-style: none;
  padding: 0;
  margin: 0;
}

.usuarios-usuarios-list-preview li {
  padding: 4px 0;
  color: #6b7280;
  font-size: 14px;
}

.usuarios-more-items {
  font-style: italic;
  color: #9ca3af;
}

.usuarios-warning-text {
  background: #fef2f2;
  color: #dc2626;
  padding: 12px;
  border-radius: 6px;
  border-left: 4px solid #dc2626;
  margin: 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.usuarios-info-text {
  background: #eff6ff;
  color: #2563eb;
  padding: 12px;
  border-radius: 6px;
  border-left: 4px solid #2563eb;
  margin: 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
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
  100% {
    transform: rotate(360deg);
  }
}

/* Panel de filtros */
.usuarios-filtros-panel {
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.usuarios-filtros-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.usuarios-filter-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  font-size: 14px;
  color: #1e293b;
}

.usuarios-filter-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
}

.usuarios-filtros-actions {
  display: flex;
  justify-content: flex-end;
}

.usuarios-btn-clear-filters {
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

.usuarios-btn-clear-filters:hover {
  background: #dc2626;
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
  transition: var(--transition);
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
  grid-template-columns: 60px 1fr 1.8fr 1.2fr 120px 100px 100px 150px 140px;
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
  grid-template-columns: 60px 1fr 1.8fr 1.2fr 120px 100px 100px 150px 140px;
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

/* Badges específicos para las nuevas columnas */
.usuarios-badge-division {
  background: #e0e7ff;
  color: #3730a3;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-align: center;
  white-space: nowrap;
}

.usuarios-badge-unidad {
  background: #f3e8ff;
  color: #6b21a8;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-align: center;
  white-space: nowrap;
}

.usuarios-badge-subordinados {
  background: #fef3c7;
  color: #92400e;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-align: center;
  white-space: nowrap;
}

/* Acciones masivas - Versión más delgada */
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
  color: var(--primary-color);
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

.usuarios-confirm-modal {
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
  color: #1e293b;
}

.usuarios-close-btn {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: #1e293b;
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
  color: var(--secondary-color);
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
  color: var(--secondary-color);
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  padding: 10px 15px;
  cursor: pointer;
  transition: var(--transition);
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

.usuarios-container .usuarios-detalles-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.usuarios-container .usuarios-detalle-item {
  padding: 15px;
  background: #f9fafb;
  border-radius: 8px;
}

.usuarios-container .usuarios-detalle-item.full-width {
  grid-column: 1 / -1;
}

.usuarios-container .usuarios-detalle-item label {
  display: block;
  font-size: 12px;
  color: #666;
  margin-bottom: 5px;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.usuarios-container .usuarios-detalle-item p {
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

/* Modal de confirmaciÃ³n mejorado */
.usuarios-confirm-modal {
  max-width: 500px;
}

.usuarios-usuarios-preview {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  margin: 15px 0;
  border: 1px solid #e5e7eb;
}

.usuarios-container .usuarios-preview-title {
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
  border-left: 3px solid #2563eb;
  font-size: 13px;
  color: #1e293b;
}

.usuarios-list-preview li.usuarios-more-items {
  background: #e5e7eb;
  border-left-color: #6b7280;
  font-style: italic;
  color: #6b7280;
  text-align: center;
}

.usuarios-info-text {
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

.usuarios-info-text i {
  color: #3b82f6;
  font-size: 18px;
}

.usuarios-btn-primary i,
.usuarios-btn-danger i,
.usuarios-btn-secondary i {
  margin-right: 5px;
}

/* Animaciones */
.usuarios-container .slide-enter-active,
.usuarios-container .slide-leave-active {
  transition: all 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}

.usuarios-container .slide-enter-from,
.usuarios-container .slide-leave-to {
  max-height: 0;
  opacity: 0;
}

.usuarios-container .fade-enter-active,
.usuarios-container .fade-leave-active {
  transition: all 0.3s ease;
}

.usuarios-container .fade-enter-from,
.usuarios-container .fade-leave-to {
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
    grid-template-columns: 60px 1fr 1.2fr 100px 120px;
  }

  /* Ocultar división, unidad y subordinados en tablets */
  .usuarios-list-header > div:nth-child(7),
  .usuarios-list-header > div:nth-child(8),
  .usuarios-list-header > div:nth-child(9),
  .usuarios-usuario-item > div:nth-child(7),
  .usuarios-usuario-item > div:nth-child(8),
  .usuarios-usuario-item > div:nth-child(9) {
    display: none;
  }
}

@media (max-width: 480px) {
  .usuarios-stats-grid {
    grid-template-columns: 1fr;
  }

  .usuarios-search-filter-container {
    flex-direction: column;
  }

  .usuarios-header-actions {
    flex-direction: column;
  }

  .usuarios-filtros-grid {
    grid-template-columns: 1fr;
  }

  /* Responsive para acciones masivas */
  .usuarios-acciones-masivas {
    bottom: 20px;
    padding: 6px 12px;
    border-radius: 40px;
  }

  .usuarios-acciones-info {
    font-size: 12px;
    padding: 0 6px;
  }

  .usuarios-acciones-info i {
    font-size: 13px;
  }

  .usuarios-btn-mass-action {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }

  .usuarios-acciones-buttons {
    gap: 4px;
  }
}

@media (max-width: 480px) {
  .usuarios-acciones-masivas {
    width: calc(100% - 32px);
    max-width: 400px;
  }

  .usuarios-acciones-info span {
    display: none;
  }

  .usuarios-btn-mass-action {
    width: 28px;
    height: 28px;
    font-size: 12px;
  }
}

/* JerarquÃ­a */
.usuarios-container .usuarios-hierarchy-type-selector {
  margin-bottom: 20px;
}

.usuarios-container .usuarios-hierarchy-selector {
  display: flex;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  overflow: hidden;
}

.usuarios-container .hierarchy-btn {
  flex: 1;
  padding: 10px;
  background-color: #f8f9fa;
  border: none;
  cursor: pointer;
  transition: var(--transition);
}

.usuarios-container .hierarchy-btn.active {
  background-color: var(--primary-color);
  color: white;
}

.usuarios-container .usuarios-hierarchy-container {
  margin-top: 15px;
}

.usuarios-container .usuarios-hierarchy-container h4 {
  margin-top: 0;
  margin-bottom: 15px;
  color: var(--secondary-color);
}

.usuarios-container .usuarios-role-selection {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid rgba(0,  0, 0, 0.1);
  border-radius: 4px;
  padding: 10px;
}

.usuarios-container .usuarios-role-item {
  display: flex;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.usuarios-container .usuarios-role-item:last-child {
  border-bottom: none;
}

.usuarios-container .usuarios-role-item input[type="checkbox"] {
  margin-right: 10px;
}

.usuarios-container .usuarios-empty-roles {
  padding: 15px;
  text-align: center;
  color: var(--secondary-color);
  font-style: italic;
}

/* Estilos para múltiples roles */
.usuarios-container .usuario-roles {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  align-items: center;
}

.usuarios-container .badge-sin-rol {
  background: #e5e7eb;
  color: #6b7280;
}

/* Selección de roles con checkboxes */
.usuarios-container .roles-selection {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 6px;
  padding: 10px;
  background: #f9fafb;
}

.usuarios-container .usuarios-role-checkbox-item {
  display: flex;
  align-items: flex-start;
  padding: 10px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  transition: background-color 0.2s;
}

.usuarios-container .usuarios-role-checkbox-item:last-child {
  border-bottom: none;
}

.usuarios-container .usuarios-role-checkbox-item:hover {
  background-color: rgba(59, 130, 246, 0.05);
}

.usuarios-container .usuarios-role-checkbox-item input[type="checkbox"] {
  margin-right: 10px;
  margin-top: 3px;
  cursor: pointer;
  width: 16px;
  height: 16px;
}

.usuarios-container .usuarios-role-checkbox-item label {
  flex: 1;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin: 0;
}

.usuarios-container .usuarios-rol-nombre {
  font-weight: 600;
  color: #1e293b;
  font-size: 14px;
}

.usuarios-container .usuarios-rol-descripcion {
  font-size: 12px;
  color: #6b7280;
  font-weight: normal;
}

.usuarios-container .usuarios-error-text {
  color: #dc2626;
  font-size: 12px;
  margin-top: 5px;
  display: block;
}

/* Estilos para la nueva tabla de usuarios */
.table-wrapper {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.loading-message,
.empty-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: #6b7280;
}

.loading-message i,
.empty-message i {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-message p {
  margin: 0;
  font-size: 16px;
}

.usuarios-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.usuarios-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.usuarios-table th {
  padding: 12px 8px;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}

.usuarios-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
  position: relative;
}

.usuarios-table th.sortable:hover {
  background-color: #f3f4f6;
}

.usuarios-table th.sortable i {
  margin-left: 5px;
  opacity: 0.5;
  transition: opacity 0.2s;
}

.usuarios-table th.sortable:hover i {
  opacity: 1;
}

.usuarios-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.usuarios-table tbody tr:hover {
  background-color: #f9fafb;
}

.usuarios-table tbody tr:last-child {
  border-bottom: none;
}

.usuarios-table td {
  padding: 12px 8px;
  vertical-align: middle;
}

.checkbox-column {
  width: 40px;
  text-align: center;
}

.checkbox-column input[type="checkbox"] {
  cursor: pointer;
  width: 16px;
  height: 16px;
}

.usuario-column {
  min-width: 120px;
}

.usuario-nombre {
  font-weight: 600;
  color: #1f2937;
}

.nombre-column {
  min-width: 200px;
  color: #374151;
}

.rol-column {
  min-width: 150px;
}

.roles-container {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.badge-rol {
  display: inline-block;
  padding: 4px 8px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 12px;
  background: #dbeafe;
  color: #1e40af;
  white-space: nowrap;
}

.badge-sin-rol {
  background: #f3f4f6;
  color: #6b7280;
}

.estatus-column {
  min-width: 80px;
}

.badge-status {
  display: inline-block;
  padding: 4px 12px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 16px;
  text-transform: uppercase;
}

.badge-status.active {
  background: #d1fae5;
  color: #065f46;
}

.badge-status.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.division-column,
.unidad-column {
  min-width: 100px;
}

.badge-division,
.badge-unidad {
  display: inline-block;
  padding: 4px 8px;
  font-size: 12px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 6px;
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.actions-column {
  min-width: 200px;
}

.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: flex-start;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
}

.action-btn:hover {
  transform: translateY(-1px);
}

.action-btn.view {
  background: #e0f2fe;
  color: #0891b2;
}

.action-btn.view:hover {
  background: #0891b2;
  color: white;
}

.action-btn.edit {
  background: #fef3c7;
  color: #d97706;
}

.action-btn.edit:hover {
  background: #d97706;
  color: white;
}

.action-btn.toggle {
  background: #f3e8ff;
  color: #7c3aed;
}

.action-btn.toggle:hover {
  background: #7c3aed;
  color: white;
}

.action-btn.hierarchy {
  background: #e0f7fa;
  color: #0097a7;
}

.action-btn.hierarchy:hover {
  background: #0097a7;
  color: white;
}

.action-btn.delete {
  background: #fee2e2;
  color: #dc2626;
}

.action-btn.delete:hover {
  background: #dc2626;
  color: white;
}

/* Responsive para tabla */
@media (max-width: 1200px) {
  .usuarios-table {
    font-size: 13px;
  }

  .usuarios-table th,
  .usuarios-table td {
    padding: 10px 6px;
  }

  .action-btn {
    width: 28px;
    height: 28px;
    font-size: 12px;
  }
}

@media (max-width: 768px) {
  .table-wrapper {
    border-radius: 0;
    border-left: none;
    border-right: none;
  }

  .usuarios-table {
    font-size: 12px;
    min-width: 800px;
  }

  .usuarios-table th,
  .usuarios-table td {
    padding: 8px 4px;
  }

  .badge-rol,
  .badge-status,
  .badge-division,
  .badge-unidad {
    font-size: 10px;
    padding: 2px 6px;
  }

  .action-buttons {
    gap: 2px;
  }

  .action-btn {
    width: 24px;
    height: 24px;
    font-size: 10px;
  }
}

/* OVERRIDES IMPORTANTES - Estos estilos tienen la mayor prioridad */
/* Asegurar que todos los labels tengan color gris, no azul */
.usuarios-form-group label,
.usuarios-filter-group label,
.usuarios-detalle-item label,
.usuarios-role-checkbox-item label,
.usuarios-role-item label,
label {
  color: #374151 !important;
}

/* Asegurar que el título del modal sea blanco */
.usuarios-modal-header h3 {
  color: white !important;
  font-weight: 600 !important;
}

/* Prevenir cualquier estilo azul en labels */
.usuarios-container label,
.usuarios-container .usuarios-form-group label,
.usuarios-container .usuarios-filter-group label,
.usuarios-container .usuarios-detalle-item label {
  color: #374151 !important;
  font-weight: 600 !important;
}
</style>
