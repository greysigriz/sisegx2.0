export default [
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        {
          path: 'tablero',
          name: 'Tablero',
          component: () => import('@/views/TableroDash.vue'),
        }
      ]
    }
  ]