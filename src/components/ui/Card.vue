<template>
  <div class="card-wrapper" v-bind="$attrs">
    <slot />
  </div>
</template>

<script setup>
defineOptions({ name: 'UiCard' })
// Contenedor Card ligero; acepta clases y atributos pasantes.
</script>

<style scoped>
.card-wrapper {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  font-family: "Inter", "Segoe UI", sans-serif;
}
</style>
