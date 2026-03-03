<?php
/**
 * Script para actualizar URLs de imágenes en la base de datos
 * Reemplaza localhost/SISEE/uploads por las rutas correctas
 */

require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

try {
    echo "=== ACTUALIZACIÓN DE URLs EN BASE DE DATOS ===\n\n";
    
    // Obtener todas las imágenes con URLs problemáticas
    $stmt = $pdo->query("
        SELECT id, entidad_tipo, entidad_id, url_acceso, filename_storage, path_relativo
        FROM imagenes 
        WHERE url_acceso LIKE '%localhost%' 
           OR url_acceso LIKE '%/SISEE/uploads/%'
        ORDER BY id DESC
    ");
    
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Imágenes encontradas con URLs problemáticas: " . count($imagenes) . "\n\n";
    
    if (count($imagenes) === 0) {
        echo "✅ No hay imágenes que actualizar\n";
        exit;
    }
    
    $updateStmt = $pdo->prepare("
        UPDATE imagenes 
        SET url_acceso = ? 
        WHERE id = ?
    ");
    
    $actualizadas = 0;
    $errores = 0;
    
    foreach ($imagenes as $img) {
        echo "ID: {$img['id']} | {$img['entidad_tipo']} #{$img['entidad_id']}\n";
        echo "URL ACTUAL: {$img['url_acceso']}\n";
        
        // Extraer la ruta relativa después de /uploads/
        $urlActual = $img['url_acceso'];
        
        // Eliminar cualquier prefijo y obtener solo la ruta relativa
        if (preg_match('#/uploads/(.+)$#', $urlActual, $matches)) {
            $rutaRelativa = $matches[1];
        } else if (preg_match('#([^/]+/\d{4}/\d{2}/.+)$#', $urlActual, $matches)) {
            $rutaRelativa = $matches[1];
        } else {
            echo "❌ No se pudo extraer ruta relativa\n\n";
            $errores++;
            continue;
        }
        
        // Construir URL correcta para producción
        $urlNueva = "/uploads/" . $rutaRelativa;
        
        echo "URL NUEVA:  $urlNueva\n";
        
        try {
            $updateStmt->execute([$urlNueva, $img['id']]);
            echo "✅ Actualizada\n\n";
            $actualizadas++;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n\n";
            $errores++;
        }
    }
    
    echo "=== RESUMEN ===\n";
    echo "✅ Actualizadas: $actualizadas\n";
    echo "❌ Errores: $errores\n";
    echo "📊 Total procesadas: " . count($imagenes) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
