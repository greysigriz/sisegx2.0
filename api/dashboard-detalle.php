<?php
// API ligera para detalle de departamento/municipio con lista de peticiones
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : null; // 'departamento' o 'municipio'
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$tipo || !$id) {
        sendJson(['success' => false, 'message' => 'Parametros tipo e id requeridos'], 400);
    }

    if ($tipo === 'departamento') {
        // Info del departamento
        $stmt = $db->prepare("SELECT id, nombre_unidad FROM unidades WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$info) sendJson(['success' => false, 'message' => 'Departamento no encontrado'], 404);

        // Totales
        $stmt = $db->prepare("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN pd.estado NOT IN ('Completado','Rechazado') THEN 1 ELSE 0 END) as pendientes,
            ROUND(SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END)*100.0/NULLIF(COUNT(*),0),1) as tasa
        FROM peticion_departamento pd WHERE pd.departamento_id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $totales = $stmt->fetch(PDO::FETCH_ASSOC);

        // Municipios donde interviene
        $stmt = $db->prepare("SELECT d.Municipio as municipio, d.Id as municipio_id,
            COUNT(pd.id) as total,
            SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN pd.estado = 'Esperando recepción' THEN 1 ELSE 0 END) as esperando,
            SUM(CASE WHEN pd.estado = 'Aceptado en proceso' THEN 1 ELSE 0 END) as en_proceso
        FROM peticion_departamento pd
        INNER JOIN peticiones p ON pd.peticion_id = p.id
        INNER JOIN DivisionAdministrativa d ON p.division_id = d.Id
        WHERE pd.departamento_id = :id
        GROUP BY d.Id, d.Municipio ORDER BY total DESC");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Timeline mensual
        $stmt = $db->prepare("SELECT
            DATE_FORMAT(pd.fecha_asignacion, '%Y-%m-01') as fecha,
            COUNT(*) as nuevas,
            SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas
        FROM peticion_departamento pd WHERE pd.departamento_id = :id
        GROUP BY DATE_FORMAT(pd.fecha_asignacion, '%Y-%m') ORDER BY fecha ASC");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lista de peticiones asignadas a este departamento
        $stmt = $db->prepare("SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
            p.NivelImportancia, p.fecha_registro, p.localidad,
            da.Municipio, pd.estado as estado_asignacion, pd.fecha_asignacion,
            DATEDIFF(CURDATE(), pd.fecha_asignacion) as dias_asignado
        FROM peticion_departamento pd
        INNER JOIN peticiones p ON pd.peticion_id = p.id
        LEFT JOIN DivisionAdministrativa da ON p.division_id = da.Id
        WHERE pd.departamento_id = :id
        ORDER BY pd.fecha_asignacion DESC");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJson([
            'success' => true,
            'tipo' => 'departamento',
            'info' => $info,
            'totales' => $totales,
            'municipios' => $municipios,
            'timeline' => $timeline,
            'peticiones' => $peticiones
        ]);

    } elseif ($tipo === 'municipio') {
        // Info del municipio
        $stmt = $db->prepare("SELECT Id, Municipio FROM DivisionAdministrativa WHERE Id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$info) sendJson(['success' => false, 'message' => 'Municipio no encontrado'], 404);

        // Totales
        $stmt = $db->prepare("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN p.estado NOT IN ('Completado','Cancelada','Improcedente') THEN 1 ELSE 0 END) as activas,
            SUM(CASE WHEN p.NivelImportancia <= 2 THEN 1 ELSE 0 END) as urgentes
        FROM peticiones p WHERE p.division_id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $totales = $stmt->fetch(PDO::FETCH_ASSOC);

        // Departamentos que intervienen
        $stmt = $db->prepare("SELECT u.id as departamento_id, u.nombre_unidad as departamento,
            COUNT(pd.id) as total,
            SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN pd.estado = 'Esperando recepción' THEN 1 ELSE 0 END) as esperando,
            SUM(CASE WHEN pd.estado = 'Aceptado en proceso' THEN 1 ELSE 0 END) as en_proceso
        FROM peticion_departamento pd
        INNER JOIN peticiones p ON pd.peticion_id = p.id
        INNER JOIN unidades u ON pd.departamento_id = u.id
        WHERE p.division_id = :id
        GROUP BY u.id, u.nombre_unidad ORDER BY total DESC");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Timeline mensual
        $stmt = $db->prepare("SELECT
            DATE_FORMAT(p.fecha_registro, '%Y-%m-01') as fecha,
            COUNT(*) as nuevas,
            SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END) as completadas
        FROM peticiones p WHERE p.division_id = :id
        GROUP BY DATE_FORMAT(p.fecha_registro, '%Y-%m') ORDER BY fecha ASC");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lista de peticiones de este municipio (con departamentos asignados)
        $stmt = $db->prepare("SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
            p.NivelImportancia, p.fecha_registro, p.localidad,
            da.Municipio,
            DATEDIFF(CURDATE(), p.fecha_registro) as dias_transcurridos,
            GROUP_CONCAT(DISTINCT u.nombre_unidad ORDER BY u.nombre_unidad SEPARATOR ', ') as departamentos_asignados,
            GROUP_CONCAT(DISTINCT u.id ORDER BY u.nombre_unidad SEPARATOR ',') as departamento_ids
        FROM peticiones p
        LEFT JOIN DivisionAdministrativa da ON p.division_id = da.Id
        LEFT JOIN peticion_departamento pd ON pd.peticion_id = p.id
        LEFT JOIN unidades u ON pd.departamento_id = u.id
        WHERE p.division_id = :id
        GROUP BY p.id
        ORDER BY p.fecha_registro DESC");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJson([
            'success' => true,
            'tipo' => 'municipio',
            'info' => $info,
            'totales' => $totales,
            'departamentos' => $departamentos,
            'timeline' => $timeline,
            'peticiones' => $peticiones
        ]);

    } else {
        sendJson(['success' => false, 'message' => 'Tipo debe ser departamento o municipio'], 400);
    }

} catch (Exception $e) {
    error_log("Error en dashboard-detalle.php: " . $e->getMessage());
    sendJson(['success' => false, 'message' => 'Error al obtener detalle', 'error' => $e->getMessage()], 500);
}
?>
