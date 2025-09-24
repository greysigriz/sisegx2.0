<template>
    <div class="roles-container">
      <div class="card">
        <div class="card-header">
          <h3>Gestión de Roles</h3>
        </div>
        <div class="card-body">
          <p class="welcome-message">Administra los roles del sistema y sus jerarquías</p>
          <div class="actions-container">
            <button class="btn-primary" @click="crearNuevoRol">
              <i class="fas fa-plus"></i> Nuevo Rol
            </button>
          </div>
          <div class="roles-list">
            <div class="list-header">
              <div>Nombre del Rol</div>
              <div>Descripción</div>
              <div>Roles Subordinados</div>
              <div>Acciones</div>
            </div>

            <div v-if="loading" class="loading-message">
              Cargando roles...
            </div>

            <div v-else-if="roles.length === 0" class="empty-message">
              No hay roles registrados
            </div>

            <div v-else v-for="rol in roles" :key="rol.Id" class="rol-item">
              <div class="rol-info">
                <p>{{ rol.Nombre }}</p>
              </div>
              <div class="rol-info description">
                <p>{{ rol.Descripcion || 'Sin descripción' }}</p>
              </div>
              <div class="rol-info">
                <p>{{ obtenerSubordinados(rol.Id) }}</p>
              </div>
              <div class="rol-actions">
                <button class="action-btn edit" @click="editarRol(rol)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn hierarchy" @click="gestionarJerarquia(rol)">
                  <i class="fas fa-sitemap"></i>
                </button>
                <button class="action-btn delete" @click="confirmarEliminar(rol)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>
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
                <label for="nombre">Nombre del Rol:</label>
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
                  {{ modoEdicion ? 'Actualizar' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Modal para gestionar jerarquía de roles -->
      <div v-if="showJerarquiaModal" class="modal-overlay" @click.self="cancelarAccion">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Gestionar Jerarquía: {{ rolSeleccionado?.Nombre }}</h3>
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
                  Roles Superiores
                </button>
                <button
                  :class="['hierarchy-btn', { 'active': jerarquiaTab === 'subordinado' }]"
                  @click="jerarquiaTab = 'subordinado'"
                >
                  Roles Subordinados
                </button>
              </div>
            </div>

            <div v-if="jerarquiaTab === 'superior'" class="hierarchy-container">
              <h4>Roles Superiores a "{{ rolSeleccionado?.Nombre }}"</h4>
              <div class="role-selection">
                <div v-for="rol in rolesDisponiblesSuperiores" :key="rol.Id" class="role-item">
                  <input
                    type="checkbox"
                    :id="'sup-' + rol.Id"
                    :value="rol.Id"
                    v-model="rolesSuperioresSeleccionados"
                  />
                  <label :for="'sup-' + rol.Id">{{ rol.Nombre }}</label>
                </div>
                <div v-if="rolesDisponiblesSuperiores.length === 0" class="empty-roles">
                  No hay roles disponibles para seleccionar
                </div>
              </div>
            </div>

            <div v-if="jerarquiaTab === 'subordinado'" class="hierarchy-container">
              <h4>Roles Subordinados a "{{ rolSeleccionado?.Nombre }}"</h4>
              <div class="role-selection">
                <div v-for="rol in rolesDisponiblesSubordinados" :key="rol.Id" class="role-item">
                  <input
                    type="checkbox"
                    :id="'sub-' + rol.Id"
                    :value="rol.Id"
                    v-model="rolesSubordinadosSeleccionados"
                  />
                  <label :for="'sub-' + rol.Id">{{ rol.Nombre }}</label>
                </div>
                <div v-if="rolesDisponiblesSubordinados.length === 0" class="empty-roles">
                  No hay roles disponibles para seleccionar
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
            <h3>Confirmar eliminación</h3>
          </div>
          <div class="modal-body">
            <p>¿Está seguro de que desea eliminar el rol "{{ rolEliminar?.Nombre }}"?</p>
            <p class="warning-message" v-if="tieneSubordinados || tieneUsuarios">
              <i class="fas fa-exclamation-triangle"></i>
              <span v-if="tieneSubordinados">Este rol tiene roles subordinados. </span>
              <span v-if="tieneUsuarios">Este rol está asignado a uno o más usuarios. </span>
              <span>La eliminación puede afectar la operación del sistema.</span>
            </p>
            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="showConfirmModal = false">
                Cancelar
              </button>
              <button type="button" class="btn-danger" @click="eliminarRol">
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
    name: 'Roles',
    data() {
      return {
        loading: true,
        roles: [],
        jerarquias: [], // Almacenará todas las relaciones de jerarquía
        showModal: false,
        showJerarquiaModal: false,
        showConfirmModal: false,
        modoEdicion: false,
        rolForm: {
          Id: null,
          Nombre: '',
          Descripcion: ''
        },
        rolSeleccionado: null,
        rolEliminar: null,
        jerarquiaTab: 'subordinado', // 'superior' o 'subordinado'
        rolesSuperioresSeleccionados: [],
        rolesSubordinadosSeleccionados: [],
        tieneSubordinados: false,
        tieneUsuarios: false,
        backendUrl: import.meta.env.VITE_API_URL
      };
    },
    computed: {
      rolesDisponiblesSuperiores() {
        // Devuelve todos los roles excepto el seleccionado y los que ya son superiores
        if (!this.rolSeleccionado) return [];
        return this.roles.filter(rol =>
          rol.Id !== this.rolSeleccionado.Id &&
          !this.esParteDeCiclo(this.rolSeleccionado.Id, rol.Id)
        );
      },
      rolesDisponiblesSubordinados() {
        // Devuelve todos los roles excepto el seleccionado y los que ya son subordinados
        if (!this.rolSeleccionado) return [];
        return this.roles.filter(rol =>
          rol.Id !== this.rolSeleccionado.Id &&
          !this.esParteDeCiclo(rol.Id, this.rolSeleccionado.Id)
        );
      }
    },
    created() {
      this.cargarRoles();
      this.cargarJerarquias();
    },
    methods: {
      async cargarRoles() {
        try {
          this.loading = true;
          const response = await axios.get(`${this.backendUrl}/roles.php`);
          this.roles = response.data.records || [];
          this.loading = false;
        } catch (error) {
          console.error('Error al cargar roles:', error);
          this.loading = false;
          if (this.$toast) {
            this.$toast.error('Error al cargar roles');
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
          await this.cargarRoles();
        } catch (error) {
          console.error('Error al guardar rol:', error);
          if (this.$toast) {
            this.$toast.error('Error al guardar el rol');
          } else {
            alert('Error al guardar el rol');
          }
        }
      },
      gestionarJerarquia(rol) {
        this.rolSeleccionado = rol;
        this.showJerarquiaModal = true;

        // Cargar los roles superiores seleccionados
        this.rolesSuperioresSeleccionados = this.jerarquias
          .filter(j => j.IdRolSubordinado === rol.Id)
          .map(j => j.IdRolSuperior);

        // Cargar los roles subordinados seleccionados
        this.rolesSubordinadosSeleccionados = this.jerarquias
          .filter(j => j.IdRolSuperior === rol.Id)
          .map(j => j.IdRolSubordinado);

        // Por defecto mostrar la pestaña de subordinados
        this.jerarquiaTab = 'subordinado';
      },
      async guardarJerarquia() {
        try {
          // Preparar datos para enviar
          const data = {
            rolId: this.rolSeleccionado.Id,
            superiores: this.rolesSuperioresSeleccionados,
            subordinados: this.rolesSubordinadosSeleccionados
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
          await this.cargarJerarquias();
        } catch (error) {
          console.error('Error al guardar jerarquía:', error);
          if (this.$toast) {
            this.$toast.error('Error al guardar la jerarquía');
          } else {
            alert('Error al guardar la jerarquía');
          }
        }
      },
      confirmarEliminar(rol) {
        this.rolEliminar = rol;

        // Verificar si tiene roles subordinados
        this.tieneSubordinados = this.jerarquias.some(j => j.IdRolSuperior === rol.Id);

        // Simular verificación de usuarios con este rol
        // En producción, se debería hacer una consulta al backend
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
          await this.cargarRoles();
          await this.cargarJerarquias();
        } catch (error) {
          console.error('Error al eliminar rol:', error);
          if (this.$toast) {
            this.$toast.error('Error al eliminar el rol');
          } else {
            alert('Error al eliminar el rol');
          }
        }
      },
      obtenerSubordinados(rolId) {
        // Obtener IDs de roles subordinados
        const subordinadosIds = this.jerarquias
          .filter(j => j.IdRolSuperior === rolId)
          .map(j => j.IdRolSubordinado);

        // Obtener nombres de roles subordinados
        const subordinadosNombres = this.roles
          .filter(r => subordinadosIds.includes(r.Id))
          .map(r => r.Nombre);

        return subordinadosNombres.length > 0 ? subordinadosNombres.join(', ') : 'Ninguno';
      },
      esParteDeCiclo(rolIdInicio, rolIdPotencial) {
        // Verifica si añadir rolIdPotencial como superior de rolIdInicio crearía un ciclo
        // Es decir, si rolIdInicio ya es superior de rolIdPotencial

        const visitados = new Set();
        const porVisitar = [rolIdPotencial];

        while (porVisitar.length > 0) {
          const rolActual = porVisitar.pop();

          if (rolActual === rolIdInicio) {
            return true; // Se encontró un ciclo
          }

          if (!visitados.has(rolActual)) {
            visitados.add(rolActual);

            // Añadir todos los roles subordinados para seguir buscando
            const subordinados = this.jerarquias
              .filter(j => j.IdRolSuperior === rolActual)
              .map(j => j.IdRolSubordinado);

            porVisitar.push(...subordinados);
          }
        }

        return false; // No se encontró ciclo
      },
      cancelarAccion() {
        this.showModal = false;
        this.showJerarquiaModal = false;
        this.showConfirmModal = false;
      }
    }
  }
  </script>

  <style scoped>
  .roles-container {
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

  .roles-list {
    margin-top: 20px;
    border-radius: 8px;
    overflow: hidden;
    background-color: var(--white-color);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  }

  .list-header {
    display: grid;
    grid-template-columns: 1fr 1.5fr 1.5fr 0.5fr;
    background-color: rgba(177, 22, 35, 0.1);
    padding: 15px;
    font-weight: 600;
    color: var(--secondary-color);
  }

  .rol-item {
    display: grid;
    grid-template-columns: 1fr 1.5fr 1.5fr 0.5fr;
    padding: 15px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: var(--transition);
  }

  .rol-item:hover {
    background-color: rgba(177, 22, 35, 0.05);
  }

  .rol-info p {
    margin: 0;
    color: var(--secondary-color);
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

  .action-btn.edit {
    background-color: #f0ad4e;
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

  .confirm-modal {
    max-width: 400px;
  }

  /* Estilos para jerarquía */
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

  .warning-message {
    background-color: rgba(217, 83, 79, 0.1);
    border-left: 4px solid #d9534f;
    padding: 10px;
    margin: 15px 0;
    color: var(--secondary-color);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .warning-message i {
    color: #d9534f;
  }
  </style>
