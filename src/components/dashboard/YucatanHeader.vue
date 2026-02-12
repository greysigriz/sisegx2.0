<template>
  <header class="yucatan-header">

    <!-- Barra superior: logo-marca izquierda + notificaciones derecha -->
    <div class="top-bar">
      <div class="top-bar__brand">
        <svg class="top-bar__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span class="top-bar__label">Gov · Yucatán</span>
      </div>

      <button class="notification-btn" @click="$emit('notifications')">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
          <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        <span v-if="notificationCount > 0" class="notification-badge">{{ notificationCount }}</span>
      </button>
    </div>

    <!-- Divider fino -->
    <div class="header-divider"></div>

    <!-- Centro: Título + Subtítulo + Buscador -->
    <div class="header-center">
      <div class="titles">
        <h1 class="main-title">
          <span class="title-accent">Sistema de Reportes Ciudadanos </span>
        </h1>
        <p class="subtitle">Panel de Control y Monitoreo · Estado de Yucatán</p>
      </div>

      <!-- Buscador/Selector de Municipio -->
      <div class="select-wrapper municipality-combobox">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/>
          <path d="m21 21-4.3-4.3"/>
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar municipio..."
          class="municipality-select search-input"
          @input="onSearch"
          @focus="onInputFocus"
          @blur="onInputBlur"
          @keydown="handleKeyDown"
        />
        <svg class="select-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" @mousedown.prevent="toggleSuggestions">
          <path d="m6 9 6 6 6-6"/>
        </svg>

        <ul v-if="showSuggestions" class="suggestions">
          <li
            class="suggestion-item suggestion-all"
            :class="{ highlighted: highlightedIndex === -1 }"
            @mousedown.prevent="selectSuggestion('Todos')"
          >
            Todos los municipios
          </li>
          <li
            v-for="(m, idx) in filteredMunicipalities"
            :key="m"
            :class="['suggestion-item', { highlighted: idx === highlightedIndex }]"
            @mousedown.prevent="selectSuggestion(m)"
          >
            {{ m }}
          </li>
        </ul>
      </div>
    </div>

  </header>
</template>

<script>
import '@/assets/css/header_dashboard.css'
import HeaderFuncionalidad from '@/components/TableroDash/header_funcionalidad.vue'

export default {
  name: "YucatanHeader",
  mixins: [HeaderFuncionalidad]
};
</script>
