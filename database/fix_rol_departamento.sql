-- =====================================================
-- Corrección del Rol "Departamento"
-- =====================================================
-- Este script corrige los permisos del rol Departamento
-- para que solo pueda:
--   1. Ver dashboard de bienvenida
--   2. Gestionar peticiones de su departamento
-- =====================================================

-- Paso 1: Crear el nuevo permiso específico para gestión de departamento
INSERT INTO Permiso (Codigo, Nombre, Descripcion, Modulo)
SELECT 
    'gestion_peticiones_departamento',
    'Gestión de Peticiones del Departamento',
    'Permite gestionar las peticiones asignadas al departamento del usuario',
    'Peticiones'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM Permiso WHERE Codigo = 'gestion_peticiones_departamento'
);

-- Paso 2: Obtener el ID del rol Departamento
SET @rol_departamento_id = (SELECT Id FROM RolSistema WHERE Nombre = 'Departamento' LIMIT 1);

-- Paso 3: Eliminar TODOS los permisos actuales del rol Departamento
DELETE FROM RolPermiso WHERE IdRolSistema = @rol_departamento_id;

-- Paso 4: Asignar SOLO los permisos correctos al rol Departamento
-- 4.1: Permiso para ver dashboard
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT 
    @rol_departamento_id,
    p.Id
FROM Permiso p
WHERE p.Codigo = 'ver_dashboard'
AND NOT EXISTS (
    SELECT 1 FROM RolPermiso rp 
    WHERE rp.IdRolSistema = @rol_departamento_id 
    AND rp.IdPermiso = p.Id
);

-- 4.2: Permiso para gestionar peticiones del departamento
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT 
    @rol_departamento_id,
    p.Id
FROM Permiso p
WHERE p.Codigo = 'gestion_peticiones_departamento'
AND NOT EXISTS (
    SELECT 1 FROM RolPermiso rp 
    WHERE rp.IdRolSistema = @rol_departamento_id 
    AND rp.IdPermiso = p.Id
);

-- =====================================================
-- Verificación
-- =====================================================

SELECT 
    '✅ VERIFICACIÓN: Permisos del rol Departamento' as Mensaje;

SELECT 
    rs.Nombre as 'Rol',
    p.Codigo as 'Código Permiso',
    p.Nombre as 'Nombre Permiso',
    p.Modulo as 'Módulo'
FROM RolSistema rs
INNER JOIN RolPermiso rp ON rs.Id = rp.IdRolSistema
INNER JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE rs.Nombre = 'Departamento'
ORDER BY p.Modulo, p.Nombre;

-- =====================================================
-- Resultado esperado:
-- =====================================================
-- Debe mostrar SOLO 2 permisos:
-- 1. ver_dashboard                       | Ver Dashboard              | Dashboard
-- 2. gestion_peticiones_departamento     | Gestión Peticiones Depto   | Peticiones
-- =====================================================

SELECT 
    CASE 
        WHEN COUNT(*) = 2 THEN '✅ CORRECTO: El rol Departamento tiene exactamente 2 permisos'
        WHEN COUNT(*) < 2 THEN '⚠️ ERROR: Faltan permisos para el rol Departamento'
        ELSE '⚠️ ERROR: El rol Departamento tiene permisos de más'
    END as 'Estado',
    COUNT(*) as 'Total Permisos Asignados'
FROM RolPermiso rp
INNER JOIN RolSistema rs ON rp.IdRolSistema = rs.Id
WHERE rs.Nombre = 'Departamento';

-- =====================================================
-- Usuarios con el rol Departamento
-- =====================================================
SELECT 
    '📋 Usuarios con rol Departamento:' as Info;

SELECT 
    u.Id,
    CONCAT(u.Nombre, ' ', IFNULL(u.ApellidoP, ''), ' ', IFNULL(u.ApellidoM, '')) as 'Nombre Completo',
    u.Usuario,
    u.Puesto,
    un.nombre_unidad as 'Departamento Asignado'
FROM Usuario u
INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
INNER JOIN RolSistema rs ON ur.IdRolSistema = rs.Id
LEFT JOIN unidades un ON u.IdUnidad = un.id
WHERE rs.Nombre = 'Departamento';
