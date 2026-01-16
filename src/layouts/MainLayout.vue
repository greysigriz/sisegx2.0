<!-- -->
<template>
  <div class="app-container">

    <Sidebar />


    <main class="main-content">
      <header class="page-header">
        <div class="breadcrumb">
          <h2>{{ pageTitle }}</h2>
        </div>
        <!-- <div class="header-actions">
          <div class="date-display">{{ currentDate }}</div>
          <button class="action-button"><i class="fas fa-bell"></i></button>
          <button class="action-button"><i class="fas fa-cog"></i></button>
        </div> -->
      </header>

      <router-view />
    </main>
  </div>
</template>


<script>

import Sidebar from '@/components/Sidebar.vue';
import { useRouter } from 'vue-router';
import { onMounted } from 'vue';

export default {
  name: 'MainLayout',
  components: {
    Sidebar
  },
  setup() {
    const router = useRouter();

    onMounted(() => {
      // Verificar si el usuario está autenticado al cargar el componente
      const userData = localStorage.getItem('user');
      if (!userData) {
        router.push('/login');
      }
    });

    return { router };
  },
  data() {
    // Formatear la fecha actual
    const today = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = today.toLocaleDateString('es-ES', options);

    return {
      currentDate: formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1)
    };
  },
  computed: {
    pageTitle() {
      // Obtener el título según la ruta actual
      const routeName = this.$route.name;
      return routeName || 'Dashboard';
    }
  }
};

</script>

<!-- Estilos movidos a App.vue -->
