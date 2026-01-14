<template>
  <div class="peticiones-container">
    <div class="card">
      <div class="card-header">
        <div class="header-title-section">
          <h3>Gestión de Peticiones</h3>
          <!-- ✅ NUEVO: Indicador del municipio que observa el usuario -->
          <div v-if="municipioUsuario" class="municipio-indicator">
            <i class="fas fa-map-marker-alt"></i>
            <span class="municipio-label">Municipio:</span>
            <span class="municipio-nombre">{{ municipioUsuario }}</span>
          </div>
          <div v-else-if="!loading && usuarioLogueado" class="municipio-indicator warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Sin municipio asignado - Mostrando todas las peticiones</span>
          </div>
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
          <template v-if="municipioUsuario">
            Administrando peticiones del municipio de <strong>{{ municipioUsuario }}</strong>
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
                <div class="peticion-acciones" :ref="el => { if (el) accionesRefs[peticion.id] = el }">
                  <button
                    :class="['action-btn', 'menu', { active: peticionActiva === peticion.id }]"
                    @click.stop="toggleAccionesMenu(peticion, $event)"
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
                    :style="dropdownStyle"
                  >
                    <!-- ✅ ACTUALIZADO: Botones condicionalmente deshabilitados -->
                    <button
                      class="dropdown-item"
                      :class="{ 'disabled': !puedeEditarPeticion(peticion) }"
                      :disabled="!puedeEditarPeticion(peticion)"
                      @click="puedeEditarPeticion(peticion) && (editarPeticion(peticion), cerrarMenuAcciones())"
                      :title="!puedeEditarPeticion(peticion) ? 'Solo el usuario asignado puede editar esta petición' : 'Editar petición'"
                    >
                      <i class="fas fa-edit"></i> Editar
                    </button>

                    <button
                      class="dropdown-item"
                      :class="{ 'disabled': !puedeEditarPeticion(peticion) }"
                      :disabled="!puedeEditarPeticion(peticion)"
                      @click="puedeEditarPeticion(peticion) && (cambiarEstado(peticion), cerrarMenuAcciones())"
                      :title="!puedeEditarPeticion(peticion) ? 'Solo el usuario asignado puede cambiar el estado' : 'Cambiar estado'"
                    >
                      <i class="fas fa-tasks"></i> Cambiar Estado
                    </button>

                    <!-- ✅ El botón de seguimiento siempre está disponible -->
                    <button class="dropdown-item" @click="seguimiento(peticion); cerrarMenuAcciones()">
                      <i class="fas fa-clipboard-list"></i>
                      {{ esUsuarioAsignado(peticion) ? 'Mi Seguimiento' : 'Asignar Seguimiento' }}
                    </button>

                    <button
                      class="dropdown-item"
                      :class="{ 'disabled': !puedeEditarPeticion(peticion) }"
                      :disabled="!puedeEditarPeticion(peticion)"
                      @click="puedeEditarPeticion(peticion) && (cambiarImportancia(peticion), cerrarMenuAcciones())"
                      :title="!puedeEditarPeticion(peticion) ? 'Solo el usuario asignado puede cambiar la importancia' : 'Cambiar importancia'"
                    >
                      <i class="fas fa-star"></i> Cambiar Importancia
                    </button>

                    <button
                      class="dropdown-item"
                      :class="{ 'disabled': !puedeEditarPeticion(peticion) }"
                      :disabled="!puedeEditarPeticion(peticion)"
                      @click="puedeEditarPeticion(peticion) && (gestionarDepartamentos(peticion), cerrarMenuAcciones())"
                      :title="!puedeEditarPeticion(peticion) ? 'Solo el usuario asignado puede gestionar departamentos' : 'Gestionar departamentos'"
                    >
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
                  <!-- ✅ NUEVO: Botón simple para ver departamentos -->
                  <div v-if="!peticion.departamentos || peticion.departamentos.length === 0" class="sin-departamentos">
                    <span class="departamentos-resumen sin-asignar">Sin asignar</span>
                  </div>
                  <div v-else class="departamentos-con-boton">
                    <button
                      @click="abrirModalDepartamentosEstados(peticion)"
                      class="btn-ver-departamentos"
                      :title="`Ver estados de ${peticion.departamentos.length} departamento(s)`"
                    >
                      <i class="fas fa-building"></i>
                      {{ peticion.departamentos.length }} Dept.
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>

                <div class="peticion-info prioridad-semaforo">
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

        console.log('🔍 DEBUG - Respuesta del backend:', response.data);

        const peticionesRaw = response.data.records || [];

        console.log('📊 Total peticiones recibidas:', peticionesRaw.length);

        // ✅ NUEVO: Filtrar por división administrativa del usuario
        const divisionUsuario = obtenerDivisionUsuario();
        console.log('🏢 División del usuario logueado:', divisionUsuario);

        let peticionesFiltradas_temp = peticionesRaw;

        if (divisionUsuario) {
          peticionesFiltradas_temp = peticionesRaw.filter(pet => {
            const divisionPeticion = parseInt(pet.division_id);
            const divisionUser = parseInt(divisionUsuario);

            // Si la petición no tiene división, no mostrarla (o cambiar lógica según necesidad)
            if (!pet.division_id) {
              console.log(`⚠️ Petición ${pet.folio} sin división asignada`);
              return false;
            }

            const coincide = divisionPeticion === divisionUser;
            if (!coincide) {
              console.log(`❌ Petición ${pet.folio} excluida: división ${divisionPeticion} != ${divisionUser}`);
            }
            return coincide;
          });

          console.log(`✅ Peticiones filtradas por división ${divisionUsuario}:`, peticionesFiltradas_temp.length);
        } else {
          console.warn('⚠️ Usuario sin división asignada - mostrando todas las peticiones');
        }

        // Asegurar que todas las peticiones tengan array de departamentos
        peticiones.value = peticionesFiltradas_temp.map(pet => ({
          ...pet,
          departamentos: pet.departamentos || []
        }));

        console.log('✅ Peticiones procesadas:', peticiones.value.length);

        // Limpiar cache de semáforo
        limpiarCacheSemaforo();

        // Ordenamos las peticiones por prioridad
        peticiones.value = ordenarPeticionesPorPrioridad(peticiones.value);

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
                          filtros.nombre || filtros.nivelImportancia || filtros.usuario_seguimiento;
        
        if (!hayFiltros) {
          peticionesFiltradas.value = ordenarPeticionesPorPrioridad([...peticiones.value]);
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

        // Aplicamos el ordenamiento a los resultados filtrados
        peticionesFiltradas.value = ordenarPeticionesPorPrioridad(peticionesFiltradas_temp);

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
      () => [filtros.estado, filtros.departamento, filtros.nivelImportancia, filtros.usuario_seguimiento],
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

      // Forzar aplicación de filtros
      aplicarFiltros();

      // Resetear paginación a la primera página
      paginacion.paginaActual = 1;

      if (window.$toast) {
        window.$toast.info('Filtros limpiados');
      }
    };

    onMounted(async () => {
      // ✅ IMPORTANTE: Obtener usuario ANTES de cargar peticiones
      await obtenerUsuarioLogueado();

      await Promise.all([
        cargarPeticiones(),
        cargarDepartamentos()
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
      municipioUsuario,
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
</style>
