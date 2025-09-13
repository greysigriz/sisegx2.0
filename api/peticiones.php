<?php
// C:\xampp\htdocs\SISE\api\peticiones.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

// Función para generar folio secuencial
function generateSequentialFolio($db) {
    error_log("🔄 Generando nuevo folio secuencial...");
    
    // Obtener el último folio
    $query = "SELECT folio FROM peticiones WHERE folio LIKE 'FOLIO-%' ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $lastFolio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("📝 Último folio encontrado: " . ($lastFolio ? $lastFolio['folio'] : 'Ninguno'));
    
    if ($lastFolio && $lastFolio['folio']) {
        preg_match('/FOLIO-(\d+)/', $lastFolio['folio'], $matches);
        $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }
    
    $newFolio = 'FOLIO-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    error_log("✨ Nuevo folio generado: " . $newFolio);
    
    return $newFolio;
}

// Función para construir consulta con filtros
function buildQuery($baseQuery, $filters) {
    $whereClause = [];
    $params = [];
    
    $filterMap = [
        'estado' => 'p.estado = :estado',
        'departamento' => 'EXISTS (SELECT 1 FROM peticion_departamento pd WHERE pd.peticion_id = p.id AND pd.departamento_id = :departamento)',
        'folio' => 'p.folio LIKE :folio',
        'nombre' => 'p.nombre LIKE :nombre',
        'nivelImportancia' => 'p.NivelImportancia = :nivelImportancia',
        'usuario_seguimiento' => 'p.usuario_id = :usuario_seguimiento'
    ];
    
    foreach ($filterMap as $key => $condition) {
        if (isset($filters[$key]) && !empty($filters[$key])) {
            $whereClause[] = $condition;
            $paramKey = ':' . $key;
            $params[$paramKey] = in_array($key, ['folio', 'nombre']) 
                ? '%' . $filters[$key] . '%' 
                : (in_array($key, ['nivelImportancia', 'departamento', 'usuario_seguimiento']) 
                    ? intval($filters[$key]) 
                    : $filters[$key]);
        }
    }
    
    // Filtro especial para mis peticiones
    if (isset($filters['mis_peticiones']) && $filters['mis_peticiones'] === 'true') {
        if (!isset($filters['current_user_id']) || empty($filters['current_user_id'])) {
            throw new Exception("ID de usuario no proporcionado para filtrar 'mis peticiones'.");
        }
        $whereClause[] = 'p.usuario_id = :current_user_id';
        $params[':current_user_id'] = intval($filters['current_user_id']);
    }
    
    if (!empty($whereClause)) {
        $baseQuery .= " WHERE " . implode(" AND ", $whereClause);
    }
    
    return [$baseQuery . " ORDER BY p.fecha_registro DESC", $params];
}

// Función para obtener datos relacionados de una petición
function getPetitionRelatedData($db, $petitionId) {
    // Obtener departamentos asignados
    $depQuery = "SELECT pd.id as asignacion_id, pd.departamento_id, pd.estado as estado_departamento, 
                       pd.fecha_asignacion, u.nombre_unidad
                FROM peticion_departamento pd
                LEFT JOIN unidades u ON pd.departamento_id = u.id
                WHERE pd.peticion_id = :peticion_id
                ORDER BY pd.fecha_asignacion DESC";
    
    $depStmt = $db->prepare($depQuery);
    $depStmt->bindParam(':peticion_id', $petitionId);
    $depStmt->execute();
    $departamentos = $depStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener sugerencias de IA
    $sugQuery = "SELECT id, departamento_nombre, estado_sugerencia, fecha_sugerencia
                FROM peticion_sugerencias
                WHERE peticion_id = :peticion_id
                ORDER BY fecha_sugerencia ASC";
    
    $sugStmt = $db->prepare($sugQuery);
    $sugStmt->bindParam(':peticion_id', $petitionId);
    $sugStmt->execute();
    $sugerencias = $sugStmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['departamentos' => $departamentos, 'sugerencias_ia' => $sugerencias];
}

if ($method === 'GET') {
    $baseQuery = "SELECT p.*, 
                         us.Usuario as nombre_usuario_seguimiento,
                         CONCAT(us.Nombre, ' ', us.ApellidoP, ' ', IFNULL(us.ApellidoM, '')) as nombre_completo_usuario,
                         da.Nombre as nombre_division
                  FROM peticiones p 
                  LEFT JOIN Usuario us ON p.usuario_id = us.Id
                  LEFT JOIN DivisionAdministrativa da ON p.division_id = da.Id";
    
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare($baseQuery . " WHERE p.id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
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
            list($query, $params) = buildQuery($baseQuery, $_GET);
            $stmt = $db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $peticiones_arr = array("records" => []);
            
            foreach ($peticiones as $row) {
                $relatedData = getPetitionRelatedData($db, $row['id']);
                $peticion_item = array_merge($row, $relatedData);
                array_push($peticiones_arr["records"], $peticion_item);
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
    
    // Debug log
    error_log("📥 Datos recibidos: " . print_r($data, true));
    
    // Acción de seguimiento
    if (isset($data->accion) && $data->accion === 'seguimiento') {
        if (empty($data->peticion_id) || empty($data->usuario_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos para el seguimiento."));
            exit;
        }
        
        try {
            // Verificar existencia de petición y usuario
            $checks = [
                ["SELECT id FROM peticiones WHERE id = :id", $data->peticion_id, "Petición no encontrada."],
                ["SELECT Id FROM Usuario WHERE Id = :id", $data->usuario_id, "Usuario no encontrado."]
            ];
            
            foreach ($checks as [$query, $id, $message]) {
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() === 0) {
                    http_response_code(404);
                    echo json_encode(array("message" => $message));
                    exit;
                }
            }
            
            // Actualizar seguimiento
            $update_query = "UPDATE peticiones SET usuario_id = :usuario_id WHERE id = :peticion_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':usuario_id', $data->usuario_id);
            $update_stmt->bindParam(':peticion_id', $data->peticion_id);
            
            if ($update_stmt->execute()) {
                // Obtener info del usuario
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
    }
    // Crear nueva petición - VERSIÓN FLEXIBLE
    else {
        // ESTRATEGIA 1: Solo validar campos mínimos esenciales
        $criticalFields = [
            'descripcion' => 'Descripción' // Solo descripción es realmente crítica
        ];
        
        // Validar solo campos críticos
        $missingCriticalFields = [];
        foreach ($criticalFields as $field => $label) {
            if (!isset($data->$field) || (is_string($data->$field) && trim($data->$field) === '')) {
                $missingCriticalFields[] = $label;
                error_log("❌ Campo crítico faltante: {$label}");
            }
        }
        
        if (!empty($missingCriticalFields)) {
            error_log("❌ Validación fallida - campos críticos faltantes: " . implode(', ', $missingCriticalFields));
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Campos críticos requeridos faltantes: " . implode(', ', $missingCriticalFields),
                "missing_fields" => $missingCriticalFields
            ]);
            exit;
        }

        error_log("✅ Validación exitosa - procediendo a crear petición con campos flexibles");
        
        try {
            $db->beginTransaction();
            
            // Generar folio
            $folio = generateSequentialFolio($db);
            error_log("🎫 Folio generado: " . $folio);
            
            // Verificar folio único
            $check_stmt = $db->prepare("SELECT id FROM peticiones WHERE folio = ?");
            $check_stmt->execute([$folio]);
            if ($check_stmt->rowCount() > 0) {
                error_log("⚠️ Error: Folio duplicado detectado: " . $folio);
                throw new Exception("Error: Folio duplicado.");
            }
            
            // ESTRATEGIA 2: Valores por defecto para campos faltantes
            $defaultValues = [
                'nombre' => 'Sin especificar',
                'telefono' => 'Sin especificar',
                'direccion' => 'Sin especificar',
                'localidad' => 'Sin especificar',
                'red_social' => null,
                'estado' => 'Sin revisar',
                'NivelImportancia' => 3, // Importancia media por defecto
                'division_id' => null,
                'usuario_id' => null
            ];
            
            // Insertar petición principal con valores flexibles
            $query = "INSERT INTO peticiones 
                     (folio, nombre, telefono, direccion, localidad, descripcion, 
                      red_social, estado, NivelImportancia, division_id, usuario_id) 
                     VALUES 
                     (:folio, :nombre, :telefono, :direccion, :localidad, :descripcion,
                      :red_social, :estado, :nivelImportancia, :division_id, :usuario_id)";
            
            $stmt = $db->prepare($query);
            
            // ESTRATEGIA 3: Función para obtener valor con fallback
            function getValueOrDefault($data, $field, $defaultValue) {
                if (isset($data->$field)) {
                    $value = $data->$field;
                    // Si es string y está vacío, usar default
                    if (is_string($value) && trim($value) === '') {
                        return $defaultValue;
                    }
                    return $value;
                }
                return $defaultValue;
            }
            
            // Preparar datos con valores por defecto
            $petitionData = [
                ':folio' => $folio,
                ':nombre' => htmlspecialchars(strip_tags(getValueOrDefault($data, 'nombre', $defaultValues['nombre']))),
                ':telefono' => htmlspecialchars(strip_tags(getValueOrDefault($data, 'telefono', $defaultValues['telefono']))),
                ':direccion' => htmlspecialchars(strip_tags(getValueOrDefault($data, 'direccion', $defaultValues['direccion']))),
                ':localidad' => htmlspecialchars(strip_tags(getValueOrDefault($data, 'localidad', $defaultValues['localidad']))),
                ':descripcion' => htmlspecialchars(strip_tags($data->descripcion)), // Este es obligatorio
                ':red_social' => getValueOrDefault($data, 'red_social', $defaultValues['red_social']),
                ':estado' => $defaultValues['estado'],
                ':nivelImportancia' => isset($data->NivelImportancia) && is_numeric($data->NivelImportancia) 
                    ? max(1, min(5, intval($data->NivelImportancia))) 
                    : $defaultValues['NivelImportancia'],
                ':division_id' => isset($data->division_id) && !empty($data->division_id) ? intval($data->division_id) : $defaultValues['division_id'],
                ':usuario_id' => isset($data->usuario_id) && !empty($data->usuario_id) ? intval($data->usuario_id) : $defaultValues['usuario_id']
            ];
            
            // Log de valores finales
            error_log("📋 Datos finales para inserción: " . print_r($petitionData, true));
            
            // Bind y ejecutar
            foreach ($petitionData as $key => $value) {
                $stmt->bindValue($key, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error al crear la petición: " . implode(" ", $stmt->errorInfo()));
            }
            
            $peticion_id = $db->lastInsertId();
            
            // Guardar sugerencia seleccionada si existe
            $sugerencias_guardadas = 0;
            if (isset($data->clasificacion_seleccionada) && 
                isset($data->clasificacion_seleccionada->dependencia)) {
                
                $sugQuery = "INSERT INTO peticion_sugerencias 
                            (peticion_id, departamento_nombre, estado_sugerencia) 
                            VALUES (:peticion_id, :departamento_nombre, 'Aceptada')";
                
                $sugStmt = $db->prepare($sugQuery);
                if ($sugStmt->execute([
                    ':peticion_id' => $peticion_id,
                    ':departamento_nombre' => htmlspecialchars(strip_tags($data->clasificacion_seleccionada->dependencia))
                ])) {
                    $sugerencias_guardadas = 1;
                }
            }

            $db->commit();
            
            // ESTRATEGIA 4: Respuesta informativa sobre campos usados
            $fieldsUsed = [];
            $fieldsDefaulted = [];
            
            foreach ($defaultValues as $field => $defaultVal) {
                if (isset($data->$field) && !empty($data->$field)) {
                    $fieldsUsed[] = $field;
                } else {
                    $fieldsDefaulted[] = $field;
                }
            }
            
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Petición creada con éxito.",
                "id" => $peticion_id,
                "folio" => $folio,
                "sugerencias_guardadas" => $sugerencias_guardadas,
                "info" => [
                    "campos_proporcionados" => array_merge(['descripcion'], $fieldsUsed),
                    "campos_con_valores_por_defecto" => $fieldsDefaulted,
                    "campos_requeridos_futuros" => "Para completar la petición, considere proporcionar: nombre, teléfono, dirección y localidad"
                ]
            ]);
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("❌ Error en peticiones.php: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error al procesar la petición: " . $e->getMessage()
            ]);
        }
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
            'estado' => 'estado'
        ];
        
        foreach ($fieldMap as $dataKey => $dbField) {
            if (isset($data->$dataKey)) {
                $updateFields[] = "$dbField = :$dataKey";
                $params[":$dataKey"] = $data->$dataKey ? htmlspecialchars(strip_tags($data->$dataKey)) : null;
            }
        }
        
        // Campos especiales
        if (isset($data->NivelImportancia)) {
            $nivel = max(1, min(5, intval($data->NivelImportancia)));
            $updateFields[] = "NivelImportancia = :nivelImportancia";
            $params[':nivelImportancia'] = $nivel;
        }
        
        if (isset($data->division_id)) {
            $updateFields[] = "division_id = :division_id";
            $params[':division_id'] = $data->division_id ? intval($data->division_id) : null;
        }
        
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
        
        foreach ($params as $key => $value) {
            if ($value === null) {
                $stmt->bindValue($key, $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($key, $value);
            }
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
        $stmt->bindParam(':id', htmlspecialchars(strip_tags($data->id)));
        
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