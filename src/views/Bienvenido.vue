<template>
  <div class="inicio-container">
    <!-- Sección superior: Botones de gestión -->
    <div class="seccion-gestion">
      <TablaGestion @filtrar-estado="estadoSeleccionado = $event" />
    </div>

    <!-- Sección media: Tabla de reportes y Dashboard -->
    <div class="seccion-contenido">
      <div class="tabla-reportes-wrapper">
        <TablaReportes :estadoSeleccionado="estadoSeleccionado" />
      </div>
    </div>

    <!-- Sección inferior: Línea de tiempo -->
    <div class="seccion-grafica-tiempo">
      <div class="linea-tiempo-wrapper">
        <LineaTiempo />
      </div>
      <div class="dashboard-wrapper">
        <Dashboard />
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import Dashboard from '@/components/Dashboard.vue'
import TablaGestion from '@/components/TablaGestion.vue'
import TablaReportes from '@/components/TablaReportes.vue'
import LineaTiempo from '@/components/LineaTiempo.vue'

export default {
  name: 'Inicio',
  components: {
    Dashboard,
    TablaReportes,
    TablaGestion,
    LineaTiempo
  },
  setup() {
    const estadoSeleccionado = ref('TOTAL')
    return { estadoSeleccionado }
  }
}
</script>

<style scoped>
.inicio-container {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 2rem;
  background-color: #f8fafc;
  min-height: 100vh;
}

/* Sección de gestión (botones superiores) */
.seccion-gestion {
  width: 100%;
  margin-bottom: 0.5rem;
}

/* Sección de contenido principal */
.seccion-contenido {
  display: flex;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  align-items: start;
}

.tabla-reportes-wrapper {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  padding: 1.5rem;
  min-height: 400px;
  width: 100%;
}

.dashboard-wrapper {
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

/* Sección de línea de tiempo */
.seccion-grafica-tiempo {
  width: 100%;
  display: flex;
  justify-content: center;
  margin-top: 1rem;
}

.linea-tiempo-wrapper {
  width: 100%;
  max-width: 820px;
  display: flex;
  justify-content: left;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .seccion-contenido {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .dashboard-wrapper {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .inicio-container {
    padding: 1rem;
    gap: 1.5rem;
  }
  
  .tabla-reportes-wrapper {
    padding: 1rem;
    min-height: auto;
  }
  
  .seccion-contenido {
    gap: 1rem;
  }
}

@media (max-width: 480px) {
  .inicio-container {
    padding: 0.75rem;
    gap: 1rem;
  }
  
  .tabla-reportes-wrapper {
    padding: 0.75rem;
  }
}
</style>