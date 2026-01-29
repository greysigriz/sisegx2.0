<template>
  <div class="department-grid">
    <div class="dept-card" v-for="n in 12" :key="n">
      <h4>Departamento {{ n }}</h4>
      <p>Casos activos: {{ Math.floor(Math.random() * 50) }}</p>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.department-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}

.dept-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
</style>
