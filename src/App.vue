<template>
  <div id="app">
    <router-view v-slot="{ Component, route }">
      <transition name="fade" mode="out-in">
        <component :is="Component" :key="route.path" />
      </transition>
    </router-view>
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
/* ...existing styles... */

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

