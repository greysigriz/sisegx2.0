<template>
  <div v-if="searchStats" class="info-cards">
    <div class="card">
      <h4>Palabras clave detectadas:</h4>
      <ul>
        <li v-for="(item, index) in searchStats.palabrasDetectadas" :key="index">
          {{ item }}
        </li>
      </ul>
    </div>

    <div class="card" v-if="searchStats.coincidenciasTotales > 0">
      <h4>Total de coincidencias:</h4>
      <p>{{ searchStats.coincidenciasTotales }}</p>
    </div>

    <div class="card" v-if="searchStats.dependenciaPrincipal">
      <h4>Dependencia principal sugerida:</h4>
      <p>{{ searchStats.dependenciaPrincipal }}</p>
    </div>

    <div class="card" v-if="searchStats.dependenciasRelacionadas?.length">
      <h4>Dependencias relacionadas:</h4>
      <ul>
        <li v-for="(dep, i) in searchStats.dependenciasRelacionadas" :key="i">{{ dep }}</li>
      </ul>
    </div>

    <div class="card" v-if="searchStats.tiposPeticion?.length">
      <h4>Tipos de petición relacionados:</h4>
      <ul>
        <li v-for="(tipo, i) in searchStats.tiposPeticion" :key="i">{{ tipo }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
defineProps({
  searchStats: Object
});
</script>

<style scoped>
.info-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}

.card {
  border: 1px solid #ccc;
  padding: 1rem;
  border-radius: 8px;
  background: #f9f9f9;
}

.card h4 {
  margin: 0 0 0.5rem;
  font-size: 1rem;
  color: #333;
}

.card p,
.card ul {
  margin: 0;
  padding: 0;
  list-style: none;
}

.card li {
  margin-bottom: 4px;
}
</style>
