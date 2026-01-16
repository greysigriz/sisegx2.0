-- ================================================================
-- MIGRACIÓN: SISTEMA DE ROLES MÚLTIPLES
-- Fecha: 2026-01-14
-- Descripción: Implementa tabla intermedia para asignar múltiples roles a usuarios
-- ================================================================

USE sisegestion;

-- ================================================================
-- PASO 1: Crear tabla intermedia UsuarioRol
-- ================================================================
CREATE TABLE IF NOT EXISTS `UsuarioRol` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `IdUsuario` int(11) NOT NULL,
  `IdRolSistema` int(11) NOT NULL,
  `FechaAsignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `AsignadoPor` int(11) DEFAULT NULL COMMENT 'ID del usuario que asignó el rol',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_usuario_rol` (`IdUsuario`, `IdRolSistema`),
  KEY `IdUsuario` (`IdUsuario`),
  KEY `IdRolSistema` (`IdRolSistema`),
  KEY `AsignadoPor` (`AsignadoPor`),
  CONSTRAINT `UsuarioRol_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `Usuario` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `UsuarioRol_ibfk_2` FOREIGN KEY (`IdRolSistema`) REFERENCES `RolSistema` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `UsuarioRol_ibfk_3` FOREIGN KEY (`AsignadoPor`) REFERENCES `Usuario` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ================================================================
-- PASO 2: Migrar roles existentes de Usuario a UsuarioRol
-- ================================================================
-- Solo migrar usuarios que tienen un rol asignado
INSERT INTO `UsuarioRol` (`IdUsuario`, `IdRolSistema`, `FechaAsignacion`)
SELECT
    `Id` as IdUsuario,
    `IdRolSistema`,
    `FechaCreacion` as FechaAsignacion
FROM `Usuario`
WHERE `IdRolSistema` IS NOT NULL;

-- ================================================================
-- PASO 3: Verificar migración
-- ================================================================
-- Verificar que todos los usuarios con rol fueron migrados
SELECT
    'Usuarios con rol en tabla Usuario' as Descripcion,
    COUNT(*) as Total
FROM `Usuario`
WHERE `IdRolSistema` IS NOT NULL

UNION ALL

SELECT
    'Registros migrados a UsuarioRol' as Descripcion,
    COUNT(*) as Total
FROM `UsuarioRol`;

-- ================================================================
-- PASO 4: (OPCIONAL - NO EJECUTAR AÚN)
-- Eliminar columna IdRolSistema de Usuario después de verificar
-- ================================================================
-- ⚠️ IMPORTANTE: Solo ejecutar después de verificar que todo funciona correctamente
-- ⚠️ Primero debes actualizar todo el código PHP y Vue.js

-- ALTER TABLE `Usuario` DROP FOREIGN KEY `Usuario_ibfk_2`;
-- ALTER TABLE `Usuario` DROP COLUMN `IdRolSistema`;

-- ================================================================
-- VISTAS ÚTILES PARA CONSULTAS
-- ================================================================

-- Vista: Usuarios con sus roles
CREATE OR REPLACE VIEW `v_UsuariosConRoles` AS
SELECT
    u.Id as IdUsuario,
    u.Usuario,
    u.Nombre,
    u.ApellidoP,
    u.ApellidoM,
    u.Estatus,
    GROUP_CONCAT(r.Nombre ORDER BY r.Nombre SEPARATOR ', ') as Roles,
    GROUP_CONCAT(r.Id ORDER BY r.Nombre SEPARATOR ',') as RolesIds,
    COUNT(ur.IdRolSistema) as CantidadRoles
FROM `Usuario` u
LEFT JOIN `UsuarioRol` ur ON u.Id = ur.IdUsuario
LEFT JOIN `RolSistema` r ON ur.IdRolSistema = r.Id
WHERE u.Estatus = 'ACTIVO'
GROUP BY u.Id, u.Usuario, u.Nombre, u.ApellidoP, u.ApellidoM, u.Estatus;

-- Vista: Roles con cantidad de usuarios
CREATE OR REPLACE VIEW `v_RolesConUsuarios` AS
SELECT
    r.Id as IdRol,
    r.Nombre,
    r.Descripcion,
    COUNT(ur.IdUsuario) as CantidadUsuarios
FROM `RolSistema` r
LEFT JOIN `UsuarioRol` ur ON r.Id = ur.IdRolSistema
GROUP BY r.Id, r.Nombre, r.Descripcion;

-- ================================================================
-- CONSULTAS DE PRUEBA
-- ================================================================

-- Ver todos los usuarios con sus roles
SELECT * FROM v_UsuariosConRoles;

-- Ver todos los roles con cantidad de usuarios
SELECT * FROM v_RolesConUsuarios;

-- Ver detalle de roles por usuario específico
SELECT
    u.Usuario,
    u.Nombre,
    r.Nombre as Rol,
    r.Descripcion,
    ur.FechaAsignacion
FROM Usuario u
JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
JOIN RolSistema r ON ur.IdRolSistema = r.Id
WHERE u.Id = 1; -- Cambiar por el ID del usuario que quieres consultar

-- ================================================================
-- FIN DE MIGRACIÓN
-- ================================================================
