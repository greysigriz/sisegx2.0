<template>
  <div class="tabla-peticiones">
    <h3>Peticiones recientes</h3>

    <div v-if="loading" class="loading-message">
      <div class="spinner"></div>
      Cargando peticiones...
    </div>

    <div v-else-if="peticiones.length === 0" class="empty-message">
      <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
      </svg>
      No se encontraron peticiones recientes
    </div>

    <div v-else class="tabla">
      <div class="encabezado">
        <div>Folio</div>
        <div>Nombre</div>
        <div>Estado</div>
        <div>Departamento</div>
      </div>

      <div
        v-for="peticion in peticiones.slice(0, 5)"
        :key="peticion.id"
        class="fila"
      >
        <div class="celda folio">{{ peticion.folio }}</div>
        <div class="celda nombre">{{ peticion.nombre }}</div>
        <div class="celda estado">
          <span :class="['badge-estado', getEstadoClass(peticion.estado)]">
            {{ peticion.estado }}
          </span>
        </div>
        <div class="celda departamento">{{ peticion.nombre_departamento || 'No asignado' }}</div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { ref, onMounted, computed } from 'vue'

export default {
  name: 'TablaReportes',
  props: {
    estadoSeleccionado: {
      type: String,
      default: 'TOTAL'
    }
  },
  setup(props) {
    const backendUrl = import.meta.env.VITE_API_URL
    const peticiones = ref([])
    const departamentos = ref([])
    const loading = ref(true)

    const cargarPeticiones = async () => {
      try {
        const res = await axios.get(`${backendUrl}/peticiones.php`)
        peticiones.value = res.data.records || []
      } catch (error) {
        console.error('Error cargando peticiones:', error)
      } finally {
        loading.value = false
      }
    }

    const cargarDepartamentos = async () => {
      try {
        const res = await axios.get(`${backendUrl}/unidades.php`)
        departamentos.value = res.data.records || []
      } catch (error) {
        console.error('Error cargando departamentos:', error)
      }
    }

    const obtenerNombreDepartamento = (id) => {
      const depto = departamentos.value.find((d) => d.id === id)
      return depto ? depto.nombre_unidad : 'No asignado'
    }

    const peticionesFiltradas = computed(() => {
      if (props.estadoSeleccionado === 'TOTAL') return peticiones.value
      return peticiones.value.filter(p => p.estado === props.estadoSeleccionado)
    })

    const getEstadoClass = (estado) => {
      const clases = {
        'Sin revisar': 'sin-revisar',
        'Esperando recepción': 'esperando',
        'Completado': 'completado',
        'Rechazado por departamento': 'rechazado'
      }
      return clases[estado] || 'default'
    }

    onMounted(async () => {
      await Promise.all([cargarPeticiones(), cargarDepartamentos()])
    })

    return {
      peticiones: peticionesFiltradas,
      loading,
      obtenerNombreDepartamento,
      getEstadoClass
    }
  }
}
</script>

<style scoped>
.tabla-peticiones {
  width: 100%;
  max-width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.tabla-peticiones h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  text-align: center;
}

.tabla {
  flex: 1;
  width: 100%;
  overflow: hidden;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: white;
}

/* Encabezado de tabla */
.encabezado {
  display: grid;
  grid-template-columns: 1fr 2fr 1.5fr 2fr;
  gap: 0.5rem;
  padding: 1rem;
  background: #2563eb;
  font-weight: 600;
  font-size: 0.875rem;
  color: #ffffff;
  border-bottom: 2px solid #e5e7eb;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Filas de datos */
.fila {
  display: grid;
  grid-template-columns: 1fr 2fr 1.5fr 2fr;
  gap: 0.5rem;
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.9rem;
  color: #374151;
  transition: all 0.2s ease;
}

.fila:hover {
  background: #f8fafc;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.fila:last-child {
  border-bottom: none;
}

.celda {
  display: flex;
  align-items: center;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.folio {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #059669;
  font-size: 0.8rem;
}

.nombre {
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.departamento {
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Estados */
.badge-estado {
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sin-revisar {
  background-color: #dbeafe;
  color: #1e40af;
}

.esperando {
  background-color: #fee2e2;
  color: #dc2626;
}

.completado {
  background-color: #dcfce7;
  color: #16a34a;
}

.rechazado {
  background-color: #fed7aa;
  color: #ea580c;
}

.default {
  background-color: #f3f4f6;
  color: #6b7280;
}

/* Estados de carga */
.loading-message,
.empty-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  color: #6b7280;
  font-size: 0.95rem;
  text-align: center;
  background: #fafafa;
  border-radius: 8px;
  border: 2px dashed #e5e7eb;
  gap: 1rem;
  width: 100%;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #f3f4f6;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.empty-icon {
  width: 48px;
  height: 48px;
  color: #9ca3af;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Botón ver más */
.ver-mas {
  margin-top: 1rem;
  text-align: center;
  width: 100%;
}

.btn-ver-mas {
  padding: 0.75rem 2rem;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  color: #374151;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-ver-mas:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
}

/* Responsive */
@media (max-width: 1024px) {
  .encabezado,
  .fila {
    grid-template-columns: 110px 2fr 150px 180px;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
  }
}

@media (max-width: 768px) {
  .encabezado,
  .fila {
    grid-template-columns: 100px 1.5fr 130px 160px;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.8rem;
  }
  
  .tabla-peticiones h3 {
    font-size: 1.125rem;
  }
}

@media (max-width: 480px) {
  .encabezado,
  .fila {
    grid-template-columns: 80px 1fr 120px;
    gap: 0.5rem;
    padding: 0.75rem;
  }
  
  .encabezado div:nth-child(4),
  .fila .celda:nth-child(4) {
    display: none;
  }
  
  .badge-estado {
    padding: 0.3rem 0.6rem;
  }
}
</style>