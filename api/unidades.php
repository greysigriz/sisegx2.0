<?php
// C:\xampp\htdocs\SISE\api\unidades.php
// Encabezados requeridos
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET') {
    // Construir la consulta base
    $query = "SELECT id, clave, nombre_unidad, estatus, nivel, tipo_cuenta, periodo, abreviatura, siglas 
              FROM unidades";
    
    $whereClause = [];
    $params = [];
    
    // Filtrar por estatus si se especifica
    if (isset($_GET['estatus']) && !empty($_GET['estatus'])) {
        $whereClause[] = "estatus = :estatus";
        $params[':estatus'] = $_GET['estatus'];
    }
    
    // Filtrar solo departamentos activos si se especifica
    if (isset($_GET['activos']) && $_GET['activos'] === 'true') {
        $whereClause[] = "estatus = 'ACTIVA'";
    }
    
    // Agregar cláusula WHERE si hay filtros
    if (!empty($whereClause)) {
        $query .= " WHERE " . implode(" AND ", $whereClause);
    }
    
    $query .= " ORDER BY nombre_unidad";
    
    $stmt = $db->prepare($query);
    
    // Vincular parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $unidades_arr = array();
        $unidades_arr["records"] = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $unidad_item = array(
                "id" => $id,
                "clave" => $clave,
                "nombre_unidad" => $nombre_unidad,
                "estatus" => $estatus,
                "nivel" => $nivel,
                "tipo_cuenta" => $tipo_cuenta,
                "periodo" => $periodo,
                "abreviatura" => $abreviatura,
                "siglas" => $siglas
            );
            
            array_push($unidades_arr["records"], $unidad_item);
        }
        
        http_response_code(200);
        echo json_encode($unidades_arr);
    } else {
        http_response_code(200);
        echo json_encode(array("records" => array()));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido."));
}
?>