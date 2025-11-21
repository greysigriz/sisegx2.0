<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Obtener peticiones asignadas a departamentos
    if (isset($_GET['departamento_id'])) {
        $dept_id = intval($_GET['departamento_id']);
        
        $query = "SELECT 
                    pd.id as asignacion_id,
                    pd.estado as estado_departamento,
                    pd.fecha_asignacion,
                    p.id as peticion_id,
                    p.folio,
                    p.nombre,
                    p.telefono,
                    p.direccion,
                    p.localidad,
                    p.descripcion,
                    p.red_social,
                    p.fecha_registro,
                    p.estado as estado_peticion,
                    p.NivelImportancia,
                    u.nombre_unidad as departamento_nombre,
                    CONCAT(usr.Nombre, ' ', COALESCE(usr.ApellidoP, ''), ' ', COALESCE(usr.ApellidoM, '')) as usuario_seguimiento
                  FROM peticion_departamento pd
                  INNER JOIN peticiones p ON pd.peticion_id = p.id
                  LEFT JOIN unidades u ON pd.departamento_id = u.id
                  LEFT JOIN Usuario usr ON p.usuario_id = usr.Id
                  WHERE pd.departamento_id = ?
                  ORDER BY pd.fecha_asignacion DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$dept_id]);
        $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener historial para cada petición
        foreach ($peticiones as &$peticion) {
            $histQuery = "SELECT 
                            h.id,
                            h.estado_anterior,
                            h.estado_nuevo,
                            h.motivo,
                            h.fecha_cambio,
                            CONCAT(u.Nombre, ' ', COALESCE(u.ApellidoP, ''), ' ', COALESCE(u.ApellidoM, '')) as usuario_nombre
                          FROM peticion_departamento_historial h
                          LEFT JOIN Usuario u ON h.usuario_id = u.Id
                          WHERE h.peticion_departamento_id = ?
                          ORDER BY h.fecha_cambio DESC";
            
            $histStmt = $db->prepare($histQuery);
            $histStmt->execute([$peticion['asignacion_id']]);
            $peticion['historial'] = $histStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "records" => $peticiones
        ]);
    }
    // Obtener historial específico
    elseif (isset($_GET['asignacion_id'])) {
        $asig_id = intval($_GET['asignacion_id']);
        
        $query = "SELECT 
                    h.*,
                    CONCAT(u.Nombre, ' ', COALESCE(u.ApellidoP, ''), ' ', COALESCE(u.ApellidoM, '')) as usuario_nombre
                  FROM peticion_departamento_historial h
                  LEFT JOIN Usuario u ON h.usuario_id = u.Id
                  WHERE h.peticion_departamento_id = ?
                  ORDER BY h.fecha_cambio DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$asig_id]);
        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "historial" => $historial
        ]);
    }
    else {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "departamento_id requerido"]);
    }
}
elseif ($method === 'PUT') {
    // Actualizar estado de petición departamento con historial
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->asignacion_id) || empty($data->estado_nuevo) || empty($data->motivo)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Se requiere asignacion_id, estado_nuevo y motivo"
        ]);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Obtener estado anterior
        $getQuery = "SELECT estado FROM peticion_departamento WHERE id = ?";
        $getStmt = $db->prepare($getQuery);
        $getStmt->execute([$data->asignacion_id]);
        $current = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) {
            throw new Exception("Asignación no encontrada");
        }
        
        // Actualizar estado
        $updateQuery = "UPDATE peticion_departamento SET estado = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$data->estado_nuevo, $data->asignacion_id]);
        
        // Registrar en historial
        $histQuery = "INSERT INTO peticion_departamento_historial 
                     (peticion_departamento_id, estado_anterior, estado_nuevo, motivo, usuario_id) 
                     VALUES (?, ?, ?, ?, ?)";
        $histStmt = $db->prepare($histQuery);
        $histStmt->execute([
            $data->asignacion_id,
            $current['estado'],
            $data->estado_nuevo,
            $data->motivo,
            $data->usuario_id ?? null
        ]);
        
        $db->commit();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Estado actualizado correctamente",
            "historial_id" => $db->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error: " . $e->getMessage()
        ]);
    }
}
else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>
