<?php
// C:\xampp\htdocs\SISEE\api\divisiones.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            // Obtener una división específica
            $query = "SELECT Id, Municipio as Nombre, Pais, Estado, CodigoPostal, Cabecera, FechaCreacion 
                      FROM DivisionAdministrativa 
                      WHERE Id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_GET['id']]);
            
            $division = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($division) {
                http_response_code(200);
                echo json_encode($division);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "División no encontrada."]);
            }
        } else {
            // Obtener todas las divisiones
            $query = "SELECT Id, Municipio as Nombre, Pais, Estado, CodigoPostal, Cabecera, FechaCreacion 
                      FROM DivisionAdministrativa 
                      ORDER BY Municipio ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $divisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                "records" => $divisiones,
                "count" => count($divisiones)
            ]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->Municipio)) {
            $query = "INSERT INTO DivisionAdministrativa (Municipio, Pais, Estado, CodigoPostal, Cabecera) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([
                $data->Municipio,
                $data->Pais ?? 'México',
                $data->Estado ?? 'Yucatán',
                $data->CodigoPostal ?? null,
                $data->Cabecera ?? null
            ])) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "División creada.",
                    "id" => $db->lastInsertId()
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["success" => false, "message" => "No se pudo crear la división."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Datos incompletos."]);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->Id)) {
            $query = "UPDATE DivisionAdministrativa 
                      SET Municipio = ?, Pais = ?, Estado = ?, CodigoPostal = ?, Cabecera = ?
                      WHERE Id = ?";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([
                $data->Municipio ?? $data->Nombre,
                $data->Pais ?? 'México',
                $data->Estado ?? 'Yucatán',
                $data->CodigoPostal ?? null,
                $data->Cabecera ?? null,
                $data->Id
            ])) {
                http_response_code(200);
                echo json_encode(["success" => true, "message" => "División actualizada."]);
            } else {
                http_response_code(503);
                echo json_encode(["success" => false, "message" => "No se pudo actualizar."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Falta ID."]);
        }
        break;
        
    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->Id)) {
            $query = "DELETE FROM DivisionAdministrativa WHERE Id = ?";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$data->Id])) {
                http_response_code(200);
                echo json_encode(["success" => true, "message" => "División eliminada."]);
            } else {
                http_response_code(503);
                echo json_encode(["success" => false, "message" => "No se pudo eliminar."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Falta ID."]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido."]);
        break;
}
?>