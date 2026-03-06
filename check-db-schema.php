<?php
/**
 * Script para verificar estructura de las tablas
 */

require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== ESTRUCTURA DE TABLAS ===\n\n";

// Ver columnas de Usuario
try {
    $stmt = $pdo->query("DESCRIBE Usuario");
    echo "Tabla Usuario:\n";
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error en Usuario: " . $e->getMessage() . "\n";
}

echo "\n";

// Ver columnas de UsuarioRol
try {
    $stmt = $pdo->query("DESCRIBE UsuarioRol");
    echo "Tabla UsuarioRol:\n";
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error en UsuarioRol: " . $e->getMessage() . "\n";
}

echo "\n";

// Ver columnas de unidades
try {
    $stmt = $pdo->query("DESCRIBE unidades");
    echo "Tabla unidades:\n";
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error en unidades: " . $e->getMessage() . "\n";
}

echo "\n";

// Ver columnas de NotificacionConfiguracion
try {
    $stmt = $pdo->query("DESCRIBE NotificacionConfiguracion");
    echo "Tabla NotificacionConfiguracion:\n";
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error en NotificacionConfiguracion: " . $e->getMessage() . "\n";
}
