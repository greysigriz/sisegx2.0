<template>
  <div class="usuarios-container">
    <div class="card">
      <div class="card-header">
        <h3>Gestión de Usuarios</h3>
      </div>
      <div class="card-body">
        <p class="welcome-message">Administra los usuarios del sistema</p>

        <div class="actions-container">
          <button class="btn-primary" @click="crearNuevoUsuario">
            <i class="fas fa-plus"></i> Nuevo Usuario
          </button>
        </div>

        <div class="usuarios-list">
          <div class="list-header">
            <div>Usuario</div>
            <div>Nombre completo</div>
            <div>Rol</div>
            <div>Acciones</div>
          </div>

          <div v-if="loading" class="loading-message">
            Cargando usuarios...
          </div>

          <div v-else-if="usuarios.length === 0" class="empty-message">
            No hay usuarios registrados
          </div>

          <div v-else v-for="usuario in usuarios" :key="usuario.Id" class="usuario-item">
            <div class="usuario-info">
              <p>{{ usuario.Usuario }}</p>
            </div>
            <div class="usuario-info">
              <p>{{ usuario.Nombre }} {{ usuario.ApellidoP }} {{ usuario.ApellidoM }}</p>
            </div>
            <div class="usuario-info">
              <p>{{ usuario.NombreRol || 'Sin rol' }}</p>
            </div>
            <div class="usuario-actions">
              <button class="action-btn edit" @click="editarUsuario(usuario)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn delete" @click="confirmarEliminar(usuario)">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
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
            <div class="form-group">
              <label for="usuario">Usuario:</label>
              <input
                type="text"
                id="usuario"
                v-model="usuarioForm.Usuario"
                required
              />
            </div>

            <div class="form-group">
              <label for="nombre">Nombre:</label>
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

            <div class="form-group">
              <label for="puesto">Puesto:</label>
              <input
                type="text"
                id="puesto"
                v-model="usuarioForm.Puesto"
              />
            </div>

            <div class="form-group">
              <label for="estatus">Estatus:</label>
              <select id="estatus" v-model="usuarioForm.Estatus">
                <option value="ACTIVO">Activo</option>
                <option value="INACTIVO">Inactivo</option>
              </select>
            </div>

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

            <div class="form-group">
              <label for="rol">Rol del Sistema:</label>
              <select id="rol" v-model="usuarioForm.IdRolSistema" required>
                <option v-for="rol in roles" :key="rol.Id" :value="rol.Id">
                  {{ rol.Nombre }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label for="password">Contraseña:</label>
              <input
                type="password"
                id="password"
                v-model="usuarioForm.Password"
                :required="!modoEdicion"
                :placeholder="modoEdicion ? 'Dejar en blanco para mantener la actual' : ''"
              />
            </div>

            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="cancelarAccion">
                Cancelar
              </button>
              <button type="submit" class="btn-primary">
                {{ modoEdicion ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div v-if="showConfirmModal" class="modal-overlay">
      <div class="modal-content confirm-modal">
        <div class="modal-header">
          <h3>Confirmar eliminación</h3>
        </div>
        <div class="modal-body">
          <p>¿Está seguro de que desea eliminar al usuario {{ usuarioEliminar.Usuario }}?</p>
          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="showConfirmModal = false">
              Cancelar
            </button>
            <button type="button" class="btn-danger" @click="eliminarUsuario">
              Eliminar
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
      roles: [],
      divisiones: [],
      unidades: [],
      showModal: false,
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
      backendUrl: import.meta.env.VITE_API_URL
    };
  },
  created() {
    this.cargarUsuarios();
    this.cargarRoles();
    this.cargarDivisiones();
    this.cargarUnidades();
  },
  methods: {
    async cargarUsuarios() {
      try {
        this.loading = true;
        const response = await axios.get(`${this.backendUrl}/usuarios.php`);
        this.usuarios = response.data.records || [];
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
        this.divisiones = response.data.records || [];
      } catch (error) {
        console.error('Error al cargar divisiones:', error);
        if (this.$toast) {
          this.$toast.error('Error al cargar divisiones');
        }
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
        await this.cargarUsuarios();
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
        await this.cargarUsuarios();
      } catch (error) {
        console.error('Error al eliminar usuario:', error);
        if (this.$toast) {
          this.$toast.error('Error al eliminar el usuario');
        } else {
          alert('Error al eliminar el usuario');
        }
      }
    },
    cancelarAccion() {
      this.showModal = false;
      this.showConfirmModal = false;
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
  box-shadow: var(--shadow);
  overflow: hidden;
}

.card-header {
  padding: 20px;
  background-color: var(--white-color);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.card-header h3 {
  margin: 0;
  color: var(--secondary-color);
  font-size: 18px;
}

.card-body {
  padding: 20px;
}

.welcome-message {
  font-size: 16px;
  font-weight: 500;
  color: var(--primary-color);
  margin-bottom: 10px;
}

.usuarios-list {
  margin-top: 20px;
  border-radius: 8px;
  overflow: hidden;
  background-color: var(--white-color);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.list-header {
  display: grid;
  grid-template-columns: 1fr 1.5fr 1fr 0.5fr;
  background-color: rgba(39, 135, 245, 0.926);
  padding: 15px;
  font-weight: 600;
  color: white;
}

.usuario-item {
  display: grid;
  grid-template-columns: 1fr 1.5fr 1fr 0.5fr;
  padding: 15px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  transition: var(--transition);
}

.usuario-item:hover {
  background-color: rgba(22, 84, 177, 0.05);
}

.usuario-info p {
  margin: 0;
  color: var(--secondary-color);
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

.actions-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 4px;
  padding: 10px 15px;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover {
  background-color: var(--secondary-color);
}

.loading-message, .empty-message {
  padding: 20px;
  text-align: center;
  color: var(--secondary-color);
}

/* Estilos Modal */
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

.form-group input, .form-group select {
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

.confirm-modal {
  max-width: 400px;
}
</style>
