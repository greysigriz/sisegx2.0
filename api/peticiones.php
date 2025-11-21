<?php
// C:\xampp\htdocs\SISEE\api\peticiones.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

function generateSequentialFolio($db) {
    $query = "SELECT folio FROM peticiones WHERE folio LIKE 'FOLIO-%' ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $lastFolio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastFolio && $lastFolio['folio']) {
        preg_match('/FOLIO-(\d+)/', $lastFolio['folio'], $matches);
        $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }
    
    return 'FOLIO-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
}

function buildQuery($baseQuery, $filters) {
    $whereClause = [];
    $params = [];
    $joins = [];
    
    // ✅ NUEVO: Agregar JOIN para departamentos si es necesario
    if (isset($filters['departamento']) && !empty($filters['departamento'])) {
        $joins[] = "LEFT JOIN peticion_departamento pd ON p.id = pd.peticion_id";
        $whereClause[] = "pd.departamento_id = :departamento";
        $params[':departamento'] = intval($filters['departamento']);
    }
    
    $filterMap = [
        'estado' => 'p.estado = :estado',
        'folio' => 'p.folio LIKE :folio',
        'nombre' => 'p.nombre LIKE :nombre',
        'nivelImportancia' => 'p.NivelImportancia = :nivelImportancia'
    ];
    
    foreach ($filterMap as $key => $condition) {
        if (isset($filters[$key]) && !empty($filters[$key]) && $key !== 'departamento') {
            $whereClause[] = $condition;
            $paramKey = ':' . $key;
            $params[$paramKey] = in_array($key, ['folio', 'nombre']) 
                ? '%' . $filters[$key] . '%' 
                : (in_array($key, ['nivelImportancia']) 
                    ? intval($filters[$key]) 
                    : $filters[$key]);
        }
    }
    
    // ✅ Agregar JOINs a la query
    if (!empty($joins)) {
        $baseQuery .= " " . implode(" ", $joins);
    }
    
    if (!empty($whereClause)) {
        $baseQuery .= " WHERE " . implode(" AND ", $whereClause);
    }
    
    // ✅ NUEVO: GROUP BY para evitar duplicados cuando hay múltiples departamentos
    if (isset($filters['departamento']) && !empty($filters['departamento'])) {
        $baseQuery .= " GROUP BY p.id";
    }
    
    return [$baseQuery . " ORDER BY p.fecha_registro DESC", $params];
}

// ✅ NUEVA función para obtener departamentos de una petición
function getPetitionDepartments($db, $petitionId) {
    $deptQuery = "SELECT 
                    pd.id as asignacion_id,
                    pd.departamento_id,
                    pd.estado as estado_asignacion,
                    pd.fecha_asignacion,
                    u.nombre_unidad
                  FROM peticion_departamento pd
                  LEFT JOIN unidades u ON pd.departamento_id = u.id
                  WHERE pd.peticion_id = ?
                  ORDER BY pd.fecha_asignacion DESC";
    
    $deptStmt = $db->prepare($deptQuery);
    $deptStmt->execute([$petitionId]);
    return $deptStmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPetitionRelatedData($db, $petitionId) {
    // Obtener sugerencias de IA
    $sugQuery = "SELECT id, departamento_nombre, estado_sugerencia as estado, fecha_sugerencia as fecha
                FROM peticion_sugerencias
                WHERE peticion_id = ?
                ORDER BY fecha_sugerencia ASC";
    
    $sugStmt = $db->prepare($sugQuery);
    $sugStmt->execute([$petitionId]);
    $sugerencias = $sugStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener departamentos asignados
    $departamentos = getPetitionDepartments($db, $petitionId);
    
    return [
        'sugerencias_ia' => $sugerencias,
        'departamentos' => $departamentos
    ];
}

if ($method === 'GET') {
    $baseQuery = "SELECT 
                    p.*,
                    u.Nombre as nombre_usuario_seguimiento,
                    u.ApellidoP as apellido_paterno_usuario,
                    u.ApellidoM as apellido_materno_usuario,
                    CONCAT(
                        u.Nombre, 
                        CASE 
                            WHEN u.ApellidoP IS NOT NULL AND u.ApellidoP != '' THEN CONCAT(' ', u.ApellidoP) 
                            ELSE '' 
                        END,
                        CASE 
                            WHEN u.ApellidoM IS NOT NULL AND u.ApellidoM != '' THEN CONCAT(' ', u.ApellidoM) 
                            ELSE '' 
                        END
                    ) as nombre_completo_usuario
                  FROM peticiones p
                  LEFT JOIN Usuario u ON p.usuario_id = u.Id";
    
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare($baseQuery . " WHERE p.id = ?");
        $stmt->execute([$id]);
        
        $peticion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($peticion) {
            $relatedData = getPetitionRelatedData($db, $id);
            $peticion = array_merge($peticion, $relatedData);
            http_response_code(200);
            echo json_encode($peticion);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Petición no encontrada."));
        }
    } else {
        try {
            $whereClause = [];
            $params = [];
            
            // ✅ SOLO aplicar filtros básicos (SIN departamento)
            if (isset($_GET['estado']) && !empty($_GET['estado'])) {
                $whereClause[] = "p.estado = :estado";
                $params[':estado'] = $_GET['estado'];
            }
            
            if (isset($_GET['folio']) && !empty($_GET['folio'])) {
                $whereClause[] = "p.folio LIKE :folio";
                $params[':folio'] = '%' . $_GET['folio'] . '%';
            }
            
            if (isset($_GET['nombre']) && !empty($_GET['nombre'])) {
                $whereClause[] = "p.nombre LIKE :nombre";
                $params[':nombre'] = '%' . $_GET['nombre'] . '%';
            }
            
            if (isset($_GET['nivelImportancia']) && !empty($_GET['nivelImportancia'])) {
                $whereClause[] = "p.NivelImportancia = :nivelImportancia";
                $params[':nivelImportancia'] = intval($_GET['nivelImportancia']);
            }
            
            $query = $baseQuery;
            
            if (!empty($whereClause)) {
                $query .= " WHERE " . implode(" AND ", $whereClause);
            }
            
            $query .= " ORDER BY p.fecha_registro DESC";
            
            $stmt = $db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ✅ CRÍTICO: Cargar departamentos para TODAS las peticiones SIEMPRE
            $peticiones_arr = array("records" => []);
            
            foreach ($peticiones as $row) {
                // ✅ SIEMPRE cargar datos relacionados
                $relatedData = getPetitionRelatedData($db, $row['id']);
                $peticion_item = array_merge($row, $relatedData);
                
                // ✅ SOLO filtrar por departamento si el filtro está activo
                if (isset($_GET['departamento']) && !empty($_GET['departamento'])) {
                    $deptId = intval($_GET['departamento']);
                    $tieneDepartamento = false;
                    
                    if (!empty($peticion_item['departamentos'])) {
                        foreach ($peticion_item['departamentos'] as $dept) {
                            if (intval($dept['departamento_id']) === $deptId) {
                                $tieneDepartamento = true;
                                break;
                            }
                        }
                    }
                    
                    // Solo agregar si tiene el departamento filtrado
                    if ($tieneDepartamento) {
                        array_push($peticiones_arr["records"], $peticion_item);
                    }
                } else {
                    // ✅ SIN FILTRO: Agregar TODAS las peticiones CON sus departamentos
                    array_push($peticiones_arr["records"], $peticion_item);
                }
            }
            
            http_response_code(200);
            echo json_encode($peticiones_arr);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(array("message" => $e->getMessage()));
        }
    }
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Verificar si es una acción de seguimiento
    if (isset($data->accion) && $data->accion === 'seguimiento') {
        if (empty($data->peticion_id) || empty($data->usuario_id)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Se requiere peticion_id y usuario_id"
            ]);
            exit;
        }
        
        try {
            // ✅ MEJORADO: Obtener información del usuario que se está asignando
            $userQuery = "SELECT 
                            Id, Nombre, ApellidoP, ApellidoM,
                            CONCAT(
                                Nombre, 
                                CASE 
                                    WHEN ApellidoP IS NOT NULL AND ApellidoP != '' THEN CONCAT(' ', ApellidoP) 
                                    ELSE '' 
                                END,
                                CASE 
                                    WHEN ApellidoM IS NOT NULL AND ApellidoM != '' THEN CONCAT(' ', ApellidoM) 
                                    ELSE '' 
                                END
                            ) as nombre_completo
                          FROM Usuario 
                          WHERE Id = ?";
            
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute([$data->usuario_id]);
            $usuario = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Usuario no encontrado"
                ]);
                exit;
            }
            
            // Actualizar el usuario de seguimiento
            $query = "UPDATE peticiones SET usuario_id = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$data->usuario_id, $data->peticion_id])) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Seguimiento asignado correctamente",
                    "usuario_asignado" => $usuario['nombre_completo'],
                    "nombre_completo" => $usuario['nombre_completo']
                ]);
            } else {
                throw new Exception("No se pudo asignar el seguimiento");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error: " . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ✅ Validación simplificada - solo campos obligatorios que existen
    $requiredFields = ['nombre', 'telefono', 'direccion', 'localidad', 'descripcion', 'NivelImportancia'];
    $camposFaltantes = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data->$field) || empty(trim($data->$field))) {
            $camposFaltantes[] = $field;
        }
    }
    
    if (!empty($camposFaltantes)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Campos requeridos faltantes: " . implode(', ', $camposFaltantes)
        ]);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        $folio = generateSequentialFolio($db);
        
        $nombre = trim($data->nombre);
        $telefono = trim($data->telefono);
        $direccion = trim($data->direccion);
        $localidad = trim($data->localidad);
        $descripcion = trim($data->descripcion);
        $red_social = isset($data->red_social) && !empty(trim($data->red_social)) ? trim($data->red_social) : null;
        $nivelImportancia = max(1, min(4, intval($data->NivelImportancia)));
        
        $query = "INSERT INTO peticiones 
                 (folio, nombre, telefono, direccion, localidad, descripcion, 
                  red_social, estado, NivelImportancia, division_id, usuario_id) 
                 VALUES 
                 (?, ?, ?, ?, ?, ?, ?, 'Sin revisar', ?, 1, NULL)";
        
        $stmt = $db->prepare($query);
        
        $success = $stmt->execute([
            $folio,
            $nombre,
            $telefono,
            $direccion,
            $localidad,
            $descripcion,
            $red_social,
            $nivelImportancia
        ]);
        
        if (!$success) {
            throw new Exception("Error al insertar: " . implode(" ", $stmt->errorInfo()));
        }
        
        $peticion_id = $db->lastInsertId();
        $sugerencias_guardadas = 0;
        
        // ✅ Guardar sugerencias de IA si existen
        if (isset($data->sugerencias_ia) && is_array($data->sugerencias_ia)) {
            $sugQuery = "INSERT IGNORE INTO peticion_sugerencias 
                        (peticion_id, departamento_nombre, estado_sugerencia) 
                        VALUES (?, ?, ?)";
            
            $sugStmt = $db->prepare($sugQuery);
            
            foreach ($data->sugerencias_ia as $sugerencia) {
                if (isset($sugerencia->dependencia) && !empty($sugerencia->dependencia)) {
                    if ($sugStmt->execute([$peticion_id, $sugerencia->dependencia, 'Pendiente'])) {
                        $sugerencias_guardadas++;
                    }
                }
            }
        }
        
        // ✅ Marcar sugerencia seleccionada como Aceptada
        if (isset($data->clasificacion_seleccionada) && 
            isset($data->clasificacion_seleccionada->dependencia)) {
            
            $updateQuery = "UPDATE peticion_sugerencias 
                           SET estado_sugerencia = 'Aceptada' 
                           WHERE peticion_id = ? AND departamento_nombre = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
                $peticion_id,
                $data->clasificacion_seleccionada->dependencia
            ]);
        }

        $db->commit();
        
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Petición creada con éxito",
            "id" => $peticion_id,
            "folio" => $folio,
            "sugerencias_guardadas" => $sugerencias_guardadas
        ]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error interno: " . $e->getMessage()
        ]);
    }
}
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->id)) {
        http_response_code(400);
        echo json_encode(array("message" => "ID no proporcionado."));
        exit;
    }
    
    try {
        $updateFields = [];
        $params = [':id' => $data->id];
        
        $fieldMap = [
            'folio' => 'folio',
            'nombre' => 'nombre', 
            'telefono' => 'telefono',
            'direccion' => 'direccion',
            'localidad' => 'localidad',
            'descripcion' => 'descripcion',
            'red_social' => 'red_social',
            'estado' => 'estado',
            'usuario_id' => 'usuario_id'
        ];
        
        foreach ($fieldMap as $dataKey => $dbField) {
            if (isset($data->$dataKey)) {
                $updateFields[] = "$dbField = :$dataKey";
                $params[":$dataKey"] = $data->$dataKey ? htmlspecialchars(strip_tags($data->$dataKey)) : null;
            }
        }
        
        if (isset($data->NivelImportancia)) {
            $nivel = max(1, min(5, intval($data->NivelImportancia)));
            $updateFields[] = "NivelImportancia = :nivelImportancia";
            $params[':nivelImportancia'] = $nivel;
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(array("message" => "No hay campos para actualizar."));
            exit;
        }
        
        $query = "UPDATE peticiones SET " . implode(", ", $updateFields) . " WHERE id = :id";
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
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
}
elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->id)) {
        http_response_code(400);
        echo json_encode(array("message" => "ID no proporcionado."));
        exit;
    }
    
    try {
        $query = "DELETE FROM peticiones WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data->id);
        
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
} 
else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido."));
}
?>