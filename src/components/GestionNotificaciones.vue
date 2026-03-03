<template>
  <div class="gestion-container">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-users-cog header-icon"></i>
        <div class="header-text">
          <h3>Gestión de Notificaciones por Departamento</h3>
          <p class="subtitle">Administra las notificaciones de todos los usuarios de departamento</p>
        </div>
      </div>

      <div class="card-body">
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
          <div class="filters-section">
            <div class="filter-group">
              <label>Filtrar por departamento:</label>
              <select v-model="filtroDeptoId" class="filter-select">
                <option value="">Todos los departamentos</option>
                <option v-for="depto in departamentos" :key="depto.id" :value="depto.id">
                  {{ depto.nombre }}
                </option>
              </select>
            </div>

            <div class="filter-group">
              <label>Estado de notificaciones:</label>
              <select v-model="filtroEstado" class="filter-select">
                <option value="">Todos</option>
                <option value="activo">Activas</option>
                <option value="inactivo">Inactivas</option>
                <option value="sin-email">Sin email configurado</option>
              </select>
            </div>

            <button @click="cargarUsuarios" class="btn-refresh">
              <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
              Actualizar
            </button>
          </div>

          <!-- Stats -->
          <div class="stats-grid">
            <div class="stat-card">
              <i class="fas fa-users"></i>
              <div class="stat-content">
                <span class="stat-value">{{ usuariosFiltrados.length }}</span>
                <span class="stat-label">Usuarios Totales</span>
              </div>
            </div>

            <div class="stat-card active">
              <i class="fas fa-bell"></i>
              <div class="stat-content">
                <span class="stat-value">{{ usuariosActivos }}</span>
                <span class="stat-label">Notificaciones Activas</span>
              </div>
            </div>

            <div class="stat-card inactive">
              <i class="fas fa-bell-slash"></i>
              <div class="stat-content">
                <span class="stat-value">{{ usuariosInactivos }}</span>
                <span class="stat-label">Notificaciones Inactivas</span>
              </div>
            </div>

            <div class="stat-card warning">
              <i class="fas fa-exclamation-triangle"></i>
              <div class="stat-content">
                <span class="stat-value">{{ usuariosSinEmail }}</span>
                <span class="stat-label">Sin Email</span>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="table-container">
            <table class="usuarios-table">
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
                      <button
                        v-if="usuario.Email && !usuario.NotificacionesActivas"
                        @click="activarNotificaciones(usuario.IdUsuario)"
                        :disabled="procesando === usuario.IdUsuario"
                        class="btn-action activate"
                        title="Activar notificaciones"
                      >
                        <i v-if="procesando === usuario.IdUsuario" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-bell"></i>
                      </button>
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

export default {
  name: 'GestionNotificaciones',
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
.gestion-container {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

/* Card */
.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.card-header {
  background: linear-gradient(135deg, #6610f2 0%, #4e0db5 100%);
  color: white;
  padding: 30px;
  display: flex;
  align-items: center;
  gap: 20px;
}

.header-icon {
  font-size: 36px;
  color: white;
  opacity: 0.95;
}

.header-text h3 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
  color: white;
}

.header-text .subtitle {
  margin: 0;
  opacity: 0.92;
  font-size: 14px;
  color: white;
}

.card-body {
  padding: 30px;
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
  background: #6610f2;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-retry:hover {
  background: #4e0db5;
}

/* Filters */
.filters-section {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.filter-select {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #ced4da;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  transition: all 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #6610f2;
  box-shadow: 0 0 0 4px rgba(102, 16, 242, 0.1);
}

.btn-refresh {
  padding: 10px 20px;
  background: #6610f2;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-refresh:hover {
  background: #4e0db5;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 10px;
  border-left: 4px solid #6c757d;
}

.stat-card.active {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  border-left-color: #28a745;
}

.stat-card.inactive {
  background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
  border-left-color: #ffc107;
}

.stat-card.warning {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  border-left-color: #dc3545;
}

.stat-card i {
  font-size: 32px;
  color: #6c757d;
}

.stat-card.active i {
  color: #28a745;
}

.stat-card.inactive i {
  color: #ffc107;
}

.stat-card.warning i {
  color: #dc3545;
}

.stat-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #333;
}

.stat-label {
  font-size: 12px;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Table */
.table-container {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid #dee2e6;
}

.usuarios-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.usuarios-table thead {
  background: #f8f9fa;
}

.usuarios-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  color: #495057;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #dee2e6;
}

.usuarios-table tbody tr {
  border-bottom: 1px solid #f1f3f5;
  transition: background 0.2s;
}

.usuarios-table tbody tr:hover {
  background: #f8f9fa;
}

.usuarios-table td {
  padding: 16px;
  color: #495057;
  font-size: 14px;
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
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
}

.usuario-info i {
  font-size: 20px;
  color: #6610f2;
}

.email-badge,
.depto-badge,
.fecha-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #e7f3ff;
  border-radius: 6px;
  font-size: 13px;
  color: #0074D9;
}

.depto-badge {
  background: #f3e7ff;
  color: #6610f2;
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
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.status-badge.active {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.status-badge.inactive {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

.status-badge.no-email {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.actions-group {
  display: flex;
  gap: 8px;
}

.btn-action {
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
  color: white;
}

.btn-action.activate {
  background: #28a745;
}

.btn-action.activate:hover {
  background: #218838;
  transform: translateY(-2px);
}

.btn-action.deactivate {
  background: #ffc107;
}

.btn-action.deactivate:hover {
  background: #e0a800;
  transform: translateY(-2px);
}

.btn-action.history {
  background: #6610f2;
}

.btn-action.history:hover {
  background: #4e0db5;
  transform: translateY(-2px);
}

.btn-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
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
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  padding: 24px;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-header h4 {
  margin: 0;
  font-size: 20px;
  color: #333;
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 20px;
  color: #6c757d;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f8f9fa;
  color: #333;
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
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.badge-fallido {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

/* Responsive */
@media (max-width: 768px) {
  .gestion-container {
    padding: 15px;
  }

  .card-body {
    padding: 20px;
  }

  .filters-section {
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .table-container {
    overflow-x: scroll;
  }

  .usuarios-table {
    min-width: 800px;
  }
}
</style>
