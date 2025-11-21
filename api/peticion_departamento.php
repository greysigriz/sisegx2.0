<?php
// C:\xampp\htdocs\SISEE\api\peticion_departamento.php

require_once __DIR__ . '/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

// ✅ Solo logging de errores críticos
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['peticion_id'])) {
            $peticion_id = intval($_GET['peticion_id']);
            
            if ($peticion_id <= 0) {
                throw new Exception("ID de petición inválido");
            }
            
            $departamentos_asignados = [];
            $sugerencias = [];
            
            // 1. ✅ CORREGIDO: Query para departamentos con estructura simple
            try {
                $query_dept = "SELECT 
                                pd.id, 
                                pd.peticion_id, 
                                pd.departamento_id as id_unidad,
                                pd.fecha_asignacion as fecha, 
                                pd.estado,
                                u.nombre_unidad
                              FROM peticion_departamento pd
                              LEFT JOIN unidades u ON pd.departamento_id = u.id
                              WHERE pd.peticion_id = ?
                              ORDER BY pd.fecha_asignacion DESC";
                
                $stmt_dept = $db->prepare($query_dept);
                $stmt_dept->execute([$peticion_id]);
                $departamentos_asignados = $stmt_dept->fetchAll(PDO::FETCH_ASSOC);
                
                // Agregar campos faltantes para compatibilidad
                foreach ($departamentos_asignados as &$dept) {
                    $dept['clave'] = null;
                    $dept['abreviatura'] = null;
                    $dept['siglas'] = null;
                }
                
            } catch (Exception $e) {
                error_log("Error en query departamentos: " . $e->getMessage());
            }
            
            // 2. ✅ CORREGIDO: Query para sugerencias
            try {
                $query_sug = "SELECT 
                                ps.id,
                                ps.peticion_id,
                                ps.fecha_sugerencia as fecha,
                                ps.estado_sugerencia as estado,
                                ps.departamento_nombre
                              FROM peticion_sugerencias ps
                              WHERE ps.peticion_id = ?
                              ORDER BY ps.fecha_sugerencia DESC";
                
                $stmt_sug = $db->prepare($query_sug);
                $stmt_sug->execute([$peticion_id]);
                $sugerencias = $stmt_sug->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                error_log("Error en query sugerencias: " . $e->getMessage());
            }
            
            // ✅ Respuesta exitosa
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "departamentos" => $departamentos_asignados,
                "sugerencias" => $sugerencias,
                "peticion_id" => $peticion_id,
                "debug" => [
                    "departamentos_count" => count($departamentos_asignados),
                    "sugerencias_count" => count($sugerencias)
                ]
            ]);
            
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de petición requerido"
            ]);
        }
    }
    elseif ($method === 'POST') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido: " . json_last_error_msg());
        }
        
        if (isset($data['accion']) && $data['accion'] === 'asignar_departamentos') {
            if (empty($data['peticion_id']) || empty($data['departamentos']) || !is_array($data['departamentos'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Datos incompletos. Se requiere peticion_id y departamentos array."
                ]);
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                // Verificar que la petición existe
                $check_query = "SELECT id FROM peticiones WHERE id = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$data['peticion_id']]);
                
                if ($check_stmt->rowCount() === 0) {
                    throw new Exception("Petición no encontrada");
                }
                
                $departamentos_asignados = 0;
                $departamentos_existentes = [];
                
                foreach ($data['departamentos'] as $departamento_id) {
                    $departamento_id = intval($departamento_id);
                    
                    // Verificar si ya existe la asignación
                    $exists_query = "SELECT id FROM peticion_departamento 
                                   WHERE peticion_id = ? AND departamento_id = ?";
                    $exists_stmt = $db->prepare($exists_query);
                    $exists_stmt->execute([$data['peticion_id'], $departamento_id]);
                    
                    if ($exists_stmt->rowCount() > 0) {
                        // ✅ CORREGIDO: Query simple para obtener nombre
                        $dept_query = "SELECT nombre_unidad FROM unidades WHERE id = ?";
                        $dept_stmt = $db->prepare($dept_query);
                        $dept_stmt->execute([$departamento_id]);
                        $dept_info = $dept_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $departamentos_existentes[] = $dept_info['nombre_unidad'] ?? "Departamento ID: $departamento_id";
                        continue;
                    }
                    
                    // Insertar nueva asignación
                    $insert_query = "INSERT INTO peticion_departamento (peticion_id, departamento_id, estado) 
                                   VALUES (?, ?, 'Esperando recepción')";
                    $insert_stmt = $db->prepare($insert_query);
                    
                    if ($insert_stmt->execute([$data['peticion_id'], $departamento_id])) {
                        $departamentos_asignados++;
                    }
                }
                
                // Actualizar estado de la petición si se asignaron departamentos
                if ($departamentos_asignados > 0) {
                    $update_query = "UPDATE peticiones SET estado = 'Esperando recepción' WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->execute([$data['peticion_id']]);
                }
                
                $db->commit();
                
                $mensaje = "Departamentos asignados correctamente: $departamentos_asignados";
                if (!empty($departamentos_existentes)) {
                    $mensaje .= ". Ya estaban asignados: " . implode(', ', $departamentos_existentes);
                }
                
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => $mensaje,
                    "asignados" => $departamentos_asignados,
                    "existentes" => $departamentos_existentes
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
        // Asignar un solo departamento
        elseif (!empty($data['peticion_id']) && !empty($data['departamento_id'])) {
            try {
                $peticion_id = intval($data['peticion_id']);
                $departamento_id = intval($data['departamento_id']);
                
                // Verificar si ya existe la asignación
                $check_query = "SELECT id FROM peticion_departamento 
                               WHERE peticion_id = ? AND departamento_id = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$peticion_id, $departamento_id]);
                
                if ($check_stmt->rowCount() > 0) {
                    http_response_code(409);
                    echo json_encode([
                        "success" => false,
                        "message" => "El departamento ya está asignado a esta petición"
                    ]);
                    exit;
                }
                
                // Insertar nueva asignación
                $query = "INSERT INTO peticion_departamento (peticion_id, departamento_id, estado) 
                         VALUES (?, ?, 'Esperando recepción')";
                
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$peticion_id, $departamento_id])) {
                    // Actualizar estado de la petición
                    $update_query = "UPDATE peticiones SET estado = 'Esperando recepción' WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->execute([$peticion_id]);
                    
                    http_response_code(201);
                    echo json_encode([
                        "success" => true,
                        "message" => "Departamento asignado correctamente",
                        "id" => $db->lastInsertId()
                    ]);
                } else {
                    throw new Exception("No se pudo insertar la asignación");
                }
                
            } catch (Exception $e) {
                throw $e;
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Datos incompletos para POST"
            ]);
        }
    }
    elseif ($method === 'PUT') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido: " . json_last_error_msg());
        }
        
        if (empty($data['id']) || empty($data['estado'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Datos incompletos. Se requiere id y estado."
            ]);
            exit;
        }
        
        try {
            $query = "UPDATE peticion_departamento SET estado = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$data['estado'], $data['id']])) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Estado actualizado correctamente"
                ]);
            } else {
                throw new Exception("No se pudo actualizar el estado");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    elseif ($method === 'DELETE') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido: " . json_last_error_msg());
        }
        
        if (isset($data['accion']) && $data['accion'] === 'eliminar_multiples') {
            if (empty($data['ids']) || !is_array($data['ids'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Se requiere un array de IDs"
                ]);
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                $eliminados = 0;
                foreach ($data['ids'] as $id) {
                    $query = "DELETE FROM peticion_departamento WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([intval($id)])) {
                        $eliminados++;
                    }
                }
                
                $db->commit();
                
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Asignaciones eliminadas: $eliminados",
                    "eliminados" => $eliminados
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
        elseif (!empty($data['id'])) {
            try {
                $query = "DELETE FROM peticion_departamento WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([intval($data['id'])])) {
                    http_response_code(200);
                    echo json_encode([
                        "success" => true,
                        "message" => "Asignación eliminada correctamente"
                    ]);
                } else {
                    throw new Exception("No se pudo eliminar la asignación");
                }
            } catch (Exception $e) {
                throw $e;
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID requerido para DELETE"
            ]);
        }
    }
    else {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Método no permitido: $method"
        ]);
    }

} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error del servidor: " . $e->getMessage()
    ]);
}
?>