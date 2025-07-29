<template>
  <div class="pagination">
    <button class="pagination-button" :disabled="current === 1" @click="$emit('prev')">
      <i class="fas fa-chevron-left"></i>
    </button>
    <span class="pagination-info">Página {{ current }} de {{ total }}</span>
    <button class="pagination-button" :disabled="current === total" @click="$emit('next')">
      <i class="fas fa-chevron-right"></i>
    </button>
  </div>
</template>

<script>
export default {
  props: {
    current: Number,
    total: Number
  }
}
</script>

<style scoped>
/* Deja aquí si hay estilos particulares, o pásalos a CSS global */
</style>
