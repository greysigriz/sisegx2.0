<?php
// C:\xampp\htdocs\SISEE\api\division.php

// Include CORS headers first
require_once __DIR__ . '/cors.php';

// Include database connection
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];

    // Only allow GET requests for this endpoint
    if($method !== 'GET') {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Método no permitido. Solo se permite GET."
        ]);
        exit;
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get division ID from query parameter
    $division_id = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if ($division_id && $division_id > 0) {
        // Get specific division
        $query = "SELECT Id, Municipio, Pais, Estado, CodigoPostal, Cabecera, FechaCreacion 
                  FROM DivisionAdministrativa 
                  WHERE Id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $division_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $division = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($division) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "division" => $division
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "División administrativa no encontrada"
            ]);
        }
    } else {
        // Get all divisions (municipios)
        $query = "SELECT Id, Municipio, Pais, Estado, CodigoPostal, Cabecera, FechaCreacion 
                  FROM DivisionAdministrativa 
                  ORDER BY Municipio ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "divisions" => $divisions,
            "count" => count($divisions)
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error de base de datos: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error del servidor: " . $e->getMessage()
    ]);
}
?>