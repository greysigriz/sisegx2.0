<?php
// C:\xampp\htdocs\SISE\api\peticiones.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Verificar si se solicita una petición específica
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        
        $query = "SELECT p.*, 
                         us.Usuario as nombre_usuario_seguimiento,
                         CONCAT(us.Nombre, ' ', us.ApellidoP, ' ', IFNULL(us.ApellidoM, '')) as nombre_completo_usuario
                  FROM peticiones p 
                  LEFT JOIN Usuario us ON p.usuario_id = us.Id
                  WHERE p.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $peticion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($peticion) {
            // Obtener departamentos asignados
            $depQuery = "SELECT pd.id as asignacion_id, pd.departamento_id, pd.estado as estado_departamento, 
                               pd.fecha_asignacion, u.nombre_unidad
                        FROM peticion_departamento pd
                        LEFT JOIN unidades u ON pd.departamento_id = u.id
                        WHERE pd.peticion_id = :peticion_id
                        ORDER BY pd.fecha_asignacion DESC";
            
            $depStmt = $db->prepare($depQuery);
            $depStmt->bindParam(':peticion_id', $id);
            $depStmt->execute();
            
            $peticion['departamentos'] = $depStmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode($peticion);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Petición no encontrada."));
        }
    } else {
        // Construir la consulta base
        $query = "SELECT p.*, 
                         us.Usuario as nombre_usuario_seguimiento,
                         CONCAT(us.Nombre, ' ', us.ApellidoP, ' ', IFNULL(us.ApellidoM, '')) as nombre_completo_usuario
                  FROM peticiones p 
                  LEFT JOIN Usuario us ON p.usuario_id = us.Id";
        
        // Aplicar filtros si existen
        $whereClause = [];
        $params = [];
        
        if (isset($_GET['estado']) && !empty($_GET['estado'])) {
            $whereClause[] = "p.estado = :estado";
            $params[':estado'] = $_GET['estado'];
        }
        
        if (isset($_GET['departamento']) && !empty($_GET['departamento'])) {
            // Buscar peticiones que tengan asignado este departamento
            $whereClause[] = "EXISTS (SELECT 1 FROM peticion_departamento pd WHERE pd.peticion_id = p.id AND pd.departamento_id = :departamento)";
            $params[':departamento'] = $_GET['departamento'];
        }
        
        if (isset($_GET['folio']) && !empty($_GET['folio'])) {
            $whereClause[] = "p.folio LIKE :folio";
            $params[':folio'] = '%' . $_GET['folio'] . '%';
        }
        
        if (isset($_GET['nombre']) && !empty($_GET['nombre'])) {
            $whereClause[] = "p.nombre LIKE :nombre";
            $params[':nombre'] = '%' . $_GET['nombre'] . '%';
        }
        
        // Filtrar por nivel de importancia
        if (isset($_GET['nivelImportancia']) && !empty($_GET['nivelImportancia'])) {
            $whereClause[] = "p.NivelImportancia = :nivelImportancia";
            $params[':nivelImportancia'] = intval($_GET['nivelImportancia']);
        }
        
        // Filtrar por usuario de seguimiento
        if (isset($_GET['usuario_seguimiento']) && !empty($_GET['usuario_seguimiento'])) {
            $whereClause[] = "p.usuario_id = :usuario_seguimiento";
            $params[':usuario_seguimiento'] = intval($_GET['usuario_seguimiento']);
        }
        
        // ✅ NUEVO: Filtrar por usuario logueado (mis peticiones)
        if (isset($_GET['mis_peticiones']) && $_GET['mis_peticiones'] === 'true') {
            // Verificar que se haya proporcionado el ID del usuario logueado
            if (isset($_GET['current_user_id']) && !empty($_GET['current_user_id'])) {
                $whereClause[] = "p.usuario_id = :current_user_id";
                $params[':current_user_id'] = intval($_GET['current_user_id']);
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "ID de usuario no proporcionado para filtrar 'mis peticiones'."));
                exit;
            }
        }
        
        // Agregar cláusula WHERE a la consulta si hay filtros
        if (!empty($whereClause)) {
            $query .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        // Ordenar por fecha de registro descendente (más reciente primero)
        $query .= " ORDER BY p.fecha_registro DESC";
        
        $stmt = $db->prepare($query);
        
        // Vincular parámetros
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $num = $stmt->rowCount();
        
        if ($num > 0) {
            $peticiones_arr = array();
            $peticiones_arr["records"] = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Obtener departamentos asignados para cada petición
                $depQuery = "SELECT pd.id as asignacion_id, pd.departamento_id, pd.estado as estado_departamento, 
                                   pd.fecha_asignacion, u.nombre_unidad
                            FROM peticion_departamento pd
                            LEFT JOIN unidades u ON pd.departamento_id = u.id
                            WHERE pd.peticion_id = :peticion_id
                            ORDER BY pd.fecha_asignacion DESC";
                
                $depStmt = $db->prepare($depQuery);
                $depStmt->bindParam(':peticion_id', $row['id']);
                $depStmt->execute();
                
                $departamentos = $depStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Transformar datos para el frontend
                $peticion_item = array(
                    "id" => $row['id'],
                    "folio" => $row['folio'],
                    "nombre" => $row['nombre'],
                    "telefono" => $row['telefono'],
                    "direccion" => $row['direccion'],
                    "localidad" => $row['localidad'],
                    "descripcion" => $row['descripcion'],
                    "red_social" => $row['red_social'],
                    "fecha_registro" => $row['fecha_registro'],
                    "estado" => $row['estado'],
                    "NivelImportancia" => $row['NivelImportancia'],
                    "usuario_id" => $row['usuario_id'],
                    "nombre_usuario_seguimiento" => $row['nombre_usuario_seguimiento'],
                    "nombre_completo_usuario" => $row['nombre_completo_usuario'],
                    "departamentos" => $departamentos
                );
                
                array_push($peticiones_arr["records"], $peticion_item);
            }
            
            http_response_code(200);
            echo json_encode($peticiones_arr);
        } else {
            http_response_code(200);
            echo json_encode(array("records" => array()));
        }
    }
} 
elseif ($method === 'POST') {
    // Recibir los datos enviados
    $data = json_decode(file_get_contents("php://input"));
    
    // Verificar si es una acción de seguimiento
    if (isset($data->accion) && $data->accion === 'seguimiento') {
        if (!empty($data->peticion_id) && !empty($data->usuario_id)) {
            try {
                // Verificar que la petición existe
                $check_query = "SELECT id FROM peticiones WHERE id = :peticion_id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':peticion_id', $data->peticion_id);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() === 0) {
                    http_response_code(404);
                    echo json_encode(array("message" => "Petición no encontrada."));
                    exit;
                }
                
                // Verificar que el usuario existe
                $user_check_query = "SELECT Id FROM Usuario WHERE Id = :usuario_id";
                $user_check_stmt = $db->prepare($user_check_query);
                $user_check_stmt->bindParam(':usuario_id', $data->usuario_id);
                $user_check_stmt->execute();
                
                if ($user_check_stmt->rowCount() === 0) {
                    http_response_code(404);
                    echo json_encode(array("message" => "Usuario no encontrado."));
                    exit;
                }
                
                // Actualizar la petición con el usuario de seguimiento
                $update_query = "UPDATE peticiones SET usuario_id = :usuario_id WHERE id = :peticion_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':usuario_id', $data->usuario_id);
                $update_stmt->bindParam(':peticion_id', $data->peticion_id);
                
                if ($update_stmt->execute()) {
                    // Obtener información del usuario para la respuesta
                    $user_info_query = "SELECT Usuario, CONCAT(Nombre, ' ', ApellidoP, ' ', IFNULL(ApellidoM, '')) as nombre_completo 
                                       FROM Usuario WHERE Id = :usuario_id";
                    $user_info_stmt = $db->prepare($user_info_query);
                    $user_info_stmt->bindParam(':usuario_id', $data->usuario_id);
                    $user_info_stmt->execute();
                    $user_info = $user_info_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    http_response_code(200);
                    echo json_encode(array(
                        "message" => "Seguimiento asignado correctamente.",
                        "usuario_asignado" => $user_info['Usuario'],
                        "nombre_completo" => $user_info['nombre_completo']
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "No se pudo asignar el seguimiento."));
                }
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(array("message" => "Error: " . $e->getMessage()));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos para el seguimiento. Se requiere peticion_id y usuario_id."));
        }
    }
    // Crear nueva petición (código original)
    elseif (
        !empty($data->folio) && 
        !empty($data->nombre) && 
        !empty($data->telefono) && 
        !empty($data->direccion) && 
        !empty($data->localidad) && 
        !empty($data->descripcion)
    ) {
        try {
            // Verificar si el folio ya existe
            $check_query = "SELECT id FROM peticiones WHERE folio = :folio";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':folio', $data->folio);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                http_response_code(409); // Conflict
                echo json_encode(array("message" => "Error: El folio ya existe."));
                exit;
            }
            
            // Consulta SQL para insertar petición (sin departamento)
            $query = "INSERT INTO peticiones 
                      (folio, nombre, telefono, direccion, localidad, descripcion, red_social, estado, NivelImportancia, usuario_id) 
                      VALUES 
                      (:folio, :nombre, :telefono, :direccion, :localidad, :descripcion, :red_social, :estado, :nivelImportancia, :usuario_id)";
            
            $stmt = $db->prepare($query);
            
            // Sanitizar datos
            $folio = htmlspecialchars(strip_tags($data->folio));
            $nombre = htmlspecialchars(strip_tags($data->nombre));
            $telefono = htmlspecialchars(strip_tags($data->telefono));
            $direccion = htmlspecialchars(strip_tags($data->direccion));
            $localidad = htmlspecialchars(strip_tags($data->localidad));
            $descripcion = htmlspecialchars(strip_tags($data->descripcion));
            $red_social = isset($data->red_social) ? htmlspecialchars(strip_tags($data->red_social)) : null;
            $estado = isset($data->estado) ? htmlspecialchars(strip_tags($data->estado)) : 'Sin revisar';
            $nivelImportancia = isset($data->NivelImportancia) ? intval($data->NivelImportancia) : 3;
            $usuario_id = isset($data->usuario_id) ? intval($data->usuario_id) : null;
            
            // Validar nivel de importancia (rango 1-5)
            if ($nivelImportancia < 1 || $nivelImportancia > 5) {
                $nivelImportancia = 3;
            }
            
            // Vincular valores
            $stmt->bindParam(':folio', $folio);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':direccion', $direccion);
            $stmt->bindParam(':localidad', $localidad);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':red_social', $red_social);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':nivelImportancia', $nivelImportancia);
            $stmt->bindParam(':usuario_id', $usuario_id);
            
            // Ejecutar la consulta
            if ($stmt->execute()) {
                $id = $db->lastInsertId();
                
                http_response_code(201);
                echo json_encode(array(
                    "message" => "Petición creada con éxito.",
                    "id" => $id
                ));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear la petición."));
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "No se puede crear la petición. Datos incompletos."));
    }
}
elseif ($method === 'PUT') {
    // Recibir los datos enviados
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id)) {
        try {
            // Consulta base para actualización
            $updateFields = [];
            $params = [':id' => $data->id];
            
            // Edición completa de la petición
            if (
                isset($data->folio) && 
                isset($data->nombre) && 
                isset($data->telefono) && 
                isset($data->direccion) && 
                isset($data->localidad) && 
                isset($data->descripcion)
            ) {
                $updateFields[] = "folio = :folio";
                $updateFields[] = "nombre = :nombre";
                $updateFields[] = "telefono = :telefono";
                $updateFields[] = "direccion = :direccion";
                $updateFields[] = "localidad = :localidad";
                $updateFields[] = "descripcion = :descripcion";
                $updateFields[] = "red_social = :red_social";
                
                $params[':folio'] = htmlspecialchars(strip_tags($data->folio));
                $params[':nombre'] = htmlspecialchars(strip_tags($data->nombre));
                $params[':telefono'] = htmlspecialchars(strip_tags($data->telefono));
                $params[':direccion'] = htmlspecialchars(strip_tags($data->direccion));
                $params[':localidad'] = htmlspecialchars(strip_tags($data->localidad));
                $params[':descripcion'] = htmlspecialchars(strip_tags($data->descripcion));
                $params[':red_social'] = isset($data->red_social) ? htmlspecialchars(strip_tags($data->red_social)) : null;
            }
            
            // Actualización de estado
            if (isset($data->estado)) {
                $updateFields[] = "estado = :estado";
                $params[':estado'] = htmlspecialchars(strip_tags($data->estado));
            }
            
            // Actualización de nivel de importancia
            if (isset($data->NivelImportancia)) {
                // Validar nivel de importancia (rango 1-5)
                $nivelImportancia = intval($data->NivelImportancia);
                if ($nivelImportancia >= 1 && $nivelImportancia <= 5) {
                    $updateFields[] = "NivelImportancia = :nivelImportancia";
                    $params[':nivelImportancia'] = $nivelImportancia;
                }
            }
            
            // Actualización de usuario de seguimiento
            if (isset($data->usuario_id)) {
                $updateFields[] = "usuario_id = :usuario_id";
                $params[':usuario_id'] = $data->usuario_id ? intval($data->usuario_id) : null;
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(array("message" => "No hay campos para actualizar."));
                exit;
            }
            
            $query = "UPDATE peticiones SET " . implode(", ", $updateFields) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            
            // Vincular todos los parámetros
            foreach ($params as $key => $value) {
                if ($value === null) {
                    $stmt->bindValue($key, $value, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            // Ejecutar la consulta
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(array("message" => "Petición actualizada con éxito."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar la petición."));
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "No se puede actualizar la petición. ID no proporcionado."));
    }
}
elseif ($method === 'DELETE') {
    // Recibir los datos enviados
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id)) {
        try {
            // Consulta SQL para eliminar petición
            $query = "DELETE FROM peticiones WHERE id = :id";
            $stmt = $db->prepare($query);
            
            // Sanitizar ID
            $id = htmlspecialchars(strip_tags($data->id));
            
            // Vincular valor
            $stmt->bindParam(':id', $id);
            
            // Ejecutar la consulta
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(array("message" => "Petición eliminada con éxito."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar la petición."));
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "No se puede eliminar la petición. ID no proporcionado."));
    }
} 
else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido."));
}
?>