<template>
  <header class="yucatan-header">
    <div class="header-content">
      <!-- Lado izquierdo: Título -->
      <div class="header-left">
        <div class="titles">
          <h1 class="main-title">Sistema de Reportes Ciudadanos - Yucatán</h1>
          <p class="subtitle">Panel de Control y Monitoreo</p>
        </div>
      </div>

      <!-- Lado derecho: Buscador de Municipio, Notificaciones -->
      <div class="header-right">
        <!-- Buscador/Selector de Municipio combinado -->
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

        <!-- Botón de Notificaciones -->
        <button class="notification-btn" @click="$emit('notifications')">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
          </svg>
          <span v-if="notificationCount > 0" class="notification-badge">{{ notificationCount }}</span>
        </button>
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

