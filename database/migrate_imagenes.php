<?php
/**
 * Script para aplicar la migración del sistema de imágenes
 * Ejecutar una sola vez para crear la tabla imagenes
 */

require_once '../config/database.php';

try {
    echo "🚀 Iniciando migración del sistema de imágenes...\n";
    
    // Crear conexión a la base de datos
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    // Verificar si la tabla ya existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'imagenes'");
    if ($checkTable->rowCount() > 0) {
        echo "⚠️  La tabla 'imagenes' ya existe. ¿Desea continuar? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($response) !== 'y' && strtolower($response) !== 'yes') {
            echo "❌ Migración cancelada.\n";
            exit(1);
        }
        
        echo "🗑️  Eliminando tabla existente...\n";
        $pdo->exec("DROP TABLE IF EXISTS imagenes");
    }
    
    // Crear tabla imagenes
    echo "📋 Creando tabla 'imagenes'...\n";
    $createTable = "
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
      KEY `idx_entidad_activa` (`entidad_tipo`, `entidad_id`, `activa`),
      KEY `idx_entidad_orden` (`entidad_tipo`, `entidad_id`, `orden`),
      CONSTRAINT `fk_imagenes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `Usuario` (`Id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla centralizada para almacenar información de imágenes del sistema'
    ";
    
    $pdo->exec($createTable);
    echo "✅ Tabla 'imagenes' creada exitosamente.\n";
    
    // Crear trigger para validar máximo de imágenes
    echo "🔧 Creando trigger de validación...\n";
    $createTrigger = "
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
    END
    ";
    
    $pdo->exec($createTrigger);
    echo "✅ Trigger de validación creado exitosamente.\n";
    
    // Verificar estructura de tablas relacionadas
    echo "🔍 Verificando estructura de tablas relacionadas...\n";
    
    // Verificar tabla Usuario
    $checkUsuario = $pdo->query("SHOW TABLES LIKE 'Usuario'");
    if ($checkUsuario->rowCount() === 0) {
        echo "⚠️  Advertencia: La tabla 'Usuario' no existe. El FK no funcionará correctamente.\n";
    } else {
        echo "✅ Tabla 'Usuario' encontrada.\n";
    }
    
    // Verificar tabla peticiones
    $checkPeticiones = $pdo->query("SHOW TABLES LIKE 'peticiones'");
    if ($checkPeticiones->rowCount() === 0) {
        echo "⚠️  Advertencia: La tabla 'peticiones' no existe.\n";
    } else {
        echo "✅ Tabla 'peticiones' encontrada.\n";
    }
    
    // Verificar tabla peticion_departamento_historial
    $checkHistorial = $pdo->query("SHOW TABLES LIKE 'peticion_departamento_historial'");
    if ($checkHistorial->rowCount() === 0) {
        echo "⚠️  Advertencia: La tabla 'peticion_departamento_historial' no existe.\n";
    } else {
        echo "✅ Tabla 'peticion_departamento_historial' encontrada.\n";
    }
    
    // Crear datos de prueba (opcional)
    echo "\n📝 ¿Desea insertar datos de prueba? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($response) === 'y' || strtolower($response) === 'yes') {
        echo "🔧 Insertando datos de prueba...\n";
        
        $testData = "
        INSERT INTO `imagenes` (`entidad_tipo`, `entidad_id`, `filename_original`, `filename_storage`, `path_relativo`, `url_acceso`, `mime_type`, `file_size`, `width`, `height`, `orden`, `usuario_id`, `activa`) VALUES
        ('peticion', 1, 'problema_bache.jpg', 'peticion_1_20260205143020_a1b2c3d4.jpg', 'peticiones/2026/02', '/SISEE/uploads/peticiones/2026/02/peticion_1_20260205143020_a1b2c3d4.jpg', 'image/jpeg', 2048576, 1920, 1080, 1, 1, 1),
        ('peticion', 1, 'vista_general.png', 'peticion_1_20260205143025_e5f6g7h8.png', 'peticiones/2026/02', '/SISEE/uploads/peticiones/2026/02/peticion_1_20260205143025_e5f6g7h8.png', 'image/png', 1536000, 1280, 720, 2, 1, 1),
        ('historial_cambio', 1, 'reparacion_progreso.jpg', 'historial_1_20260205150030_x9y8z7w6.jpg', 'historial/2026/02', '/SISEE/uploads/historial/2026/02/historial_1_20260205150030_x9y8z7w6.jpg', 'image/jpeg', 1789440, 1600, 900, 1, 2, 1)
        ";
        
        $pdo->exec($testData);
        echo "✅ Datos de prueba insertados exitosamente.\n";
    }
    
    // Mostrar resumen
    echo "\n📊 RESUMEN DE LA MIGRACIÓN:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Tabla 'imagenes' creada con todos los índices\n";
    echo "✅ Trigger de validación configurado\n";
    echo "✅ Foreign Keys configurados\n";
    echo "✅ Estructura lista para uso en producción\n";
    echo "\n🔧 PRÓXIMOS PASOS:\n";
    echo "1. Verificar que las carpetas uploads/ existan\n";
    echo "2. Configurar permisos de escritura\n";
    echo "3. Probar upload de imágenes desde el frontend\n";
    echo "4. Configurar backup automático de uploads/\n";
    
    // Estadísticas finales
    $countImages = $pdo->query("SELECT COUNT(*) as total FROM imagenes")->fetch(PDO::FETCH_ASSOC);
    echo "\n📈 ESTADÍSTICAS:\n";
    echo "Total de imágenes en BD: " . $countImages['total'] . "\n";
    
    echo "\n🎉 ¡Migración completada exitosamente!\n";
    
} catch (PDOException $e) {
    echo "❌ Error en la migración: " . $e->getMessage() . "\n";
    echo "💡 Sugerencia: Verifique la configuración de la base de datos\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    exit(1);
}
?>