<!-- -->
<template>
  <div class="app-container">

    <Sidebar />


    <main class="main-content">
      <header class="mainlayout-page-header">
        <div class="mainlayout-breadcrumb">
          <h2 class="mainlayout-page-title">{{ pageTitle }}</h2>
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

<style scoped>
/* Estilos únicos de MainLayout con prefijo mainlayout- */

.app-container {
  display: flex;
  height: 100vh;
  width: 100%;
  overflow: hidden;
  background-color: #f8fafc;
}

.main-content {
  flex: 1;
  height: 100vh;
  overflow-y: auto;
  padding: 0;
}

.mainlayout-page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0;
  padding: 20px 20px 15px 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  background-color: #ffffff;
}

.mainlayout-breadcrumb {
  flex: 1;
}

.mainlayout-page-title {
  font-size: 22px;
  font-weight: 600;
  color: #1654b1;
  margin: 0;
  padding: 0;
}

@media (max-width: 768px) {
  .app-container {
    flex-direction: column;
  }

  .main-content {
    height: auto;
  }

  .mainlayout-page-header {
    padding: 15px;
  }

  .mainlayout-page-title {
    font-size: 18px;
  }
}
</style>
