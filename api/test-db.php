<?php
// Test de conexión - Acceder: http://localhost/SISEE/api/test-db.php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'tests' => []
];

// Test extensiones
$result['tests']['pdo'] = extension_loaded('pdo') ? 'OK' : 'FALTA';
$result['tests']['pdo_mysql'] = extension_loaded('pdo_mysql') ? 'OK' : 'FALTA';

// Test archivo
$dbPath = __DIR__ . '/../config/database.php';
$result['tests']['db_file'] = file_exists($dbPath) ? 'OK' : 'NO EXISTE';

if (file_exists($dbPath)) {
    require_once $dbPath;
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $result['tests']['connection'] = 'OK';
        
        // Verificar columnas de DivisionAdministrativa
        $stmt = $conn->query("DESCRIBE DivisionAdministrativa");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['tests']['division_columns'] = $columns;
        
        // Verificar columnas de RolSistema
        $stmt = $conn->query("DESCRIBE RolSistema");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['tests']['rol_columns'] = $columns;
        
        // Ver un registro de ejemplo de DivisionAdministrativa
        $stmt = $conn->query("SELECT * FROM DivisionAdministrativa LIMIT 1");
        $result['tests']['division_sample'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ver un registro de ejemplo de RolSistema
        $stmt = $conn->query("SELECT * FROM RolSistema LIMIT 1");
        $result['tests']['rol_sample'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $result['tests']['error'] = $e->getMessage();
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
