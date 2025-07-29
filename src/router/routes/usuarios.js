export default [
    {
      path: '/usuarios',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        {
          path: 'usuarios',
          name: 'Usuarios',
          component: () => import('@/views/Usuarios.vue'),
        }
      ]
    }
  ]
  