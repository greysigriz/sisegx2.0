<template>
  <div class="page-actions">
    <div class="search-container">
      <i class="fas fa-search search-icon"></i>
      <input
        type="text"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        placeholder="Buscar trámite..."
        class="search-input"
      />
    </div>
    <router-link to="/formulario" class="new-button">
      <i class="fas fa-plus"></i> Nuevo Trámite
    </router-link>
  </div>
</template>

<script>
export default {
  props: {
    modelValue: String
  },
  emits: ['update:modelValue']
}
</script>

<style scoped>
/* Tus estilos aquí */
</style>
