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
      },
      {
        path: '/configuracion/notificaciones',
        name: 'Notificaciones por Email',
        component: () => import('@/components/ConfiguracionNotificaciones.vue'),
        meta: {
          requiresAuth: true,
          requiresRole: [9] // Solo rol Departamento (RolId = 9)
        }
      },
      {
        path: '/configuracion/gestion-notificaciones',
        name: 'Gestión de Notificaciones',
        component: () => import('@/components/GestionNotificaciones.vue'),
        meta: {
          requiresAuth: true,
          requiresRole: [1] // Solo Super Usuario (RolId = 1)
        }
      }

    ]
  }
]
