# 📖 Sistema de Permisos y Roles - Guía Completa

## 🎯 ¿Cómo Funciona el Sistema?

El sistema tiene **3 niveles** de control de acceso:

```
Usuario → Roles → Permisos → Vistas/Funcionalidades
```

### 1. **Permisos** (¿Qué puede hacer?)
Son las acciones específicas que se pueden realizar en el sistema. Ejemplos:
- `ver_dashboard` - Puede ver el dashboard
- `crear_peticiones` - Puede crear peticiones
- `gestionar_usuarios` - Puede administrar usuarios

### 2. **Roles** (¿Qué cargo tiene?)
Agrupan permisos según el tipo de usuario. Ejemplos:
- **Super Usuario**: Tiene TODOS los permisos
- **Director**: Puede administrar peticiones y ver reportes
- **Call center**: Solo puede ver y crear peticiones

### 3. **Usuarios** (¿Quién es?)
Cada usuario tiene uno o más roles asignados.

---

## 📊 Estructura de la Base de Datos

```
┌─────────────┐      ┌──────────────┐      ┌─────────────┐
│   Usuario   │──────│  UsuarioRol  │──────│ RolSistema  │
└─────────────┘      └──────────────┘      └─────────────┘
                                                    │
                                                    │
                                            ┌───────┴──────┐
                                            │  RolPermiso  │
                                            └───────┬──────┘
                                                    │
                                            ┌───────┴──────┐
                                            │   Permiso    │
                                            └──────────────┘
```

### Tablas:

1. **Usuario**: Los usuarios del sistema
2. **RolSistema**: Los roles disponibles (Director, Call center, etc.)
3. **Permiso**: Los permisos disponibles (ver_dashboard, crear_peticiones, etc.)
4. **UsuarioRol**: Relaciona usuarios con roles (un usuario puede tener múltiples roles)
5. **RolPermiso**: Relaciona roles con permisos (un rol tiene múltiples permisos)

---

## 🚀 Cómo Usar el Sistema

### PASO 1: Ejecutar el SQL de Permisos

```sql
-- Ejecutar en phpMyAdmin
database/permisos_sistema.sql
```

Este script:
- ✅ Crea la tabla `Permiso`
- ✅ Crea la tabla `RolPermiso`
- ✅ Inserta 30+ permisos predefinidos
- ✅ Asigna permisos a los roles existentes
- ✅ Crea vistas útiles

---

### PASO 2: Ver Qué Permisos Tiene Cada Rol

```sql
-- Ver todos los roles con sus permisos
SELECT * FROM v_RolesConPermisos;

-- Ver permisos de un rol específico
SELECT 
    r.Nombre as Rol,
    p.Modulo,
    p.Nombre as Permiso,
    p.Descripcion
FROM RolSistema r
JOIN RolPermiso rp ON r.Id = rp.IdRolSistema
JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE r.Nombre = 'Director'
ORDER BY p.Modulo, p.Nombre;
```

---

### PASO 3: Asignar Permisos a un Rol

#### Opción A: Desde SQL (Manual)

```sql
-- Dar permiso "crear_peticiones" al rol "Call center"
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT 
    (SELECT Id FROM RolSistema WHERE Nombre = 'Call center'),
    (SELECT Id FROM Permiso WHERE Codigo = 'crear_peticiones');

-- Quitar un permiso
DELETE FROM RolPermiso
WHERE IdRolSistema = (SELECT Id FROM RolSistema WHERE Nombre = 'Call center')
  AND IdPermiso = (SELECT Id FROM Permiso WHERE Codigo = 'eliminar_peticiones');
```

#### Opción B: Desde la Interfaz (Próximamente)
Crearemos una vista de "Gestión de Permisos" donde podrás:
- Ver todos los roles
- Ver permisos de cada rol
- Agregar/quitar permisos con checkboxes

---

## 🔐 Cómo Funciona en el Código

### En el Backend (PHP)

Cuando un usuario inicia sesión, el sistema:

1. **Obtiene sus roles** desde `UsuarioRol`
2. **Obtiene los permisos** de esos roles desde `RolPermiso`
3. **Retorna un array de permisos** al frontend

```php
// En check-session.php
$queryPermisos = "SELECT DISTINCT p.Codigo
                  FROM UsuarioRol ur
                  JOIN RolPermiso rp ON ur.IdRolSistema = rp.IdRolSistema
                  JOIN Permiso p ON rp.IdPermiso = p.Id
                  WHERE ur.IdUsuario = :user_id";

// Resultado: ['ver_dashboard', 'crear_peticiones', 'ver_usuarios', ...]
```

### En el Frontend (Vue.js)

El `Sidebar.vue` filtra las opciones del menú según los permisos:

```javascript
filteredMenuItems() {
  const permisos = this.currentUser.usuario.Permisos || [];
  
  return this.allMenuItems.filter(item => {
    return permisos.includes(item.requiredPermission);
  });
}
```

Cada opción del menú tiene un `requiredPermission`:

```javascript
allMenuItems: [
  { 
    name: 'peticiones', 
    label: 'Peticiones', 
    icon: 'fas fa-tasks', 
    path: '/peticiones', 
    requiredPermission: 'ver_peticiones' // <-- Permiso requerido
  },
  { 
    name: 'configuracion', 
    label: 'Configuración', 
    icon: 'fas fa-cog', 
    path: '/configuracion', 
    requiredPermission: 'acceder_configuracion'
  }
]
```

---

## 📝 Permisos Disponibles

### Módulo: Dashboard
- `ver_dashboard` - Ver el dashboard principal
- `ver_estadisticas` - Ver estadísticas y gráficos

### Módulo: Peticiones
- `ver_peticiones` - Ver lista de peticiones
- `crear_peticiones` - Crear nuevas peticiones
- `editar_peticiones` - Editar peticiones existentes
- `eliminar_peticiones` - Eliminar peticiones
- `admin_peticiones` - Administración completa de peticiones
- `exportar_peticiones` - Exportar a Excel/PDF

### Módulo: Configuración
- `acceder_configuracion` - Acceder al módulo de configuración
- `configuracion_sistema` - Configuración completa del sistema
- `ver_usuarios` - Ver lista de usuarios
- `crear_usuarios` - Crear usuarios
- `editar_usuarios` - Editar usuarios
- `eliminar_usuarios` - Eliminar usuarios
- `gestionar_usuarios` - Gestión completa de usuarios
- `ver_roles` - Ver roles
- `crear_roles` - Crear roles
- `editar_roles` - Editar roles
- `eliminar_roles` - Eliminar roles
- `gestionar_roles` - Gestión completa de roles
- `asignar_permisos` - Asignar permisos a roles

### Módulo: Departamentos
- `ver_departamentos` - Ver departamentos
- `gestionar_departamentos` - Gestionar departamentos

### Módulo: Tablero
- `ver_tablero` - Ver el tablero
- `gestionar_tablero` - Configurar el tablero

### Módulo: Reportes
- `ver_reportes` - Ver reportes
- `generar_reportes` - Generar y exportar reportes

---

## 🎭 Roles Predefinidos y Sus Permisos

### Super Usuario
**Permisos**: TODOS ✅
- Acceso completo al sistema
- Puede hacer todo

### Director
**Permisos**:
- ✅ Ver dashboard y estadísticas
- ✅ Gestión completa de peticiones
- ✅ Gestión de departamentos
- ✅ Ver y editar usuarios (no puede crear/eliminar)
- ✅ Ver roles y jerarquías
- ✅ Generar reportes
- ❌ NO puede gestionar usuarios ni roles

### Call center
**Permisos**:
- ✅ Ver dashboard
- ✅ Ver, crear y editar peticiones
- ✅ Ver departamentos
- ❌ NO puede eliminar peticiones
- ❌ NO puede acceder a configuración

### Canalizador municipal
**Permisos**:
- ✅ Ver dashboard
- ✅ Ver y editar peticiones de su municipio
- ✅ Ver departamentos
- ✅ Ver tablero

### Departamento
**Permisos**:
- ✅ Ver dashboard
- ✅ Ver y editar peticiones de su departamento
- ✅ Ver tablero

---

## 🔧 Agregar un Nuevo Permiso

### 1. Agregar el permiso a la base de datos

```sql
INSERT INTO Permiso (Codigo, Nombre, Descripcion, Modulo)
VALUES ('exportar_reportes', 'Exportar Reportes', 'Permite exportar reportes a Excel', 'Reportes');
```

### 2. Asignarlo a los roles que lo necesiten

```sql
-- Darle el permiso a Super Usuario y Director
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT Id, (SELECT Id FROM Permiso WHERE Codigo = 'exportar_reportes')
FROM RolSistema
WHERE Nombre IN ('Super Usuario', 'Director');
```

### 3. Usar el permiso en el frontend

#### En el menú (Sidebar.vue):
```javascript
{ 
  name: 'reportes', 
  label: 'Reportes', 
  icon: 'fas fa-file-alt', 
  path: '/reportes', 
  requiredPermission: 'ver_reportes' 
}
```

#### En un componente:
```vue
<template>
  <div>
    <button v-if="puedeExportar" @click="exportar">
      Exportar
    </button>
  </div>
</template>

<script>
export default {
  computed: {
    puedeExportar() {
      const user = JSON.parse(localStorage.getItem('user'));
      return user && user.Permisos && user.Permisos.includes('exportar_reportes');
    }
  }
}
</script>
```

---

## 🛠️ Crear Nueva Vista de Gestión de Permisos

Próximamente crearemos una vista en `/configuracion/permisos` donde podrás:

1. **Ver todos los roles**
2. **Seleccionar un rol**
3. **Ver sus permisos actuales**
4. **Agregar/quitar permisos con checkboxes**
5. **Guardar cambios**

Similar a cómo funciona la asignación de roles en Usuarios.

---

## ❓ FAQ - Preguntas Frecuentes

### ¿Por qué no veo ningún menú en el Sidebar?
- El usuario no tiene roles asignados
- Los roles no tienen permisos asignados
- Ejecuta el SQL de permisos: `database/permisos_sistema.sql`

### ¿Cómo saber qué permisos tiene un usuario?
```sql
SELECT 
    u.Usuario,
    r.Nombre as Rol,
    GROUP_CONCAT(p.Codigo) as Permisos
FROM Usuario u
JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
JOIN RolSistema r ON ur.IdRolSistema = r.Id
JOIN RolPermiso rp ON r.Id = rp.IdRolSistema
JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE u.Usuario = 'admin'
GROUP BY u.Usuario, r.Nombre;
```

### ¿Cómo dar todos los permisos a un rol?
```sql
-- Dar todos los permisos al rol "Super Usuario"
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT 
    (SELECT Id FROM RolSistema WHERE Nombre = 'Super Usuario'),
    Id
FROM Permiso;
```

### ¿Un usuario puede tener múltiples roles?
¡Sí! Por eso existe la tabla `UsuarioRol`. Un usuario puede tener múltiples roles y obtiene TODOS los permisos de TODOS sus roles.

Ejemplo:
- Usuario: Juan
- Roles: Director + Call center
- Permisos: Todos los de Director + Todos los de Call center

### ¿Cómo verificar permisos en el frontend?
```javascript
// Método 1: En computed
computed: {
  puedeCrear() {
    const user = JSON.parse(localStorage.getItem('user'));
    return user?.Permisos?.includes('crear_peticiones');
  }
}

// Método 2: Directamente en template
<button v-if="$root.currentUser.usuario.Permisos.includes('crear_peticiones')">
  Crear
</button>
```

---

## 🔄 Flujo Completo del Sistema

```
1. Usuario inicia sesión
   ↓
2. Backend obtiene roles del usuario (UsuarioRol)
   ↓
3. Backend obtiene permisos de esos roles (RolPermiso)
   ↓
4. Backend envía usuario + roles + permisos al frontend
   ↓
5. Frontend guarda en localStorage
   ↓
6. Sidebar filtra menú según permisos
   ↓
7. Vistas verifican permisos para mostrar botones/funciones
```

---

## 📚 Archivos Importantes

- `database/permisos_sistema.sql` - Script SQL de permisos
- `api/check-session.php` - Carga permisos del usuario
- `src/components/Sidebar.vue` - Filtra menú por permisos
- `src/services/auth.js` - Maneja autenticación y permisos
- `src/utils/rolesHelper.js` - Funciones helper para verificar roles

---

**Fecha:** 2026-01-14  
**Versión:** 1.0
