# ✅ Sistema de Canalizadores - Implementación Completa

## 🎯 Resumen de cambios

### 1. Base de datos (SQL ejecutado)
- ✅ Creados permisos:
  - `peticiones_municipio` - Para Canalizador Municipal
  - `peticiones_estatal` - Para Canalizador Estatal
  - `ver_dashboard` - Para ambos roles
- ✅ Permisos asignados a los roles correspondientes

### 2. Backend (`api/peticiones.php`)
- ✅ Agregada lógica de filtrado automático según permisos:
  - **Canalizador Municipal**: Ve solo peticiones de su municipio (`IdDivisionAdm`)
  - **Canalizador Estatal**: Ve todas las peticiones, puede filtrar por `municipio_id`
  - **Admin** (`ver_peticiones`): Ve todas sin restricción
- ✅ El filtro se aplica en el backend, no en el frontend

### 3. Frontend (`src/components/Sidebar.vue`)
- ✅ Actualizado item "Peticiones" para aceptar múltiples permisos:
  ```javascript
  requiredPermission: 'ver_peticiones,peticiones_municipio,peticiones_estatal'
  ```
- ✅ Lógica de filtrado mejorada para aceptar permisos separados por coma

### 4. Frontend (`src/views/Peticiones.vue`)
- ✅ Agregado filtro de municipios **solo visible para Canalizador Estatal**
- ✅ El filtro de municipio recarga peticiones del backend automáticamente
- ✅ Función `hasPermission()` para verificar permisos del usuario
- ✅ Función `cargarMunicipios()` para cargar lista de municipios
- ✅ Watcher reactivo para el cambio de municipio

## 🔐 Roles y comportamiento

### Canalizador Municipal
- **Permiso**: `peticiones_municipio`
- **Comportamiento**: 
  - Solo ve peticiones de su municipio (campo `IdDivisionAdm` del usuario)
  - No ve el filtro de municipios (está bloqueado automáticamente)
  - El filtro se aplica en el backend, no puede evitarlo
- **Campos requeridos**: Debe tener `IdDivisionAdm` asignado en su perfil de usuario

### Canalizador Estatal
- **Permiso**: `peticiones_estatal`
- **Comportamiento**:
  - Por defecto ve peticiones de todos los municipios
  - Tiene un filtro desplegable de municipios en la interfaz
  - Puede filtrar por municipio específico si lo desea
  - El filtro es opcional

### Super Usuario / Admin
- **Permiso**: `ver_peticiones`
- **Comportamiento**:
  - Ve todas las peticiones sin restricción
  - No necesita filtros especiales

## 📝 Pasos para crear usuarios

### 1. Crear usuario Canalizador Municipal
```sql
-- 1. Crear el usuario
INSERT INTO Usuario (Usuario, Contraseña, Nombre, ApellidoP, ApellidoM, Puesto, Estatus, IdDivisionAdm)
VALUES (
    'canalizador.municipal',
    '$2y$10$hashedpassword', -- Hash de la contraseña
    'Juan',
    'Pérez',
    'López',
    'Canalizador Municipal',
    'ACTIVO',
    1 -- ⚠️ IMPORTANTE: ID del municipio (ej: 1 = Mérida)
);

-- 2. Asignar el rol
INSERT INTO UsuarioRol (IdUsuario, IdRolSistema)
VALUES (
    LAST_INSERT_ID(),
    (SELECT Id FROM RolSistema WHERE Nombre = 'Canalizador municipal')
);
```

### 2. Crear usuario Canalizador Estatal
```sql
-- 1. Crear el usuario
INSERT INTO Usuario (Usuario, Contraseña, Nombre, ApellidoP, ApellidoM, Puesto, Estatus, IdDivisionAdm)
VALUES (
    'canalizador.estatal',
    '$2y$10$hashedpassword',
    'María',
    'García',
    'Rodríguez',
    'Canalizador Estatal',
    'ACTIVO',
    NULL -- No requiere municipio específico
);

-- 2. Asignar el rol
INSERT INTO UsuarioRol (IdUsuario, IdRolSistema)
VALUES (
    LAST_INSERT_ID(),
    (SELECT Id FROM RolSistema WHERE Nombre = 'Canalizador Estatal')
);
```

## 🧪 Pruebas recomendadas

1. **Crear un usuario Canalizador Municipal**:
   - Asignarle el rol "Canalizador municipal"
   - Asignarle un `IdDivisionAdm` (ej: municipio de Mérida)
   - Iniciar sesión
   - Verificar que solo ve peticiones de ese municipio
   - Verificar que NO ve el filtro de municipios

2. **Crear un usuario Canalizador Estatal**:
   - Asignarle el rol "Canalizador Estatal"
   - NO asignarle `IdDivisionAdm` (puede ser NULL)
   - Iniciar sesión
   - Verificar que ve peticiones de todos los municipios
   - Verificar que SÍ ve el filtro de municipios
   - Probar filtrar por diferentes municipios

3. **Verificar seguridad**:
   - Intentar manipular la URL para ver peticiones de otro municipio
   - El backend debe bloquear el acceso según el permiso

## 📚 Documentación de permisos

| Permiso | Descripción | Roles asignados |
|---------|-------------|-----------------|
| `ver_dashboard` | Ver página de inicio | Todos |
| `peticiones_formulario` | Crear petición (formulario) | Formulario |
| `peticiones_municipio` | Gestionar peticiones (Municipal) | Canalizador municipal |
| `peticiones_estatal` | Gestionar peticiones (Estatal) | Canalizador Estatal |
| `ver_peticiones` | Ver todas las peticiones (Admin) | Super Usuario |

## 🐛 Troubleshooting

**Problema**: Usuario Canalizador Municipal no ve ninguna petición
**Solución**: 
1. Verificar que tiene `IdDivisionAdm` asignado
2. Verificar que existen peticiones con ese `division_id`
3. Cerrar sesión y volver a iniciar

**Problema**: Usuario Canalizador Estatal ve solo un municipio
**Solución**:
1. Verificar que tiene el permiso `peticiones_estatal` asignado
2. Verificar en la tabla `RolPermiso` que el rol tiene el permiso correcto
3. Cerrar sesión y volver a iniciar

**Problema**: No aparece el filtro de municipios
**Solución**:
1. Verificar que el usuario tiene el permiso `peticiones_estatal`
2. Verificar que los municipios se cargaron correctamente (revisar consola del navegador)
3. Recargar la página

## ✨ Próximos pasos sugeridos

1. Crear usuarios de prueba para ambos roles
2. Crear peticiones en diferentes municipios para probar el filtro
3. Probar el flujo completo de cada rol
4. Documentar cualquier comportamiento inesperado
