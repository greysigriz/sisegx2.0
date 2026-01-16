// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import AuthService from '../services/auth'

// Importa automáticamente todos los archivos en /routes (si es necesario)
const routeModules = import.meta.glob('./routes/*.js', { eager: true })
const routes = Object.values(routeModules).flatMap(m => m.default)

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0, behavior: 'smooth' }
  }
})

// ✅ CORREGIDO: Router guard más robusto para evitar loops
router.beforeEach(async (to, from, next) => {
  console.log('Router Guard:', { to: to.path, from: from.path });

  // Evitar loops - si ya estamos en la misma ruta, continuar
  if (to.path === from.path) {
    return next();
  }

  const publicPages = ['/login', '/register', '/recuperar-password', '/'];
  const authRequired = !publicPages.includes(to.path);
  const isAuthenticated = AuthService.isAuthenticated();

  // Si requiere autenticación y no está autenticado
  if (authRequired && !isAuthenticated) {
    console.log('🔒 Redirigiendo a login - no autenticado');

    // Evitar loop infinito
    if (to.path === '/login') {
      return next();
    }

    return next({
      path: '/login',
      query: { redirect: to.fullPath }
    });
  }

  // Si está autenticado y trata de ir a login, redirigir a bienvenido
  if (isAuthenticated && to.path === '/login') {
    console.log('✅ Ya autenticado, redirigiendo a bienvenido');
    return next('/bienvenido');
  }

  // Verificar permisos si la ruta los requiere
  if (to.meta && to.meta.requiredPermission) {
    const hasPermission = AuthService.hasPermission(to.meta.requiredPermission);
    if (!hasPermission) {
      console.log('🚫 Sin permisos para:', to.path);
      return next('/bienvenido');
    }
  }

  next();
});

export default router
