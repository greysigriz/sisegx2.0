<!-- C:\xampp\htdocs\SISE\src\views\Configuracion\index.vue -->
<template>
  <div class="configuracion-container">
    <div class="configuracion-card">
      <div class="configuracion-card-header">
        <h3>Configuración del Sistema</h3>
      </div>
      <div class="configuracion-card-body">
        <p class="configuracion-welcome-message">Bienvenido al módulo de Configuración</p>
        <p>Desde aquí podrás administrar las configuraciones del sistema.</p>

        <div class="configuracion-sections">
          <!-- Usuarios -->
          <div class="configuracion-item" @click="redirectTo('usuarios')">
            <div class="configuracion-icon">
              <font-awesome-icon :icon="['fas', 'user-shield']" />
            </div>
            <div class="configuracion-details">
              <div>
                <h4>Usuarios</h4>
                <p>Administrar usuarios</p>
              </div>
              <div class="configuracion-arrow-icon">
                →
              </div>
            </div>
          </div>

          <!-- Roles -->
          <div class="configuracion-item" @click="redirectTo('roles')">
            <div class="configuracion-icon">
              <font-awesome-icon :icon="['fas', 'database']" />
            </div>
            <div class="configuracion-details">
              <div>
                <h4>Roles</h4>
                <p>Administrar roles</p>
              </div>
              <div class="configuracion-arrow-icon">
                →
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

.configuracion-card {
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow: hidden;
}

.configuracion-card-header {
  padding: 20px;
  background: linear-gradient(135deg, #165CB1 0%, #1976d2 100%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.configuracion-card-header h3 {
  margin: 0;
  color: white;
  font-size: 18px;
}

.configuracion-card-body {
  padding: 20px;
}

.configuracion-welcome-message {
  font-size: 16px;
  font-weight: 500;
  color: #165CB1;
  margin-bottom: 10px;
}

.configuracion-sections {
  margin-top: 20px;
}

.configuracion-item {
  display: flex;
  align-items: center;
  padding: 15px;
  border-radius: 8px;
  background-color: rgba(22, 92, 177, 0.05);
  margin-bottom: 15px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.configuracion-item:hover {
  background-color: rgba(22, 97, 177, 0.15);
}

.configuracion-icon {
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

.configuracion-details {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.configuracion-details h4 {
  margin: 0;
  font-size: 16px;
  color: #333;
  font-weight: 600;
}

.configuracion-details p {
  margin: 5px 0 0;
  font-size: 14px;
  color: #666;
}

.configuracion-arrow-icon {
  color: #165CB1;
  font-size: 24px;
  font-weight: bold;
}

/* Responsive */
@media (max-width: 768px) {
  .configuracion-sections {
    grid-template-columns: 1fr;
  }

  .configuracion-item {
    flex-direction: column;
    text-align: center;
  }

  .configuracion-icon {
    margin: 0 0 15px 0;
  }

  .configuracion-details {
    flex-direction: column;
    gap: 10px;
  }
}
</style>

