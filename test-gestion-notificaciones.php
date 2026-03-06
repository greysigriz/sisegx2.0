<?php
/**
 * Script de prueba para depurar gestion-notificaciones.php
 */

// Simular una sesión
session_start();
$_SESSION['user_id'] = 1; // Super Usuario
$_SESSION['user_data'] = [
    'usuario' => [
        'Id' => 1,
        'Usuario' => 'admin',
        'RolesIds' => [1]
    ]
];

echo "=== PRUEBA DE GESTION-NOTIFICACIONES ===\n\n";

// Simular REQUEST_METHOD
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];

// Capturar la salida
ob_start();

try {
    require_once 'config/database.php';
    
    echo "✅ Database class loaded\n";
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo) {
        echo "✅ PDO connection successful\n";
        
        // Probar consulta
        $stmt = $pdo->query("
            SELECT DISTINCT
                u.IdUsuario,
                u.Usuario,
                u.Email,
                u.IdUnidad,
                un.nombre_unidad AS nombre_unidad,
                nc.NotificacionesActivas,
                nc.UltimaNotificacion
            FROM Usuario u
            INNER JOIN UsuarioRol ur ON u.IdUsuario = ur.IdUsuario
            LEFT JOIN unidades un ON u.IdUnidad = un.id
            LEFT JOIN NotificacionConfiguracion nc ON u.IdUsuario = nc.IdUsuario
            WHERE ur.IdRol = 9
            ORDER BY u.Usuario ASC
            LIMIT 5
        ");
        
        echo "✅ Query executed\n";
        
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Found " . count($usuarios) . " usuarios\n\n";
        
        echo "Usuarios:\n";
        print_r($usuarios);
        
    } else {
        echo "❌ PDO connection failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();
echo $output;
