<template>
  <div class="petition-page">
    <!-- Banner principal con animación mejorada -->
    <div class="hero-banner">
      <div
        class="hero-content"
        v-motion-fade-visible-once
        :initial="{ opacity: 0, y: 100 }"
        :enter="{ opacity: 1, y: 0, transition: { duration: 1200, ease: 'easeOut' } }"
      >
        <h1>Solicitud de Peticiones Ciudadanas - Yucatán</h1>
        <p>Sistema de Peticiones del Gobierno del Estado de Yucatán</p>
      </div>
    </div>

    <div class="content-container">
      <div
        class="form-container"
        v-motion-fade-visible-once
        :initial="{ opacity: 0, scale: 0.9 }"
        :enter="{
          opacity: 1,
          scale: 1,
          transition: { duration: 1000, delay: 300, ease: 'easeOut' },
        }"
      >
        <h2>Formulario de Petición</h2>
        <!-- <p class="form-description">Complete todos los campos para enviar su petición</p> -->

        <!-- Mensaje de éxito mejorado con folio destacado -->
        <div v-if="successMessage" class="success-message" ref="successMessageRef">
          <div class="success-header">
            <font-awesome-icon icon="fa-solid fa-check-circle" class="success-icon" />
            <h3>¡Petición enviada exitosamente!</h3>
          </div>

          <div class="folio-display">
            <p class="folio-label">Su folio de seguimiento es:</p>
            <div class="folio-container">
              <h2 class="folio-number">{{ generatedFolio }}</h2>
            </div>
            <p class="folio-instructions">
              <strong>¡IMPORTANTE!</strong> Guarde este folio para dar seguimiento a su petición.
              Puede usarlo para consultar el estado de su solicitud.
            </p>
          </div>

          <!-- Información de clasificación automática -->
          <div v-if="lastClassification" class="classification-success">
            <h4>Clasificación automática asignada:</h4>
            <div class="classification-details">
              <div class="classification-item">
                <span class="label">Categoría:</span>
                <span class="value">{{ lastClassification.categoria }}</span>
              </div>
              <div class="classification-item">
                <span class="label">Dependencia:</span>
                <span class="value">{{ lastClassification.dependencia }}</span>
              </div>
              <div class="classification-item">
                <span class="label">Tipo de petición:</span>
                <span class="value">{{ lastClassification.tipo_peticion }}</span>
              </div>
            </div>
          </div>

          <!-- Información adicional -->
          <div class="next-steps">
            <h4>¿Qué sigue?</h4>
            <ul>
              <li>Recibirá una respuesta inicial en 3-5 días hábiles</li>
              <li>Su petición será revisada y asignada al departamento correspondiente</li>
              <li>Puede dar seguimiento usando su folio de seguimiento</li>
            </ul>
          </div>

          <!-- Botones de acción -->
          <div class="success-actions">
            <button class="primary-button new-petition-btn" @click="resetForm">
              <font-awesome-icon icon="fa-solid fa-plus" />
              Nueva Petición
            </button>
            <button class="secondary-button print-btn" @click="printFolio">
              <font-awesome-icon icon="fa-solid fa-print" />
              Imprimir Folio
            </button>
          </div>
        </div>

        <div v-if="errorMessage" class="error-message">
          <font-awesome-icon icon="fa-solid fa-exclamation-circle" />
          {{ errorMessage }}
        </div>

        <div v-if="!successMessage" class="petition-form">
          <!-- Nombre -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 500 } }"
          >
            <label for="nombre"
              >Nombre del solicitante <span class="required">*</span></label
            >
            <input
              type="text"
              id="nombre"
              v-model="formData.nombre"
              required
              placeholder="Ingrese el nombre completo del solicitante"
              :class="{ 'error-input': errors.nombre }"
              @blur="validateField('nombre', formData.nombre)"
            />
            <span v-if="errors.nombre" class="error-text">{{ errors.nombre }}</span>
          </div>

          <!-- Teléfono -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 550 } }"
          >
            <label for="telefono"
              >Teléfono del solicitante <span class="required">*</span></label
            >
            <input
              type="tel"
              id="telefono"
              v-model="formData.telefono"
              @input="validatePhone"
              @blur="validateField('telefono', formData.telefono)"
              placeholder="Ej. 555-123-4567"
              required
              :class="{ 'error-input': errors.telefono }"
            />
            <span v-if="errors.telefono" class="error-text">{{ errors.telefono }}</span>
          </div>

          <!-- Dirección -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 600 } }"
          >
            <label for="direccion"
              >Dirección donde sucede el problema <span class="required">*</span></label
            >
            <input
              type="text"
              id="direccion"
              v-model="formData.direccion"
              required
              :class="{ 'error-input': errors.direccion }"
              @blur="validateField('direccion', formData.direccion)"
            />
            <span v-if="errors.direccion" class="error-text">{{ errors.direccion }}</span>
          </div>

          <!-- Municipio de Yucatán - ACTUALIZADO para cargar desde API -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 650 } }"
          >
            <label for="municipio"
              ><font-awesome-icon icon="fa-solid fa-map-marker-alt" /> Municipio de Yucatán <span class="required">*</span></label
            >
            <select
              id="municipio"
              v-model="formData.municipio_id"
              required
              :class="{ 'error-input': errors.municipio }"
              @change="onMunicipioChange"
              :disabled="isLoadingMunicipios"
            >
              <option value="">
                {{ isLoadingMunicipios ? 'Cargando municipios...' : 'Seleccione un municipio' }}
              </option>
              <option
                v-for="municipio in municipiosYucatan"
                :key="municipio.Id"
                :value="municipio.Id"
              >
                {{ municipio.Municipio }}
              </option>
            </select>
            <span v-if="errors.municipio" class="error-text">{{ errors.municipio }}</span>
            <span v-if="municipioError" class="error-text">{{ municipioError }}</span>
          </div>

          <!-- Localidad -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 700 } }"
          >
            <label for="localidad">Localidad/Colonia <span class="required">*</span></label>
            <input
              type="text"
              id="localidad"
              v-model="formData.localidad"
              required
              placeholder="Ej. Centro, Col. García Ginerés, etc."
              :class="{ 'error-input': errors.localidad }"
              @blur="validateField('localidad', formData.localidad)"
            />
            <span v-if="errors.localidad" class="error-text">{{ errors.localidad }}</span>
            <div class="help-text">
              Especifique la colonia o localidad dentro del municipio seleccionado
            </div>
          </div>

          <!-- Nivel de importancia -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 800 } }"
          >
            <label for="nivel_importancia"
              >Nivel de Importancia <span class="required">*</span></label
            >
            <select
              id="nivel_importancia"
              v-model="formData.nivel_importancia"
              required
              :class="{ 'error-input': errors.nivel_importancia }"
              @change="validateField('nivel_importancia', formData.nivel_importancia)"
            >
              <option value="">Seleccione un nivel</option>
              <option value="1">🔴 Crítico (1) - Requiere atención inmediata</option>
              <option value="2">🟠 Alto (2) - Problema urgente</option>
              <option value="3">🟡 Medio (3) - Problema importante</option>
              <option value="4">🟢 Bajo (4) - Problema menor</option>
            </select>
            <span v-if="errors.nivel_importancia" class="error-text">{{
              errors.nivel_importancia
            }}</span>
            <div class="help-text">
              Seleccione el nivel que mejor describa la urgencia de su petición
            </div>
          </div>

          <!-- Descripción -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 900 } }"
          >
            <label for="descripcion"
              >Descripción del problema <span class="required">*</span></label
            >
            <textarea
              id="descripcion"
              v-model="formData.descripcion"
              rows="5"
              required
              :class="{ 'error-input': errors.descripcion }"
              placeholder="Describa detalladamente su petición o problema (mínimo 10 caracteres)..."
              maxlength="1000"
              @input="onDescriptionChange"
              @blur="validateField('descripcion', formData.descripcion)"
            ></textarea>
            <div class="character-count">
              {{ formData.descripcion.length }}/1000 caracteres
            </div>
            <span v-if="errors.descripcion" class="error-text">{{
              errors.descripcion
            }}</span>

            <!-- Botón de clasificación automática -->
            <div class="classification-controls" v-if="canClassify && !classification">
              <button
                type="button"
                @click="testClassification"
                :disabled="isClassifying"
                class="classify-button"
              >
                <font-awesome-icon icon="fa-solid fa-wand-magic-sparkles" />
                {{ isClassifying ? "Clasificando..." : "Clasificar Automáticamente" }}
              </button>
            </div>
          </div>

          <!-- Clasificación automática con selección -->
          <div
            v-if="classification && classification.length"
            class="classification-section"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, y: 20 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 600 } }"
          >
            <h3>
              <font-awesome-icon icon="fa-solid fa-robot" />
              Clasificación Automática
            </h3>
            <p class="classification-help">
              Seleccione la clasificación que mejor se adapte a su problema:
            </p>

            <div
              v-for="(sugerencia, index) in classification"
              :key="index"
              class="classification-card"
              :class="{ 'selected': selectedClassification === sugerencia }"
              @click="selectClassification(sugerencia)"
            >
              <div class="classification-header">
                <h4>Opción {{ index + 1 }}</h4>
                <div class="classification-score">
                  Confianza: {{ (sugerencia.puntuacion * 100).toFixed(1) }}%
                </div>
              </div>

              <div class="classification-content">
                <div class="classification-item">
                  <strong>Dependencia:</strong>
                  <span class="classification-value">{{ sugerencia.dependencia }}</span>
                </div>

                <div
                  v-if="sugerencia.palabras_encontradas && sugerencia.palabras_encontradas.length"
                  class="classification-item"
                >
                  <strong>Palabras clave:</strong>
                  <div class="keywords">
                    <span
                      v-for="palabra in sugerencia.palabras_encontradas"
                      :key="palabra"
                      class="keyword-tag"
                    >
                      {{ palabra }}
                    </span>
                  </div>
                </div>
              </div>

              <div v-if="selectedClassification === sugerencia" class="selected-indicator">
                <font-awesome-icon icon="fa-solid fa-check-circle" />
                Clasificación seleccionada
              </div>
            </div>

            <div class="classification-actions">
              <button
                type="button"
                @click="reclassifyDescription"
                :disabled="isClassifying"
                class="reclassify-btn"
              >
                <font-awesome-icon icon="fa-solid fa-sync-alt" />
                {{ isClassifying ? "Reclasificando..." : "Reclasificar" }}
              </button>

              <button
                v-if="selectedClassification"
                type="button"
                @click="selectedClassification = null"
                class="clear-selection-btn"
              >
                <font-awesome-icon icon="fa-solid fa-times" />
                Limpiar selección
              </button>

              <button
                type="button"
                @click="showManualClassification = !showManualClassification"
                class="manual-classify-btn"
              >
                <font-awesome-icon icon="fa-solid fa-hand-pointer" />
                {{ showManualClassification ? 'Ocultar clasificación manual' : 'Clasificar manualmente' }}
              </button>
            </div>
          </div>

          <!-- Clasificación Manual -->
          <div
            v-if="showManualClassification"
            class="manual-classification-section"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, y: 20 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 600 } }"
          >
            <h3>
              <font-awesome-icon icon="fa-solid fa-hand-pointer" />
              Clasificación Manual
            </h3>
            <p class="classification-help">
              Si ninguna de las opciones automáticas es correcta, seleccione manualmente:
            </p>

            <div class="manual-selectors">
              <div class="form-group">
                <label for="manual-dependencia">Dependencia</label>
                <select
                  id="manual-dependencia"
                  v-model="manualClassification.dependencia"
                  class="manual-select"
                >
                  <option value="">Seleccione una dependencia</option>
                  <option
                    v-for="dep in allDependencias"
                    :key="dep"
                    :value="dep"
                  >
                    {{ dep }}
                  </option>
                </select>
              </div>
            </div>

            <div class="manual-classification-actions">
              <button
                type="button"
                @click="applyManualClassification"
                :disabled="!manualClassification.dependencia"
                class="apply-manual-btn"
              >
                <font-awesome-icon icon="fa-solid fa-check" />
                Aplicar clasificación manual
              </button>

              <button
                type="button"
                @click="clearManualClassification"
                class="clear-manual-btn"
              >
                <font-awesome-icon icon="fa-solid fa-times" />
                Limpiar
              </button>
            </div>

            <div v-if="manualClassificationApplied" class="manual-applied-indicator">
              <font-awesome-icon icon="fa-solid fa-check-circle" />
              Clasificación manual aplicada
            </div>
          </div>

          <!-- Imagen de la petición (Opcional) -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 950 } }"
          >
            <label for="imagen">
              <font-awesome-icon icon="fa-solid fa-image" />
              Imagen de la petición
              <span class="optional-badge">Opcional</span>
            </label>
            <div class="image-upload-container">
              <input
                type="file"
                id="imagen"
                ref="imageInput"
                accept="image/*"
                @change="handleImageChange"
                class="image-input"
              />
              <label for="imagen" class="image-upload-label">
                <font-awesome-icon icon="fa-solid fa-cloud-upload-alt" class="upload-icon" />
                <span v-if="!selectedImage">Haga clic para seleccionar una imagen</span>
                <span v-else>Cambiar imagen</span>
              </label>

              <div v-if="imagePreview" class="image-preview-container">
                <img :src="imagePreview" alt="Vista previa" class="image-preview" />
                <button type="button" @click="removeImage" class="remove-image-btn">
                  <font-awesome-icon icon="fa-solid fa-times-circle" />
                  Eliminar imagen
                </button>
                <div class="image-info">
                  <font-awesome-icon icon="fa-solid fa-file-image" />
                  {{ selectedImage.name }} ({{ formatFileSize(selectedImage.size) }})
                </div>
              </div>
            </div>
            <div class="help-text">
              <font-awesome-icon icon="fa-solid fa-info-circle" />
              Puede adjuntar una foto que ayude a ilustrar su petición (máximo 5MB)
            </div>
          </div>

          <!-- Red social -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 1000 } }"
          >
            <label for="red_social">Red social del solicitante</label>
            <input
              type="text"
              id="red_social"
              v-model="formData.red_social"
              placeholder="Ej. @usuario"
            />
            <div class="help-text">
              Opcional: Proporcione su usuario de redes sociales para seguimiento
            </div>
          </div>

          <!-- Botones -->
          <div
            class="form-actions"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, y: 30 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 1100 } }"
          >
            <button
              type="button"
              class="primary-button pulse-animation"
              :disabled="!canSubmit || isLoading"
              @click="submitForm"
            >
              <font-awesome-icon icon="fa-solid fa-paper-plane" />
              <span v-if="!isLoading">Enviar Petición</span>
              <span v-else>Enviando...</span>
            </button>
            <button type="button" class="secondary-button" @click="resetForm">
              <font-awesome-icon icon="fa-solid fa-times" /> Cancelar
            </button>
          </div>
        </div>
      </div>

      <!-- Info extra (tarjetas) -->
      <div class="info-container">
        <div
          class="info-card"
          v-motion-fade-visible-once
          :initial="{ opacity: 0, scale: 0.8, rotateY: '30deg' }"
          :enter="{
            opacity: 1,
            scale: 1,
            rotateY: '0deg',
            transition: { duration: 1000, delay: 400 },
          }"
        >
          <div class="info-icon">
            <font-awesome-icon icon="fa-solid fa-question-circle" class="icon-animation" />
          </div>
          <h3>¿Qué pasará con mi petición?</h3>
          <p>
            Su petición será revisada por el Gobierno del Estado de Yucatán y se le asignará un folio único
            para seguimiento.
          </p>
        </div>

        <div
          class="info-card"
          v-motion-fade-visible-once
          :initial="{ opacity: 0, scale: 0.8, rotateY: '30deg' }"
          :enter="{
            opacity: 1,
            scale: 1,
            rotateY: '0deg',
            transition: { duration: 1000, delay: 600 },
          }"
        >
          <div class="info-icon">
            <font-awesome-icon icon="fa-solid fa-clock" class="icon-animation" />
          </div>
          <h3>Tiempo de respuesta</h3>
          <p>
            El tiempo estimado para recibir una primera respuesta es de 3 a 5 días
            hábiles.
          </p>
        </div>

        <div
          class="info-card"
          v-motion-fade-visible-once
          :initial="{ opacity: 0, scale: 0.8, rotateY: '30deg' }"
          :enter="{
            opacity: 1,
            scale: 1,
            rotateY: '0deg',
            transition: { duration: 1000, delay: 800 },
          }"
        >
          <div class="info-icon">
            <font-awesome-icon icon="fa-solid fa-phone" class="icon-animation" />
          </div>
          <h3>Contacto directo</h3>
          <p>
            Para casos urgentes en Yucatán, puede comunicarse al número de atención ciudadana:
            800-YUCATAN
          </p>
        </div>

        <div
          class="info-card"
          v-motion-fade-visible-once
          :initial="{ opacity: 0, scale: 0.8, rotateY: '30deg' }"
          :enter="{
            opacity: 1,
            scale: 1,
            rotateY: '0deg',
            transition: { duration: 1000, delay: 1000 },
          }"
        >
          <div class="info-icon">
            <font-awesome-icon
              icon="fa-solid fa-exclamation-triangle"
              class="icon-animation"
            />
          </div>
          <h3>Niveles de Importancia</h3>
          <p>
            Seleccione correctamente el nivel de importancia para garantizar que su
            petición reciba la atención adecuada.
          </p>
        </div>

        <div
          class="info-card"
          v-motion-fade-visible-once
          :initial="{ opacity: 0, scale: 0.8, rotateY: '30deg' }"
          :enter="{
            opacity: 1,
            scale: 1,
            rotateY: '0deg',
            transition: { duration: 1000, delay: 1200 },
          }"
        >
          <div class="info-icon">
            <font-awesome-icon icon="fa-solid fa-lightbulb" class="icon-animation" />
          </div>
          <h3>Clasificación Inteligente</h3>
          <p>
            Nuestro sistema de IA analiza su petición y la clasifica automáticamente para
            dirigirla a la dependencia correcta.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, nextTick } from "vue";

export default {
  name: "PetitionPage",
  setup() {
    // -----------------------
    // State
    // -----------------------
    const formData = ref({
      nombre: "",
      telefono: "",
      direccion: "",
      municipio_id: "",  // ✅ Cambiado a municipio_id
      localidad: "",
      nivel_importancia: "",
      descripcion: "",
      red_social: "",
    });

    // ✅ Municipios cargados desde la API
    const municipiosYucatan = ref([]);
    const isLoadingMunicipios = ref(false);
    const municipioError = ref("");

    const errors = ref({});
    const successMessage = ref("");
    const errorMessage = ref("");
    const generatedFolio = ref("");
    const isLoading = ref(false);

    const classification = ref(null);
    const isClassifying = ref(false);
    const lastClassification = ref(null);
    const selectedClassification = ref(null);
    const selectedImage = ref(null);
    const imagePreview = ref(null);

    // Manual classification
    const showManualClassification = ref(false);
    const manualClassification = ref({
      dependencia: ''
    });
    const manualClassificationApplied = ref(false);
    const allDependencias = ref([]);
    const dependenciasData = ref(null);

    // APIs
    const API_BASE = import.meta.env.VITE_API_URL?.replace('/api', '') || window.location.origin;
    const PETITION_API = `${import.meta.env.VITE_API_URL || '/api'}/peticiones.php`;
    const DIVISION_API = `${import.meta.env.VITE_API_URL || '/api'}/division.php`;

    // -----------------------
    // ✅ NUEVO: Cargar municipios desde API
    // -----------------------
    const loadMunicipios = async () => {
      try {
        isLoadingMunicipios.value = true;
        municipioError.value = "";

        const response = await fetch(DIVISION_API, {
          method: "GET",
          headers: {
            "Accept": "application/json",
          },
        });

        if (!response.ok) {
          throw new Error(`Error ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success && Array.isArray(data.divisions)) {
          municipiosYucatan.value = data.divisions;
          console.log(`✅ ${data.count} municipios cargados desde la base de datos`);
        } else {
          throw new Error(data.message || "Error al cargar municipios");
        }
      } catch (error) {
        console.error("❌ Error cargando municipios:", error);
        municipioError.value = "No se pudieron cargar los municipios. Intente recargar la página.";
        municipiosYucatan.value = [];
      } finally {
        isLoadingMunicipios.value = false;
      }
    };

    // ✅ Handler para cambio de municipio
    const onMunicipioChange = () => {
      validateField('municipio', formData.value.municipio_id);
    };

    // ✅ Obtener nombre del municipio seleccionado
    const getSelectedMunicipioName = () => {
      const selected = municipiosYucatan.value.find(
        m => m.Id === formData.value.municipio_id
      );
      return selected ? selected.Municipio : "";
    };

    // -----------------------
    // Computed
    // -----------------------
    const isManualClassificationComplete = computed(() => {
      return manualClassification.value.dependencia !== '';
    });

    const canSubmit = computed(() => {
      return (
        Object.keys(errors.value).length === 0 &&
        formData.value.nombre.length >= 2 &&
        formData.value.telefono.length >= 10 &&
        formData.value.direccion.length >= 5 &&
        formData.value.municipio_id !== "" &&  // ✅ Cambiado
        formData.value.localidad.length >= 2 &&
        formData.value.nivel_importancia !== "" &&
        formData.value.descripcion.length >= 10 &&
        formData.value.descripcion.length <= 1000
      );
    });

    const canClassify = computed(() => formData.value.descripcion.length >= 10);

    // -----------------------
    // Validaciones - ACTUALIZADO
    // -----------------------
    const validateField = (field, value) => {
      errors.value = { ...errors.value };
      switch (field) {
        case "nombre":
          if (!value || value.length < 2)
            errors.value.nombre = "El nombre debe tener al menos 2 caracteres";
          else if (value.length > 100)
            errors.value.nombre = "El nombre no puede exceder 100 caracteres";
          else delete errors.value.nombre;
          break;
        case "telefono":
          if (!value) errors.value.telefono = "El teléfono es requerido";
          else if (value.length < 10 || value.length > 15)
            errors.value.telefono =
              "El teléfono debe tener entre 10 y 15 caracteres";
          else delete errors.value.telefono;
          break;
        case "direccion":
          if (!value || value.length < 5)
            errors.value.direccion =
              "La dirección debe tener al menos 5 caracteres";
          else delete errors.value.direccion;
          break;
        case "municipio":
          if (!value)
            errors.value.municipio = "Debe seleccionar un municipio";
          else delete errors.value.municipio;
          break;
        case "localidad":
          if (!value || value.length < 2)
            errors.value.localidad =
              "La localidad debe tener al menos 2 caracteres";
          else delete errors.value.localidad;
          break;
        case "nivel_importancia":
          if (!value)
            errors.value.nivel_importancia =
              "Debe seleccionar un nivel de importancia";
          else delete errors.value.nivel_importancia;
          break;
        case "descripcion":
          if (!value || value.length < 10)
            errors.value.descripcion =
              "La descripción debe tener al menos 10 caracteres";
          else if (value.length > 1000)
            errors.value.descripcion =
              "La descripción no puede exceder 1000 caracteres";
          else delete errors.value.descripcion;
          break;
      }
    };

    // -----------------------
    // Clasificación - RESTAURAR MÉTODOS COMPLETOS
    // -----------------------
    let debounceTimeout = null;

    const classifyDescription = async (texto) => {
      if (!texto || texto.length < 10) return;

      try {
        isClassifying.value = true;
        errorMessage.value = "";

        const response = await fetch(`${API_BASE}/py/clasificacion/clasificar`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({ texto }),
        });

        if (!response.ok) {
          const errorData = await response.json().catch(() => ({}));
          throw new Error(
            errorData.detail || `Error ${response.status}: ${response.statusText}`
          );
        }

        const data = await response.json();
        classification.value = Array.isArray(data.resultado)
          ? data.resultado.map((item) => ({ ...item, texto_original: texto }))
          : null;
      } catch (error) {
        errorMessage.value = `Error al clasificar: ${error.message}`;
        if (error.message.includes("fetch")) {
          errorMessage.value =
            "No se pudo conectar con el servidor de clasificación. Verifique su conexión.";
        }
      } finally {
        isClassifying.value = false;
      }
    };

    const onDescriptionChange = () => {
      validateField("descripcion", formData.value.descripcion);

      // Limpiar clasificación si el texto cambió significativamente
      if (
        classification.value &&
        Array.isArray(classification.value) &&
        classification.value.length > 0 &&
        classification.value[0].texto_original &&
        Math.abs(
          formData.value.descripcion.length -
            classification.value[0].texto_original.length
        ) > 10
      ) {
        classification.value = null;
        selectedClassification.value = null;
      }

      // Debounce para clasificación automática
      if (debounceTimeout) clearTimeout(debounceTimeout);

      if (formData.value.descripcion.length >= 20) {
        debounceTimeout = setTimeout(() => {
          classifyDescription(formData.value.descripcion);
        }, 1500);
      }
    };

    const testClassification = () => {
      if (formData.value.descripcion.length >= 10) {
        classifyDescription(formData.value.descripcion);
      }
    };

    const reclassifyDescription = () => {
      classification.value = null;
      selectedClassification.value = null;
      classifyDescription(formData.value.descripcion);
    };

    const selectClassification = (sugerencia) => {
      selectedClassification.value = sugerencia;
      console.log("Clasificación seleccionada:", sugerencia);
    };

    // -----------------------
    // Submit
    // -----------------------
    const submitForm = async () => {
      try {
        isLoading.value = true;
        errorMessage.value = "";
        successMessage.value = "";
        lastClassification.value = null;

        // Validar todos los campos
        validateField('nombre', formData.value.nombre);
        validateField('telefono', formData.value.telefono);
        validateField('direccion', formData.value.direccion);
        validateField('municipio', formData.value.municipio_id);
        validateField('localidad', formData.value.localidad);
        validateField('nivel_importancia', formData.value.nivel_importancia);
        validateField('descripcion', formData.value.descripcion);

        if (Object.keys(errors.value).length > 0) {
          throw new Error("Corrige los errores en el formulario antes de enviar.");
        }

        // ✅ Payload actualizado - incluye municipio_id
        const petitionData = {
          nombre: formData.value.nombre,
          telefono: formData.value.telefono,
          direccion: formData.value.direccion,
          municipio_id: formData.value.municipio_id,  // ✅ ID del municipio
          municipio: getSelectedMunicipioName(),       // ✅ Nombre para referencia
          localidad: formData.value.localidad,
          descripcion: formData.value.descripcion,
          red_social: formData.value.red_social || null,
          NivelImportancia: parseInt(formData.value.nivel_importancia),
          estado: "Sin revisar"
        };

        // ✅ Enviar TODAS las sugerencias de IA para guardarlas
        if (classification.value && Array.isArray(classification.value) && classification.value.length > 0) {
          petitionData.sugerencias_ia = classification.value.map(sug => ({
            dependencia: sug.dependencia,
            puntuacion: sug.puntuacion,
            palabras_encontradas: sug.palabras_encontradas || []
          }));
        }

        // ✅ Marcar la clasificación seleccionada
        if (selectedClassification.value) {
          petitionData.clasificacion_seleccionada = {
            dependencia: selectedClassification.value.dependencia,
            puntuacion: selectedClassification.value.puntuacion || 1.0,
            manual: selectedClassification.value.manual || false
          };
        }

        console.log("📤 Enviando petición:", petitionData);

        const response = await fetch(PETITION_API, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(petitionData),
        });

        const responseData = await response.json().catch(() => ({}));
        console.log("📥 Respuesta del servidor:", responseData);

        if (!response.ok) {
          throw new Error(responseData.message || `Error ${response.status}`);
        }

        if (responseData.success) {
          generatedFolio.value = responseData.folio || "FOLIO-ERROR";
          lastClassification.value = selectedClassification.value;
          successMessage.value = "¡Petición enviada exitosamente!";

          console.log("✅ Petición guardada. Folio:", generatedFolio.value, "Division ID:", responseData.division_id);
          await scrollToSuccessMessage();
        } else {
          throw new Error(responseData.message || "Error desconocido al guardar");
        }
      } catch (error) {
        console.error("❌ Error al enviar formulario:", error);
        errorMessage.value = error.message || "Ocurrió un error inesperado";
        generatedFolio.value = "";
        await nextTick();
        const errorElement = document.querySelector(".error-message");
        if (errorElement) {
          errorElement.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      } finally {
        isLoading.value = false;
      }
    };

    // -----------------------
    // Reset Form - ACTUALIZADO
    // -----------------------
    const resetForm = () => {
      formData.value = {
        nombre: "",
        telefono: "",
        direccion: "",
        municipio_id: "",  // ✅ Cambiado
        localidad: "",
        nivel_importancia: "",
        descripcion: "",
        red_social: "",
      };

      errors.value = {};
      classification.value = null;
      selectedClassification.value = null;
      lastClassification.value = null;
      successMessage.value = "";
      errorMessage.value = "";
      generatedFolio.value = "";
      selectedImage.value = null;
      imagePreview.value = null;

      nextTick(() => {
        const formElement = document.querySelector(".form-container");
        if (formElement) {
          formElement.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    };

    // -----------------------
    // Utilidades UI
    // -----------------------
    // -----------------------
    // Utilidades UI - MOVER ANTES DE submitForm
    // -----------------------
    const scrollToSuccessMessage = async () => {
      await nextTick();
      const successElement = document.querySelector(".success-message");
      if (successElement) {
        successElement.scrollIntoView({
          behavior: "smooth",
          block: "start",
          inline: "nearest",
        });
        setTimeout(() => {
          const rect = successElement.getBoundingClientRect();
          const scrollTop =
            window.pageYOffset +
            rect.top -
            window.innerHeight / 2 +
            successElement.offsetHeight / 2;
          window.scrollTo({ top: scrollTop, behavior: "smooth" });
        }, 100);
      }
    };

    // -----------------------
    // Manual Classification Methods
    // -----------------------
    const loadDependenciasData = async () => {
      try {
        const response = await fetch('/backend/dependecias_yucatan.json');
        const data = await response.json();
        dependenciasData.value = data.sistema_clasificacion_dependencias;

        // Cargar todas las dependencias de todas las categorías de Yucatán
        const dependenciasSet = new Set();
        Object.values(data.sistema_clasificacion_dependencias.catalogo_dependencias).forEach(dep => {
          dependenciasSet.add(dep.nombre);
        });
        allDependencias.value = Array.from(dependenciasSet).sort();
      } catch (error) {
        console.error('Error cargando dependencias:', error);
      }
    };

    const applyManualClassification = () => {
      if (manualClassification.value.dependencia) {
        selectedClassification.value = {
          dependencia: manualClassification.value.dependencia,
          puntuacion: 1.0,
          manual: true
        };
        manualClassificationApplied.value = true;
        showManualClassification.value = false;
      }
    };

    const clearManualClassification = () => {
      manualClassification.value = {
        dependencia: ''
      };
      manualClassificationApplied.value = false;
    };

    // -----------------------
    // onMounted - ACTUALIZADO
    // -----------------------
    onMounted(async () => {
      // ✅ Cargar municipios al iniciar
      await loadMunicipios();

      // Cargar datos de dependencias para clasificación manual
      await loadDependenciasData();

      // Checks opcionales de conectividad
      try {
        await fetch(`${API_BASE}/py/clasificacion/categorias`);
      } catch {
        console.warn("API de clasificación no disponible");
      }
    });

    // -----------------------
    // Expose to template
    // -----------------------
    return {
      // Datos
      formData,
      errors,
      successMessage,
      errorMessage,
      generatedFolio,
      isLoading,
      classification,
      isClassifying,
      lastClassification,
      selectedClassification,
      municipiosYucatan,
      isLoadingMunicipios,
      municipioError,
      selectedImage,
      imagePreview,

      // Computed
      canSubmit,
      canClassify,

      // Métodos
      validateField,
      validatePhone: (event) => {
        const value = event.target.value;
        const cleanValue = value.replace(/[^\d\-\s]/g, "");
        formData.value.telefono = cleanValue;
        validateField("telefono", cleanValue);
      },
      handleImageChange: (event) => {
        const file = event.target.files[0];
        if (file) {
          // Validar tamaño (máximo 5MB)
          if (file.size > 5 * 1024 * 1024) {
            errorMessage.value = "La imagen no debe superar los 5MB";
            event.target.value = "";
            return;
          }

          // Validar tipo de archivo
          if (!file.type.startsWith("image/")) {
            errorMessage.value = "Solo se permiten archivos de imagen";
            event.target.value = "";
            return;
          }

          selectedImage.value = file;

          // Crear preview
          const reader = new FileReader();
          reader.onload = (e) => {
            imagePreview.value = e.target.result;
          };
          reader.readAsDataURL(file);

          errorMessage.value = "";
        }
      },
      removeImage: () => {
        selectedImage.value = null;
        imagePreview.value = null;
        const input = document.getElementById("imagen");
        if (input) input.value = "";
      },
      formatFileSize: (bytes) => {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + " " + sizes[i];
      },
      onMunicipioChange,
      onDescriptionChange,
      testClassification,
      reclassifyDescription,
      selectClassification,
      submitForm,
      resetForm,
      scrollToSuccessMessage, // ✅ Exportar la función definida arriba
      // Manual classification
      showManualClassification,
      manualClassification,
      manualClassificationApplied,
      allDependencias,
      isManualClassificationComplete,
      applyManualClassification,
      clearManualClassification,
      copyToClipboard: async (text) => {
        try {
          await navigator.clipboard.writeText(text);
        } catch (err) {
          console.error("Error al copiar:", err);
        }
      },
      printFolio: () => {
        const printContent = `
          <div style="text-align: center; padding: 20px; font-family: Arial, sans-serif;">
            <h2>Petición Ciudadana</h2>
            <p><strong>Folio de seguimiento:</strong></p>
            <h1 style="font-size: 24px; border: 2px solid #000; padding: 10px; display: inline-block;">
              ${generatedFolio.value}
            </h1>
            <p style="margin-top: 20px;">Guarde este folio para dar seguimiento a su petición.</p>
            <p><small>Fecha: ${new Date().toLocaleString()}</small></p>
          </div>
        `;
        const printWindow = window.open("", "_blank");
        printWindow.document.write(`<html><head><title>Folio</title></head><body>${printContent}</body></html>`);
        printWindow.document.close();
        printWindow.print();
      },
    };
  },
};
</script>

<style src="@/assets/css/PetionPage.css"></style>
