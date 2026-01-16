export default [
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        {
          path: 'petition',
          name: 'PetitionPage',
          component: () => import('@/views/PetitionPage.vue'),
          meta: { requiresAuth: true, requiredPermission: 'peticiones_formulario' }
        },
        {
          path: 'petitions',
          name: 'Petitions',
          component: () => import('@/views/PetitionPage.vue'),
        }
      ]
    }
  ]
