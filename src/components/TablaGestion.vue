<template>
  <div class="resumen-gestion">
    <div class="botones-container">
      <button
        v-for="item in resumen"
        :key="item.nombre"
        class="boton-celda"
        :class="[item.color, { 'activo': estadoActivo === item.nombre }]"
        @click="seleccionarEstado(item.nombre)"
      >
        <div class="boton-contenido">
          <span class="boton-numero">{{ item.cantidad }}</span>
          <span class="boton-texto">{{ item.nombre }}</span>
        </div>
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'TablaGestion',
  data() {
    return {
      resumen: [],
      estadoActivo: 'TOTAL',
      backendUrl: import.meta.env.VITE_API_URL
    }
  },
  mounted() {
    this.cargarResumen()
  },
  methods: {
    async cargarResumen() {
      try {
        const res = await axios.get(`${this.backendUrl}/peticiones.php`)
        const peticiones = res.data.records || []

        // Inicializar en orden fijo
        const resumenOrdenado = [
          { nombre: 'TOTAL', cantidad: peticiones.length, color: 'azul', icono: '📊' },
          { nombre: 'Sin revisar', cantidad: 0, color: 'azul', icono: '👀' },
          { nombre: 'Esperando recepción', cantidad: 0, color: 'amarillo', icono: '⏳' },
          { nombre: 'Completado', cantidad: 0, color: 'verde', icono: '✅' },
          { nombre: 'Rechazado por departamento', cantidad: 0, color: 'rojo', icono: '❌' }
        ]

        const mapa = {
          'Sin revisar': 1,
          'Esperando recepción': 2,
          'Completado': 3,
          'Rechazado por departamento': 4
        }

        // Contar ocurrencias
        peticiones.forEach(p => {
          const idx = mapa[p.estado]
          if (idx !== undefined) {
            resumenOrdenado[idx].cantidad++
          }
        })

        this.resumen = resumenOrdenado
      } catch (err) {
        console.error('Error cargando resumen:', err)
      }
    },
    seleccionarEstado(estado) {
      this.estadoActivo = estado
      this.$emit('filtrar-estado', estado)
    }
  }
}
</script>

<style scoped>
.resumen-gestion {
  width: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  position: relative;
  overflow: hidden;
  margin-top: 0.5rem;
}

.resumen-gestion::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: white;
  pointer-events: none;
}

.titulo-dashboard {
  color: rgb(0, 0, 0);
  font-size: 1.75rem;
  font-weight: 700;
  text-align: center;
  margin: 0 0 2rem 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  position: relative;
  z-index: 1;
}

.botones-container {
  display: flex;
  flex-wrap: wrap;
  gap: 1.25rem;
  justify-content: center;
  position: relative;
  z-index: 1;
}

.boton-celda {
  border: none;
  border-radius: 12px;
  padding: 0;
  font-weight: 600;
  cursor: pointer;
  min-width: 160px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

.boton-contenido {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.25rem 1rem;
  gap: 0.5rem;
}

.boton-numero {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
}

.boton-texto {
  font-size: 0.875rem;
  font-weight: 600;
  text-align: center;
  line-height: 1.2;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Colores de botones */
.azul .boton-numero { color: #1e40af; }
.azul .boton-texto { color: #1e40af; }
.azul.activo {
  background: linear-gradient(135deg, #3b82f6, #1e40af);
  color: white;
}
.azul.activo .boton-numero,
.azul.activo .boton-texto { color: white; }

.amarillo .boton-numero { color: #d97706; }
.amarillo .boton-texto { color: #d97706; }
.amarillo.activo {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}
.amarillo.activo .boton-numero,
.amarillo.activo .boton-texto { color: white; }

.verde .boton-numero { color: #16a34a; }
.verde .boton-texto { color: #16a34a; }
.verde.activo {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
}
.verde.activo .boton-numero,
.verde.activo .boton-texto { color: white; }

.rojo .boton-numero { color: #dc2626; }
.rojo .boton-texto { color: #dc2626; }
.rojo.activo {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
}
.rojo.activo .boton-numero,
.rojo.activo .boton-texto { color: white; }

/* Efectos hover */
.boton-celda:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.boton-celda.activo {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
}

.boton-celda::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s;
}

.boton-celda:hover::before {
  left: 100%;
}

/* Responsive */
@media (max-width: 1200px) {
  .botones-container {
    gap: 1rem;
  }
  
  .boton-celda {
    min-width: 140px;
  }
  
  .boton-numero {
    font-size: 1.75rem;
  }
}

@media (max-width: 768px) {
  .resumen-gestion {
    padding: 1.5rem;
  }
  
  .titulo-dashboard {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
  }
  
  .botones-container {
    gap: 0.75rem;
  }
  
  .boton-celda {
    min-width: 120px;
  }
  
  .boton-contenido {
    padding: 1rem 0.75rem;
  }
  
  .boton-numero {
    font-size: 1.5rem;
  }
  
  .boton-texto {
    font-size: 0.75rem;
  }
}

@media (max-width: 480px) {
  .resumen-gestion {
    padding: 1rem;
  }
  
  .titulo-dashboard {
    font-size: 1.25rem;
    margin-bottom: 1rem;
  }
  
  .botones-container {
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
  }
  
  .boton-celda {
    min-width: 200px;
    max-width: 280px;
  }
  
  .boton-contenido {
    flex-direction: row;
    justify-content: space-between;
    padding: 1rem 1.5rem;
  }
  
  .boton-numero {
    font-size: 1.75rem;
  }
  
  .boton-texto {
    text-align: right;
    font-size: 0.8rem;
  }
}
</style>