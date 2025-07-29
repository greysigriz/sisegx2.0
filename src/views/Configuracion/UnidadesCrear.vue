<template>
    <div class="usuarios-container">
      <div class="card">
        <div class="card-header">
          <h3>Registrar Nueva Unidad</h3>
        </div>
        <div class="card-body">
          <form @submit.prevent="guardarUnidad">
            <div class="form-group">
              <label>Clave</label>
              <input v-model="unidad.clave" type="text" required />
            </div>
  
            <div class="form-group">
              <label>Nombre de la unidad</label>
              <input v-model="unidad.nombre_unidad" type="text" required />
            </div>
  
            <div class="form-group">
              <label>Estatus</label>
              <select v-model="unidad.estatus">
                <option value="ACTIVA">Activa</option>
                <option value="INACTIVA">Inactiva</option>
              </select>
            </div>
  
            <div class="form-group">
              <label>Nivel</label>
              <input v-model.number="unidad.nivel" type="number" />
            </div>
  
            <div class="form-group">
              <label>Tipo de cuenta</label>
              <input v-model="unidad.tipo_cuenta" type="text" />
            </div>
  
            <div class="form-group">
              <label>Periodo</label>
              <input v-model="unidad.periodo" type="text" />
            </div>
  
            <div class="form-group">
              <label>Abreviatura</label>
              <input v-model="unidad.abreviatura" type="text" />
            </div>
  
            <div class="form-group">
              <label>Siglas</label>
              <input v-model="unidad.siglas" type="text" />
            </div>
  
            <div class="form-actions">
              <button type="submit" class="btn-primary">Guardar</button>
              <button type="button" class="btn-secondary" @click="router.back()">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import axios from 'axios'
  import { useRouter } from 'vue-router'
  
  const router = useRouter()
  
  const unidad = ref({
    clave: '',
    nombre_unidad: '',
    estatus: 'ACTIVA',
    nivel: null,
    tipo_cuenta: '',
    periodo: '',
    abreviatura: '',
    siglas: ''
  })
  
  const guardarUnidad = async () => {
    try {
      await axios.post('/api/unidades', unidad.value)
      alert('Unidad registrada con éxito')
      router.push('/unidades')
    } catch (error) {
      console.error(error)
      alert('Error al guardar la unidad')
    }
  }
  </script>
  
  <style scoped>
  @import "@/assets/css/base.css"; /* Si tienes variables --color, asegúrate de importar el CSS base */
  
  .usuarios-container {
    padding: 20px;
  }
  
  .card {
    background-color: var(--white-color);
    border-radius: 8px;
    box-shadow: var(--shadow);
    overflow: hidden;
  }
  
  .card-header {
    padding: 20px;
    background-color: var(--white-color);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  }
  
  .card-header h3 {
    margin: 0;
    color: var(--secondary-color);
    font-size: 18px;
  }
  
  .card-body {
    padding: 20px;
  }
  
  .form-group {
    margin-bottom: 15px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--secondary-color);
  }
  
  .form-group input,
  .form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    font-size: 14px;
  }
  
  .form-actions {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }
  
  .btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 10px 15px;
    cursor: pointer;
    transition: var(--transition);
  }
  
  .btn-primary:hover {
    background-color: var(--secondary-color);
  }
  
  .btn-secondary {
    background-color: #f8f9fa;
    color: var(--secondary-color);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    padding: 10px 15px;
    cursor: pointer;
    transition: var(--transition);
  }
  </style>
  