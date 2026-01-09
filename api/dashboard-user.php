<?php
// C:\xampp\htdocs\SISEE\api\dashboard-user.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

// Configurar e iniciar sesión para obtener usuario actual
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 8 * 60 * 60);
    ini_set('session.cookie_lifetime', 8 * 60 * 60);
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Función de logging
function debugLog($message, $data = null) {
    $logFile = __DIR__ . '/logs/dashboard_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    
    $logMessage .= PHP_EOL;
    
    if (!is_dir(dirname($logFile))) {
        @mkdir(dirname($logFile), 0755, true);
    }
    
    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    debugLog("=== Dashboard Request ===");
    debugLog("Session ID", session_id());
    debugLog("Session Data", $_SESSION);
    
    // Verificar que hay sesión activa
    if (!isset($_SESSION['user_id'])) {
        debugLog("ERROR: No hay user_id en sesión");
        sendJsonResponse([
            'success' => false,
            'message' => 'No hay sesión activa',
            'debug' => [
                'session_id' => session_id(),
                'session_status' => session_status()
            ]
        ], 401);
    }
    
    debugLog("Usuario autenticado: " . $_SESSION['user_id']);

    debugLog("Creando instancia Database");
    $database = new Database();
    
    debugLog("Obteniendo conexión BD");
    $db = $database->getConnection();
    
    debugLog("Conexión BD exitosa");
    $userId = $_SESSION['user_id'];

    // Obtener información del usuario
    debugLog("Ejecutando query usuario");
    $queryUser = "SELECT u.Id, u.Nombre, u.ApellidoP, u.IdRolSistema, u.IdDivisionAdm, u.IdUnidad,
                         r.Nombre as NombreRol,
                         d.Municipio as NombreDivision
                  FROM Usuario u
                  LEFT JOIN RolSistema r ON u.IdRolSistema = r.Id
                  LEFT JOIN DivisionAdministrativa d ON u.IdDivisionAdm = d.Id
                  WHERE u.Id = :user_id AND u.Estatus = 'ACTIVO'";
    
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
    $unidadId = $user['IdUnidad'];

    // ==========================================
    // ESTADÍSTICAS PERSONALIZADAS POR ROL
    // ==========================================
    
    $stats = [];
    
    // Rol 1: Administrador - Ver TODO
    if ($rolId == 1) {
        // Total de peticiones
        $queryTotal = "SELECT COUNT(*) as total FROM peticiones";
        $stmtTotal = $db->query($queryTotal);
        $stats['total_peticiones'] = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        // Por estado
        $queryEstados = "SELECT estado, COUNT(*) as cantidad 
                        FROM peticiones 
                        GROUP BY estado";
        $stmtEstados = $db->query($queryEstados);
        $stats['por_estado'] = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

        // Por importancia
        $queryImportancia = "SELECT NivelImportancia, COUNT(*) as cantidad 
                            FROM peticiones 
                            GROUP BY NivelImportancia 
                            ORDER BY NivelImportancia";
        $stmtImp = $db->query($queryImportancia);
        $stats['por_importancia'] = $stmtImp->fetchAll(PDO::FETCH_ASSOC);

        // Últimos 7 días
        $query7dias = "SELECT DATE(fecha_registro) as fecha, COUNT(*) as cantidad
                      FROM peticiones
                      WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(fecha_registro)
                      ORDER BY fecha";
        $stmt7dias = $db->query($query7dias);
        $stats['ultimos_7_dias'] = $stmt7dias->fetchAll(PDO::FETCH_ASSOC);

        // Top municipios
        $queryMunicipios = "SELECT d.Municipio, COUNT(p.id) as cantidad
                           FROM peticiones p
                           LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                           GROUP BY d.Municipio
                           ORDER BY cantidad DESC
                           LIMIT 5";
        $stmtMuni = $db->query($queryMunicipios);
        $stats['top_municipios'] = $stmtMuni->fetchAll(PDO::FETCH_ASSOC);

    } 
    // Rol 9: Usuario de departamento - Ver su división
    elseif ($rolId == 9 && $divisionId) {
        // Peticiones de su división/municipio
        $queryTotal = "SELECT COUNT(*) as total 
                      FROM peticiones 
                      WHERE division_id = :division_id";
        $stmtTotal = $db->prepare($queryTotal);
        $stmtTotal->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtTotal->execute();
        $stats['total_peticiones'] = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        // Por estado de su división
        $queryEstados = "SELECT estado, COUNT(*) as cantidad 
                        FROM peticiones 
                        WHERE division_id = :division_id
                        GROUP BY estado";
        $stmtEstados = $db->prepare($queryEstados);
        $stmtEstados->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtEstados->execute();
        $stats['por_estado'] = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

        // Por importancia de su división
        $queryImportancia = "SELECT NivelImportancia, COUNT(*) as cantidad 
                            FROM peticiones 
                            WHERE division_id = :division_id
                            GROUP BY NivelImportancia 
                            ORDER BY NivelImportancia";
        $stmtImp = $db->prepare($queryImportancia);
        $stmtImp->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtImp->execute();
        $stats['por_importancia'] = $stmtImp->fetchAll(PDO::FETCH_ASSOC);

    } 
    // Rol 10 o cualquier otro - Ver departamentos asignados
    elseif ($unidadId) {
        // Peticiones asignadas a su departamento
        $queryTotal = "SELECT COUNT(*) as total 
                      FROM peticion_departamento 
                      WHERE departamento_id = :unidad_id";
        $stmtTotal = $db->prepare($queryTotal);
        $stmtTotal->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtTotal->execute();
        $stats['total_peticiones'] = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        // Por estado de departamento
        $queryEstados = "SELECT estado, COUNT(*) as cantidad 
                        FROM peticion_departamento 
                        WHERE departamento_id = :unidad_id
                        GROUP BY estado";
        $stmtEstados = $db->prepare($queryEstados);
        $stmtEstados->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtEstados->execute();
        $stats['por_estado'] = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // Usuario sin permisos específicos - estadísticas básicas
        $queryTotal = "SELECT COUNT(*) as total FROM peticiones";
        $stmtTotal = $db->query($queryTotal);
        $stats['total_peticiones'] = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // ==========================================
    // PETICIONES RECIENTES
    // ==========================================
    
    $recentPetitions = [];

    if ($rolId == 1) {
        // Admin ve las últimas 10 peticiones
        $queryRecent = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado, 
                              p.NivelImportancia, p.fecha_registro,
                              d.Municipio
                       FROM peticiones p
                       LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                       ORDER BY p.fecha_registro DESC
                       LIMIT 10";
        $stmtRecent = $db->query($queryRecent);
        $recentPetitions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rolId == 9 && $divisionId) {
        // Usuario de departamento ve peticiones de su municipio
        $queryRecent = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado, 
                              p.NivelImportancia, p.fecha_registro,
                              d.Municipio
                       FROM peticiones p
                       LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                       WHERE p.division_id = :division_id
                       ORDER BY p.fecha_registro DESC
                       LIMIT 10";
        $stmtRecent = $db->prepare($queryRecent);
        $stmtRecent->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtRecent->execute();
        $recentPetitions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($unidadId) {
        // Usuario ve peticiones asignadas a su unidad
        $queryRecent = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado, 
                              p.NivelImportancia, p.fecha_registro,
                              d.Municipio, pd.estado as estado_departamento,
                              pd.fecha_asignacion
                       FROM peticion_departamento pd
                       INNER JOIN peticiones p ON pd.peticion_id = p.id
                       LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                       WHERE pd.departamento_id = :unidad_id
                       ORDER BY pd.fecha_asignacion DESC
                       LIMIT 10";
        $stmtRecent = $db->prepare($queryRecent);
        $stmtRecent->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtRecent->execute();
        $recentPetitions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // ALERTAS Y NOTIFICACIONES
    // ==========================================
    
    $alerts = [];

    // Peticiones críticas sin revisar
    if ($rolId == 1) {
        $queryCriticas = "SELECT COUNT(*) as cantidad 
                         FROM peticiones 
                         WHERE NivelImportancia = 1 
                         AND estado IN ('Sin revisar', 'Pendiente')";
        $stmtCriticas = $db->query($queryCriticas);
        $criticasCount = $stmtCriticas->fetch(PDO::FETCH_ASSOC)['cantidad'];
        
        if ($criticasCount > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "Hay {$criticasCount} peticiones críticas pendientes de revisión",
                'count' => $criticasCount
            ];
        }
    }

    // Respuesta final
    sendJsonResponse([
        'success' => true,
        'user_info' => [
            'id' => $user['Id'],
            'nombre' => $user['Nombre'] . ' ' . ($user['ApellidoP'] ?? ''),
            'rol' => $user['NombreRol'],
            'rol_id' => $user['IdRolSistema'],
            'division' => $user['NombreDivision']
        ],
        'statistics' => $stats,
        'recent_petitions' => $recentPetitions,
        'alerts' => $alerts,
        'timestamp' => date('Y-m-d H:i:s')
    ], 200);

} catch (Exception $e) {
    debugLog("EXCEPTION CAPTURADA: " . $e->getMessage());
    debugLog("Stack trace: " . $e->getTraceAsString());
    error_log("Error en dashboard-user.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error al obtener datos del dashboard',
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}
?>
