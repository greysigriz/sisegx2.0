<?php
/**
 * Script para crear datos de prueba en la tabla imagenes
 * Simula una imagen de historial_cambio para testing
 */

require_once '../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

try {
    // Verificar si ya existe una imagen de test
    $stmt = $pdo->prepare("
        SELECT * FROM imagenes 
        WHERE entidad_tipo = 'historial_cambio' 
        AND entidad_id = 999 
        LIMIT 1
    ");
    $stmt->execute();
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "✅ Ya existe imagen de test con ID: {$existing['id']}\n";
        echo "URL: {$existing['url_acceso']}\n\n";
    }
    
    // Obtener una imagen real existente
    $stmt = $pdo->prepare("
        SELECT * FROM imagenes 
        WHERE entidad_tipo = 'historial_cambio' 
        ORDER BY id DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== IMÁGENES EXISTENTES EN BD ===\n\n";
    foreach ($imagenes as $img) {
        echo "ID: {$img['id']}\n";
        echo "Entidad: {$img['entidad_tipo']} #{$img['entidad_id']}\n";
        echo "URL: {$img['url_acceso']}\n";
        echo "Archivo: {$img['nombre_archivo']}\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
