<template>
  <div v-if="selectedDependencia" class="selected-dependency-info">
    <h4>
      <font-awesome-icon icon="fa-solid fa-building" />
      Dependencia Seleccionada
    </h4>
    <div class="selected-dependency-card">
      <div class="selected-dep-header">
        <h5>{{ selectedDependencia.nombre }}</h5>
        <button type="button" @click="clearSelectedDependencia" class="clear-selection-btn">
          <font-awesome-icon icon="fa-solid fa-times" />
        </button>
      </div>
      <p>{{ selectedDependencia.descripcion }}</p>
      <div v-if="selectedDependencia.contacto" class="dependency-contact">
        <p><strong>Contacto:</strong> {{ selectedDependencia.contacto }}</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SelectedDependencyInfo',
  props: {
    selectedDependencia: Object
  },
  emits: ['clear'] ,
  methods: {
    clearSelectedDependencia() {
      this.$emit('clear')
    }
  }
}
</script>

<style scoped>
.selected-dependency-info {
  margin-top: 2rem;
  border: 1px solid #ccc;
  padding: 1rem;
  border-radius: 8px;
  background: #f9f9f9;
}
.selected-dep-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.clear-selection-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #c00;
}
</style>
