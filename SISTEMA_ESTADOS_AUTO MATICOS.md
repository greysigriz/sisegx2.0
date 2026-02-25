# Sistema de Estados Automáticos para Peticiones Ciudadanas

## 📋 Descripción General

Este sistema automatiza la gestión de estados de las peticiones ciudadanas basándose en los estados de los departamentos asignados. Proporciona visualización clara y automática del progreso de cada petición.

## 🎯 Objetivo

- **Automatizar** la actualización de estados de peticiones según el progreso de los departamentos
- **Visibilizar** de forma clara el estado actual y progreso de cada petición
- **Alertar** sobre peticiones que requieren atención inmediata
- Mantener **consistencia** entre estados de departamentos y estado general de la petición

## 🏗️ Arquitectura del Sistema

### Backend

#### 1. **EstadoService.php** (`api/services/EstadoService.php`)
Servicio principal que gestiona la lógica de actualización automática de estados.

**Métodos principales:**
- `actualizarEstadoAutomatico($peticion_id)`: Calcula y actualiza el estado de una petición
- `getEstadoCompleto($peticion_id)`: Obtiene información completa del estado
- `determinarEstado()`: Aplica reglas de negocio para determinar el estado
- `requiereAtencion()`: Detecta si una petición necesita atención

**Reglas de Estados:**

| Condición | Estado Resultante | Prioridad |
|-----------|-------------------|-----------|
| Todos los departamentos completados | `Completado` | Baja |
| Todos los departamentos rechazaron | `Rechazado por departamento` | Alta |
| Al menos un departamento devolvió | `Devuelto` | Alta |
| Al menos uno en proceso | `Aceptada en proceso` | Media |
| Todos esperando recepción | `Esperando recepción` | Media |
| Sin departamentos asignados | `Por asignar departamento` | Alta |

#### 2. **Actualización Automática en APIs**

**peticion_departamento.php:**
- Al cambiar el estado de un departamento (PUT), automáticamente actualiza el estado de la petición
- Al asignar nuevos departamentos (POST), recalcula el estado de la petición

**estado_peticion.php:** (Nuevo endpoint)
- GET: Obtiene información completa del estado de una petición
- POST: Fuerza actualización del estado automático

### Frontend

#### 1. **EstadosPeticiones.css** (`src/assets/css/EstadosPeticiones.css`)
Estilos visuales para los diferentes estados con animaciones y colores distintivos.

**Características visuales:**
- **Animaciones**: Estados críticos tienen animación de pulso
- **Iconos**: Cada estado tiene un icono distintivo
- **Colores**: Gradientes que indican severidad
- **Responsivo**: Adaptado para móviles

**Ejemplos de estados:**
```css
.estado-sin-revisar          /* Amarillo con pulso ⚠ */
.estado-por-asignar-departamento /* Rojo con animación ⚠ */
.estado-esperando-recepción   /* Azul claro ⏳ */
.estado-aceptada-en-proceso  /* Azul ⚙ */
.estado-completado            /* Verde ✓ */
.estado-devuelto              /* Naranja con pulso ↩ */
.estado-rechazado-por-departamento /* Rojo oscuro ✗ */
```

#### 2. **Componentes Vue Mejorados**

**Peticiones.vue:**
- `calcularProgresoPeticion()`: Calcula el % de completado
- `contarEstadosDepartamentos()`: Cuenta estados por categoría
- `requiereAtencionPeticion()`: Determina si necesita atención
- `obtenerInfoEstado()`: Obtiene resumen completo del estado

**Visualización mejorada:**
- Badge de estado principal con animaciones
- Indicador de atención (punto rojo pulsante)
- Mini badges de estados de departamentos
- Barra de progreso visual
- Texto descriptivo del progreso

**TablaDepartamento.vue:**
- Muestra estado del departamento como principal
- Muestra estado general de la petición como secundario
- Indica claramente qué estado corresponde a qué nivel

## 📊 Indicadores Visuales

### 1. Badge de Atención
- Aparece cuando una petición requiere atención inmediata
- Punto rojo pulsante en la esquina superior derecha del estado
- Casos que activan el indicador:
  - Estado "Sin revisar"
  - Estado "Por asignar departamento"
  - Al menos un departamento devolvió
  - Todos los departamentos rechazaron
  - Más de 48 horas en espera

### 2. Mini Badges de Departamentos
- Muestran el estado de cada departamento asignado
- Colores distintivos por estado:
  - 🔵 Azul claro: Esperando recepción
  - 🔵 Azul: Aceptado en proceso
  - 🟢 Verde: Completado
  - 🔴 Rojo: Rechazado
  - 🟠 Naranja: Devuelto a seguimiento

### 3. Barra de Progreso
- Muestra visualmente el % de departamentos que completaron
- Gradiente verde con efecto shimmer
- Texto descriptivo: "X de Y departamento(s) completado(s)"

### 4. Semáforo de Tiempo
- Verde: 0-24 horas
- Amarillo: 24-48 horas
- Naranja: 48-72 horas
- Rojo: +72 horas

## 🔄 Flujo de Actualización Automática

```
┌─────────────────────────────────────┐
│ Departamento cambia de estado       │
└────────────┬────────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│ peticion_departamento.php (PUT)     │
└────────────┬────────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│ EstadoService->actualizarEstadoAuto()│
└────────────┬────────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│ 1. Obtiene todos los departamentos  │
│ 2. Cuenta estados                   │
│ 3. Aplica reglas de negocio         │
│ 4. Calcula nuevo estado             │
└────────────┬────────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│ Actualiza estado de la petición     │
└────────────┬────────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│ Retorna info al frontend            │
│ - Nuevo estado                      │
│ - Razón del cambio                  │
│ - Requiere atención                 │
└─────────────────────────────────────┘
```

## 🎨 Ejemplos de Visualización

### Vista de Peticiones
```
┌──────────────────────────────────────────┐
│ [⚠] ACEPTADA EN PROCESO              🔴  │ ← Badge de atención
├──────────────────────────────────────────┤
│ [🏢 Obras] [🏢 Servicios] +2             │ ← Mini badges depts
├──────────────────────────────────────────┤
│ ████████░░░░░░░░░░░░░░░ 40%             │ ← Barra de progreso
│ 2 de 5 departamento(s) completado(s)    │ ← Texto descriptivo
└──────────────────────────────────────────┘
```

### Vista de Departamento
```
┌──────────────────────────────────────────┐
│ Estado Principal (Departamento):         │
│ [⚙] ACEPTADO EN PROCESO                  │
├──────────────────────────────────────────┤
│ ℹ️ Petición: ACEPTADA EN PROCESO         │ ← Estado general
└──────────────────────────────────────────┘
```

## 🚀 Uso

### Para Desarrolladores

1. **Crear nueva petición:** El estado inicial será "Sin revisar"
2. **Asignar departamentos:** Estado cambia automáticamente a "Esperando recepción"
3. **Departamento cambia estado:** El estado general se recalcula automáticamente
4. **Verificar estado:** Usar `estado_peticion.php?peticion_id=X`

### Endpoints API

#### Obtener Estado Completo
```javascript
GET /api/estado_peticion.php?peticion_id=123

Response:
{
  "success": true,
  "peticion_id": 123,
  "estado": {
    "estado_peticion": "Aceptada en proceso",
    "nivel_importancia": 2,
    "total_departamentos": 5,
    "completados": 2,
    "rechazados": 0,
    "en_proceso": 2,
    "devueltos": 0,
    "esperando": 1,
    "progreso_porcentaje": 40.0,
    "requiere_atencion": false,
    "razon_atencion": "",
    "prioridad": "media"
  }
}
```

#### Forzar Actualización de Estado
```javascript
POST /api/estado_peticion.php
Body: { "peticion_id": 123 }

Response:
{
  "success": true,
  "peticion_id": 123,
  "resultado": {
    "estado_anterior": "Esperando recepción",
    "estado_nuevo": "Aceptada en proceso",
    "razon": "2 departamento(s) trabajando (0 de 5 completados)",
    "requiere_atencion": false,
    "prioridad": "media"
  }
}
```

## 🔧 Configuración

### Estados de Petición (tabla `peticiones`)
```sql
estado ENUM(
  'Sin revisar',
  'Rechazado por departamento',
  'Por asignar departamento',
  'Completado',
  'Aceptada en proceso',
  'Devuelto',
  'Improcedente',
  'Cancelada',
  'Esperando recepción'
)
```

### Estados de Departamento (tabla `peticion_departamento`)
```sql
estado ENUM(
  'Esperando recepción',
  'Aceptado en proceso',
  'Devuelto a seguimiento',
  'Rechazado',
  'Completado'
)
```

## 📝 Mantenimiento

### Agregar Nuevo Estado

1. **Backend:** Actualizar `EstadoService.php`
   - Agregar caso en `determinarEstado()`
   - Actualizar `requiereAtencion()` si aplica

2. **Frontend:** Actualizar `EstadosPeticiones.css`
   - Agregar clase `.estado-nuevo-nombre`
   - Definir colores, iconos y animaciones

3. **Base de Datos:** Ejecutar migración
   ```sql
   ALTER TABLE peticiones
   MODIFY estado ENUM(..., 'Nuevo Estado');
   ```

### Debugging

Para ver logs de cambios de estado:
```bash
tail -f /path/to/php/error.log | grep "Petición"
```

Los cambios se registran automáticamente con:
- ID de petición
- Estado anterior
- Estado nuevo
- Razón del cambio

## 🎯 Beneficios

1. **Automático**: No requiere intervención manual para actualizar estados
2. **Consistente**: El estado siempre refleja la realidad de los departamentos
3. **Visual**: Fácil identificar qué peticiones necesitan atención
4. **Informativo**: Progreso claro con porcentajes y contadores
5. **Responsive**: Funciona en dispositivos móviles
6. **Escalable**: Fácil agregar nuevas reglas o estados

## 🐛 Solución de Problemas

### El estado no se actualiza automáticamente
- Verificar que `EstadoService.php` esté incluido en las APIs
- Revisar logs de PHP para errores
- Verificar permisos de base de datos

### Las animaciones no se muestran
- Verificar que `EstadosPeticiones.css` esté importado
- Limpiar caché del navegador
- Verificar estructura de clases CSS en el HTML

### Estados inconsistentes
- Ejecutar actualización forzada vía `estado_peticion.php`
- Verificar integridad de datos en `peticion_departamento`

## 👥 Autor

Sistema desarrollado para SISEE - Sistema de Gestión de Peticiones Ciudadanas

---

**Fecha de creación:** Febrero 2026
**Versión:** 1.0.0
