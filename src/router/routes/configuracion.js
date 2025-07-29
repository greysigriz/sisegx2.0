//C:\xampp\htdocs\SISE\src\router\routes\configuracion.js
export default [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    children: [
      {
        path: 'configuracion',
        name: 'Configuración',
        component: () => import('@/views/Configuracion/index.vue'),
      },
      {
        path: 'configuracion/usuarios',
        name: 'Configuración de Usuarios',
        component: () => import('@/views/Configuracion/Usuarios.vue'),
      },
      {
        path: '/configuracion/Unidadescrear',
        name: 'Unidadescrear',
        component: () => import('@/views/Configuracion/UnidadesCrear.vue')
      },
      {
        path: '/configuracion/roles',
        name: 'Configuración de roles',
        component: () => import('@/views/Configuracion/Roles.vue')
      }
      
    ]
  }
]