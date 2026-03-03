-- ===============================================
-- Verificar y asignar permisos para Notificaciones
-- ===============================================

-- 1. Ver qué permiso tiene el rol Departamento (RolId = 9)
SELECT 
    r.Id as RolId,
    r.Nombre as RolNombre,
    p.Codigo as PermisoCodigo,
    p.Nombre as PermisoNombre
FROM RolSistema r
LEFT JOIN RolPermiso rp ON r.Id = rp.IdRolSistema
LEFT JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE r.Id = 9
ORDER BY p.Codigo;

-- 2. Verificar si existe el permiso gestion_peticiones_departamento
SELECT * FROM Permiso WHERE Codigo = 'gestion_peticiones_departamento';

-- 3. Si el rol Departamento (9) ya tiene 'gestion_peticiones_departamento', 
--    entonces ya debería poder ver la opción de Notificaciones en el navbar

-- 4. Verificar usuarios con rol Departamento
SELECT 
    u.Id,
    u.Usuario,
    u.Nombre,
    u.Email,
    u.IdUnidad,
    uni.nombre_unidad,
    r.Nombre as Rol
FROM Usuario u
INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
INNER JOIN RolSistema r ON ur.IdRolSistema = r.Id
LEFT JOIN unidades uni ON u.IdUnidad = uni.id
WHERE ur.IdRolSistema = 9
ORDER BY u.Id;

-- 5. Ver configuraciones de notificaciones existentes
SELECT 
    nc.*,
    u.Usuario,
    u.Email as EmailUsuario,
    uni.nombre_unidad
FROM NotificacionConfiguracion nc
INNER JOIN Usuario u ON nc.IdUsuario = u.Id
LEFT JOIN unidades uni ON nc.IdUnidad = uni.id
ORDER BY nc.Id;
