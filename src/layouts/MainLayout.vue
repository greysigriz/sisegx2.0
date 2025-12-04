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

<style>


@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');

:root {
  --primary-color: #1630b1;
  --secondary-color: #292c37;
  --dark-color: #000000;
  --accent-color: #9f111b;
  --light-color: #cccccc;
  --background-color: #f5f7fa;
  --white-color: #ffffff;
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s ease;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', sans-serif;
}

.app-container {
  display: flex;
  height: 100vh;
  width: 100%;
  overflow: hidden;
  background-color: var(--background-color);
}


.main-content {
  flex: 1;
  height: 100vh;
  overflow-y: auto;
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  height: 35px;
}

.breadcrumb h2 {
  font-size: 22px;
  font-weight: 600;
  color: var(--secondary-color);
}

.header-actions {
  display: flex;
  align-items: center;
}

.date-display {
  margin-right: 15px;
  color: var(--secondary-color);
  font-size: 14px;
}

.action-button {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: #000000;
  color: #ffffff;
  border: none;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  margin-left: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
}

.action-button:hover {
  background-color: var(--light-color);
}


@media (max-width: 768px) {
  .app-container {
    flex-direction: column;
  }

  .main-content {
    height: auto;
  }
}

</style>
