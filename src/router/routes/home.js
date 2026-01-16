// src/router/routes/home.js
export default [
  {
    path: '/',
    redirect: to => {
      // Si está autenticado, redirigir a bienvenido
      const userData = localStorage.getItem('user');
      return userData ? '/bienvenido' : '/login';
    }
  }
]
