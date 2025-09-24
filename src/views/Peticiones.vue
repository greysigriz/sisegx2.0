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

              <div v-else v-for="peticion in peticionesPaginadas" :key="peticion.id" class="peticion-item">
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

        <!-- Paginación -->
        <div class="paginacion-container">
          <div class="paginacion-controles">
            <div class="registros-por-pagina">
              <label for="registrosPorPagina">Mostrar:</label>
              <select
                id="registrosPorPagina"
                :value="paginacion.registrosPorPagina"
                @change="cambiarRegistrosPorPagina(parseInt($event.target.value))"
                class="select-registros"
              >
                <option v-for="opcion in opcionesPaginacion" :key="opcion" :value="opcion">
                  {{ opcion }}
                </option>
              </select>
              <span class="registros-info">
                registros por página
              </span>
            </div>

            <!-- Información de registros -->
            <div class="info-registros">
              <span v-if="paginacion.totalRegistros > 0">
                Mostrando {{ ((paginacion.paginaActual - 1) * paginacion.registrosPorPagina) + 1 }}
                a {{ Math.min(paginacion.paginaActual * paginacion.registrosPorPagina, paginacion.totalRegistros) }}
                de {{ paginacion.totalRegistros }} registros
              </span>
              <span v-else>No hay registros</span>
            </div>
          </div>

          <!-- Navegación de páginas -->
          <div v-if="paginacion.totalPaginas > 1" class="paginacion-navegacion">
            <!-- Botón Primera -->
            <button
              @click="irAPagina(1)"
              :disabled="paginacion.paginaActual === 1"
              class="btn-paginacion btn-extremo"
              title="Primera página"
            >
              <i class="fas fa-angle-double-left"></i>
            </button>

            <!-- Botón Anterior -->
            <button
              @click="paginaAnterior"
              :disabled="paginacion.paginaActual === 1"
              class="btn-paginacion btn-nav"
              title="Página anterior"
            >
              <i class="fas fa-angle-left"></i>
            </button>

            <!-- Números de página -->
            <div class="numeros-pagina">
              <button
                v-for="pagina in paginasVisibles"
                :key="pagina"
                @click="pagina !== '...' && irAPagina(pagina)"
                :class="[

                  'btn-paginacion',
                  'btn-numero',
                  {
                    'activa': pagina === paginacion.paginaActual,
                    'puntos': pagina === '...'
                  }
                ]"
                :disabled="pagina === '...'"
              >
                {{ pagina }}
              </button>
            </div>

            <!-- Botón Siguiente -->
            <button
              @click="paginaSiguiente"
              :disabled="paginacion.paginaActual === paginacion.totalPaginas"
              class="btn-paginacion btn-nav"
              title="Página siguiente"
            >
              <i class="fas fa-angle-right"></i>
            </button>

            <!-- Botón Última -->
            <button
              @click="irAPagina(paginacion.totalPaginas)"
              :disabled="paginacion.paginaActual === paginacion.totalPaginas"
              class="btn-paginacion btn-extremo"
              title="Última página"
            >
              <i class="fas fa-angle-double-right"></i>
            </button>
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
            <i class="fas fa-spinner fa-spin"></i> Cargando información...
          </div>

          <div v-else>
            <!-- ✅ NUEVA SECCIÓN: Sugerencias de IA -->
            <div v-if="sugerenciasIA && sugerenciasIA.length > 0" class="departamentos-section">
              <h4 class="departamentos-section-title">
                <i class="fas fa-robot"></i> Sugerencias de IA
              </h4>

              <div class="sugerencias-list">
                <div
                  v-for="sugerencia in sugerenciasIA"
                  :key="'sug-' + sugerencia.id"
                  class="sugerencia-item"
                  :class="`sugerencia-${sugerencia.estado.toLowerCase()}`"
                >
                  <div class="sugerencia-info">
                    <div class="sugerencia-nombre">
                      <i class="fas fa-brain"></i>
                      {{ sugerencia.departamento_nombre }}
                    </div>
                    <div class="sugerencia-fecha">
                      Sugerido: {{ formatearFecha(sugerencia.fecha) }}
                    </div>
                  </div>

                  <div class="sugerencia-estado">
                    <span class="estado-badge" :class="`estado-${sugerencia.estado.toLowerCase()}`">
                      {{ sugerencia.estado }}
                    </span>
                  </div>

                  <!-- Botón para asignar si está disponible -->
                  <div class="sugerencia-acciones" v-if="sugerencia.estado === 'Pendiente'">
                    <button
                      @click="asignarDesdeSugerencia(sugerencia)"
                      class="btn-asignar-sugerencia"
                      title="Asignar este departamento"
                    >
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Departamentos Asignados -->
            <div class="departamentos-section">
              <h4 class="departamentos-section-title">
                <i class="fas fa-check-circle"></i> Departamentos Asignados
              </h4>

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
              <h4 class="departamentos-section-title">
                <i class="fas fa-plus-circle"></i> Asignar Nuevos Departamentos
              </h4>

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
import { ref, reactive, onMounted, onBeforeUnmount, watch, computed } from 'vue';

export default {
  name: 'GestionPeticiones',
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

    const sugerenciasIA = ref([]);

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

        // Inicializar paginación
        actualizarPaginacion();

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
        loadingDepartamentos.value = true;
        console.log('🔄 Cargando unidades desde API...');

        const response = await axios.get(`${backendUrl}/unidades.php`);
        console.log('📦 Respuesta unidades:', response.data);

        if (response.data && response.data.records) {
          departamentos.value = response.data.records;
          console.log('✅ Unidades cargadas:', departamentos.value.length);
        } else {
          console.warn('⚠️ No se encontraron unidades');
          departamentos.value = [];
        }

      } catch (error) {
        console.error('❌ Error al cargar unidades:', error);
        departamentos.value = [];
        if (window.$toast) {
          window.$toast.error('Error al cargar departamentos');
        }
      } finally {
        loadingDepartamentos.value = false;
      }
    };

    // Función para cargar departamentos asignados a una petición específica
    const cargarDepartamentosAsignados = async (peticionId) => {
      try {
        loadingDepartamentos.value = true;
        const response = await axios.get(`${backendUrl}/peticion_departamento.php?peticion_id=${peticionId}`);

        if (response.data.success) {
          departamentosAsignados.value = response.data.departamentos || [];
          // ✅ NUEVO: Cargar también las sugerencias
          sugerenciasIA.value = response.data.sugerencias || [];

          // Filtrar departamentos disponibles (excluir los ya asignados)
          const idsAsignados = departamentosAsignados.value.map(d => d.id_unidad || d.departamento_id);
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

    // En el setup(), agregar variables para paginación:
    const paginacion = reactive({
      paginaActual: 1,
      registrosPorPagina: 20, // Cambiable por el usuario
      totalRegistros: 0,
      totalPaginas: 0
    });

    const opcionesPaginacion = [10, 20, 50, 100];

    // Computed para peticiones paginadas
    const peticionesPaginadas = computed(() => {
      const inicio = (paginacion.paginaActual - 1) * paginacion.registrosPorPagina;
      const fin = inicio + paginacion.registrosPorPagina;
      return peticionesFiltradas.value.slice(inicio, fin);
    });

    // Función para actualizar paginación cuando cambian los filtros
    const actualizarPaginacion = () => {
      paginacion.totalRegistros = peticionesFiltradas.value.length;
      paginacion.totalPaginas = Math.ceil(paginacion.totalRegistros / paginacion.registrosPorPagina);

      // Si la página actual es mayor que el total de páginas, ir a la primera
      if (paginacion.paginaActual > paginacion.totalPaginas && paginacion.totalPaginas > 0) {
        paginacion.paginaActual = 1;
      }
    };

    // Funciones de navegación
    const irAPagina = (pagina) => {
      if (pagina >= 1 && pagina <= paginacion.totalPaginas) {
        paginacion.paginaActual = pagina;
      }
    };

    const paginaAnterior = () => {
      if (paginacion.paginaActual > 1) {
        paginacion.paginaActual--;
      }
    };

    const paginaSiguiente = () => {
      if (paginacion.paginaActual < paginacion.totalPaginas) {
        paginacion.paginaActual++;
      }
    };

    const cambiarRegistrosPorPagina = (nuevaCantidad) => {
      paginacion.registrosPorPagina = nuevaCantidad;
      paginacion.paginaActual = 1; // Volver a la primera página
      actualizarPaginacion();
    };

    // Computed para generar números de páginas visibles
    const paginasVisibles = computed(() => {
      const total = paginacion.totalPaginas;
      const actual = paginacion.paginaActual;
      const ventana = 5; // Mostrar 5 páginas alrededor de la actual

      if (total <= ventana + 2) {
        // Si hay pocas páginas, mostrar todas
        return Array.from({ length: total }, (_, i) => i + 1);
      }

      let inicio = Math.max(1, actual - Math.floor(ventana / 2));
      let fin = Math.min(total, inicio + ventana - 1);

      // Ajustar si estamos cerca del final
      if (fin - inicio < ventana - 1) {
        inicio = Math.max(1, fin - ventana + 1);
      }

      const pages = [];

      // Siempre mostrar la primera página
      if (inicio > 1) {
        pages.push(1);
        if (inicio > 2) {
          pages.push('...');
        }
      }

      // Páginas del rango
      for (let i = inicio; i <= fin; i++) {
        pages.push(i);
      }

      // Siempre mostrar la última página
      if (fin < total) {
        if (fin < total - 1) {
          pages.push('...');
        }
        pages.push(total);
      }

      return pages;
    });

    // Modificar la función aplicarFiltros para actualizar paginación
    const aplicarFiltros = () => {
      try {
        let peticionesFiltradas_temp = [...peticiones.value];

        // Aplicar filtros con validaciones robustas
        peticionesFiltradas_temp = peticionesFiltradas_temp.filter(peticion => {
          // Validar que peticion existe
          if (!peticion) return false;

          // Filtrar por estado
          if (filtros.estado && peticion.estado !== filtros.estado) {
            return false;
          }

          // Filtrar por nivel de importancia
          if (filtros.nivelImportancia) {
            const nivel = parseInt(filtros.nivelImportancia);
            const peticionNivel = parseInt(peticion.NivelImportancia);
            if (isNaN(peticionNivel) || peticionNivel !== nivel) {
              return false;
            }
          }

          // Filtrar por usuario de seguimiento
          if (filtros.usuario_seguimiento) {
            const usuarioFiltro = parseInt(filtros.usuario_seguimiento);
            const usuarioPeticion = parseInt(peticion.usuario_id);
            if (isNaN(usuarioPeticion) || usuarioPeticion !== usuarioFiltro) {
              return false;
            }
          }

          // ✅ Filtrar por folio con validación robusta
          if (filtros.folio && filtros.folio.trim() !== '') {
            const folioPeticion = peticion.folio || '';
            const folioFiltro = filtros.folio.trim();

            if (!folioPeticion.toLowerCase().includes(folioFiltro.toLowerCase())) {
              return false;
            }
          }

          // ✅ Filtrar por nombre con validación robusta
          if (filtros.nombre && filtros.nombre.trim() !== '') {
            const nombrePeticion = peticion.nombre || '';
            const nombreFiltro = filtros.nombre.trim();

            if (!nombrePeticion.toLowerCase().includes(nombreFiltro.toLowerCase())) {
              return false;
            }
          }

          return true;
        });

        // Aplicamos el ordenamiento a los resultados filtrados
        peticionesFiltradas.value = ordenarPeticionesPorPrioridad(peticionesFiltradas_temp);

        // ✅ NUEVO: Actualizar paginación después de filtrar
        actualizarPaginacion();

      } catch (error) {
        console.error('Error en aplicarFiltros:', error);
        // En caso de error, mostrar todas las peticiones
        peticionesFiltradas.value = [...peticiones.value];
        actualizarPaginacion();
      }
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

    // Función para asignar desde sugerencia
    const asignarDesdeSugerencia = async (sugerencia) => {
      // Buscar el departamento por nombre
      const departamento = departamentos.value.find(d =>
        d.nombre_unidad.toLowerCase().includes(sugerencia.departamento_nombre.toLowerCase()) ||
        sugerencia.departamento_nombre.toLowerCase().includes(d.nombre_unidad.toLowerCase())
      );

      if (!departamento) {
        if (window.$toast) {
          window.$toast.warning(`No se encontró el departamento "${sugerencia.departamento_nombre}" en el sistema`);
        }
        return;
      }

      // Asignar el departamento encontrado
      departamentosSeleccionados.value = [departamento.id];
      await asignarDepartamentos();
    };

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

      // Paginación
      paginacion,
      opcionesPaginacion,
      peticionesPaginadas,
      paginasVisibles,

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
      obtenerEtiquetaNivelImportancia,

      sugerenciasIA,
      asignarDesdeSugerencia,

      // Funciones de paginación
      irAPagina,
      paginaAnterior,
      paginaSiguiente,
      cambiarRegistrosPorPagina,
      actualizarPaginacion,
    };
  }
};
</script>
<style src="@/assets/css/Petition.css"></style>
