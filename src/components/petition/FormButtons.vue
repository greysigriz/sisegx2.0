<template>
  <div class="form-actions">
    <button type="submit" class="submit-button">Enviar</button>
    <button type="button" class="reset-button" @click="resetForm">Limpiar</button>
  </div>
</template>

<script setup>
const emit = defineEmits(['reset'])
const resetForm = () => emit('reset')
</script>

<style scoped>
.form-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
}

.submit-button {
  background-color: #007bff;
  color: white;
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.reset-button {
  background-color: #6c757d;
  color: white;
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
</style>
