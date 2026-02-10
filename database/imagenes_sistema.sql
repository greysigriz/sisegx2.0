-- ============================================
-- Sistema de Imágenes Centralizado
-- Tabla para manejar imágenes de peticiones y cambios de estado
-- ============================================

CREATE TABLE `imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entidad_tipo` enum('peticion', 'historial_cambio') NOT NULL COMMENT 'Tipo de entidad al que pertenece la imagen',
  `entidad_id` int(11) NOT NULL COMMENT 'ID de la petición o historial',
  `filename_original` varchar(255) NOT NULL COMMENT 'Nombre original del archivo',
  `filename_storage` varchar(255) NOT NULL COMMENT 'Nombre del archivo en el servidor',
  `path_relativo` varchar(500) NOT NULL COMMENT 'Ruta relativa desde la carpeta uploads',
  `url_acceso` varchar(500) NOT NULL COMMENT 'URL para acceder a la imagen',
  `mime_type` varchar(100) NOT NULL COMMENT 'Tipo MIME del archivo',
  `file_size` int(11) NOT NULL COMMENT 'Tamaño del archivo en bytes',
  `width` int(11) DEFAULT NULL COMMENT 'Ancho de la imagen en píxeles',
  `height` int(11) DEFAULT NULL COMMENT 'Alto de la imagen en píxeles',
  `orden` tinyint(4) DEFAULT 1 COMMENT 'Orden de la imagen (1, 2, 3)',
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuario que subió la imagen',
  `activa` tinyint(1) DEFAULT 1 COMMENT 'Si la imagen está activa (para borrado lógico)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_storage_filename` (`filename_storage`),
  KEY `idx_entidad` (`entidad_tipo`, `entidad_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha_subida`),
  KEY `idx_activa` (`activa`),
  CONSTRAINT `fk_imagenes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `Usuario` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla centralizada para almacenar información de imágenes del sistema';

-- ============================================
-- Índices adicionales para optimización
-- ============================================

-- Índice compuesto para búsquedas frecuentes
ALTER TABLE `imagenes` ADD INDEX `idx_entidad_activa` (`entidad_tipo`, `entidad_id`, `activa`);

-- Índice para ordenamiento
ALTER TABLE `imagenes` ADD INDEX `idx_entidad_orden` (`entidad_tipo`, `entidad_id`, `orden`);

-- ============================================
-- Trigger para validar máximo de imágenes
-- ============================================

DELIMITER $$
CREATE TRIGGER `tr_validar_max_imagenes_insert`
BEFORE INSERT ON `imagenes`
FOR EACH ROW
BEGIN
    DECLARE imagen_count INT DEFAULT 0;
    
    -- Contar imágenes activas existentes para la entidad
    SELECT COUNT(*) INTO imagen_count
    FROM imagenes 
    WHERE entidad_tipo = NEW.entidad_tipo 
      AND entidad_id = NEW.entidad_id 
      AND activa = 1;
    
    -- Validar límite máximo (3 imágenes por entidad)
    IF imagen_count >= 3 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'No se pueden subir más de 3 imágenes por petición o cambio de estado';
    END IF;
END$$

DELIMITER ;

-- ============================================
-- Datos de prueba (opcional)
-- ============================================

-- INSERT INTO `imagenes` VALUES 
-- (1, 'peticion', 1, 'problema_bache.jpg', '20260205_123456_problema_bache.jpg', 'peticiones/2026/02/', '/uploads/peticiones/2026/02/20260205_123456_problema_bache.jpg', 'image/jpeg', 2048576, 1920, 1080, 1, current_timestamp(), 1, 1);

-- ============================================
-- Consultas útiles para administración
-- ============================================

-- Ver todas las imágenes de una petición
-- SELECT * FROM imagenes WHERE entidad_tipo = 'peticion' AND entidad_id = ? AND activa = 1 ORDER BY orden;

-- Ver todas las imágenes de un cambio de estado
-- SELECT * FROM imagenes WHERE entidad_tipo = 'historial_cambio' AND entidad_id = ? AND activa = 1 ORDER BY orden;

-- Estadísticas de uso de imágenes
-- SELECT 
--     entidad_tipo,
--     COUNT(*) as total_imagenes,
--     SUM(file_size) as espacio_usado_bytes,
--     ROUND(SUM(file_size) / 1024 / 1024, 2) as espacio_usado_mb
-- FROM imagenes 
-- WHERE activa = 1 
-- GROUP BY entidad_tipo;