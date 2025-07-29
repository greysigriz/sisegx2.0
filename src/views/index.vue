<template>
    <div id="app">
      <header>
        <h1>Bienvenido al Sistema de Seguimiento</h1>
        <p>Inicia sesión para continuar.</p>
      </header>
  
      <login></login> <!-- Componente de login que has creado -->
  
    </div>
  </template>
  
  <script>
  // Importar el componente de login (puedes agregar más componentes conforme los necesites)
  import Login from "@/components/Login.vue";
  
  export default {
    name: "App",
    components: {
      Login,
    },
  };
  </script>
  
  <style scoped>
  #app {
    font-family: 'Arial', sans-serif;
    text-align: center;
    margin-top: 50px;
  }
  
  header {
    background-color: #292c37;
    color: #fff;
    padding: 20px;
  }
  
  h1 {
    font-size: 2em;
  }
  
  p {
    font-size: 1.2em;
    color: #ccc;
  }
  </style>
  