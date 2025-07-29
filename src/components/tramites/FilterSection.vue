<template>
  <div class="filter-section">
    <div class="filter-group">
      <label>Estado:</label>
      <select
        :value="status"
        @change="$emit('update:status', $event.target.value)"
        class="filter-select"
      >
        <option value="">Todos</option>
        <option value="pendiente">Pendiente</option>
        <option value="en-proceso">En Proceso</option>
        <option value="completado">Completado</option>
        <option value="rechazado">Rechazado</option>
      </select>
    </div>

    <div class="filter-group">
      <label>Fecha:</label>
      <select
        :value="range"
        @change="$emit('update:range', $event.target.value)"
        class="filter-select"
      >
        <option value="today">Hoy</option>
        <option value="week">Última semana</option>
        <option value="month">Último mes</option>
        <option value="year">Último año</option>
        <option value="custom">Personalizado</option>
      </select>
    </div>

    <div class="filter-group" v-if="range === 'custom'">
      <input
        type="date"
        :value="start"
        @input="$emit('update:start', $event.target.value)"
        class="date-input"
      />
      <span class="date-separator">a</span>
      <input
        type="date"
        :value="end"
        @input="$emit('update:end', $event.target.value)"
        class="date-input"
      />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    status: String,
    range: String,
    start: String,
    end: String
  },
  emits: ['update:status', 'update:range', 'update:start', 'update:end']
}
</script>

<style scoped>
/* Aquí van tus estilos */
</style>
