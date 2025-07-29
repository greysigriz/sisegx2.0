// src/services/axios-config.js
import axios from 'axios';

// Configurar Axios para enviar cookies con todas las peticiones
axios.defaults.withCredentials = true;
axios.defaults.baseURL = 'http://localhost/SISE/api/';
axios.defaults.timeout = 15000; // 15 segundos de timeout

// Variables para evitar múltiples redirects y requests
let isRedirecting = false;
let failedQueue = [];
let isRefreshing = false;

// Función para procesar la cola de requests fallidos
const processQueue = (error, token = null) => {
  failedQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error);
    } else {
      resolve(token);
    }
  });
  
  failedQueue = [];
};

// Interceptor para manejar requests
axios.interceptors.request.use(
  (config) => {
    // Solo agregar timestamp para GET requests
    if (config.method === 'get') {
      config.params = {
        ...config.params,
        _t: Date.now()
      };
    }
    
    // ✅ AGREGAR headers de autenticación si existen
    const token = localStorage.getItem('authToken');
    if (token) {
      // Usar el nombre correcto del header (minúsculas con guiones)
      config.headers['X-Auth-Token'] = token;
    }
    
    // ✅ Asegurar que Content-Type esté configurado para requests POST/PUT
    if (['post', 'put', 'patch'].includes(config.method) && !config.headers['Content-Type']) {
      config.headers['Content-Type'] = 'application/json';
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor mejorado para manejar responses
axios.interceptors.response.use(
  (response) => {
    return response;
  },
  async (error) => {
    const originalRequest = error.config;
    
    console.error('Axios Error:', error);
    
    // Manejar errores de autenticación
    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        // Si ya estamos refrescando, agregar a la cola
        return new Promise(function(resolve, reject) {
          failedQueue.push({ resolve, reject });
        }).then(token => {
          originalRequest.headers['X-Auth-Token'] = token;
          return axios(originalRequest);
        }).catch(err => {
          return Promise.reject(err);
        });
      }

      originalRequest._retry = true;
      isRefreshing = true;

      // Intentar manejar el error de autenticación
      try {
        await handleAuthError();
        processQueue(error, null);
        return Promise.reject(error);
      } catch (refreshError) {
        processQueue(refreshError, null);
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }
    
    // Manejar errores de conexión y timeout
    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
      console.error('Timeout de conexión');
      showNetworkError('Timeout de conexión. Por favor, intente nuevamente.');
      return Promise.reject(error);
    }
    
    // Manejar errores de red
    if (!error.response) {
      console.error('Error de red');
      showNetworkError('Error de conexión con el servidor. Verifique su conexión a internet.');
      return Promise.reject(error);
    }
    
    // Manejar errores del servidor
    if (error.response?.status >= 500) {
      showNetworkError('Error del servidor. Por favor, intente más tarde.');
    }
    
    return Promise.reject(error);
  }
);

// Función mejorada para manejar errores de autenticación
async function handleAuthError() {
  if (isRedirecting) return;
  
  isRedirecting = true;
  
  try {
    // Limpiar datos locales
    const keysToRemove = ['user', 'authToken', 'loginTime', 'lastActivity'];
    keysToRemove.forEach(key => {
      localStorage.removeItem(key);
    });
    
    // Mostrar mensaje de sesión expirada solo si no estamos en login
    if (!window.location.pathname.includes('/login')) {
      showSessionExpiredMessage();
      
      // Redirigir al login después de un breve delay
      setTimeout(() => {
        if (!window.location.pathname.includes('/login')) {
          window.location.href = '/login';
        }
        isRedirecting = false;
      }, 2000);
    } else {
      isRedirecting = false;
    }
  } catch (error) {
    console.error('Error en handleAuthError:', error);
    isRedirecting = false;
  }
}

// Función para mostrar mensaje de sesión expirada
function showSessionExpiredMessage() {
  // Evitar múltiples notificaciones
  if (document.querySelector('.session-expired-notification')) return;
  
  const notification = document.createElement('div');
  notification.className = 'session-expired-notification';
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #ff5722;
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    z-index: 10000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-family: Arial, sans-serif;
    font-size: 14px;
    max-width: 300px;
    animation: slideInRight 0.3s ease-out;
  `;
  
  // Agregar animación CSS
  if (!document.querySelector('#notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
      @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
    `;
    document.head.appendChild(style);
  }
  
  notification.innerHTML = `
    <div style="display: flex; align-items: center;">
      <div style="margin-right: 10px;">⚠️</div>
      <div>Su sesión ha expirado. Redirigiendo al login...</div>
    </div>
  `;
  
  document.body.appendChild(notification);
  
  // Remover después de 3 segundos
  setTimeout(() => {
    if (notification.parentNode) {
      notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }
  }, 3000);
}

// Función para mostrar errores de red
function showNetworkError(message) {
  // Evitar múltiples notificaciones del mismo tipo
  if (document.querySelector('.network-error-notification')) return;
  
  const notification = document.createElement('div');
  notification.className = 'network-error-notification';
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #f44336;
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    z-index: 10000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-family: Arial, sans-serif;
    font-size: 14px;
    max-width: 300px;
    animation: slideInRight 0.3s ease-out;
  `;
  
  notification.innerHTML = `
    <div style="display: flex; align-items: center;">
      <div style="margin-right: 10px;">🌐</div>
      <div>${message}</div>
    </div>
  `;
  
  document.body.appendChild(notification);
  
  // Remover después de 4 segundos
  setTimeout(() => {
    if (notification.parentNode) {
      notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }
  }, 4000);
}

// Función para limpiar notificaciones existentes
export const clearNotifications = () => {
  const notifications = document.querySelectorAll('.session-expired-notification, .network-error-notification');
  notifications.forEach(notification => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  });
};

export default axios;