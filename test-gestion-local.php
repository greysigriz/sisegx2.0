<?php
// Test para gestion-notificaciones.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== PRUEBA DE GESTION-NOTIFICACIONES (LOCAL) ===\n\n";

// Simular sesión
session_start();
$_SESSION['user_id'] = 18; // Tu user_id
$_SESSION['user_data'] = [
    'usuario' => [
        'RolesIds' => [1] // Super Usuario
    ]
];

echo "✅ Sesión simulada: user_id = " . $_SESSION['user_id'] . "\n";
echo "✅ Roles: " . implode(', ', $_SESSION['user_data']['usuario']['RolesIds']) . "\n\n";

// Cargar configuración
require_once 'config/database.php';

try {
    $database = new Database();
    echo "✅ Database class loaded\n";
    
    $pdo = $database->getConnection();
    echo "✅ PDO connection successful\n\n";
    
    // Probar query de Super Usuario
    echo "Verificando rol Super Usuario...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM UsuarioRol WHERE IdUsuario = :userId AND IdRolSistema = 1");
    $stmt->execute([':userId' => $_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Es Super Usuario: " . ($result['count'] > 0 ? 'SÍ' : 'NO') . "\n\n";
    
    // Probar query principal
    echo "Ejecutando query principal...\n";
    $stmt = $pdo->query("
        SELECT DISTINCT
            u.Id AS IdUsuario,
            u.Usuario,
            u.Email,
            u.IdUnidad,
            un.nombre_unidad AS nombre_unidad,
            nc.NotificacionesActivas,
            nh_last.UltimaNotificacion
        FROM Usuario u
        INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
        LEFT JOIN unidades un ON u.IdUnidad = un.id
        LEFT JOIN NotificacionConfiguracion nc ON u.Id = nc.IdUsuario
        LEFT JOIN (
            SELECT IdUsuario, MAX(FechaEnvio) AS UltimaNotificacion
            FROM NotificacionHistorial
            WHERE Estado = 'enviado'
            GROUP BY IdUsuario
        ) nh_last ON u.Id = nh_last.IdUsuario
        WHERE ur.IdRolSistema = 9
        ORDER BY u.Usuario ASC
    ");
    
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Query ejecutada correctamente\n";
    echo "✅ Usuarios encontrados: " . count($usuarios) . "\n\n";
    
    if (count($usuarios) > 0) {
        echo "Primer usuario:\n";
        print_r($usuarios[0]);
    }
    
    // Probar query de departamentos
    echo "\nEjecutando query de departamentos...\n";
    $stmtDeptos = $pdo->query("
        SELECT DISTINCT
            un.id,
            un.nombre_unidad AS nombre
        FROM unidades un
        INNER JOIN Usuario u ON un.id = u.IdUnidad
        INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
        WHERE ur.IdRolSistema = 9
        ORDER BY un.nombre_unidad ASC
    ");
    
    $departamentos = $stmtDeptos->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Departamentos encontrados: " . count($departamentos) . "\n";
    
    if (count($departamentos) > 0) {
        echo "\nDepartamentos:\n";
        print_r($departamentos);
    }
    
    echo "\n✅ TODAS LAS PRUEBAS PASARON\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
}
