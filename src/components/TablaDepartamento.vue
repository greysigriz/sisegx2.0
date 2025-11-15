<template>
  <div class="peticiones-container">
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-building"></i> Gestión de Peticiones - Departamento</h3>
        <div class="header-actions">
          <!-- ✅ CAMBIADO: Mostrar nombre del departamento en lugar de selector -->
          <div class="departamento-actual">
            <i class="fas fa-building"></i>
            <span v-if="departamentoActual">{{ departamentoActual.nombre_unidad }}</span>
            <span v-else class="text-muted">Cargando departamento...</span>
          </div>
          <button @click="cargarPeticiones" class="btn-filter">
            <i class="fas fa-sync"></i> Actualizar
          </button>
        </div>
      </div>

      <div class="card-body">
        <!-- ✅ NUEVO: Mensaje de error si no tiene departamento asignado -->
        <div v-if="errorDepartamento" class="empty-message error-message">
          <i class="fas fa-exclamation-triangle"></i>
          {{ errorDepartamento }}
        </div>

        <div v-else-if="loading" class="loading-message">
          <i class="fas fa-spinner fa-spin"></i> Cargando peticiones...
        </div>

        <div v-else-if="peticiones.length === 0" class="empty-message">
          <i class="fas fa-inbox"></i> No hay peticiones asignadas a tu departamento
        </div>

        <div v-else class="peticiones-list">
          <div class="tabla-scroll-container">
            <div class="tabla-contenido">
              <!-- Header -->
              <div class="list-header" style="grid-template-columns: 120px 180px 1fr 200px 160px 150px 180px;">
                <div>Folio</div>
                <div>Localidad</div>
                <div>Descripción del Problema</div>
                <div>Fecha Asignación</div>
                <div>Importancia</div>
                <div>Estado Actual</div>
                <div>Acciones</div>
              </div>

              <!-- Filas -->
              <div
                v-for="peticion in peticiones"
                :key="peticion.asignacion_id"
                class="peticion-item"
                style="grid-template-columns: 120px 180px 1fr 200px 160px 150px 180px;"
              >
                <div class="peticion-info">
                  <span class="folio-badge">{{ peticion.folio }}</span>
                </div>
                <div class="peticion-info localidad">{{ peticion.localidad }}</div>
                <div class="peticion-info descripcion-clickable"
                     @click="abrirModalDescripcion(peticion)"
                     :title="'Click para ver descripción completa'">
                  <i class="fas fa-file-alt"></i>
                  {{ truncateText(peticion.descripcion, 80) }}
                </div>
                <div class="peticion-info fecha-registro">
                  {{ formatearFecha(peticion.fecha_asignacion) }}
                </div>
                <div class="peticion-info">
                  <div class="indicadores-container">
                    <div :class="['nivel-importancia', `nivel-${peticion.NivelImportancia}`]"
                         :title="`Nivel ${peticion.NivelImportancia} - ${obtenerEtiquetaNivelImportancia(peticion.NivelImportancia)}`">
                      {{ obtenerTextoNivelImportancia(peticion.NivelImportancia) }}
                    </div>
                    <div :class="['semaforo', obtenerColorSemaforo(peticion.fecha_asignacion)]"
                         :title="obtenerTituloSemaforo(peticion.fecha_asignacion)"></div>
                  </div>
                </div>
                <div class="peticion-info">
                  <span :class="['estado-badge', `estado-${peticion.estado_departamento.toLowerCase().replace(/ /g, '-')}`]">
                    {{ peticion.estado_departamento }}
                  </span>
                </div>
                <div class="peticion-acciones">
                  <button @click="abrirModalCambioEstado(peticion)" class="action-btn menu" title="Cambiar Estado">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="abrirModalHistorial(peticion)" class="action-btn"
                          style="background: linear-gradient(135deg, #28a745, #20c997); margin-left: 0.5rem;"
                          title="Ver Historial">
                    <i class="fas fa-history"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Descripción Completa -->
    <div v-if="mostrarModalDescripcion" class="modal-overlay" @click.self="cerrarModalDescripcion">
      <div class="modal-content">
        <div class="modal-header">
          <h3><i class="fas fa-file-alt"></i> Descripción Completa del Problema</h3>
          <button @click="cerrarModalDescripcion" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div class="info-message">
            <i class="fas fa-info-circle"></i>
            <div>
              <strong>Folio:</strong> {{ peticionSeleccionada.folio }}<br>
              <strong>Localidad:</strong> {{ peticionSeleccionada.localidad }}<br>
              <strong>Fecha de asignación:</strong> {{ formatearFecha(peticionSeleccionada.fecha_asignacion) }}
            </div>
          </div>

          <div class="descripcion-completa-container">
            <h4><i class="fas fa-comment-alt"></i> Descripción del Problema:</h4>
            <div class="descripcion-texto">
              {{ peticionSeleccionada.descripcion }}
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="cerrarModalDescripcion" class="btn-secondary">
            <i class="fas fa-times"></i> Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Cambio de Estado -->
    <div v-if="mostrarModalEstado" class="modal-overlay" @click.self="cerrarModalEstado">
      <div class="modal-content">
        <div class="modal-header">
          <h3><i class="fas fa-edit"></i> Cambiar Estado de Petición</h3>
          <button @click="cerrarModalEstado" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div class="info-message">
            <i class="fas fa-info-circle"></i>
            <div>
              <strong>{{ peticionSeleccionada.folio }}</strong> - {{ peticionSeleccionada.localidad }}<br>
              Estado actual: <strong>{{ peticionSeleccionada.estado_departamento }}</strong>
            </div>
          </div>

          <div class="form-group">
            <label>Nuevo Estado *</label>
            <select v-model="nuevoEstado" required>
              <option value="">Seleccionar estado</option>
              <option value="Esperando recepción">Esperando recepción</option>
              <option value="Aceptado en proceso">Aceptado en proceso</option>
              <option value="Devuelto a seguimiento">Devuelto a seguimiento</option>
              <option value="Rechazado">Rechazado</option>
              <option value="Completado">Completado</option>
            </select>
          </div>

          <div class="form-group">
            <label>Motivo del cambio *</label>
            <textarea v-model="motivoCambio" rows="4" placeholder="Explica el motivo del cambio de estado..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="cerrarModalEstado" class="btn-secondary">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button @click="guardarCambioEstado" class="btn-primary" :disabled="!nuevoEstado || !motivoCambio">
            <i class="fas fa-save"></i> Guardar Cambio
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Historial -->
    <div v-if="mostrarModalHistorial" class="modal-overlay" @click.self="cerrarModalHistorial">
      <div class="modal-content modal-departamentos">
        <div class="modal-header">
          <h3><i class="fas fa-history"></i> Historial de Cambios</h3>
          <button @click="cerrarModalHistorial" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div class="info-message">
            <i class="fas fa-info-circle"></i>
            <div>
              <strong>{{ peticionSeleccionada.folio }}</strong> - {{ peticionSeleccionada.localidad }}
            </div>
          </div>

          <div v-if="historialCargando" class="loading-message">
            <i class="fas fa-spinner fa-spin"></i> Cargando historial...
          </div>

          <div v-else-if="!historialSeleccionado || historialSeleccionado.length === 0" class="no-departamentos">
            <i class="fas fa-inbox"></i> No hay cambios registrados
          </div>

          <div v-else class="historial-list">
            <div v-for="cambio in historialSeleccionado" :key="cambio.id" class="historial-item">
              <div class="historial-header">
                <div class="historial-fecha">
                  <i class="fas fa-clock"></i>
                  {{ formatearFechaCompleta(cambio.fecha_cambio) }}
                </div>
                <div class="historial-usuario" v-if="cambio.usuario_nombre">
                  <i class="fas fa-user"></i>
                  {{ cambio.usuario_nombre }}
                </div>
              </div>
              <div class="historial-cambio">
                <div class="estado-cambio">
                  <span class="estado-badge-small" v-if="cambio.estado_anterior">
                    {{ cambio.estado_anterior }}
                  </span>
                  <i class="fas fa-arrow-right"></i>
                  <span class="estado-badge-small estado-nuevo">
                    {{ cambio.estado_nuevo }}
                  </span>
                </div>
                <div class="historial-motivo">
                  <strong>Motivo:</strong> {{ cambio.motivo }}
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="cerrarModalHistorial" class="btn-secondary">
            <i class="fas fa-times"></i> Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, onMounted } from 'vue';

export default {
  name: 'TablaDepartamento',
  setup() {
    const backendUrl = import.meta.env.VITE_API_URL;
    const peticiones = ref([]);
    const departamentoActual = ref(null); // ✅ CAMBIADO: departamento del usuario
    const usuarioLogueado = ref(null); // ✅ NUEVO: usuario logueado
    const errorDepartamento = ref(''); // ✅ NUEVO: mensaje de error
    const loading = ref(false);

    // Modal Descripción
    const mostrarModalDescripcion = ref(false);

    // Modal Estado
    const mostrarModalEstado = ref(false);
    const peticionSeleccionada = ref({});
    const nuevoEstado = ref('');
    const motivoCambio = ref('');

    // Modal Historial
    const mostrarModalHistorial = ref(false);
    const historialSeleccionado = ref([]);
    const historialCargando = ref(false);

    // ✅ NUEVA: Función para obtener usuario logueado
    const obtenerUsuarioLogueado = async () => {
      try {
        const response = await axios.get(`${backendUrl}/check-session.php`);

        if (response.data.success && response.data.user) {
          usuarioLogueado.value = response.data.user;
          return response.data.user;
        }

        // Fallback a localStorage/sessionStorage
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        if (userData) {
          try {
            const user = JSON.parse(userData);
            usuarioLogueado.value = user;
            return user;
          } catch (e) {
            console.error('Error al parsear datos del usuario:', e);
          }
        }

        throw new Error('No se pudo obtener la información del usuario logueado');
      } catch (error) {
        console.error('Error al obtener usuario logueado:', error);
        throw error;
      }
    };

    // ✅ NUEVA: Función para cargar el departamento del usuario
    const cargarDepartamentoUsuario = async () => {
      try {
        const usuario = await obtenerUsuarioLogueado();

        if (!usuario.IdUnidad) {
          errorDepartamento.value = 'Tu usuario no tiene un departamento asignado. Contacta al administrador.';
          return null;
        }

        // Cargar información completa del departamento
        const response = await axios.get(`${backendUrl}/unidades.php`);
        const departamentos = response.data.records || [];

        const departamento = departamentos.find(d => d.id === usuario.IdUnidad);

        if (!departamento) {
          errorDepartamento.value = 'No se encontró información de tu departamento. Contacta al administrador.';
          return null;
        }

        departamentoActual.value = departamento;
        return departamento.id;
      } catch (error) {
        console.error('Error al cargar departamento del usuario:', error);
        errorDepartamento.value = 'Error al cargar información del departamento.';
        return null;
      }
    };

    // ✅ MODIFICADA: Cargar peticiones del departamento del usuario
    const cargarPeticiones = async () => {
      if (!departamentoActual.value) {
        console.warn('No hay departamento seleccionado');
        return;
      }

      loading.value = true;
      try {
        const res = await axios.get(`${backendUrl}/departamentos_peticiones.php`, {
          params: { departamento_id: departamentoActual.value.id }
        });
        peticiones.value = res.data.records || [];
      } catch (error) {
        console.error('Error cargando peticiones:', error);
        if (window.$toast) {
          window.$toast.error('Error al cargar las peticiones');
        } else {
          alert('Error al cargar las peticiones');
        }
      } finally {
        loading.value = false;
      }
    };

    const abrirModalDescripcion = (peticion) => {
      peticionSeleccionada.value = { ...peticion };
      mostrarModalDescripcion.value = true;
    };

    const cerrarModalDescripcion = () => {
      mostrarModalDescripcion.value = false;
      peticionSeleccionada.value = {};
    };

    const abrirModalCambioEstado = (peticion) => {
      peticionSeleccionada.value = { ...peticion };
      nuevoEstado.value = '';
      motivoCambio.value = '';
      mostrarModalEstado.value = true;
    };

    const cerrarModalEstado = () => {
      mostrarModalEstado.value = false;
      peticionSeleccionada.value = {};
      nuevoEstado.value = '';
      motivoCambio.value = '';
    };

    // ✅ MODIFICADA: guardarCambioEstado con usuario logueado real
    const guardarCambioEstado = async () => {
      if (!nuevoEstado.value || !motivoCambio.value) {
        alert('Completa todos los campos requeridos');
        return;
      }

      try {
        const res = await axios.put(`${backendUrl}/departamentos_peticiones.php`, {
          asignacion_id: peticionSeleccionada.value.asignacion_id,
          estado_nuevo: nuevoEstado.value,
          motivo: motivoCambio.value,
          usuario_id: usuarioLogueado.value?.Id || 1 // ✅ USAR ID real del usuario
        });

        if (res.data.success) {
          if (window.$toast) {
            window.$toast.success('Estado actualizado correctamente');
          } else {
            alert('Estado actualizado correctamente');
          }
          cerrarModalEstado();
          await cargarPeticiones();
        }
      } catch (error) {
        console.error('Error actualizando estado:', error);
        if (window.$toast) {
          window.$toast.error('Error al actualizar el estado');
        } else {
          alert('Error al actualizar el estado');
        }
      }
    };

    const abrirModalHistorial = async (peticion) => {
      peticionSeleccionada.value = { ...peticion };
      historialSeleccionado.value = peticion.historial || [];
      mostrarModalHistorial.value = true;
    };

    const cerrarModalHistorial = () => {
      mostrarModalHistorial.value = false;
      peticionSeleccionada.value = {};
      historialSeleccionado.value = [];
    };

    const truncateText = (text, length) => {
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    };

    const formatearFecha = (fecha) => {
      if (!fecha) return '';
      const date = new Date(fecha);
      return date.toLocaleDateString('es-MX');
    };

    const formatearFechaCompleta = (fecha) => {
      if (!fecha) return '';
      const date = new Date(fecha);
      return date.toLocaleString('es-MX');
    };

    const obtenerColorSemaforo = (fecha) => {
      const dias = Math.floor((new Date() - new Date(fecha)) / (1000 * 60 * 60 * 24));
      if (dias < 7) return 'verde';
      if (dias < 14) return 'amarillo';
      if (dias < 30) return 'naranja';
      return 'rojo';
    };

    const obtenerTituloSemaforo = (fecha) => {
      const dias = Math.floor((new Date() - new Date(fecha)) / (1000 * 60 * 60 * 24));
      return `Asignada hace ${dias} días`;
    };

    // ✅ NUEVA: Función para obtener etiqueta completa del nivel de importancia
    const obtenerEtiquetaNivelImportancia = (nivel) => {
      const etiquetas = {
        '1': 'Muy Alta',
        '2': 'Alta',
        '3': 'Media',
        '4': 'Baja',
        '5': 'Muy Baja'
      };
      return etiquetas[nivel] || 'No definida';
    };

    // ✅ NUEVA: Función para obtener texto corto del nivel de importancia
    const obtenerTextoNivelImportancia = (nivel) => {
      const textos = {
        '1': 'MUY ALTA',
        '2': 'ALTA',
        '3': 'MEDIA',
        '4': 'BAJA',
        '5': 'MUY BAJA'
      };
      return textos[nivel] || 'N/D';
    };

    // ✅ MODIFICADA: onMounted para cargar automáticamente
    onMounted(async () => {
      const departamentoId = await cargarDepartamentoUsuario();
      if (departamentoId) {
        await cargarPeticiones();
      }
    });

    return {
      peticiones,
      departamentoActual, // ✅ CAMBIADO: en lugar de departamentos y departamentoSeleccionado
      usuarioLogueado, // ✅ NUEVO
      errorDepartamento, // ✅ NUEVO
      loading,
      mostrarModalDescripcion,
      mostrarModalEstado,
      mostrarModalHistorial,
      peticionSeleccionada,
      nuevoEstado,
      motivoCambio,
      historialSeleccionado,
      historialCargando,
      cargarPeticiones,
      abrirModalDescripcion,
      cerrarModalDescripcion,
      abrirModalCambioEstado,
      cerrarModalEstado,
      guardarCambioEstado,
      abrirModalHistorial,
      cerrarModalHistorial,
      truncateText,
      formatearFecha,
      formatearFechaCompleta,
      obtenerColorSemaforo,
      obtenerTituloSemaforo,
      obtenerEtiquetaNivelImportancia,
      obtenerTextoNivelImportancia
    };
  }
};
</script>

<style src="@/assets/css/Petition.css"></style>
<style scoped>
/* ✅ NUEVOS: Estilos para mostrar departamento actual */
.departamento-actual {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 1.2rem;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 6px;
  color: white;
  font-size: 0.95rem;
  font-weight: 600;
}

.departamento-actual i {
  font-size: 1.1rem;
}

.departamento-actual .text-muted {
  opacity: 0.8;
  font-style: italic;
}

.error-message {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
  padding: 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.error-message i {
  font-size: 1.5rem;
  color: #ff9800;
}
</style>

