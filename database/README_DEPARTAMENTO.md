# Corrección del Rol Departamento

## Problema Identificado
El rol "Departamento" tenía permisos incorrectos que le permitían acceder a vistas y funciones no autorizadas.

## Solución Implementada

### 1. Nuevo Permiso Creado
- **Código**: `gestion_peticiones_departamento`
- **Nombre**: "Gestión de Peticiones del Departamento"
- **Descripción**: Permite gestionar las peticiones asignadas al departamento del usuario
- **Módulo**: Peticiones

### 2. Permisos Correctos del Rol Departamento
El rol "Departamento" ahora tiene **SOLO** estos 2 permisos:

| Permiso | Descripción | Acceso |
|---------|-------------|--------|
| `ver_dashboard` | Ver Dashboard | Dashboard de bienvenida |
| `gestion_peticiones_departamento` | Gestión de Peticiones del Depto | Vista TablaDepartamento |

### 3. Cambios en el Sistema

#### SQL (fix_rol_departamento.sql)
```sql
-- Crea el nuevo permiso
-- Elimina TODOS los permisos anteriores del rol Departamento
-- Asigna SOLO los 2 permisos correctos
-- Incluye verificaciones y listado de usuarios afectados
```

#### Router (TablaDepartamentos.js)
- Agregada protección con `requiresAuth: true`
- Agregado `requiredPermission: 'gestion_peticiones_departamento'`

#### Sidebar (Sidebar.vue)
- Item renombrado de "Departamentos" a "Mis Peticiones"
- Icono cambiado a `fa-clipboard-list` (más apropiado)
- Usa el nuevo permiso `gestion_peticiones_departamento`

## Instrucciones de Aplicación

### Paso 1: Ejecutar el SQL
```bash
# En tu cliente MySQL/phpMyAdmin, ejecuta:
database/fix_rol_departamento.sql
```

El script:
1. ✅ Crea el nuevo permiso `gestion_peticiones_departamento`
2. ✅ Limpia los permisos incorrectos del rol Departamento
3. ✅ Asigna solo los 2 permisos correctos
4. ✅ Verifica que todo esté correcto
5. ✅ Muestra los usuarios afectados

### Paso 2: Verificar en la Base de Datos
Después de ejecutar el SQL, deberías ver:

```
✅ CORRECTO: El rol Departamento tiene exactamente 2 permisos
```

### Paso 3: Probar con un Usuario de Departamento

#### 3.1. Crear usuario de prueba (opcional)
```sql
-- Si necesitas crear un usuario de prueba
INSERT INTO Usuario (Nombre, Email, Password, IdUnidad) 
VALUES ('Usuario Departamento', 'dept@test.com', 'password_hash', ID_DE_TU_DEPARTAMENTO);

-- Asignar el rol Departamento
INSERT INTO UsuarioRol (IdUsuario, IdRolSistema)
SELECT 
    (SELECT Id FROM Usuario WHERE Email = 'dept@test.com'),
    (SELECT Id FROM RolSistema WHERE Nombre = 'Departamento');
```

#### 3.2. Iniciar sesión y verificar
Al iniciar sesión con un usuario con rol "Departamento", debe ver:
- ✅ Dashboard de bienvenida
- ✅ Sidebar con item "Mis Peticiones"
- ✅ Vista TablaDepartamento (solo peticiones de su departamento)

**NO debe ver**:
- ❌ Configuración
- ❌ Peticiones completas
- ❌ Tablero general
- ❌ Otros módulos administrativos

## Comportamiento del Usuario Departamento

### Vista "Mis Peticiones" (TablaDepartamento)
- Muestra **solo** las peticiones asignadas a su departamento
- Puede ver detalles completos de cada petición
- Puede cambiar el estado de las peticiones asignadas
- Puede ver el historial de cambios
- Solo ve peticiones donde `departamento_id = IdUnidad del usuario`

### Dashboard
- Vista de bienvenida con información general
- Sin acceso a estadísticas administrativas

## Consideraciones Importantes

### IdUnidad en Usuario
**CRÍTICO**: Los usuarios con rol "Departamento" DEBEN tener un valor válido en `Usuario.IdUnidad`:

```sql
-- Verificar usuarios sin departamento asignado
SELECT u.Id, u.Nombre, u.Email 
FROM Usuario u
INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
INNER JOIN RolSistema rs ON ur.IdRolSistema = rs.Id
WHERE rs.Nombre = 'Departamento'
AND (u.IdUnidad IS NULL OR u.IdUnidad = 0);
```

Si hay usuarios sin departamento asignado:
```sql
-- Asignar departamento a un usuario
UPDATE Usuario 
SET IdUnidad = [ID_DEPARTAMENTO] 
WHERE Id = [ID_USUARIO];
```

## Diferencias con Otros Roles

| Característica | Departamento | Canalizador Municipal | Canalizador Estatal | Admin |
|----------------|--------------|----------------------|---------------------|-------|
| Ver Dashboard | ✅ | ✅ | ✅ | ✅ |
| Peticiones | Solo su dept | Solo su municipio | Todos los municipios | Todas |
| Cambiar estado | ✅ Su dept | ❌ | ❌ | ✅ |
| Configuración | ❌ | ❌ | ❌ | ✅ |
| Ver tablero | ❌ | ❌ | ❌ | ✅ |

## Troubleshooting

### Problema: Usuario de departamento no ve sus peticiones
**Solución**: Verificar que:
1. El usuario tiene `IdUnidad` asignado
2. Hay peticiones asignadas a ese departamento en `PeticionDepartamento`
3. El permiso `gestion_peticiones_departamento` está asignado al rol

### Problema: Usuario ve otras peticiones
**Solución**: 
- Verificar que la tabla `PeticionDepartamento` está correctamente configurada
- Revisar el código de TablaDepartamento.vue que filtra por `departamento_id`

### Problema: No aparece el item en el Sidebar
**Solución**:
1. Verificar que el usuario tiene el permiso `gestion_peticiones_departamento`
2. Limpiar caché del navegador
3. Cerrar sesión y volver a iniciar

## Archivos Modificados
1. ✅ `database/fix_rol_departamento.sql` - Script de corrección
2. ✅ `src/router/routes/TablaDepartamentos.js` - Protección de ruta
3. ✅ `src/components/Sidebar.vue` - Item con permiso correcto
4. ✅ `database/README_DEPARTAMENTO.md` - Esta documentación

## Próximos Pasos
1. Ejecutar el SQL de corrección
2. Verificar los permisos en la base de datos
3. Probar con un usuario de departamento
4. Verificar que no puede acceder a otras vistas
5. Confirmar que solo ve peticiones de su departamento
