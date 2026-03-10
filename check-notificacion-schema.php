<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== ESTRUCTURA DE NotificacionConfiguracion ===\n\n";
    
    $stmt = $pdo->query("DESCRIBE NotificacionConfiguracion");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n=== ESTRUCTURA DE NotificacionHistorial ===\n\n";
    
    $stmt2 = $pdo->query("DESCRIBE NotificacionHistorial");
    $columns2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns2 as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
