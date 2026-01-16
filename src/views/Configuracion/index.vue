<!-- C:\xampp\htdocs\SISE\src\views\Configuracion\index.vue -->
<template>
  <div class="configuracion-container">
    <div class="card">
      <div class="card-header">
        <h3>Configuración del Sistema</h3>
      </div>
      <div class="card-body">
        <p class="welcome-message">Bienvenido al módulo de Configuración</p>
        <p>Desde aquí podrás administrar las configuraciones del sistema.</p>

        <div class="config-sections">
          <!-- Usuarios -->
          <div class="config-item" @click="redirectTo('usuarios')">
            <div class="config-icon">
              <font-awesome-icon :icon="['fas', 'user-shield']" />
            </div>
            <div class="config-details">
              <div>
                <h4>Usuarios</h4>
                <p>Administrar usuarios</p>
              </div>
              <div class="arrow-icon">
                <font-awesome-icon :icon="['fas', 'chevron-right']" />
              </div>
            </div>
          </div>

          <!-- Roles -->
          <div class="config-item" @click="redirectTo('roles')">
            <div class="config-icon">
              <font-awesome-icon :icon="['fas', 'database']" />
            </div>
            <div class="config-details">
              <div>
                <h4>Roles</h4>
                <p>Administrar roles</p>
              </div>
              <div class="arrow-icon">
                <font-awesome-icon :icon="['fas', 'chevron-right']" />
              </div>
            </div>
          </div>

          <!-- Unidades -->
          <!-- <div class="config-item" @click="redirectTo('unidades/crear')">
            <div class="config-icon">
              <font-awesome-icon :icon="['fas', 'building']" />
            </div>
            <div class="config-details">
              <div>
                <h4>Unidades</h4>
                <p>Administrar unidades</p>
              </div>
              <div class="arrow-icon">
                <font-awesome-icon :icon="['fas', 'chevron-right']" />
              </div>
            </div>
          </div> -->

        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfiguracionPanel',
  methods: {
    redirectTo(page) {
      this.$router.push(`/configuracion/${page}`)
    }
  }
}
</script>

<style>
/* Estilos sin scoped para asegurar que siempre se apliquen */
.configuracion-container {
  padding: 20px;
}

.configuracion-container .card {
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow: hidden;
}

.configuracion-container .card-header {
  padding: 20px;
  background: linear-gradient(135deg, #165CB1 0%, #1976d2 100%) !important;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.configuracion-container .card-header h3 {
  margin: 0;
  color: white !important;
  font-size: 18px;
}

.configuracion-container .card-body {
  padding: 20px;
}

.configuracion-container .welcome-message {
  font-size: 16px;
  font-weight: 500;
  color: #165CB1;
  margin-bottom: 10px;
}

.configuracion-container .config-sections {
  margin-top: 20px;
}

.configuracion-container .config-item {
  display: flex;
  align-items: center;
  padding: 15px;
  border-radius: 8px;
  background-color: rgba(22, 92, 177, 0.05);
  margin-bottom: 15px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.configuracion-container .config-item:hover {
  background-color: rgba(22, 97, 177, 0.15);
}

.configuracion-container .config-icon {
  width: 50px;
  height: 50px;
  background-color: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  color: #165CB1;
  font-size: 20px;
}

.configuracion-container .config-details {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.configuracion-container .config-details h4 {
  margin: 0;
  font-size: 16px;
  color: #333;
  font-weight: 600;
}

.configuracion-container .config-details p {
  margin: 5px 0 0;
  font-size: 14px;
  color: #666;
}

.configuracion-container .arrow-icon {
  color: #165CB1;
  font-size: 16px;
}
</style>
