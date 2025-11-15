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
          <div class="config-item" @click="redirectTo('unidades/crear')">
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
          </div>

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

<style scoped>
.configuracion-container {
  padding: 20px;
}

.card {
  background-color: var(--white-color);
  border-radius: 8px;
  box-shadow: var(--shadow);
  overflow: hidden;
}

.card-header {
  padding: 20px;
  background-color: var(--white-color);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.card-header h3 {
  margin: 0;
  color: white;
  font-size: 18px;
}

.card-body {
  padding: 20px;
}

.welcome-message {
  font-size: 16px;
  font-weight: 500;
  color: var(--primary-color);
  margin-bottom: 10px;
}

.config-sections {
  margin-top: 20px;
}

.config-item {
  display: flex;
  align-items: center;
  padding: 15px;
  border-radius: 8px;
  background-color: rgba(22, 92, 177, 0.05);
  margin-bottom: 15px;
  transition: var(--transition);
  cursor: pointer;
}

.config-item:hover {
  background-color: rgba(22, 97, 177, 0.15);
}

.config-icon {
  width: 50px;
  height: 50px;
  background-color: var(--white-color);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  color: var(--primary-color);
  font-size: 20px;
}

.config-details {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.config-details h4 {
  margin: 0 0 5px 0;
  color: var(--secondary-color);
  font-size: 16px;
}

.config-details p {
  margin: 0;
  color: var(--light-color);
  font-size: 14px;
}

.arrow-icon {
  color: var(--primary-color);
  font-size: 16px;
}
</style>
