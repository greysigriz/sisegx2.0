<template>
  <div>
    <!-- Botón flotante para mostrar sidebar -->
    <transition name="fade-scale">
      <button
        v-if="isSidebarHidden"
        class="sidebar-toggle-fab"
        @click="toggleSidebar"
        title="Mostrar menú"
      >
        <i class="fas fa-compass"></i>
      </button>
    </transition>

    <!-- Sidebar con animación -->
    <transition name="slide-up">
      <div v-if="!isSidebarHidden" class="sidebar-container">
        <div class="sidebar-backdrop" @click="toggleSidebar"></div>

        <div class="sidebar-content">
          <!-- Barra superior con info del usuario y botón cerrar -->
          <div class="sidebar-top">
            <div class="user-badge">
              <div class="user-avatar">
                {{ userInitial }}
              </div>
              <div class="user-info-compact">
                <span class="user-name-short">{{ userName }}</span>
                <span class="user-role-short">{{ userRole }}</span>
              </div>
            </div>

            <button class="hide-sidebar-btn" @click="toggleSidebar" title="Ocultar menú">
              <i class="fas fa-chevron-down"></i>
            </button>
          </div>

          <!-- Navegación -->
          <nav class="navigation">
            <ul v-if="userReady">
              <li
                v-for="(item, index) in filteredMenuItems"
                :key="`menu-${item.name}-${index}`"
                :class="{ active: isActive(item.path) }"
                @click="navigateTo(item.path)"
              >
                <div class="nav-icon">
                  <i :class="item.icon"></i>
                </div>
                <span class="nav-label">{{ item.label }}</span>
                <div class="nav-indicator"></div>
              </li>
            </ul>
          </nav>

          <!-- Botón de logout mejorado -->
          <div class="sidebar-footer">
            <button
              class="logout-button-modern"
              @click="handleLogout"
              :disabled="isLoggingOut"
            >
              <i class="fas fa-sign-out-alt"></i>
              <span>{{ isLoggingOut ? 'Saliendo...' : 'Cerrar sesión' }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { useRouter } from 'vue-router'
import authService from '@/services/auth.js'

export default {
  name: 'AppSidebar',
  setup() {
    const router = useRouter()
    return { router }
  },
  data() {
    return {
      isCollapsed: false,
      isLoggingOut: false,
      isSidebarHidden: false,
      allMenuItems: [
        { name: 'Inicio', label: 'Bienvenido', icon: 'fas fa-chart-line', path: '/bienvenido', requiredPermission: 'ver_dashboard' },
        { name: 'formulario', label: 'Crear Petición', icon: 'fas fa-edit', path: '/petition', requiredPermission: 'peticiones_formulario' },
        { name: 'peticiones', label: 'Peticiones', icon: 'fas fa-tasks', path: '/peticiones', requiredPermission: 'ver_peticiones,peticiones_municipio,peticiones_estatal' },
        { name: 'petitions', label: 'Petitions', icon: 'fas fa-user-check', path: '/petitions', requiredPermission: 'ver_peticiones' },
        { name: 'configuracion', label: 'Configuración', icon: 'fas fa-cog', path: '/configuracion', requiredPermission: 'acceder_configuracion' },
        { name: 'mis-peticiones', label: 'Mis Peticiones', icon: 'fas fa-clipboard-list', path: '/departamentos', requiredPermission: 'gestion_peticiones_departamento' },
        { name: 'tablero', label: 'Tablero', icon: 'fas fa-th-large', path: '/tablero', requiredPermission: 'ver_tablero' },
      ],
      currentUser: null,
      isLoadingUser: false,
      retryCount: 0,
      maxRetries: 3,
      authCheckInterval: null,
      isInitialized: false,
      userReady: false
    }
  },

  computed: {
    userInitial() {
      if (!this.currentUser || !this.currentUser.usuario) return 'U';
      const usuario = this.currentUser.usuario;
      if (usuario.Nombre && typeof usuario.Nombre === 'string') {
        return usuario.Nombre.charAt(0).toUpperCase();
      }
      if (usuario.Usuario && typeof usuario.Usuario === 'string') {
        return usuario.Usuario.charAt(0).toUpperCase();
      }
      return 'U';
    },
    userName() {
      if (!this.currentUser || !this.currentUser.usuario) return 'Cargando...';
      const usuario = this.currentUser.usuario;
      if (usuario.Nombre && typeof usuario.Nombre === 'string') {
        return usuario.Nombre.split(' ')[0];
      }
      if (usuario.Usuario && typeof usuario.Usuario === 'string') {
        return usuario.Usuario;
      }
      return 'Usuario';
    },
    userRole() {
      if (!this.currentUser || !this.currentUser.usuario) return 'Usuario';

      // Mostrar todos los roles si tiene múltiples
      const usuario = this.currentUser.usuario;
      if (usuario.RolesNombres && Array.isArray(usuario.RolesNombres) && usuario.RolesNombres.length > 0) {
        // Si tiene más de un rol, mostrar el primero + cantidad
        if (usuario.RolesNombres.length > 1) {
          return `${usuario.RolesNombres[0]} +${usuario.RolesNombres.length - 1}`;
        }
        return usuario.RolesNombres[0];
      }

      // Fallback al rol antiguo
      if (this.currentUser.rol) {
        return this.currentUser.rol.nombre || 'Usuario';
      }

      return 'Usuario';
    },
    filteredMenuItems() {
      if (!this.currentUser || !this.currentUser.usuario) {
        return [];
      }

      const usuario = this.currentUser.usuario;

      // Obtener permisos del usuario
      const permisos = usuario.Permisos || this.currentUser.permisos || [];

      // Si no hay permisos, no mostrar ningún menú
      if (!Array.isArray(permisos) || permisos.length === 0) {
        console.warn('Usuario sin permisos asignados');
        return [];
      }

      // Filtrar elementos del menú según los permisos del usuario
      return this.allMenuItems.filter(item => {
        // Si el permiso contiene múltiples opciones separadas por coma, verificar si tiene alguno
        if (item.requiredPermission.includes(',')) {
          const permisosRequeridos = item.requiredPermission.split(',').map(p => p.trim());
          return permisosRequeridos.some(p => permisos.includes(p));
        }
        // Si es un solo permiso, verificar directamente
        return permisos.includes(item.requiredPermission);
      });
    }
  },
  async created() {
    // Inicializar componente de forma segura
    await this.initializeComponent();

    // Restaurar preferencia de visibilidad
    const savedVisibility = localStorage.getItem('sidebarHidden');
    if (savedVisibility !== null) {
      this.isSidebarHidden = savedVisibility === 'true';
    }
  },
  async mounted() {
    // Asegurar que el componente esté completamente montado
    if (!this.isInitialized) {
      await this.initializeComponent();
    }
  },
  beforeUnmount() {
    this.cleanup();
  },
  methods: {
    async initializeComponent() {
      try {
        this.isInitialized = true;

        // Cargar datos del usuario
        await this.loadUserData();

        // Verificar si hay una preferencia guardada para el estado del sidebar
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState !== null) {
          this.isCollapsed = savedState === 'true';
        }

        // Verificar autenticación periódicamente
        this.startPeriodicAuthCheck();

      } catch (error) {
        console.error('Error al inicializar componente:', error);
        this.handleInitializationError(error);
      }
    },

    handleInitializationError(error) {
      // Si hay error de autenticación, redirigir al login
      if (error.message && error.message.includes('auth')) {
        this.router.push('/login');
        return;
      }

      // Para otros errores, intentar recuperar
      console.warn('Error en inicialización, intentando recuperar...');
      setTimeout(() => {
        this.initializeComponent();
      }, 2000);
    },

    isActive(path) {
      if (!this.$route || !this.$route.path) return false;
      return this.$route.path === path;
    },

    toggleSidebar() {
      this.isSidebarHidden = !this.isSidebarHidden;

      // Guardar preferencia
      if (this.currentUser) {
        localStorage.setItem('sidebarHidden', this.isSidebarHidden);
      }
    },

    async navigateTo(path) {
      try {
        // Verificar autenticación antes de navegar
        if (!authService.isAuthenticated()) {
          this.router.push('/login');
          return;
        }

        // Evitar navegación innecesaria a la misma ruta
        if (this.$route.path === path) {
          return;
        }

        // Forzar navegación usando replace y luego push
        await this.$router.replace({ path: '/' });
        await this.$nextTick();
        await this.$router.push(path);

      } catch (error) {
        console.error('Error en navegación:', error);

        // Fallback: navegación directa
        try {
          await this.$router.push(path);
        } catch (fallbackError) {
          console.error('Error en navegación fallback:', fallbackError);
          // Último recurso: recargar la página con la nueva ruta
          window.location.href = path;
        }
      }

      // Ocultar sidebar en móvil después de navegar
      if (window.innerWidth < 768) {
        this.isSidebarHidden = true;
      }
    },

    async loadUserData() {
      if (this.isLoadingUser) return;

      this.isLoadingUser = true;

      try {
        // Esperar activamente hasta que se pueda acceder a los datos
        let retries = 0;
        let userData = null;

        while ((!userData || !userData.usuario) && retries < 10) {
          if (!authService.isAuthenticated()) {
            throw new Error('Usuario no autenticado');
          }

          userData = authService.getCurrentUser();
          if (!userData || !userData.usuario) {
            console.warn('Esperando datos de usuario...');
            await new Promise(resolve => setTimeout(resolve, 100)); // Espera 100ms
            retries++;
          }
        }

        // Si sigue sin datos válidos, intenta sincronizar
        if (!userData || !userData.usuario) {
          console.warn('Datos aún no disponibles, forzando sincronización...');
          userData = await authService.syncUserData();
        }

        if (!userData || !userData.usuario) {
          throw new Error('No se pudieron obtener los datos del usuario');
        }

        this.currentUser = userData;
        this.userReady = true;

      } catch (error) {
        console.error('Error al cargar datos del usuario:', error);
        await this.handleUserDataError(error);
      } finally {
        this.isLoadingUser = false;
      }
    },

    async backgroundSync() {
      try {
        const freshUserData = await authService.syncUserData();
        if (freshUserData && freshUserData.usuario) {
          this.currentUser = freshUserData;
        }
      } catch (error) {
        console.warn('Error en sincronización en segundo plano:', error);
        // No hacer nada crítico, solo log
      }
    },

    async handleUserDataError(error) {
      // Implementar retry logic
      if (this.retryCount < this.maxRetries) {
        this.retryCount++;
        console.log(`Reintentando carga de usuario (${this.retryCount}/${this.maxRetries})`);

        const retryDelay = 1000 * Math.pow(2, this.retryCount); // Backoff exponencial
        setTimeout(() => {
          this.loadUserData();
        }, retryDelay);

      } else {
        // Si no hay datos válidos después de varios intentos
        console.error('No se pudo cargar datos del usuario después de varios intentos');

        if (error.message && error.message.includes('auth')) {
          this.showErrorMessage('Error de autenticación. Redirigiendo al login...');
          setTimeout(() => {
            this.router.push('/login');
          }, 2000);
        } else {
          this.showErrorMessage('Error al cargar datos del usuario. Intente recargar la página.');
        }
      }
    },

    async handleLogout() {
      if (this.isLoggingOut) return;

      this.isLoggingOut = true;

      try {
        // Confirmar logout
        const confirmLogout = confirm('¿Está seguro que desea cerrar sesión?');
        if (!confirmLogout) {
          this.isLoggingOut = false;
          return;
        }

        // Limpiar intervals
        this.cleanup();

        // Mostrar mensaje de despedida
        this.showSuccessMessage('Cerrando sesión...');

        // Realizar logout
        await authService.logout();

        // Limpiar datos locales del componente
        this.currentUser = null;

        // Pequeño delay para mejor UX
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Redirigir al login
        this.router.push('/login');

      } catch (error) {
        console.error('Error al cerrar sesión:', error);

        // Incluso si hay error en el servidor, limpiar localmente y redirigir
        this.currentUser = null;
        authService.clearAllData();

        this.showErrorMessage('Error al cerrar sesión, pero se limpiaron los datos locales.');

        setTimeout(() => {
          this.router.push('/login');
        }, 1500);

      } finally {
        this.isLoggingOut = false;
      }
    },

    startPeriodicAuthCheck() {
      // Limpiar interval anterior si existe
      if (this.authCheckInterval) {
        clearInterval(this.authCheckInterval);
      }

      // Verificar autenticación cada 2 minutos
      this.authCheckInterval = setInterval(async () => {
        if (!authService.isAuthenticated()) {
          console.log('Sesión no válida detectada en verificación periódica');
          this.cleanup();
          this.router.push('/login');
        } else {
          // Verificar si necesitamos actualizar datos del usuario
          if (!this.currentUser || !this.currentUser.usuario) {
            await this.loadUserData();
          }
        }
      }, 2 * 60 * 1000);
    },

    cleanup() {
      if (this.authCheckInterval) {
        clearInterval(this.authCheckInterval);
        this.authCheckInterval = null;
      }
    },

    showSuccessMessage(message) {
      this.showMessage(message, 'success');
    },

    showErrorMessage(message) {
      this.showMessage(message, 'error');
    },

    showMessage(message, type = 'info') {
      // Evitar múltiples notificaciones del mismo tipo
      const existingNotification = document.querySelector(`.${type}-notification`);
      if (existingNotification) return;

      const notification = document.createElement('div');
      const bgColor = type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3';

      notification.className = `${type}-notification`;
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-family: Arial, sans-serif;
        font-size: 14px;
        max-width: 300px;
        animation: slideIn 0.3s ease-out;
      `;

      notification.textContent = message;
      document.body.appendChild(notification);

      // Remover después de 3 segundos
      setTimeout(() => {
        if (notification.parentNode) {
          notification.style.animation = 'slideOut 0.3s ease-in forwards';
          setTimeout(() => {
            if (notification.parentNode) {
              notification.parentNode.removeChild(notification);
            }
          }, 300);
        }
      }, 3000);
    }
  }
}
</script>

<style scoped>
/* Botón flotante mejorado - A la derecha */
.sidebar-toggle-fab {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  border: 2px solid rgba(255, 255, 255, 0.15);
  color: white;
  font-size: 20px;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(0, 116, 217, 0.3);
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-toggle-fab:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 6px 20px rgba(0, 116, 217, 0.4);
}

.sidebar-toggle-fab i {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.08);
  }
}

/* Contenedor del sidebar - Más delgado */
.sidebar-container {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 999;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.sidebar-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(3px);
  z-index: 1;
}

.sidebar-content {
  position: relative;
  z-index: 2;
  width: 92%;
  max-width: 850px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.97) 0%, rgba(255, 255, 255, 0.99) 100%);
  backdrop-filter: blur(15px);
  border-radius: 20px 20px 0 0;
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.12);
  overflow: hidden;
  border: 1px solid rgba(0, 116, 217, 0.15);
  border-bottom: none;
}

/* Barra superior - Más delgada */
.sidebar-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 18px;
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.user-badge {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 16px;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.25);
}

.user-info-compact {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name-short {
  font-size: 13px;
  font-weight: 600;
  color: white;
  line-height: 1;
}

.user-role-short {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.85);
  line-height: 1;
}

.hide-sidebar-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  font-size: 14px;
}

.hide-sidebar-btn:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.08);
}

/* Navegación mejorada - Más delgada y azul */
.navigation {
  padding: 12px 10px;
}

.navigation ul {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
  gap: 10px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.navigation li {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 12px 10px;
  background: white;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1.5px solid transparent;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.navigation li:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 16px rgba(0, 116, 217, 0.12);
  border-color: rgba(0, 116, 217, 0.25);
}

.navigation li.active {
  background: linear-gradient(135deg, #0074D9 0%, #0056a6 100%);
  border-color: transparent;
  box-shadow: 0 6px 16px rgba(0, 116, 217, 0.25);
}

.navigation li.active .nav-icon i {
  color: white;
}

.navigation li.active .nav-label {
  color: white;
  font-weight: 600;
}

.nav-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(0, 116, 217, 0.08) 0%, rgba(0, 86, 166, 0.08) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
  transition: all 0.3s ease;
}

.navigation li.active .nav-icon {
  background: rgba(255, 255, 255, 0.18);
}

.navigation li:hover .nav-icon {
  transform: scale(1.08);
}

.nav-icon i {
  font-size: 20px;
  color: #0074D9;
  transition: all 0.3s ease;
}

.nav-label {
  font-size: 11px;
  font-weight: 500;
  color: #1e293b;
  text-align: center;
  line-height: 1.2;
  transition: all 0.3s ease;
}

.nav-indicator {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 2.5px;
  background: linear-gradient(90deg, #0074D9 0%, #0056a6 100%);
  border-radius: 2.5px 2.5px 0 0;
  transition: width 0.3s ease;
}

.navigation li.active .nav-indicator {
  width: 55%;
}

/* Footer con logout - Más delgado */
.sidebar-footer {
  padding: 12px 18px;
  background: linear-gradient(180deg, rgba(249, 250, 251, 0) 0%, rgba(249, 250, 251, 1) 100%);
  border-top: 1px solid rgba(0, 0, 0, 0.04);
}

.logout-button-modern {
  width: 100%;
  padding: 11px 18px;
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  border: none;
  border-radius: 10px;
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 3px 10px rgba(239, 68, 68, 0.25);
}

.logout-button-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 14px rgba(239, 68, 68, 0.35);
}

.logout-button-modern:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.logout-button-modern i {
  font-size: 14px;
}

/* Animaciones */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: all 0.3s ease;
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.85);
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar-content {
    width: 96%;
    border-radius: 18px 18px 0 0;
  }

  .navigation ul {
    grid-template-columns: repeat(auto-fit, minmax(75px, 1fr));
    gap: 8px;
  }

  .navigation li {
    padding: 10px 8px;
  }

  .nav-icon {
    width: 38px;
    height: 38px;
    margin-bottom: 5px;
  }

  .nav-icon i {
    font-size: 18px;
  }

  .nav-label {
    font-size: 10px;
  }

  .sidebar-top {
    padding: 10px 14px;
  }

  .user-avatar {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }

  .user-name-short {
    font-size: 12px;
  }

  .user-role-short {
    font-size: 9px;
  }

  .sidebar-footer {
    padding: 10px 14px;
  }

  .logout-button-modern {
    padding: 10px 16px;
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .navigation ul {
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
  }

  .navigation li {
    padding: 8px 6px;
  }

  .nav-icon {
    width: 34px;
    height: 34px;
  }

  .nav-icon i {
    font-size: 16px;
  }

  .nav-label {
    font-size: 9px;
  }

  .sidebar-toggle-fab {
    width: 48px;
    height: 48px;
    font-size: 18px;
    bottom: 18px;
    right: 18px;
  }
}

/* Ocultar elementos antiguos */
.branding,
.user-info,
.toggle-button {
  display: none;
}
</style>
