<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

// Iniciar sesión para verificar permisos
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 8 * 60 * 60);
    session_start();
}

function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar sesión activa
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'No hay sesión activa'
        ], 401);
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $userId = $_SESSION['user_id'];

    // Obtener información del usuario para filtrar según rol
    $queryUser = "SELECT IdRolSistema, IdDivisionAdm FROM Usuario WHERE Id = :user_id";
    $stmtUser = $db->prepare($queryUser);
    $stmtUser->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ], 404);
    }

    $rolId = $user['IdRolSistema'];
    $divisionId = $user['IdDivisionAdm'];

    // Query para obtener estadísticas de todos los departamentos (incluye los que tienen 0 peticiones)
    $query = "SELECT 
                u.id as departamento_id,
                u.nombre_unidad as departamento_nombre,
                COALESCE(COUNT(pd.id), 0) as total_peticiones,
                COALESCE(SUM(CASE 
                    WHEN pd.estado IN ('Esperando recepción') 
                    THEN 1 ELSE 0 
                END), 0) as pendientes,
                COALESCE(SUM(CASE 
                    WHEN pd.estado IN ('Aceptado en proceso') 
                    THEN 1 ELSE 0 
                END), 0) as en_proceso,
                COALESCE(SUM(CASE 
                    WHEN pd.estado IN ('Completado') 
                    THEN 1 ELSE 0 
                END), 0) as completadas,
                COALESCE(SUM(CASE 
                    WHEN pd.estado IN ('Devuelto a seguimiento', 'Rechazado')
                    THEN 1 ELSE 0
                END), 0) as devueltas,
                COALESCE(SUM(CASE 
                    WHEN p.NivelImportancia = 1 
                    AND pd.estado NOT IN ('Completado', 'Rechazado')
                    THEN 1 ELSE 0 
                END), 0) as criticas,
                MAX(pd.fecha_asignacion) as ultima_asignacion
              FROM unidades u
              LEFT JOIN peticion_departamento pd ON u.id = pd.departamento_id
              LEFT JOIN peticiones p ON pd.peticion_id = p.id";

    // Filtrar por municipio si es rol 12 (Canalizador Municipal)
    if ($rolId == 12 && $divisionId) {
        $query .= " WHERE (p.division_id = :division_id OR p.division_id IS NULL)";
    }

    $query .= " GROUP BY u.id, u.nombre_unidad
                ORDER BY total_peticiones DESC, u.nombre_unidad ASC";

    $stmt = $db->prepare($query);
    
    if ($rolId == 12 && $divisionId) {
        $stmt->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular estadísticas generales
    $totalPeticiones = 0;
    $totalPendientes = 0;
    $totalEnProceso = 0;
    $totalCompletadas = 0;
    $totalDevueltas = 0;

    foreach ($departamentos as $dept) {
        $totalPeticiones += $dept['total_peticiones'];
        $totalPendientes += $dept['pendientes'];
        $totalEnProceso += $dept['en_proceso'];
        $totalCompletadas += $dept['completadas'];
        $totalDevueltas += $dept['devueltas'];
    }

    sendJsonResponse([
        'success' => true,
        'departamentos' => $departamentos,
        'totales' => [
            'total_departamentos' => count($departamentos),
            'total_peticiones' => $totalPeticiones,
            'total_pendientes' => $totalPendientes,
            'total_en_proceso' => $totalEnProceso,
            'total_completadas' => $totalCompletadas,
            'total_devueltas' => $totalDevueltas
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], 200);

} catch (Exception $e) {
    error_log("Error en tarjetas-depto.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error al obtener datos de departamentos',
        'error' => $e->getMessage()
    ], 500);
}
?>
