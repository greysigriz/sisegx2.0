// src/services/auth.js
import axios from './axios-config';

class AuthService {
  constructor() {
    this.sessionCheckInterval = null;
    this.isCheckingSession = false;
    this.lastActivity = Date.now();
    this.SESSION_TIMEOUT = 8 * 60 * 60 * 1000; // 8 horas en millisegundos
    this.INACTIVITY_TIMEOUT = 30 * 60 * 1000; // 30 minutos de inactividad
    this.CHECK_INTERVAL = 2 * 60 * 1000; // Verificar cada 2 minutos
    
    // Variables para evitar múltiples redirects
    this.isRedirecting = false;
    this.isDestroyed = false;
    this.serverCheckCounter = 0;
    
    // ✅ SOLO inicializar seguimiento si estamos autenticados
    if (this.isAuthenticated()) {
      this.initActivityTracking();
      this.initSessionCheck();
    }
  }

  checkSessionValidity() {
    const loginTime = localStorage.getItem('loginTime');
    const now = Date.now();
    
    if (!loginTime) return false;

    const sessionAge = now - parseInt(loginTime, 10);
    return sessionAge <= this.SESSION_TIMEOUT;
  }

  // ✅ Inicializar seguimiento de actividad del usuario
  initActivityTracking() {
    if (this.isDestroyed) return;
    
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    
    let throttleTimer = null;
    const throttledUpdate = () => {
      if (throttleTimer) return;
      throttleTimer = setTimeout(() => {
        this.updateLastActivity();
        throttleTimer = null;
      }, 10000); // Solo actualizar cada 10 segundos
    };
    
    events.forEach(event => {
      document.addEventListener(event, throttledUpdate, { passive: true });
    });
  }

  // Actualizar timestamp de última actividad
  updateLastActivity() {
    if (this.isDestroyed) return;
    this.lastActivity = Date.now();
    localStorage.setItem('lastActivity', this.lastActivity.toString());
  }

  // ✅ Inicializar verificación periódica de sesión
  initSessionCheck() {
    if (this.isDestroyed || this.sessionCheckInterval) return;
    
    // Solo iniciar si hay una sesión válida
    if (!this.isAuthenticated()) return;
    
    this.sessionCheckInterval = setInterval(() => {
      if (!this.isDestroyed) {
        this.performSessionCheck();
      }
    }, this.CHECK_INTERVAL);
  }

  // ✅ Realizar verificación de sesión (menos agresiva)
  async performSessionCheck() {
    if (this.isCheckingSession || this.isDestroyed || this.isRedirecting) return;
    
    try {
      this.isCheckingSession = true;
      
      // Verificar si todavía estamos autenticados localmente
      if (!this.isAuthenticated()) {
        this.cleanup();
        return;
      }
      
      // ✅ SOLO verificar inactividad si estamos en una página protegida
      const currentPath = window.location.pathname;
      const publicPages = ['/login', '/register', '/recuperar-password', '/petition'];
      const isOnPublicPage = publicPages.includes(currentPath);
      
      if (!isOnPublicPage) {
        const now = Date.now();
        const lastActivityTime = localStorage.getItem('lastActivity');
        const timeSinceLastActivity = lastActivityTime ? 
          now - parseInt(lastActivityTime) : 
          now - this.lastActivity;
        
        if (timeSinceLastActivity > this.INACTIVITY_TIMEOUT) {
          console.log('Sesión cerrada por inactividad');
          await this.forceLogout('Sesión cerrada por inactividad');
          return;
        }
      }

      // ✅ Verificar con servidor menos frecuentemente
      if (!this.serverCheckCounter) this.serverCheckCounter = 0;
      this.serverCheckCounter++;
      
      if (this.serverCheckCounter >= 5) {
        this.serverCheckCounter = 0;
        
        try {
          const sessionData = await this.checkSession();
          
          if (!sessionData.success) {
            console.log('Sesión no válida en el servidor');
            await this.forceLogout('Su sesión ha expirado');
          }
        } catch (serverError) {
          // Solo forzar logout si es un error de autenticación
          if (serverError.response?.status === 401) {
            await this.forceLogout('Su sesión ha expirado');
          }
        }
      }
      
    } catch (error) {
      console.error('Error en verificación de sesión:', error);
      if (error.response?.status === 401) {
        await this.forceLogout('Su sesión ha expirado');
      }
    } finally {
      this.isCheckingSession = false;
    }
  }

  // ✅ Método para iniciar sesión MEJORADO
  async login(usuario, password) {
    try {
      // Asegurarse de que no hay requests pendientes
      this.cleanup();
      
      console.log('Iniciando login para usuario:', usuario);
      
      const response = await axios.post('login.php', { 
        usuario: usuario.trim(), 
        password: password 
      });
      
      console.log('Respuesta del servidor:', response.data);
      
      if (response.data.success) {
        console.log('Login exitoso, guardando datos...');
        
        // Guardar datos del usuario en localStorage
        const userData = response.data.user;
        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('authToken', Date.now().toString());
        localStorage.setItem('loginTime', Date.now().toString());
        
        // Guardar session_id si se proporciona
        if (response.data.session_id) {
          localStorage.setItem('sessionId', response.data.session_id);
        }
        
        this.updateLastActivity();
        
        // Reset flags
        this.isRedirecting = false;
        this.isDestroyed = false;
        
        // ✅ Inicializar servicios DESPUÉS del login exitoso
        setTimeout(() => {
          this.initActivityTracking();
          this.initSessionCheck();
        }, 100);
        
        console.log('Login completado exitosamente');
      } else {
        console.log('Login fallido:', response.data.message);
      }
      
      return response.data;
    } catch (error) {
      console.error('Error en login:', error);
      
      // Manejar errores específicos
      if (error.response) {
        const errorData = error.response.data;
        throw new Error(errorData.message || 'Error de autenticación');
      } else if (error.request) {
        throw new Error('No se pudo conectar con el servidor');
      } else {
        throw new Error('Error desconocido: ' + error.message);
      }
    }
  }

  // ✅ Método mejorado para cerrar sesión
  async logout() {
    if (this.isDestroyed) return;
    
    try {
      this.cleanup();
      
      // Intentar cerrar sesión en el servidor
      await axios.post('logout.php');
      
    } catch (error) {
      console.error('Error en logout del servidor:', error);
    } finally {
      // Siempre limpiar datos locales
      this.clearAllData();
    }
  }

  // ✅ Forzar logout (MÁS SUAVE para evitar loops)
  async forceLogout(reason = 'Sesión terminada') {
    if (this.isRedirecting || this.isDestroyed) return;
    
    try {
      this.isRedirecting = true;
      this.cleanup();
      
      // Limpiar datos locales
      this.clearAllData();

      // ✅ SOLO mostrar mensaje y redirigir si NO estamos en una página pública
      const currentPath = window.location.pathname;
      const publicPages = ['/login', '/register', '/recuperar-password', '/petition'];
      const isOnPublicPage = publicPages.includes(currentPath);
      
      if (!isOnPublicPage) {
        this.showLogoutMessage(reason);
        
        setTimeout(() => {
          if (!this.isDestroyed) {
            window.location.href = '/login';
          }
        }, 1500);
      } else {
        // Si ya estamos en una página pública, solo limpiar
        this.isRedirecting = false;
      }
      
    } catch (error) {
      console.error('Error en forceLogout:', error);
      // Solo redirigir si no estamos en login
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login';
      }
    }
  }

  // Limpiar intervals y flags
  cleanup() {
    if (this.sessionCheckInterval) {
      clearInterval(this.sessionCheckInterval);
      this.sessionCheckInterval = null;
    }
    this.serverCheckCounter = 0;
  }

  // Mostrar mensaje de logout
  showLogoutMessage(message) {
    // Evitar múltiples notificaciones
    if (document.querySelector('.logout-notification')) return;
    
    const notification = document.createElement('div');
    notification.className = 'logout-notification';
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #f44336;
      color: white;
      padding: 15px 20px;
      border-radius: 5px;
      z-index: 10000;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      font-family: Arial, sans-serif;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
  }

  // Verificar sesión en el servidor
  async checkSession() {
    try {
      const response = await axios.get('check-session.php');
      return response.data;
    } catch (error) {
      if (error.response?.status === 401) {
        return { success: false, message: 'Sesión no válida' };
      }
      throw error;
    }
  }

  // ✅ Verificar si hay un usuario autenticado (mejorado)
  isAuthenticated() {
    const user = localStorage.getItem('user');
    const token = localStorage.getItem('authToken');
    const loginTime = localStorage.getItem('loginTime');
    
    if (!user || !token || !loginTime) {
      return false;
    }

    // ✅ Verificar si la sesión ha expirado por tiempo (menos estricto)
    const now = Date.now();
    const sessionAge = now - parseInt(loginTime);
    
    if (sessionAge > this.SESSION_TIMEOUT) {
      // Solo limpiar, no forzar logout aquí para evitar loops
      this.clearAllData();
      return false;
    }

    return true;
  }

  // ✅ Obtener datos del usuario actual (con validación mejorada)
  getCurrentUser() {
    if (!this.isAuthenticated()) {
      return null;
    }
    
    try {
      const userData = localStorage.getItem('user');
      if (!userData) return null;
      
      const parsedUser = JSON.parse(userData);
      
      // ✅ Validar estructura mínima del usuario
      if (!parsedUser || typeof parsedUser !== 'object') {
        console.warn('Datos de usuario inválidos en localStorage');
        this.clearAllData();
        return null;
      }
      
      // ✅ Asegurar estructura mínima para evitar errores en el frontend
      return {
        usuario: parsedUser.usuario || {},
        rol: parsedUser.rol || { nombre: 'Usuario' },
        permisos: Array.isArray(parsedUser.permisos) ? parsedUser.permisos : [],
        unidades: Array.isArray(parsedUser.unidades) ? parsedUser.unidades : [],
        ...parsedUser
      };
      
    } catch (error) {
      console.error('Error al parsear datos del usuario:', error);
      this.clearAllData();
      return null;
    }
  }

  // ✅ Limpiar todos los datos locales
  clearAllData() {
    const keysToRemove = [
      'user',
      'authToken', 
      'loginTime',
      'lastActivity',
      'sessionId',
      'sidebarCollapsed'
    ];
    
    keysToRemove.forEach(key => {
      localStorage.removeItem(key);
    });
    
    sessionStorage.clear();
  }

  // Verificar si el usuario tiene un permiso específico
  hasPermission(permission) {
    const user = this.getCurrentUser();
    
    if (!user || !user.permisos) {
      return false;
    }
    
    return user.permisos.includes(permission);
  }

  // Verificar si el usuario pertenece a una unidad específica
  belongsToUnit(unitId) {
    const user = this.getCurrentUser();
    
    if (!user || !user.unidades) {
      return false;
    }
    
    return user.unidades.some(unit => unit.unidad_id === unitId);
  }

  // Verificar si el usuario tiene un rol específico
  hasRole(rolId) {
    const user = this.getCurrentUser();
    
    if (!user || !user.rol) {
      return false;
    }
    
    return user.rol.id === rolId;
  }

  // Sincronizar datos del usuario con el servidor
  async syncUserData() {
    try {
      if (!this.isAuthenticated()) return null;
      
      const sessionData = await this.checkSession();
      if (sessionData.success && sessionData.user) {
        localStorage.setItem('user', JSON.stringify(sessionData.user));
        this.updateLastActivity();
        return sessionData.user;
      }
      return null;
    } catch (error) {
      console.error('Error al sincronizar datos del usuario:', error);
      return null;
    }
  }

  // Cleanup al destruir el servicio
  destroy() {
    this.isDestroyed = true;
    this.cleanup();
  }
}

export default new AuthService();