<template>
  <div class="usuarios-container">
    <!-- Estadísticas -->
    <div class="usuarios-stats-grid">
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-number">{{ stats.total }}</div>
        <div class="usuarios-stat-label">Total Usuarios</div>
      </div>
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-number">{{ stats.activos }}</div>
        <div class="usuarios-stat-label">Usuarios Activos</div>
      </div>
      <div class="usuarios-stat-card">
        <div class="usuarios-stat-number">{{ stats.inactivos }}</div>
        <div class="usuarios-stat-label">Usuarios Inactivos</div>
      </div>
    </div>

    <!-- Header -->
    <div class="usuarios-header">
      <div class="usuarios-header-content">
        <h1 class="usuarios-header-title">Gestión de Usuarios</h1>
        <button class="usuarios-btn-primary" @click="mostrarModalNuevoUsuario = true">
          + Nuevo Usuario
        </button>
      </div>

      <!-- Filtros horizontales -->
      <div class="usuarios-filter-container">
        <div class="usuarios-filter-group">
          <label>Estado:</label>
          <select v-model="filtroEstado" @change="filtrarUsuarios">
            <option value="">Todos</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
          </select>
        </div>

        <div class="usuarios-filter-group">
          <label>Rol:</label>
          <select v-model="filtroRol" @change="filtrarUsuarios">
            <option value="">Todos los roles</option>
            <option v-for="rol in roles" :key="rol.id" :value="rol.id">
              {{ rol.nombre }}
            </option>
          </select>
        </div>

        <div class="usuarios-filter-group">
          <label>Departamento:</label>
          <select v-model="filtroDepartamento" @change="filtrarUsuarios">
            <option value="">Todos</option>
            <option v-for="dept in departamentos" :key="dept.id" :value="dept.id">
              {{ dept.nombre }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Lista de usuarios -->
    <div class="usuarios-list">
      <!-- Loading state -->
      <div v-if="loading" class="usuarios-loading">
        Cargando usuarios...
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="usuarios-error">
        Error al cargar usuarios: {{ error }}
      </div>

      <!-- Lista -->
      <div v-else>
        <!-- Header de la lista -->
        <div class="usuarios-list-header">
          <div>#</div>
          <div>Usuario</div>
          <div>Email</div>
          <div>Departamento</div>
          <div>Estado</div>
          <div>Rol</div>
          <div>División</div>
          <div>Unidad</div>
          <div>Acciones</div>
        </div>

        <!-- Items de usuarios -->
        <div v-if="usuariosFiltrados.length === 0" class="usuarios-empty-state">
          No se encontraron usuarios.
        </div>

        <div
          v-for="(usuario, index) in usuariosPaginados"
          :key="usuario.id"
          class="usuarios-usuario-item"
        >
          <div class="usuarios-avatar">{{ getInitials(usuario.nombre) }}</div>
          <div class="usuarios-user-info">
            <div class="usuarios-user-name">{{ usuario.nombre }}</div>
            <div class="usuarios-user-username">@{{ usuario.username }}</div>
          </div>
          <div class="usuarios-user-email">{{ usuario.email }}</div>
          <div class="usuarios-badge-departamento">{{ usuario.departamento_nombre || 'Sin asignar' }}</div>
          <div>
            <span :class="`usuarios-badge usuarios-badge-${usuario.estatus}`">
              {{ usuario.estatus === 'activo' ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
          <div class="usuarios-badge-role">{{ usuario.rol_nombre || 'Sin rol' }}</div>
          <div>
            <span class="usuarios-badge-division">{{ obtenerNombreDivision(usuario.division_id) }}</span>
          </div>
          <div>
            <span class="usuarios-badge-unidad">{{ usuario.unidad_nombre || 'Sin unidad' }}</span>
          </div>
          <div class="usuarios-action-buttons">
            <button
              class="usuarios-btn-icon usuarios-edit"
              @click="editarUsuario(usuario)"
              title="Editar usuario"
            >
              ✏️
            </button>
            <button
              class="usuarios-btn-icon usuarios-delete"
              @click="eliminarUsuario(usuario.id)"
              title="Eliminar usuario"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Usuarios',
  data() {
    return {
      usuarios: [],
      usuariosFiltrados: [],
      roles: [],
      departamentos: [],
      divisiones: [],
      loading: true,
      error: null,
      filtroEstado: '',
      filtroRol: '',
      filtroDepartamento: '',
      mostrarModalNuevoUsuario: false,
      paginaActual: 1,
      usuariosPorPagina: 10,
      stats: {
        total: 0,
        activos: 0,
        inactivos: 0
      }
    }
  },
  computed: {
    usuariosPaginados() {
      const inicio = (this.paginaActual - 1) * this.usuariosPorPagina;
      const fin = inicio + this.usuariosPorPagina;
      return this.usuariosFiltrados.slice(inicio, fin);
    },
    totalPaginas() {
      return Math.ceil(this.usuariosFiltrados.length / this.usuariosPorPagina);
    }
  },
  async mounted() {
    await this.cargarDatos();
  },
  methods: {
    async cargarDatos() {
      try {
        this.loading = true;

        // Cargar usuarios, roles, departamentos y divisiones en paralelo
        const [usuariosResponse, rolesResponse, departamentosResponse, divisionesResponse] = await Promise.all([
          fetch('/api/usuarios.php'),
          fetch('/api/roles.php'),
          fetch('/api/dependencias.php'),
          fetch('/api/divisiones.php')
        ]);

        if (!usuariosResponse.ok) throw new Error('Error al cargar usuarios');
        if (!rolesResponse.ok) throw new Error('Error al cargar roles');
        if (!departamentosResponse.ok) throw new Error('Error al cargar departamentos');
        if (!divisionesResponse.ok) throw new Error('Error al cargar divisiones');

        this.usuarios = await usuariosResponse.json();
        this.roles = await rolesResponse.json();
        this.departamentos = await departamentosResponse.json();
        this.divisiones = await divisionesResponse.json();

        this.usuariosFiltrados = [...this.usuarios];
        this.calcularEstadisticas();
        this.loading = false;
      } catch (error) {
        console.error('Error cargando datos:', error);
        this.error = error.message;
        this.loading = false;
      }
    },

    calcularEstadisticas() {
      this.stats = {
        total: this.usuarios.length,
        activos: this.usuarios.filter(u => u.estatus === 'activo').length,
        inactivos: this.usuarios.filter(u => u.estatus === 'inactivo').length
      };
    },

    filtrarUsuarios() {
      this.usuariosFiltrados = this.usuarios.filter(usuario => {
        let cumpleFiltros = true;

        if (this.filtroEstado && usuario.estatus !== this.filtroEstado) {
          cumpleFiltros = false;
        }

        if (this.filtroRol && usuario.rol_id != this.filtroRol) {
          cumpleFiltros = false;
        }

        if (this.filtroDepartamento && usuario.departamento_id != this.filtroDepartamento) {
          cumpleFiltros = false;
        }

        return cumpleFiltros;
      });

      this.paginaActual = 1;
    },

    getInitials(nombre) {
      if (!nombre) return 'U';
      return nombre.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    },

    obtenerNombreDivision(divisionId) {
      if (!divisionId) return 'Sin división';
      const division = this.divisiones.find(d => d.id == divisionId);
      return division ? division.nombre : 'Sin división';
    },

    editarUsuario(usuario) {
      // Implementar edición de usuario
      console.log('Editar usuario:', usuario);
    },

    eliminarUsuario(usuarioId) {
      if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
        // Implementar eliminación
        console.log('Eliminar usuario:', usuarioId);
      }
    }
  }
}
</script>

<style scoped>
@import '@/assets/css/Usuarios.css';
</style>
