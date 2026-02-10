<template>
  <div class="peticiones-container">
    <div class="card">
      <div class="card-header">
        <div class="header-title-section">
          <h3>Gestión de Peticiones</h3>
        </div>
        <div class="header-actions">
          <button @click="filtrarMisPeticiones" class="btn-filter">
            <i class="fas fa-user"></i> Mis Peticiones
          </button>
          <!-- ✅ NUEVO: Botón para peticiones sin seguimiento -->
          <button @click="filtrarSinSeguimiento" class="btn-filter btn-warning">
            <i class="fas fa-user-slash"></i>
            Sin Seguimiento
            <span v-if="contadorSinSeguimiento > 0" class="badge-count">{{ contadorSinSeguimiento }}</span>
          </button>
          <button @click="limpiarFiltros" class="btn-clear">
            <i class="fas fa-times"></i> Limpiar Filtros
          </button>
        </div>
      </div>
      <div class="card-body">
        <p class="welcome-message">
          <template v-if="hasPermission('peticiones_municipio') && municipioUsuario">
            Administrando peticiones del municipio de <strong>{{ municipioUsuario }}</strong>
          </template>
          <template v-else-if="hasPermission('peticiones_estatal') && filtros.municipio_id">
            Administra las peticiones recibidas
          </template>
          <template v-else-if="hasPermission('peticiones_estatal') && !filtros.municipio_id">
            Administrando peticiones de <strong>todos los municipios</strong>
          </template>
          <template v-else>
            Administra las peticiones recibidas
          </template>
          <span v-if="peticiones.length > 0" class="peticiones-count">
            ({{ peticiones.length }} petición{{ peticiones.length !== 1 ? 'es' : '' }} en total)
          </span>
        </p>

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
              <!-- ✅ NUEVA: Opción para peticiones sin seguimiento -->
              <option value="sin_asignar">Sin seguimiento asignado</option>
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
          <!-- ✅ NUEVO: Filtro de municipio solo para Canalizador Estatal -->
          <div v-if="hasPermission('peticiones_estatal')" class="filtro">
            <label for="filtroMunicipio">
              <i class="fas fa-map-marker-alt"></i> Municipio:
            </label>
            <select id="filtroMunicipio" v-model="filtros.municipio_id">
              <option value="">Todos los municipios</option>
              <option v-for="mun in municipios" :key="mun.Id" :value="mun.Id">
                {{ mun.Municipio }}
              </option>
            </select>
          </div>
          <!-- ✅ NUEVO: Filtro de ordenamiento -->
          <div class="filtro">
            <label for="filtroOrdenar">
              <i class="fas fa-sort"></i> Ordenar por:
            </label>
            <select id="filtroOrdenar" v-model="filtros.ordenarPor">
              <option value="prioridad">Prioridad (Semáforo + Importancia)</option>
              <option value="fecha_desc">Fecha (Más reciente primero)</option>
              <option value="fecha_asc">Fecha (Más antigua primero)</option>
              <option value="importancia">Nivel de Importancia</option>
              <option value="folio">Folio</option>
            </select>
          </div>
          <!-- ✅ NUEVO: Filtro de rango de fechas -->
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
        </div>

        <!-- Tabla de peticiones -->
        <div class="peticiones-list">
          <div class="tabla-scroll-container">
            <div class="tabla-contenido">
              <div
                class="list-header header-forzado"
              >
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

              <!-- ✅ OPTIMIZADO: Usar v-show para loading que cambia frecuentemente -->
              <div v-show="loading" class="loading-container">
                <!-- Skeleton loader para mejor UX -->
                <div class="skeleton-item" v-for="n in 5" :key="n">
                  <div class="skeleton skeleton-acciones"></div>
                  <div class="skeleton skeleton-folio"></div>
                  <div class="skeleton skeleton-nombre"></div>
                  <div class="skeleton skeleton-telefono"></div>
                  <div class="skeleton skeleton-localidad"></div>
                  <div class="skeleton skeleton-estado"></div>
                  <div class="skeleton skeleton-depts"></div>
                  <div class="skeleton skeleton-prioridad"></div>
                  <div class="skeleton skeleton-fecha"></div>
                </div>
              </div>

              <div v-show="!loading && peticionesFiltradas.length === 0" class="empty-message">
                <i class="fas fa-inbox"></i> No se encontraron peticiones con los filtros aplicados
              </div>

              <!-- ✅ OPTIMIZADO: Usar v-show en lugar de v-else para mejor rendimiento -->
              <div v-show="!loading && peticionesFiltradas.length > 0" v-for="peticion in peticionesPaginadas" :key="peticion.id" class="peticion-item">
                <!-- Columna Acciones con Diseño de Badges -->
                <div class="peticion-acciones">
                  <div class="acciones-badges">
                    <button
                      class="accion-badge edit-badge"
                      :class="{ 'disabled': !puedeEditarPeticion(peticion) }"
                      :disabled="!puedeEditarPeticion(peticion)"
                      @click.stop="puedeEditarPeticion(peticion) && editarPeticion(peticion)"
                      :title="!puedeEditarPeticion(peticion) ? 'Solo el usuario asignado puede editar' : 'Editar petición'"
                    >
                      <i class="fas fa-edit"></i>
                      <span>Edit</span>
                    </button>
                    <button
                      class="accion-badge seguimiento-badge"
                      @click.stop="seguimiento(peticion)"
                      :title="esUsuarioAsignado(peticion) ? 'Mi Seguimiento' : 'Asignar Seguimiento'"
                    >
                      <i class="fas fa-user-check"></i>
                      <span>Track</span>
                    </button>
                  </div>
                </div>

                <!-- Columna Folio -->
                <div class="peticion-folio" @click="abrirDetallesPeticion(peticion)">
                  <span class="folio-badge">{{ peticion.folio }}</span>
                </div>

                <!-- Columna Nombre -->
                <div class="peticion-nombre" @click="abrirDetallesPeticion(peticion)">
                  <span class="nombre-peticion nombre-clickable" :title="'Ver detalles de: ' + peticion.nombre">{{ peticion.nombre }}</span>
                </div>

                <!-- Columna Teléfono -->
                <div class="peticion-telefono" @click="abrirDetallesPeticion(peticion)">
                  <span class="telefono">{{ peticion.telefono }}</span>
                </div>

                <!-- Columna Localidad -->
                <div class="peticion-localidad" @click="abrirDetallesPeticion(peticion)">
                  <span class="localidad">{{ peticion.localidad }}</span>
                </div>

                <!-- Columna Estado - Clickeable para cambiar estado -->
                <div class="peticion-estado estado-clickeable"
                     @click.stop="puedeEditarPeticion(peticion) ? cambiarEstado(peticion) : null"
                     :class="{ 'clickeable': puedeEditarPeticion(peticion), 'no-clickeable': !puedeEditarPeticion(peticion) }"
                     :title="puedeEditarPeticion(peticion) ? 'Click para cambiar estado' : 'Solo el usuario asignado puede cambiar el estado'">
                  <span :class="['estado-badge', 'estado-' + peticion.estado.toLowerCase().replace(/\s+/g, '-')]">
                    {{ peticion.estado }}
                  </span>
                  <i v-if="puedeEditarPeticion(peticion)" class="fas fa-edit estado-edit-icon"></i>
                </div>

                <!-- Columna Departamentos -->
                <div class="peticion-departamentos" @click.stop>
                  <div v-if="!peticion.departamentos || peticion.departamentos.length === 0"
                       class="departamentos-badge sin-asignar-badge"
                       @click="mostrarMenuDepartamentos(peticion)"
                       title="Click para opciones de departamentos">
                    <span class="badge-text">Sin asignar</span>
                    <i class="fas fa-cog badge-icon"></i>
                  </div>
                  <div v-else class="departamentos-badge con-asignaciones-badge"
                       @click="mostrarMenuDepartamentos(peticion)"
                       title="Click para opciones de departamentos">
                    <i class="fas fa-building badge-icon-left"></i>
                    <span class="badge-text">{{ peticion.departamentos.length }}</span>
                    <i class="fas fa-cog badge-icon"></i>
                  </div>
                </div>

                <!-- Columna Prioridad/Semáforo - Clickeable para cambiar importancia -->
                <div class="peticion-prioridad prioridad-clickeable"
                     @click.stop="puedeEditarPeticion(peticion) ? cambiarImportancia(peticion) : null"
                     :class="{ 'clickeable': puedeEditarPeticion(peticion), 'no-clickeable': !puedeEditarPeticion(peticion) }"
                     :title="puedeEditarPeticion(peticion) ? 'Click para cambiar prioridad' : 'Solo el usuario asignado puede cambiar la prioridad'">
                  <div class="indicadores-container">
                    <div class="nivel-importancia" :class="`nivel-${peticion.NivelImportancia}`"
                         :title="`Nivel ${peticion.NivelImportancia} - ${obtenerEtiquetaNivelImportancia(peticion.NivelImportancia)}`">
                      {{ obtenerTextoNivelImportancia(peticion.NivelImportancia) }}
                    </div>
                    <!-- ✅ OPTIMIZADO: Usar función memoizada para semáforo -->
                    <div class="semaforo" :class="obtenerColorSemaforoMemo(peticion)" :title="obtenerTituloSemaforo(peticion)"></div>
                    <div class="seguimiento-indicator" :class="obtenerClaseSeguimiento(peticion)" :title="obtenerTituloSeguimiento(peticion)">
                      <i :class="obtenerIconoSeguimiento(peticion)"></i>
                    </div>
                  </div>
                  <i v-if="puedeEditarPeticion(peticion)" class="fas fa-edit prioridad-edit-icon"></i>
                </div>

                <!-- Columna Fecha Registro -->
                <div class="peticion-fecha" @click="abrirDetallesPeticion(peticion)">
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

    <!-- Modal de opciones para departamentos -->
    <div v-if="showMenuDepartamentos" class="modal-overlay" @click.self="cancelarAccion">
      <div class="modal-content modal-opciones-departamentos">
        <div class="modal-header">
          <h3>
            <i class="fas fa-building"></i>
            Opciones de Departamentos
          </h3>
          <button class="close-btn" @click="cancelarAccion">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p class="peticion-info">
            <strong>Petición:</strong> {{ peticionSeleccionadaMenu?.nombre || 'Sin nombre' }}
          </p>

          <div class="opciones-departamentos">
            <button
              v-if="peticionSeleccionadaMenu?.departamentos?.length > 0"
              @click="verEstadosDepartamentos"
              class="btn-opcion btn-ver-estados"
              title="Ver historial y estados de departamentos">
              <div class="opcion-content">
                <i class="fas fa-eye"></i>
                <div class="opcion-text">
                  <span class="opcion-titulo">Ver Estados</span>
                  <span class="opcion-descripcion">Historial y seguimiento</span>
                </div>
              </div>
            </button>

            <button
              v-if="puedeEditarPeticion(peticionSeleccionadaMenu)"
              @click="gestionarDepartamentosMenu"
              class="btn-opcion btn-gestionar"
              title="Asignar o modificar departamentos">
              <div class="opcion-content">
                <i class="fas fa-cog"></i>
                <div class="opcion-text">
                  <span class="opcion-titulo">{{ (!peticionSeleccionadaMenu?.departamentos || peticionSeleccionadaMenu?.departamentos.length === 0) ? 'Asignar' : 'Gestionar' }}</span>
                  <span class="opcion-descripcion">{{ (!peticionSeleccionadaMenu?.departamentos || peticionSeleccionadaMenu?.departamentos.length === 0) ? 'Asignar departamentos' : 'Modificar asignaciones' }}</span>
                </div>
              </div>
            </button>

            <div v-if="!puedeEditarPeticion(peticionSeleccionadaMenu)" class="sin-permisos">
              <i class="fas fa-lock"></i>
              <span>Solo el usuario asignado puede gestionar departamentos</span>
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
                <input
                  type="text"
                  id="folio"
                  v-model="peticionForm.folio"
                  disabled
                  class="input-disabled"
                  title="El folio no puede ser modificado"
                />
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
            <!-- Mensaje informativo -->
            <div class="info-message">
              <i class="fas fa-info-circle"></i>
              <strong>Nota:</strong> Las sugerencias no fuerzan a ese departamento a trabajar la petición, únicamente ayudan a seleccionar departamentos en base al problema de la petición.
            </div>

            <!-- ✅ ACTUALIZADA: Sección de Sugerencias de IA -->
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

                  <!-- ✅ CAMBIO: Mostrar estado solo si es "Aceptada" -->
                  <div class="sugerencia-estado" v-if="sugerencia.estado === 'Aceptada'">
                    <span class="estado-badge estado-sugerida-creador">
                      Sugerida por el creador del folio
                    </span>
                  </div>

                  <!-- ✅ REMOVIDO: Botón de asignar desde sugerencia -->
                </div>
              </div>
            </div>

            <!-- Departamentos Asignados -->
            <div class="departamentos-section">
              <h4 class="departamentos-section-title">
                <i class="fas fa-check-circle"></i> Departamentos Asignados
              </h4>

              <div v-if="departamentosAsignados.length === 0" class="no-departamentos">
                <i class="fas fa-building"></i>
                <div class="mensaje">No hay departamentos asignados</div>
                <div class="descripcion">Esta petición aún no ha sido asignada a ningún departamento</div>
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
                      <option value="Aceptado en proceso">Aceptado en proceso</option>
                      <option value="Devuelto a seguimiento">Devuelto a seguimiento</option>
                      <option value="Rechazado">Rechazado</option>
                      <option value="Completado">Completado</option>
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

            <!-- ✅ ACTUALIZADA: Asignar Nuevos Departamentos con buscador -->
            <div class="departamentos-section">
              <h4 class="departamentos-section-title">
                <i class="fas fa-plus-circle"></i> Asignar Nuevos Departamentos
              </h4>

              <!-- ✅ NUEVA: Barra de búsqueda con sugerencias rápidas -->
              <div class="busqueda-departamentos">
                <div class="busqueda-input-container">
                  <input
                    type="text"
                    v-model="busquedaDepartamento"
                    @input="filtrarDepartamentos"
                    placeholder="Buscar departamentos..."
                    class="busqueda-input"
                  >
                  <i class="fas fa-search busqueda-icon"></i>
                </div>

                <!-- ✅ NUEVO: Botones de sugerencias rápidas -->
                <div v-if="sugerenciasRapidas.length > 0" class="sugerencias-rapidas">
                  <span class="sugerencias-label">Sugerencias rápidas:</span>
                  <button
                    v-for="sugerencia in sugerenciasRapidas"
                    :key="'rapid-' + sugerencia"
                    @click="buscarSugerencia(sugerencia)"
                    class="btn-sugerencia-rapida"
                    :title="`Buscar: ${sugerencia}`"
                  >
                    <i class="fas fa-lightbulb"></i> {{ sugerencia }}
                  </button>
                </div>
              </div>

              <div v-if="departamentosFiltrados.length === 0" class="no-departamentos">
                <i class="fas fa-search"></i>
                <span v-if="busquedaDepartamento">No se encontraron departamentos con "<strong>{{ busquedaDepartamento }}</strong>"</span>
                <span v-else>Todos los departamentos están asignados</span>
              </div>

              <div v-else class="asignar-departamentos-form">
                <div class="departamentos-checkboxes">
                  <div
                    v-for="departamento in departamentosFiltrados"
                    :key="departamento.id"
                    class="departamento-checkbox"
                    :class="{ 'sugerido': esDepartamentoSugerido(departamento.nombre_unidad) }"
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
                        <!-- ✅ NUEVO: Indicador de sugerencia -->
                        <i v-if="esDepartamentoSugerido(departamento.nombre_unidad)"
                           class="fas fa-star sugerencia-star"
                           title="Sugerido por IA"></i>
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

    <!-- ✅ NUEVO: Modal para Ver Estados de Departamentos -->
    <div v-if="showModalDepartamentosEstados" class="modal-overlay" @click.self="cerrarModalDepartamentosEstados">
      <div class="modal-content modal-departamentos">
        <div class="modal-header">
          <h3>
            <i class="fas fa-building"></i>
            Estados de Departamentos - {{ peticionDeptEstados.folio }}
          </h3>
          <button class="close-btn" @click="cerrarModalDepartamentosEstados">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="info-message">
            <i class="fas fa-info-circle"></i>
            <div>
              <strong>Petición:</strong> {{ peticionDeptEstados.nombre }}<br>
              <strong>Localidad:</strong> {{ peticionDeptEstados.localidad }}<br>
              <strong>Total de departamentos:</strong> {{ (peticionDeptEstados.departamentos || []).length }}
            </div>
          </div>

          <div class="departamentos-estados-list">
            <div
              v-for="dept in peticionDeptEstados.departamentos"
              :key="dept.id || dept.asignacion_id"
              class="departamento-estado-card"
            >
              <div class="dept-header">
                <div class="dept-info-principal">
                  <i class="fas fa-building dept-icon-large"></i>
                  <div class="dept-detalles">
                    <h4 class="dept-nombre-completo">{{ dept.nombre_unidad }}</h4>
                    <span class="dept-fecha">
                      <i class="fas fa-calendar-alt"></i>
                      Asignado: {{ formatearFecha(dept.fecha_asignacion) }}
                    </span>
                  </div>
                </div>
                <span :class="['estado-badge-large', `estado-${(dept.estado_asignacion || dept.estado).toLowerCase().replace(/ /g, '-')}`]">
                  {{ dept.estado_asignacion || dept.estado }}
                </span>
              </div>

              <div class="dept-acciones">
                <button
                  @click="abrirHistorialDepartamentoDesdeModal(peticionDeptEstados, dept)"
                  class="btn-historial-dept"
                  title="Ver historial de cambios"
                >
                  <i class="fas fa-history"></i>
                  Ver Historial
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="cerrarModalDepartamentosEstados">
            <i class="fas fa-times"></i> Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- ✅ Modal Historial de Departamento Específico -->
    <div v-if="showHistorialDepartamentoModal" class="modal-overlay" @click.self="cerrarHistorialDepartamento">
      <div class="modal-content modal-departamentos">
        <div class="modal-header">
          <h3>
            <i class="fas fa-history"></i>
            Historial de {{ historialDeptSeleccionado.nombre_unidad }}
          </h3>
          <button class="close-btn" @click="cerrarHistorialDepartamento">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="info-message">
            <i class="fas fa-info-circle"></i>
            <div>
              <strong>Folio:</strong> {{ historialPeticionSeleccionada.folio }}<br>
              <strong>Departamento:</strong> {{ historialDeptSeleccionado.nombre_unidad }}<br>
              <strong>Estado Actual:</strong>
              <span :class="['estado-badge', `estado-${(historialDeptSeleccionado.estado_asignacion || historialDeptSeleccionado.estado).toLowerCase().replace(/ /g, '-')}`]">
                {{ historialDeptSeleccionado.estado_asignacion || historialDeptSeleccionado.estado }}
              </span>
            </div>
          </div>

          <div v-if="loadingHistorialDept" class="loading-message">
            <i class="fas fa-spinner fa-spin"></i> Cargando historial...
          </div>

          <div v-else-if="!historialDeptCambios || historialDeptCambios.length === 0" class="no-departamentos">
            <i class="fas fa-inbox"></i> No hay cambios registrados para este departamento
          </div>

          <div v-else class="historial-list">
            <div v-for="cambio in historialDeptCambios" :key="cambio.id" class="historial-item">
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
                <!-- ✅ NUEVO: Imágenes del cambio de estado -->
                <div v-if="cambio.id" class="historial-imagenes">
                  <h6><i class="fas fa-images"></i> Imágenes del cambio</h6>
                  <ImageGallery
                    :entidad-tipo="'historial_cambio'"
                    :entidad-id="cambio.id"
                    :readonly="true"
                    :show-upload="false"
                    :compact="true"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="cerrarHistorialDepartamento">
            <i class="fas fa-times"></i> Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- ✅ Modal Detalles Completos de Petición -->
    <div v-if="showDetallesPeticionModal" class="modal-overlay" @click.self="cerrarDetallesPeticion">
      <div class="modal-content modal-detalles-peticion">
        <div class="modal-header modal-detalles-header">
          <h3>
            <i class="fas fa-file-alt"></i>
            Detalles de Petición - {{ peticionDetalles.folio }}
          </h3>
          <div class="header-actions">
            <button type="button" class="btn-print" @click="imprimirPeticion" title="Imprimir PDF">
              <i class="fas fa-print"></i>
              Imprimir/PDF
            </button>
            <button class="close-btn" @click="cerrarDetallesPeticion">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <div class="modal-body modal-detalles-body">
          <!-- Información principal -->
          <div class="detalles-grid">
            <div class="detalle-seccion info-principal">
              <h4><i class="fas fa-info-circle"></i> Información Principal</h4>
              <div class="detalle-item">
                <span class="label">Folio:</span>
                <span class="valor">{{ peticionDetalles.folio || 'No asignado' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Nombre:</span>
                <span class="valor">{{ peticionDetalles.nombre || 'Sin nombre' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Teléfono:</span>
                <span class="valor">{{ peticionDetalles.telefono || 'No especificado' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Dirección:</span>
                <span class="valor">{{ peticionDetalles.direccion || 'No especificada' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Localidad:</span>
                <span class="valor">{{ peticionDetalles.localidad || 'No especificada' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Red Social:</span>
                <span class="valor">{{ peticionDetalles.red_social || 'No especificada' }}</span>
              </div>
            </div>

            <div class="detalle-seccion info-estado">
              <h4><i class="fas fa-flag"></i> Estado y Prioridad</h4>
              <div class="detalle-item">
                <span class="label">Estado:</span>
                <span class="valor estado-badge" :class="`estado-${(peticionDetalles.estado || '').toLowerCase().replace(/\s+/g, '-')}`">
                  {{ peticionDetalles.estado || 'Sin estado' }}
                </span>
              </div>
              <div class="detalle-item">
                <span class="label">Prioridad:</span>
                <span class="valor prioridad-badge" :class="`prioridad-${peticionDetalles.NivelImportancia}`">
                  {{ obtenerTextoNivelImportancia(peticionDetalles.NivelImportancia) }}
                </span>
              </div>
              <div class="detalle-item">
                <span class="label">Fecha Registro:</span>
                <span class="valor">{{ formatearFechaCompleta(peticionDetalles.fecha_registro) }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Usuario Seguimiento:</span>
                <span class="valor">{{
                  tieneUsuarioAsignado(peticionDetalles) ?
                    (peticionDetalles.nombre_completo_usuario || peticionDetalles.nombre_usuario_seguimiento || 'Usuario asignado') :
                    'Sin asignar'
                }}</span>
              </div>
            </div>
          </div>

          <!-- Descripción -->
          <div class="detalle-seccion descripcion-completa">
            <h4><i class="fas fa-file-text"></i> Descripción</h4>
            <div class="descripcion-contenido">
              {{ peticionDetalles.descripcion || 'Sin descripción' }}
            </div>
          </div>

          <!-- Departamentos asignados -->
          <div class="detalle-seccion departamentos-info">
            <h4><i class="fas fa-building"></i> Departamentos Asignados</h4>
            <div v-if="!peticionDetalles.departamentos || peticionDetalles.departamentos.length === 0" class="no-departamentos">
              <i class="fas fa-building"></i>
              <div class="mensaje">Sin departamentos asignados</div>
              <div class="descripcion">Esta petición aún no ha sido derivada a ningún departamento</div>
            </div>
            <div v-else class="departamentos-lista">
              <div v-for="dept in peticionDetalles.departamentos" :key="dept.id" class="departamento-item">
                <div class="dept-info">
                  <h5>{{ dept.nombre_unidad }}</h5>
                  <span class="dept-estado" :class="`dept-estado-${dept.estado_asignacion?.toLowerCase()}`">
                    {{ dept.estado_asignacion || 'Sin estado' }}
                  </span>
                </div>
                <div class="dept-fecha">
                  {{ formatearFecha(dept.fecha_asignacion) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Imágenes de la petición -->
          <div class="detalle-seccion imagenes-peticion">
            <h4><i class="fas fa-images"></i> Imágenes de la Petición</h4>
            <div class="galeria-contenedor">
              <ImageGallery
                :entidad-tipo="'peticion'"
                :entidad-id="peticionDetalles.id"
                :readonly="true"
                :show-upload="false"
              />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="cerrarDetallesPeticion">
            <i class="fas fa-times"></i>
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- ✅ Modal Detalles Completos de Petición -->
    <div v-if="showDetallesPeticionModal" class="modal-overlay" @click.self="cerrarDetallesPeticion">
      <div class="modal-content modal-detalles-peticion">
        <div class="modal-header modal-detalles-header">
          <h3>
            <i class="fas fa-file-alt"></i>
            Detalles de Petición - {{ peticionDetalles.folio }}
          </h3>
          <div class="header-actions">

            <button class="close-btn" @click="cerrarDetallesPeticion">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <div class="modal-body modal-detalles-body">
          <!-- Información principal -->
          <div class="detalles-grid">
            <div class="detalle-seccion info-principal">
              <h4><i class="fas fa-info-circle"></i> Información Principal</h4>
              <div class="detalle-item">
                <span class="label">Folio:</span>
                <span class="valor">{{ peticionDetalles.folio || 'No asignado' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Nombre:</span>
                <span class="valor">{{ peticionDetalles.nombre || 'Sin nombre' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Teléfono:</span>
                <span class="valor">{{ peticionDetalles.telefono || 'No especificado' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Dirección:</span>
                <span class="valor">{{ peticionDetalles.direccion || 'No especificada' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Localidad:</span>
                <span class="valor">{{ peticionDetalles.localidad || 'No especificada' }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Red Social:</span>
                <span class="valor">{{ peticionDetalles.red_social || 'No especificada' }}</span>
              </div>
            </div>

            <div class="detalle-seccion info-estado">
              <h4><i class="fas fa-flag"></i> Estado y Prioridad</h4>
              <div class="detalle-item">
                <span class="label">Estado:</span>
                <span class="valor estado-badge" :class="`estado-${(peticionDetalles.estado || '').toLowerCase().replace(/\\s+/g, '-')}`">
                  {{ peticionDetalles.estado || 'Sin estado' }}
                </span>
              </div>
              <div class="detalle-item">
                <span class="label">Prioridad:</span>
                <span class="valor prioridad-badge" :class="`prioridad-${peticionDetalles.NivelImportancia}`">
                  {{ obtenerTextoNivelImportancia(peticionDetalles.NivelImportancia) }}
                </span>
              </div>
              <div class="detalle-item">
                <span class="label">Fecha Registro:</span>
                <span class="valor">{{ formatearFechaCompleta(peticionDetalles.fecha_registro) }}</span>
              </div>
              <div class="detalle-item">
                <span class="label">Usuario Seguimiento:</span>
                <span class="valor">{{
                  tieneUsuarioAsignado(peticionDetalles) ?
                    (peticionDetalles.nombre_completo_usuario || peticionDetalles.nombre_usuario_seguimiento || 'Usuario asignado') :
                    'Sin asignar'
                }}</span>
              </div>
            </div>
          </div>

          <!-- Descripción -->
          <div class="detalle-seccion descripcion-completa">
            <h4><i class="fas fa-file-text"></i> Descripción</h4>
            <div class="descripcion-contenido">
              {{ peticionDetalles.descripcion || 'Sin descripción' }}
            </div>
          </div>

          <!-- Departamentos asignados -->
          <div class="detalle-seccion departamentos-info">
            <h4><i class="fas fa-building"></i> Departamentos Asignados</h4>
            <div v-if="!peticionDetalles.departamentos || peticionDetalles.departamentos.length === 0" class="no-departamentos">
              <i class="fas fa-building"></i>
              <div class="mensaje">Sin departamentos asignados</div>
              <div class="descripcion">Esta petición aún no ha sido derivada a ningún departamento</div>
            </div>
            <div v-else class="departamentos-lista">
              <div v-for="dept in peticionDetalles.departamentos" :key="dept.id" class="departamento-item">
                <div class="dept-info">
                  <h5>{{ dept.nombre_unidad }}</h5>
                  <span class="dept-estado" :class="`dept-estado-${dept.estado_asignacion?.toLowerCase()}`">
                    {{ dept.estado_asignacion || 'Sin estado' }}
                  </span>
                </div>
                <div class="dept-fecha">
                  {{ formatearFecha(dept.fecha_asignacion) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Imágenes de la petición -->
          <div class="detalle-seccion imagenes-peticion">
            <h4><i class="fas fa-images"></i> Imágenes de la Petición</h4>
            <div class="galeria-contenedor">
              <ImageGallery
                :entidad-tipo="'peticion'"
                :entidad-id="peticionDetalles.id"
                :readonly="true"
                :show-upload="false"
              />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="cerrarDetallesPeticion">
            <i class="fas fa-times"></i>
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, reactive, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import ImageGallery from '@/components/ImageGallery.vue';

export default {
  name: 'GestionPeticiones',
  components: {
    ImageGallery
  },
  setup() {
    const loading = ref(true);
    const peticiones = ref([]);
    const peticionesFiltradas = ref([]);
    const departamentos = ref([]);
    const showEditModal = ref(false);
    const showEstadoModal = ref(false);
    const showDepartamentosModal = ref(false);
    const showMenuDepartamentos = ref(false);
    const peticionSeleccionadaMenu = ref(null);
    const showImportanciaModal = ref(false);
    const peticionActiva = ref(null);
    const usuarioLogueado = ref(null);
    const municipioUsuario = ref(null);
    const accionesRefs = ref({});
    const dropdownStyle = ref({});

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
      usuario_seguimiento: '',
      municipio_id: '', // ✅ Filtro de municipio para Canalizador Estatal
      ordenarPor: 'prioridad', // ✅ NUEVO: Ordenamiento (prioridad por defecto)
      fechaDesde: '', // ✅ NUEVO: Fecha desde para filtro de rango
      fechaHasta: '' // ✅ NUEVO: Fecha hasta para filtro de rango
    });

    const municipios = ref([]); // ✅ NUEVO: Lista de municipios disponibles

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
          console.log('✅ Usuario logueado:', usuarioLogueado.value);
          console.log('🏢 División del usuario:', usuarioLogueado.value.IdDivisionAdm);

          // ✅ NUEVO: Obtener nombre del municipio
          if (usuarioLogueado.value.NombreDivision) {
            municipioUsuario.value = usuarioLogueado.value.NombreDivision;
          } else if (usuarioLogueado.value.IdDivisionAdm) {
            // Si no viene el nombre, intentar obtenerlo
            await obtenerNombreMunicipio(usuarioLogueado.value.IdDivisionAdm);
          }

          return response.data.user.Id;
        }

        // Fallback a localStorage
        const userData = localStorage.getItem('user');
        if (userData) {
          try {
            const parsedData = JSON.parse(userData);
            const user = parsedData.usuario || parsedData;
            usuarioLogueado.value = user;

            // ✅ NUEVO: Intentar obtener municipio desde localStorage
            if (parsedData.division?.nombre) {
              municipioUsuario.value = parsedData.division.nombre;
            } else if (parsedData.division?.municipio) {
              municipioUsuario.value = parsedData.division.municipio;
            } else if (user.IdDivisionAdm) {
              await obtenerNombreMunicipio(user.IdDivisionAdm);
            }

            return user.Id || user.id;
          } catch (e) {
            console.error('Error al parsear datos del usuario:', e);
          }
        }

        console.warn('No se pudo obtener la información del usuario logueado');
        return null;
      } catch (error) {
        console.error('Error al obtener usuario logueado:', error);
        return null;
      }
    };

    // ✅ NUEVA: Función para obtener el nombre del municipio desde la API
    const obtenerNombreMunicipio = async (divisionId) => {
      if (!divisionId) return;

      try {
        const response = await axios.get(`${backendUrl}/division.php`);
        if (response.data.success && response.data.divisions) {
          const division = response.data.divisions.find(d => d.Id === divisionId);
          if (division) {
            municipioUsuario.value = division.Municipio;
            console.log('🏢 Municipio obtenido:', municipioUsuario.value);
          }
        }
      } catch (error) {
        console.error('Error al obtener nombre del municipio:', error);
      }
    };

    // ✅ AGREGAR: Función para obtener info del usuario logueado
    const obtenerInfoUsuarioLogueado = () => {
      return usuarioLogueado.value;
    };

    // ✅ NUEVA: Función para obtener la división del usuario
    const obtenerDivisionUsuario = () => {
      if (usuarioLogueado.value && usuarioLogueado.value.IdDivisionAdm) {
        return usuarioLogueado.value.IdDivisionAdm;
      }
      return null;
    };

    // ✅ MEJORADO: Función de ordenamiento dinámico según filtro seleccionado
    const ordenarPeticiones = (peticiones) => {
      const criterio = filtros.ordenarPor || 'prioridad';

      return peticiones.sort((a, b) => {
        switch (criterio) {
          case 'prioridad': {
            // Ordenamiento por prioridad (semáforo + importancia)
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
            const fechaA2 = new Date(a.fecha_registro);
            const fechaB2 = new Date(b.fecha_registro);
            return fechaA2 - fechaB2;
          }

          case 'fecha_desc':
            // Más reciente primero
            return new Date(b.fecha_registro) - new Date(a.fecha_registro);

          case 'fecha_asc':
            // Más antigua primero
            return new Date(a.fecha_registro) - new Date(b.fecha_registro);

          case 'importancia': {
            // Por nivel de importancia (1 más importante)
            const nivelA = parseInt(a.NivelImportancia) || 3;
            const nivelB = parseInt(b.NivelImportancia) || 3;
            if (nivelA !== nivelB) {
              return nivelA - nivelB;
            }
            // Si empatan, ordenar por fecha (más antiguo primero)
            return new Date(a.fecha_registro) - new Date(b.fecha_registro);
          }

          case 'folio':
            // Ordenar alfabéticamente por folio
            return (a.folio || '').localeCompare(b.folio || '');

          default:
            return 0;
        }
      });
    };


    const cargarPeticiones = async () => {
      try {
        loading.value = true;

        // ✅ NUEVO: Construir query params según permisos
        const queryParams = new URLSearchParams();

        // Si es Canalizador Estatal y seleccionó un municipio, agregarlo al query
        if (hasPermission('peticiones_estatal') && filtros.municipio_id) {
          queryParams.append('municipio_id', filtros.municipio_id);
        }

        const url = queryParams.toString()
          ? `${backendUrl}/peticiones.php?${queryParams.toString()}`
          : `${backendUrl}/peticiones.php`;

        const response = await axios.get(url);

        console.log('🔍 DEBUG - Respuesta del backend:', response.data);

        const peticionesRaw = response.data.records || [];

        console.log('📊 Total peticiones recibidas:', peticionesRaw.length);

        // ✅ MODIFICADO: El filtro por municipio ahora lo hace el backend
        // Solo mostramos el mensaje informativo
        const divisionUsuario = obtenerDivisionUsuario();

        if (hasPermission('peticiones_municipio') && divisionUsuario) {
          console.log(`🏢 Canalizador Municipal - Mostrando solo municipio ${divisionUsuario}`);
        } else if (hasPermission('peticiones_estatal')) {
          console.log('🌍 Canalizador Estatal - Mostrando todos los municipios o filtrado seleccionado');
        } else {
          console.log('👤 Usuario con acceso completo - Mostrando todas las peticiones');
        }

        // Asegurar que todas las peticiones tengan array de departamentos
        peticiones.value = peticionesRaw.map(pet => ({
          ...pet,
          departamentos: pet.departamentos || []
        }));

        console.log('✅ Peticiones procesadas:', peticiones.value.length);

        // Limpiar cache de semáforo
        limpiarCacheSemaforo();

        // Ordenamos las peticiones según criterio seleccionado
        peticiones.value = ordenarPeticiones(peticiones.value);

        // Aplicamos filtros después de cargar
        aplicarFiltros();

        // Inicializar paginación
        actualizarPaginacion();

        loading.value = false;
      } catch (error) {
        console.error('❌ Error al cargar peticiones:', error);
        loading.value = false;
        if (window.$toast) {
          window.$toast.error('Error al cargar peticiones');
        }
      }
    };

    // ✅ OPTIMIZADO: Cachear departamentos para evitar cargas múltiples
    let departamentosCargados = false;
    const cargarDepartamentos = async (forzarRecarga = false) => {
      // Si ya están cargados y no se fuerza recarga, salir
      if (departamentosCargados && !forzarRecarga && departamentos.value.length > 0) {
        console.log('📦 Usando departamentos en cache');
        return;
      }

      try {
        loadingDepartamentos.value = true;
        console.log('🔄 Cargando unidades desde API...');

        const response = await axios.get(`${backendUrl}/unidades.php`);
        console.log('📦 Respuesta unidades:', response.data);

        if (response.data && response.data.records) {
          departamentos.value = response.data.records;
          departamentosCargados = true;
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

          // ✅ NUEVO: Inicializar filtros
          departamentosFiltrados.value = [...departamentosDisponibles.value];
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
        return departamentos[0].nombre_unidad || 'Sin nombre';
      }

      if (departamentos.length <= 3) {
        return departamentos.map(d => d.nombre_unidad || 'Sin nombre').join(', ');
      }

      return `${departamentos.length} departamentos`;
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

    // Computed para contar peticiones sin seguimiento
    const contadorSinSeguimiento = computed(() => {
      return peticiones.value.filter(p => !tieneUsuarioAsignado(p)).length;
    });

    // ✅ NUEVO: Cache de cálculos de semáforo para mejorar rendimiento
    const cacheSemaforo = new Map();
    const obtenerColorSemaforoMemo = (peticion) => {
      const key = `${peticion.id}-${peticion.estado}-${peticion.fecha_registro}`;
      if (cacheSemaforo.has(key)) {
        return cacheSemaforo.get(key);
      }
      const color = obtenerColorSemaforo(peticion);
      cacheSemaforo.set(key, color);
      return color;
    };

    // ✅ NUEVO: Limpiar cache cuando se recargan peticiones
    const limpiarCacheSemaforo = () => {
      cacheSemaforo.clear();
    };

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

    // ✅ OPTIMIZADO: Función aplicarFiltros mejorada con debounce implícito
    const aplicarFiltros = () => {
      try {
        console.log('🔍 APLICANDO FILTROS:', filtros);
        console.log('📊 Total peticiones antes de filtrar:', peticiones.value.length);

        // Si no hay filtros activos, retornar todas las peticiones
        const hayFiltros = filtros.estado || filtros.departamento || filtros.folio ||
                          filtros.nombre || filtros.nivelImportancia || filtros.usuario_seguimiento ||
                          filtros.fechaDesde || filtros.fechaHasta;

        if (!hayFiltros) {
          peticionesFiltradas.value = ordenarPeticiones([...peticiones.value]);
          actualizarPaginacion();
          return;
        }

        let peticionesFiltradas_temp = [...peticiones.value];

        // Aplicar filtros con validaciones robustas
        peticionesFiltradas_temp = peticionesFiltradas_temp.filter(peticion => {
          // Validar que peticion existe
          if (!peticion) return false;

          // Filtrar por estado
          if (filtros.estado && filtros.estado.trim() !== '') {
            if (peticion.estado !== filtros.estado) {
              console.log(`❌ Petición ${peticion.folio} excluida por estado`);
              return false;
            }
          }

          // ✅ CORREGIDO: Filtrar por departamento - Solo aplicar si hay valor
          if (filtros.departamento && filtros.departamento.toString().trim() !== '') {
            const departamentoFiltro = parseInt(filtros.departamento);

            // Si no tiene departamentos asignados, excluir
            if (!peticion.departamentos || peticion.departamentos.length === 0) {
              console.log(`❌ Petición ${peticion.folio} excluida - sin departamentos`);
              return false;
            }

            // Buscar si tiene el departamento específico
            const tieneDepartamento = peticion.departamentos.some(dept => {
              const deptId = parseInt(dept.departamento_id || dept.id_unidad);
              return deptId === departamentoFiltro;
            });

            if (!tieneDepartamento) {
              console.log(`❌ Petición ${peticion.folio} excluida - no tiene dept ${departamentoFiltro}`);
              return false;
            }
          }

          // Filtrar por nivel de importancia
          if (filtros.nivelImportancia && filtros.nivelImportancia.toString().trim() !== '') {
            const nivel = parseInt(filtros.nivelImportancia);
            const peticionNivel = parseInt(peticion.NivelImportancia);
            if (isNaN(peticionNivel) || peticionNivel !== nivel) {
              console.log(`❌ Petición ${peticion.folio} excluida por nivel importancia`);
              return false;
            }
          }

          // ✅ ACTUALIZADO: Filtrar por usuario de seguimiento (incluyendo sin seguimiento)
          if (filtros.usuario_seguimiento && filtros.usuario_seguimiento.toString().trim() !== '') {
            if (filtros.usuario_seguimiento === 'sin_asignar') {
              // Mostrar solo peticiones SIN usuario asignado
              if (tieneUsuarioAsignado(peticion)) {
                console.log(`❌ Petición ${peticion.folio} excluida - tiene usuario asignado`);
                return false;
              }
            } else {
              // Filtrar por usuario específico
              const usuarioFiltro = parseInt(filtros.usuario_seguimiento);
              const usuarioPeticion = parseInt(peticion.usuario_id);
              if (isNaN(usuarioPeticion) || usuarioPeticion !== usuarioFiltro) {
                console.log(`❌ Petición ${peticion.folio} excluida por usuario seguimiento`);
                return false;
              }
            }
          }

          // Filtrar por folio con validación robusta
          if (filtros.folio && filtros.folio.trim() !== '') {
            const folioPeticion = peticion.folio || '';
            const folioFiltro = filtros.folio.trim();

            if (!folioPeticion.toLowerCase().includes(folioFiltro.toLowerCase())) {
              console.log(`❌ Petición ${peticion.folio} excluida por folio`);
              return false;
            }
          }

          // Filtrar por nombre with robust validation
          if (filtros.nombre && filtros.nombre.trim() !== '') {
            const nombrePeticion = peticion.nombre || '';
            const nombreFiltro = filtros.nombre.trim();

            if (!nombrePeticion.toLowerCase().includes(nombreFiltro.toLowerCase())) {
              console.log(`❌ Petición ${peticion.folio} excluida por nombre`);
              return false;
            }
          }

          // ✅ NUEVO: Filtrar por rango de fechas
          if (filtros.fechaDesde || filtros.fechaHasta) {
            const fechaPeticion = new Date(peticion.fecha_registro);
            fechaPeticion.setHours(0, 0, 0, 0); // Normalizar a inicio del día

            if (filtros.fechaDesde) {
              const fechaDesde = new Date(filtros.fechaDesde);
              fechaDesde.setHours(0, 0, 0, 0);
              if (fechaPeticion < fechaDesde) {
                console.log(`❌ Petición ${peticion.folio} excluida - antes de fecha desde`);
                return false;
              }
            }

            if (filtros.fechaHasta) {
              const fechaHasta = new Date(filtros.fechaHasta);
              fechaHasta.setHours(23, 59, 59, 999); // Incluir todo el día hasta
              if (fechaPeticion > fechaHasta) {
                console.log(`❌ Petición ${peticion.folio} excluida - después de fecha hasta`);
                return false;
              }
            }
          }

          return true;
        });

        console.log('✅ Peticiones después de filtrar:', peticionesFiltradas_temp.length);
        console.log('📋 Muestra de peticiones filtradas con departamentos:',
          peticionesFiltradas_temp.slice(0, 3).map(p => ({
            folio: p.folio,
            tiene_depts: p.departamentos?.length || 0,
            usuario_id: p.usuario_id
          }))
        );

        // Aplicamos el ordenamiento dinámico a los resultados filtrados
        peticionesFiltradas.value = ordenarPeticiones(peticionesFiltradas_temp);

        // Actualizar paginación después de filtrar
        actualizarPaginacion();

      } catch (error) {
        console.error('Error en aplicarFiltros:', error);
        // En caso de error, mostrar todas las peticiones
        peticionesFiltradas.value = [...peticiones.value];
        actualizarPaginacion();
      }
    };

    // ✅ OPTIMIZADO: Watchers con debounce para filtros de texto
    let debounceTimeout = null;
    watch(
      () => [filtros.estado, filtros.departamento, filtros.nivelImportancia, filtros.usuario_seguimiento, filtros.ordenarPor, filtros.fechaDesde, filtros.fechaHasta],
      () => {
        aplicarFiltros();
      }
    );

    // Debounce para filtros de texto (300ms)
    watch(
      () => [filtros.folio, filtros.nombre],
      () => {
        if (debounceTimeout) clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
          aplicarFiltros();
        }, 300);
      }
    );

    // ✅ AGREGAR: Watcher para filtro de departamento
    watch(() => filtros.departamento, () => {
      aplicarFiltros();
    });

    // ✅ NUEVO: Watcher para filtro de municipio (recarga peticiones del backend)
    watch(() => filtros.municipio_id, async () => {
      if (hasPermission('peticiones_estatal')) {
        await cargarPeticiones();
      }
    });

    // En el setup(), agregar nuevas variables reactivas:
    const busquedaDepartamento = ref('');
    const departamentosFiltrados = ref([]);
    const sugerenciasRapidas = computed(() => {
      return sugerenciasIA.value
        .filter(s => s.departamento_nombre && s.departamento_nombre.trim() !== '')
        .map(s => s.departamento_nombre)
        .filter((nombre, index, arr) => arr.indexOf(nombre) === index); // Eliminar duplicados
    });

    // ✅ NUEVO: Computed para usuarios únicos (optimiza el filtro de usuario)
    const usuariosUnicos = computed(() => {
      const usuarios = new Map();
      peticiones.value.forEach(p => {
        if (tieneUsuarioAsignado(p) && p.usuario_id) {
          const nombre = p.nombre_completo_usuario || p.nombre_usuario_seguimiento || 'Usuario';
          usuarios.set(p.usuario_id, nombre);
        }
      });
      return Array.from(usuarios, ([id, nombre]) => ({ id, nombre }));
    });

    // ✅ NUEVA: Función para filtrar departamentos
    const filtrarDepartamentos = () => {
      if (!busquedaDepartamento.value || busquedaDepartamento.value.trim() === '') {
        departamentosFiltrados.value = [...departamentosDisponibles.value];
        return;
      }

      const busqueda = busquedaDepartamento.value.toLowerCase().trim();
      departamentosFiltrados.value = departamentosDisponibles.value.filter(dept =>
        dept.nombre_unidad.toLowerCase().includes(busqueda)
      );
    };

    // ✅ NUEVA: Función para buscar sugerencia rápida
    const buscarSugerencia = (nombreSugerencia) => {
      busquedaDepartamento.value = nombreSugerencia;
      filtrarDepartamentos();
    };

    // ✅ NUEVA: Función para verificar si un departamento está sugerido
    const esDepartamentoSugerido = (nombreDepartamento) => {
      return sugerenciasIA.value.some(s =>
        s.departamento_nombre &&
        s.departamento_nombre.toLowerCase().includes(nombreDepartamento.toLowerCase())
      );
    };

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
      busquedaDepartamento.value = '';// ✅ Resetear búsqueda

      await cargarDepartamentosAsignados(peticion.id);
      showDepartamentosModal.value = true;
    };

    // Función para mostrar menú de opciones de departamentos
    const mostrarMenuDepartamentos = (peticion) => {
      peticionSeleccionadaMenu.value = peticion;
      showMenuDepartamentos.value = true;
    };

    // Función para ver estados desde el menú
    const verEstadosDepartamentos = () => {
      showMenuDepartamentos.value = false;
      abrirModalDepartamentosEstados(peticionSeleccionadaMenu.value);
    };

    // Función para gestionar desde el menú
    const gestionarDepartamentosMenu = () => {
      showMenuDepartamentos.value = false;
      gestionarDepartamentos(peticionSeleccionadaMenu.value);
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

          // ✅ OPTIMIZADO: Solo recargar departamentos asignados, no todas las peticiones
          await cargarDepartamentosAsignados(peticionForm.id);
          departamentosSeleccionados.value = [];

          // Actualizar departamentos en la petición local
          const peticion = peticiones.value.find(p => p.id === peticionForm.id);
          if (peticion && response.data.departamentos) {
            peticion.departamentos = response.data.departamentos;
            aplicarFiltros();
          }
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

          // ✅ OPTIMIZADO: Solo recargar departamentos asignados
          await cargarDepartamentosAsignados(peticionForm.id);

          // Actualizar departamentos en la petición local
          const peticion = peticiones.value.find(p => p.id === peticionForm.id);
          if (peticion) {
            peticion.departamentos = departamentosAsignados.value;
            aplicarFiltros();
          }
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

          // ✅ OPTIMIZADO: Solo actualizar el estado local del departamento
          const deptAsignado = departamentosAsignados.value.find(d => d.asignacion_id === asignacionId);
          if (deptAsignado) {
            deptAsignado.estado_asignacion = nuevoEstado;
          }

          // También actualizar en la petición local si está cargada
          const peticionLocal = peticiones.value.find(p => p.id === peticionForm.id);
          if (peticionLocal && peticionLocal.departamentos) {
            const dept = peticionLocal.departamentos.find(d => d.asignacion_id === asignacionId);
            if (dept) {
              dept.estado_asignacion = nuevoEstado;
            }
          }
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

          // ✅ OPTIMIZADO: Actualizar solo la petición local sin recargar todo
          const peticionLocal = peticiones.value.find(p => p.id === peticion.id);
          if (peticionLocal) {
            peticionLocal.usuario_id = usuarioId;
            peticionLocal.nombre_completo_usuario = nombreCompleto;
            peticionLocal.nombre_usuario_seguimiento = usuarioLogueado.value?.Nombre;
            aplicarFiltros();
          }

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

        // ✅ OPTIMIZADO: Actualizar solo el registro local sin recargar todo
        const peticion = peticiones.value.find(p => p.id === peticionForm.id);
        if (peticion) {
          peticion.estado = peticionForm.estado;
          aplicarFiltros(); // Re-aplicar filtros con datos actuales
        }

        showEstadoModal.value = false;
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

        // ✅ OPTIMIZADO: Actualizar solo el registro local sin recargar todo
        const peticion = peticiones.value.find(p => p.id === peticionForm.id);
        if (peticion) {
          peticion.NivelImportancia = parseInt(peticionForm.NivelImportancia);
          limpiarCacheSemaforo(); // Limpiar cache de semáforo
          aplicarFiltros(); // Re-aplicar filtros con datos actuales
        }

        showImportanciaModal.value = false;
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

    const toggleAccionesMenu = (peticion, event) => {
      if (peticionActiva.value === peticion.id) {
        peticionActiva.value = null;
        dropdownStyle.value = {};
      } else {
        peticionActiva.value = peticion.id;

        // Calcular posición del dropdown con position fixed
        const button = event.currentTarget;
        const rect = button.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const dropdownHeight = 250; // Altura estimada

        // Determinar si va arriba o abajo
        const spaceBelow = viewportHeight - rect.bottom;
        const spaceAbove = rect.top;

        if (spaceBelow >= dropdownHeight || spaceBelow > spaceAbove) {
          // Mostrar abajo
          dropdownStyle.value = {
            top: `${rect.bottom + 4}px`,
            left: `${rect.left}px`,
          };
        } else {
          // Mostrar arriba
          dropdownStyle.value = {
            bottom: `${viewportHeight - rect.top + 4}px`,
            left: `${rect.left}px`,
          };
        }
      }
    };

    const cerrarMenuAcciones = () => {
      peticionActiva.value = null;
      dropdownStyle.value = {};
    };

    const cerrarMenusAcciones = (event) => {
      if (event.target.closest('.acciones-dropdown') ||
          event.target.closest('.action-btn.menu')) {
        return;
      }

      peticionActiva.value = null;
      dropdownStyle.value = {};
    };

    const cancelarAccion = () => {
      showEditModal.value = false;
      showEstadoModal.value = false;
      showDepartamentosModal.value = false;
      showMenuDepartamentos.value = false;
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

    // ✅ NUEVA: Función para filtrar peticiones sin seguimiento
    const filtrarSinSeguimiento = () => {
      // Filtrar peticiones que no tienen usuario asignado (usuario_id es null, undefined, 0 o vacío)
      filtros.usuario_seguimiento = 'sin_asignar'; // Usamos un valor especial
    };

    // ✅ CORREGIDA: Función limpiarFiltros mejorada
    const limpiarFiltros = () => {
      // Limpiar todos los filtros
      filtros.estado = '';
      filtros.departamento = '';
      filtros.folio = '';
      filtros.nombre = '';
      filtros.nivelImportancia = '';
      filtros.usuario_seguimiento = '';
      filtros.municipio_id = '';
      filtros.ordenarPor = 'prioridad'; // ✅ NUEVO: Resetear a ordenamiento por defecto
      filtros.fechaDesde = ''; // ✅ NUEVO
      filtros.fechaHasta = ''; // ✅ NUEVO

      // Forzar aplicación de filtros
      aplicarFiltros();

      // Resetear paginación a la primera página
      paginacion.paginaActual = 1;

      if (window.$toast) {
        window.$toast.info('Filtros limpiados');
      }
    };

    // ✅ NUEVO: Cargar municipios para el filtro
    const cargarMunicipios = async () => {
      try {
        const response = await axios.get(`${backendUrl}/division.php`);
        if (response.data.success && response.data.divisions) {
          municipios.value = response.data.divisions;
        }
      } catch (error) {
        console.error('Error al cargar municipios:', error);
      }
    };

    // ✅ NUEVO: Verificar si el usuario tiene un permiso específico
    const hasPermission = (permiso) => {
      if (!usuarioLogueado.value || !usuarioLogueado.value.Permisos) {
        return false;
      }
      return usuarioLogueado.value.Permisos.includes(permiso);
    };

    onMounted(async () => {
      // ✅ IMPORTANTE: Obtener usuario ANTES de cargar peticiones
      await obtenerUsuarioLogueado();

      await Promise.all([
        cargarPeticiones(),
        cargarDepartamentos(),
        cargarMunicipios() // ✅ NUEVO: Cargar municipios
      ]);

      document.addEventListener('click', cerrarMenusAcciones);

      // Cerrar dropdown al hacer scroll
      const scrollContainer = document.querySelector('.tabla-scroll-container');
      if (scrollContainer) {
        scrollContainer.addEventListener('scroll', cerrarMenuAcciones);
      }
    });

    onBeforeUnmount(() => {
      document.removeEventListener('click', cerrarMenusAcciones);
      const scrollContainer = document.querySelector('.tabla-scroll-container');
      if (scrollContainer) {
        scrollContainer.removeEventListener('scroll', cerrarMenuAcciones);
      }
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

    const obtenerTituloDepartamentos = (departamentos) => {
      if (!departamentos || departamentos.length === 0) {
        return 'Esta petición no tiene departamentos asignados';
      }

      if (departamentos.length === 1) {



        const dept = departamentos[0];
        return `Departamento: ${dept.nombre_unidad}\nEstado: ${dept.estado_asignacion}\nFecha: ${formatearFecha(dept.fecha_asignacion)}`;
      }

      return `Departamentos asignados:\n${departamentos.map(d =>
        `• ${d.nombre_unidad} (${d.estado_asignacion})`
      ).join('\n')}`;
    };

    // En el setup(), agregar esta función después de esUsuarioAsignado:

    const puedeEditarPeticion = (peticion) => {
      if (!usuarioLogueado.value) return false;

      // El usuario solo puede editar si es el asignado para seguimiento
      return tieneUsuarioAsignado(peticion) && peticion.usuario_id === usuarioLogueado.value.Id;
    };

    // ✅ NUEVO: Variables para modal de estados de departamentos
    const showModalDepartamentosEstados = ref(false);
    const peticionDeptEstados = ref({ departamentos: [] });

    // ✅ NUEVA: Función para abrir modal de estados de departamentos
    const abrirModalDepartamentosEstados = (peticion) => {
      peticionDeptEstados.value = { ...peticion };
      showModalDepartamentosEstados.value = true;
    };

    // ✅ NUEVA: Función para cerrar modal de estados
    const cerrarModalDepartamentosEstados = () => {
      showModalDepartamentosEstados.value = false;
      peticionDeptEstados.value = { departamentos: [] };
    };

    // ✅ NUEVA: Función para abrir historial desde el modal de estados
    const abrirHistorialDepartamentoDesdeModal = async (peticion, departamento) => {
      // Cerrar el modal de estados
      cerrarModalDepartamentosEstados();
      // Abrir el modal de historial
      await abrirHistorialDepartamento(peticion, departamento);
    };

    // ✅ NUEVO: Variables para modal de historial de departamento
    const showHistorialDepartamentoModal = ref(false);
    const historialDeptSeleccionado = ref({});
    const historialPeticionSeleccionada = ref({});
    const historialDeptCambios = ref([]);
    const loadingHistorialDept = ref(false);

    // ✅ NUEVO: Variables para modal de detalles de petición
    const showDetallesPeticionModal = ref(false);
    const peticionDetalles = ref({});

    // ✅ NUEVA: Función para abrir historial de departamento
    const abrirHistorialDepartamento = async (peticion, departamento) => {
      historialPeticionSeleccionada.value = peticion;
      historialDeptSeleccionado.value = departamento;
      showHistorialDepartamentoModal.value = true;
      loadingHistorialDept.value = true;

      try {
        const asignacionId = departamento.id || departamento.asignacion_id;
        const response = await axios.get(`${backendUrl}/departamentos_peticiones.php`, {
          params: { asignacion_id: asignacionId }
        });

        if (response.data.success) {
          historialDeptCambios.value = response.data.historial || [];
        }
      } catch (error) {
        console.error('Error al cargar historial:', error);
        if (window.$toast) {
          window.$toast.error('Error al cargar el historial');
        }
      } finally {
        loadingHistorialDept.value = false;
      }
    };

    // ✅ NUEVA: Función para cerrar historial de departamento
    const cerrarHistorialDepartamento = () => {
      showHistorialDepartamentoModal.value = false;
      historialDeptSeleccionado.value = {};
      historialPeticionSeleccionada.value = {};
      historialDeptCambios.value = [];
    };

    // ✅ NUEVA: Función auxiliar para formatear fecha completa
    const formatearFechaCompleta = (fechaStr) => {
      if (!fechaStr) return '';
      const fecha = new Date(fechaStr);
      return fecha.toLocaleString('es-MX', {
        year: 'numeric',
               month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };

    // ✅ NUEVA: Función auxiliar para truncar texto
    const truncarTexto = (texto, maxLength = 100) => {
      if (!texto || texto.length <= maxLength) return texto;
      return texto.substring(0, maxLength) + '...';
    };

    // ✅ NUEVA: Función para abrir modal de detalles de petición
    const abrirDetallesPeticion = (peticion) => {
      peticionDetalles.value = { ...peticion };
      showDetallesPeticionModal.value = true;
    };

    // ✅ NUEVA: Función para cerrar modal de detalles
    const cerrarDetallesPeticion = () => {
      showDetallesPeticionModal.value = false;
      peticionDetalles.value = {};
    };

    return {
      loading,

      peticiones,
      peticionesFiltradas,
      departamentos,
      showEditModal,
      showEstadoModal,
      showDepartamentosModal,
      showMenuDepartamentos,
      peticionSeleccionadaMenu,
      showImportanciaModal,
      peticionForm,
      filtros,
      peticionActiva,
      usuarioLogueado,
      accionesRefs,
      dropdownStyle,
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
      obtenerColorSemaforoMemo,
      obtenerTituloSemaforo,
      aplicarFiltros,
      usuariosUnicos,
      editarPeticion,
      cambiarEstado,
      cambiarImportancia,
      seguimiento,
      gestionarDepartamentos,
      mostrarMenuDepartamentos,
      verEstadosDepartamentos,
      gestionarDepartamentosMenu,
      asignarDepartamentos,
      eliminarDepartamentoAsignado,
      cambiarEstadoAsignacion,
      obtenerDepartamentosPeticion,
      formatearDepartamentosResumen,
      obtenerTituloDepartamentos,

      guardarPeticion,
      guardarEstado,
      guardarImportancia,
      esUsuarioAsignado,
      obtenerUsuarioLogueado,
      obtenerInfoUsuarioLogueado, // ✅ Ahora la función está definida
      obtenerDivisionUsuario,
      tieneUsuarioAsignado,
      obtenerIconoSeguimiento,
      obtenerTituloSeguimiento,
      obtenerClaseSeguimiento,
      obtenerEtiquetaNivelImportancia,
      obtenerTextoNivelImportancia,

      sugerenciasIA,
      asignarDesdeSugerencia,

      // Funciones de paginación
      irAPagina,
      paginaAnterior,
      paginaSiguiente,
      cambiarRegistrosPorPagina,
      actualizarPaginacion,

      // Nuevas variables y funciones para gestión de departamentos
      busquedaDepartamento,
      departamentosFiltrados,
      sugerenciasRapidas,
      filtrarDepartamentos,
      buscarSugerencia,
      esDepartamentoSugerido,

      contadorSinSeguimiento,
      filtrarSinSeguimiento,
      limpiarFiltros,

      puedeEditarPeticion,
      toggleAccionesMenu,
      cerrarMenuAcciones,
      cancelarAccion,
      filtrarMisPeticiones,

      // ✅ NUEVO: Variables para filtro de municipios
      municipios,
      municipioUsuario,
      hasPermission,

      // ✅ Modal de estados de departamentos
      showModalDepartamentosEstados,
      peticionDeptEstados,
      abrirModalDepartamentosEstados,
      cerrarModalDepartamentosEstados,
      abrirHistorialDepartamentoDesdeModal,

      // ✅ Historial de departamento
      showHistorialDepartamentoModal,
      historialDeptSeleccionado,
      historialPeticionSeleccionada,
      historialDeptCambios,
      loadingHistorialDept,
      abrirHistorialDepartamento,
      cerrarHistorialDepartamento,
      truncarTexto,
      formatearFechaCompleta,

      // ✅ Modal de detalles de petición
      showDetallesPeticionModal,
      peticionDetalles,
      abrirDetallesPeticion,
      cerrarDetallesPeticion,
    };
  }
};
</script>

<style src="@/assets/css/Petition.css"></style>
<style>
/* Sin scoped - usando namespace .peticiones-container para evitar conflictos */

/* ✅ NUEVO: Estilos para Skeleton Loader */
.peticiones-container .loading-container {
  padding: 1rem;
}

.peticiones-container .skeleton-item {
  display: grid;
  grid-template-columns: 100px 120px 200px 130px 150px 180px 200px 180px 150px;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 0.5rem;
  border-radius: 8px;
}

.peticiones-container .skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 6px;
  height: 20px;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.peticiones-container .skeleton-acciones {
  width: 80%;
  height: 36px;
}

.peticiones-container .skeleton-folio {
  width: 90%;
  height: 24px;
}

.peticiones-container .skeleton-nombre {
  width: 95%;
}

.peticiones-container .skeleton-telefono {
  width: 85%;
}

.peticiones-container .skeleton-localidad {
  width: 90%;
}

.peticiones-container .skeleton-estado {
  width: 80%;
  height: 28px;
}

.peticiones-container .skeleton-depts {
  width: 90%;
}

.peticiones-container .skeleton-prioridad {
  width: 85%;
  height: 32px;
}

.peticiones-container .skeleton-fecha {
  width: 90%;
}

/* Estilos con máxima especificidad para forzar el header */
.peticiones-container .peticiones-list .tabla-scroll-container .tabla-contenido .list-header.header-forzado {
  display: grid !important;
  grid-template-columns: 100px 120px 200px 130px 150px 180px 200px 180px 150px !important;
  background: linear-gradient(135deg, #0074D9, #0056b3) !important;
  color: white !important;
  padding: 1rem !important;
  font-weight: 600 !important;
  font-size: 0.9rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  position: sticky !important;
  top: 0 !important;
  z-index: 100 !important;
  min-width: 1410px !important;
  width: calc(100% + 8px) !important;
  margin-right: -8px !important;
  box-sizing: border-box !important;
  border-radius: 12px 12px 0 0 !important;
}

.peticiones-container .peticiones-list .tabla-scroll-container .tabla-contenido .list-header.header-forzado > div {
  color: white !important;
  background: transparent !important;
}

/* ✅ ESTILOS PARA FILAS DE LA TABLA CON GRID CONSISTENTE */
.peticiones-container .peticion-item {
  display: grid !important;
  grid-template-columns: 100px 120px 200px 130px 150px 180px 200px 180px 150px !important;
  align-items: center !important;
  padding: 0.75rem 0 !important;
  border-bottom: 1px solid #e9ecef !important;
  transition: all 0.2s ease !important;
  min-width: 1410px !important;
  gap: 1rem !important;
  background: white;
}

.peticiones-container .peticion-item:hover {
  background-color: #f8f9fa !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
}

/* ✅ ESTILOS ESPECÍFICOS PARA CADA COLUMNA */
.peticiones-container .peticion-acciones {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0 1rem;
}

.peticiones-container .peticion-folio {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0 1rem;
  transition: all 0.2s ease;
}

.peticiones-container .peticion-folio:hover {
  background: rgba(0, 116, 217, 0.1);
  border-radius: 6px;
}

.peticiones-container .peticion-nombre {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0 1rem;
  overflow: hidden;
  transition: all 0.2s ease;
}

.peticiones-container .peticion-nombre:hover {
  background: rgba(0, 116, 217, 0.1);
  border-radius: 6px;
}

.peticiones-container .peticion-telefono {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0 1rem;
  transition: all 0.2s ease;
}

.peticiones-container .peticion-telefono:hover {
  background: rgba(0, 116, 217, 0.1);
  border-radius: 6px;
}

.peticiones-container .peticion-localidad {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0 1rem;
  overflow: hidden;
  transition: all 0.2s ease;
}

.peticiones-container .peticion-localidad:hover {
  background: rgba(0, 116, 217, 0.1);
  border-radius: 6px;
}

.peticiones-container .peticion-estado {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0 1rem;
  transition: all 0.2s ease;
}

.peticiones-container .peticion-estado:hover {
  background: rgba(0, 116, 217, 0.1);
  border-radius: 6px;
}

.peticiones-container .peticion-departamentos {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 1rem;
}

.peticiones-container .peticion-prioridad {
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 0 1rem;
  transition: all 0.2s ease;
}

.peticiones-container .peticion-prioridad:hover {
  background: rgba(0, 116, 217, 0.1);
  border-radius: 6px;
}

.peticiones-container .peticion-fecha {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0 1rem;
  font-size: 13px;
  transition: all 0.2s ease;
}

.peticiones-container .peticion-fecha:hover {
  background: rgba(0, 116, 217, 0.1);
  border-radius: 6px;
}

/* ✅ AJUSTES PARA CONTENIDO DE COLUMNAS */
.peticiones-container .folio-badge {
  background: linear-gradient(135deg, #0074D9, #0056b3);
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.peticiones-container .nombre-peticion {
  font-weight: 500;
  color: #495057;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 180px;
}

.peticiones-container .telefono,
.peticiones-container .localidad {
  font-size: 13px;
  color: #6c757d;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.peticiones-container .indicadores-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.peticiones-container .fecha-registro {
  font-size: 12px;
  color: #6c757d;
  white-space: nowrap;
}

/* ✅ NUEVO: Estilos para items de la tabla con grid consistente */
.peticiones-container .peticion-item {
  display: grid !important;
  grid-template-columns: 100px 120px 200px 130px 150px 180px 200px 180px 150px !important;
  align-items: center !important;
  padding: 0.75rem 1rem !important;
  border-bottom: 1px solid #e9ecef !important;
  transition: all 0.2s ease !important;
  min-width: 1410px !important;
  gap: 0 !important;
}

.peticiones-container .peticion-item:hover {
  background-color: #f8f9fa !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
}

/* ✅ NUEVO: Estilos específicos para cada columna */
.peticiones-container .peticion-acciones {
  display: flex;
  justify-content: center;
  align-items: center;
}

.peticiones-container .peticion-folio {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.peticiones-container .peticion-nombre {
  display: flex;
  align-items: center;
  cursor: pointer;
  overflow: hidden;
}

.peticiones-container .peticion-telefono {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.peticiones-container .peticion-localidad {
  display: flex;
  align-items: center;
  cursor: pointer;
  overflow: hidden;
}

.peticiones-container .peticion-estado {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.peticiones-container .peticion-departamentos {
  display: flex;
  align-items: center;
  justify-content: center;
}

.peticiones-container .peticion-prioridad {
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.peticiones-container .peticion-fecha {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 13px;
}

/* Estilos para el header con título y municipio */
.peticiones-container .header-title-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.peticiones-container .header-title-section h3 {
  margin: 0;
}

/* Estilos para el indicador de municipio */
.peticiones-container .municipio-indicator {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  color: #2e7d32;
  border: 1px solid #a5d6a7;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.peticiones-container .municipio-indicator i {
  font-size: 14px;
  color: #43a047;
}

.peticiones-container .municipio-indicator .municipio-label {
  font-weight: 500;
  color: #388e3c;
}

.peticiones-container .municipio-indicator .municipio-nombre {
  font-weight: 700;
  color: #1b5e20;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.peticiones-container .municipio-indicator.warning {
  background: linear-gradient(135deg, #fff3e0, #ffe0b2);
  color: #e65100;
  border-color: #ffcc80;
}

.peticiones-container .municipio-indicator.warning i {
  color: #ff9800;
}

.peticiones-container .municipio-indicator.warning span {
  color: #e65100;
  font-weight: 500;
}

/* Estilos para el mensaje de bienvenida y contador */
.peticiones-container .welcome-message {
  margin-bottom: 15px;
  color: #666;
  font-size: 14px;
}

.peticiones-container .welcome-message strong {
  color: #1b5e20;
  text-transform: uppercase;
}

.peticiones-container .peticiones-count {
  color: #999;
  font-size: 12px;
  margin-left: 8px;
}

/* Responsive */
@media (max-width: 768px) {
  .peticiones-container .header-title-section {
    align-items: flex-start;
  }

  .peticiones-container .municipio-indicator {
    font-size: 11px;
    padding: 4px 10px;
  }

  .peticiones-container .municipio-indicator .municipio-label {
    display: none;
  }
}

/* Estilos para sugerencias rápidas */
.peticiones-container .sugerencias-rapidas {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 12px;
  padding: 12px;
  background: linear-gradient(135deg, #fff9e6, #fff3d9);
  border-radius: 10px;
  border: 1px solid #ffe0a3;
}

.peticiones-container .sugerencias-label {
  font-size: 13px;
  font-weight: 600;
  color: #b8860b;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-right: 4px;
}

.peticiones-container .sugerencias-label::before {
  content: "💡";
  font-size: 16px;
}

.peticiones-container .btn-sugerencia-rapida {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  border: 1px solid #daa520;
  border-radius: 18px;
  color: #8b6914;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(218, 165, 32, 0.2);
}

.peticiones-container .btn-sugerencia-rapida:hover {
  background: linear-gradient(135deg, #ffed4e, #ffd700);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(218, 165, 32, 0.3);
  border-color: #b8860b;
}

.peticiones-container .btn-sugerencia-rapida:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(218, 165, 32, 0.2);
}

.peticiones-container .btn-sugerencia-rapida i {
  font-size: 13px;
  animation: pulse-light 2s ease-in-out infinite;
}

@keyframes pulse-light {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.6;
  }
}

/* Contenedor de búsqueda */
.peticiones-container .busqueda-departamentos {
  margin-bottom: 20px;
}

.peticiones-container .busqueda-input-container {
  position: relative;
  margin-bottom: 8px;
}

.peticiones-container .busqueda-input {
  width: 100%;
  padding: 12px 40px 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  transition: all 0.3s ease;
}

.peticiones-container .busqueda-input:focus {
  outline: none;
  border-color: #0074D9;
  box-shadow: 0 0 0 3px rgba(0, 116, 217, 0.1);
}

.peticiones-container .busqueda-icon {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 16px;
}

/* Responsive para sugerencias */
@media (max-width: 768px) {
  .peticiones-container .sugerencias-rapidas {
    flex-direction: column;
    align-items: flex-start;
  }

  .peticiones-container .sugerencias-label {
    width: 100%;
    margin-bottom: 4px;
  }

  .peticiones-container .btn-sugerencia-rapida {
    font-size: 11px;
    padding: 5px 12px;
  }
}

/* Estilos para nombre clickeable */
.peticiones-container .nombre-clickable {
  cursor: pointer;
  color: #0074D9;
  transition: all 0.2s ease;
  text-decoration: none;
  border-bottom: 1px dotted transparent;
}

.peticiones-container .nombre-clickable:hover {
  color: #0056b3;
  border-bottom-color: #0056b3;
  transform: translateY(-1px);
}

/* Estilos para modal de opciones de departamentos */
.peticiones-container .modal-opciones-departamentos {
  max-width: 500px;
  width: 90vw;
  max-height: 80vh;
}

.peticiones-container .modal-opciones-departamentos .modal-body {
  padding: 1.5rem;
}

.peticiones-container .peticion-info {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  border-left: 4px solid #0074D9;
  font-size: 14px;
  color: #495057;
}

.peticiones-container .opciones-departamentos {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.peticiones-container .btn-opcion {
  display: flex;
  align-items: center;
  padding: 1rem;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: left;
  width: 100%;
}

.peticiones-container .btn-opcion:hover {
  border-color: #0074D9;
  box-shadow: 0 4px 12px rgba(0, 116, 217, 0.15);
  transform: translateY(-2px);
}

.peticiones-container .btn-ver-estados {
  border-color: #28a745;
}

.peticiones-container .btn-ver-estados:hover {
  border-color: #1e7e34;
  background: linear-gradient(135deg, #f8fff8, #e8f5e8);
}

.peticiones-container .btn-gestionar {
  border-color: #ffc107;
}

.peticiones-container .btn-gestionar:hover {
  border-color: #e0a800;
  background: linear-gradient(135deg, #fffbf0, #fff3cd);
}

.peticiones-container .opcion-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  width: 100%;
}

.peticiones-container .opcion-content i {
  font-size: 1.5rem;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.peticiones-container .btn-ver-estados .opcion-content i {
  color: #28a745;
}

.peticiones-container .btn-gestionar .opcion-content i {
  color: #ffc107;
}

.peticiones-container .opcion-text {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.peticiones-container .opcion-titulo {
  font-weight: 600;
  font-size: 16px;
  color: #495057;
  margin-bottom: 4px;
}

.peticiones-container .opcion-descripcion {
  font-size: 13px;
  color: #6c757d;
}

.peticiones-container .sin-permisos {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1rem;
  background: #fff5f5;
  border: 2px dashed #dc3545;
  border-radius: 8px;
  color: #721c24;
  font-size: 14px;
}

.peticiones-container .sin-permisos i {
  color: #dc3545;
  font-size: 1.2rem;
}

/* Responsive para modal de opciones */
@media (max-width: 768px) {
  .peticiones-container .modal-opciones-departamentos {
    width: 95vw;
    max-width: none;
  }

  .peticiones-container .opcion-content {
    gap: 0.75rem;
  }

  .peticiones-container .opcion-titulo {
    font-size: 15px;
  }

  .peticiones-container .opcion-descripcion {
    font-size: 12px;
  }
}

/* Estilos para modal de detalles de petición */
.peticiones-container .modal-detalles-peticion {
  max-width: 1000px;
  width: 95vw;
  max-height: 90vh;
  overflow-y: auto;
  overflow-x: hidden;
}

.peticiones-container .modal-detalles-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.1rem;
  background: linear-gradient(135deg, #0074D9, #0056b3);
  color: white;
  border-radius: 12px 12px 0 0;
}

.peticiones-container .modal-detalles-header h3 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.3rem;
}

.peticiones-container .header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.peticiones-container .modal-detalles-body {
  padding: 2rem;
  background: #f8f9fa;
  overflow-x: hidden;
  word-wrap: break-word;
}

.peticiones-container .detalles-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 2rem;
  margin-bottom: 2rem;
  width: 100%;
  max-width: 100%;
}

.peticiones-container .detalle-seccion {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
  display: flex;
  flex-direction: column;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}

.peticiones-container .detalle-seccion h4 {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #495057;
  margin-bottom: 1.5rem;
  font-size: 1.1rem;
  border-bottom: 2px solid #e9ecef;
  padding-bottom: 0.5rem;
}

.peticiones-container .detalle-item {
  display: flex;
  margin-bottom: 1rem;
  align-items: flex-start;
  min-width: 0;
  max-width: 100%;
}

.peticiones-container .detalle-item .label {
  font-weight: 600;
  color: #6c757d;
  min-width: 120px;
  margin-right: 1rem;
}

.peticiones-container .detalle-item .valor {
  color: #495057;
  flex: 1;
  word-wrap: break-word;
  overflow-wrap: break-word;
  word-break: break-word;
  max-width: 100%;
  white-space: pre-wrap;
}

.peticiones-container .descripcion-completa {
  grid-column: 1 / -1;
}

.peticiones-container .descripcion-contenido {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  border-left: 4px solid #0074D9;
  line-height: 1.6;
  white-space: pre-wrap;
  font-size: 15px;
  color: #495057;
}

.peticiones-container .departamentos-info {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
}

.peticiones-container .departamentos-lista {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

/* Estilos para cuando no hay departamentos */
.peticiones-container .no-departamentos {
  text-align: center;
  padding: 2rem 1rem;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border: 2px dashed #dee2e6;
  border-radius: 12px;
  color: #6c757d;
  font-size: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  margin: 1rem 0;
}

.peticiones-container .no-departamentos i {
  font-size: 2rem;
  opacity: 0.7;
  margin-bottom: 0.5rem;
  color: #adb5bd;
}

.peticiones-container .no-departamentos .mensaje {
  font-weight: 500;
  color: #6c757d;
}

.peticiones-container .no-departamentos .descripcion {
  font-size: 12px;
  color: #adb5bd;
  margin-top: 0.25rem;
}

.peticiones-container .departamento-item {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid #6c757d;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.peticiones-container .dept-info h5 {
  margin: 0 0 0.5rem 0;
  color: #495057;
  font-size: 14px;
}

.peticiones-container .dept-estado {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  background: #6c757d;
  color: white;
}

.peticiones-container .dept-estado-pendiente { background: #ffc107; color: #212529; }
.peticiones-container .dept-estado-aceptado { background: #28a745; }
.peticiones-container .dept-estado-rechazado { background: #dc3545; }
.peticiones-container .dept-estado-procesando { background: #17a2b8; }

.peticiones-container .dept-fecha {
  font-size: 12px;
  color: #6c757d;
  text-align: right;
}

.peticiones-container .imagenes-peticion {
  grid-column: 1 / -1;
}

.peticiones-container .galeria-contenedor {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 2px dashed #dee2e6;
}

.peticiones-container .estado-badge,
.peticiones-container .prioridad-badge {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.peticiones-container .prioridad-1 { background: #dc3545; color: white; }
.peticiones-container .prioridad-2 { background: #fd7e14; color: white; }
.peticiones-container .prioridad-3 { background: #ffc107; color: #212529; }
.peticiones-container .prioridad-4 { background: #28a745; color: white; }
.peticiones-container .prioridad-5 { background: #6c757d; color: white; }

/* Estilos para imágenes en historial */
.peticiones-container .historial-imagenes {
  margin-top: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 3px solid #17a2b8;
}

.peticiones-container .historial-imagenes h6 {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #495057;
  margin-bottom: 1rem;
  font-size: 13px;
  font-weight: 600;
}

/* Responsive para modal de detalles */
@media (max-width: 768px) {
  .peticiones-container .modal-detalles-peticion {
    width: 98vw;
    max-height: 95vh;
  }

  .peticiones-container .detalles-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
    max-width: 100%;
    overflow-x: hidden;
  }

  .peticiones-container .modal-detalles-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .peticiones-container .header-actions {
    justify-content: space-between;
  }

  .peticiones-container .detalle-item {
    flex-direction: column;
    min-width: 0;
    max-width: 100%;
    word-wrap: break-word;
  }

  .peticiones-container .detalle-item .label {
    min-width: auto;
    margin-right: 0;
    margin-bottom: 0.25rem;
  }
}

/* Estilos para badges de acciones */
.peticiones-container .acciones-badges {
  display: flex;
  gap: 6px;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
}

.peticiones-container .accion-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  border: 1px solid;
  cursor: pointer;
  transition: all 0.2s ease;
  background: white;
  min-height: 24px;
}

.peticiones-container .edit-badge {
  color: #0074D9;
  border-color: #0074D9;
}

.peticiones-container .edit-badge:hover {
  background: linear-gradient(135deg, #0074D9, #0056b3);
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(0, 116, 217, 0.3);
}

.peticiones-container .seguimiento-badge {
  color: #28a745;
  border-color: #28a745;
}

.peticiones-container .seguimiento-badge:hover {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(40, 167, 69, 0.3);
}

.peticiones-container .accion-badge.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  color: #6c757d;
  border-color: #dee2e6;
}

.peticiones-container .accion-badge.disabled:hover {
  background: white;
  transform: none;
  box-shadow: none;
  color: #6c757d;
  border-color: #dee2e6;
}

.peticiones-container .accion-badge i {
  font-size: 9px;
}

.peticiones-container .accion-badge span {
  font-size: 9px;
  font-weight: 700;
}

/* Responsive para badges de acciones */
@media (max-width: 768px) {
  .peticiones-container .acciones-badges {
    gap: 4px;
  }

  .peticiones-container .accion-badge {
    padding: 3px 6px;
    font-size: 9px;
    gap: 3px;
    min-height: 20px;
  }

  .peticiones-container .accion-badge i,
  .peticiones-container .accion-badge span {
    font-size: 8px;
  }

  .peticiones-container .accion-badge span {
    display: none; /* Ocultar texto en móvil, solo mostrar iconos */
  }
}

/* Estilos para badges de departamentos */
.peticiones-container .departamentos-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 14px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid;
  min-height: 28px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.peticiones-container .sin-asignar-badge {
  background: linear-gradient(135deg, #fff5f5, #ffe9e9);
  color: #dc3545;
  border-color: #dc3545;
}

.peticiones-container .sin-asignar-badge:hover {
  background: linear-gradient(135deg, #ffe9e9, #ffcccb);
  border-color: #c82333;
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(220, 53, 69, 0.2);
}

.peticiones-container .con-asignaciones-badge {
  background: linear-gradient(135deg, #f8fff8, #e8f5e8);
  color: #28a745;
  border-color: #28a745;
}

.peticiones-container .con-asignaciones-badge:hover {
  background: linear-gradient(135deg, #e8f5e8, #d4edda);
  border-color: #1e7e34;
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2);
}

.peticiones-container .badge-text {
  font-weight: 600;
}

.peticiones-container .badge-icon,
.peticiones-container .badge-icon-left {
  font-size: 10px;
  opacity: 0.8;
}

.peticiones-container .badge-icon {
  margin-left: 2px;
}

.peticiones-container .badge-icon-left {
  margin-right: 2px;
}

/* Responsive para acciones y departamentos */
@media (max-width: 768px) {
  .peticiones-container .acciones-compact {
    gap: 2px;
  }

  .peticiones-container .accion-table-btn {
    width: 24px;
    height: 24px;
    font-size: 10px;
  }

  .peticiones-container .departamentos-badge {
    padding: 4px 8px;
    font-size: 10px;
    gap: 4px;
    min-height: 24px;
  }

  .peticiones-container .badge-icon,
  .peticiones-container .badge-icon-left {
    font-size: 9px;
  }
}

/* Estilos para columnas clickeables */
.peticiones-container .estado-clickeable.clickeable,
.peticiones-container .prioridad-clickeable.clickeable {
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 8px;
  padding: 4px 8px;
  position: relative;
}

.peticiones-container .estado-clickeable.clickeable:hover,
.peticiones-container .prioridad-clickeable.clickeable:hover {
  background-color: #f0f8ff;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 116, 217, 0.15);
}

.peticiones-container .estado-clickeable.no-clickeable,
.peticiones-container .prioridad-clickeable.no-clickeable {
  cursor: not-allowed;
  opacity: 0.7;
}

/* Iconos de edición */
.peticiones-container .estado-edit-icon,
.peticiones-container .prioridad-edit-icon {
  font-size: 12px;
  margin-left: 8px;
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.peticiones-container .estado-clickeable.clickeable:hover .estado-edit-icon,
.peticiones-container .prioridad-clickeable.clickeable:hover .prioridad-edit-icon {
  opacity: 1;
}

.peticiones-container .departamentos-lista {
  grid-template-columns: 1fr;
}
</style>
