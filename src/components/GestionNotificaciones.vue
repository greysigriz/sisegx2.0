<template>
  <div class="notificaciones-container">
    <!-- Botón de volver -->
    <BackButton />

    <!-- Estadísticas -->
    <div class="notificaciones-stats-grid">
      <div class="notificaciones-stat-card">
        <div class="notificaciones-stat-icon total">
          <i class="fas fa-users"></i>
        </div>
        <div class="notificaciones-stat-info">
          <p class="notificaciones-stat-value">{{ usuariosFiltrados.length }}</p>
          <p class="notificaciones-stat-label">Total Usuarios</p>
        </div>
      </div>
      <div class="notificaciones-stat-card">
        <div class="notificaciones-stat-icon active">
          <i class="fas fa-bell"></i>
        </div>
        <div class="notificaciones-stat-info">
          <p class="notificaciones-stat-value">{{ usuariosActivos }}</p>
          <p class="notificaciones-stat-label">Notificaciones Activas</p>
        </div>
      </div>
      <div class="notificaciones-stat-card">
        <div class="notificaciones-stat-icon inactive">
          <i class="fas fa-bell-slash"></i>
        </div>
        <div class="notificaciones-stat-info">
          <p class="notificaciones-stat-value">{{ usuariosInactivos }}</p>
          <p class="notificaciones-stat-label">Notificaciones Inactivas</p>
        </div>
      </div>
      <div class="notificaciones-stat-card">
        <div class="notificaciones-stat-icon warning">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="notificaciones-stat-info">
          <p class="notificaciones-stat-value">{{ usuariosSinEmail }}</p>
          <p class="notificaciones-stat-label">Sin Email</p>
        </div>
      </div>
    </div>

    <div class="notificaciones-card">
      <div class="notificaciones-card-header">
        <h3>Gestión de Notificaciones</h3>
        <div class="notificaciones-header-actions">
          <button class="notificaciones-btn-refresh" @click="cargarUsuarios" title="Actualizar">
            <i class="fas fa-sync-alt" :class="{ 'spinning': loading }"></i>
          </button>
        </div>
      </div>

      <div class="notificaciones-card-body">
        <!-- Loading state -->
        <div v-if="loading" class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Cargando usuarios...</span>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          <span>{{ error }}</span>
          <button @click="cargarUsuarios" class="btn-retry">
            <i class="fas fa-redo"></i> Reintentar
          </button>
        </div>

        <!-- Content -->
        <div v-else>
          <!-- Filters -->
          <div class="notificaciones-search-filter-container">
            <div class="notificaciones-filter-group">
              <label>Filtrar por departamento:</label>
              <select v-model="filtroDeptoId" class="notificaciones-filter-select">
                <option value="">Todos los departamentos</option>
                <option v-for="depto in departamentos" :key="depto.id" :value="depto.id">
                  {{ depto.nombre }}
                </option>
              </select>
            </div>

            <div class="notificaciones-filter-group">
              <label>Estado de notificaciones:</label>
              <select v-model="filtroEstado" class="notificaciones-filter-select">
                <option value="">Todos</option>
                <option value="activo">Activas</option>
                <option value="inactivo">Inactivas</option>
                <option value="sin-email">Sin email configurado</option>
              </select>
            </div>
          </div>

          <!-- Resultados -->
          <div class="notificaciones-results-info">
            <p>
              Mostrando {{ usuariosFiltrados.length }} de {{ usuarios.length }} usuarios
            </p>
          </div>

          <!-- Table -->
          <div class="table-wrapper">
            <table class="notificaciones-table">
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th>Email</th>
                  <th>Departamento</th>
                  <th>Estado</th>
                  <th>Última Notificación</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="usuariosFiltrados.length === 0">
                  <td colspan="6" class="empty-row">
                    <i class="fas fa-inbox"></i>
                    <p>No se encontraron usuarios</p>
                  </td>
                </tr>
                <tr v-for="usuario in usuariosFiltrados" :key="usuario.IdUsuario">
                  <td>
                    <div class="usuario-info">
                      <i class="fas fa-user-circle"></i>
                      <span>{{ usuario.Usuario }}</span>
                    </div>
                  </td>
                  <td>
                    <span v-if="usuario.Email" class="email-badge">
                      <i class="fas fa-envelope"></i>
                      {{ usuario.Email }}
                    </span>
                    <span v-else class="no-email">
                      <i class="fas fa-times-circle"></i>
                      Sin email
                    </span>
                  </td>
                  <td>
                    <span class="depto-badge">
                      <i class="fas fa-building"></i>
                      {{ usuario.nombre_unidad || 'No asignado' }}
                    </span>
                  </td>
                  <td>
                    <span
                      class="status-badge"
                      :class="{
                        'active': usuario.NotificacionesActivas,
                        'inactive': !usuario.NotificacionesActivas && usuario.Email,
                        'no-email': !usuario.Email
                      }"
                    >
                      <i v-if="usuario.NotificacionesActivas" class="fas fa-check-circle"></i>
                      <i v-else-if="usuario.Email" class="fas fa-times-circle"></i>
                      <i v-else class="fas fa-exclamation-circle"></i>
                      {{
                        usuario.NotificacionesActivas ? 'Activa' :
                        usuario.Email ? 'Inactiva' : 'Sin configurar'
                      }}
                    </span>
                  </td>
                  <td>
                    <span v-if="usuario.UltimaNotificacion" class="fecha-badge">
                      <i class="fas fa-calendar"></i>
                      {{ formatearFecha(usuario.UltimaNotificacion) }}
                    </span>
                    <span v-else class="no-data">-</span>
                  </td>
                  <td>
                    <div class="actions-group">
                      <!-- Botón Activar: visible cuando notificaciones están inactivas -->
                      <button
                        v-if="!usuario.NotificacionesActivas"
                        @click="activarNotificaciones(usuario.IdUsuario)"
                        :disabled="!usuario.Email || procesando === usuario.IdUsuario"
                        class="btn-action activate"
                        :title="!usuario.Email ? 'Requiere email configurado' : 'Activar notificaciones'"
                      >
                        <i v-if="procesando === usuario.IdUsuario" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-bell"></i>
                      </button>
                      <!-- Botón Desactivar: visible cuando notificaciones están activas -->
                      <button
                        v-if="usuario.NotificacionesActivas"
                        @click="desactivarNotificaciones(usuario.IdUsuario)"
                        :disabled="procesando === usuario.IdUsuario"
                        class="btn-action deactivate"
                        title="Desactivar notificaciones"
                      >
                        <i v-if="procesando === usuario.IdUsuario" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-bell-slash"></i>
                      </button>
                      <button
                        @click="verHistorial(usuario.IdUsuario)"
                        class="btn-action history"
                        title="Ver historial"
                      >
                        <i class="fas fa-history"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de historial -->
    <div v-if="mostrarModalHistorial" class="modal-overlay" @click="cerrarModalHistorial">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h4>
            <i class="fas fa-history"></i>
            Historial de Notificaciones - {{ usuarioSeleccionado?.Usuario }}
          </h4>
          <button @click="cerrarModalHistorial" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="cargandoHistorial" class="loading-small">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Cargando historial...</span>
          </div>
          <div v-else-if="historialUsuario.length === 0" class="empty-historial">
            <i class="fas fa-inbox"></i>
            <p>No hay historial de notificaciones</p>
          </div>
          <div v-else class="historial-list">
            <div
              v-for="item in historialUsuario"
              :key="item.Id"
              class="historial-item"
              :class="`estado-${item.Estado}`"
            >
              <div class="historial-icon">
                <i v-if="item.Estado === 'enviado'" class="fas fa-check-circle"></i>
                <i v-else-if="item.Estado === 'fallido'" class="fas fa-times-circle"></i>
                <i v-else class="fas fa-clock"></i>
              </div>
              <div class="historial-content">
                <div class="historial-asunto">{{ item.Asunto }}</div>
                <div class="historial-meta">
                  <span class="historial-fecha">
                    <i class="fas fa-calendar"></i>
                    {{ formatearFecha(item.FechaEnvio) }}
                  </span>
                  <span class="historial-peticiones">
                    <i class="fas fa-file-alt"></i>
                    {{ item.CantidadPeticionesPendientes }} pendientes
                  </span>
                </div>
              </div>
              <div class="historial-estado">
                <span :class="`badge badge-${item.Estado}`">
                  {{ item.Estado }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
import AuthService from '@/services/auth';
import { useRouter } from 'vue-router';
import BackButton from '@/components/BackButton.vue';

export default {
  name: 'GestionNotificaciones',
  components: {
    BackButton
  },
  setup() {
    const router = useRouter();
    const loading = ref(true);
    const error = ref(null);
    const procesando = ref(null);
    const usuarios = ref([]);
    const departamentos = ref([]);
    const filtroDeptoId = ref('');
    const filtroEstado = ref('');
    const mostrarModalHistorial = ref(false);
    const cargandoHistorial = ref(false);
    const historialUsuario = ref([]);
    const usuarioSeleccionado = ref(null);

    const apiUrl = import.meta.env.VITE_API_URL;

    /**
     * Usuarios filtrados
     */
    const usuariosFiltrados = computed(() => {
      let resultado = usuarios.value;

      // Filtrar por departamento
      if (filtroDeptoId.value) {
        resultado = resultado.filter(u => u.IdUnidad == filtroDeptoId.value);
      }

      // Filtrar por estado
      if (filtroEstado.value === 'activo') {
        resultado = resultado.filter(u => u.NotificacionesActivas);
      } else if (filtroEstado.value === 'inactivo') {
        resultado = resultado.filter(u => !u.NotificacionesActivas && u.Email);
      } else if (filtroEstado.value === 'sin-email') {
        resultado = resultado.filter(u => !u.Email);
      }

      return resultado;
    });

    const usuariosActivos = computed(() =>
      usuarios.value.filter(u => u.NotificacionesActivas).length
    );

    const usuariosInactivos = computed(() =>
      usuarios.value.filter(u => !u.NotificacionesActivas && u.Email).length
    );

    const usuariosSinEmail = computed(() =>
      usuarios.value.filter(u => !u.Email).length
    );

    /**
     * Cargar todos los usuarios con rol departamento
     */
    const cargarUsuarios = async () => {
      loading.value = true;
      error.value = null;

      try {
        const response = await axios.get(`${apiUrl}/gestion-notificaciones.php`);

        if (response.data.success) {
          usuarios.value = response.data.data.usuarios;
          departamentos.value = response.data.data.departamentos;
        }
      } catch (err) {
        console.error('Error cargando usuarios:', err);
        error.value = err.response?.data?.message || 'Error al cargar usuarios';
      } finally {
        loading.value = false;
      }
    };

    /**
     * Activar notificaciones de un usuario
     */
    const activarNotificaciones = async (userId) => {
      if (!confirm('¿Activar las notificaciones para este usuario?')) {
        return;
      }

      procesando.value = userId;

      try {
        const response = await axios.put(`${apiUrl}/gestion-notificaciones.php`, {
          userId,
          NotificacionesActivas: 1
        });

        if (response.data.success) {
          alert('✅ Notificaciones activadas correctamente');
          await cargarUsuarios();
        }
      } catch (err) {
        console.error('Error activando notificaciones:', err);
        alert('❌ Error: ' + (err.response?.data?.message || err.message));
      } finally {
        procesando.value = null;
      }
    };

    /**
     * Desactivar notificaciones de un usuario
     */
    const desactivarNotificaciones = async (userId) => {
      if (!confirm('¿Desactivar las notificaciones para este usuario?')) {
        return;
      }

      procesando.value = userId;

      try {
        const response = await axios.put(`${apiUrl}/gestion-notificaciones.php`, {
          userId,
          NotificacionesActivas: 0
        });

        if (response.data.success) {
          alert('✅ Notificaciones desactivadas correctamente');
          await cargarUsuarios();
        }
      } catch (err) {
        console.error('Error desactivando notificaciones:', err);
        alert('❌ Error: ' + (err.response?.data?.message || err.message));
      } finally {
        procesando.value = null;
      }
    };

    /**
     * Ver historial de un usuario
     */
    const verHistorial = async (userId) => {
      usuarioSeleccionado.value = usuarios.value.find(u => u.IdUsuario === userId);
      mostrarModalHistorial.value = true;
      cargandoHistorial.value = true;
      historialUsuario.value = [];

      try {
        const response = await axios.get(`${apiUrl}/gestion-notificaciones.php?userId=${userId}&historial=true`);

        if (response.data.success) {
          historialUsuario.value = response.data.data;
        }
      } catch (err) {
        console.error('Error cargando historial:', err);
      } finally {
        cargandoHistorial.value = false;
      }
    };

    const cerrarModalHistorial = () => {
      mostrarModalHistorial.value = false;
      usuarioSeleccionado.value = null;
      historialUsuario.value = [];
    };

    /**
     * Formatear fecha
     */
    const formatearFecha = (fecha) => {
      if (!fecha) return '';
      const date = new Date(fecha);
      return date.toLocaleString('es-MX', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };

    onMounted(() => {
      // Verificar si el usuario tiene rol Super Usuario
      const currentUser = AuthService.getCurrentUser();
      if (!currentUser || !currentUser.usuario || !currentUser.usuario.RolesIds) {
        alert('No tienes acceso a esta sección');
        router.push('/bienvenido');
        return;
      }

      const esSuperUsuario = currentUser.usuario.RolesIds.includes(1);
      if (!esSuperUsuario) {
        alert('Esta sección es solo para Super Usuarios');
        router.push('/bienvenido');
        return;
      }

      cargarUsuarios();
    });

    return {
      loading,
      error,
      procesando,
      usuarios,
      departamentos,
      usuariosFiltrados,
      filtroDeptoId,
      filtroEstado,
      usuariosActivos,
      usuariosInactivos,
      usuariosSinEmail,
      mostrarModalHistorial,
      cargandoHistorial,
      historialUsuario,
      usuarioSeleccionado,
      cargarUsuarios,
      activarNotificaciones,
      desactivarNotificaciones,
      verHistorial,
      cerrarModalHistorial,
      formatearFecha
    };
  }
};
</script>

<style scoped>
/* Container */
.notificaciones-container {
  padding: 0 1rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Estadísticas */
.notificaciones-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin: 2rem 0 1.5rem 0;
}

.notificaciones-stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: transform 0.2s;
}

.notificaciones-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.notificaciones-stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.notificaciones-stat-icon.total {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.notificaciones-stat-icon.active {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.notificaciones-stat-icon.inactive {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.notificaciones-stat-icon.warning {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.notificaciones-stat-info {
  flex: 1;
}

.notificaciones-stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.notificaciones-stat-label {
  font-size: 14px;
  color: #666;
  margin: 5px 0 0 0;
}

/* Card */
.notificaciones-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 1.5rem;
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}

.notificaciones-card-header {
  background: linear-gradient(135deg, #165CB1 0%, #1976d2 100%);
  color: white;
  padding: 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 12px 12px 0 0;
}

.notificaciones-card-header h3 {
  margin: 0;
  color: white;
  font-size: 18px;
}

.notificaciones-header-actions {
  display: flex;
  gap: 10px;
}

.notificaciones-card-body {
  padding: 20px;
}

/* Loading & Error */
.loading-state,
.error-message {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.loading-state i,
.error-message i {
  font-size: 48px;
  margin-bottom: 20px;
  display: block;
  color: #999;
}

.error-message {
  color: #dc3545;
}

.error-message i {
  color: #dc3545;
}

.btn-retry {
  margin-top: 20px;
  padding: 12px 24px;
  background: #165CB1;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-retry:hover {
  background: #1976d2;
}

/* Filters */
.notificaciones-search-filter-container {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  align-items: flex-end;
  flex-wrap: wrap;
}

.notificaciones-filter-group {
  flex: 1;
  min-width: 200px;
}

.notificaciones-filter-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #666;
  font-size: 14px;
}

.notificaciones-filter-select {
  width: 100%;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  transition: all 0.3s;
}

.notificaciones-filter-select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.notificaciones-btn-refresh {
  background: white;
  border: 2px solid #e5e7eb;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 18px;
  color: #666;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 45px;
  height: 45px;
}

.notificaciones-btn-refresh:hover {
  background: #f9fafb;
  color: #2563eb;
  border-color: #2563eb;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Resultados */
.notificaciones-results-info {
  margin-bottom: 1rem;
  color: #666;
}

.notificaciones-results-info p {
  margin: 0;
  font-size: 14px;
}



/* Table */
.table-wrapper {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid #dee2e6;
}

.notificaciones-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.notificaciones-table thead {
  background: linear-gradient(135deg, #165CB1, #1976d2);
}

.notificaciones-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  color: white;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #1976d2;
}

.notificaciones-table tbody tr {
  border-bottom: 1px solid #f1f3f5;
  transition: background 0.2s;
}

.notificaciones-table tbody tr:hover {
  background: #f8f9fa;
}

.notificaciones-table tbody tr:last-child {
  border-bottom: none;
}

.notificaciones-table td {
  padding: 16px;
  color: #495057;
  font-size: 14px;
  text-align: left;
  vertical-align: middle;
}

.empty-row {
  text-align: center;
  padding: 60px 20px !important;
  color: #999;
}

.empty-row i {
  font-size: 48px;
  display: block;
  margin-bottom: 16px;
  opacity: 0.4;
}

.empty-row p {
  margin: 0;
  font-size: 15px;
}

.usuario-info {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
}

.usuario-info {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
}

.usuario-info i {
  font-size: 20px;
  color: #165CB1;
}

.usuario-nombre {
  font-weight: 600;
}

.email-badge {
  display: inline-block;
  font-size: 13px;
  color: #495057;
}

.depto-badge {
  display: inline-block;
  padding: 4px 10px;
  background: rgba(22, 92, 177, 0.1);
  border-radius: 4px;
  font-size: 12px;
  color: #165CB1;
  font-weight: 500;
}

.fecha-badge {
  display: inline-block;
  font-size: 13px;
  color: #666;
}

.fecha-badge {
  background: #f8f9fa;
  color: #666;
}

.no-email,
.no-data {
  color: #999;
  font-style: italic;
  font-size: 13px;
}

.no-email i {
  color: #dc3545;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.no-email {
  background: #fef3c7;
  color: #92400e;
}

.actions-group {
  display: flex;
  gap: 8px;
  justify-content: flex-start;
}

.btn-action {
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s;
  color: white;
  font-weight: 500;
}

.btn-action.activate {
  background: #10b981;
}

.btn-action.activate:hover {
  background: #059669;
}

.btn-action.deactivate {
  background: #f59e0b;
}

.btn-action.deactivate:hover {
  background: #d97706;
}

.btn-action.history {
  background: #2563eb;
}

.btn-action.history:hover {
  background: #1e40af;
}

.btn-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #9ca3af !important;
  color: #e5e7eb !important;
}

.btn-action:disabled:hover {
  background: #9ca3af !important;
  transform: none;
}

/* Modal */
.modal-overlay {
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

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 800px;
  width: 100%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  padding: 20px;
  background: linear-gradient(135deg, #165CB1 0%, #1976d2 100%);
  color: white;
  border-bottom: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-radius: 12px 12px 0 0;
}

.modal-header h4 {
  margin: 0;
  font-size: 18px;
  color: white;
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  font-size: 18px;
  color: white;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  transition: all 0.2s;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.3);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
}

.loading-small {
  text-align: center;
  padding: 40px;
  color: #666;
}

.empty-historial {
  text-align: center;
  padding: 60px 20px;
  color: #999;
}

.empty-historial i {
  font-size: 56px;
  margin-bottom: 16px;
  display: block;
  opacity: 0.4;
}

.historial-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.historial-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #dee2e6;
  transition: all 0.2s;
}

.historial-item:hover {
  background: #f1f3f5;
  transform: translateX(4px);
}

.historial-item.estado-enviado {
  border-left-color: #28a745;
  background: #f0f8f4;
}

.historial-item.estado-fallido {
  border-left-color: #dc3545;
  background: #fef5f5;
}

.historial-icon {
  font-size: 28px;
  flex-shrink: 0;
}

.historial-item.estado-enviado .historial-icon i {
  color: #28a745;
}

.historial-item.estado-fallido .historial-icon i {
  color: #dc3545;
}

.historial-item .historial-icon i {
  color: #999;
}

.historial-content {
  flex: 1;
  min-width: 0;
}

.historial-asunto {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 6px;
}

.historial-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  font-size: 13px;
  color: #6c757d;
}

.historial-meta span {
  display: flex;
  align-items: center;
  gap: 6px;
}

.historial-estado .badge {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-enviado {
  background: #d1fae5;
  color: #065f46;
}

.badge-fallido {
  background: #fee2e2;
  color: #991b1b;
}

/* Responsive */
@media (max-width: 768px) {
  .notificaciones-container {
    padding: 15px;
  }

  .notificaciones-card-body {
    padding: 15px;
  }

  .notificaciones-search-filter-container {
    flex-direction: column;
  }

  .notificaciones-stats-grid {
    grid-template-columns: 1fr;
  }

  .table-wrapper {
    overflow-x: scroll;
  }

  .notificaciones-table {
    min-width: 800px;
  }
}
</style>
