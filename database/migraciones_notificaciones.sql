-- ================================================================================
-- SISTEMA DE NOTIFICACIONES POR EMAIL - MIGRACIONES DE BASE DE DATOS
-- ================================================================================
-- Este archivo contiene todas las migraciones necesarias para implementar
-- el sistema de notificaciones por correo electrónico para gestores de departamentos
-- 
-- Autor: Sistema SISEE
-- Fecha: 2026-02-25
-- Base de datos: MySQL/MariaDB
-- ================================================================================

USE sisegestion;

-- ================================================================================
-- 1. AGREGAR COLUMNAS DE EMAIL A LA TABLA USUARIO
-- ================================================================================
-- Permite almacenar el correo electrónico de cada usuario y su estado de verificación

ALTER TABLE `Usuario` 
ADD COLUMN IF NOT EXISTS `Email` VARCHAR(255) NULL 
    COMMENT 'Correo electrónico del usuario' AFTER `Password`;

ALTER TABLE `Usuario` 
ADD COLUMN IF NOT EXISTS `EmailVerificado` TINYINT(1) DEFAULT 0 
    COMMENT 'Indica si el email ha sido verificado (1=verificado, 0=no verificado)' AFTER `Email`;

-- Crear índice para búsquedas rápidas por email
CREATE INDEX IF NOT EXISTS idx_usuario_email ON Usuario(Email);

-- ================================================================================
-- 2. CREAR TABLA DE CONFIGURACIÓN DE NOTIFICACIONES
-- ================================================================================
-- Almacena las preferencias de notificación de cada usuario gestor

CREATE TABLE IF NOT EXISTS `NotificacionConfiguracion` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT,
  `IdUsuario` INT(11) NOT NULL COMMENT 'Usuario que configura las notificaciones',
  `IdUnidad` INT(11) NULL COMMENT 'Unidad/Departamento asignado al usuario',
  `NotificacionesActivas` TINYINT(1) DEFAULT 1 COMMENT 'Si el usuario recibe notificaciones (1=activas, 0=inactivas)',
  `FiltrarPorMunicipio` TINYINT(1) DEFAULT 0 COMMENT 'Si filtra notificaciones solo por su municipio (1=sí, 0=todas)',
  `FrecuenciaNotificacion` ENUM('diaria', 'semanal', 'inmediata') DEFAULT 'diaria' COMMENT 'Frecuencia de envío de notificaciones',
  `HoraEnvio` TIME DEFAULT '08:00:00' COMMENT 'Hora preferida para recibir notificaciones programadas',
  `UmbralPeticionesPendientes` INT(11) DEFAULT 5 COMMENT 'Número mínimo de peticiones pendientes para enviar notificación',
  `NotificarPeticionesNuevas` TINYINT(1) DEFAULT 1 COMMENT 'Notificar cuando se asignen nuevas peticiones (1=sí, 0=no)',
  `NotificarPeticionesVencidas` TINYINT(1) DEFAULT 1 COMMENT 'Notificar cuando haya peticiones atrasadas (1=sí, 0=no)',
  `FechaCreacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación de la configuración',
  `FechaActualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_usuario_unidad` (`IdUsuario`, `IdUnidad`),
  KEY `idx_usuario` (`IdUsuario`),
  KEY `idx_unidad` (`IdUnidad`),
  KEY `idx_notificaciones_activas` (`NotificacionesActivas`),
  CONSTRAINT `fk_notif_config_usuario` 
    FOREIGN KEY (`IdUsuario`) REFERENCES `Usuario` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_config_unidad` 
    FOREIGN KEY (`IdUnidad`) REFERENCES `unidades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Configuración personalizada de notificaciones por usuario';

-- ================================================================================
-- 3. CREAR TABLA DE HISTORIAL DE NOTIFICACIONES ENVIADAS
-- ================================================================================
-- Registra todas las notificaciones enviadas para auditoría, análisis y control

CREATE TABLE IF NOT EXISTS `NotificacionHistorial` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT,
  `IdUsuario` INT(11) NOT NULL COMMENT 'Usuario que recibió la notificación',
  `IdUnidad` INT(11) NULL COMMENT 'Unidad relacionada con la notificación',
  `Email` VARCHAR(255) NOT NULL COMMENT 'Email al que se envió la notificación',
  `TipoNotificacion` VARCHAR(50) NOT NULL COMMENT 'Tipo de notificación (peticiones_pendientes, nueva_asignacion, etc.)',
  `CantidadPeticionesPendientes` INT(11) DEFAULT 0 COMMENT 'Número de peticiones pendientes al momento del envío',
  `CantidadPeticionesNuevas` INT(11) DEFAULT 0 COMMENT 'Número de peticiones nuevas desde última notificación',
  `Asunto` TEXT NULL COMMENT 'Asunto del correo electrónico enviado',
  `Estado` ENUM('enviado', 'fallido', 'pendiente') DEFAULT 'pendiente' COMMENT 'Estado del envío de la notificación',
  `MensajeError` TEXT NULL COMMENT 'Mensaje de error si el envío falló',
  `FechaEnvio` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del envío',
  PRIMARY KEY (`Id`),
  KEY `idx_usuario_historial` (`IdUsuario`),
  KEY `idx_unidad_historial` (`IdUnidad`),
  KEY `idx_fecha_envio` (`FechaEnvio`),
  KEY `idx_estado` (`Estado`),
  KEY `idx_tipo_notificacion` (`TipoNotificacion`),
  CONSTRAINT `fk_notif_historial_usuario` 
    FOREIGN KEY (`IdUsuario`) REFERENCES `Usuario` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_historial_unidad` 
    FOREIGN KEY (`IdUnidad`) REFERENCES `unidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Historial completo de notificaciones enviadas';

-- ================================================================================
-- 4. CREAR VISTA PARA PETICIONES PENDIENTES POR DEPARTAMENTO
-- ================================================================================
-- Vista optimizada que muestra estadísticas de peticiones pendientes por departamento
-- Esta vista se utiliza para generar los reportes en las notificaciones

CREATE OR REPLACE VIEW vista_peticiones_pendientes_departamento AS
SELECT 
    u.id AS departamento_id,
    u.nombre_unidad AS departamento_nombre,
    p.division_id AS municipio_id,
    m.Municipio AS municipio_nombre,
    COUNT(pd.id) AS total_pendientes,
    SUM(CASE WHEN pd.estado = 'Esperando recepción' THEN 1 ELSE 0 END) AS esperando_recepcion,
    SUM(CASE WHEN pd.estado = 'Aceptado en proceso' THEN 1 ELSE 0 END) AS en_proceso,
    SUM(CASE WHEN pd.estado = 'Devuelto a seguimiento' THEN 1 ELSE 0 END) AS devueltas,
    MIN(pd.fecha_asignacion) AS peticion_mas_antigua,
    MAX(pd.fecha_asignacion) AS peticion_mas_reciente
FROM unidades u
LEFT JOIN peticion_departamento pd ON u.id = pd.departamento_id
LEFT JOIN peticiones p ON pd.peticion_id = p.id
LEFT JOIN DivisionAdministrativa m ON p.division_id = m.Id
WHERE pd.estado IN ('Esperando recepción', 'Aceptado en proceso', 'Devuelto a seguimiento')
GROUP BY u.id, u.nombre_unidad, p.division_id, m.Municipio;

-- ================================================================================
-- 5. ÍNDICES ADICIONALES PARA OPTIMIZAR CONSULTAS
-- ================================================================================
-- Mejoran el rendimiento de las consultas frecuentes del sistema de notificaciones

-- Índice compuesto para peticion_departamento (usado en conteos)
CREATE INDEX IF NOT EXISTS idx_dept_estado ON peticion_departamento(departamento_id, estado);

-- Índice para fechas de asignación (usado en cálculo de antigüedad)
CREATE INDEX IF NOT EXISTS idx_fecha_asignacion ON peticion_departamento(fecha_asignacion);

-- ================================================================================
-- 6. INSERTAR CONFIGURACIÓN POR DEFECTO PARA USUARIOS CON ROL 9
-- ================================================================================
-- Crea automáticamente configuración de notificaciones para usuarios gestores existentes

INSERT INTO NotificacionConfiguracion (IdUsuario, IdUnidad, NotificacionesActivas, FiltrarPorMunicipio)
SELECT 
    u.Id,
    u.IdUnidad,
    0, -- Notificaciones desactivadas por defecto (el usuario las activará manualmente)
    0  -- Sin filtro de municipio por defecto
FROM Usuario u
INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
WHERE ur.IdRolSistema = 9
  AND u.IdUnidad IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM NotificacionConfiguracion nc WHERE nc.IdUsuario = u.Id
  );

-- ================================================================================
-- 7. TRIGGER: CREAR CONFIGURACIÓN AL ASIGNAR ROL 9
-- ================================================================================
-- Trigger automático que crea configuración cuando se asigna el rol de gestor
--
-- NOTA IMPORTANTE PARA DBEAVER:
-- Si usas DBeaver, ejecuta este bloque de manera especial:
-- 1. Selecciona TODO el bloque desde "DROP TRIGGER" hasta el final (incluyendo el punto y coma)
-- 2. Click derecho → "Execute SQL Statement" (o Ctrl+Enter)
-- 3. NO ejecutes línea por línea
--
-- Si tienes problemas, ejecuta cada statement por separado:

-- Paso 1: Eliminar trigger si existe (ejecutar solo esta línea)
DROP TRIGGER IF EXISTS trg_crear_config_notificacion_rol9;

-- Paso 2: Crear el trigger (ejecutar todo este bloque completo desde CREATE hasta el último punto y coma)
CREATE TRIGGER trg_crear_config_notificacion_rol9
AFTER INSERT ON UsuarioRol
FOR EACH ROW
BEGIN
    DECLARE v_unidad_id INT;
    
    -- Solo ejecutar si el rol asignado es 9 (Gestor de Departamento)
    IF NEW.IdRolSistema = 9 THEN
        -- Obtener la unidad del usuario
        SELECT IdUnidad INTO v_unidad_id 
        FROM Usuario 
        WHERE Id = NEW.IdUsuario;
        
        -- Si tiene unidad asignada y no tiene configuración, crearla
        IF v_unidad_id IS NOT NULL THEN
            INSERT IGNORE INTO NotificacionConfiguracion 
                (IdUsuario, IdUnidad, NotificacionesActivas, FiltrarPorMunicipio)
            VALUES 
                (NEW.IdUsuario, v_unidad_id, 0, 0);
        END IF;
    END IF;
END;

-- ================================================================================
-- 8. CONSULTAS ÚTILES PARA ADMINISTRACIÓN
-- ================================================================================

-- Ver todos los usuarios con notificaciones activas
-- SELECT 
--     u.Usuario,
--     u.Email,
--     nc.NotificacionesActivas,
--     nc.FrecuenciaNotificacion,
--     nc.HoraEnvio,
--     uni.nombre_unidad
-- FROM Usuario u
-- INNER JOIN NotificacionConfiguracion nc ON u.Id = nc.IdUsuario
-- INNER JOIN unidades uni ON nc.IdUnidad = uni.id
-- WHERE nc.NotificacionesActivas = 1;

-- Ver historial de notificaciones de los últimos 7 días
-- SELECT 
--     nh.Id,
--     u.Usuario,
--     nh.Email,
--     nh.Asunto,
--     nh.CantidadPeticionesPendientes,
--     nh.Estado,
--     nh.FechaEnvio
-- FROM NotificacionHistorial nh
-- INNER JOIN Usuario u ON nh.IdUsuario = u.Id
-- WHERE nh.FechaEnvio >= DATE_SUB(NOW(), INTERVAL 7 DAY)
-- ORDER BY nh.FechaEnvio DESC;

-- Ver estadísticas de envío por estado
-- SELECT 
--     Estado,
--     COUNT(*) as Total,
--     ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM NotificacionHistorial), 2) as Porcentaje
-- FROM NotificacionHistorial
-- GROUP BY Estado;

-- Ver departamentos con más peticiones pendientes
-- SELECT * FROM vista_peticiones_pendientes_departamento
-- ORDER BY total_pendientes DESC
-- LIMIT 10;

-- ================================================================================
-- FIN DE MIGRACIONES
-- ================================================================================

-- Verificar que todo se creó correctamente
SELECT 
    'Usuario.Email' as Elemento,
    IF(COUNT(*) > 0, '✅ Columna existe', '❌ Columna no existe') as Estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'sisegestion' 
  AND TABLE_NAME = 'Usuario' 
  AND COLUMN_NAME = 'Email'
UNION ALL
SELECT 
    'NotificacionConfiguracion',
    IF(COUNT(*) > 0, '✅ Tabla existe', '❌ Tabla no existe')
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'sisegestion' 
  AND TABLE_NAME = 'NotificacionConfiguracion'
UNION ALL
SELECT 
    'NotificacionHistorial',
    IF(COUNT(*) > 0, '✅ Tabla existe', '❌ Tabla no existe')
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'sisegestion' 
  AND TABLE_NAME = 'NotificacionHistorial'
UNION ALL
SELECT 
    'vista_peticiones_pendientes_departamento',
    IF(COUNT(*) > 0, '✅ Vista existe', '❌ Vista no existe')
FROM INFORMATION_SCHEMA.VIEWS 
WHERE TABLE_SCHEMA = 'sisegestion' 
  AND TABLE_NAME = 'vista_peticiones_pendientes_departamento';

-- ================================================================================
-- ROLLBACK (En caso de necesitar deshacer los cambios)
-- ================================================================================
-- ADVERTENCIA: Esto eliminará todas las configuraciones y el historial
-- ¡Solo ejecutar si necesitas desinstalar completamente el sistema!

-- DROP TRIGGER IF EXISTS trg_crear_config_notificacion_rol9;
-- DROP VIEW IF EXISTS vista_peticiones_pendientes_departamento;
-- DROP TABLE IF EXISTS NotificacionHistorial;
-- DROP TABLE IF EXISTS NotificacionConfiguracion;
-- ALTER TABLE Usuario DROP COLUMN IF EXISTS EmailVerificado;
-- ALTER TABLE Usuario DROP COLUMN IF EXISTS Email;
-- DROP INDEX IF EXISTS idx_usuario_email ON Usuario;
-- DROP INDEX IF EXISTS idx_dept_estado ON peticion_departamento;
-- DROP INDEX IF EXISTS idx_fecha_asignacion ON peticion_departamento;

-- ================================================================================
