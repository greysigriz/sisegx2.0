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
    
    // ✅ NUEVO: Rol 1 (Super Usuario), Rol 2, Rol 10 (Director) - Ver TODO el sistema
    if ($rolId == 1 || $rolId == 2 || $rolId == 10) {
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
    // Rol 12: Canalizador Municipal - Ver peticiones de su municipio
    elseif ($rolId == 12 && $divisionId) {
        // Total de peticiones de su municipio
        $queryTotal = "SELECT COUNT(*) as total 
                      FROM peticiones 
                      WHERE division_id = :division_id";
        $stmtTotal = $db->prepare($queryTotal);
        $stmtTotal->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtTotal->execute();
        $stats['total_peticiones'] = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        // Por estado de su municipio
        $queryEstados = "SELECT estado, COUNT(*) as cantidad 
                        FROM peticiones 
                        WHERE division_id = :division_id
                        GROUP BY estado";
        $stmtEstados = $db->prepare($queryEstados);
        $stmtEstados->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtEstados->execute();
        $stats['por_estado'] = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

        // Por importancia de su municipio
        $queryImportancia = "SELECT NivelImportancia, COUNT(*) as cantidad 
                            FROM peticiones 
                            WHERE division_id = :division_id
                            GROUP BY NivelImportancia 
                            ORDER BY NivelImportancia";
        $stmtImp = $db->prepare($queryImportancia);
        $stmtImp->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtImp->execute();
        $stats['por_importancia'] = $stmtImp->fetchAll(PDO::FETCH_ASSOC);

        // Peticiones retrasadas (no completadas y con más de 30 días)
        $queryRetrasadas = "SELECT COUNT(*) as cantidad
                           FROM peticiones
                           WHERE division_id = :division_id
                           AND estado NOT IN ('Completada', 'Cancelada')
                           AND DATEDIFF(CURDATE(), fecha_registro) > 30";
        $stmtRetrasadas = $db->prepare($queryRetrasadas);
        $stmtRetrasadas->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtRetrasadas->execute();
        $stats['peticiones_retrasadas'] = $stmtRetrasadas->fetch(PDO::FETCH_ASSOC)['cantidad'];

        // Departamentos con más peticiones asignadas en su municipio
        $queryDepartamentos = "SELECT u.nombre_unidad as departamento, COUNT(pd.id) as cantidad
                              FROM peticion_departamento pd
                              INNER JOIN peticiones p ON pd.peticion_id = p.id
                              INNER JOIN unidades u ON pd.departamento_id = u.id
                              WHERE p.division_id = :division_id
                              GROUP BY u.id, u.nombre_unidad
                              ORDER BY cantidad DESC
                              LIMIT 5";
        $stmtDepts = $db->prepare($queryDepartamentos);
        $stmtDepts->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtDepts->execute();
        $stats['departamentos_top'] = $stmtDepts->fetchAll(PDO::FETCH_ASSOC);

    }
    // Rol 13: Canalizador Estatal - Ver todas las peticiones del estado
    elseif ($rolId == 13) {
        // Total de peticiones estatales
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

        // Peticiones retrasadas (no completadas y con más de 30 días)
        $queryRetrasadas = "SELECT COUNT(*) as cantidad
                           FROM peticiones
                           WHERE estado NOT IN ('Completada', 'Cancelada')
                           AND DATEDIFF(CURDATE(), fecha_registro) > 30";
        $stmtRetrasadas = $db->query($queryRetrasadas);
        $stats['peticiones_retrasadas'] = $stmtRetrasadas->fetch(PDO::FETCH_ASSOC)['cantidad'];

        // Departamentos con más peticiones asignadas a nivel estatal
        $queryDepartamentos = "SELECT u.nombre_unidad as departamento, COUNT(pd.id) as cantidad
                              FROM peticion_departamento pd
                              INNER JOIN unidades u ON pd.departamento_id = u.id
                              GROUP BY u.id, u.nombre_unidad
                              ORDER BY cantidad DESC
                              LIMIT 5";
        $stmtDepts = $db->query($queryDepartamentos);
        $stats['departamentos_top'] = $stmtDepts->fetchAll(PDO::FETCH_ASSOC);

        // Top municipios con más peticiones
        $queryMunicipios = "SELECT d.Municipio, COUNT(p.id) as cantidad
                           FROM peticiones p
                           LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                           GROUP BY d.Municipio
                           ORDER BY cantidad DESC
                           LIMIT 5";
        $stmtMuni = $db->query($queryMunicipios);
        $stats['top_municipios'] = $stmtMuni->fetchAll(PDO::FETCH_ASSOC);

        // Últimos 7 días
        $query7dias = "SELECT DATE(fecha_registro) as fecha, COUNT(*) as cantidad
                      FROM peticiones
                      WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(fecha_registro)
                      ORDER BY fecha";
        $stmt7dias = $db->query($query7dias);
        $stats['ultimos_7_dias'] = $stmt7dias->fetchAll(PDO::FETCH_ASSOC);

    }
    // Rol 9: Usuario de departamento - Ver peticiones asignadas a su departamento
    elseif ($rolId == 9 && $unidadId) {
        // Total de peticiones asignadas a su departamento
        $queryTotal = "SELECT COUNT(*) as total 
                      FROM peticion_departamento 
                      WHERE departamento_id = :unidad_id";
        $stmtTotal = $db->prepare($queryTotal);
        $stmtTotal->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtTotal->execute();
        $stats['total_peticiones'] = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        // Por estado en peticion_departamento
        $queryEstados = "SELECT estado, COUNT(*) as cantidad 
                        FROM peticion_departamento 
                        WHERE departamento_id = :unidad_id
                        GROUP BY estado";
        $stmtEstados = $db->prepare($queryEstados);
        $stmtEstados->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtEstados->execute();
        $stats['por_estado'] = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

        // Por importancia (de las peticiones asignadas)
        $queryImportancia = "SELECT p.NivelImportancia, COUNT(*) as cantidad 
                            FROM peticion_departamento pd
                            INNER JOIN peticiones p ON pd.peticion_id = p.id
                            WHERE pd.departamento_id = :unidad_id
                            GROUP BY p.NivelImportancia 
                            ORDER BY p.NivelImportancia";
        $stmtImp = $db->prepare($queryImportancia);
        $stmtImp->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtImp->execute();
        $stats['por_importancia'] = $stmtImp->fetchAll(PDO::FETCH_ASSOC);

        // Peticiones pendientes (no completadas)
        $queryPendientes = "SELECT COUNT(*) as cantidad
                           FROM peticion_departamento
                           WHERE departamento_id = :unidad_id
                           AND estado NOT IN ('Completado', 'Cerrado')";
        $stmtPendientes = $db->prepare($queryPendientes);
        $stmtPendientes->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtPendientes->execute();
        $stats['peticiones_pendientes'] = $stmtPendientes->fetch(PDO::FETCH_ASSOC)['cantidad'];

        // Peticiones retrasadas (más de 15 días sin completar)
        $queryRetrasadas = "SELECT COUNT(*) as cantidad
                           FROM peticion_departamento
                           WHERE departamento_id = :unidad_id
                           AND estado NOT IN ('Completado', 'Cerrado')
                           AND DATEDIFF(CURDATE(), fecha_asignacion) > 15";
        $stmtRetrasadas = $db->prepare($queryRetrasadas);
        $stmtRetrasadas->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtRetrasadas->execute();
        $stats['peticiones_retrasadas'] = $stmtRetrasadas->fetch(PDO::FETCH_ASSOC)['cantidad'];

        // Nombre del departamento
        $queryDept = "SELECT nombre_unidad FROM unidades WHERE id = :unidad_id";
        $stmtDept = $db->prepare($queryDept);
        $stmtDept->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtDept->execute();
        $deptInfo = $stmtDept->fetch(PDO::FETCH_ASSOC);
        $stats['nombre_departamento'] = $deptInfo ? $deptInfo['nombre_unidad'] : 'Departamento';

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

    if ($rolId == 1 || $rolId == 2 || $rolId == 10) {
        // Super Usuario y Director ven las últimas 10 peticiones de TODO el sistema
        $queryRecent = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado, 
                              p.NivelImportancia, p.fecha_registro,
                              d.Municipio
                       FROM peticiones p
                       LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                       ORDER BY p.fecha_registro DESC
                       LIMIT 10";
        $stmtRecent = $db->query($queryRecent);
        $recentPetitions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rolId == 12 && $divisionId) {
        // Canalizador Municipal ve peticiones de su municipio
        $queryRecent = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado, 
                              p.NivelImportancia, p.fecha_registro,
                              d.Municipio,
                              DATEDIFF(CURDATE(), p.fecha_registro) as dias_transcurridos
                       FROM peticiones p
                       LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                       WHERE p.division_id = :division_id
                       AND p.estado NOT IN ('Completada', 'Cancelada')
                       ORDER BY p.NivelImportancia ASC, p.fecha_registro ASC
                       LIMIT 10";
        $stmtRecent = $db->prepare($queryRecent);
        $stmtRecent->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtRecent->execute();
        $recentPetitions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rolId == 13) {
        // Canalizador Estatal ve peticiones urgentes de todos los municipios
        $queryRecent = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado, 
                              p.NivelImportancia, p.fecha_registro,
                              d.Municipio,
                              DATEDIFF(CURDATE(), p.fecha_registro) as dias_transcurridos
                       FROM peticiones p
                       LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                       WHERE p.estado NOT IN ('Completada', 'Cancelada')
                       ORDER BY p.NivelImportancia ASC, p.fecha_registro ASC
                       LIMIT 10";
        $stmtRecent = $db->query($queryRecent);
        $recentPetitions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rolId == 9 && $unidadId) {
        // Usuario de departamento ve peticiones asignadas a su unidad (ordenadas por urgencia)
        $queryRecent = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado, 
                              p.NivelImportancia, p.fecha_registro,
                              d.Municipio, pd.estado as estado_departamento,
                              pd.fecha_asignacion,
                              DATEDIFF(CURDATE(), pd.fecha_asignacion) as dias_asignacion
                       FROM peticion_departamento pd
                       INNER JOIN peticiones p ON pd.peticion_id = p.id
                       LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
                       WHERE pd.departamento_id = :unidad_id
                       AND pd.estado NOT IN ('Completado', 'Cerrado')
                       ORDER BY p.NivelImportancia ASC, pd.fecha_asignacion ASC
                       LIMIT 10";
        $stmtRecent = $db->prepare($queryRecent);
        $stmtRecent->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
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

    // Peticiones críticas sin revisar (Super Usuario y Director)
    if ($rolId == 1 || $rolId == 2 || $rolId == 10) {
        $queryCriticas = "SELECT COUNT(*) as cantidad 
                         FROM peticiones 
                         WHERE NivelImportancia = 1 
                         AND estado IN ('Sin revisar', 'Pendiente', 'Esperando recepción')";
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

    // Alertas para Canalizador Municipal
    if ($rolId == 12 && $divisionId) {
        // Peticiones críticas de su municipio
        $queryCriticas = "SELECT COUNT(*) as cantidad 
                         FROM peticiones 
                         WHERE division_id = :division_id
                         AND NivelImportancia = 1 
                         AND estado IN ('Sin revisar', 'Pendiente', 'Esperando recepción')";
        $stmtCriticas = $db->prepare($queryCriticas);
        $stmtCriticas->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtCriticas->execute();
        $criticasCount = $stmtCriticas->fetch(PDO::FETCH_ASSOC)['cantidad'];
        
        if ($criticasCount > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "Hay {$criticasCount} peticiones críticas en tu municipio",
                'count' => $criticasCount
            ];
        }

        // Peticiones retrasadas
        $queryRetrasadas = "SELECT COUNT(*) as cantidad 
                           FROM peticiones 
                           WHERE division_id = :division_id
                           AND estado NOT IN ('Completada', 'Cancelada')
                           AND DATEDIFF(CURDATE(), fecha_registro) > 30";
        $stmtRetrasadas = $db->prepare($queryRetrasadas);
        $stmtRetrasadas->bindParam(':division_id', $divisionId, PDO::PARAM_INT);
        $stmtRetrasadas->execute();
        $retrasadasCount = $stmtRetrasadas->fetch(PDO::FETCH_ASSOC)['cantidad'];
        
        if ($retrasadasCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Tienes {$retrasadasCount} peticiones retrasadas (más de 30 días sin completar)",
                'count' => $retrasadasCount
            ];
        }
    }

    // Alertas para Rol Departamento (9)
    if ($rolId == 9 && $unidadId) {
        // Peticiones críticas asignadas
        $queryCriticas = "SELECT COUNT(*) as cantidad 
                         FROM peticion_departamento pd
                         INNER JOIN peticiones p ON pd.peticion_id = p.id
                         WHERE pd.departamento_id = :unidad_id
                         AND p.NivelImportancia = 1 
                         AND pd.estado NOT IN ('Completado', 'Cerrado')";
        $stmtCriticas = $db->prepare($queryCriticas);
        $stmtCriticas->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtCriticas->execute();
        $criticasCount = $stmtCriticas->fetch(PDO::FETCH_ASSOC)['cantidad'];
        
        if ($criticasCount > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "Tienes {$criticasCount} peticiones críticas asignadas a tu departamento",
                'count' => $criticasCount
            ];
        }

        // Peticiones retrasadas del departamento
        $queryRetrasadas = "SELECT COUNT(*) as cantidad 
                           FROM peticion_departamento
                           WHERE departamento_id = :unidad_id
                           AND estado NOT IN ('Completado', 'Cerrado')
                           AND DATEDIFF(CURDATE(), fecha_asignacion) > 15";
        $stmtRetrasadas = $db->prepare($queryRetrasadas);
        $stmtRetrasadas->bindParam(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtRetrasadas->execute();
        $retrasadasCount = $stmtRetrasadas->fetch(PDO::FETCH_ASSOC)['cantidad'];
        
        if ($retrasadasCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Tienes {$retrasadasCount} peticiones retrasadas en tu departamento (más de 15 días sin completar)",
                'count' => $retrasadasCount
            ];
        }
    }

    // Alertas para Canalizador Estatal
    if ($rolId == 13) {
        // Peticiones críticas a nivel estatal
        $queryCriticas = "SELECT COUNT(*) as cantidad 
                         FROM peticiones 
                         WHERE NivelImportancia = 1 
                         AND estado IN ('Sin revisar', 'Pendiente', 'Esperando recepción')";
        $stmtCriticas = $db->query($queryCriticas);
        $criticasCount = $stmtCriticas->fetch(PDO::FETCH_ASSOC)['cantidad'];
        
        if ($criticasCount > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "Hay {$criticasCount} peticiones críticas a nivel estatal",
                'count' => $criticasCount
            ];
        }

        // Peticiones retrasadas a nivel estatal
        $queryRetrasadas = "SELECT COUNT(*) as cantidad 
                           FROM peticiones 
                           WHERE estado NOT IN ('Completada', 'Cancelada')
                           AND DATEDIFF(CURDATE(), fecha_registro) > 30";
        $stmtRetrasadas = $db->query($queryRetrasadas);
        $retrasadasCount = $stmtRetrasadas->fetch(PDO::FETCH_ASSOC)['cantidad'];
        
        if ($retrasadasCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Hay {$retrasadasCount} peticiones retrasadas a nivel estatal (más de 30 días)",
                'count' => $retrasadasCount
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
