<template>
  <div v-if="showSugerencias && sugerencias.length > 0" class="dependencies-suggestions" v-motion-fade-visible-once
       :initial="{ opacity: 0, y: -20 }"
       :enter="{ opacity: 1, y: 0, transition: { duration: 500 } }">
    <div class="suggestions-header">
      <h3>
        <font-awesome-icon icon="fa-solid fa-lightbulb" />
        Dependencias Sugeridas
        <span class="suggestions-count">({{ sugerencias.length }})</span>
      </h3>
      <button type="button" class="close-suggestions" @click="$emit('close')" title="Cerrar sugerencias">
        <font-awesome-icon icon="fa-solid fa-times" />
      </button>
    </div>

    <div class="suggestions-content">
      <p class="suggestions-intro">
        <font-awesome-icon icon="fa-solid fa-info-circle" />
        Basado en su descripción, estas dependencias podrían ser las más adecuadas:
      </p>

      <div v-if="isSearching" class="suggestions-loading">
        <font-awesome-icon icon="fa-solid fa-spinner" spin />
        <span>Analizando su petición...</span>
      </div>

      <div v-else class="suggestions-list">
        <div
          v-for="(sugerencia, index) in sugerencias"
          :key="index"
          class="suggestion-item"
          :class="{
            'special-rule': sugerencia.esReglaEspecial,
            'high-score': sugerencia.score > 0.7,
            'selected': selectedDependencia && selectedDependencia.nombre === sugerencia.nombre
          }"
          @click="$emit('select', sugerencia)"
        >
          <div class="suggestion-header">
            <h4>{{ sugerencia.nombre }}</h4>
            <div class="suggestion-badges">
              <span class="suggestion-score" :class="getScoreClass(sugerencia)">
                {{ getSugerenciaLabel(sugerencia) }}
              </span>
              <span v-if="sugerencia.esReglaEspecial" class="priority-badge">
                <font-awesome-icon icon="fa-solid fa-star" /> Prioritaria
              </span>
            </div>
          </div>

          <p class="suggestion-description">{{ sugerencia.descripcion }}</p>

          <div class="suggestion-category" v-if="sugerencia.categoria">
            <span class="category-label">
              <font-awesome-icon icon="fa-solid fa-tag" />
              Categoría:
            </span>
            <span class="category-tag">{{ sugerencia.categoria }}</span>
          </div>

          <div class="suggestion-types" v-if="sugerencia.tipos_peticion && sugerencia.tipos_peticion.length > 0">
            <span class="types-label">
              <font-awesome-icon icon="fa-solid fa-list" />
              Tipos de petición:
            </span>
            <div class="types-tags">
              <span
                v-for="tipo in sugerencia.tipos_peticion.slice(0, 3)"
                :key="tipo"
                class="type-tag"
              >
                {{ tipo }}
              </span>
              <span v-if="sugerencia.tipos_peticion.length > 3" class="more-types-tag">
                +{{ sugerencia.tipos_peticion.length - 3 }} más
              </span>
            </div>
          </div>

          <div class="suggestion-keywords" v-if="sugerencia.matchedKeywords && sugerencia.matchedKeywords.length > 0">
            <span class="keywords-label">
              <font-awesome-icon icon="fa-solid fa-key" />
              Coincidencias:
            </span>
            <div class="keywords-tags">
              <span
                v-for="keyword in sugerencia.matchedKeywords.slice(0, 5)"
                :key="keyword"
                class="keyword-tag"
              >
                {{ keyword }}
              </span>
            </div>
          </div>

          <div class="suggestion-actions">
            <button type="button" class="select-dependency-btn" @click.stop="$emit('apply', sugerencia)">
              <font-awesome-icon icon="fa-solid fa-check" />
              Seleccionar esta dependencia
            </button>
          </div>
        </div>
      </div>

      <div class="suggestions-footer">
        <p class="suggestion-note">
          <font-awesome-icon icon="fa-solid fa-info-circle" />
          Las sugerencias se actualizan automáticamente mientras escribe. Haga clic en una dependencia para más detalles.
        </p>
        <div class="suggestion-stats" v-if="searchStats">
          <small>
            Análisis completado en {{ searchStats.tiempo }}ms |
            {{ searchStats.totalAnalizado }} dependencias analizadas
          </small>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DependencySuggestions',
  props: {
    sugerencias: Array,
    showSugerencias: Boolean,
    isSearching: Boolean,
    selectedDependencia: Object,
    searchStats: Object,
    getSugerenciaLabel: Function,
    getScoreClass: Function
  }
}
</script>
