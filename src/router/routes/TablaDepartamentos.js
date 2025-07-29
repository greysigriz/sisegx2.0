export default [
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        {
          path: 'departamentos',
          name: 'Departamentos',
          component: () => import('@/views/TablaDepartamentos.vue'),
        }
      ]
    }
  ]