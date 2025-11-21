<?php
// C:\xampp\htdocs\SISEE\api\unidades.php
// Encabezados requeridos
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

// Instanciar base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET') {
    try {
        // ✅ CORREGIDO: Query simple para tu estructura de tabla
        $query = "SELECT id, nombre_unidad FROM unidades ORDER BY nombre_unidad";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ✅ Formatear respuesta para compatibilidad
        $unidades_arr = array("records" => []);
        
        foreach ($unidades as $row) {
            $unidad_item = array(
                "id" => intval($row['id']),
                "nombre_unidad" => $row['nombre_unidad'],
                // Valores por defecto para compatibilidad
                "clave" => null,
                "estatus" => "ACTIVA",
                "nivel" => null,
                "tipo_cuenta" => null,
                "periodo" => null,
                "abreviatura" => null,
                "siglas" => null
            );
            
            array_push($unidades_arr["records"], $unidad_item);
        }
        
        http_response_code(200);
        echo json_encode($unidades_arr);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error: " . $e->getMessage(),
            "records" => []
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido."));
}
?>