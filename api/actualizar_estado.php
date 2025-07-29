<?php
// Habilitar CORS para cualquier origen (solo para desarrollo)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejo para preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// api/actualizar_estado.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Solo aceptar método PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405); // Método no permitido
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Leer cuerpo de la petición
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->estado)) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Faltan datos (id o estado)']);
    exit;
}

try {
    $query = "UPDATE peticiones SET estado = :estado WHERE id = :id";
    $stmt = $db->prepare($query);

    $stmt->bindValue(':estado', htmlspecialchars(strip_tags($data->estado)));
    $stmt->bindValue(':id', intval($data->id), PDO::PARAM_INT);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['message' => 'Estado actualizado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'No se pudo actualizar el estado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
}
