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

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        sendJson(['success' => false, 'message' => 'No hay sesión activa'], 401);
    }

    $database = new Database();
    $db = $database->getConnection();
    $userId = $_SESSION['user_id'];

    $stmtUser = $db->prepare("SELECT IdRolSistema, IdDivisionAdm FROM Usuario WHERE Id = :uid AND Estatus = 'ACTIVO'");
    $stmtUser->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) sendJson(['success' => false, 'message' => 'Usuario no encontrado'], 404);

    $rolId = $user['IdRolSistema'];
    $divisionId = $user['IdDivisionAdm'];

    if (!in_array($rolId, ROLES_CON_DRILLDOWN)) {
        sendJson(['success' => false, 'message' => 'Acceso no permitido'], 403);
    }

    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
    if (!$tipo) {
        sendJson(['success' => false, 'message' => 'Parámetro tipo requerido'], 400);
    }

    // Paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(200, max(10, intval($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];
    $joins = "LEFT JOIN DivisionAdministrativa da ON p.division_id = da.Id";

    // Filtro base: canalizador municipal solo ve su municipio
    if ($rolId == ROL_CANALIZADOR_MUNICIPAL && $divisionId) {
        $where[] = "p.division_id = :division_id";
        $params[':division_id'] = $divisionId;
    }

    $estadosFinales = "'" . implode("','", ESTADOS_FINALES) . "'";
    $estadosCriticos = "'" . implode("','", ESTADOS_CRITICOS) . "'";

    switch ($tipo) {
        case 'total':
            break;
        case 'retrasadas':
            $where[] = "p.estado NOT IN ($estadosFinales)";
            $where[] = "DATEDIFF(CURDATE(), p.fecha_registro) > " . DIAS_RETRASO_PETICION;
            break;
        case 'alert_critical':
            $where[] = "p.NivelImportancia = 1";
            $where[] = "p.estado IN ($estadosCriticos)";
            break;
        case 'alert_retrasadas':
            $where[] = "p.estado NOT IN ($estadosFinales)";
            $where[] = "DATEDIFF(CURDATE(), p.fecha_registro) > " . DIAS_RETRASO_PETICION;
            break;
        case 'estado':
            $estado = isset($_GET['valor']) ? trim($_GET['valor']) : '';
            if (!$estado) sendJson(['success' => false, 'message' => 'Valor de estado requerido'], 400);
            $where[] = "p.estado = :estado";
            $params[':estado'] = $estado;
            break;
        case 'importancia':
            $nivel = intval($_GET['valor'] ?? 0);
            if ($nivel <= 0) sendJson(['success' => false, 'message' => 'Valor de importancia requerido'], 400);
            $where[] = "p.NivelImportancia = :nivel";
            $params[':nivel'] = $nivel;
            break;
        case 'departamento':
            $deptNombre = isset($_GET['valor']) ? trim($_GET['valor']) : '';
            if (!$deptNombre) sendJson(['success' => false, 'message' => 'Nombre de departamento requerido'], 400);
            $joins .= " INNER JOIN peticion_departamento pd_f ON p.id = pd_f.peticion_id
                        INNER JOIN unidades u_f ON pd_f.departamento_id = u_f.id";
            $where[] = "u_f.nombre_unidad = :dept_nombre";
            $params[':dept_nombre'] = $deptNombre;
            break;
        case 'municipio':
            $muniNombre = isset($_GET['valor']) ? trim($_GET['valor']) : '';
            if (!$muniNombre) sendJson(['success' => false, 'message' => 'Nombre de municipio requerido'], 400);
            $where[] = "da.Municipio = :muni_nombre";
            $params[':muni_nombre'] = $muniNombre;
            break;
        case 'urgentes':
            $where[] = "p.estado NOT IN ($estadosFinales)";
            break;
        default:
            sendJson(['success' => false, 'message' => 'Tipo no válido'], 400);
    }

    $whereSQL = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    // Query de conteo total
    $countSQL = "SELECT COUNT(*) as total FROM peticiones p $joins $whereSQL";
    $stmtCount = $db->prepare($countSQL);
    foreach ($params as $key => $val) {
        $stmtCount->bindValue($key, $val);
    }
    $stmtCount->execute();
    $totalRows = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $limit);

    // Query de datos con paginación
    $sql = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
                   p.NivelImportancia, p.fecha_registro, p.localidad, p.telefono,
                   da.Municipio,
                   DATEDIFF(CURDATE(), p.fecha_registro) as dias_transcurridos
            FROM peticiones p
            $joins
            $whereSQL
            ORDER BY p.fecha_registro DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendJson([
        'success' => true,
        'tipo' => $tipo,
        'peticiones' => $peticiones,
        'total' => $totalRows,
        'page' => $page,
        'limit' => $limit,
        'pages' => $totalPages
    ]);

} catch (Exception $e) {
    error_log("Error en dashboard-user-detalle.php: " . $e->getMessage());
    sendJson(['success' => false, 'message' => 'Error al obtener detalle', 'error' => $e->getMessage()], 500);
}
?>
