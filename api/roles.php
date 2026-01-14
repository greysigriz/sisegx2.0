<?php
//C:\xampp\htdocs\SISE\api\roles.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET') {
    // Verificar si se solicita un rol específico
    if(isset($_GET['rolId'])) {
        $rolId = $_GET['rolId'];
        // Consulta para verificar si el rol está en uso por usuarios
        $query = "SELECT COUNT(*) as total FROM Usuario WHERE IdRolSistema = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $rolId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $response = array("usedByUsers" => ($row['total'] > 0));
        http_response_code(200);
        echo json_encode($response);
    } else {
        // Consulta para obtener todos los roles
        $query = "SELECT Id, Nombre, Descripcion FROM RolSistema ORDER BY Nombre";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $num = $stmt->rowCount();

        if($num > 0) {
            $roles_arr = array();
            $roles_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $rol_item = array(
                    "Id" => $Id,
                    "Nombre" => $Nombre,
                    "Descripcion" => $Descripcion
                );

                array_push($roles_arr["records"], $rol_item);
            }

            http_response_code(200);
            echo json_encode($roles_arr);
        } else {
            http_response_code(200);
            echo json_encode(array("records" => array()));
        }
    }
}
elseif($method === 'POST') {
    // Recibir los datos enviados
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->Nombre)) {
        try {
            // Consulta SQL para insertar rol
            $query = "INSERT INTO RolSistema (Nombre, Descripcion) VALUES (:nombre, :descripcion)";
            $stmt = $db->prepare($query);

            // Sanitizar datos
            $nombre = strip_tags($data->Nombre);
            $descripcion = isset($data->Descripcion) ? strip_tags($data->Descripcion) : "";

            // Vincular valores
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);

            // Ejecutar la consulta
            if($stmt->execute()) {
                http_response_code(201);
                echo json_encode(array("message" => "Rol creado con éxito."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el rol."));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "No se puede crear el rol. Datos incompletos."));
    }
}
elseif($method === 'PUT') {
    // Recibir los datos enviados
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->Id) && !empty($data->Nombre)) {
        try {
            // Consulta SQL para actualizar rol
            $query = "UPDATE RolSistema SET Nombre = :nombre, Descripcion = :descripcion WHERE Id = :id";
            $stmt = $db->prepare($query);

            // Sanitizar datos
            $id = strip_tags($data->Id);
            $nombre = strip_tags($data->Nombre);
            $descripcion = isset($data->Descripcion) ? strip_tags($data->Descripcion) : "";

            // Vincular valores
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);

            // Ejecutar la consulta
            if($stmt->execute()) {
                http_response_code(200);
                echo json_encode(array("message" => "Rol actualizado con éxito."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el rol."));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "No se puede actualizar el rol. Datos incompletos."));
    }
}
elseif($method === 'DELETE') {
    // Recibir los datos enviados
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->Id)) {
        try {
            // Comenzar transacción
            $db->beginTransaction();

            // 1. Eliminar todas las relaciones de jerarquía donde el rol participa
            $query = "DELETE FROM JerarquiaRol WHERE IdRolSuperior = :id OR IdRolSubordinado = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data->Id);
            $stmt->execute();

            // 2. Eliminar el rol
            $query = "DELETE FROM RolSistema WHERE Id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data->Id);

            // Ejecutar la consulta
            if($stmt->execute()) {
                // Confirmar transacción
                $db->commit();

                http_response_code(200);
                echo json_encode(array("message" => "Rol eliminado con éxito."));
            } else {
                // Revertir en caso de error
                $db->rollBack();

                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar el rol."));
            }
        } catch(Exception $e) {
            // Revertir en caso de error
            $db->rollBack();

            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "No se puede eliminar el rol. Datos incompletos."));
    }
}
else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido."));
}
?>