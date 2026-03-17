<template>
  <button
    class="back-button"
    @click="handleBack"
    :title="title"
  >
    <font-awesome-icon :icon="['fas', 'arrow-left']" />
    <span>{{ label }}</span>
  </button>
</template>

<script>
export default {
  name: 'BackButton',
  props: {
    to: {
      type: String,
      default: '/configuracion'
    },
    label: {
      type: String,
      default: 'Volver'
    },
    title: {
      type: String,
      default: 'Volver a Configuración'
    }
  },
  methods: {
    handleBack() {
      this.$router.push(this.to);
    }
  }
}
</script>

<style scoped>
.back-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #165CB1 0%, #1976d2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(22, 92, 177, 0.2);
  margin-top: 15px;
  margin-bottom: 5px;
}

.back-button:hover {
  background: linear-gradient(135deg, #1976d2 0%, #165CB1 100%);
  box-shadow: 0 4px 8px rgba(22, 92, 177, 0.3);
  transform: translateY(-2px);
}

.back-button:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(22, 92, 177, 0.2);
}

.back-button svg {
  font-size: 14px;
}
</style>
