<?php
/**
 * Verificación rápida de la base de datos
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "🔍 Verificando tabla imagenes...\n\n";
    
    // Verificar si existe la tabla
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'imagenes'");
    if ($tableCheck->rowCount() === 0) {
        echo "❌ ERROR: La tabla 'imagenes' no existe!\n";
        echo "💡 Solución: Ejecutar el script de migración:\n";
        echo "   cd database && php migrate_imagenes.php\n\n";
        exit;
    }
    
    echo "✅ Tabla 'imagenes' existe\n\n";
    
    // Mostrar estructura
    echo "📋 Estructura de la tabla:\n";
    $structure = $pdo->query("DESCRIBE imagenes");
    while ($field = $structure->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$field['Field']} ({$field['Type']})\n";
    }
    echo "\n";
    
    // Contar registros
    $count = $pdo->query("SELECT COUNT(*) as total FROM imagenes")->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total de imágenes: {$count['total']}\n\n";
    
    // Mostrar últimas imágenes
    if ($count['total'] > 0) {
        echo "📸 Últimas imágenes subidas:\n";
        $recent = $pdo->query("
            SELECT id, entidad_tipo, entidad_id, filename_original, file_size, fecha_subida, activa
            FROM imagenes 
            ORDER BY fecha_subida DESC 
            LIMIT 10
        ");
        
        while ($img = $recent->fetch(PDO::FETCH_ASSOC)) {
            $status = $img['activa'] ? "ACTIVA" : "ELIMINADA";
            $size = round($img['file_size'] / 1024, 1) . " KB";
            echo "  ID {$img['id']}: {$img['filename_original']} ({$size}) - {$img['entidad_tipo']} #{$img['entidad_id']} - {$img['fecha_subida']} - {$status}\n";
        }
    } else {
        echo "ℹ️ No hay imágenes en la base de datos aún\n";
    }
    
    echo "\n";
    
    // Verificar últimas peticiones
    echo "📝 Últimas peticiones creadas:\n";
    $peticiones = $pdo->query("
        SELECT id, folio, nombre, fecha_registro 
        FROM peticiones 
        ORDER BY fecha_registro DESC 
        LIMIT 5
    ");
    
    while ($pet = $peticiones->fetch(PDO::FETCH_ASSOC)) {
        echo "  ID {$pet['id']}: {$pet['folio']} - {$pet['nombre']} - {$pet['fecha_registro']}\n";
    }
    
    echo "\n✅ Verificación completada\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>