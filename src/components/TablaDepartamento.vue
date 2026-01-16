<template>
  <div class="tabla-departamento-container">
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

        <!-- Filtros -->
        <div v-if="!errorDepartamento" class="filtros-container">
          <div class="filtro">
            <label for="filtroBusqueda">
              <i class="fas fa-search"></i> Buscar Folio:
            </label>
            <input
              type="text"
              id="filtroBusqueda"
              v-model="filtros.busqueda"
              placeholder="Buscar por folio..."
            >
          </div>
          <div class="filtro">
            <label for="filtroEstado">
              <i class="fas fa-tag"></i> Estado:
            </label>
            <select id="filtroEstado" v-model="filtros.estado">
              <option value="">Todos</option>
              <option value="Esperando recepción">Esperando recepción</option>
              <option value="Aceptado en proceso">Aceptado en proceso</option>
              <option value="Devuelto a seguimiento">Devuelto a seguimiento</option>
              <option value="Rechazado">Rechazado</option>
              <option value="Completado">Completado</option>
            </select>
          </div>
          <div class="filtro">
            <label for="filtroImportancia">
              <i class="fas fa-exclamation-circle"></i> Importancia:
            </label>
            <select id="filtroImportancia" v-model="filtros.importancia">
              <option value="">Todas</option>
              <option value="1">1 - Muy Alta</option>
              <option value="2">2 - Alta</option>
              <option value="3">3 - Media</option>
              <option value="4">4 - Baja</option>
              <option value="5">5 - Muy Baja</option>
            </select>
          </div>
          <div class="filtro">
            <label for="filtroOrdenar">
              <i class="fas fa-sort"></i> Ordenar por:
            </label>
            <select id="filtroOrdenar" v-model="filtros.ordenarPor">
              <option value="fecha_desc">Más reciente primero</option>
              <option value="fecha_asc">Más antigua primero</option>
              <option value="importancia">Nivel de Importancia</option>
              <option value="estado">Estado</option>
            </select>
          </div>
          <div class="filtro filtro-fecha">
            <label for="filtroFechaDesde">
              <i class="fas fa-calendar-alt"></i> Desde:
            </label>
            <input type="date" id="filtroFechaDesde" v-model="filtros.fechaDesde">
          </div>
          <div class="filtro filtro-fecha">
            <label for="filtroFechaHasta">
              <i class="fas fa-calendar-alt"></i> Hasta:
            </label>
            <input type="date" id="filtroFechaHasta" v-model="filtros.fechaHasta">
          </div>
          <div class="filtro-acciones">
            <button @click="limpiarFiltros" class="btn-clear-filters">
              <i class="fas fa-times"></i> Limpiar Filtros
            </button>
          </div>
        </div>

        <!-- Mensajes de Estado -->
        <div v-if="errorDepartamento" class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          <div>{{ errorDepartamento }}</div>
        </div>

        <div v-else-if="loading" class="loading-message">
          <i class="fas fa-spinner fa-spin"></i> Cargando peticiones...
        </div>

        <div v-else-if="peticiones.length === 0" class="empty-message">
          <i class="fas fa-inbox"></i> No hay peticiones asignadas a tu departamento
        </div>

        <div v-else-if="peticionesFiltradas.length === 0" class="empty-message">
          <i class="fas fa-filter"></i> No se encontraron peticiones con los filtros aplicados
        </div>

        <!-- Tabla de Peticiones -->
        <div v-else>
          <!-- Resultados -->
          <div class="resultados-info">
            Mostrando {{ peticionesFiltradas.length }} de {{ peticiones.length }} peticiones
          </div>

          <div class="peticiones-list">
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
                  v-for="peticion in peticionesPaginadas"
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

          <!-- ✅ NUEVO: Paginación -->
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
              <span class="registros-info">registros por página</span>
            </div>

            <div class="info-registros">
              <span v-if="paginacion.totalRegistros > 0">
                Mostrando {{ ((paginacion.paginaActual - 1) * paginacion.registrosPorPagina) + 1 }}
                a {{ Math.min(paginacion.paginaActual * paginacion.registrosPorPagina, paginacion.totalRegistros) }}
                de {{ paginacion.totalRegistros }} registros
              </span>
              <span v-else>No hay registros</span>
            </div>
          </div>

          <div v-if="paginacion.totalPaginas > 1" class="paginacion-navegacion">
            <button
              @click="irAPagina(1)"
              :disabled="paginacion.paginaActual === 1"
              class="btn-paginacion btn-extremo"
              title="Primera página"
            >
              <i class="fas fa-angle-double-left"></i>
            </button>

            <button
              @click="paginaAnterior"
              :disabled="paginacion.paginaActual === 1"
              class="btn-paginacion btn-nav"
              title="Página anterior"
            >
              <i class="fas fa-angle-left"></i>
            </button>

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

            <button
              @click="paginaSiguiente"
              :disabled="paginacion.paginaActual === paginacion.totalPaginas"
              class="btn-paginacion btn-nav"
              title="Página siguiente"
            >
              <i class="fas fa-angle-right"></i>
            </button>

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
  </div>
</template>

<script>
import axios from 'axios';
import { ref, onMounted, watch, computed, reactive } from 'vue';

export default {
  name: 'TablaDepartamento',
  setup() {
    const backendUrl = import.meta.env.VITE_API_URL;
    const peticiones = ref([]);
    const peticionesFiltradas = ref([]);
    const departamentoActual = ref(null); // ✅ CAMBIADO: departamento del usuario
    const usuarioLogueado = ref(null); // ✅ NUEVO: usuario logueado
    const errorDepartamento = ref(''); // ✅ NUEVO: mensaje de error
    const loading = ref(false);

    // ✅ NUEVO: Filtros
    const filtros = ref({
      busqueda: '',
      estado: '',
      importancia: '',
      ordenarPor: 'fecha_desc',
      fechaDesde: '',
      fechaHasta: ''
    });

    // ✅ NUEVO: Paginación
    const paginacion = reactive({
      paginaActual: 1,
      registrosPorPagina: 20,
      totalRegistros: 0,
      totalPaginas: 0
    });

    const opcionesPaginacion = [10, 20, 50, 100];

    // Computed para peticiones paginadas
    const peticionesPaginadas = computed(() => {
      console.log('🔥 COMPUTED EJECUTÁNDOSE!');
      console.log('🔥 peticionesFiltradas.value:', peticionesFiltradas.value);
      console.log('🔥 paginacion:', paginacion);

      const inicio = (paginacion.paginaActual - 1) * paginacion.registrosPorPagina;
      const fin = inicio + paginacion.registrosPorPagina;
      const resultado = peticionesFiltradas.value.slice(inicio, fin);

      console.log('📋 Peticiones paginadas:', resultado.length, 'de', peticionesFiltradas.value.length);
      console.log('📋 Página:', paginacion.paginaActual, 'Inicio:', inicio, 'Fin:', fin);
      console.log('📋 Datos resultado:', resultado);

      return resultado;
    });

    // Computed para páginas visibles
    const paginasVisibles = computed(() => {
      const total = paginacion.totalPaginas;
      const actual = paginacion.paginaActual;
      const ventana = 5;

      if (total <= ventana + 2) {
        return Array.from({ length: total }, (_, i) => i + 1);
      }

      let inicio = Math.max(1, actual - Math.floor(ventana / 2));
      let fin = Math.min(total, inicio + ventana - 1);

      if (fin - inicio < ventana - 1) {
        inicio = Math.max(1, fin - ventana + 1);
      }

      const pages = [];

      if (inicio > 1) {
        pages.push(1);
        if (inicio > 2) pages.push('...');
      }

      for (let i = inicio; i <= fin; i++) {
        pages.push(i);
      }

      if (fin < total) {
        if (fin < total - 1) pages.push('...');
        pages.push(total);
      }

      return pages;
    });

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

    // ✅ NUEVA: Función para aplicar filtros
    const aplicarFiltros = () => {
      console.log('🔍 Aplicando filtros. Peticiones originales:', peticiones.value.length);
      let resultado = [...peticiones.value];

      // Filtrar por búsqueda (folio)
      if (filtros.value.busqueda.trim() !== '') {
        const busqueda = filtros.value.busqueda.toLowerCase();
        resultado = resultado.filter(p =>
          p.folio.toLowerCase().includes(busqueda)
        );
      }

      // Filtrar por estado
      if (filtros.value.estado !== '') {
        resultado = resultado.filter(p => p.estado_departamento === filtros.value.estado);
      }

      // Filtrar por importancia
      if (filtros.value.importancia !== '') {
        const nivel = parseInt(filtros.value.importancia);
        resultado = resultado.filter(p => parseInt(p.NivelImportancia) === nivel);
      }

      // Filtrar por rango de fechas
      if (filtros.value.fechaDesde || filtros.value.fechaHasta) {
        resultado = resultado.filter(p => {
          const fechaPeticion = new Date(p.fecha_asignacion);
          fechaPeticion.setHours(0, 0, 0, 0);

          if (filtros.value.fechaDesde) {
            const fechaDesde = new Date(filtros.value.fechaDesde);
            fechaDesde.setHours(0, 0, 0, 0);
            if (fechaPeticion < fechaDesde) return false;
          }

          if (filtros.value.fechaHasta) {
            const fechaHasta = new Date(filtros.value.fechaHasta);
            fechaHasta.setHours(23, 59, 59, 999);
            if (fechaPeticion > fechaHasta) return false;
          }

          return true;
        });
      }

      // Ordenar según criterio
      const criterio = filtros.value.ordenarPor;
      resultado.sort((a, b) => {
        switch (criterio) {
          case 'fecha_desc':
            return new Date(b.fecha_asignacion) - new Date(a.fecha_asignacion);
          case 'fecha_asc':
            return new Date(a.fecha_asignacion) - new Date(b.fecha_asignacion);
          case 'importancia': {
            const nivelA = parseInt(a.NivelImportancia) || 3;
            const nivelB = parseInt(b.NivelImportancia) || 3;
            if (nivelA !== nivelB) return nivelA - nivelB;
            return new Date(a.fecha_asignacion) - new Date(b.fecha_asignacion);
          }
          case 'estado':
            return (a.estado_departamento || '').localeCompare(b.estado_departamento || '');
          default:
            return 0;
        }
      });

      peticionesFiltradas.value = resultado;
      console.log('✅ Peticiones filtradas:', peticionesFiltradas.value.length);
      console.log('📄 Datos filtrados:', JSON.stringify(peticionesFiltradas.value, null, 2));
      actualizarPaginacion(); // ✅ NUEVO: Actualizar paginación
    };

    // ✅ NUEVA: Función para actualizar paginación
    const actualizarPaginacion = () => {
      paginacion.totalRegistros = peticionesFiltradas.value.length;
      paginacion.totalPaginas = Math.ceil(paginacion.totalRegistros / paginacion.registrosPorPagina);

      if (paginacion.paginaActual > paginacion.totalPaginas && paginacion.totalPaginas > 0) {
        paginacion.paginaActual = 1;
      }

      console.log('📊 Paginación actualizada:', {
        totalRegistros: paginacion.totalRegistros,
        totalPaginas: paginacion.totalPaginas,
        paginaActual: paginacion.paginaActual,
        registrosPorPagina: paginacion.registrosPorPagina
      });
    };

    // ✅ NUEVAS: Funciones de navegación de paginación
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
      paginacion.paginaActual = 1;
      actualizarPaginacion();
    };

    // ✅ NUEVA: Función para limpiar filtros
    const limpiarFiltros = () => {
      filtros.value = {
        busqueda: '',
        estado: '',
        importancia: '',
        ordenarPor: 'fecha_desc',
        fechaDesde: '',
        fechaHasta: ''
      };
      aplicarFiltros();
      if (window.$toast) {
        window.$toast.info('Filtros limpiados');
      }
    };

    // ✅ NUEVO: Watch para debug de peticionesFiltradas
    watch(
      peticionesFiltradas,
      (newVal) => {
        console.log('👀 peticionesFiltradas cambió:', newVal.length, newVal);
      },
      { deep: true }
    );

    // ✅ NUEVO: Watch para debug de paginación
    watch(
      () => paginacion.paginaActual,
      (newVal) => {
        console.log('📖 Página actual cambió a:', newVal);
      }
    );

    // ✅ NUEVO: Watch para aplicar filtros automáticamente
    watch(
      () => [filtros.value.busqueda, filtros.value.estado, filtros.value.importancia,
             filtros.value.ordenarPor, filtros.value.fechaDesde, filtros.value.fechaHasta],
      () => {
        aplicarFiltros();
      },
      { deep: true }
    );

    // ✅ MODIFICADA: Cargar peticiones del departamento del usuario
    const cargarPeticiones = async () => {
      if (!departamentoActual.value) {
        console.warn('No hay departamento seleccionado');
        return;
      }

      loading.value = true;
      console.log('🔄 Cargando peticiones para departamento:', departamentoActual.value.id);
      try {
        const res = await axios.get(`${backendUrl}/departamentos_peticiones.php`, {
          params: { departamento_id: departamentoActual.value.id }
        });
        peticiones.value = res.data.records || [];
        console.log('✅ Peticiones cargadas desde API:', peticiones.value.length);
        console.log('📄 Datos cargados:', peticiones.value);
        aplicarFiltros(); // ✅ NUEVO: Aplicar filtros después de cargar
      } catch (error) {
        console.error('Error cargando peticiones:', error);
        if (window.$toast) {
          window.$toast.error('Error al cargar las peticiones');
        } else {
          alert('Error al cargar las peticiones');
        }
      } finally {
        console.log('🏁 Finalizando carga. Loading = false');
        loading.value = false;
        console.log('🏁 Loading después de false:', loading.value);
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

    // 🔍 DEBUG: Log de estado inicial
    console.log('🎯 Setup completado. Estado inicial:', {
      loading: loading.value,
      errorDepartamento: errorDepartamento.value,
      peticiones: peticiones.value.length,
      peticionesFiltradas: peticionesFiltradas.value.length,
      filtros: filtros.value
    });

    return {
      peticiones,
      peticionesFiltradas, // ✅ NUEVO
      peticionesPaginadas, // ✅ NUEVO
      filtros, // ✅ NUEVO
      paginacion, // ✅ NUEVO
      opcionesPaginacion, // ✅ NUEVO
      paginasVisibles, // ✅ NUEVO
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
      aplicarFiltros, // ✅ NUEVO
      limpiarFiltros, // ✅ NUEVO
      actualizarPaginacion, // ✅ NUEVO
      irAPagina, // ✅ NUEVO
      paginaAnterior, // ✅ NUEVO
      paginaSiguiente, // ✅ NUEVO
      cambiarRegistrosPorPagina, // ✅ NUEVO
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

<style src="@/assets/css/TablaDepartamento.css"></style>
<style scoped>
/* ✅ NUEVOS: Estilos para mostrar departamento actual y estilos de peticiones */
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

/* ✅ NUEVO: Estilos para filtros */
.filtros-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1.25rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  border: 1px solid #dee2e6;
}

.filtro {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filtro label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #495057;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filtro label i {
  color: #0074D9;
  font-size: 0.9rem;
}

.filtro input,
.filtro select {
  padding: 0.6rem 0.8rem;
  border: 2px solid #ced4da;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  background: white;
}

.filtro input:focus,
.filtro select:focus {
  outline: none;
  border-color: #0074D9;
  box-shadow: 0 0 0 3px rgba(0, 116, 217, 0.1);
}

.filtro-fecha input {
  cursor: pointer;
}

.filtro-acciones {
  display: flex;
  align-items: flex-end;
}

.btn-clear-filters {
  padding: 0.6rem 1.2rem;
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  justify-content: center;
}

.btn-clear-filters:hover {
  background: linear-gradient(135deg, #c82333, #bd2130);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

.btn-clear-filters i {
  font-size: 0.85rem;
}

.resultados-info {
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  border-left: 4px solid #0074D9;
  border-radius: 8px;
  color: #1565c0;
  font-size: 0.9rem;
  font-weight: 600;
}

/* Responsive para filtros */
@media (max-width: 768px) {
  .filtros-container {
    grid-template-columns: 1fr;
  }
}

/* ✅ NUEVO: Estilos para paginación */
.paginacion-container {
  margin-top: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.paginacion-controles {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.registros-por-pagina {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.9rem;
  color: #495057;
}

.registros-por-pagina label {
  font-weight: 600;
}

.select-registros {
  padding: 0.5rem 0.75rem;
  border: 2px solid #ced4da;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  background: white;
}

.select-registros:focus {
  outline: none;
  border-color: #0074D9;
  box-shadow: 0 0 0 3px rgba(0, 116, 217, 0.1);
}

.registros-info {
  color: #6c757d;
}

.info-registros {
  font-size: 0.9rem;
  color: #6c757d;
  font-weight: 500;
}

.paginacion-navegacion {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-paginacion {
  min-width: 40px;
  height: 40px;
  padding: 0.5rem 0.75rem;
  border: 2px solid #dee2e6;
  background: white;
  border-radius: 8px;
  color: #495057;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-paginacion:hover:not(:disabled):not(.puntos) {
  border-color: #0074D9;
  background: #e3f2fd;
  color: #0074D9;
  transform: translateY(-2px);
}

.btn-paginacion:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.btn-paginacion.activa {
  background: linear-gradient(135deg, #0074D9, #0056b3);
  color: white;
  border-color: #0074D9;
  box-shadow: 0 4px 8px rgba(0, 116, 217, 0.3);
}

.btn-paginacion.puntos {
  border: none;
  background: transparent;
  cursor: default;
}

.numeros-pagina {
  display: flex;
  gap: 0.5rem;
}

.btn-extremo {
  font-size: 0.85rem;
}

.btn-nav {
  font-size: 1rem;
}

.btn-numero {
  font-size: 0.9rem;
}

/* Responsive para paginación */
@media (max-width: 768px) {
  .paginacion-controles {
    flex-direction: column;
    align-items: stretch;
  }

  .info-registros {
    text-align: center;
  }

  .registros-por-pagina {
    justify-content: center;
  }

  .numeros-pagina {
    flex-wrap: wrap;
    justify-content: center;
  }

  .btn-paginacion {
    min-width: 35px;
    height: 35px;
    padding: 0.4rem 0.6rem;
  }
}
</style>

