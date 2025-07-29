// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import AuthService from '../services/auth'

// Importa automáticamente todos los archivos en /routes (si es necesario)
const routeModules = import.meta.glob('./routes/*.js', { eager: true })
const routes = Object.values(routeModules).flatMap(m => m.default)

// Importar la vista para la página de peticiones
import PetitionPage from '../views/PetitionPage.vue'

// Definir explícitamente la ruta '/petition'
routes.push({
  path: '/petition',
  name: 'PetitionPage',
  component: PetitionPage
})

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guard de navegación CORREGIDO
router.beforeEach((to, from, next) => {
  // ✅ PÁGINAS PÚBLICAS (NO requieren autenticación)
  const publicPages = ['/login', '/register', '/recuperar-password', '/petition']
  const authRequired = !publicPages.includes(to.path)
  
  console.log('Router Guard:', {
    to: to.path,
    authRequired,
    isAuthenticated: AuthService.isAuthenticated()
  })
  
  // ✅ SI NO SE REQUIERE AUTENTICACIÓN, PERMITIR ACCESO
  if (!authRequired) {
    // Si está autenticado e intenta ir a login, redirigir al dashboard
    if (AuthService.isAuthenticated() && to.path === '/login') {
      return next('/bienvenido')
    }
    // Para otras páginas públicas, permitir acceso
    return next()
  }
  
  // ✅ SI SE REQUIERE AUTENTICACIÓN, VERIFICAR
  const isAuthenticated = AuthService.isAuthenticated()
  
  if (!isAuthenticated) {
    console.log('Usuario no autenticado, redirigiendo a login')
    return next('/login')
  }
  
  // ✅ VERIFICAR VALIDEZ DE SESIÓN (más suave)
  const isSessionValid = AuthService.checkSessionValidity()
  if (!isSessionValid) {
    console.log('Sesión expirada, limpiando y redirigiendo')
    // Limpiar datos sin hacer logout completo
    AuthService.clearAllData()
    return next('/login?expired=true')
  }
  
  // ✅ TODO OK, PERMITIR ACCESO
  next()
})

export default router