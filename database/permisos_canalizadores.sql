-- ============================================
-- Script para agregar permisos de Canalizadores
-- ============================================

USE sisegestion;

-- 1. Insertar los nuevos permisos
INSERT INTO Permiso (Codigo, Nombre, Descripcion, Modulo) VALUES
('peticiones_municipio', 'Gestionar Peticiones (Municipal)', 'Permite ver y gestionar peticiones únicamente del municipio asignado al usuario', 'Peticiones'),
('peticiones_estatal', 'Gestionar Peticiones (Estatal)', 'Permite ver y gestionar peticiones de todos los municipios con filtro opcional', 'Peticiones');

-- 2. Obtener IDs de los permisos
SET @permiso_municipal_id = (SELECT Id FROM Permiso WHERE Codigo = 'peticiones_municipio' LIMIT 1);
SET @permiso_estatal_id = (SELECT Id FROM Permiso WHERE Codigo = 'peticiones_estatal' LIMIT 1);

-- 3. Obtener IDs de los roles
SET @rol_canalizador_municipal_id = (SELECT Id FROM RolSistema WHERE Nombre = 'Canalizador municipal' LIMIT 1);
SET @rol_canalizador_estatal_id = (SELECT Id FROM RolSistema WHERE Nombre = 'Canalizador Estatal' LIMIT 1);

-- 4. Verificar que los roles existen
SELECT 
    CASE 
        WHEN @rol_canalizador_municipal_id IS NOT NULL 
        THEN CONCAT('✅ Rol "Canalizador municipal" encontrado con ID: ', @rol_canalizador_municipal_id)
        ELSE '❌ ERROR: Rol "Canalizador municipal" no encontrado'
    END AS VerificacionMunicipal;

SELECT 
    CASE 
        WHEN @rol_canalizador_estatal_id IS NOT NULL 
        THEN CONCAT('✅ Rol "Canalizador Estatal" encontrado con ID: ', @rol_canalizador_estatal_id)
        ELSE '❌ ERROR: Rol "Canalizador Estatal" no encontrado'
    END AS VerificacionEstatal;

-- 5. Asignar permiso ver_dashboard a ambos roles (para que puedan ver el menú)
SET @permiso_dashboard_id = (SELECT Id FROM Permiso WHERE Codigo = 'ver_dashboard' LIMIT 1);

INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT @rol_canalizador_municipal_id, @permiso_dashboard_id
FROM DUAL
WHERE @rol_canalizador_municipal_id IS NOT NULL 
  AND @permiso_dashboard_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM RolPermiso 
    WHERE IdRolSistema = @rol_canalizador_municipal_id 
    AND IdPermiso = @permiso_dashboard_id
  );

INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT @rol_canalizador_estatal_id, @permiso_dashboard_id
FROM DUAL
WHERE @rol_canalizador_estatal_id IS NOT NULL 
  AND @permiso_dashboard_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM RolPermiso 
    WHERE IdRolSistema = @rol_canalizador_estatal_id 
    AND IdPermiso = @permiso_dashboard_id
  );

-- 6. Asignar permiso de peticiones municipal al Canalizador Municipal
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT @rol_canalizador_municipal_id, @permiso_municipal_id
FROM DUAL
WHERE @rol_canalizador_municipal_id IS NOT NULL 
  AND @permiso_municipal_id IS NOT NULL;

-- 7. Asignar permiso de peticiones estatal al Canalizador Estatal
INSERT INTO RolPermiso (IdRolSistema, IdPermiso)
SELECT @rol_canalizador_estatal_id, @permiso_estatal_id
FROM DUAL
WHERE @rol_canalizador_estatal_id IS NOT NULL 
  AND @permiso_estatal_id IS NOT NULL;

-- 8. Verificar las asignaciones
SELECT 
    r.Nombre AS Rol,
    p.Codigo AS CodigoPermiso,
    p.Nombre AS NombrePermiso,
    p.Modulo
FROM RolPermiso rp
JOIN RolSistema r ON rp.IdRolSistema = r.Id
JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE r.Nombre IN ('Canalizador municipal', 'Canalizador Estatal')
ORDER BY r.Nombre, p.Codigo;

-- 9. Resumen final
SELECT 
    r.Nombre AS Rol,
    COUNT(rp.IdPermiso) AS CantidadPermisos,
    GROUP_CONCAT(p.Codigo SEPARATOR ', ') AS Permisos
FROM RolPermiso rp
JOIN RolSistema r ON rp.IdRolSistema = r.Id
JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE r.Nombre IN ('Canalizador municipal', 'Canalizador Estatal')
GROUP BY r.Nombre;
