<?php
// C:\xampp\htdocs\SISEE\api\estado_peticion.php

require_once __DIR__ . '/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/services/EstadoService.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $estadoService = new EstadoService($db);

    if ($method === 'GET') {
        // Obtener información completa del estado de una petición
        if (isset($_GET['peticion_id'])) {
            $peticion_id = intval($_GET['peticion_id']);
            
            if ($peticion_id <= 0) {
                throw new Exception("ID de petición inválido");
            }
            
            $estadoCompleto = $estadoService->getEstadoCompleto($peticion_id);
            
            if ($estadoCompleto) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "peticion_id" => $peticion_id,
                    "estado" => $estadoCompleto
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Petición no encontrada"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de petición requerido"
            ]);
        }
    }
    elseif ($method === 'POST') {
        // Forzar actualización del estado automático de una petición
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido: " . json_last_error_msg());
        }
        
        if (empty($data['peticion_id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de petición requerido"
            ]);
            exit;
        }
        
        $resultado = $estadoService->actualizarEstadoAutomatico($data['peticion_id']);
        
        http_response_code(200);
        echo json_encode([
            "success" => $resultado['success'],
            "peticion_id" => $data['peticion_id'],
            "resultado" => $resultado
        ]);
    }
    else {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Método no permitido: $method"
        ]);
    }

} catch (Exception $e) {
    error_log("Error en estado_peticion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error del servidor: " . $e->getMessage()
    ]);
}
?>
