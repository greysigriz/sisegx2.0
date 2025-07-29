<?php
// C:\xampp\htdocs\SISE\api\division.php

// Include CORS headers first
require_once __DIR__ . '/cors.php';

// Start session
session_start();

// Include database connection
require_once __DIR__ . '/../config/database.php';

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

    // Get division ID from URL or query parameter
    $division_id = null;
    
    // Check for ID in query parameters first
    if (isset($_GET['id'])) {
        $division_id = intval($_GET['id']);
    } else {
        // Parse URL path for ID (e.g., /api/division/1)
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($request_uri, PHP_URL_PATH);
        
        // Remove base path to isolate the ID
        $base_patterns = ['/SISE/api/division', '/api/division'];
        foreach ($base_patterns as $pattern) {
            if (strpos($path, $pattern) === 0) {
                $remaining_path = substr($path, strlen($pattern));
                if (preg_match('/^\/(\d+)$/', $remaining_path, $matches)) {
                    $division_id = intval($matches[1]);
                }
                break;
            }
        }
    }
    
    if ($division_id && $division_id > 0) {
        // Get specific division
        $query = "SELECT Id, Nombre, Pais, Region, Ciudad, FechaCreacion 
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
        // Get all divisions
        $query = "SELECT Id, Nombre, Pais, Region, Ciudad, FechaCreacion 
                  FROM DivisionAdministrativa 
                  ORDER BY Nombre ASC";
        
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