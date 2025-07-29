export default [
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        {
          path: 'reportes',
          name: 'Reportes',
          component: () => import('@/views/Reportes.vue'),
        }
      ]
    }
  ]
  