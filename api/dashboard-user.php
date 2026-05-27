<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/constants.php';

header('Content-Type: application/json; charset=UTF-8');

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

// ==========================================
// FUNCIONES REUTILIZABLES DE ESTADÍSTICAS
// ==========================================

function getEstadisticasPeticiones($db, $divisionId = null) {
    $stats = [];
    $where = $divisionId ? "WHERE division_id = :division_id" : "";

    // Query combinada: total, por estado, por importancia en una sola pasada
    $queryBase = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN estado NOT IN ('" . implode("','", ESTADOS_FINALES) . "')
                         AND DATEDIFF(CURDATE(), fecha_registro) > " . DIAS_RETRASO_PETICION . " THEN 1 ELSE 0 END) as retrasadas
                  FROM peticiones $where";
    $stmt = $db->prepare($queryBase);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    $base = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_peticiones'] = $base['total'];
    $stats['peticiones_retrasadas'] = $base['retrasadas'];

    // Por estado
    $queryEstados = "SELECT estado, COUNT(*) as cantidad FROM peticiones $where GROUP BY estado";
    $stmt = $db->prepare($queryEstados);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['por_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Por importancia
    $queryImp = "SELECT NivelImportancia, COUNT(*) as cantidad FROM peticiones $where GROUP BY NivelImportancia ORDER BY NivelImportancia";
    $stmt = $db->prepare($queryImp);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['por_importancia'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $stats;
}

function getDepartamentosTop($db, $divisionId = null) {
    $join = $divisionId ? "INNER JOIN peticiones p ON pd.peticion_id = p.id" : "";
    $where = $divisionId ? "WHERE p.division_id = :division_id" : "";

    $query = "SELECT u.nombre_unidad as departamento, COUNT(pd.id) as cantidad
              FROM peticion_departamento pd
              $join
              INNER JOIN unidades u ON pd.departamento_id = u.id
              $where
              GROUP BY u.id, u.nombre_unidad
              ORDER BY cantidad DESC";
    $stmt = $db->prepare($query);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDeptMuniCross($db, $divisionId = null) {
    $where = $divisionId ? "WHERE p.division_id = :division_id" : "";

    $query = "SELECT u.nombre_unidad as departamento, da.Municipio as municipio, COUNT(pd.id) as cantidad
              FROM peticion_departamento pd
              INNER JOIN peticiones p ON pd.peticion_id = p.id
              INNER JOIN unidades u ON pd.departamento_id = u.id
              LEFT JOIN DivisionAdministrativa da ON p.division_id = da.Id
              $where
              GROUP BY u.id, u.nombre_unidad, da.Municipio
              ORDER BY cantidad DESC";
    $stmt = $db->prepare($query);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopMunicipios($db) {
    $query = "SELECT d.Municipio, COUNT(p.id) as cantidad
              FROM peticiones p
              LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
              GROUP BY d.Municipio
              ORDER BY cantidad DESC";
    return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

function getUltimos7Dias($db, $divisionId = null) {
    $where = $divisionId
        ? "WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND division_id = :division_id"
        : "WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";

    $query = "SELECT DATE(fecha_registro) as fecha, COUNT(*) as cantidad
              FROM peticiones $where
              GROUP BY DATE(fecha_registro) ORDER BY fecha";
    $stmt = $db->prepare($query);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTimelineEstados($db, $divisionId = null) {
    $where = $divisionId
        ? "WHERE p.division_id = :division_id AND p.fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)"
        : "WHERE p.fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";

    $query = "SELECT DATE(p.fecha_registro) as fecha, p.estado, COUNT(*) as cantidad
              FROM peticiones p $where
              GROUP BY DATE(p.fecha_registro), p.estado
              ORDER BY fecha ASC, p.estado";
    $stmt = $db->prepare($query);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAlertasCriticas($db, $divisionId = null, $mensaje = '') {
    $where = $divisionId ? "AND division_id = :division_id" : "";
    $estadosCriticos = "'" . implode("','", ESTADOS_CRITICOS) . "'";

    $query = "SELECT COUNT(*) as cantidad FROM peticiones
              WHERE NivelImportancia = 1 AND estado IN ($estadosCriticos) $where";
    $stmt = $db->prepare($query);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];

    if ($count > 0) {
        return [
            'type' => 'critical',
            'message' => str_replace('{n}', $count, $mensaje),
            'count' => $count
        ];
    }
    return null;
}

function getAlertasRetrasadas($db, $divisionId = null, $diasRetraso = null, $mensaje = '') {
    $dias = $diasRetraso ?? DIAS_RETRASO_PETICION;
    $estadosFinales = "'" . implode("','", ESTADOS_FINALES) . "'";
    $where = $divisionId ? "AND division_id = :division_id" : "";

    $query = "SELECT COUNT(*) as cantidad FROM peticiones
              WHERE estado NOT IN ($estadosFinales)
              AND DATEDIFF(CURDATE(), fecha_registro) > $dias $where";
    $stmt = $db->prepare($query);
    if ($divisionId) $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];

    if ($count > 0) {
        return [
            'type' => 'warning',
            'message' => str_replace('{n}', $count, $mensaje),
            'count' => $count
        ];
    }
    return null;
}

// ==========================================
// MAIN
// ==========================================

try {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'No hay sesión activa',
            'code' => 'NO_SESSION'
        ], 401);
    }

    $database = new Database();
    $db = $database->getConnection();
    $userId = $_SESSION['user_id'];

    // Obtener información del usuario
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
        sendJsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
    }

    $rolId = $user['IdRolSistema'];
    $divisionId = $user['IdDivisionAdm'];
    $unidadId = $user['IdUnidad'];

    // ==========================================
    // ESTADÍSTICAS POR ROL
    // ==========================================
    $stats = [];
    $alerts = [];

    if (in_array($rolId, ROLES_ADMIN)) {
        // Admin / Director — ve todo el sistema
        $stats = getEstadisticasPeticiones($db);
        $stats['ultimos_7_dias'] = getUltimos7Dias($db);
        $stats['top_municipios'] = getTopMunicipios($db);
        $stats['departamentos_top'] = getDepartamentosTop($db);
        $stats['dept_muni_cross'] = getDeptMuniCross($db);
        $stats['timeline_estados'] = getTimelineEstados($db);

        $alert = getAlertasCriticas($db, null, "Hay {n} peticiones críticas pendientes de revisión");
        if ($alert) $alerts[] = $alert;

    } elseif ($rolId == ROL_CANALIZADOR_MUNICIPAL && $divisionId) {
        // Canalizador Municipal — ve su municipio
        $stats = getEstadisticasPeticiones($db, $divisionId);
        $stats['departamentos_top'] = getDepartamentosTop($db, $divisionId);
        $stats['dept_muni_cross'] = getDeptMuniCross($db, $divisionId);
        $stats['timeline_estados'] = getTimelineEstados($db, $divisionId);

        $alert = getAlertasCriticas($db, $divisionId, "Hay {n} peticiones críticas en tu municipio");
        if ($alert) $alerts[] = $alert;

        $alert = getAlertasRetrasadas($db, $divisionId, DIAS_RETRASO_PETICION, "Tienes {n} peticiones retrasadas (más de " . DIAS_RETRASO_PETICION . " días sin completar)");
        if ($alert) $alerts[] = $alert;

    } elseif ($rolId == ROL_CANALIZADOR_ESTATAL) {
        // Canalizador Estatal — ve todo
        $stats = getEstadisticasPeticiones($db);
        $stats['departamentos_top'] = getDepartamentosTop($db);
        $stats['top_municipios'] = getTopMunicipios($db);
        $stats['dept_muni_cross'] = getDeptMuniCross($db);
        $stats['ultimos_7_dias'] = getUltimos7Dias($db);
        $stats['timeline_estados'] = getTimelineEstados($db);

        $alert = getAlertasCriticas($db, null, "Hay {n} peticiones críticas a nivel estatal");
        if ($alert) $alerts[] = $alert;

        $alert = getAlertasRetrasadas($db, null, DIAS_RETRASO_PETICION, "Hay {n} peticiones retrasadas a nivel estatal (más de " . DIAS_RETRASO_PETICION . " días)");
        if ($alert) $alerts[] = $alert;

    } elseif ($rolId == ROL_DEPARTAMENTO && $unidadId) {
        // Departamento — ve peticiones asignadas a su unidad
        $estadosFinalesDepto = "'" . implode("','", ESTADOS_FINALES_DEPTO) . "'";

        $queryBase = "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN estado NOT IN ($estadosFinalesDepto) THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estado NOT IN ($estadosFinalesDepto) AND DATEDIFF(CURDATE(), fecha_asignacion) > " . DIAS_RETRASO_DEPARTAMENTO . " THEN 1 ELSE 0 END) as retrasadas
                      FROM peticion_departamento WHERE departamento_id = :unidad_id";
        $stmt = $db->prepare($queryBase);
        $stmt->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmt->execute();
        $base = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_peticiones'] = $base['total'];
        $stats['peticiones_pendientes'] = $base['pendientes'];
        $stats['peticiones_retrasadas'] = $base['retrasadas'];

        // Por estado en peticion_departamento
        $queryEstados = "SELECT estado, COUNT(*) as cantidad FROM peticion_departamento WHERE departamento_id = :unidad_id GROUP BY estado";
        $stmt = $db->prepare($queryEstados);
        $stmt->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmt->execute();
        $stats['por_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Por importancia
        $queryImp = "SELECT p.NivelImportancia, COUNT(*) as cantidad
                     FROM peticion_departamento pd
                     INNER JOIN peticiones p ON pd.peticion_id = p.id
                     WHERE pd.departamento_id = :unidad_id
                     GROUP BY p.NivelImportancia ORDER BY p.NivelImportancia";
        $stmt = $db->prepare($queryImp);
        $stmt->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmt->execute();
        $stats['por_importancia'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Nombre del departamento
        $stmtDept = $db->prepare("SELECT nombre_unidad FROM unidades WHERE id = :unidad_id");
        $stmtDept->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmtDept->execute();
        $deptInfo = $stmtDept->fetch(PDO::FETCH_ASSOC);
        $stats['nombre_departamento'] = $deptInfo ? $deptInfo['nombre_unidad'] : 'Departamento';

        // Alertas departamento
        $queryCriticas = "SELECT COUNT(*) as cantidad
                         FROM peticion_departamento pd
                         INNER JOIN peticiones p ON pd.peticion_id = p.id
                         WHERE pd.departamento_id = :unidad_id
                         AND p.NivelImportancia = 1
                         AND pd.estado NOT IN ($estadosFinalesDepto)";
        $stmt = $db->prepare($queryCriticas);
        $stmt->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmt->execute();
        $criticasCount = $stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];
        if ($criticasCount > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "Tienes {$criticasCount} peticiones críticas asignadas a tu departamento",
                'count' => $criticasCount
            ];
        }
        if ($base['retrasadas'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Tienes {$base['retrasadas']} peticiones retrasadas en tu departamento (más de " . DIAS_RETRASO_DEPARTAMENTO . " días sin completar)",
                'count' => intval($base['retrasadas'])
            ];
        }

    } elseif ($unidadId) {
        // Otro usuario con unidad — estadísticas básicas de su departamento
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM peticion_departamento WHERE departamento_id = :unidad_id");
        $stmt->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmt->execute();
        $stats['total_peticiones'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->prepare("SELECT estado, COUNT(*) as cantidad FROM peticion_departamento WHERE departamento_id = :unidad_id GROUP BY estado");
        $stmt->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmt->execute();
        $stats['por_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $stats['total_peticiones'] = $db->query("SELECT COUNT(*) as total FROM peticiones")->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // ==========================================
    // PETICIONES RECIENTES
    // ==========================================
    $recentPetitions = [];

    if (in_array($rolId, ROLES_ADMIN)) {
        $stmtRecent = $db->query(
            "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
                    p.NivelImportancia, p.fecha_registro, d.Municipio
             FROM peticiones p
             LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
             ORDER BY p.fecha_registro DESC LIMIT 10"
        );
        $recentPetitions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rolId == ROL_CANALIZADOR_MUNICIPAL && $divisionId) {
        $estadosFinales = "'" . implode("','", ESTADOS_FINALES) . "'";
        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
                    p.NivelImportancia, p.fecha_registro, d.Municipio,
                    DATEDIFF(CURDATE(), p.fecha_registro) as dias_transcurridos
             FROM peticiones p
             LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
             WHERE p.division_id = :division_id AND p.estado NOT IN ($estadosFinales)
             ORDER BY p.NivelImportancia ASC, p.fecha_registro ASC"
        );
        $stmt->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
        $stmt->execute();
        $recentPetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rolId == ROL_CANALIZADOR_ESTATAL) {
        $estadosFinales = "'" . implode("','", ESTADOS_FINALES) . "'";
        $recentPetitions = $db->query(
            "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
                    p.NivelImportancia, p.fecha_registro, d.Municipio,
                    DATEDIFF(CURDATE(), p.fecha_registro) as dias_transcurridos
             FROM peticiones p
             LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
             WHERE p.estado NOT IN ($estadosFinales)
             ORDER BY p.NivelImportancia ASC, p.fecha_registro ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($rolId == ROL_DEPARTAMENTO && $unidadId) {
        $estadosFinalesDepto = "'" . implode("','", ESTADOS_FINALES_DEPTO) . "'";
        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
                    p.NivelImportancia, p.fecha_registro, d.Municipio,
                    pd.estado as estado_departamento, pd.fecha_asignacion,
                    DATEDIFF(CURDATE(), pd.fecha_asignacion) as dias_asignacion
             FROM peticion_departamento pd
             INNER JOIN peticiones p ON pd.peticion_id = p.id
             LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
             WHERE pd.departamento_id = :unidad_id AND pd.estado NOT IN ($estadosFinalesDepto)
             ORDER BY p.NivelImportancia ASC, pd.fecha_asignacion ASC"
        );
        $stmt->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmt->execute();
        $recentPetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($unidadId) {
        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
                    p.NivelImportancia, p.fecha_registro, d.Municipio,
                    pd.estado as estado_departamento, pd.fecha_asignacion
             FROM peticion_departamento pd
             INNER JOIN peticiones p ON pd.peticion_id = p.id
             LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
             WHERE pd.departamento_id = :unidad_id
             ORDER BY pd.fecha_asignacion DESC LIMIT 10"
        );
        $stmt->bindValue(':unidad_id', $unidadId, PDO::PARAM_INT);
        $stmt->execute();
        $recentPetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Respuesta
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
    error_log("Error en dashboard-user.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error al obtener datos del dashboard',
        'error' => $e->getMessage()
    ], 500);
}
?>
