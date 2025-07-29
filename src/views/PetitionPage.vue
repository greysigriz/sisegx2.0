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

        <div v-if="successMessage" class="success-message">
          <font-awesome-icon icon="fa-solid fa-check-circle" />
          {{ successMessage }}
          <p>
            Su folio de seguimiento es: <strong>{{ generatedFolio }}</strong>
          </p>
          <div v-if="lastClassification" class="classification-success">
            <p><strong>Categoría asignada:</strong> {{ lastClassification.categoria }}</p>
            <p><strong>Dependencia:</strong> {{ lastClassification.dependencia }}</p>
            <p>
              <strong>Tipo de petición:</strong> {{ lastClassification.tipo_peticion }}
            </p>
          </div>
          <button class="primary-button new-petition-btn" @click="resetForm">
            <font-awesome-icon icon="fa-solid fa-plus" /> Nueva Petición
          </button>
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
              <span
                >{{ userData.Nombre }} {{ userData.ApellidoP }}
                {{ userData.ApellidoM }}</span
              >
            </div>
            <div class="user-info-item">
              <label>División Administrativa:</label>
              <span>{{ divisionNombre || "Cargando..." }}</span>
            </div>
          </div>
        </div>

        <form v-if="!successMessage" @submit.prevent="submitForm" class="petition-form">
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
              <option value="5">🔵 Muy Bajo (5) - Consulta o sugerencia</option>
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

          <!-- Clasificación automática - Sugerencias -->
          <div
            v-if="classification && classification.length"
            class="classification-section"
            v-motion-fade-visible-once
            :initial="{ opacity: 0, y: 20 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 600 } }"
          >
            <h3>
              <font-awesome-icon icon="fa-solid fa-robot" />
              Clasificación Automática (Sugerencias)
            </h3>

            <div
              v-for="(sugerencia, index) in classification"
              :key="index"
              class="classification-info"
            >
              <h4>Sugerencia {{ index + 1 }}</h4>

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

              <div class="classification-item">
                <strong>Puntuación:</strong>
                <span class="classification-value">{{
                  sugerencia.puntuacion.toFixed(2)
                }}</span>
              </div>

              <div
                v-if="
                  sugerencia.palabras_encontradas &&
                  sugerencia.palabras_encontradas.length
                "
                class="classification-item"
              >
                <strong>Palabras clave detectadas:</strong>
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

              <hr />
            </div>
            <button
              type="button"
              @click="reclassifyDescription"
              :disabled="isClassifying"
              class="reclassify-btn"
            >
              <font-awesome-icon icon="fa-solid fa-refresh" />
              {{ isClassifying ? "Reclasificando..." : "Reclasificar" }}
            </button>
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
              type="submit"
              class="primary-button pulse-animation"
              :disabled="!canSubmit || isLoading"
            >
              <font-awesome-icon icon="fa-solid fa-paper-plane" />
              <span v-if="!isLoading">Enviar Petición</span>
              <span v-else>Enviando...</span>
            </button>
            <button type="button" class="secondary-button" @click="resetForm">
              <font-awesome-icon icon="fa-solid fa-times" /> Cancelar
            </button>
          </div>
        </form>
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
import { ref, computed, watch, onMounted } from "vue";

export default {
  name: "PetitionPage",
  setup() {
    const formData = ref({
      nombre: "",
      telefono: "",
      direccion: "",
      localidad: "",
      nivel_importancia: "",
      descripcion: "",
      red_social: "",
      dependencia_seleccionada: "", // agregado
      tipo_peticion_seleccionado: "", // agregado
    });

    const errors = ref({});
    const successMessage = ref("");
    const errorMessage = ref("");
    const generatedFolio = ref("");
    const isLoading = ref(false);
    const classification = ref(null);
    const isClassifying = ref(false);
    const lastClassification = ref(null);

    const userData = ref({
      Nombre: "Juan",
      ApellidoP: "Pérez",
      ApellidoM: "González",
    });
    const divisionNombre = ref("Zona Hotelera");

    const API_BASE = "http://127.0.0.1:8000";

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
            errors.value.telefono = "El teléfono debe tener entre 10 y 15 caracteres";
          else delete errors.value.telefono;
          break;
        case "direccion":
          if (!value || value.length < 5)
            errors.value.direccion = "La dirección debe tener al menos 5 caracteres";
          else delete errors.value.direccion;
          break;
        case "localidad":
          if (!value || value.length < 2)
            errors.value.localidad = "La localidad debe tener al menos 2 caracteres";
          else delete errors.value.localidad;
          break;
        case "nivel_importancia":
          if (!value)
            errors.value.nivel_importancia = "Debe seleccionar un nivel de importancia";
          else delete errors.value.nivel_importancia;
          break;
        case "descripcion":
          if (!value || value.length < 10)
            errors.value.descripcion = "La descripción debe tener al menos 10 caracteres";
          else if (value.length > 1000)
            errors.value.descripcion = "La descripción no puede exceder 1000 caracteres";
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
        formData.value.descripcion.length <= 1000 &&
        formData.value.dependencia_seleccionada !== ""
      );
    });

    const canClassify = computed(() => {
      return formData.value.descripcion.length >= 10;
    });

    let debounceTimeout = null;

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

        classification.value = data.resultado.map((item) => ({
          ...item,
          texto_original: texto,
        }));
      } catch (error) {
        console.error("Error en clasificación:", error);
        errorMessage.value = `Error al clasificar: ${error.message}`;

        if (error.message.includes("fetch")) {
          errorMessage.value =
            "No se pudo conectar con el servidor de clasificación. Verifique su conexión.";
        }
      } finally {
        isClassifying.value = false;
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

    watch(
      () => formData.value.descripcion,
      (newVal) => {
        validateField("descripcion", newVal);

        if (
          classification.value &&
          Array.isArray(classification.value) &&
          classification.value.length > 0 &&
          Math.abs(newVal.length - classification.value[0].texto_original.length) > 10
        ) {
          classification.value = null;
        }
      }
    );

    const submitForm = async () => {
      try {
        isLoading.value = true;
        errorMessage.value = "";
        successMessage.value = "";

        Object.entries(formData.value).forEach(([field, value]) => {
          if (field !== "red_social") validateField(field, value);
        });

        if (Object.keys(errors.value).length > 0) {
          throw new Error("Corrige los errores en el formulario antes de enviar.");
        }

        if (!classification.value) {
          await classifyDescription(formData.value.descripcion);
          if (!classification.value) {
            throw new Error("No se pudo clasificar la petición automáticamente.");
          }
        }

        if (!formData.value.dependencia_seleccionada) {
          throw new Error("Debe seleccionar una dependencia sugerida antes de enviar.");
        }

        const seleccion = classification.value.find(
          (item) => item.dependencia === formData.value.dependencia_seleccionada
        );

        formData.value.tipo_peticion_seleccionado = seleccion?.tipo_peticion || "";

        generatedFolio.value = "PET-" + Date.now().toString().slice(-8);
        lastClassification.value = seleccion ? [seleccion] : [...classification.value];
        successMessage.value = "¡Petición enviada exitosamente!";

        // Aquí podrías enviar petitionData al backend si lo deseas
        // const petitionData = { ...formData.value, clasificacion: seleccion, folio: generatedFolio.value }
      } catch (error) {
        console.error("Error al enviar formulario:", error);
        errorMessage.value = error.message;
      } finally {
        isLoading.value = false;
      }
    };

    const resetForm = () => {
      formData.value = {
        nombre: "",
        telefono: "",
        direccion: "",
        localidad: "",
        nivel_importancia: "",
        descripcion: "",
        red_social: "",
        dependencia_seleccionada: "",
        tipo_peticion_seleccionado: "",
      };
      errors.value = {};
      classification.value = null;
      lastClassification.value = null;
      successMessage.value = "";
      errorMessage.value = "";
      generatedFolio.value = "";
    };

    onMounted(async () => {
      try {
        const response = await fetch(`${API_BASE}/api/clasificacion/categorias`);
        if (response.ok) {
          console.log("✅ Conexión con API exitosa");
        } else {
          console.warn("⚠️ API responde pero con error:", response.status);
        }
      } catch (error) {
        console.error("❌ Error de conexión con API:", error);
        errorMessage.value =
          "Advertencia: No se pudo conectar con el servicio de clasificación automática.";
      }
    });

    return {
      formData,
      errors,
      successMessage,
      errorMessage,
      generatedFolio,
      isLoading,
      classification,
      isClassifying,
      lastClassification,
      userData,
      divisionNombre,
      canSubmit,
      canClassify,
      validateField,
      validatePhone,
      onDescriptionChange,
      testClassification,
      reclassifyDescription,
      submitForm,
      resetForm,
    };
  },
};
</script>

<style src="@/assets/css/PetionPage.css"></style>
