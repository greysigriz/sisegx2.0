<?php
//C:\xampp\htdocs\SISE\api\jerarquias.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Consulta para obtener todas las jerarquías
    $query = "SELECT jr.IdRolSuperior, jr.IdRolSubordinado, 
              r1.Nombre as NombreRolSuperior, r2.Nombre as NombreRolSubordinado 
              FROM JerarquiaRol jr
              JOIN RolSistema r1 ON jr.IdRolSuperior = r1.Id
              JOIN RolSistema r2 ON jr.IdRolSubordinado = r2.Id
              ORDER BY r1.Nombre, r2.Nombre";
              
    $stmt = $db->prepare($query);
    $stmt->execute();
    $num = $stmt->rowCount();

    $jerarquias_arr = array();
    $jerarquias_arr["records"] = array();

    if ($num > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $jerarquia_item = array(
                "IdRolSuperior" => $IdRolSuperior,
                "IdRolSubordinado" => $IdRolSubordinado,
                "NombreRolSuperior" => $NombreRolSuperior,
                "NombreRolSubordinado" => $NombreRolSubordinado
            );

            array_push($jerarquias_arr["records"], $jerarquia_item);
        }
    }

    http_response_code(200);
    echo json_encode($jerarquias_arr);
} 
elseif ($method === 'POST') {
    // Recibir los datos enviados
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->rolId)) {
        try {
            // Comenzar transacción
            $db->beginTransaction();
            
            // 1. Eliminar todas las relaciones existentes donde el rol es superior
            $query = "DELETE FROM JerarquiaRol WHERE IdRolSuperior = :rolId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':rolId', $data->rolId);
            $stmt->execute();
            
            // 2. Eliminar todas las relaciones existentes donde el rol es subordinado
            $query = "DELETE FROM JerarquiaRol WHERE IdRolSubordinado = :rolId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':rolId', $data->rolId);
            $stmt->execute();
            
            // 3. Insertar nuevas relaciones donde el rol es superior
            if (!empty($data->subordinados) && is_array($data->subordinados)) {
                $query = "INSERT INTO JerarquiaRol (IdRolSuperior, IdRolSubordinado) VALUES (:rolId, :subordinadoId)";
                $stmt = $db->prepare($query);
                
                foreach ($data->subordinados as $subordinadoId) {
                    $stmt->bindParam(':rolId', $data->rolId);
                    $stmt->bindParam(':subordinadoId', $subordinadoId);
                    $stmt->execute();
                }
            }
            
            // 4. Insertar nuevas relaciones donde el rol es subordinado
            if (!empty($data->superiores) && is_array($data->superiores)) {
                $query = "INSERT INTO JerarquiaRol (IdRolSuperior, IdRolSubordinado) VALUES (:superiorId, :rolId)";
                $stmt = $db->prepare($query);
                
                foreach ($data->superiores as $superiorId) {
                    $stmt->bindParam(':superiorId', $superiorId);
                    $stmt->bindParam(':rolId', $data->rolId);
                    $stmt->execute();
                }
            }
            
            // Confirmar transacción
            $db->commit();
            
            http_response_code(200);
            echo json_encode(array("message" => "Jerarquías actualizadas con éxito."));
        } 
        catch (Exception $e) {
            // Revertir cambios en caso de error
            $db->rollBack();
            
            http_response_code(500);
            echo json_encode(array("message" => "Error al actualizar jerarquías: " . $e->getMessage()));
        }
    } 
    else {
        http_response_code(400);
        echo json_encode(array("message" => "Datos incompletos."));
    }
} 
else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido."));
}
?>