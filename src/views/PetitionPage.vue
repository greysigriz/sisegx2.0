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
        <h1>Solicitud de Peticiones Ciudadanas</h1>
        <p>Envía tu petición al gobierno y recibirás seguimiento de tu caso</p>
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
        <p class="form-description">Complete todos los campos para enviar su petición</p>

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
              <button 
                type="button" 
                class="copy-folio-btn" 
                @click="copyToClipboard(generatedFolio)"
                title="Copiar folio"
              >
                <font-awesome-icon icon="fa-solid fa-copy" />
              </button>
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

        <!-- Información del usuario -->
        <div
          v-if="userData && !successMessage"
          class="user-info-section"
          v-motion-fade-visible-once
          :initial="{ opacity: 0, x: -30 }"
          :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 400 } }"
        >
          <h3>Información del Usuario</h3>
          <div class="user-info-card">
            <div class="user-info-item">
              <label>Usuario registrado:</label>
              <span>{{ userFullName }}</span>
            </div>
            <div class="user-info-item">
              <label>Unidad:</label>
              <span>{{ unidadNombre || 'Sin unidad asignada' }}</span>
            </div>
          </div>
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

          <!-- Email -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 550 } }"
          >
            <label for="email"
              >Correo electrónico <span class="required">*</span></label
            >
            <input
              type="email"
              id="email"
              v-model="formData.email"
              required
              placeholder="ejemplo@correo.com"
              :class="{ 'error-input': errors.email }"
              @blur="validateField('email', formData.email)"
            />
            <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
          </div>

          <!-- Teléfono -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 600 } }"
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
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 700 } }"
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

          <!-- Localidad -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 800 } }"
          >
            <label for="localidad">Localidad <span class="required">*</span></label>
            <input
              type="text"
              id="localidad"
              v-model="formData.localidad"
              required
              :class="{ 'error-input': errors.localidad }"
              @blur="validateField('localidad', formData.localidad)"
            />
            <span v-if="errors.localidad" class="error-text">{{ errors.localidad }}</span>
          </div>

          <!-- Nivel de importancia -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 900 } }"
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
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 1000 } }"
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
                <font-awesome-icon icon="fa-solid fa-magic" />
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
                  <strong>Categoría:</strong>
                  <span class="classification-value">{{ sugerencia.categoria }}</span>
                </div>

                <div class="classification-item">
                  <strong>Dependencia:</strong>
                  <span class="classification-value">{{ sugerencia.dependencia }}</span>
                </div>

                <div class="classification-item">
                  <strong>Tipo de petición:</strong>
                  <span class="classification-value">{{ sugerencia.tipo_peticion }}</span>
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
                <font-awesome-icon icon="fa-solid fa-refresh" />
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
            </div>
          </div>

          <!-- Red social -->
          <div
            class="form-group"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, x: -30 }"
            :enter="{ opacity: 1, x: 0, transition: { duration: 800, delay: 1100 } }"
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
            :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 1200 } }"
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
            <font-awesome-icon icon="fa-solid fa-info-circle" class="icon-animation" />
          </div>
          <h3>¿Qué pasará con mi petición?</h3>
          <p>
            Su petición será revisada por nuestro equipo y se le asignará un folio único
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
            <font-awesome-icon icon="fa-solid fa-phone-alt" class="icon-animation" />
          </div>
          <h3>Contacto directo</h3>
          <p>
            Para casos urgentes, puede comunicarse al número de atención ciudadana:
            800-123-4567
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
            <font-awesome-icon icon="fa-solid fa-robot" class="icon-animation" />
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
import { ref, computed, watch, onMounted, nextTick } from "vue";

export default {
  name: "PetitionPage",
  setup() {
    // -----------------------
    // Utils
    // -----------------------
    const toIntOrNull = (v) => {
      const n = Number(v);
      return Number.isFinite(n) ? n : null;
    };

    const safeString = (v) => (typeof v === "string" ? v : v == null ? "" : String(v));

    // -----------------------
    // State
    // -----------------------
    const formData = ref({
      nombre: "",
      email: "",
      telefono: "",
      direccion: "",
      localidad: "",
      nivel_importancia: "",
      descripcion: "",
      red_social: "",
      division_id: null, // <- se poblará con IdDivisionAdm del usuario
    });

    const errors = ref({});
    const showSuccess = ref(false);         // <- booleano claro para la UI de éxito
    const successMessage = ref("");
    const errorMessage = ref("");
    const generatedFolio = ref("");
    const isLoading = ref(false);

    const classification = ref(null);
    const isClassifying = ref(false);
    const lastClassification = ref(null);
    const selectedClassification = ref(null);

    const unidadNombre = ref("");

    // Datos de usuario
    const userData = ref(null);
    const isUserDataLoaded = ref(false);

    // APIs
    const API_BASE = "http://127.0.0.1:8000";
    const PETITION_API = "http://127.0.0.1/SISE/api/peticiones.php";

    // -----------------------
    // Computed
    // -----------------------
    const userFullName = computed(() => {
      if (!userData.value) return "Usuario no identificado";
      const nombre = safeString(userData.value.Nombre);
      const apellidoP = safeString(userData.value.ApellidoP);
      const apellidoM = safeString(userData.value.ApellidoM);
      const full = `${nombre} ${apellidoP} ${apellidoM}`.trim();
      return full || "Usuario sin nombre";
    });

    const canSubmit = computed(() => {
      return (
        Object.keys(errors.value).length === 0 &&
        formData.value.nombre.length >= 2 &&
        formData.value.email.includes("@") &&
        formData.value.telefono.length >= 10 &&
        formData.value.direccion.length >= 5 &&
        formData.value.localidad.length >= 2 &&
        formData.value.nivel_importancia !== "" &&
        formData.value.descripcion.length >= 10 &&
        formData.value.descripcion.length <= 1000
      );
    });

    const canClassify = computed(() => formData.value.descripcion.length >= 10);

    // -----------------------
    // Carga de usuario
    // -----------------------
    const loadUserData = async () => {
      try {
        const storedUser = localStorage.getItem("user");
        if (!storedUser) return false;

        const parsedUser = JSON.parse(storedUser);
        let userInfo = null;

        if (parsedUser.usuario) userInfo = parsedUser.usuario;
        else if (parsedUser.user) userInfo = parsedUser.user;
        else if (parsedUser.Id || parsedUser.Nombre) userInfo = parsedUser;

        if (userInfo && (userInfo.Id || userInfo.Nombre)) {
          userData.value = {
            Id: userInfo.Id ?? null,
            Nombre: userInfo.Nombre ?? "",
            ApellidoP: userInfo.ApellidoP ?? "",
            ApellidoM: userInfo.ApellidoM ?? "",
            Usuario: userInfo.Usuario ?? "",
            IdUnidad: userInfo.IdUnidad ?? null,
            IdDivisionAdm: userInfo.IdDivisionAdm ?? null,
            Puesto: userInfo.Puesto ?? "",
            Estatus: userInfo.Estatus ?? "ACTIVO",
          };

          // Nombre de unidad (best effort)
          if (userData.value.IdUnidad) {
            try {
              const r = await fetch(
                `http://127.0.0.1/SISE/api/unidades.php?id=${userData.value.IdUnidad}`
              );
              if (r.ok) {
                const unidadData = await r.json();
                unidadNombre.value =
                  unidadData.nombre_unidad || `Unidad ${userData.value.IdUnidad}`;
              } else {
                unidadNombre.value = `Unidad ${userData.value.IdUnidad}`;
              }
            } catch {
              unidadNombre.value = `Unidad ${userData.value.IdUnidad}`;
            }
          }

          // Prellenar nombre
          const fullName = userFullName.value;
          if (
            fullName !== "Usuario no identificado" &&
            fullName !== "Usuario sin nombre"
          ) {
            formData.value.nombre = fullName;
          }

          // 🔒 Asegurar division_id correcto desde el usuario
          formData.value.division_id = toIntOrNull(userData.value.IdDivisionAdm);

          isUserDataLoaded.value = true;
          return true;
        }
        return false;
      } catch {
        return false;
      }
    };

    const loadUserDataFromAPI = async () => {
      try {
        const response = await fetch(
          "http://127.0.0.1/SISE/api/check-session.php",
          {
            method: "GET",
            credentials: "include",
            headers: {
              Accept: "application/json",
              "Content-Type": "application/json",
            },
          }
        );

        if (!response.ok) return false;

        const sessionData = await response.json();
        if (sessionData.success && sessionData.user) {
          userData.value = {
            Id: sessionData.user.Id ?? null,
            Nombre: sessionData.user.Nombre ?? "",
            ApellidoP: sessionData.user.ApellidoP ?? "",
            ApellidoM: sessionData.user.ApellidoM ?? "",
            Usuario: sessionData.user.Usuario ?? "",
            IdUnidad: sessionData.user.IdUnidad ?? null,
            IdDivisionAdm: sessionData.user.IdDivisionAdm ?? null,
            Puesto: sessionData.user.Puesto ?? "",
            Estatus: sessionData.user.Estatus ?? "ACTIVO",
          };

          // Nombre de unidad (best effort)
          if (userData.value.IdUnidad) {
            try {
              const r = await fetch(
                `http://127.0.0.1/SISE/api/unidades.php?id=${userData.value.IdUnidad}`
              );
              if (r.ok) {
                const unidadData = await r.json();
                unidadNombre.value =
                  unidadData.nombre_unidad || `Unidad ${userData.value.IdUnidad}`;
              } else {
                unidadNombre.value = `Unidad ${userData.value.IdUnidad}`;
              }
            } catch {
              unidadNombre.value = `Unidad ${userData.value.IdUnidad}`;
            }
          }

          // Prellenar nombre
          const fullName = userFullName.value;
          if (
            fullName !== "Usuario no identificado" &&
            fullName !== "Usuario sin nombre"
          ) {
            formData.value.nombre = fullName;
          }

          // 🔒 division_id desde usuario (y guardar en form)
          formData.value.division_id = toIntOrNull(userData.value.IdDivisionAdm);

          // Refrescar localStorage con formato consistente
          localStorage.setItem(
            "user",
            JSON.stringify({ usuario: sessionData.user })
          );

          isUserDataLoaded.value = true;
          return true;
        }
        return false;
      } catch {
        return false;
      }
    };

    // -----------------------
    // Validaciones
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
        case "email":
          if (!value) errors.value.email = "El email es requerido";
          else if (!/\S+@\S+\.\S+/.test(value))
            errors.value.email = "El email no tiene un formato válido";
          else delete errors.value.email;
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

    const validatePhone = (event) => {
      const value = event.target.value;
      const cleanValue = value.replace(/[^\d\-\s]/g, "");
      formData.value.telefono = cleanValue;
      validateField("telefono", cleanValue);
    };

    // -----------------------
    // Clasificación
    // -----------------------
    let debounceTimeout = null;

    const classifyDescription = async (texto) => {
      if (!texto || texto.length < 10) return;

      try {
        isClassifying.value = true;
        errorMessage.value = "";

        const response = await fetch(`${API_BASE}/api/clasificacion/clasificar`, {
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

      if (
        classification.value &&
        Array.isArray(classification.value) &&
        classification.value.length > 0 &&
        Math.abs(
          formData.value.descripcion.length -
            classification.value[0].texto_original.length
        ) > 10
      ) {
        classification.value = null;
      }

      if (debounceTimeout) clearTimeout(debounceTimeout);

      if (formData.value.descripcion.length >= 20) {
        debounceTimeout = setTimeout(() => {
          classifyDescription(formData.value.descripcion);
        }, 1000);
      }
    };

    const testClassification = () => {
      if (formData.value.descripcion.length >= 10) {
        classifyDescription(formData.value.descripcion);
      }
    };

    const reclassifyDescription = () => {
      classification.value = null;
      classifyDescription(formData.value.descripcion);
    };

    const selectClassification = (sugerencia) => {
      selectedClassification.value = sugerencia;
      console.log("Clasificación seleccionada:", sugerencia);
    };

    watch(
      () => formData.value.descripcion,
      (newVal) => {
        validateField("descripcion", newVal);
        if (
          classification.value &&
          Array.isArray(classification.value) &&
          classification.value.length > 0 &&
          Math.abs(
            newVal.length - classification.value[0].texto_original.length
          ) > 10
        ) {
          classification.value = null;
        }
      }
    );

    // -----------------------
    // Scroll helpers
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
    // Submit
    // -----------------------
    const submitForm = async () => {
      try {
        isLoading.value = true;
        errorMessage.value = "";
        showSuccess.value = false;
        successMessage.value = "";
        lastClassification.value = null;

        // Validar todos los campos salvo opcionales
        Object.entries(formData.value).forEach(([field, value]) => {
          if (field !== "red_social" && field !== "division_id") {
            validateField(field, value);
          }
        });

        if (Object.keys(errors.value).length > 0) {
          throw new Error("Corrige los errores en el formulario antes de enviar.");
        }

        // Asegurar division_id final desde userData o form
        const finalDivisionId =
          toIntOrNull(userData.value?.IdDivisionAdm) ??
          toIntOrNull(formData.value.division_id);

        // Generar folio local (fallback si el backend no devuelve folio)
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000)
          .toString()
          .padStart(3, "0");
        generatedFolio.value = `PET-${timestamp.toString().slice(-8)}-${random}`;

        // Payload para backend
        const petitionData = {
          folio: generatedFolio.value, // se actualizará si backend regresa otro
          nombre: formData.value.nombre,
          email: formData.value.email,
          telefono: formData.value.telefono,
          direccion: formData.value.direccion,
          localidad: formData.value.localidad,
          descripcion: formData.value.descripcion,
          red_social: formData.value.red_social || null,
          NivelImportancia: parseInt(formData.value.nivel_importancia, 10),
          division_id: finalDivisionId,          // 🔒 numérico o null
          usuario_id: toIntOrNull(userData.value?.Id) ?? null,
          estado: "Sin revisar",
        };

        // Adjuntar IA
        if (classification.value && Array.isArray(classification.value)) {
          petitionData.sugerencias_ia = classification.value;
        }
        if (selectedClassification.value) {
          petitionData.clasificacion_seleccionada = selectedClassification.value;
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
          // ✅ confiar en el folio del backend si lo devuelve
          if (responseData.folio) {
            generatedFolio.value = responseData.folio;
          }

          lastClassification.value = selectedClassification.value;
          showSuccess.value = true;
          successMessage.value = "¡Petición enviada exitosamente!";

          console.log("✅ Petición guardada. Folio:", generatedFolio.value);
          await scrollToSuccessMessage();
        } else {
          throw new Error(responseData.message || "Error desconocido al guardar");
        }
      } catch (error) {
        console.error("❌ Error al enviar formulario:", error);
        errorMessage.value = error.message || "Ocurrió un error inesperado";
        generatedFolio.value = ""; // limpiar folio solo en error
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
    // Utilidades UI
    // -----------------------
    const resetForm = () => {
      const preserveName = userData.value ? userFullName.value : "";
      const preserveDivision =
        toIntOrNull(userData.value?.IdDivisionAdm) ??
        toIntOrNull(formData.value.division_id) ??
        null;

      formData.value = {
        nombre: preserveName,
        email: "",
        telefono: "",
        direccion: "",
        localidad: "",
        nivel_importancia: "",
        descripcion: "",
        red_social: "",
        division_id: preserveDivision, // 🔒 preservar división
      };

      errors.value = {};
      classification.value = null;
      selectedClassification.value = null;
      lastClassification.value = null;
      showSuccess.value = false;
      successMessage.value = "";
      errorMessage.value = "";
      generatedFolio.value = "";

      nextTick(() => {
        const formElement = document.querySelector(".form-container");
        if (formElement) {
          formElement.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    };

    const copyToClipboard = async (text) => {
      try {
        await navigator.clipboard.writeText(text);
        console.log("Folio copiado al portapapeles");
      } catch (err) {
        console.error("Error al copiar:", err);
      }
    };

    const printFolio = () => {
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
      printWindow.document.write(`
        <html>
          <head><title>Folio de Petición</title></head>
          <body>${printContent}</body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    };

    // -----------------------
    // onMounted
    // -----------------------
    onMounted(async () => {
      try {
        // 1) Intentar localStorage
        let userLoaded = await loadUserData();

        // 2) Si falla, intentar API
        if (!userLoaded) {
          userLoaded = await loadUserDataFromAPI();
        }

        // 3) Si no hay usuario, trabajar en modo invitado
        if (!userLoaded) {
          userData.value = {
            Id: null,
            Nombre: "",
            ApellidoP: "",
            ApellidoM: "",
            Usuario: "invitado",
            IdUnidad: null,
            IdDivisionAdm: null,
            Puesto: "",
            Estatus: "GUEST",
          };
          isUserDataLoaded.value = false;
          // division_id se queda null
        } else {
          // asegurar que division_id esté poblado numéricamente
          formData.value.division_id = toIntOrNull(userData.value.IdDivisionAdm);
        }
      } catch (e) {
        errorMessage.value = "Error al inicializar el componente: " + (e?.message || e);
      }

      // Checks opcionales
      try {
        const classResponse = await fetch(`${API_BASE}/api/clasificacion/categorias`);
        if (classResponse.ok) console.log("✅ API de clasificación disponible");
      } catch (e) {
        console.warn("⚠️ API de clasificación no disponible:", e?.message);
      }

      try {
        const petResponse = await fetch(PETITION_API, {
          method: "GET",
          headers: { Accept: "application/json" },
        });
        if (petResponse.ok) console.log("✅ API de peticiones disponible");
      } catch (e) {
        console.warn("⚠️ API de peticiones no disponible:", e?.message);
      }
    });

    // -----------------------
    // Expose to template
    // -----------------------
    return {
      // Datos
      formData,
      errors,
      showSuccess,
      successMessage,
      errorMessage,
      generatedFolio,
      isLoading,
      classification,
      isClassifying,
      lastClassification,
      selectedClassification,
      userData,
      isUserDataLoaded,
      unidadNombre,

      // Computed
      userFullName,
      canSubmit,
      canClassify,

      // Métodos
      validateField,
      validatePhone,
      onDescriptionChange,
      testClassification,
      reclassifyDescription,
      selectClassification,
      submitForm,
      resetForm,
      scrollToSuccessMessage,
      copyToClipboard,
      printFolio,
    };
  },
};
</script>


<style src="@/assets/css/PetionPage.css"></style>