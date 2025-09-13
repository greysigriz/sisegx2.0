<?php
// C:\xampp\htdocs\SISE\api\peticion-sugerencias.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Función para validar los datos de entrada
 */
function validateSugerenciaData($data, $isUpdate = false) {
    $errors = [];
    
    if (!$isUpdate && (empty($data->peticion_id) || !is_numeric($data->peticion_id))) {
        $errors[] = "peticion_id es requerido y debe ser numérico.";
    }
    
    if (empty($data->departamento_nombre)) {
        $errors[] = "departamento_nombre es requerido.";
    } elseif (strlen($data->departamento_nombre) > 255) {
        $errors[] = "departamento_nombre no puede exceder 255 caracteres.";
    }
    
    $estadosValidos = ['Pendiente', 'Aceptada', 'Rechazada'];
    if (isset($data->estado_sugerencia) && !in_array($data->estado_sugerencia, $estadosValidos)) {
        $errors[] = "estado_sugerencia debe ser uno de: " . implode(', ', $estadosValidos);
    }
    
    return $errors;
}

/**
 * Función para verificar si existe la petición
 */
function verificarPeticionExiste($db, $peticionId) {
    $query = "SELECT id FROM peticiones WHERE id = :peticion_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':peticion_id', $peticionId);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

if ($method === 'GET') {
    try {
        if (isset($_GET['id'])) {
            // Obtener una sugerencia específica
            $id = $_GET['id'];
            $query = "SELECT ps.*, p.folio, p.nombre as peticion_nombre 
                     FROM peticion_sugerencias ps 
                     LEFT JOIN peticiones p ON ps.peticion_id = p.id 
                     WHERE ps.id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $sugerencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sugerencia) {
                http_response_code(200);
                echo json_encode($sugerencia);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Sugerencia no encontrada."));
            }
        } elseif (isset($_GET['peticion_id'])) {
            // Obtener todas las sugerencias de una petición específica
            $peticionId = $_GET['peticion_id'];
            
            $query = "SELECT ps.*, p.folio, p.nombre as peticion_nombre 
                     FROM peticion_sugerencias ps 
                     LEFT JOIN peticiones p ON ps.peticion_id = p.id 
                     WHERE ps.peticion_id = :peticion_id 
                     ORDER BY ps.fecha_sugerencia ASC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':peticion_id', $peticionId);
            $stmt->execute();
            
            $sugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(array(
                "records" => $sugerencias,
                "total" => count($sugerencias)
            ));
        } else {
            // Obtener todas las sugerencias con filtros opcionales
            $baseQuery = "SELECT ps.*, p.folio, p.nombre as peticion_nombre 
                         FROM peticion_sugerencias ps 
                         LEFT JOIN peticiones p ON ps.peticion_id = p.id";
            
            $whereClause = [];
            $params = [];
            
            // Filtros
            if (isset($_GET['estado']) && !empty($_GET['estado'])) {
                $whereClause[] = "ps.estado_sugerencia = :estado";
                $params[':estado'] = $_GET['estado'];
            }
            
            if (isset($_GET['departamento']) && !empty($_GET['departamento'])) {
                $whereClause[] = "ps.departamento_nombre LIKE :departamento";
                $params[':departamento'] = '%' . $_GET['departamento'] . '%';
            }
            
            if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
                $whereClause[] = "DATE(ps.fecha_sugerencia) >= :fecha_desde";
                $params[':fecha_desde'] = $_GET['fecha_desde'];
            }
            
            if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
                $whereClause[] = "DATE(ps.fecha_sugerencia) <= :fecha_hasta";
                $params[':fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            // Construir consulta final
            $query = $baseQuery;
            if (!empty($whereClause)) {
                $query .= " WHERE " . implode(" AND ", $whereClause);
            }
            $query .= " ORDER BY ps.fecha_sugerencia DESC";
            
            // Paginación
            $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 50;
            $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
            $query .= " LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $sugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total de registros
            $countQuery = str_replace("SELECT ps.*, p.folio, p.nombre as peticion_nombre", "SELECT COUNT(*)", $baseQuery);
            if (!empty($whereClause)) {
                $countQuery .= " WHERE " . implode(" AND ", $whereClause);
            }
            
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetchColumn();
            
            http_response_code(200);
            echo json_encode(array(
                "records" => $sugerencias,
                "total" => intval($total),
                "limit" => $limit,
                "offset" => $offset
            ));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error: " . $e->getMessage()));
    }
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(array("message" => "Datos JSON inválidos."));
        exit;
    }
    
    // Validar datos
    $errors = validateSugerenciaData($data);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(array(
            "message" => "Errores de validación.",
            "errors" => $errors
        ));
        exit;
    }
    
    // Verificar que existe la petición
    if (!verificarPeticionExiste($db, $data->peticion_id)) {
        http_response_code(404);
        echo json_encode(array("message" => "La petición especificada no existe."));
        exit;
    }
    
    try {
        // Verificar si ya existe una sugerencia para este departamento en esta petición
        $checkQuery = "SELECT id FROM peticion_sugerencias 
                      WHERE peticion_id = :peticion_id AND departamento_nombre = :departamento_nombre";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':peticion_id', $data->peticion_id);
        $checkStmt->bindParam(':departamento_nombre', $data->departamento_nombre);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(array("message" => "Ya existe una sugerencia para este departamento en esta petición."));
            exit;
        }
        
        // Insertar nueva sugerencia
        $query = "INSERT INTO peticion_sugerencias (peticion_id, departamento_nombre, estado_sugerencia) 
                 VALUES (:peticion_id, :departamento_nombre, :estado_sugerencia)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':peticion_id', $data->peticion_id);
        $stmt->bindParam(':departamento_nombre', htmlspecialchars(strip_tags($data->departamento_nombre)));
        
        $estado = isset($data->estado_sugerencia) ? $data->estado_sugerencia : 'Pendiente';
        $stmt->bindParam(':estado_sugerencia', $estado);
        
        if ($stmt->execute()) {
            $sugerencia_id = $db->lastInsertId();
            
            // Obtener la sugerencia recién creada con datos relacionados
            $selectQuery = "SELECT ps.*, p.folio, p.nombre as peticion_nombre 
                           FROM peticion_sugerencias ps 
                           LEFT JOIN peticiones p ON ps.peticion_id = p.id 
                           WHERE ps.id = :id";
            $selectStmt = $db->prepare($selectQuery);
            $selectStmt->bindParam(':id', $sugerencia_id);
            $selectStmt->execute();
            $nuevaSugerencia = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(201);
            echo json_encode(array(
                "success" => true,
                "message" => "Sugerencia creada con éxito.",
                "id" => $sugerencia_id,
                "data" => $nuevaSugerencia
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo crear la sugerencia."));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error: " . $e->getMessage()));
    }
}
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || empty($data->id)) {
        http_response_code(400);
        echo json_encode(array("message" => "ID no proporcionado o datos JSON inválidos."));
        exit;
    }
    
    // Validar datos (excluyendo peticion_id ya que no se debe cambiar)
    $errors = validateSugerenciaData($data, true);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(array(
            "message" => "Errores de validación.",
            "errors" => $errors
        ));
        exit;
    }
    
    try {
        // Verificar que la sugerencia existe
        $checkQuery = "SELECT id, peticion_id FROM peticion_sugerencias WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $data->id);
        $checkStmt->execute();
        $existingSugerencia = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingSugerencia) {
            http_response_code(404);
            echo json_encode(array("message" => "Sugerencia no encontrada."));
            exit;
        }
        
        // Construir consulta de actualización dinámicamente
        $updateFields = [];
        $params = [':id' => $data->id];
        
        if (isset($data->departamento_nombre)) {
            // Verificar que no exista otra sugerencia con el mismo departamento para la misma petición
            $duplicateQuery = "SELECT id FROM peticion_sugerencias 
                              WHERE peticion_id = :peticion_id AND departamento_nombre = :departamento_nombre AND id != :current_id";
            $duplicateStmt = $db->prepare($duplicateQuery);
            $duplicateStmt->bindParam(':peticion_id', $existingSugerencia['peticion_id']);
            $duplicateStmt->bindParam(':departamento_nombre', $data->departamento_nombre);
            $duplicateStmt->bindParam(':current_id', $data->id);
            $duplicateStmt->execute();
            
            if ($duplicateStmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(array("message" => "Ya existe otra sugerencia para este departamento en esta petición."));
                exit;
            }
            
            $updateFields[] = "departamento_nombre = :departamento_nombre";
            $params[':departamento_nombre'] = htmlspecialchars(strip_tags($data->departamento_nombre));
        }
        
        if (isset($data->estado_sugerencia)) {
            $updateFields[] = "estado_sugerencia = :estado_sugerencia";
            $params[':estado_sugerencia'] = $data->estado_sugerencia;
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(array("message" => "No hay campos para actualizar."));
            exit;
        }
        
        // Ejecutar actualización
        $updateQuery = "UPDATE peticion_sugerencias SET " . implode(", ", $updateFields) . " WHERE id = :id";
        $stmt = $db->prepare($updateQuery);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if ($stmt->execute()) {
            // Obtener la sugerencia actualizada
            $selectQuery = "SELECT ps.*, p.folio, p.nombre as peticion_nombre 
                           FROM peticion_sugerencias ps 
                           LEFT JOIN peticiones p ON ps.peticion_id = p.id 
                           WHERE ps.id = :id";
            $selectStmt = $db->prepare($selectQuery);
            $selectStmt->bindParam(':id', $data->id);
            $selectStmt->execute();
            $sugerenciaActualizada = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Sugerencia actualizada con éxito.",
                "data" => $sugerenciaActualizada
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo actualizar la sugerencia."));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error: " . $e->getMessage()));
    }
}
elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || empty($data->id)) {
        http_response_code(400);
        echo json_encode(array("message" => "ID no proporcionado o datos JSON inválidos."));
        exit;
    }
    
    try {
        // Verificar que la sugerencia existe
        $checkQuery = "SELECT id FROM peticion_sugerencias WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $data->id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(array("message" => "Sugerencia no encontrada."));
            exit;
        }
        
        // Eliminar sugerencia
        $deleteQuery = "DELETE FROM peticion_sugerencias WHERE id = :id";
        $stmt = $db->prepare($deleteQuery);
        $stmt->bindParam(':id', $data->id);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Sugerencia eliminada con éxito."
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo eliminar la sugerencia."));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error: " . $e->getMessage()));
    }
}
// Método PATCH para operaciones específicas (ej: cambiar solo el estado)
elseif ($method === 'PATCH') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || empty($data->id)) {
        http_response_code(400);
        echo json_encode(array("message" => "ID no proporcionado o datos JSON inválidos."));
        exit;
    }
    
    // Operación para cambiar el estado de múltiples sugerencias
    if (isset($data->operacion) && $data->operacion === 'cambiar_estado_lote') {
        if (empty($data->ids) || !is_array($data->ids) || empty($data->nuevo_estado)) {
            http_response_code(400);
            echo json_encode(array("message" => "IDs o nuevo_estado no proporcionados para operación en lote."));
            exit;
        }
        
        $estadosValidos = ['Pendiente', 'Aceptada', 'Rechazada'];
        if (!in_array($data->nuevo_estado, $estadosValidos)) {
            http_response_code(400);
            echo json_encode(array("message" => "Estado inválido. Debe ser: " . implode(', ', $estadosValidos)));
            exit;
        }
        
        try {
            $db->beginTransaction();
            
            $placeholders = str_repeat('?,', count($data->ids) - 1) . '?';
            $updateQuery = "UPDATE peticion_sugerencias SET estado_sugerencia = ? WHERE id IN ($placeholders)";
            $stmt = $db->prepare($updateQuery);
            
            $params = array_merge([$data->nuevo_estado], $data->ids);
            $result = $stmt->execute($params);
            
            if ($result) {
                $affectedRows = $stmt->rowCount();
                $db->commit();
                
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Estados actualizados con éxito.",
                    "affected_rows" => $affectedRows
                ));
            } else {
                $db->rollBack();
                http_response_code(503);
                echo json_encode(array("message" => "No se pudieron actualizar los estados."));
            }
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    } 
    // Operación para cambiar estado individual
    else {
        if (empty($data->estado_sugerencia)) {
            http_response_code(400);
            echo json_encode(array("message" => "estado_sugerencia no proporcionado."));
            exit;
        }
        
        $estadosValidos = ['Pendiente', 'Aceptada', 'Rechazada'];
        if (!in_array($data->estado_sugerencia, $estadosValidos)) {
            http_response_code(400);
            echo json_encode(array("message" => "Estado inválido. Debe ser: " . implode(', ', $estadosValidos)));
            exit;
        }
        
        try {
            $updateQuery = "UPDATE peticion_sugerencias SET estado_sugerencia = :estado WHERE id = :id";
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':estado', $data->estado_sugerencia);
            $stmt->bindParam(':id', $data->id);
            
            if ($stmt->execute()) {
                // Obtener la sugerencia actualizada
                $selectQuery = "SELECT ps.*, p.folio, p.nombre as peticion_nombre 
                               FROM peticion_sugerencias ps 
                               LEFT JOIN peticiones p ON ps.peticion_id = p.id 
                               WHERE ps.id = :id";
                $selectStmt = $db->prepare($selectQuery);
                $selectStmt->bindParam(':id', $data->id);
                $selectStmt->execute();
                $sugerenciaActualizada = $selectStmt->fetch(PDO::FETCH_ASSOC);
                
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Estado actualizado con éxito.",
                    "data" => $sugerenciaActualizada
                ));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el estado."));
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    }
}
else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido."));
}
?>