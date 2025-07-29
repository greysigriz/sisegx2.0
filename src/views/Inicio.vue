
<template>
  <div class="layout">
    <!-- Menú lateral fijo -->
    <aside class="sidebar">
      <h2 class="logo">Menú</h2>
      <ul>
        <li v-for="item in menuItems" :key="item.name" :class="{ active: item.name === selected }" @click="selected = item.name">
          {{ item.label }}
        </li>
      </ul>
    </aside>

    <!-- Contenido -->
    <div class="content">
      <h1>Bienvenido, Admin</h1>

      <div v-if="selected === 'dashboard'">
        <h2>Dashboard</h2>
        <p>Estadísticas, métricas, KPIs, etc.</p>
        <GraficoBarra/>
      </div>

      <div v-else-if="selected === 'tramites'">
        <h2>Trámites</h2>
        <p>Visualización de trámites.</p>
      </div>

      <div v-else-if="selected === 'reportes'">
        <h2>Reportes</h2>
        <p>Generar reportes.</p>
      </div>

      <div v-else-if="selected === 'logout'">
        <h2>Saliendo...</h2>
      </div>
    </div>
  </div>
</template>

<script>
import GraficoBarra from '@/components/GraficoBarra.vue'

export default {
  components: {
    GraficoBarra
  },
  data() {
    return {
      selected: 'dashboard',
      menuItems: [
        { name: 'dashboard', label: 'Dashboard' },
        { name: 'tramites', label: 'Trámites' },
        { name: 'reportes', label: 'Reportes' },
        { name: 'logout', label: 'Cerrar sesión' }
      ]
    }
  },
  watch: {
    selected(value) {
      if (value === 'logout') {
        localStorage.removeItem('user')
        this.$router.push('/')
      }
    }
  }
}
</script>
<style scoped>
.layout {
  display: flex;
  height: 100vh;
  width: 100vw;
  overflow: hidden;
}

.sidebar {
  width: 200px;
  background-color: #2c3e50;
  color: white;
  padding: 20px;
}

.logo {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  text-align: center;
}

.sidebar ul {
  list-style: none;
  padding: 0;
}

.sidebar li {
  padding: 10px;
  cursor: pointer;
  border-radius: 4px;
}

.sidebar li:hover,
.sidebar li.active {
  background-color: #1abc9c;
}

.content {
  padding: 30px;
  background-color: #ecf0f1;
  width: calc(100% - 200px); /* resto del ancho */
  overflow-y: auto;
}
</style>
