// C:\xampp\htdocs\SISE\src\routes/bienvenido.js
export default [
  {
    path: '/bienvenido', // Cambia la ruta a '/dashboard'
    component: () => import('@/layouts/MainLayout.vue'),
    children: [
      {
        path: '', // Esta ruta está vacía para que cargue el Dashboard por defecto dentro de /dashboard
        name: 'Bienvenido',
        component: () => import('@/views/Bienvenido.vue'),
      }
    ]
  }
]
