-- ============================================
-- Agregar permiso de Dashboard al rol Formulario
-- ============================================

USE sisegestion;

-- Obtener IDs necesarios
SET @rol_formulario_id = (SELECT Id FROM RolSistema WHERE Nombre = 'Formulario' LIMIT 1);
SET @permiso_dashboard_id = (SELECT Id FROM Permiso WHERE Codigo = 'ver_dashboard' LIMIT 1);

-- Asignar permiso de dashboard al rol Formulario (si no lo tiene)
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT @rol_formulario_id, @permiso_dashboard_id
FROM DUAL
WHERE @rol_formulario_id IS NOT NULL 
  AND @permiso_dashboard_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM RolPermiso 
    WHERE IdRolSistema = @rol_formulario_id 
    AND IdPermiso = @permiso_dashboard_id
  );

-- Verificar todos los permisos del rol Formulario
SELECT 
    r.Nombre AS Rol,
    p.Codigo AS CodigoPermiso,
    p.Nombre AS NombrePermiso
FROM RolPermiso rp
JOIN RolSistema r ON rp.IdRolSistema = r.Id
JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE r.Nombre = 'Formulario'
ORDER BY p.Codigo;
