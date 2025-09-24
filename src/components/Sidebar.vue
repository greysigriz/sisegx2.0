<template>
  <div class="sidebar-container" :class="{ 'collapsed': isCollapsed }">
    <aside class="sidebar">
      <div class="branding">
        <h1 class="logo" :class="{ 'fade-scale': !isCollapsed }">{{ isCollapsed ? '' : 'SISEGX' }}</h1>
        <p class="tagline" v-if="!isCollapsed" :class="{ 'fade-in': !isCollapsed }">Sistema de Trámites</p>
      </div>

      <div class="user-info" :class="{ 'collapsed': isCollapsed }">
        <div class="avatar" :class="{ 'pulse': !isCollapsed }">{{ userInitial }}</div>
        <div class="user-details slide-fade" v-if="!isCollapsed">
          <p class="user-name">{{ userName }}</p>
          <p class="user-role">{{ userRole }}</p>
        </div>
      </div>

      <nav class="navigation">
        <ul v-if="userReady">
          <li v-for="(item, index) in filteredMenuItems"
              :key="`menu-${item.name}-${index}`"
              :class="{ active: isActive(item.path) }"
              @click="navigateTo(item.path)"
              :style="{ transitionDelay: !isCollapsed ? `${index * 50}ms` : '0ms' }">
            <i :class="[item.icon, isCollapsed ? 'icon-centered' : '']"></i>
            <span v-if="!isCollapsed" class="menu-text">{{ item.label }}</span>
          </li>
        </ul>
      </nav>

      <div class="logout-container slide-up" v-if="!isCollapsed">
        <button class="logout-button" @click="handleLogout" :disabled="isLoggingOut">
          <i class="fas fa-sign-out-alt"></i>
          {{ isLoggingOut ? 'Cerrando...' : 'Cerrar sesión' }}
        </button>
      </div>
    </aside>

    <button class="toggle-button" @click="toggleSidebar" :class="{ 'rotate': isCollapsed }">
      <i :class="isCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left'"></i>
    </button>
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
      allMenuItems: [
        { name: 'Inicio', label: 'Bienvenido', icon: 'fas fa-chart-line', path: '/bienvenido', requiredPermission: 'ver_dashboard' },
        { name: 'peticiones', label: 'Peticiones', icon: 'fas fa-tasks', path: '/peticiones', requiredPermission: 'admin_peticiones' },
        { name: 'petitions', label: 'Petitions', icon: 'fas fa-user-check', path: '/petitions', requiredPermission: 'admin_peticiones' },
        { name: 'configuracion', label: 'Configuración', icon: 'fas fa-cog', path: '/configuracion', requiredPermission: 'configuracion_sistema' },
        { name: 'departamentos', label: 'Departamentos', icon: 'fas fa-users', path: '/departamentos', requiredPermission: 'ver_departamentos' },
        { name: 'tablero', label: 'Tablero', icon: 'fas fa-th-large', path: '/tablero', requiredPermission: 'ver_tablero' },
      ],
      currentUser: null,
      isLoadingUser: false,
      retryCount: 0,
      maxRetries: 3,
      authCheckInterval: null,
      isInitialized: false,
      userReady: false // <-- NUEVO
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
        const apellido = usuario.ApellidoP || '';
        return `${usuario.Nombre} ${apellido}`.trim();
      }

      if (usuario.Usuario && typeof usuario.Usuario === 'string') {
        return usuario.Usuario;
      }

      return 'Usuario';
    },
    userRole() {
      if (!this.currentUser || !this.currentUser.rol) return 'Usuario';

      return this.currentUser.rol.nombre || 'Usuario';
    },
    filteredMenuItems() {
      if (!this.currentUser || !Array.isArray(this.currentUser.permisos)) {
        return [];
      }

      // Filtrar elementos del menú según los permisos del usuario
      return this.allMenuItems.filter(item => {
        return this.currentUser.permisos.includes(item.requiredPermission);
      });
    }
  },
  async created() {
    // Inicializar componente de forma segura
    await this.initializeComponent();
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

    toggleSidebar() {
      this.isCollapsed = !this.isCollapsed;
      // Guardar la preferencia del usuario solo si hay una sesión válida
      if (this.currentUser) {
        localStorage.setItem('sidebarCollapsed', this.isCollapsed);
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
/* Sidebar en la parte inferior para todas las resoluciones */
.sidebar-container {
  width: 100%;
  height: auto;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  top: auto;
  z-index: 1000;
  margin-bottom: 20px;
}

.sidebar {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  height: 60px;
  width: 70%;
  padding: 0 1rem;
  background: rgb(4, 106, 189);
  /* backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px); */
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
  border-radius: 10px;
  margin: 0 auto;
}

.navigation {
  flex: 1;
  padding: 0;
  height: 100%;
}

.navigation ul {
  display: flex;
  justify-content: space-around;
  align-items: center;
  height: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
}

.navigation li {
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0 8px;
  margin: 0;
  font-size: 11px;
  height: 100%;
  display: flex;
  cursor: pointer;
  color: white; /* ✅ Cambiado a blanco */
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.navigation li:hover {
  background-color: rgba(255, 255, 255, 0.2); /* ✅ Hover con blanco transparente */
  color: white; /* ✅ Mantener blanco en hover */
}

.navigation li.active {
  background-color: rgba(255, 255, 255, 0.3); /* ✅ Active con blanco transparente */
  color: white; /* ✅ Mantener blanco cuando está activo */
  font-weight: 500;
}

.navigation li i {
  margin: 0;
  font-size: 18px;
  color: white; /* ✅ Iconos específicamente en blanco */
}

.menu-text {
  display: none;
}

/* Mostrar el botón de logout como ícono */
.logout-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.logout-button {
  background: none;
  border: none;
  color: white; /* ✅ Botón logout en blanco */
  font-size: 18px;
  cursor: pointer;
  padding: 0 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: all 0.3s ease; /* ✅ Transición suave */
}

.logout-button i {
  font-size: 18px;
  color: white; /* ✅ Icono logout específicamente en blanco */
}

.logout-button:hover {
  color: rgba(255, 255, 255, 0.8); /* ✅ Hover con blanco ligeramente transparente */
  background-color: rgba(255, 255, 255, 0.1); /* ✅ Fondo sutil en hover */
  border-radius: 4px;
}

.logout-button:hover i {
  color: rgba(255, 255, 255, 0.8); /* ✅ Icono en hover */
}

/* Ocultar otros elementos no necesarios */
.branding,
.user-info,
.toggle-button {
  display: none;
}

/* ✅ Asegurar que todos los iconos sean blancos */
.sidebar i {
  color: white !important;
}

/* ✅ Estados de hover y active más consistentes */
.navigation li:hover i,
.navigation li.active i {
  color: white !important;
}
</style>
