<template>
  <div class="notificaciones-container">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-bell header-icon"></i>
        <div class="header-text">
          <h3>Configuración de Notificaciones por Email</h3>
          <p class="subtitle">Configura tu correo electrónico para recibir notificaciones automáticas</p>
        </div>
      </div>

      <div class="card-body">
        <!-- Loading state -->
        <div v-if="loading" class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Cargando configuración...</span>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          <span>{{ error }}</span>
          <button @click="cargarConfiguracion" class="btn-retry">
            <i class="fas fa-redo"></i> Reintentar
          </button>
        </div>

        <!-- Configuration form -->
        <div v-else class="config-form">

          <!-- Email configuration section -->
          <div class="section email-section">
            <div class="section-header">
              <i class="fas fa-envelope section-icon"></i>
              <h4>Correo Electrónico para Notificaciones</h4>
            </div>

            <div class="form-group">
              <label for="email">Ingresa tu correo personal:</label>
              <div class="email-input-group">
                <input
                  type="email"
                  id="email"
                  v-model="emailTemp"
                  :disabled="guardandoEmail"
                  placeholder="tu-email@ejemplo.com"
                  @keyup.enter="actualizarEmail"
                  class="input-email"
                />
                <button
                  @click="actualizarEmail"
                  :disabled="!emailValido || guardandoEmail"
                  class="btn-update-email"
                >
                  <i v-if="guardandoEmail" class="fas fa-spinner fa-spin"></i>
                  <i v-else class="fas fa-save"></i>
                  <span>{{ guardandoEmail ? 'Guardando y probando...' : 'Guardar y Probar' }}</span>
                </button>
              </div>

              <div v-if="config.Email" class="email-status success">
                <i class="fas fa-check-circle"></i>
                <span>Email configurado: <strong>{{ config.Email }}</strong></span>
              </div>
              <div v-else class="email-status warning">
                <i class="fas fa-exclamation-circle"></i>
                <span>Debes configurar un email para recibir notificaciones</span>
              </div>

              <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p>Al guardar tu email, se enviará automáticamente un correo de prueba. Si no lo recibes, verifica que el email sea correcto e inténtalo nuevamente.</p>
              </div>

              <!-- Botón para desvincular email -->
              <button
                v-if="config.Email && config.NotificacionesActivas"
                @click="desvincularEmail"
                :disabled="desvinculando"
                class="btn-desvincular"
              >
                <i v-if="desvinculando" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-unlink"></i>
                <span>{{ desvinculando ? 'Desvinculando...' : 'Desvincular Email y Desactivar Notificaciones' }}</span>
              </button>
            </div>
          </div>

          <!-- Notification info section (read-only) -->
          <div v-if="config.Email && config.NotificacionesActivas" class="section info-section">
            <div class="section-header">
              <i class="fas fa-info-circle section-icon"></i>
              <h4>Información de Notificaciones</h4>
            </div>

            <div class="notification-status active">
              <i class="fas fa-check-circle"></i>
              <span>Notificaciones activadas correctamente</span>
            </div>

            <div class="info-grid">
              <div class="info-item">
                <i class="fas fa-building"></i>
                <div class="info-content">
                  <span class="info-label">Departamento</span>
                  <span class="info-value">{{ config.nombre_unidad || 'No asignado' }}</span>
                </div>
              </div>

              <div class="info-item">
                <i class="fas fa-calendar-alt"></i>
                <div class="info-content">
                  <span class="info-label">Frecuencia</span>
                  <span class="info-value">Lunes a Viernes</span>
                </div>
              </div>

              <div class="info-item">
                <i class="fas fa-clock"></i>
                <div class="info-content">
                  <span class="info-label">Horario</span>
                  <span class="info-value">9:00 AM (Hora CDMX)</span>
                </div>
              </div>

              <div class="info-item">
                <i class="fas fa-filter"></i>
                <div class="info-content">
                  <span class="info-label">Alcance</span>
                  <span class="info-value">Todas las peticiones del departamento</span>
                </div>
              </div>

              <div class="info-item">
                <i class="fas fa-tasks"></i>
                <div class="info-content">
                  <span class="info-label">Estados notificados</span>
                  <span class="info-value">Esperando recepción y Aceptado en proceso</span>
                </div>
              </div>

              <div class="info-item">
                <i class="fas fa-file-alt"></i>
                <div class="info-content">
                  <span class="info-label">Peticiones pendientes actuales</span>
                  <span class="info-value">{{ config.peticiones_pendientes_actuales || 0 }}</span>
                </div>
              </div>
            </div>

            <div class="features-list">
              <div class="feature-item">
                <i class="fas fa-check"></i>
                <span>Notifica nuevas asignaciones a tu departamento</span>
              </div>
              <div class="feature-item">
                <i class="fas fa-check"></i>
                <span>Notifica peticiones atrasadas que requieren atención</span>
              </div>
              <div class="feature-item">
                <i class="fas fa-check"></i>
                <span>Excluye automáticamente peticiones completadas, rechazadas o devueltas</span>
              </div>
            </div>
          </div>

          <!-- Notification history -->
          <div v-if="config.NotificacionesActivas" class="section history-section">
            <div class="section-header">
              <i class="fas fa-history section-icon"></i>
              <h4>Historial de Notificaciones</h4>
              <button @click="cargarHistorial" class="btn-refresh" :disabled="cargandoHistorial">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': cargandoHistorial }"></i>
              </button>
            </div>

            <div v-if="cargandoHistorial" class="loading-small">
              <i class="fas fa-spinner fa-spin"></i>
              <span>Cargando historial...</span>
            </div>

            <div v-else-if="historial.length === 0" class="empty-historial">
              <i class="fas fa-inbox"></i>
              <p>Aún no se han enviado notificaciones</p>
            </div>

            <div v-else class="historial-list">
              <div
                v-for="item in historial.slice(0, 10)"
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
  </div>
</template>

<script>
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
import AuthService from '@/services/auth';
import { useRouter } from 'vue-router';

export default {
  name: 'ConfiguracionNotificaciones',
  setup() {
    const router = useRouter();
    const loading = ref(true);
    const guardandoEmail = ref(false);
    const desvinculando = ref(false);
    const cargandoHistorial = ref(false);
    const error = ref(null);

    const config = ref({
      Email: '',
      NotificacionesActivas: false,
      FiltrarPorMunicipio: false,
      FrecuenciaNotificacion: 'diaria',
      HoraEnvio: '09:00:00',
      UmbralPeticionesPendientes: 1,
      NotificarPeticionesNuevas: true,
      NotificarPeticionesVencidas: true,
      nombre_unidad: '',
      peticiones_pendientes_actuales: 0
    });

    const emailTemp = ref('');
    const historial = ref([]);

    const emailValido = computed(() => {
      const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return regex.test(emailTemp.value);
    });

    const apiUrl = import.meta.env.VITE_API_URL;

    /**
     * Cargar configuración del usuario
     */
    const cargarConfiguracion = async () => {
      loading.value = true;
      error.value = null;

      try {
        const response = await axios.get(`${apiUrl}/notificaciones.php`);

        if (response.data.success && response.data.data) {
          Object.assign(config.value, response.data.data);
          emailTemp.value = config.value.Email || '';

          // Convertir valores booleanos
          config.value.NotificacionesActivas = Boolean(Number(config.value.NotificacionesActivas));
          config.value.FiltrarPorMunicipio = Boolean(Number(config.value.FiltrarPorMunicipio));
          config.value.NotificarPeticionesNuevas = Boolean(Number(config.value.NotificarPeticionesNuevas));
          config.value.NotificarPeticionesVencidas = Boolean(Number(config.value.NotificarPeticionesVencidas));

          // Cargar historial si las notificaciones están activas
          if (config.value.NotificacionesActivas) {
            cargarHistorial();
          }
        }
      } catch (err) {
        console.error('Error cargando configuración:', err);
        error.value = err.response?.data?.message || 'Error al cargar la configuración de notificaciones';
      } finally {
        loading.value = false;
      }
    };

    /**
     * Actualizar email del usuario y enviar prueba automáticamente
     */
    const actualizarEmail = async () => {
      if (!emailValido.value) {
        alert('Por favor ingresa un email válido');
        return;
      }

      guardandoEmail.value = true;

      try {
        const response = await axios.post(`${apiUrl}/notificaciones.php`, {
          email: emailTemp.value
        });

        if (response.data.success) {
          config.value.Email = emailTemp.value;
          config.value.NotificacionesActivas = true;
          alert('✅ Email guardado correctamente.\n\n📧 Se ha enviado un correo de prueba a: ' + emailTemp.value + '\n\nRevisa tu bandeja de entrada (y carpeta de SPAM).');

          // Recargar configuración para actualizar todos los campos
          await cargarConfiguracion();
        } else {
          // Email guardado pero fallo el envío
          alert('⚠️ ' + response.data.message + '\n\nPor favor verifica que el email sea correcto e inténtalo de nuevo.');
          config.value.Email = emailTemp.value;
          config.value.NotificacionesActivas = false;
        }
      } catch (err) {
        console.error('Error actualizando email:', err);
        const errorMsg = err.response?.data?.message || err.message;
        alert('❌ Error: ' + errorMsg);
      } finally {
        guardandoEmail.value = false;
      }
    };

    /**
     * Desvincular email y desactivar notificaciones
     */
    const desvincularEmail = async () => {
      if (!confirm('¿Estás seguro de que deseas desvincular tu email y desactivar las notificaciones?\n\nYa no recibirás alertas de peticiones pendientes.')) {
        return;
      }

      desvinculando.value = true;

      try {
        // Desactivar notificaciones
        const response = await axios.put(`${apiUrl}/notificaciones.php`, {
          NotificacionesActivas: 0
        });

        if (response.data.success) {
          config.value.NotificacionesActivas = false;
          alert('✅ Notificaciones desactivadas correctamente.\n\nPuedes volver a activarlas en cualquier momento ingresando tu email nuevamente.');

          // Recargar configuración
          await cargarConfiguracion();
        }
      } catch (err) {
        console.error('Error desvinculando email:', err);
        const errorMsg = err.response?.data?.message || err.message;
        alert('❌ Error al desvincular: ' + errorMsg);
      } finally {
        desvinculando.value = false;
      }
    };

    /**
     * Cargar historial de notificaciones
     */
    const cargarHistorial = async () => {
      cargandoHistorial.value = true;

      try {
        const response = await axios.get(`${apiUrl}/enviar-notificaciones.php?limit=10`);

        if (response.data.success) {
          historial.value = response.data.data;
        }
      } catch (err) {
        console.error('Error cargando historial:', err);
      } finally {
        cargandoHistorial.value = false;
      }
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
      // Verificar si el usuario tiene rol Departamento
      const currentUser = AuthService.getCurrentUser();
      if (!currentUser || !currentUser.usuario || !currentUser.usuario.RolesIds) {
        alert('No tienes acceso a esta sección');
        router.push('/bienvenido');
        return;
      }

      const tieneRolDepartamento = currentUser.usuario.RolesIds.includes(9);
      if (!tieneRolDepartamento) {
        alert('Esta sección es solo para usuarios de Departamento');
        router.push('/bienvenido');
        return;
      }

      cargarConfiguracion();
    });

    return {
      loading,
      guardandoEmail,
      desvinculando,
      cargandoHistorial,
      error,
      config,
      emailTemp,
      emailValido,
      historial,
      cargarConfiguracion,
      actualizarEmail,
      desvincularEmail,
      cargarHistorial,
      formatearFecha
    };
  }
};
</script>

<style scoped>
/* Container */
.notificaciones-container {
  padding: 20px;
  max-width: 1000px;
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
  background: linear-gradient(135deg, #0074D9 0%, #0056b3 100%);
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

/* Loading & Error States */
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
  background: #0074D9;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-retry:hover {
  background: #0056b3;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.3);
}

/* Sections */
.section {
  margin-bottom: 30px;
  padding: 25px;
  background: #f8f9fa;
  border-radius: 10px;
  border: 1px solid #e9ecef;
}

.email-section {
  background: linear-gradient(135deg, #e3f2fd 0%, #f0f7ff 100%);
  border-color: #bbdefb;
}

.info-section {
  background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%);
  border-color: #c8e6c9;
}

.history-section {
  background: #ffffff;
  border-color: #dee2e6;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.section-icon {
  font-size: 24px;
  color: #0074D9;
}

.section-header h4 {
  margin: 0;
  font-size: 18px;
  color: #333;
  font-weight: 600;
  flex: 1;
}

.btn-refresh {
  background: white;
  border: 1px solid #dee2e6;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  color: #666;
  transition: all 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #0074D9;
  color: #0074D9;
}

.btn-refresh:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Form Groups */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 10px;
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.email-input-group {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.input-email {
  flex: 1;
  padding: 12px 16px;
  border: 2px solid #ced4da;
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.2s;
  background: white;
}

.input-email:focus {
  outline: none;
  border-color: #0074D9;
  box-shadow: 0 0 0 4px rgba(0, 116, 217, 0.1);
}

.input-email:disabled {
  background: #e9ecef;
  cursor: not-allowed;
  opacity: 0.7;
}

.btn-update-email {
  padding: 12px 24px;
  background: #0074D9;
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

.btn-update-email:hover:not(:disabled) {
  background: #0056b3;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.3);
}

.btn-update-email:disabled {
  background: #6c757d;
  cursor: not-allowed;
  opacity: 0.7;
}

.btn-update-email i {
  font-size: 14px;
  color: white;
}

.btn-desvincular {
  padding: 12px 24px;
  background: #dc3545;
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
  margin-top: 12px;
  width: 100%;
  justify-content: center;
}

.btn-desvincular:hover:not(:disabled) {
  background: #c82333;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-desvincular:disabled {
  background: #6c757d;
  cursor: not-allowed;
  opacity: 0.7;
}

.btn-desvincular i {
  font-size: 14px;
  color: white;
}

.email-status {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 12px;
}

.email-status.success {
  background: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.email-status.warning {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  color: #856404;
}

.email-status i {
  font-size: 18px;
}

.email-status.success i {
  color: #28a745;
}

.email-status.warning i {
  color: #ffc107;
}

/* Info Box */
.info-box {
  display: flex;
  gap: 15px;
  padding: 16px;
  background: rgba(0, 116, 217, 0.08);
  border-left: 4px solid #0074D9;
  border-radius: 8px;
  margin-top: 12px;
}

.info-box i {
  font-size: 20px;
  color: #0074D9;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-box p {
  margin: 0;
  font-size: 13px;
  color: #495057;
  line-height: 1.6;
}

/* Notification Status */
.notification-status {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 24px;
}

.notification-status.active {
  background: #d4edda;
  border: 2px solid #28a745;
  color: #155724;
}

.notification-status i {
  font-size: 24px;
  color: #28a745;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.info-item i {
  font-size: 22px;
  color: #0074D9;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 12px;
  color: #6c757d;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 14px;
  color: #333;
  font-weight: 600;
}

/* Features List */
.features-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: white;
  border-radius: 8px;
  font-size: 14px;
  color: #333;
  border: 1px solid #e0e0e0;
}

.feature-item i {
  font-size: 18px;
  color: #28a745;
  flex-shrink: 0;
}

/* Historial */
.loading-small {
  text-align: center;
  padding: 30px;
  color: #666;
  font-size: 14px;
}

.loading-small i {
  margin-right: 8px;
  color: #0074D9;
}

.empty-historial {
  text-align: center;
  padding: 50px 20px;
  color: #999;
}

.empty-historial i {
  font-size: 56px;
  margin-bottom: 16px;
  display: block;
  opacity: 0.4;
  color: #999;
}

.empty-historial p {
  margin: 0;
  font-size: 15px;
  color: #999;
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
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

.historial-meta i {
  font-size: 12px;
  color: #999;
}

.historial-estado .badge {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
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

.badge-pendiente {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

/* Responsive */
@media (max-width: 768px) {
  .notificaciones-container {
    padding: 15px;
  }

  .card-body {
    padding: 20px;
  }

  .card-header {
    padding: 20px;
    flex-direction: column;
    text-align: center;
  }

  .header-icon {
    font-size: 32px;
  }

  .email-input-group {
    flex-direction: column;
  }

  .btn-update-email {
    width: 100%;
    justify-content: center;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .section {
    padding: 20px;
  }

  .historial-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .historial-meta {
    flex-direction: column;
    gap: 8px;
  }
}

@media (max-width: 480px) {
  .card-header {
    padding: 16px;
  }

  .header-text h3 {
    font-size: 20px;
  }

  .header-text .subtitle {
    font-size: 13px;
  }

  .section {
    padding: 16px;
  }

  .section-header h4 {
    font-size: 16px;
  }
}
</style>
