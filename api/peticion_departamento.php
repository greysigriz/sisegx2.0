<?php
// C:\xampp\htdocs\SISE\api\peticion-departamento.php

// CORS headers - DEBEN IR ANTES DE CUALQUIER OTRA SALIDA
require_once __DIR__ . '/cors.php';


// Manejar solicitud preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Establecer tipo de contenido para respuestas JSON
header("Content-Type: application/json; charset=UTF-8");

// IMPORTANTE: Evitar cualquier salida antes de las cabeceras
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';

try {
    // Instanciar base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Obtener método de solicitud
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Obtener departamentos asignados a una petición específica
        if (isset($_GET['peticion_id'])) {
            $peticion_id = $_GET['peticion_id'];
            
            $query = "SELECT pd.id, pd.peticion_id, pd.departamento_id, pd.fecha_asignacion, pd.estado,
                             u.nombre_unidad, u.clave, u.abreviatura, u.siglas
                      FROM peticion_departamento pd
                      LEFT JOIN unidades u ON pd.departamento_id = u.id
                      WHERE pd.peticion_id = :peticion_id
                      ORDER BY pd.fecha_asignacion DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':peticion_id', $peticion_id);
            $stmt->execute();
            
            $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "departamentos" => $departamentos
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
        // Recibir los datos enviados
        $data = json_decode(file_get_contents("php://input"));
        
        // Asignar departamentos a una petición
        if (isset($data->accion) && $data->accion === 'asignar_departamentos') {
            if (!empty($data->peticion_id) && !empty($data->departamentos) && is_array($data->departamentos)) {
                try {
                    $db->beginTransaction();
                    
                    // Verificar que la petición existe
                    $check_query = "SELECT id FROM peticiones WHERE id = :peticion_id";
                    $check_stmt = $db->prepare($check_query);
                    $check_stmt->bindParam(':peticion_id', $data->peticion_id);
                    $check_stmt->execute();
                    
                    if ($check_stmt->rowCount() === 0) {
                        throw new Exception("Petición no encontrada");
                    }
                    
                    $departamentos_asignados = 0;
                    $departamentos_existentes = [];
                    
                    foreach ($data->departamentos as $departamento_id) {
                        // Verificar si ya existe la asignación
                        $exists_query = "SELECT id FROM peticion_departamento 
                                       WHERE peticion_id = :peticion_id AND departamento_id = :departamento_id";
                        $exists_stmt = $db->prepare($exists_query);
                        $exists_stmt->bindParam(':peticion_id', $data->peticion_id);
                        $exists_stmt->bindParam(':departamento_id', $departamento_id);
                        $exists_stmt->execute();
                        
                        if ($exists_stmt->rowCount() > 0) {
                            // Obtener nombre del departamento
                            $dept_query = "SELECT nombre_unidad FROM unidades WHERE id = :id";
                            $dept_stmt = $db->prepare($dept_query);
                            $dept_stmt->bindParam(':id', $departamento_id);
                            $dept_stmt->execute();
                            $dept_info = $dept_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            $departamentos_existentes[] = $dept_info['nombre_unidad'] ?? "Departamento ID: $departamento_id";
                            continue;
                        }
                        
                        // Insertar nueva asignación
                        $insert_query = "INSERT INTO peticion_departamento (peticion_id, departamento_id, estado) 
                                       VALUES (:peticion_id, :departamento_id, 'Esperando recepción')";
                        $insert_stmt = $db->prepare($insert_query);
                        $insert_stmt->bindParam(':peticion_id', $data->peticion_id);
                        $insert_stmt->bindParam(':departamento_id', $departamento_id);
                        
                        if ($insert_stmt->execute()) {
                            $departamentos_asignados++;
                        }
                    }
                    
                    // Actualizar estado de la petición si se asignaron departamentos
                    if ($departamentos_asignados > 0) {
                        $update_query = "UPDATE peticiones SET estado = 'Esperando recepción' WHERE id = :peticion_id";
                        $update_stmt = $db->prepare($update_query);
                        $update_stmt->bindParam(':peticion_id', $data->peticion_id);
                        $update_stmt->execute();
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
                    http_response_code(500);
                    echo json_encode([
                        "success" => false,
                        "message" => "Error: " . $e->getMessage()
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Datos incompletos. Se requiere peticion_id y departamentos."
                ]);
            }
        }
        // Asignar un solo departamento
        elseif (!empty($data->peticion_id) && !empty($data->departamento_id)) {
            try {
                // Verificar si ya existe la asignación
                $check_query = "SELECT id FROM peticion_departamento 
                               WHERE peticion_id = :peticion_id AND departamento_id = :departamento_id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':peticion_id', $data->peticion_id);
                $check_stmt->bindParam(':departamento_id', $data->departamento_id);
                $check_stmt->execute();
                
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
                         VALUES (:peticion_id, :departamento_id, 'Esperando recepción')";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':peticion_id', $data->peticion_id);
                $stmt->bindParam(':departamento_id', $data->departamento_id);
                
                if ($stmt->execute()) {
                    // Actualizar estado de la petición
                    $update_query = "UPDATE peticiones SET estado = 'Esperando recepción' WHERE id = :peticion_id";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(':peticion_id', $data->peticion_id);
                    $update_stmt->execute();
                    
                    http_response_code(201);
                    echo json_encode([
                        "success" => true,
                        "message" => "Departamento asignado correctamente",
                        "id" => $db->lastInsertId()
                    ]);
                } else {
                    http_response_code(503);
                    echo json_encode([
                        "success" => false,
                        "message" => "No se pudo asignar el departamento"
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Error: " . $e->getMessage()
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Datos incompletos"
            ]);
        }
    }
    elseif ($method === 'PUT') {
        // Actualizar estado de una asignación
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id) && !empty($data->estado)) {
            try {
                $query = "UPDATE peticion_departamento SET estado = :estado WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':estado', $data->estado);
                $stmt->bindParam(':id', $data->id);
                
                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode([
                        "success" => true,
                        "message" => "Estado actualizado correctamente"
                    ]);
                } else {
                    http_response_code(503);
                    echo json_encode([
                        "success" => false,
                        "message" => "No se pudo actualizar el estado"
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Error: " . $e->getMessage()
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Datos incompletos. Se requiere id y estado."
            ]);
        }
    }
    elseif ($method === 'DELETE') {
        // Eliminar asignación de departamento
        $data = json_decode(file_get_contents("php://input"));
        
        if (isset($data->accion) && $data->accion === 'eliminar_multiples') {
            // Eliminar múltiples asignaciones
            if (!empty($data->ids) && is_array($data->ids)) {
                try {
                    $db->beginTransaction();
                    
                    $eliminados = 0;
                    foreach ($data->ids as $id) {
                        $query = "DELETE FROM peticion_departamento WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $id);
                        
                        if ($stmt->execute()) {
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
                    http_response_code(500);
                    echo json_encode([
                        "success" => false,
                        "message" => "Error: " . $e->getMessage()
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Se requiere un array de IDs"
                ]);
            }
        }
        // Eliminar una sola asignación
        elseif (!empty($data->id)) {
            try {
                $query = "DELETE FROM peticion_departamento WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data->id);
                
                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode([
                        "success" => true,
                        "message" => "Asignación eliminada correctamente"
                    ]);
                } else {
                    http_response_code(503);
                    echo json_encode([
                        "success" => false,
                        "message" => "No se pudo eliminar la asignación"
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Error: " . $e->getMessage()
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID requerido"
            ]);
        }
    }
    else {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Método no permitido"
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error del servidor: " . $e->getMessage()
    ]);
}
?>