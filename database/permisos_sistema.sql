-- ================================================================
-- SISTEMA DE PERMISOS POR ROL
-- Fecha: 2026-01-14
-- Descripción: Crear tabla de permisos y asignar permisos a roles
-- ================================================================

USE sisegestion;

-- ================================================================
-- PASO 1: Crear tabla de Permisos
-- ================================================================
CREATE TABLE IF NOT EXISTS `Permiso` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Codigo` varchar(50) NOT NULL UNIQUE COMMENT 'Código único del permiso (ej: ver_dashboard)',
  `Nombre` varchar(100) NOT NULL COMMENT 'Nombre descriptivo del permiso',
  `Descripcion` text DEFAULT NULL COMMENT 'Descripción detallada del permiso',
  `Modulo` varchar(50) DEFAULT NULL COMMENT 'Módulo al que pertenece (Dashboard, Peticiones, Configuración, etc.)',
  `FechaCreacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `idx_codigo` (`Codigo`),
  KEY `idx_modulo` (`Modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ================================================================
-- PASO 2: Crear tabla intermedia RolPermiso
-- ================================================================
CREATE TABLE IF NOT EXISTS `RolPermiso` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `IdRolSistema` int(11) NOT NULL,
  `IdPermiso` int(11) NOT NULL,
  `FechaAsignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_rol_permiso` (`IdRolSistema`, `IdPermiso`),
  KEY `IdRolSistema` (`IdRolSistema`),
  KEY `IdPermiso` (`IdPermiso`),
  CONSTRAINT `RolPermiso_ibfk_1` FOREIGN KEY (`IdRolSistema`) REFERENCES `RolSistema` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `RolPermiso_ibfk_2` FOREIGN KEY (`IdPermiso`) REFERENCES `Permiso` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ================================================================
-- PASO 3: Insertar permisos base del sistema
-- ================================================================
INSERT INTO Permiso (Codigo, Nombre, Descripcion, Modulo) VALUES
('ver_dashboard', 'Ver Dashboard', 'Permite ver el panel principal del sistema', 'Dashboard'),
('ver_estadisticas', 'Ver Estadisticas', 'Permite ver estadisticas y graficos del sistema', 'Dashboard'),
('ver_peticiones', 'Ver Peticiones', 'Permite ver la lista de peticiones', 'Peticiones'),
('crear_peticiones', 'Crear Peticiones', 'Permite crear nuevas peticiones', 'Peticiones'),
('editar_peticiones', 'Editar Peticiones', 'Permite editar peticiones existentes', 'Peticiones'),
('eliminar_peticiones', 'Eliminar Peticiones', 'Permite eliminar peticiones', 'Peticiones'),
('admin_peticiones', 'Administrar Peticiones', 'Acceso completo a la gestion de peticiones', 'Peticiones'),
('exportar_peticiones', 'Exportar Peticiones', 'Permite exportar peticiones a Excel/PDF', 'Peticiones'),
('ver_departamentos', 'Ver Departamentos', 'Permite ver departamentos y divisiones', 'Departamentos'),
('gestionar_departamentos', 'Gestionar Departamentos', 'Permite crear/editar/eliminar departamentos', 'Departamentos'),
('ver_tablero', 'Ver Tablero', 'Permite acceder al tablero de control', 'Tablero'),
('gestionar_tablero', 'Gestionar Tablero', 'Permite configurar el tablero', 'Tablero'),
('acceder_configuracion', 'Acceder a Configuracion', 'Permite acceder al modulo de configuracion', 'Configuracion'),
('configuracion_sistema', 'Configuracion del Sistema', 'Acceso completo a la configuracion del sistema', 'Configuracion'),
('ver_usuarios', 'Ver Usuarios', 'Permite ver la lista de usuarios', 'Configuracion'),
('crear_usuarios', 'Crear Usuarios', 'Permite crear nuevos usuarios', 'Configuracion'),
('editar_usuarios', 'Editar Usuarios', 'Permite editar usuarios existentes', 'Configuracion'),
('eliminar_usuarios', 'Eliminar Usuarios', 'Permite eliminar usuarios', 'Configuracion'),
('gestionar_usuarios', 'Gestionar Usuarios', 'Acceso completo a la gestion de usuarios', 'Configuracion'),
('ver_roles', 'Ver Roles', 'Permite ver la lista de roles', 'Configuracion'),
('crear_roles', 'Crear Roles', 'Permite crear nuevos roles', 'Configuracion'),
('editar_roles', 'Editar Roles', 'Permite editar roles existentes', 'Configuracion'),
('eliminar_roles', 'Eliminar Roles', 'Permite eliminar roles', 'Configuracion'),
('gestionar_roles', 'Gestionar Roles', 'Acceso completo a la gestion de roles', 'Configuracion'),
('asignar_permisos', 'Asignar Permisos', 'Permite asignar permisos a roles', 'Configuracion'),
('ver_jerarquias', 'Ver Jerarquias', 'Permite ver jerarquias de usuarios', 'Configuracion'),
('gestionar_jerarquias', 'Gestionar Jerarquias', 'Permite crear/editar jerarquias', 'Configuracion'),
('ver_reportes', 'Ver Reportes', 'Permite ver reportes del sistema', 'Reportes'),
('generar_reportes', 'Generar Reportes', 'Permite generar y exportar reportes', 'Reportes');

-- ================================================================
-- PASO 4: Asignar permisos a roles existentes
-- ================================================================

-- Super Usuario: Todos los permisos
INSERT INTO `RolPermiso` (`IdRolSistema`, `IdPermiso`)
SELECT 
    (SELECT Id FROM RolSistema WHERE Nombre = 'Super Usuario' LIMIT 1),
    Id
FROM Permiso;

-- Director: Permisos administrativos completos excepto gestión de usuarios/roles
INSERT INTO `RolPermiso` (`IdRolSistema`, `IdPermiso`)
SELECT 
    (SELECT Id FROM RolSistema WHERE Nombre = 'Director' LIMIT 1),
    Id
FROM Permiso
WHERE Codigo IN (
    'ver_dashboard', 'ver_estadisticas',
    'ver_peticiones', 'crear_peticiones', 'editar_peticiones', 'admin_peticiones', 'exportar_peticiones',
    'ver_departamentos', 'gestionar_departamentos',
    'ver_tablero', 'gestionar_tablero',
    'acceder_configuracion',
    'ver_usuarios', 'editar_usuarios',
    'ver_roles', 'ver_jerarquias', 'gestionar_jerarquias',
    'ver_reportes', 'generar_reportes'
);

-- Call center: Solo ver y gestionar peticiones
INSERT INTO `RolPermiso` (`IdRolSistema`, `IdPermiso`)
SELECT 
    (SELECT Id FROM RolSistema WHERE Nombre = 'Call center' LIMIT 1),
    Id
FROM Permiso
WHERE Codigo IN (
    'ver_dashboard',
    'ver_peticiones', 'crear_peticiones', 'editar_peticiones',
    'ver_departamentos'
);

-- Canalizador municipal: Ver peticiones de su municipio
INSERT INTO `RolPermiso` (`IdRolSistema`, `IdPermiso`)
SELECT 
    (SELECT Id FROM RolSistema WHERE Nombre = 'Canalizador municipal' LIMIT 1),
    Id
FROM Permiso
WHERE Codigo IN (
    'ver_dashboard',
    'ver_peticiones', 'editar_peticiones',
    'ver_departamentos',
    'ver_tablero'
);

-- Departamento: Ver peticiones de su departamento
INSERT INTO `RolPermiso` (`IdRolSistema`, `IdPermiso`)
SELECT 
    (SELECT Id FROM RolSistema WHERE Nombre = 'Departamento' LIMIT 1),
    Id
FROM Permiso
WHERE Codigo IN (
    'ver_dashboard',
    'ver_peticiones', 'editar_peticiones',
    'ver_tablero'
);

-- Carlink: Permisos específicos del proyecto Carlink
INSERT INTO `RolPermiso` (`IdRolSistema`, `IdPermiso`)
SELECT 
    (SELECT Id FROM RolSistema WHERE Nombre = 'Carlink' LIMIT 1),
    Id
FROM Permiso
WHERE Codigo IN (
    'ver_dashboard',
    'ver_peticiones'
);

-- ================================================================
-- PASO 5: Crear vistas útiles
-- ================================================================

-- Vista: Permisos por rol
CREATE OR REPLACE VIEW `v_RolesConPermisos` AS
SELECT 
    r.Id as IdRol,
    r.Nombre as NombreRol,
    r.Descripcion,
    GROUP_CONCAT(p.Nombre ORDER BY p.Modulo, p.Nombre SEPARATOR ', ') as Permisos,
    GROUP_CONCAT(p.Codigo ORDER BY p.Modulo, p.Codigo SEPARATOR ',') as CodigosPermisos,
    COUNT(rp.IdPermiso) as CantidadPermisos
FROM RolSistema r
LEFT JOIN RolPermiso rp ON r.Id = rp.IdRolSistema
LEFT JOIN Permiso p ON rp.IdPermiso = p.Id
GROUP BY r.Id, r.Nombre, r.Descripcion;

-- Vista: Permisos por módulo
CREATE OR REPLACE VIEW `v_PermisosPorModulo` AS
SELECT 
    Modulo,
    COUNT(*) as CantidadPermisos,
    GROUP_CONCAT(Nombre ORDER BY Nombre SEPARATOR ', ') as Permisos
FROM Permiso
GROUP BY Modulo;

-- ================================================================
-- PASO 6: Consultas útiles para verificar
-- ================================================================

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
WHERE r.Nombre = 'Super Usuario'
ORDER BY p.Modulo, p.Nombre;

-- Ver permisos agrupados por módulo
SELECT * FROM v_PermisosPorModulo;

-- ================================================================
-- FIN
-- ================================================================
