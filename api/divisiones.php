<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

//C:\xampp\htdocs\SISE\api\divisiones.php
// Instanciar base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET') {
    // Consulta para obtener todas las divisiones administrativas
    $query = "SELECT Id, Nombre, Pais, Region, Ciudad FROM DivisionAdministrativa ORDER BY Nombre";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $divisiones_arr = array();
        $divisiones_arr["records"] = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $division_item = array(
                "Id" => $Id,
                "Nombre" => $Nombre,
                "Pais" => $Pais,
                "Region" => $Region,
                "Ciudad" => $Ciudad
            );
            
            array_push($divisiones_arr["records"], $division_item);
        }
        
        http_response_code(200);
        echo json_encode($divisiones_arr);
    } else {
        http_response_code(200);
        echo json_encode(array("records" => array()));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido."));
}
?>