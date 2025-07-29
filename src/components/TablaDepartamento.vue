<template>
  <div class="tabla-peticiones">
    <h3>Peticiones recientes</h3>

    <div v-if="loading" class="loading-message">
      Cargando peticiones...
    </div>

    <div v-else-if="peticiones.length === 0" class="empty-message">
      No se encontraron peticiones recientes.
    </div>

    <div class="scroll-horizontal"> <!-- 👈 NUEVO CONTENEDOR -->
      <div class="tabla">
        <div class="encabezado">
          <div>Acciones</div>
          <div>Folio</div>
          <div>Nombre</div>
          <div>Teléfono</div>
          <div>Dirección</div>
          <div>Localidad</div>
          <div>Descripción</div>
          <div>Red Social</div>
          <div>Fecha de Registro</div>
          <div>Nivel de Importancia</div>
          <div>Departamento</div>
        </div>

        <div
          v-for="peticion in peticiones"
          :key="peticion.id"
          class="fila"
        >
          <div>
            <select v-model="peticion.estado" @change="actualizarEstado(peticion)">
              <option disabled value="">Seleccione un estado</option>
              <option value="Sin revisar">Sin revisar</option>
              <option value="Rechazado por departamento">Rechazado por departamento</option>
              <option value="Por asignar departamento">Rechazado por usuario</option>
              <option value="Completado">En proceso</option>
              <option value="Aceptada en proceso">Completado</option>
              <option value="Devuelto">Rechazado por usuario</option>
              <option value="Improcedente">Devuelto</option>
              <option value="Cancelada">Improcedente</option>
              <option value="Esperando recepción">Cancelada</option>
            </select>
          </div>
          <div>{{ peticion.folio }}</div>
          <div>{{ peticion.nombre }}</div>
          <div>{{ peticion.telefono }}</div>
          <div>{{ peticion.direccion }}</div>
          <div>{{ peticion.localidad }}</div>
          <div>{{ peticion.descripcion }}</div>
          <div>{{ peticion.red_social }}</div>
          <div>{{ peticion.fecha_registro }}</div>
          <div>{{ peticion.NivelImportancia }}</div>
          <div>{{ peticion.nombre_departamento || 'No asignado' }}</div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>
import axios from 'axios';
import { ref, onMounted } from 'vue';

export default {
  name: 'TablaPeticiones',
  setup() {
    const backendUrl = import.meta.env.VITE_API_URL;
    const peticiones = ref([]);
    const departamentos = ref([]);
    const loading = ref(true);

    const cargarPeticiones = async () => {
      try {
        const res = await axios.get(`${backendUrl}/peticiones.php`);
        peticiones.value = (res.data.records || []).map(p => ({
          ...p,
          estado: p.estado || 'Sin revisar'  // ✅ Valor por defecto si viene vacío
        }));
      } catch (error) {
        console.error('Error cargando peticiones:', error);
      } finally {
        loading.value = false;
      }
    };


    const cargarDepartamentos = async () => {
      try {
        const res = await axios.get(`${backendUrl}/unidades.php`);
        departamentos.value = res.data.records || [];
      } catch (error) {
        console.error('Error cargando departamentos:', error);
      }
    };

    const obtenerNombreDepartamento = (id) => {
      const depto = departamentos.value.find((d) => d.id === id);
      return depto ? depto.nombre_unidad : 'No asignado';
    };

    onMounted(async () => {
      await Promise.all([cargarPeticiones(), cargarDepartamentos()]);
    });

const actualizarEstado = async (peticion) => {
  const payload = {
    id: Number(peticion.id),
    estado: String(peticion.estado)
  };

  try {
    const res = await axios({
      method: 'put',
      url: `${backendUrl}/actualizar_estado.php`,
      headers: {
        'Content-Type': 'application/json'
      },
      data: JSON.stringify(payload)
    });

    console.log('✅ Estado actualizado:', res.data);
  } catch (error) {
    console.error('❌ Error al actualizar estado:', error.response?.data || error.message);
  }
};

    return {
      peticiones,
      loading,
      obtenerNombreDepartamento,
      actualizarEstado,
    };
  }
};
</script>

<style scoped>
.tabla-peticiones {
  margin: 2rem auto;
  padding: 1rem;
  background: #ffffffcc;
  border-radius: 12px;
  max-width: 95vw;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 4rem;
}

.tabla-peticiones h3 {
  text-align: center;
  margin-bottom: 1rem;
  color: #333;
}

.scroll-horizontal {
  overflow-x: auto;
  width: 100%;
}

.tabla {
  min-width: 2350px; /* 🔧 Aumentado para forzar scroll visible */
  width: 100%; /* 🔥 Se adapta automáticamente al contenido real */
}

.encabezado,
.fila {
  display: grid;
  grid-template-columns:
    minmax(140px, 1fr)
    minmax(120px, 1fr)
    minmax(200px, 1fr)
    minmax(180px, 1fr)
    minmax(320px, 1fr)
    minmax(180px, 1fr)
    minmax(320px, 1fr)
    minmax(180px, 1fr)
    minmax(200px, 1fr)
    minmax(160px, 1fr)
    minmax(180px, 1fr)
    minmax(200px, 1fr);
  gap: 0.75rem;
  padding: 10px 0;
  border-bottom: 1px solid #e2e2e2;
}

.encabezado {
  font-weight: bold;
  background: #2563eb;
  color: #ffffff;
  border-radius: 8px;
  padding: 12px;
}

.fila {
  align-items: center;
}

.fila > div {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.fila > div:hover {
  white-space: normal;
  word-break: break-word;
}

select {
  padding: 4px;
  border-radius: 6px;
  font-size: 0.9rem;
  width: 100%;
  min-width: 120px;
}
</style>

