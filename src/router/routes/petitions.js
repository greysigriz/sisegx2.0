export default [
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        {
          path: 'petitions',
          name: 'Petitions',
          component: () => import('@/views/PetitionPage.vue'),
        }
      ]
    }
  ]