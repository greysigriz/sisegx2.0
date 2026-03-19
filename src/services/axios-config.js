// src/services/axios-config.js
import axios from 'axios';
import router from '@/router'


// Configurar Axios para enviar cookies con todas las peticiones
axios.defaults.withCredentials = true;
axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://localhost/SISEE/api/';
axios.defaults.timeout = 30000; // 30 segundos de timeout

// Variables para evitar múltiples redirects y requests
let isRedirecting = false;
let failedQueue = [];
let isRefreshing = false;
// ✅ NUEVO: Sistema de cancelación de requests pendientes
const pendingRequests = new Map();
const CancelToken = axios.CancelToken;

// Función para generar una clave única para cada request
function generateRequestKey(config) {
  const { method, url, params, data } = config;
  return `${method}:${url}:${JSON.stringify(params)}:${JSON.stringify(data)}`;
}

// Función para cancelar todos los requests pendientes
export function cancelAllPendingRequests() {
  console.log(`🧹 Cancelando ${pendingRequests.size} requests pendientes`);
  pendingRequests.forEach((cancel, key) => {
    cancel(`Request cancelado por cambio de ruta: ${key}`);
  });
  pendingRequests.clear();
}

// Función para cancelar requests de una ruta específica
export function cancelRequestsByRoute(route) {
  const toCancelCount = Array.from(pendingRequests.keys())
    .filter(key => key.includes(route))
    .length;

  if (toCancelCount > 0) {
    console.log(`🧹 Cancelando ${toCancelCount} requests de la ruta: ${route}`);
    pendingRequests.forEach((cancel, key) => {
      if (key.includes(route)) {
        cancel(`Request cancelado: cambio desde ${route}`);
        pendingRequests.delete(key);
      }
    });
  }
}
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
    // ✅ NUEVO: Excluir endpoint de imágenes del sistema anti-duplicados
    const isImageEndpoint = config.url && config.url.includes('imagenes.php');

    if (!isImageEndpoint) {
      // ✅ NUEVO: Generar CancelToken para este request (excepto imágenes)
      const requestKey = generateRequestKey(config);

      // Si ya existe un request idéntico pendiente, cancelarlo
      if (pendingRequests.has(requestKey)) {
        const cancel = pendingRequests.get(requestKey);
        cancel('Request duplicado cancelado');
        pendingRequests.delete(requestKey);
      }

      // Crear nuevo CancelToken
      config.cancelToken = new CancelToken((cancel) => {
        pendingRequests.set(requestKey, cancel);
      });
    }

    // Solo agregar timestamp para GET requests no-imagen
    if (config.method === 'get' && !isImageEndpoint) {
      config.params = {
        ...config.params,
        _t: Date.now()
      };
    }

    // ✅ AGREGAR headers de autenticación si existen (excepto si se especifica skipAuthToken)
    const token = localStorage.getItem('authToken');
    if (token && !config.skipAuthToken) {
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
    // ✅ NUEVO: Solo remover de la cola si no es endpoint de imágenes
    const isImageEndpoint = response.config.url && response.config.url.includes('imagenes.php');

    if (!isImageEndpoint) {
      const requestKey = generateRequestKey(response.config);
      pendingRequests.delete(requestKey);
    }

    return response;
  },
  async (error) => {
    // ✅ NUEVO: Manejar requests cancelados sin mostrar error
    if (axios.isCancel(error)) {
      console.log('Request cancelado:', error.message);
      return Promise.reject({ cancelled: true, message: error.message });
    }

    // ✅ NUEVO: Solo remover de la cola si no es endpoint de imágenes y hay config
    if (error.config) {
      const isImageEndpoint = error.config.url && error.config.url.includes('imagenes.php');
      if (!isImageEndpoint) {
        const requestKey = generateRequestKey(error.config);
        pendingRequests.delete(requestKey);
      }
    }

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
      console.error('[NETWORK] Timeout de conexión. Por favor, intente nuevamente.');
      return Promise.reject(error);
    }

    // Manejar errores de red
    if (!error.response) {
      console.error('[NETWORK] Error de conexión con el servidor. Verifique su conexión a internet.');
      return Promise.reject(error);
    }

    // Manejar errores del servidor
    if (error.response?.status >= 500) {
      console.error('[SERVER] Error del servidor:', error.response.status, error.response.data);
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
          router.replace('/login');
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

// Función para manejar sesión expirada (solo consola)
function showSessionExpiredMessage() {
  console.warn('[AUTH] Su sesión ha expirado. Redirigiendo al login...');
}

document.addEventListener('visibilitychange', () => {
  if (!document.hidden) {
    setTimeout(() => {
      window.dispatchEvent(new Event('resize'))
    }, 50)
  }
})


export default axios;
