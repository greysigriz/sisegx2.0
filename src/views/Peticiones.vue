
<template>
  <div class="peticiones-container">
    <div class="card">
      <div class="card-header">
        <h3>Gestión de Peticiones</h3>
        <div class="header-actions">
          <button @click="filtrarMisPeticiones" class="btn-filter">
            <i class="fas fa-user"></i> Mis Peticiones
          </button>
          <button @click="limpiarFiltros" class="btn-clear">
            <i class="fas fa-times"></i> Limpiar Filtros
          </button>
        </div>
      </div>
      <div class="card-body">
        <p class="welcome-message">Administra las peticiones recibidas</p>
        
        <!-- Filtros -->
        <div class="filtros-container">
          <div class="filtro">
            <label for="filtroEstado">Estado:</label>
            <select id="filtroEstado" v-model="filtros.estado">
              <option value="">Todos</option>
              <option value="Sin revisar">Sin revisar</option>
              <option value="Rechazado por departamento">Rechazado por departamento</option>
              <option value="Por asignar departamento">Por asignar departamento</option>
              <option value="Completado">Completado</option>
              <option value="Aceptada en proceso">Aceptada en proceso</option>
              <option value="Devuelto">Devuelto</option>
              <option value="Improcedente">Improcedente</option>
              <option value="Cancelada">Cancelada</option>
              <option value="Esperando recepción">Esperando recepción</option>
            </select>
          </div>
          <div class="filtro">
            <label for="filtroDepartamento">Departamento:</label>
            <select id="filtroDepartamento" v-model="filtros.departamento">
              <option value="">Todos</option>
              <option v-for="departamento in departamentos" :key="departamento.id" :value="departamento.id">
                {{ departamento.nombre_unidad }}
              </option>
            </select>
          </div>
          <div class="filtro">
            <label for="filtroNivelImportancia">Nivel Importancia:</label>
            <select id="filtroNivelImportancia" v-model="filtros.nivelImportancia">
              <option value="">Todos</option>
              <option value="1">1 - Muy Alta</option>
              <option value="2">2 - Alta</option>
              <option value="3">3 - Media</option>
              <option value="4">4 - Baja</option>
              <option value="5">5 - Muy Baja</option>
            </select>
          </div>
          <div class="filtro">
            <label for="filtroUsuarioSeguimiento">Usuario Seguimiento:</label>
            <select id="filtroUsuarioSeguimiento" v-model="filtros.usuario_seguimiento">
              <option value="">Todos</option>
              <option v-if="usuarioLogueado" :value="usuarioLogueado.Id">Mis peticiones</option>
            </select>
          </div>
          <div class="filtro">
            <label for="filtroFolio">Folio:</label>
            <input type="text" id="filtroFolio" v-model="filtros.folio" placeholder="Buscar por folio">
          </div>
          <div class="filtro">
            <label for="filtroNombre">Nombre:</label>
            <input type="text" id="filtroNombre" v-model="filtros.nombre" placeholder="Buscar por nombre">
          </div>
        </div>
        
        <!-- Tabla de peticiones -->
        <div class="peticiones-list">
          <div class="tabla-scroll-container">
            <div class="tabla-contenido">
              <div class="list-header">
                <div>Acciones</div>
                <div>Folio</div>
                <div>Nombre</div>
                <div>Teléfono</div>
                <div>Localidad</div>
                <div>Estado</div>
                <div>Departamentos</div>
                <div>Prioridad/Semáforo</div>
                <div>Fecha Registro</div>
              </div>
              
              <div v-if="loading" class="loading-message">
                <i class="fas fa-spinner fa-spin"></i> Cargando peticiones...
              </div>
              
              <div v-else-if="peticionesFiltradas.length === 0" class="empty-message">
                <i class="fas fa-inbox"></i> No se encontraron peticiones con los filtros aplicados
              </div>
              
              <div v-else v-for="peticion in peticionesFiltradas" :key="peticion.id" class="peticion-item">
                <div class="peticion-acciones">
                  <button 
                    :class="['action-btn', 'menu', { active: peticionActiva === peticion.id }]" 
                    @click.stop="toggleAccionesMenu(peticion)" 
                    :title="peticionActiva === peticion.id ? 'Cerrar menú' : 'Mostrar acciones'"
                  >
                    <i class="fas fa-ellipsis-v"></i>
                  </button>
                  
                  <!-- Overlay para cerrar el dropdown -->
                  <div 
                    v-if="peticionActiva === peticion.id" 
                    class="dropdown-overlay"
                    @click="cerrarMenuAcciones"
                  ></div>
                  
                  <div 
                    v-if="peticionActiva === peticion.id" 
                    class="acciones-dropdown show"
                  >
                    <button class="dropdown-item" @click="editarPeticion(peticion); cerrarMenuAcciones()">
                      <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="dropdown-item" @click="cambiarEstado(peticion); cerrarMenuAcciones()">
                      <i class="fas fa-tasks"></i> Cambiar Estado
                    </button>
                    <button class="dropdown-item" @click="seguimiento(peticion); cerrarMenuAcciones()">
                      <i class="fas fa-clipboard-list"></i> 
                      {{ esUsuarioAsignado(peticion) ? 'Mi Seguimiento' : 'Asignar Seguimiento' }}
                    </button>
                    <button class="dropdown-item" @click="cambiarImportancia(peticion); cerrarMenuAcciones()">
                      <i class="fas fa-star"></i> Cambiar Importancia
                    </button>
                    <button class="dropdown-item" @click="gestionarDepartamentos(peticion); cerrarMenuAcciones()">
                      <i class="fas fa-building"></i> Gestionar Departamentos
                    </button>
                  </div>
                </div>
                
                <div class="peticion-info">
                  <span class="folio-badge">{{ peticion.folio }}</span>
                </div>
                
                <div class="peticion-info">
                  <span class="nombre-peticion">{{ peticion.nombre }}</span>
                </div>
                
                <div class="peticion-info">
                  <span class="telefono">{{ peticion.telefono }}</span>
                </div>
                
                <div class="peticion-info">
                  <span class="localidad">{{ peticion.localidad }}</span>
                </div>
                
                <div class="peticion-info">
                  <span :class="['estado-badge', 'estado-' + peticion.estado.toLowerCase().replace(/\s+/g, '-')]">
                    {{ peticion.estado }}
                  </span>
                </div>
                
                <div class="peticion-info departamentos-info">
                  <span class="departamentos-resumen">
                    {{ formatearDepartamentosResumen(peticion.departamentos) }}
                  </span>
                </div>
                
                <div class="peticion-info prioridad-semaforo">
                  <div class="indicadores-container">
                    <div class="nivel-importancia" :class="`nivel-${peticion.NivelImportancia}`" 
                         :title="`Nivel ${peticion.NivelImportancia} - ${obtenerEtiquetaNivelImportancia(peticion.NivelImportancia)}`">
                      {{ peticion.NivelImportancia }}
                    </div>
                    <div class="semaforo" :class="obtenerColorSemaforo(peticion)" :title="obtenerTituloSemaforo(peticion)"></div>
                    <div class="seguimiento-indicator" :class="obtenerClaseSeguimiento(peticion)" :title="obtenerTituloSeguimiento(peticion)">
                      <i :class="obtenerIconoSeguimiento(peticion)"></i>
                    </div>
                  </div>
                </div>
                
                <div class="peticion-info">
                  <span class="fecha-registro">{{ formatearFecha(peticion.fecha_registro) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal para editar petición -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="cancelarAccion">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Editar Petición</h3>
          <button class="close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="guardarPeticion">
            <div class="form-row">
              <div class="form-group">
                <label for="folio">Folio:</label>
                <input type="text" id="folio" v-model="peticionForm.folio" required />
              </div>
              
              <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" v-model="peticionForm.nombre" required />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" v-model="peticionForm.telefono" required />
              </div>
              
              <div class="form-group">
                <label for="localidad">Localidad:</label>
                <input type="text" id="localidad" v-model="peticionForm.localidad" required />
              </div>
            </div>
            
            <div class="form-group">
              <label for="direccion">Dirección:</label>
              <input type="text" id="direccion" v-model="peticionForm.direccion" required />
            </div>
            
            <div class="form-group">
              <label for="descripcion">Descripción:</label>
              <textarea id="descripcion" v-model="peticionForm.descripcion" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
              <label for="red_social">Red Social:</label>
              <input type="text" id="red_social" v-model="peticionForm.red_social" />
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="cancelarAccion">
                <i class="fas fa-times"></i> Cancelar
              </button>
              <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Modal para cambiar estado -->
    <div v-if="showEstadoModal" class="modal-overlay" @click.self="cancelarAccion">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Cambiar Estado de la Petición</h3>
          <button class="close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="guardarEstado">
            <div class="form-group">
              <label for="estado">Estado:</label>
              <select id="estado" v-model="peticionForm.estado" required>
                <option value="Sin revisar">Sin revisar</option>
                <option value="Rechazado por departamento">Rechazado por departamento</option>
                <option value="Por asignar departamento">Por asignar departamento</option>
                <option value="Completado">Completado</option>
                <option value="Aceptada en proceso">Aceptada en proceso</option>
                <option value="Devuelto">Devuelto</option>
                <option value="Improcedente">Improcedente</option>
                <option value="Cancelada">Cancelada</option>
                <option value="Esperando recepción">Esperando recepción</option>
              </select>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="cancelarAccion">
                <i class="fas fa-times"></i> Cancelar
              </button>
              <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Modal para cambiar nivel de importancia -->
    <div v-if="showImportanciaModal" class="modal-overlay" @click.self="cancelarAccion">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Cambiar Nivel de Importancia</h3>
          <button class="close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="guardarImportancia">
            <div class="form-group">
              <label for="nivelImportancia">Nivel de Importancia:</label>
              <select id="nivelImportancia" v-model="peticionForm.NivelImportancia" required>
                <option value="1">1 - Muy Alta</option>
                <option value="2">2 - Alta</option>
                <option value="3">3 - Media</option>
                <option value="4">4 - Baja</option>
                <option value="5">5 - Muy Baja</option>
              </select>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="cancelarAccion">
                <i class="fas fa-times"></i> Cancelar
              </button>
              <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Modal para gestionar departamentos -->
    <div v-if="showDepartamentosModal" class="modal-overlay" @click.self="cancelarAccion">
      <div class="modal-content modal-departamentos">
        <div class="modal-header">
          <h3>Gestionar Departamentos</h3>
          <button class="close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="loadingDepartamentos" class="loading-message">
            <i class="fas fa-spinner fa-spin"></i> Cargando departamentos...
          </div>
          
          <div v-else>
            <!-- Departamentos Asignados -->
            <div class="departamentos-section">
              <h4 class="departamentos-section-title">Departamentos Asignados</h4>
              
              <div v-if="departamentosAsignados.length === 0" class="no-departamentos">
                <i class="fas fa-info-circle"></i> No hay departamentos asignados
              </div>
              
              <div v-else class="departamentos-asignados-list">
                <div 
                  v-for="depAsignado in departamentosAsignados" 
                  :key="depAsignado.id"
                  class="departamento-asignado-item"
                >
                  <div class="departamento-info">
                    <span class="departamento-nombre">{{ depAsignado.nombre_unidad }}</span>
                    <span class="departamento-siglas">{{ depAsignado.siglas || depAsignado.abreviatura }}</span>
                  </div>
                  
                  <div class="departamento-estado">
                    <select 
                      :value="depAsignado.estado" 
                      @change="cambiarEstadoAsignacion(depAsignado.id, $event.target.value)"
                      class="estado-select"
                    >
                      <option value="Esperando recepción">Esperando recepción</option>
                      <option value="En proceso">En proceso</option>
                      <option value="Completado">Completado</option>
                      <option value="Rechazado por departamento">Rechazado</option>
                    </select>
                  </div>
                  
                  <div class="departamento-acciones">
                    <button 
                      class="btn-danger btn-sm" 
                      @click="eliminarDepartamentoAsignado(depAsignado.id)"
                      title="Eliminar asignación"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Asignar Nuevos Departamentos -->
            <div class="departamentos-section">
              <h4 class="departamentos-section-title">Asignar Nuevos Departamentos</h4>
              
              <div v-if="departamentosDisponibles.length === 0" class="no-departamentos">
                <i class="fas fa-check-circle"></i> Todos los departamentos están asignados
              </div>
              
              <div v-else class="asignar-departamentos-form">
                <div class="departamentos-checkboxes">
                  <div 
                    v-for="departamento in departamentosDisponibles" 
                    :key="departamento.id"
                    class="departamento-checkbox"
                  >
                    <label class="checkbox-label">
                      <input 
                        type="checkbox" 
                        :value="departamento.id" 
                        v-model="departamentosSeleccionados"
                        class="checkbox-input"
                      />
                      <span class="checkmark"></span>
                      <span class="departamento-label">
                        {{ departamento.nombre_unidad }}
                        <small v-if="departamento.siglas || departamento.abreviatura" class="departamento-small">
                          ({{ departamento.siglas || departamento.abreviatura }})
                        </small>
                      </span>
                    </label>
                  </div>
                </div>

                <div class="asignar-actions">
                  <button 
                    @click="asignarDepartamentos"
                    :disabled="departamentosSeleccionados.length === 0"
                    class="btn-primary"
                  >
                    <i class="fas fa-plus"></i> 
                    Asignar Seleccionados ({{ departamentosSeleccionados.length }})
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="cancelarAccion">
              <i class="fas fa-times"></i> Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import axios from 'axios';
import { ref, reactive, onMounted, onBeforeUnmount, watch } from 'vue';

export default {
  name: 'Peticiones',
  setup() {
    const loading = ref(true);
    const peticiones = ref([]);
    const peticionesFiltradas = ref([]);
    const departamentos = ref([]);
    const showEditModal = ref(false);
    const showEstadoModal = ref(false);
    const showDepartamentosModal = ref(false);
    const showImportanciaModal = ref(false);
    const peticionActiva = ref(null);
    const usuarioLogueado = ref(null);
    
    // Estado para gestión de departamentos
    const departamentosAsignados = ref([]);
    const departamentosDisponibles = ref([]);
    const departamentosSeleccionados = ref([]);
    const loadingDepartamentos = ref(false);
    
    const peticionForm = reactive({
      id: null,
      folio: '',
      nombre: '',
      telefono: '',
      direccion: '',
      localidad: '',
      descripcion: '',
      red_social: '',
      estado: '',
      NivelImportancia: 3
    });
    
    const filtros = reactive({
      estado: '',
      departamento: '',
      folio: '',
      nombre: '',
      nivelImportancia: '',
      usuario_seguimiento: ''
    });
    
    const backendUrl = import.meta.env.VITE_API_URL;
    
    // Función mejorada para obtener el usuario logueado
    const obtenerUsuarioLogueado = async () => {
      try {
        if (usuarioLogueado.value && usuarioLogueado.value.Id) {
          return usuarioLogueado.value.Id;
        }
        
        const response = await axios.get(`${backendUrl}/check-session.php`);
        
        if (response.data.success && response.data.user) {
          usuarioLogueado.value = response.data.user;
          return response.data.user.Id;
        }
        
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        if (userData) {
          try {
            const user = JSON.parse(userData);
            usuarioLogueado.value = user;
            return user.Id || user.id;
          } catch (e) {
            console.error('Error al parsear datos del usuario del almacenamiento local:', e);
          }
        }
        
        console.warn('No se pudo obtener la información del usuario logueado');
        return null;
      } catch (error) {
        console.error('Error al obtener usuario logueado:', error);
        
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        if (userData) {
          try {
            const user = JSON.parse(userData);
            usuarioLogueado.value = user;
            return user.Id || user.id;
          } catch (e) {
            console.error('Error en fallback al almacenamiento local:', e);
          }
        }
        
        return null;
      }
    };
    
    const obtenerInfoUsuarioLogueado = () => {
      return usuarioLogueado.value;
    };
    
    // Función mejorada para ordenar peticiones por prioridad
    const ordenarPeticionesPorPrioridad = (peticiones) => {
      return peticiones.sort((a, b) => {
        // Primero separamos por color de semáforo
        const colorA = obtenerColorSemaforo(a);
        const colorB = obtenerColorSemaforo(b);
        
        // Si uno es verde y el otro no, el verde va al final
        if (colorA === 'verde' && colorB !== 'verde') return 1;
        if (colorB === 'verde' && colorA !== 'verde') return -1;
        
        // Si ambos son verdes, ordenamos por fecha más reciente
        if (colorA === 'verde' && colorB === 'verde') {
          const fechaA = new Date(a.fecha_registro);
          const fechaB = new Date(b.fecha_registro);
          return fechaB - fechaA;
        }
        
        // Para registros no verdes, aplicamos la lógica de prioridad
        // 1. Primero por nivel de importancia (1 es más importante que 4)
        const importanciaA = parseInt(a.NivelImportancia) || 3;
        const importanciaB = parseInt(b.NivelImportancia) || 3;
        
        if (importanciaA !== importanciaB) {
          return importanciaA - importanciaB;
        }
        
        // 2. Si tienen la misma importancia, ordenar por antigüedad (más viejo primero)
        const fechaA = new Date(a.fecha_registro);
        const fechaB = new Date(b.fecha_registro);
        
        return fechaA - fechaB;
      });
    };
    
    const cargarPeticiones = async () => {
      try {
        loading.value = true;
        
        const response = await axios.get(`${backendUrl}/peticiones.php`);
        const peticionesRaw = response.data.records || [];
        
        // Ordenamos las peticiones por prioridad
        peticiones.value = ordenarPeticionesPorPrioridad(peticionesRaw);
        
        // Aplicamos filtros después de cargar
        aplicarFiltros();
        
        loading.value = false;
      } catch (error) {
        console.error('Error al cargar peticiones:', error);
        loading.value = false;
        if (window.$toast) {
          window.$toast.error('Error al cargar peticiones');
        }
      }
    };
    
    const cargarDepartamentos = async () => {
      try {
        const response = await axios.get(`${backendUrl}/unidades.php?activos=true`);
        departamentos.value = response.data.records || [];
      } catch (error) {
        console.error('Error al cargar departamentos:', error);
        if (window.$toast) {
          window.$toast.error('Error al cargar departamentos');
        }
      }
    };
    
    // Función para cargar departamentos asignados a una petición específica
    const cargarDepartamentosAsignados = async (peticionId) => {
      try {
        loadingDepartamentos.value = true;
        const response = await axios.get(`${backendUrl}/peticion_departamento.php?peticion_id=${peticionId}`);
        
        if (response.data.success) {
          departamentosAsignados.value = response.data.departamentos || [];
          
          // Filtrar departamentos disponibles (excluir los ya asignados)
          const idsAsignados = departamentosAsignados.value.map(d => d.departamento_id);
          departamentosDisponibles.value = departamentos.value.filter(d => !idsAsignados.includes(d.id));
        }
        
        loadingDepartamentos.value = false;
      } catch (error) {
        console.error('Error al cargar departamentos asignados:', error);
        loadingDepartamentos.value = false;
        if (window.$toast) {
          window.$toast.error('Error al cargar departamentos asignados');
        }
      }
    };
    
    // Función para obtener departamentos de una petición (para mostrar en la tabla)
    const obtenerDepartamentosPeticion = async (peticionId) => {
      try {
        const response = await axios.get(`${backendUrl}/peticion_departamento.php?peticion_id=${peticionId}`);
        if (response.data.success) {
          return response.data.departamentos || [];
        }
        return [];
      } catch (error) {
        console.error('Error al obtener departamentos de petición:', error);
        return [];
      }
    };
    
    // Función para mostrar departamentos en formato resumido
    const formatearDepartamentosResumen = (departamentos) => {
      if (!departamentos || departamentos.length === 0) {
        return 'Sin asignar';
      }
      
      if (departamentos.length === 1) {
        return departamentos[0].siglas || departamentos[0].abreviatura || departamentos[0].nombre_unidad;
      }
      
      return `${departamentos.length} depts.`;
    };
    
    const formatearFecha = (fechaStr) => {
      if (!fechaStr) return '';
      
      const fecha = new Date(fechaStr);
      const dia = fecha.getDate().toString().padStart(2, '0');
      const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
      const anio = fecha.getFullYear();
      const hora = fecha.getHours().toString().padStart(2, '0');
      const minutos = fecha.getMinutes().toString().padStart(2, '0');
      
      return `${dia}/${mes}/${anio} ${hora}:${minutos}`;
    };
    
    const obtenerNombreDepartamento = (departamentoId) => {
      if (!departamentoId) return 'Sin asignar';
      
      const departamento = departamentos.value.find(d => d.id === departamentoId);
      return departamento ? departamento.nombre_unidad : 'Sin asignar';
    };
    
    const tieneUsuarioAsignado = (peticion) => {
      return peticion.usuario_id !== null && peticion.usuario_id !== undefined && peticion.usuario_id !== '' && peticion.usuario_id !== 0;
    };
    
    const obtenerIconoSeguimiento = (peticion) => {
      return tieneUsuarioAsignado(peticion) ? 'fas fa-user-check' : 'fas fa-user-times';
    };
    
    const obtenerTituloSeguimiento = (peticion) => {
      if (tieneUsuarioAsignado(peticion)) {
        const nombreUsuario = peticion.nombre_completo_usuario || peticion.nombre_usuario_seguimiento || 'Usuario asignado';
        return `Seguimiento asignado a: ${nombreUsuario}`;
      }
      return 'Sin usuario asignado para seguimiento';
    };
    
    const obtenerClaseSeguimiento = (peticion) => {
      return tieneUsuarioAsignado(peticion) ? 'seguimiento-asignado text-success' : 'seguimiento-sin-asignar text-muted';
    };
    
    const esUsuarioAsignado = (peticion) => {
      if (!usuarioLogueado.value || !tieneUsuarioAsignado(peticion)) {
        return false;
      }
      return peticion.usuario_id === usuarioLogueado.value.Id;
    };
    
    const obtenerColorSemaforo = (peticion) => {
      const estadosParaSemaforo = ['Sin revisar', 'Rechazado por departamento', 
                                  'Por asignar departamento', 'Esperando recepción'];
      
      if (!estadosParaSemaforo.includes(peticion.estado)) {
        return 'verde';
      }
      
      const fechaRegistro = new Date(peticion.fecha_registro);
      const ahora = new Date();
      const horasTranscurridas = (ahora - fechaRegistro) / (1000 * 60 * 60);
      
      if (horasTranscurridas <= 24) return 'verde';
      if (horasTranscurridas <= 48) return 'amarillo';
      if (horasTranscurridas <= 72) return 'naranja';
      return 'rojo';
    };

    const obtenerTituloSemaforo = (peticion) => {
      const estadosParaSemaforo = ['Sin revisar', 'Rechazado por departamento', 
                                  'Por asignar departamento', 'Esperando recepción'];
      
      if (!estadosParaSemaforo.includes(peticion.estado)) {
        return 'Petición procesada';
      }
      
      const fechaRegistro = new Date(peticion.fecha_registro);
      const ahora = new Date();
      const horasTranscurridas = Math.floor((ahora - fechaRegistro) / (1000 * 60 * 60));
      const dias = Math.floor(horasTranscurridas / 24);
      const horasRestantes = horasTranscurridas % 24;
      
      let mensaje = `Tiempo de espera: ${horasTranscurridas} horas`;
      if (dias > 0) {
        mensaje = `Tiempo de espera: ${dias} día${dias !== 1 ? 's' : ''} y ${horasRestantes} hora${horasRestantes !== 1 ? 's' : ''}`;
      }
      
      if (horasTranscurridas <= 24) return `${mensaje} (Normal)`;
      if (horasTranscurridas <= 48) return `${mensaje} (Atención recomendable)`;
      if (horasTranscurridas <= 72) return `${mensaje} (Atención prioritaria)`;
      return `${mensaje} (¡ATENCIÓN URGENTE!)`;
    };
    
    // Función mejorada de aplicarFiltros
    const aplicarFiltros = () => {
      let peticionesFiltradas_temp = [...peticiones.value];
      
      // Aplicar filtros
      peticionesFiltradas_temp = peticionesFiltradas_temp.filter(peticion => {
        // Filtrar por estado
        if (filtros.estado && peticion.estado !== filtros.estado) {
          return false;
        }
        
        // Filtrar por nivel de importancia
        if (filtros.nivelImportancia && 
            peticion.NivelImportancia !== parseInt(filtros.nivelImportancia)) {
          return false;
        }
        
        // Filtrar por usuario de seguimiento
        if (filtros.usuario_seguimiento && 
            peticion.usuario_id !== parseInt(filtros.usuario_seguimiento)) {
          return false;
        }
        
        // Filtrar por folio
        if (filtros.folio && 
            !peticion.folio.toLowerCase().includes(filtros.folio.toLowerCase())) {
          return false;
        }
        
        // Filtrar por nombre
        if (filtros.nombre && 
            !peticion.nombre.toLowerCase().includes(filtros.nombre.toLowerCase())) {
          return false;
        }
        
        return true;
      });
      
      // Aplicamos el ordenamiento a los resultados filtrados
      peticionesFiltradas.value = ordenarPeticionesPorPrioridad(peticionesFiltradas_temp);
    };
    
    // Watchers para los filtros
    watch(() => filtros.estado, () => {
      aplicarFiltros();
    });
    
    watch(() => filtros.nivelImportancia, () => {
      aplicarFiltros();
    });
    
    watch(() => filtros.usuario_seguimiento, () => {
      aplicarFiltros();
    });
    
    watch(() => filtros.folio, () => {
      aplicarFiltros();
    });
    
    watch(() => filtros.nombre, () => {
      aplicarFiltros();
    });
    
    const editarPeticion = (peticion) => {
      Object.assign(peticionForm, peticion);
      showEditModal.value = true;
    };
    
    const cambiarEstado = (peticion) => {
      peticionForm.id = peticion.id;
      peticionForm.estado = peticion.estado;
      showEstadoModal.value = true;
    };
    
    const cambiarImportancia = (peticion) => {
      peticionForm.id = peticion.id;
      peticionForm.NivelImportancia = peticion.NivelImportancia || 3;
      showImportanciaModal.value = true;
    };
    
    // Nueva función para gestionar departamentos
    const gestionarDepartamentos = async (peticion) => {
      peticionForm.id = peticion.id;
      departamentosSeleccionados.value = [];
      await cargarDepartamentosAsignados(peticion.id);
      showDepartamentosModal.value = true;
    };
    
    // Función para asignar departamentos seleccionados
    const asignarDepartamentos = async () => {
      if (departamentosSeleccionados.value.length === 0) {
        if (window.$toast) {
          window.$toast.warning('Seleccione al menos un departamento');
        }
        return;
      }
      
      try {
        if (window.$loading) {
          window.$loading.show();
        }
        
        const response = await axios.post(`${backendUrl}/peticion_departamento.php`, {
          accion: 'asignar_departamentos',
          peticion_id: peticionForm.id,
          departamentos: departamentosSeleccionados.value
        });
        
        if (response.data.success) {
          if (window.$toast) {
            window.$toast.success(response.data.message);
          }
          
          // Recargar departamentos asignados
          await cargarDepartamentosAsignados(peticionForm.id);
          departamentosSeleccionados.value = [];
          
          // Recargar peticiones para actualizar estados
          await cargarPeticiones();
        }
        
      } catch (error) {
        console.error('Error al asignar departamentos:', error);
        if (window.$toast) {
          let mensaje = 'Error al asignar departamentos';
          if (error.response && error.response.data && error.response.data.message) {
            mensaje = error.response.data.message;
          }
          window.$toast.error(mensaje);
        }
      } finally {
        if (window.$loading) {
          window.$loading.hide();
        }
      }
    };
    
    // Función para eliminar departamento asignado
    const eliminarDepartamentoAsignado = async (asignacionId) => {
      if (!confirm('¿Está seguro de que desea eliminar esta asignación de departamento?')) {
        return;
      }
      
      try {
        if (window.$loading) {
          window.$loading.show();
        }
        
        const response = await axios.delete(`${backendUrl}/peticion_departamento.php`, {
          data: { id: asignacionId }
        });
        
        if (response.data.success) {
          if (window.$toast) {
            window.$toast.success('Asignación eliminada correctamente');
          }
          
          // Recargar departamentos asignados
          await cargarDepartamentosAsignados(peticionForm.id);
          
          // Recargar peticiones para actualizar estados
          await cargarPeticiones();
        }
        
      } catch (error) {
        console.error('Error al eliminar asignación:', error);
        if (window.$toast) {
          window.$toast.error('Error al eliminar la asignación');
        }
      } finally {
        if (window.$loading) {
          window.$loading.hide();
        }
      }
    };
    
    // Función para cambiar estado de asignación
    const cambiarEstadoAsignacion = async (asignacionId, nuevoEstado) => {
      try {
        if (window.$loading) {
          window.$loading.show();
        }
        
        const response = await axios.put(`${backendUrl}/peticion_departamento.php`, {
          id: asignacionId,
          estado: nuevoEstado
        });
        
        if (response.data.success) {
          if (window.$toast) {
            window.$toast.success('Estado actualizado correctamente');
          }
          
          // Recargar departamentos asignados
          await cargarDepartamentosAsignados(peticionForm.id);
        }
        
      } catch (error) {
        console.error('Error al cambiar estado:', error);
        if (window.$toast) {
          window.$toast.error('Error al cambiar el estado');
        }
      } finally {
        if (window.$loading) {
          window.$loading.hide();
        }
      }
    };
    
    const seguimiento = async (peticion) => {
      try {
        const usuarioId = await obtenerUsuarioLogueado();
        
        if (!usuarioId) {
          if (window.$toast) {
            window.$toast.error('No se pudo obtener la información del usuario logueado. Por favor, inicie sesión nuevamente.');
          }
          console.error('No se pudo obtener el ID del usuario logueado');
          return;
        }
        
        if (tieneUsuarioAsignado(peticion)) {
          if (peticion.usuario_id === usuarioId) {
            if (window.$toast) {
              window.$toast.info('Esta petición ya está asignada a su usuario.');
            }
            return;
          }
          
          const nombreUsuarioAsignado = peticion.nombre_completo_usuario || peticion.nombre_usuario_seguimiento || 'Usuario desconocido';
          const confirmar = confirm(`Esta petición ya está asignada a: ${nombreUsuarioAsignado}.\n¿Desea reasignarla a su usuario?`);
          if (!confirmar) {
            return;
          }
        }
        
        if (window.$loading) {
          window.$loading.show();
        }
        
        const datosFollowup = {
          accion: 'seguimiento',
          peticion_id: peticion.id,
          usuario_id: usuarioId
        };
        
        const response = await axios.post(`${backendUrl}/peticiones.php`, datosFollowup);
        
        if (response.status === 200) {
          const nombreCompleto = response.data.nombre_completo || response.data.usuario_asignado || usuarioLogueado.value?.Nombre || 'Usuario';
          
          if (window.$toast) {
            window.$toast.success(`Seguimiento asignado correctamente a ${nombreCompleto}`);
          }
          
          await cargarPeticiones();
          peticionActiva.value = null;
        }
        
      } catch (error) {
        console.error('Error al asignar seguimiento:', error);
        
        let mensajeError = 'Error al asignar seguimiento';
        if (error.response) {
          if (error.response.status === 404) {
            mensajeError = 'Petición o usuario no encontrado';
          } else if (error.response.data && error.response.data.message) {
            mensajeError = error.response.data.message;
          }
        }
        
        if (window.$toast) {
          window.$toast.error(mensajeError);
        }
      } finally {
        if (window.$loading) {
          window.$loading.hide();
        }
      }
    };
    
    const guardarPeticion = async () => {
      try {
        await axios.put(`${backendUrl}/peticiones.php`, peticionForm);
        
        if (window.$toast) {
          window.$toast.success('Petición actualizada correctamente');
        }
        
        showEditModal.value = false;
        await cargarPeticiones();
      } catch (error) {
        console.error('Error al guardar petición:', error);
        if (window.$toast) {
          window.$toast.error('Error al guardar la petición');
        }
      }
    };
    
    const guardarEstado = async () => {
      try {
        await axios.put(`${backendUrl}/peticiones.php`, {
          id: peticionForm.id,
          estado: peticionForm.estado
        });
        
        if (window.$toast) {
          window.$toast.success('Estado actualizado correctamente');
        }
        
        showEstadoModal.value = false;
        await cargarPeticiones();
      } catch (error) {
        console.error('Error al guardar estado:', error);
        if (window.$toast) {
          window.$toast.error('Error al guardar el estado');
        }
      }
    };
    
    const guardarImportancia = async () => {
      try {
        await axios.put(`${backendUrl}/peticiones.php`, {
          id: peticionForm.id,
          NivelImportancia: parseInt(peticionForm.NivelImportancia)
        });
        
        if (window.$toast) {
          window.$toast.success('Nivel de importancia actualizado correctamente');
        }
        
        showImportanciaModal.value = false;
        await cargarPeticiones();
      } catch (error) {
        console.error('Error al actualizar nivel de importancia:', error);
        if (window.$toast) {
          window.$toast.error('Error al actualizar nivel de importancia');
        }
      }
    };

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
    
    const toggleAccionesMenu = (peticion) => {
      if (peticionActiva.value === peticion.id) {
        peticionActiva.value = null;
      } else {
        peticionActiva.value = peticion.id;
      }
    };
    
    const cerrarMenuAcciones = () => {
      peticionActiva.value = null;
    };
    
    const cerrarMenusAcciones = (event) => {
      if (event.target.closest('.acciones-dropdown') || 
          event.target.closest('.action-btn.menu')) {
        return;
      }
      
      peticionActiva.value = null;
    };
    
    const cancelarAccion = () => {
      showEditModal.value = false;
      showEstadoModal.value = false;
      showDepartamentosModal.value = false;
      showImportanciaModal.value = false;
    };
    
    const filtrarMisPeticiones = async () => {
      try {
        const usuarioId = await obtenerUsuarioLogueado();
        if (usuarioId) {
          filtros.usuario_seguimiento = usuarioId.toString();
        }
      } catch (error) {
        console.error('Error al filtrar mis peticiones:', error);
      }
    };
    
    const limpiarFiltros = () => {
      Object.keys(filtros).forEach(key => {
        filtros[key] = '';
      });
    };
    
    onMounted(async () => {
      await obtenerUsuarioLogueado();
      await Promise.all([
        cargarPeticiones(),
        cargarDepartamentos()
      ]);
      
      document.addEventListener('click', cerrarMenusAcciones);
    });
    
    onBeforeUnmount(() => {
      document.removeEventListener('click', cerrarMenusAcciones);
    });
    
    return {
      loading,
      peticiones,
      peticionesFiltradas,
      departamentos,
      showEditModal,
      showEstadoModal,
      showDepartamentosModal,
      showImportanciaModal,
      peticionForm,
      filtros,
      peticionActiva,
      usuarioLogueado,
      departamentosAsignados,
      departamentosDisponibles,
      departamentosSeleccionados,
      loadingDepartamentos,
      
      cargarPeticiones,
      cargarDepartamentos,
      formatearFecha,
      obtenerNombreDepartamento,
      obtenerColorSemaforo,
      obtenerTituloSemaforo,
      aplicarFiltros,
      editarPeticion,
      cambiarEstado,
      cambiarImportancia,
      seguimiento,
      gestionarDepartamentos,
      asignarDepartamentos,
      eliminarDepartamentoAsignado,
      cambiarEstadoAsignacion,
      obtenerDepartamentosPeticion,
      formatearDepartamentosResumen,
      
      guardarPeticion,
      guardarEstado,
      guardarImportancia,
      toggleAccionesMenu,
      cerrarMenuAcciones,
      cancelarAccion,
      filtrarMisPeticiones,
      limpiarFiltros,
      tieneUsuarioAsignado,
      obtenerIconoSeguimiento,
      obtenerTituloSeguimiento,
      obtenerClaseSeguimiento,
      esUsuarioAsignado,
      obtenerUsuarioLogueado,
      obtenerInfoUsuarioLogueado,
      obtenerEtiquetaNivelImportancia
    };
  }
};
</script>

<style scoped>
/* ESTILOS FIJOS PARA BOTÓN DE ACCIONES - ALTA ESPECIFICIDAD */
.peticion-acciones .action-btn.menu {
  width: 36px !important;
  height: 36px !important;
  border-radius: 8px !important;
  border: none !important;
  background-color: #518dce !important;
  color: #ffffff !important;
  cursor: pointer !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  position: relative !important;
  z-index: 1 !important;
  /* Sin transiciones para evitar animaciones */
  transition: none !important;
  transform: none !important;
  animation: none !important;
}

.peticion-acciones .action-btn.menu:hover {
  background-color: #043c74 !important;
  color: #ffffff !important;
  /* Sin transiciones */
  transition: none !important;
  transform: none !important;
}

.peticion-acciones .action-btn.menu.active {
  background-color: #013c7c !important;
  color: white !important;
  /* Sin transiciones */
  transition: none !important;
  transform: none !important;
}

.peticion-acciones .action-btn.menu i {
  font-size: 14px !important;
  /* Sin transiciones */
  transition: none !important;
  transform: none !important;
}

/* Contenedor de acciones con posición relativa */
.peticion-acciones {
  position: relative !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  min-width: 50px !important;
}

/* Dropdown de acciones - alta especificidad */
.peticion-acciones .acciones-dropdown {
  position: absolute !important;
  top: 100% !important;
  left: 0 !important;
  background: white !important;
  border: 1px solid #dee2e6 !important;
  border-radius: 8px !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
  z-index: 1000 !important;
  min-width: 200px !important;
  padding: 8px 0 !important;
  margin-top: 4px !important;
  display: none !important;
  /* Sin animaciones */
  transition: none !important;
  transform: none !important;
  animation: none !important;
}

.peticion-acciones .acciones-dropdown.show {
  display: block !important;
}

.peticion-acciones .dropdown-item {
  width: 100% !important;
  padding: 10px 16px !important;
  background: none !important;
  border: none !important;
  text-align: left !important;
  cursor: pointer !important;
  color: #495057 !important;
  font-size: 14px !important;
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  /* Sin transiciones */
  transition: none !important;
  transform: none !important;
}

.peticion-acciones .dropdown-item:hover {
  background-color: #f8f9fa !important;
  color: #007bff !important;
  /* Sin transiciones */
  transition: none !important;
  transform: none !important;
}

.peticion-acciones .dropdown-item i {
  width: 16px !important;
  text-align: center !important;
  /* Sin transiciones */
  transition: none !important;
  transform: none !important;
}

/* Overlay para cerrar dropdown */
.peticion-acciones .dropdown-overlay {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: 999 !important;
  background: transparent !important;
  cursor: default !important;
}

/* Asegurar que el contenedor de la petición no interfiera */
.peticion-item {
  position: relative !important;
  z-index: 1 !important;
}

/* Cuando hay dropdown activo, aumentar z-index del contenedor */
.peticion-item:has(.acciones-dropdown.show) {
  z-index: 1001 !important;
}

/* Fallback para navegadores sin soporte :has() */
.peticion-acciones:has(.acciones-dropdown.show) {
  z-index: 1001 !important;
}

.peticion-acciones .acciones-dropdown.show ~ * {
  z-index: auto !important;
}
/* --------------------------------------------------------------------------------------- */
 * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Contenedor principal */
        .peticiones-container {
            margin: 2rem auto;
            padding: 1.5rem;
            width: 95%;
            max-width: 1600px;
        }

        .card {
            background: #ffffff;
            color: black;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid rgba(0, 74, 217, 0.1);
            overflow: hidden;
            position: relative;
        }

        .card-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #0074D9, #0056b3);
            color: white;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-filter, .btn-clear {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-filter:hover, .btn-clear:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .card-body {
            padding: 1.5rem;
        }

        .welcome-message {
            font-size: 16px;
            font-weight: 500;
            color: #0074D9;
            margin-bottom: 1rem;
        }

        /* Filtros */
        .filtros-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(0, 116, 217, 0.05), rgba(0, 86, 179, 0.08));
            border-radius: 12px;
            border: 1px solid rgba(0, 116, 217, 0.1);
        }

        .filtro label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .filtro input, .filtro select {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #0074D9;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: white;
        }

        .filtro input:focus, .filtro select:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 116, 217, 0.1);
        }

        /* TABLA CON SCROLL HORIZONTAL MEJORADO */
        .peticiones-list {
            margin-top: 1.5rem;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 116, 217, 0.1);
            position: relative;
            overflow: hidden;
        }

        .tabla-scroll-container {
            overflow-x: auto;
            overflow-y: visible;
            position: relative;
            /* Scrollbar personalizado */
            scrollbar-width: thin;
            scrollbar-color: #0074D9 #f1f3f4;
        }

        .tabla-scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .tabla-scroll-container::-webkit-scrollbar-track {
            background: #f1f3f4;
            border-radius: 4px;
        }

        .tabla-scroll-container::-webkit-scrollbar-thumb {
            background: #0074D9;
            border-radius: 4px;
        }

        .tabla-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #0056b3;
        }

        .tabla-contenido {
            min-width: 1200px; /* Ancho mínimo para evitar compresión */
            width: 100%;
        }

        /* HEADER FIJO QUE CUBRE TODO EL ANCHO */
        .list-header {
            display: grid;
            grid-template-columns: 100px 120px 200px 130px 150px 180px 200px 180px 150px;
            background: linear-gradient(135deg, #0074D9, #0056b3);
            color: white;
            padding: 1rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
            /* ASEGURAR QUE CUBRA TODO EL ANCHO */
            width: 100%;
            min-width: 1200px;
        }

        .peticion-item {
            display: grid;
            grid-template-columns: 100px 120px 200px 130px 150px 180px 200px 180px 150px;
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 116, 217, 0.1);
            transition: background-color 0.3s ease;
            background: white;
            position: relative;
            min-height: 70px;
            align-items: center;
        }

        .peticion-item:hover {
            background: linear-gradient(135deg, rgba(0, 116, 217, 0.03), rgba(0, 86, 179, 0.05));
        }

        .peticion-item:last-child {
            border-bottom: none;
        }

        .peticion-info {
            display: flex;
            align-items: center;
            min-height: 40px;
            padding: 0.25rem;
            word-break: break-word;
            font-weight: 500;
            color: #333;
        }

        /* INDICADORES HORIZONTALES MEJORADOS */
        .indicadores-container {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-start;
            flex-wrap: nowrap;
            min-width: 160px;
        }

        .nivel-importancia {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.9rem;
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            cursor: help;
            flex-shrink: 0;
        }

        .nivel-importancia:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .nivel-importancia.nivel-1 {
            background: linear-gradient(135deg, #FF4136, #e74c3c);
        }

        .nivel-importancia.nivel-2 {
            background: linear-gradient(135deg, #FF851B, #e67600);
        }

        .nivel-importancia.nivel-3 {
            background: linear-gradient(135deg, #FFDC00, #f1c40f);
            color: #333;
        }

        .nivel-importancia.nivel-4 {
            background: linear-gradient(135deg, #2ECC40, #27ae60);
        }

        .nivel-importancia.nivel-5 {
            background: linear-gradient(135deg, #2ECC40, #1e8e3e);
        }

        .semaforo {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: help;
            flex-shrink: 0;
        }

        .semaforo:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .semaforo.verde {
            background: linear-gradient(135deg, #2ECC40, #27ae60);
        }

        .semaforo.amarillo {
            background: linear-gradient(135deg, #FFDC00, #f1c40f);
        }

        .semaforo.naranja {
            background: linear-gradient(135deg, #FF851B, #e67600);
        }

        .semaforo.rojo {
            background: linear-gradient(135deg, #FF4136, #e74c3c);
        }

        .seguimiento-indicator {
            font-size: 20px;
            cursor: help;
            transition: all 0.3s ease;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
        }

        .seguimiento-indicator:hover {
            transform: scale(1.1);
        }

        .seguimiento-asignado {
            color: #28a745;
        }

        .seguimiento-sin-asignar {
            color: #6c757d;
        }

        /* ACCIONES Y DROPDOWN MEJORADO */
        .peticion-acciones {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 5; /* Z-index base para el contenedor */
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            font-weight: bold;
            position: relative;
        }

        /* .action-btn.menu {
            background: linear-gradient(135deg, #0074D9, #0056b3);
        } */

        /* .action-btn.menu:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .action-btn.menu.active {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        } */

        /* DROPDOWN DE ACCIONES CON Z-INDEX ALTO */
        .acciones-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 220px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(0, 116, 217, 0.1);
            z-index: 9999; /* Z-index muy alto para estar encima de todo */
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .acciones-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Ajuste automático del dropdown */
        .acciones-dropdown.show-up {
            top: auto;
            bottom: calc(100% + 8px);
        }

        .acciones-dropdown.show-left {
            left: auto;
            right: 0;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.85rem 1rem;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(0, 116, 217, 0.08), rgba(0, 86, 179, 0.1));
            color: #0074D9;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        /* OVERLAY PARA CERRAR DROPDOWN - REMOVIDO PARA PERMITIR SCROLL */
        /* Ya no necesitamos el overlay que bloquea el scroll */
        .dropdown-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9998;
            background: transparent;
            /* PERMITIR EVENTOS DE SCROLL */
            pointer-events: none;
        }

        /* Asegurar que el dropdown mantenga su funcionalidad */
        .acciones-dropdown {
            /* PERMITIR QUE EL DROPDOWN CAPTURE EVENTOS MIENTRAS PERMITE SCROLL EN EL FONDO */
            pointer-events: auto;
        }

        /* Estados y badges */
        .estado-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .folio-badge {
            background: linear-gradient(135deg, #0074D9, #0056b3);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .nombre-peticion {
            font-weight: 600;
            color: #333;
        }

        .telefono, .localidad {
            color: #666;
            font-size: 0.9rem;
        }

        .departamentos-resumen {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.3;
        }

        .fecha-registro {
            font-size: 0.85rem;
            color: #666;
        }

        /* Estados específicos */
        .estado-sin-revisar {
            background: #fff3cd;
            color: #856404;
        }

        .estado-completado {
            background: #d4edda;
            color: #155724;
        }

        .estado-en-proceso {
            background: #d1ecf1;
            color: #0c5460;
        }

        .estado-pendiente {
            background: #f8d7da;
            color: #721c24;
        }

        .estado-rechazado-por-departamento {
            background: #f8d7da;
            color: #721c24;
        }

        .estado-por-asignar-departamento {
            background: #fff3cd;
            color: #856404;
        }

        .estado-aceptada-en-proceso {
            background: #d1ecf1;
            color: #0c5460;
        }

        .estado-devuelto {
            background: #ffeaa7;
            color: #856404;
        }

        .estado-improcedente {
            background: #e2e3e5;
            color: #383d41;
        }

        .estado-cancelada {
            background: #f8d7da;
            color: #721c24;
        }

        .estado-esperando-recepcion {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Mensajes de estado */
        .loading-message, .empty-message {
            padding: 3rem;
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .loading-message i {
            margin-right: 0.5rem;
            color: #0074D9;
        }

        .empty-message i {
            margin-right: 0.5rem;
            color: #6c757d;
        }

        /* Tooltips */
        [title] {
            position: relative;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .peticiones-container {
                width: 100%;
                padding: 1rem;
            }

            .card-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-actions {
                justify-content: center;
            }

            .filtros-container {
                grid-template-columns: 1fr;
            }

            .tabla-contenido {
                min-width: 900px;
            }

            .list-header,
            .peticion-item {
                grid-template-columns: 80px 100px 150px 100px 120px 140px 160px 140px 120px;
            }

            .list-header {
                min-width: 900px;
            }

            .indicadores-container {
                gap: 4px;
                min-width: 120px;
            }

            .nivel-importancia {
                width: 24px;
                height: 24px;
                font-size: 0.8rem;
            }

            .semaforo {
                width: 20px;
                height: 20px;
            }

            .seguimiento-indicator {
                width: 24px;
                height: 24px;
                font-size: 16px;
            }

            .acciones-dropdown {
                width: 180px;
            }
        }

        @media (max-width: 576px) {
            .tabla-contenido {
                min-width: 800px;
            }

            .list-header,
            .peticion-item {
                grid-template-columns: 70px 90px 140px 90px 100px 120px 140px 120px 100px;
                padding: 0.75rem;
                font-size: 0.85rem;
            }

            .list-header {
                min-width: 800px;
            }
        }




        /*-------------------------------------------------------------------*/
              /* Estilos para modales */
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
        z-index: 10000;
        backdrop-filter: blur(3px);
      }

      .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(0, 116, 217, 0.1);
      }

      .modal-departamentos {
        max-width: 800px;
      }

      .modal-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #0074D9, #0056b3);
        color: white;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
      }

      .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 4px;
        transition: background 0.3s ease;
      }

      .close-btn:hover {
        background: rgba(255, 255, 255, 0.2);
      }

      .modal-body {
        padding: 1.5rem;
      }

      .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(0, 116, 217, 0.1);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
      }

      /* Estilos para formularios */
      .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
      }

      .form-group {
        margin-bottom: 1rem;
      }

      .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
      }

      .form-group input,
      .form-group textarea,
      .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #0074D9;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
      }

      .form-group input:focus,
      .form-group textarea:focus,
      .form-group select:focus {
        outline: none;
        border-color: #0056b3;
        box-shadow: 0 0 0 3px rgba(0, 116, 217, 0.1);
      }

      .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0, 116, 217, 0.1);
      }

      .btn-primary {
        background: linear-gradient(135deg, #0074D9, #0056b3);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .btn-primary:hover:not(:disabled) {
        background: linear-gradient(135deg, #0056b3, #004085);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      }

      .btn-primary:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
      }

      .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      }

      /* Estilos para gestión de departamentos */
      .departamentos-section {
        margin-bottom: 2rem;
      }

      .departamentos-section-title {
        color: #0074D9;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid rgba(0, 116, 217, 0.2);
      }

      .no-departamentos {
        padding: 1rem;
        text-align: center;
        color: #666;
        background: rgba(0, 116, 217, 0.05);
        border-radius: 8px;
        border: 1px solid rgba(0, 116, 217, 0.1);
      }

      .no-departamentos i {
        margin-right: 0.5rem;
        color: #0074D9;
      }

      .departamentos-asignados-list {
        border: 1px solid rgba(0, 116, 217, 0.1);
        border-radius: 8px;
        background: white;
      }

      .departamento-asignado-item {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 116, 217, 0.1);
        align-items: center;
      }

      .departamento-asignado-item:last-child {
        border-bottom: none;
      }

      .departamento-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
      }

      .departamento-nombre {
        font-weight: 600;
        color: #333;
      }

      .departamento-siglas {
        font-size: 0.85rem;
        color: #666;
        font-style: italic;
      }

      .estado-select {
        padding: 0.5rem;
        border: 1px solid #0074D9;
        border-radius: 6px;
        background: white;
        min-width: 150px;
      }

      .btn-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .btn-danger:hover {
        background: linear-gradient(135deg, #c82333, #a71e2a);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
      }

      .btn-sm {
        padding: 0.4rem 0.6rem;
        font-size: 0.85rem;
      }

      .departamentos-checkboxes {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.5rem;
      }

      .departamento-checkbox {
        background: rgba(0, 116, 217, 0.03);
        border: 1px solid rgba(0, 116, 217, 0.1);
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
      }

      .departamento-checkbox:hover {
        background: rgba(0, 116, 217, 0.05);
        border-color: rgba(0, 116, 217, 0.2);
      }

      .checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        margin: 0;
      }

      .checkbox-input {
        width: auto;
        margin: 0;
      }

      .checkmark {
        width: 18px;
        height: 18px;
        border: 2px solid #0074D9;
        border-radius: 3px;
        position: relative;
        flex-shrink: 0;
      }

      .checkbox-input:checked + .checkmark {
        background: #0074D9;
      }

      .checkbox-input:checked + .checkmark::after {
        content: '✓';
        position: absolute;
        top: -2px;
        left: 2px;
        color: white;
        font-weight: bold;
        font-size: 0.8rem;
      }

      .departamento-label {
        font-size: 0.9rem;
        font-weight: 500;
        color: #333;
      }

      .departamento-small {
        color: #666;
        font-weight: 400;
      }

      .asignar-actions {
        text-align: center;
      }
</style>