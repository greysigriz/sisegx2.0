<?php
/**
 * Script de prueba para verificar sesión y permisos
 */

session_start();

echo "=== VERIFICACIÓN DE SESIÓN ===\n\n";

// Verificar user_id
echo "user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO EXISTE') . "\n";

// Verificar user_data
echo "user_data existe: " . (isset($_SESSION['user_data']) ? 'SÍ' : 'NO') . "\n";

if (isset($_SESSION['user_data'])) {
    echo "\nEstructura de user_data:\n";
    echo "- usuario: " . (isset($_SESSION['user_data']['usuario']) ? 'SÍ' : 'NO') . "\n";
    echo "- rol: " . (isset($_SESSION['user_data']['rol']) ? 'SÍ' : 'NO') . "\n";
    echo "- permisos: " . (isset($_SESSION['user_data']['permisos']) ? 'SÍ' : 'NO') . "\n";
    
    if (isset($_SESSION['user_data']['usuario'])) {
        echo "\nDatos de usuario:\n";
        echo "- Id: " . ($_SESSION['user_data']['usuario']['Id'] ?? 'NO') . "\n";
        echo "- Usuario: " . ($_SESSION['user_data']['usuario']['Usuario'] ?? 'NO') . "\n";
        echo "- Nombre: " . ($_SESSION['user_data']['usuario']['Nombre'] ?? 'NO') . "\n";
        echo "- RolesIds: " . (isset($_SESSION['user_data']['usuario']['RolesIds']) ? json_encode($_SESSION['user_data']['usuario']['RolesIds']) : 'NO') . "\n";
        
        if (isset($_SESSION['user_data']['usuario']['RolesIds']) && is_array($_SESSION['user_data']['usuario']['RolesIds'])) {
            $esSuperUsuario = in_array(1, $_SESSION['user_data']['usuario']['RolesIds']);
            echo "\nEs Super Usuario: " . ($esSuperUsuario ? 'SÍ ✅' : 'NO ❌') . "\n";
        }
    }
}

echo "\n=== TODAS LAS VARIABLES DE SESIÓN ===\n";
print_r($_SESSION);
