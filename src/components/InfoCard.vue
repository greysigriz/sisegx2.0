<template>
  <div class="info-card" v-motion-fade-visible-once
       :initial="{ opacity: 0, y: 30 }"
       :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: delay } }">
    <font-awesome-icon :icon="['fas', icon]" class="info-icon" />
    <h3>{{ title }}</h3>
    <p>{{ description }}</p>
  </div>
</template>

<script>
export default {
  name: 'InfoCard',
  props: {
    icon: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, required: true },
    delay: { type: Number, default: 0 }
  }
}
</script>

<style scoped>
.info-card {
  background-color: #f9f9f9;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 1rem;
  text-align: center;
}
.info-icon {
  font-size: 2rem;
  color: #7d2755;
  margin-bottom: 0.5rem;
}
</style>
