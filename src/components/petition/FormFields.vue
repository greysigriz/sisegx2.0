<template>
  <div class="form-fields">
    <div class="form-group">
      <label for="descripcion">Describe tu problema</label>
      <textarea
        id="descripcion"
        class="form-control"
        rows="4"
        v-model="formData.descripcion"
        @input="emitDescripcion"
      ></textarea>
      <span class="error-message" v-if="errors.descripcion">{{ errors.descripcion }}</span>
    </div>

    <div class="form-group">
      <label for="red_social">Red social (opcional)</label>
      <input
        type="text"
        id="red_social"
        class="form-control"
        v-model="formData.red_social"
      />
    </div>

    <div class="form-group" v-if="showSugerencias && sugerencias.length">
      <label>Dependencias sugeridas:</label>
      <ul class="sugerencias-list">
        <li
          v-for="(item, index) in sugerencias"
          :key="index"
          @click="selectSugerencia(item)"
        >
          {{ item.dependencia }} ({{ item.tipo_peticion }})
        </li>
      </ul>
    </div>

    <div class="form-group">
      <label for="dependencia_sugerida">Dependencia seleccionada</label>
      <input
        type="text"
        id="dependencia_sugerida"
        class="form-control"
        v-model="formData.dependencia_sugerida"
        readonly
      />
    </div>
  </div>
</template>

<script>
export default {
  name: 'FormFields',
  props: {
    formData: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      required: true
    },
    sugerencias: {
      type: Array,
      required: false
    },
    showSugerencias: {
      type: Boolean,
      required: false
    }
  },
  emits: ['descripcionChange', 'selectSugerencia'],
  methods: {
    emitDescripcion() {
      this.$emit('descripcionChange', this.formData.descripcion);
    },
    selectSugerencia(item) {
      this.$emit('selectSugerencia', item);
    }
  }
}
</script>

<style scoped>
.form-fields {
  margin-bottom: 2rem;
}
.form-group {
  margin-bottom: 1rem;
}
.sugerencias-list {
  list-style: none;
  padding-left: 0;
  margin-top: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.sugerencias-list li {
  padding: 8px 12px;
  cursor: pointer;
}
.sugerencias-list li:hover {
  background-color: #f0f0f0;
}
.error-message {
  color: red;
  font-size: 0.875rem;
}
</style>
