# 📖 Sistema de Permisos y Roles - Guía Completa

## 🚨 CONTEXTO TÉCNICO PARA IA

### Ubicación del Proyecto
- **Path absoluto**: `c:\xampp\htdocs\SISEE\`
- **Base de datos**: `sisegestion` (MySQL 8.0+)
- **Backend**: PHP 7.4+ (carpeta `api/`)
- **Frontend**: Vue.js 3 + Vite (carpeta `src/`)
- **Servidor**: XAMPP (http://localhost)

### Archivos Clave del Sistema de Permisos

#### SQL (Base de Datos)
```
c:\xampp\htdocs\SISEE\database\
├── permisos_sistema.sql              # EJECUTAR: Crea tablas Permiso y RolPermiso
└── migration_roles_multiples.sql     # EJECUTAR: Crea tabla UsuarioRol
```

#### Backend PHP
```
c:\xampp\htdocs\SISEE\api\
├── check-session.php                 # MODIFICAR: Cargar permisos del usuario
├── login.php                         # MODIFICAR: Incluir permisos en login
├── roles.php                         # Gestión de roles
├── usuarios.php                      # Gestión de usuarios
└── usuario-roles.php                 # Asignar roles a usuarios
```

#### Frontend Vue.js
```
c:\xampp\htdocs\SISEE\src\
├── components\Sidebar.vue            # USA: Filtra menú por permisos
├── utils\rolesHelper.js              # HELPER: hasRole(), hasAnyRole(), etc.
├── services\auth.js                  # Servicio de autenticación
└── views\Configuracion\Usuarios.vue  # Gestión de usuarios y roles
```

### Variables Importantes
- **VITE_API_URL**: URL del backend (default: `http://localhost/sisee/api`)
- **Base de datos**: `sisegestion`
- **Tabla principal roles**: `RolSistema`
- **Tabla permisos**: `Permiso`
- **Tabla intermedia rol-permiso**: `RolPermiso`
- **Tabla intermedia usuario-rol**: `UsuarioRol`

### Flujo de Datos
```
1. Usuario inicia sesión → login.php
2. login.php consulta UsuarioRol → obtiene roles
3. login.php consulta RolPermiso → obtiene permisos
4. Retorna: user { Roles[], Permisos[] }
5. Frontend guarda en localStorage
6. Sidebar.vue filtra menú según Permisos[]
7. Vistas verifican permisos con rolesHelper.js
```

---

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

**Archivo SQL**: `c:\xampp\htdocs\SISEE\database\permisos_sistema.sql`

**Cómo ejecutar**:
1. Abrir phpMyAdmin: http://localhost/phpmyadmin
2. Seleccionar base de datos: `sisegestion`
3. Ir a pestaña **SQL**
4. Abrir archivo: `c:\xampp\htdocs\SISEE\database\permisos_sistema.sql`
5. Copiar todo el contenido
6. Pegar en el textarea y hacer clic en **Ejecutar**

**Verificar ejecución**:
```sql
-- Debe retornar > 0 registros
SELECT COUNT(*) as TotalPermisos FROM Permiso;
SELECT COUNT(*) as AsignacionesRolPermiso FROM RolPermiso;
```

**Este script**:
- ✅ Crea la tabla `Permiso` (30+ permisos predefinidos)
- ✅ Crea la tabla `RolPermiso` (relación roles-permisos)
- ✅ Inserta permisos base del sistema
- ✅ Asigna permisos a roles existentes (Super Usuario, Director, etc.)
- ✅ Crea vistas: `v_RolesConPermisos`, `v_UsuariosConPermisos`

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

### Archivo a crear:
**Ruta**: `c:\xampp\htdocs\SISEE\src\views\Configuracion\Permisos.vue`

### Funcionalidad requerida:
1. **Ver todos los roles** (GET /api/roles.php)
2. **Seleccionar un rol** (v-model)
3. **Ver permisos actuales** (GET /api/rol-permisos.php?idRol=X)
4. **Agregar/quitar permisos** (checkboxes con v-model)
5. **Guardar cambios** (POST /api/rol-permisos.php)

### Estructura del componente:
```vue
<template>
  <div class="gestion-permisos">
    <!-- Selector de rol -->
    <select v-model="rolSeleccionado">
      <option v-for="rol in roles" :value="rol.Id">{{ rol.Nombre }}</option>
    </select>
    
    <!-- Checkboxes de permisos por módulo -->
    <div v-for="modulo in modulosAgrupados">
      <h3>{{ modulo.nombre }}</h3>
      <label v-for="permiso in modulo.permisos">
        <input type="checkbox" v-model="permisosSeleccionados" :value="permiso.Id">
        {{ permiso.Nombre }}
      </label>
    </div>
    
    <!-- Botón guardar -->
    <button @click="guardarPermisos">Guardar</button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      roles: [],
      permisosDisponibles: [],
      permisosSeleccionados: [],
      rolSeleccionado: null
    }
  },
  computed: {
    modulosAgrupados() {
      // Agrupar permisos por módulo
    }
  },
  methods: {
    async cargarRoles() { /* GET /api/roles.php */ },
    async cargarPermisos() { /* GET /api/permisos.php */ },
    async cargarPermisosRol(idRol) { /* GET /api/rol-permisos.php */ },
    async guardarPermisos() { /* POST /api/rol-permisos.php */ }
  }
}
</script>
```

Similar a cómo funciona la asignación de roles en `Usuarios.vue`.

---

## 🔍 DIAGNÓSTICO Y VERIFICACIÓN

### Verificar que el sistema está funcionando:

#### 1. Verificar Base de Datos
```sql
-- En phpMyAdmin, ejecutar:

-- ¿Existen las tablas?
SHOW TABLES LIKE 'Permiso';
SHOW TABLES LIKE 'RolPermiso';
SHOW TABLES LIKE 'UsuarioRol';

-- ¿Hay permisos en la BD?
SELECT COUNT(*) as TotalPermisos FROM Permiso;

-- ¿Hay relaciones rol-permiso?
SELECT r.Nombre, COUNT(rp.IdPermiso) as TotalPermisos
FROM RolSistema r
LEFT JOIN RolPermiso rp ON r.Id = rp.IdRolSistema
GROUP BY r.Id, r.Nombre;

-- ¿Los usuarios tienen roles?
SELECT u.Usuario, COUNT(ur.IdRolSistema) as TotalRoles
FROM Usuario u
LEFT JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
GROUP BY u.Id, u.Usuario;
```

#### 2. Verificar Backend PHP
```bash
# En terminal PowerShell
cd c:\xampp\htdocs\SISEE\api

# Verificar que existen los archivos:
ls check-session.php
ls login.php
ls usuario-roles.php

# Verificar logs de errores:
cat logs\error.log
```

#### 3. Verificar Frontend
```javascript
// En consola del navegador (F12):

// ¿El usuario tiene permisos?
const user = JSON.parse(localStorage.getItem('user'));
console.log('Permisos:', user.Permisos);
console.log('Roles:', user.Roles);

// ¿Las funciones helper funcionan?
import { hasRole } from '@/utils/rolesHelper';
console.log('Es admin?', hasRole('Super Usuario'));
```

#### 4. Verificar VITE_API_URL
```bash
# Verificar .env
cat c:\xampp\htdocs\SISEE\.env

# Debe contener:
VITE_API_URL=http://localhost/sisee/api
```

### Checklist de Verificación

- [ ] Tabla `Permiso` creada con 30+ registros
- [ ] Tabla `RolPermiso` tiene asignaciones
- [ ] Tabla `UsuarioRol` tiene usuarios con roles
- [ ] `check-session.php` retorna array `Permisos[]`
- [ ] `login.php` retorna `user.Roles[]` y `user.Permisos[]`
- [ ] `rolesHelper.js` existe en `src/utils/`
- [ ] localStorage tiene `user` con `Permisos[]`
- [ ] Sidebar muestra opciones según permisos
- [ ] No hay errores en consola de navegador
- [ ] No hay errores en `api/logs/error.log`

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
