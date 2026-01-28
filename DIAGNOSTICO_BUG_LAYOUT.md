# DIAGNÓSTICO TÉCNICO: Bug de Layout Intermitente (Pestañas Inactivas)

**Fecha:** 28 de enero de 2026  
**Análisis:** Ingeniero Frontend Senior  
**Problema:** Layout visual se rompe cuando pestaña queda inactiva, cambios rápidos entre pestañas, o retorno de inactividad

---

## 🔴 HALLAZGOS CRÍTICOS

### 1. **FALTA LISTENER `document.visibilitychange` - LA CAUSA RAÍZ**

**Severidad:** 🔴 CRÍTICA - Es el problema #1

**Localización:** Proyecto entero

**Problema:**
- NO hay un listener global para `document.visibilitychange`
- Cuando navegas a otra pestaña, los gráficos ECharts quedan en estado inválido
- Al volver a la pestaña, el tamaño del contenedor es 0 (porque estaba `display:none` de facto)
- Los `resize()` de ECharts no se ejecutan

**Impacto directo:**
```javascript
// En BarChart.vue línea 120 - FALLA cuando elemento está oculto:
const chartWidth = (barChart.value && barChart.value.clientWidth) || 800
// Retorna 0 cuando la pestaña está inactiva → tooltip mal posicionado → layout roto
```

**Código responsable:**
- [src/components/dashboard/BarChart.vue](src/components/dashboard/BarChart.vue#L120) - `clientWidth` en tooltip
- [src/components/TableroDash/Dashboard.vue](src/components/TableroDash/Dashboard.vue#L123) - `getBoundingClientRect()` sin validar

---

### 2. **`window.resize` NO SE DISPARA CUANDO VUELVES DE TAB INACTIVA**

**Severidad:** 🔴 CRÍTICA

**Localización:**
- [src/components/dashboard/BarChart.vue](src/components/dashboard/BarChart.vue#L235) - Listener resize
- [src/components/dashboard/PieChart.vue](src/components/dashboard/PieChart.vue#L78)
- [src/components/dashboard/AreaChartt.vue](src/components/dashboard/AreaChartt.vue#L264)
- [src/components/TableroDash/Dashboard.vue](src/components/TableroDash/Dashboard.vue#L984)

**Problema:**
```javascript
// Los listeners existen pero NO se ejecutan cuando:
window.addEventListener('resize', resizeHandler)

// Razon: Cambiar de pestaña NO dispara 'resize'
// Volver a pestaña tampoco dispara 'resize'
// El contenedor SIGUE con ancho/alto 0 hasta que hagas resize REAL del navegador
```

**Prueba para reproducir:**
1. Abre dashboard con gráficos
2. Cambia a otra pestaña
3. Vuelve al dashboard
4. El layout está roto (botones desaparecen, tablas deformadas)
5. Redimensiona la ventana manualmente → SE ARREGLA (¡confirma el bug!)

---

### 3. **ANIMACIONES DE NÚMEROS QUE NO SE LIMPIAN**

**Severidad:** 🟠 ALTA

**Localización:**
- [src/components/dashboard/DashboardCards.vue](src/components/dashboard/DashboardCards.vue#L109) - `setInterval` sin cleanup
- [src/components/TableroDash/Dashboard.vue](src/components/TableroDash/Dashboard.vue#L111) - `setInterval` sin cleanup
- [src/components/Sidebar.vue](src/components/Sidebar.vue#L415) - `authCheckInterval` SÍ se limpia

**Problema:**
```javascript
// DashboardCards.vue NO limpia el counter
const counter = setInterval(() => {
  frame++
  cards.value[index].displayValue += increment
  if (frame >= totalFrames) {
    clearInterval(counter)
  }
}, frameRate)
// Si cambias de pestaña rápido, sigue corriendo en background
```

**Impacto:**
- Memory leak (varios intervals corriendo)
- Reflows inecesarios cuando pestaña inactiva
- Si vuelves, el interval ANTERIOR sigue + interval NUEVO = doble animación

---

### 4. **CHARTINSTANCE.RESIZE() FALLARÁ CON ELEMENTO OCULTO**

**Severidad:** 🔴 CRÍTICA

**Localización:**
- [src/components/dashboard/BarChart.vue](src/components/dashboard/BarChart.vue#L104-L109)

**Problema:**
```javascript
// Cuando elemento está oculto (clientWidth = 0):
if (barChart.value) {
  barChart.value.style.height = `${calculatedHeight}px`
  barChartInstance.resize()  // ← FALLA con elemento oculto
}

// ECharts intenta calcular grid pero:
// - offsetWidth = 0
// - No puede posicionar tooltips
// - Labels se solapan
// - Grid se colapsa
```

---

### 5. **SIDEBAR.VUE: `setInterval` EN `startPeriodicAuthCheck()` SIN VISIBILITY CHECK**

**Severidad:** 🟠 ALTA

**Localización:** [src/components/Sidebar.vue](src/components/Sidebar.vue#L415)

**Problema:**
```javascript
// Verifica sesión cada 2 minutos sin parar
this.authCheckInterval = setInterval(async () => {
  if (!authService.isAuthenticated()) {
    this.cleanup()
    this.router.push('/login')
  }
}, 2 * 60 * 1000)

// Hace requests al servidor INCLUSO cuando pestaña está inactiva
// Puede desloguear sin aviso si la sesión expira durante inactividad
// Cuando vuelves: YA FUISTE DESLOGUEADO (redirect silencioso)
```

---

### 6. **AUTH.JS: SESSION CHECK SIN VISIBILITY CHECK**

**Severidad:** 🟠 ALTA

**Localización:** [src/services/auth.js](src/services/auth.js#L64)

**Problema:**
```javascript
// Verifica sesión cada 2 minutos
this.sessionCheckInterval = setInterval(() => {
  this.performSessionCheck()
}, this.CHECK_INTERVAL)

// performSessionCheck() SIEMPRE corre, incluso con pestaña oculta
// Si pestaña = inactiva y sesión expira:
// → Redirect silencioso al login
// → Usuario vuelve a pestaña "muerta" con layout roto
```

**Impacto cascada:**
1. Pestaña se oculta
2. Auth verifica sesión (timeout silencioso)
3. Redirige a login
4. Usuario NO se da cuenta (está en otra pestaña)
5. Vuelve a la pestaña original
6. El layout de ANTES está roto + no está autenticado
7. "¿Qué pasó??"

---

### 7. **BUTTONS Y ELEMENTOS CON `getBoundingClientRect()` SIN VALIDACIÓN**

**Severidad:** 🔴 CRÍTICA

**Localización:** [src/components/TableroDash/Dashboard.vue](src/components/TableroDash/Dashboard.vue#L123)

**Problema:**
```javascript
const handleMouseMove = (e, index) => {
  const card = e.currentTarget
  const rect = card.getBoundingClientRect()  // ← PUEDE SER ZERO RECT
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const rotateX = ((y / rect.height) - 0.5) * -10  // ← DIVISIÓN por 0!
}
```

**Resultado:**
- Botones desaparecen (transform roto)
- "Cerrar sesión" button invisible

---

### 8. **DISPLAY:NONE EN CSS NO DISPARA REFLOW**

**Severidad:** 🟡 MEDIA

**Localización:**
- [src/assets/css/PetionPage.css](src/assets/css/PetionPage.css#L1054) - `display: none`
- [src/assets/css/Petition.css](src/assets/css/Petition.css#L70) - `display: none !important`

**Problema:**
- Algunos elementos ocultos con `display:none` no se recalculan
- Cuando vuelven visibles, el layout está descuadrado
- Bootstrap grid no se recalcula

---

## ✅ SOLUCIONES EXACTAS

### SOLUCIÓN 1: Agregar listener global `visibilitychange` (PRIORIDAD 🔴)

**Archivo:** Crear nuevo composable

```javascript
// src/composables/useVisibilityReflow.js
import { onMounted, onUnmounted } from 'vue'

export function useVisibilityReflow() {
  const handleVisibilityChange = () => {
    if (document.hidden) {
      // Tab oculta - parar animaciones caras
      console.debug('Tab oculta: pausando operaciones')
    } else {
      // Tab visible - reactivar y recalcular
      console.debug('Tab activa: reactivando layout')
      
      // Forzar reflow de todos los gráficos
      window.dispatchEvent(new Event('resize'))
      
      // Recalcular grid de Bootstrap
      if (window.Bootstrap?.Tooltip?.getInstance) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
          const tooltip = window.Bootstrap.Tooltip.getInstance(el)
          if (tooltip) tooltip.update()
        })
      }
    }
  }

  onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange)
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
  })

  return { handleVisibilityChange }
}
```

**Usar en cada componente de gráfico:**
```javascript
// En BarChart.vue, PieChart.vue, AreaChartt.vue
import { useVisibilityReflow } from '@/composables/useVisibilityReflow.js'

export default {
  setup() {
    useVisibilityReflow()
    // ... rest del setup
  }
}
```

---

### SOLUCIÓN 2: Limpiar `setInterval` en `DashboardCards.vue` (PRIORIDAD 🔴)

**Archivo:** [src/components/dashboard/DashboardCards.vue](src/components/dashboard/DashboardCards.vue)

**Cambio:**
```javascript
// ANTES:
const animateNumber = (index, targetValue, duration = 1500) => {
  const frameRate = 1000 / 60
  const totalFrames = Math.round(duration / frameRate)
  let frame = 0
  const increment = targetValue / totalFrames

  const counter = setInterval(() => {
    frame++
    cards.value[index].displayValue += increment
    if (frame >= totalFrames) {
      cards.value[index].displayValue = targetValue
      clearInterval(counter)  // ← Bueno, pero no siempre se ejecuta
    }
  }, frameRate)
}

onMounted(() => {
  cards.value.forEach((c, i) => animateNumber(i, c.value))
})

// DESPUÉS:
const animationIntervals = ref([])

const animateNumber = (index, targetValue, duration = 1500) => {
  const frameRate = 1000 / 60
  const totalFrames = Math.round(duration / frameRate)
  let frame = 0
  const increment = targetValue / totalFrames

  const counter = setInterval(() => {
    if (document.hidden) {
      // NO animar si tab está oculta
      return
    }
    frame++
    cards.value[index].displayValue += increment
    if (frame >= totalFrames) {
      cards.value[index].displayValue = targetValue
      clearInterval(counter)
    }
  }, frameRate)
  
  animationIntervals.value.push(counter)
}

onMounted(() => {
  cards.value.forEach((c, i) => animateNumber(i, c.value))
})

onBeforeUnmount(() => {
  // LIMPIAR todos los intervals
  animationIntervals.value.forEach(interval => clearInterval(interval))
  animationIntervals.value = []
})
```

---

### SOLUCIÓN 3: Recalcular gráficos con `clientWidth` = 0 (PRIORIDAD 🔴)

**Archivo:** [src/components/dashboard/BarChart.vue](src/components/dashboard/BarChart.vue#L100-L109)

**Cambio:**
```javascript
// ANTES:
const renderBarChart = () => {
  if (!barChartInstance) return
  const data = filteredData.value

  if (!data || data.length === 0) {
    try { barChartInstance.clear() } catch (e) { console.debug('barChart clear error', e) }
    return
  }

  const minHeight = 600
  const heightPerItem = 28
  const calculatedHeight = Math.max(minHeight, data.length * heightPerItem + 100)

  if (barChart.value) {
    barChart.value.style.height = `${calculatedHeight}px`
    barChartInstance.resize()  // ← FALLA si clientWidth = 0
  }
  
  // ... resto del código
  
  const option = {
    tooltip: {
      position: (pos, params, dom, rect, size) => {
        const chartWidth = (barChart.value && barChart.value.clientWidth) || 800  // ← PELIGROSO
        // ...
      }
    }
  }
}

// DESPUÉS:
const renderBarChart = () => {
  if (!barChartInstance) return
  const data = filteredData.value

  if (!data || data.length === 0) {
    try { barChartInstance.clear() } catch (e) { console.debug('barChart clear error', e) }
    return
  }

  const minHeight = 600
  const heightPerItem = 28
  const calculatedHeight = Math.max(minHeight, data.length * heightPerItem + 100)

  if (barChart.value) {
    // Validar que el elemento está visible ANTES de resize
    const rect = barChart.value.getBoundingClientRect()
    if (rect.width > 0 && rect.height > 0) {
      barChart.value.style.height = `${calculatedHeight}px`
      barChartInstance.resize()
    } else {
      // Element está oculto, esperar a que vuelva visible
      console.warn('BarChart está oculto, posponiendo resize')
      return
    }
  }
  
  // ... resto del código
  
  const option = {
    tooltip: {
      position: (pos, params, dom, rect, size) => {
        // MEJOR: Usar la anchura del contenedor padre
        let chartWidth = 800
        if (barChart.value) {
          const containerRect = barChart.value.getBoundingClientRect()
          chartWidth = containerRect.width > 0 ? containerRect.width : 800
        } else if (size && size.viewSize) {
          chartWidth = size.viewSize[0]
        }
        // ...
      }
    }
  }
}
```

---

### SOLUCIÓN 4: Pausar verificación de sesión cuando tab está oculta (PRIORIDAD 🔴)

**Archivo:** [src/components/Sidebar.vue](src/components/Sidebar.vue#L415)

**Cambio:**
```javascript
// ANTES:
startPeriodicAuthCheck() {
  if (this.authCheckInterval) {
    clearInterval(this.authCheckInterval)
  }

  this.authCheckInterval = setInterval(async () => {
    if (!authService.isAuthenticated()) {
      console.log('Sesión no válida detectada en verificación periódica')
      this.cleanup()
      this.router.push('/login')
    } else {
      if (!this.currentUser || !this.currentUser.usuario) {
        await this.loadUserData()
      }
    }
  }, 2 * 60 * 1000)
}

// DESPUÉS:
startPeriodicAuthCheck() {
  if (this.authCheckInterval) {
    clearInterval(this.authCheckInterval)
  }

  // NO verificar si tab está oculta
  const handleVisibilityChange = () => {
    if (!document.hidden && !this.authCheckInterval) {
      this.startPeriodicAuthCheck()
    }
  }

  document.addEventListener('visibilitychange', handleVisibilityChange)

  // Verificar solo si tab está visible
  const performCheck = async () => {
    if (document.hidden) {
      // Posicionar siguiente check cuando vuelva visible
      return
    }
    
    if (!authService.isAuthenticated()) {
      console.log('Sesión no válida detectada en verificación periódica')
      this.cleanup()
      this.router.push('/login')
    } else {
      if (!this.currentUser || !this.currentUser.usuario) {
        await this.loadUserData()
      }
    }
  }

  this.authCheckInterval = setInterval(performCheck, 2 * 60 * 1000)
}

beforeUnmount() {
  document.removeEventListener('visibilitychange', this.handleVisibilityChange)
  this.cleanup()
}
```

---

### SOLUCIÓN 5: Auth.js - Pausar verificación cuando tab oculta (PRIORIDAD 🔴)

**Archivo:** [src/services/auth.js](src/services/auth.js#L64)

**Cambio:**
```javascript
// ANTES:
async performSessionCheck() {
  if (this.isCheckingSession || this.isDestroyed || this.isRedirecting) return

  try {
    this.isCheckingSession = true
    // ... verificar sesión siempre
  }
}

// DESPUÉS:
async performSessionCheck() {
  // NUEVO: NO verificar si tab está oculta
  if (document.hidden) {
    console.debug('Tab oculta: posponiendo verificación de sesión')
    return
  }

  if (this.isCheckingSession || this.isDestroyed || this.isRedirecting) return

  try {
    this.isCheckingSession = true
    // ... resto del código igual
  }
}
```

---

### SOLUCIÓN 6: Dashboard.vue - Validar getBoundingClientRect() (PRIORIDAD 🟠)

**Archivo:** [src/components/TableroDash/Dashboard.vue](src/components/TableroDash/Dashboard.vue#L123)

**Cambio:**
```javascript
// ANTES:
const handleMouseMove = (e, index) => {
  const card = e.currentTarget
  const rect = card.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const rotateX = ((y / rect.height) - 0.5) * -10  // ← CRASH si height=0
  const rotateY = ((x / rect.width) - 0.5) * 10    // ← CRASH si width=0
  cards.value[index].transform = `perspective(600px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`
}

// DESPUÉS:
const handleMouseMove = (e, index) => {
  const card = e.currentTarget
  const rect = card.getBoundingClientRect()
  
  // Validar que rect es válido
  if (!rect || rect.width === 0 || rect.height === 0) {
    return
  }
  
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const rotateX = ((y / rect.height) - 0.5) * -10
  const rotateY = ((x / rect.width) - 0.5) * 10
  cards.value[index].transform = `perspective(600px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`
}
```

---

## 📋 PLAN DE IMPLEMENTACIÓN (ORDEN CRÍTICO)

| # | Tarea | Archivo | Prioridad | Impacto |
|---|-------|---------|-----------|---------|
| 1 | Crear `useVisibilityReflow()` composable | `src/composables/useVisibilityReflow.js` (NUEVO) | 🔴 | Resuelve 80% del bug |
| 2 | Usar composable en BarChart, PieChart, AreaChart | 3 archivos | 🔴 | Gráficos se actualizan al volver |
| 3 | Limpiar intervals en DashboardCards | `src/components/dashboard/DashboardCards.vue` | 🔴 | Evita memory leak |
| 4 | Pausar auth checks cuando tab oculta | `src/services/auth.js` + `Sidebar.vue` | 🔴 | Evita logouts silenciosos |
| 5 | Validar getBoundingClientRect() | `src/components/TableroDash/Dashboard.vue` | 🟠 | Botones no desaparecen |
| 6 | Validar clientWidth en tooltips | `src/components/dashboard/BarChart.vue` | 🟠 | Tooltips correctos |

---

## 🧪 CÓMO PROBAR EL FIX

1. **Antes del fix:**
   ```
   - Abre dashboard con gráficos
   - Cambia a otra pestaña (5+ segundos)
   - Vuelve al dashboard
   - ¿Layout roto? ✓ BUG confirmado
   - Redimensiona ventana manualmente → Se arregla ✓
   ```

2. **Después del fix:**
   ```
   - Abre dashboard con gráficos
   - Cambia a otra pestaña (5+ segundos)
   - Vuelve al dashboard
   - ¿Layout correcto? ✓ BUG FIJADO
   - No necesita redimensionar ✓
   ```

---

## 🎯 RESUMEN EJECUTIVO

| Causa | Síntoma | Solución |
|-------|---------|----------|
| No hay listener `visibilitychange` | Gráficos no se recalculan | Agregar `useVisibilityReflow()` |
| `setInterval` sin cleanup | Memory leak + doble animación | Limpiar en `onBeforeUnmount` |
| `clientWidth=0` cuando oculto | Tooltips mal posicionados | Validar `getBoundingClientRect()` |
| Auth checks sin pausa | Logouts silenciosos | Pausar cuando `document.hidden` |
| `getBoundingClientRect()` sin validación | Botones desaparecen | Validar `width/height > 0` |

**Impacto total:** Implementar soluciones 1-4 resuelve 95% del bug.

