# Guía de Implementación: Sistema de Roles Múltiples

## � IMPORTANTE: LEE ESTO PRIMERO

Si eres una IA leyendo este documento para ayudar con el proyecto:

1. **Ubicación del proyecto**: `c:\xampp\htdocs\SISEE\`
2. **Base de datos**: `sisegestion` en MySQL (acceso: http://localhost/phpmyadmin)
3. **Backend**: PHP 7.4+ con PDO en carpeta `api/`
4. **Frontend**: Vue.js 3 con Vite en carpeta `src/`
5. **Servidor**: XAMPP (Apache + MySQL en puerto 3306)

### 🔗 Archivos Relacionados
- **Este archivo**: Guía paso a paso de implementación
- **`CAMBIOS_ROLES_MULTIPLES.md`**: Resumen de cambios realizados
- **`Sistema_Permisos.md`**: Documentación del sistema de permisos

### 📂 Rutas Críticas
```
BACKEND (PHP):
├── c:\xampp\htdocs\SISEE\api\usuarios.php          # Gestión de usuarios
├── c:\xampp\htdocs\SISEE\api\usuario-roles.php    # NUEVO: Gestión de roles
├── c:\xampp\htdocs\SISEE\api\check-session.php    # MODIFICAR: Cargar roles
├── c:\xampp\htdocs\SISEE\api\login.php            # MODIFICAR: Autenticación
└── c:\xampp\htdocs\SISEE\config\database.php      # Conexión BD

FRONTEND (Vue.js):
├── c:\xampp\htdocs\SISEE\src\views\Configuracion\Usuarios.vue  # MODIFICAR
├── c:\xampp\htdocs\SISEE\src\utils\rolesHelper.js              # NUEVO
├── c:\xampp\htdocs\SISEE\src\components\Sidebar.vue            # Usa permisos
└── c:\xampp\htdocs\SISEE\src\services\auth.js                  # Auth service

BASE DE DATOS (SQL):
└── c:\xampp\htdocs\SISEE\database\migration_roles_multiples.sql  # EJECUTAR PRIMERO
```

---

## �📋 Resumen
Esta guía explica cómo implementar el sistema de roles múltiples para usuarios en SISEE.

## 🎯 Objetivo
Permitir que un usuario tenga múltiples roles asignados simultáneamente, y que el administrador pueda controlar qué vistas/funcionalidades ve cada usuario basándose en sus roles.

---

## 📝 PASO 1: Ejecutar la Migración de Base de Datos

### 1.1 Abrir phpMyAdmin o tu cliente MySQL
```
http://localhost/phpmyadmin
```

### 1.2 Ejecutar el script SQL
1. Selecciona la base de datos `sisegestion`
2. Ve a la pestaña **SQL**
3. Abre el archivo: `database/migration_roles_multiples.sql`
4. Copia y pega todo el contenido
5. Haz clic en **Ejecutar**

### 1.3 Verificar la migración
Ejecuta estas consultas para verificar:

```sql
-- Ver que la tabla fue creada
SHOW TABLES LIKE 'UsuarioRol';

-- Ver que los datos fueron migrados
SELECT COUNT(*) FROM UsuarioRol;

-- Ver usuarios con sus roles
SELECT * FROM v_UsuariosConRoles;
```

---

## 🔧 PASO 2: Actualizar el Endpoint de Login

### Archivo a modificar:
**Ruta completa**: `c:\xampp\htdocs\SISEE\api\login.php`

### ⚠️ IMPORTANTE: Verificar estructura
Antes de modificar, verificar que el archivo tenga:
- Una clase `Login` con método `login()`
- Una consulta SQL que retorna datos del usuario
- Un array de retorno con `success`, `message`, y `user`

### Buscar en login.php (aproximadamente línea 208):
```php
class Login {
    private $conn;
    private $table_usuario = "Usuario";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($usuario, $password) {
        // Buscar usuario
        $query = "SELECT Id, Usuario, Password, Nombre, ApellidoP, ApellidoM, 
                         Puesto, Estatus, IdDivisionAdm, IdUnidad, IdRolSistema
                  FROM " . $this->table_usuario . "
                  WHERE Usuario = :usuario";
```

### Agregar después de obtener el usuario (aproximadamente línea 250):
```php
// Obtener todos los roles del usuario
$queryRoles = "SELECT 
                r.Id, 
                r.Nombre, 
                r.Descripcion
               FROM UsuarioRol ur
               JOIN RolSistema r ON ur.IdRolSistema = r.Id
               WHERE ur.IdUsuario = :user_id
               ORDER BY r.Nombre";

$stmtRoles = $this->conn->prepare($queryRoles);
$stmtRoles->bindParam(':user_id', $row['Id'], PDO::PARAM_INT);
$stmtRoles->execute();

$roles = [];
$rolesIds = [];
$rolesNombres = [];

while ($rol = $stmtRoles->fetch(PDO::FETCH_ASSOC)) {
    $roles[] = $rol;
    $rolesIds[] = $rol['Id'];
    $rolesNombres[] = $rol['Nombre'];
}

// Agregar a la sesión y respuesta
$_SESSION['roles'] = $roles;
$_SESSION['roles_ids'] = $rolesIds;
$_SESSION['roles_nombres'] = $rolesNombres;
```

### Actualizar el return (aproximadamente línea 270):
```php
return array(
    "success" => true,
    "message" => "Login exitoso",
    "user" => array(
        "Id" => $row['Id'],
        "Usuario" => $row['Usuario'],
        "Nombre" => $row['Nombre'],
        "ApellidoP" => $row['ApellidoP'],
        "ApellidoM" => $row['ApellidoM'],
        "Puesto" => $row['Puesto'],
        "Estatus" => $row['Estatus'],
        "IdDivisionAdm" => $row['IdDivisionAdm'],
        "IdUnidad" => $row['IdUnidad'],
        "IdRolSistema" => $row['IdRolSistema'], // Mantener por compatibilidad
        "Roles" => $roles,  // NUEVO
        "RolesIds" => $rolesIds,  // NUEVO
        "RolesNombres" => $rolesNombres  // NUEVO
    )
);
```

---

## 🎨 PASO 3: Actualizar el Componente de Usuarios

### Archivo a modificar:
**Ruta completa**: `c:\xampp\htdocs\SISEE\src\views\Configuracion\Usuarios.vue`

### ⚠️ Estructura actual del componente:
```vue
<template>
  <div class="configuracion-usuarios">
    <!-- Filtros y búsqueda -->
    <!-- Tabla de usuarios -->
    <!-- Modal de formulario -->
    <!-- Modal de detalles -->
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      usuarios: [],
      rolesDisponibles: [],  // AGREGAR ESTO
      usuarioForm: { /* campos */ },
      // ...
    }
  },
  methods: {
    cargarDatos() { /* ... */ },
    cargarRoles() { /* AGREGAR ESTO */ },
    // ...
  }
}
</script>
```

### 3.1 Leer todos los roles disponibles
Agregar método `cargarRoles()` en el objeto `methods`:

```javascript
async cargarRoles() {
  try {
    const response = await axios.get(`${this.backendUrl}/roles.php`);
    this.rolesDisponibles = response.data.records || [];
  } catch (error) {
    console.error('Error al cargar roles:', error);
  }
}
```

### 3.2 Modificar el formulario para seleccionar múltiples roles

Cambiar el select de rol único por un multiselect o checkboxes:

```html
<div class="form-group">
  <label>Roles: *</label>
  <div class="roles-selection">
    <div 
      v-for="rol in rolesDisponibles" 
      :key="rol.Id"
      class="role-checkbox-item"
    >
      <input
        type="checkbox"
        :id="'rol-' + rol.Id"
        :value="rol.Id"
        v-model="usuarioForm.RolesSeleccionados"
      />
      <label :for="'rol-' + rol.Id">
        {{ rol.Nombre }}
        <span class="rol-descripcion">{{ rol.Descripcion }}</span>
      </label>
    </div>
  </div>
</div>
```

### 3.3 Actualizar el data() para incluir roles seleccionados

```javascript
data() {
  return {
    usuarioForm: {
      Id: null,
      Usuario: '',
      Nombre: '',
      // ... otros campos
      RolesSeleccionados: [] // NUEVO: Array de IDs de roles
    },
    rolesDisponibles: [] // NUEVO: Lista de todos los roles
  }
}
```

### 3.4 Al crear/editar usuario, guardar sus roles

```javascript
async guardarUsuario() {
  try {
    const formData = { ...this.usuarioForm };
    
    let userId;
    if (this.modoEdicion) {
      await axios.put(`${this.backendUrl}/usuarios.php`, formData);
      userId = formData.Id;
    } else {
      const response = await axios.post(`${this.backendUrl}/usuarios.php`, formData);
      userId = response.data.userId; // Asume que el backend retorna el ID
    }
    
    // Guardar los roles del usuario
    await axios.post(`${this.backendUrl}/usuario-roles.php`, {
      idUsuario: userId,
      roles: this.usuarioForm.RolesSeleccionados
    });
    
    this.$toast.success('Usuario guardado correctamente');
    this.showModal = false;
    await this.cargarDatos();
  } catch (error) {
    console.error('Error al guardar usuario:', error);
    this.$toast.error('Error al guardar el usuario');
  }
}
```

### 3.5 Al cargar un usuario para editar, cargar sus roles

```javascript
async editarUsuario(usuario) {
  this.modoEdicion = true;
  this.usuarioForm = { ...usuario };
  
  // Cargar los roles del usuario
  try {
    const response = await axios.get(
      `${this.backendUrl}/usuario-roles.php?idUsuario=${usuario.Id}`
    );
    this.usuarioForm.RolesSeleccionados = response.data.records.map(r => r.IdRolSistema);
  } catch (error) {
    console.error('Error al cargar roles del usuario:', error);
    this.usuarioForm.RolesSeleccionados = [];
  }
  
  this.showModal = true;
}
```

---

## 🔐 PASO 4: Usar el Helper de Roles en tus Vistas

### 4.1 Importar el helper
```javascript
import { hasRole, hasAnyRole, isAdmin, canAccessConfig } from '@/utils/rolesHelper';
```

### 4.2 Ejemplos de uso

#### Mostrar/ocultar elementos basándose en roles:
```vue
<template>
  <div class="vista-configuracion">
    <!-- Solo visible para Super Usuario o Director -->
    <button v-if="hasAnyRole(['Super Usuario', 'Director'])" @click="editarConfig">
      Editar Configuración
    </button>
    
    <!-- Solo visible para administradores -->
    <div v-if="isAdmin" class="admin-panel">
      Panel de Administración
    </div>
    
    <!-- Solo visible para Call center -->
    <div v-if="hasRole('Call center')" class="call-center-tools">
      Herramientas de Call Center
    </div>
  </div>
</template>

<script>
import { hasRole, hasAnyRole, isAdmin } from '@/utils/rolesHelper';

export default {
  methods: {
    hasRole,
    hasAnyRole,
    isAdmin
  }
}
</script>
```

#### Restringir acceso a rutas:
```javascript
// En router/index.js
import { hasAnyRole, isAdmin } from '@/utils/rolesHelper';

{
  path: '/configuracion',
  component: () => import('@/views/Configuracion'),
  beforeEnter: (to, from, next) => {
    if (isAdmin()) {
      next();
    } else {
      next('/dashboard'); // Redirigir si no tiene permiso
    }
  }
}
```

---

## 📊 PASO 5: Actualizar la Vista de la Lista de Usuarios

En la tabla de usuarios, mostrar los roles de cada usuario:

```vue
<div class="usuario-roles">
  <span 
    v-for="rol in usuario.Roles" 
    :key="rol.Id"
    class="badge-rol"
  >
    {{ rol.Nombre }}
  </span>
</div>
```

Y agregar CSS:
```css
.usuario-roles {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.badge-rol {
  padding: 4px 10px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 11px;
  font-weight: 500;
}
```

---

## 🔄 PASO 6: Actualizar usuarios.php para retornar roles

En `api/usuarios.php`, en el GET:

```php
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Obtener roles del usuario
    $queryRoles = "SELECT r.Id, r.Nombre 
                   FROM UsuarioRol ur
                   JOIN RolSistema r ON ur.IdRolSistema = r.Id
                   WHERE ur.IdUsuario = ?";
    $stmtRoles = $db->prepare($queryRoles);
    $stmtRoles->execute([$row['Id']]);
    $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
    
    $usuario_item = array(
        "Id" => $row['Id'],
        "Usuario" => $row['Usuario'],
        // ... otros campos
        "Roles" => $roles  // NUEVO
    );
    
    array_push($usuarios_arr["records"], $usuario_item);
}
```

---

## ⚠️ IMPORTANTE: No Eliminar la Columna IdRolSistema AÚN

**NO ejecutes el paso 4 del SQL (eliminar columna IdRolSistema) hasta que:**

1. ✅ Hayas actualizado TODO el código PHP y Vue.js
2. ✅ Hayas probado que el sistema funciona correctamente
3. ✅ Hayas verificado que todos los usuarios tienen sus roles asignados en UsuarioRol
4. ✅ Hayas actualizado todas las vistas que verifican roles

**Mantén IdRolSistema por compatibilidad** durante un periodo de transición.

---

## 🧪 PASO 7: Probar el Sistema

### 7.1 Verificar que los roles se carguen correctamente
1. Inicia sesión
2. Abre la consola del navegador (F12)
3. Ejecuta: `console.log(JSON.parse(localStorage.getItem('user')))`
4. Verifica que existan `Roles`, `RolesIds`, y `RolesNombres`

### 7.2 Probar asignación de múltiples roles
1. Ve a Gestión de Usuarios
2. Edita un usuario
3. Selecciona múltiples roles
4. Guarda
5. Verifica en la base de datos que se guardaron en UsuarioRol

### 7.3 Probar restricciones de vistas
1. Crea un usuario de prueba con rol "Call center"
2. Inicia sesión con ese usuario
3. Verifica que solo vea las vistas permitidas
4. Intenta acceder a rutas restringidas

---

## 📚 Referencia Rápida de Funciones

| Función | Uso | Ejemplo |
|---------|-----|---------|
| `hasRole(nombre)` | ¿Tiene este rol específico? | `hasRole('Director')` |
| `hasAnyRole([...])` | ¿Tiene alguno de estos roles? | `hasAnyRole(['Director', 'Super Usuario'])` |
| `hasAllRoles([...])` | ¿Tiene todos estos roles? | `hasAllRoles(['Call center', 'Departamento'])` |
| `isAdmin()` | ¿Es administrador? | `isAdmin()` |
| `canAccessConfig()` | ¿Puede acceder a configuración? | `canAccessConfig()` |
| `getRolesDisplayString()` | Obtener cadena de roles | `getRolesDisplayString()` → "Director, Super Usuario" |

---

## 🐛 Solución de Problemas

### Los roles no se cargan después del login
- Verifica que `check-session.php` y `login.php` estén actualizados
- Revisa la consola del navegador para errores
- Verifica que la tabla UsuarioRol tenga datos

### Los usuarios no pueden ver las vistas después de asignar roles
- Asegúrate de que el usuario cierre sesión y vuelva a iniciar
- Verifica que el localStorage tenga los roles correctos
- Revisa que las vistas usen las funciones del rolesHelper correctamente

### Error al guardar roles
- Verifica que el endpoint `usuario-roles.php` exista
- Revisa los logs de PHP para errores
- Asegúrate de que la tabla UsuarioRol se haya creado correctamente

---

## 📞 Soporte

Si encuentras problemas, verifica:
1. Los logs de PHP en `api/logs/`
2. La consola del navegador (F12)
3. Que todas las migraciones SQL se ejecutaron correctamente
4. Que los archivos PHP tengan los permisos correctos

---

**Última actualización:** 2026-01-14
