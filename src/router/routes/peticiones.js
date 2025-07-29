export default [
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        {
          path: 'peticiones',
          name: 'Peticiones',
          component: () => import('@/views/Peticiones.vue'),
        }
      ]
    }
  ]
  