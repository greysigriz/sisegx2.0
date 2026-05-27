<?php
// Endpoint para drill-down de peticiones desde Bienvenido.vue (canalizador)
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

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
        sendJson(['success' => false, 'message' => 'No hay sesion activa'], 401);
    }

    $database = new Database();
    $db = $database->getConnection();
    $userId = $_SESSION['user_id'];

    // Obtener info del usuario
    $stmtUser = $db->prepare("SELECT IdRolSistema, IdDivisionAdm FROM Usuario WHERE Id = :uid AND Estatus = 'ACTIVO'");
    $stmtUser->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) sendJson(['success' => false, 'message' => 'Usuario no encontrado'], 404);

    $rolId = $user['IdRolSistema'];
    $divisionId = $user['IdDivisionAdm'];

    // Solo canalizadores (12=Municipal, 13=Estatal)
    if (!in_array($rolId, [12, 13])) {
        sendJson(['success' => false, 'message' => 'Acceso no permitido'], 403);
    }

    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;
    if (!$tipo) {
        sendJson(['success' => false, 'message' => 'Parametro tipo requerido'], 400);
    }

    $where = [];
    $params = [];

    // Filtro base: canalizador municipal solo ve su municipio
    if ($rolId == 12 && $divisionId) {
        $where[] = "p.division_id = :division_id";
        $params[':division_id'] = $divisionId;
    }

    // Condiciones por tipo de drill-down
    switch ($tipo) {
        case 'total':
            break;
        case 'retrasadas':
            $where[] = "p.estado NOT IN ('Completada', 'Cancelada', 'Improcedente')";
            $where[] = "DATEDIFF(CURDATE(), p.fecha_registro) > 30";
            break;
        case 'alert_critical':
            $where[] = "p.NivelImportancia = 1";
            $where[] = "p.estado IN ('Sin revisar', 'Pendiente', 'Esperando recepción')";
            break;
        case 'alert_retrasadas':
            $where[] = "p.estado NOT IN ('Completada', 'Cancelada')";
            $where[] = "DATEDIFF(CURDATE(), p.fecha_registro) > 30";
            break;
        // Estados
        case 'estado':
            $estado = isset($_GET['valor']) ? trim($_GET['valor']) : '';
            if ($estado) {
                $where[] = "p.estado = :estado";
                $params[':estado'] = $estado;
            }
            break;
        // Importancia
        case 'importancia':
            $nivel = isset($_GET['valor']) ? intval($_GET['valor']) : 0;
            if ($nivel > 0) {
                $where[] = "p.NivelImportancia = :nivel";
                $params[':nivel'] = $nivel;
            }
            break;
        // Departamento
        case 'departamento':
            $deptNombre = isset($_GET['valor']) ? trim($_GET['valor']) : '';
            if ($deptNombre) {
                $where[] = "p.id IN (SELECT pd2.peticion_id FROM peticion_departamento pd2 INNER JOIN unidades u2 ON pd2.departamento_id = u2.id WHERE u2.nombre_unidad = :dept_nombre)";
                $params[':dept_nombre'] = $deptNombre;
            }
            break;
        // Municipio (solo estatal)
        case 'municipio':
            $muniNombre = isset($_GET['valor']) ? trim($_GET['valor']) : '';
            if ($muniNombre) {
                $where[] = "da.Municipio = :muni_nombre";
                $params[':muni_nombre'] = $muniNombre;
            }
            break;
        // Peticiones recientes/urgentes (las que requieren atencion)
        case 'urgentes':
            $where[] = "p.estado NOT IN ('Completada', 'Cancelada')";
            break;
        default:
            sendJson(['success' => false, 'message' => 'Tipo no valido'], 400);
    }

    $whereSQL = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
                   p.NivelImportancia, p.fecha_registro, p.localidad, p.telefono,
                   da.Municipio,
                   DATEDIFF(CURDATE(), p.fecha_registro) as dias_transcurridos
            FROM peticiones p
            LEFT JOIN DivisionAdministrativa da ON p.division_id = da.Id
            $whereSQL
            ORDER BY p.fecha_registro DESC
            LIMIT 200";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendJson([
        'success' => true,
        'tipo' => $tipo,
        'peticiones' => $peticiones,
        'total' => count($peticiones)
    ]);

} catch (Exception $e) {
    error_log("Error en dashboard-user-detalle.php: " . $e->getMessage());
    sendJson(['success' => false, 'message' => 'Error al obtener detalle', 'error' => $e->getMessage()], 500);
}
?>
