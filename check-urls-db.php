<?php
/**
 * Script para verificar URLs actuales en la base de datos
 */

require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

try {
    echo "=== URLS ACTUALES EN BASE DE DATOS ===\n\n";
    
    $stmt = $pdo->query("
        SELECT id, entidad_tipo, entidad_id, url_acceso 
        FROM imagenes 
        WHERE activa = 1
        ORDER BY id DESC
        LIMIT 10
    ");
    
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($imagenes as $img) {
        echo "ID: {$img['id']} | {$img['entidad_tipo']} #{$img['entidad_id']}\n";
        echo "URL: {$img['url_acceso']}\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
