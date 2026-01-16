-- ============================================
-- Script para agregar permiso de Formulario
-- ============================================

USE sisegestion;

-- 1. Insertar el nuevo permiso
INSERT INTO Permiso (Codigo, Nombre, Descripcion, Modulo)
VALUES (
    'peticiones_formulario',
    'Crear Petición (Formulario)',
    'Permite acceder al formulario para crear nuevas peticiones ciudadanas',
    'Peticiones'
);

-- 2. Obtener el ID del permiso recién creado
SET @permiso_id = LAST_INSERT_ID();

-- 3. Obtener el ID del rol "Formulario"
SET @rol_formulario_id = (SELECT Id FROM RolSistema WHERE Nombre = 'Formulario' LIMIT 1);

-- 4. Verificar que el rol existe
SELECT 
    CASE 
        WHEN @rol_formulario_id IS NOT NULL 
        THEN CONCAT('✅ Rol "Formulario" encontrado con ID: ', @rol_formulario_id)
        ELSE '❌ ERROR: Rol "Formulario" no encontrado. Créalo primero.'
    END AS Verificacion;

-- 5. Asignar el permiso al rol "Formulario" (solo si el rol existe)
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT @rol_formulario_id, @permiso_id
FROM DUAL
WHERE @rol_formulario_id IS NOT NULL;

-- 6. Verificar la asignación
SELECT 
    r.Nombre AS Rol,
    p.Codigo AS CodigoPermiso,
    p.Nombre AS NombrePermiso,
    p.Modulo,
    rp.FechaAsignacion
FROM RolPermiso rp
JOIN RolSistema r ON rp.IdRolSistema = r.Id
JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE p.Codigo = 'peticiones_formulario';

-- 7. Mostrar todos los permisos del rol "Formulario"
SELECT 
    r.Nombre AS Rol,
    GROUP_CONCAT(p.Codigo SEPARATOR ', ') AS Permisos
FROM RolPermiso rp
JOIN RolSistema r ON rp.IdRolSistema = r.Id
JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE r.Nombre = 'Formulario'
GROUP BY r.Nombre;
