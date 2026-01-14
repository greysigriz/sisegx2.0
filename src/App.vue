<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
import { onErrorCaptured, ref } from 'vue';

const hasError = ref(false);

// ✅ Capturar errores para evitar que rompan la app
onErrorCaptured((error, instance, info) => {
  console.error('Error capturado en App:', error);
  console.error('Info:', info);

  // Evitar propagación de errores de parentNode
  if (error.message && error.message.includes('parentNode')) {
    console.warn('Error de DOM ignorado - posible problema de timing');
    return false; // Evitar propagación
  }

  hasError.value = true;
  return true;
});
</script>

<style>
/* Estilos globales */
</style>

