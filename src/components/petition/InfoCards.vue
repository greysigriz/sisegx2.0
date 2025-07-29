<template>
  <div class="info-cards" v-if="searchStats">
    <div class="card">
      <h4>Total de coincidencias</h4>
      <p>{{ searchStats.total }}</p>
    </div>
    <div class="card">
      <h4>Dependencias sugeridas</h4>
      <ul>
        <li v-for="(dep, index) in searchStats.dependencias" :key="index">{{ dep }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
defineProps({
  searchStats: Object
})
</script>

<style scoped>
.info-cards {
  margin-top: 20px;
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}
.card {
  background: #fff;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 0 5px rgba(0,0,0,0.1);
  flex: 1;
  min-width: 200px;
}
.card h4 {
  margin-bottom: 10px;
  font-weight: 600;
}
</style>
