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
        // Consulta para obtener todos los roles con la cantidad de usuarios que los tienen
        $query = "SELECT 
                    r.Id, 
                    r.Nombre, 
                    r.Descripcion,
                    COUNT(DISTINCT ur.IdUsuario) as CantidadUsuarios
                  FROM RolSistema r
                  LEFT JOIN UsuarioRol ur ON r.Id = ur.IdRolSistema
                  GROUP BY r.Id, r.Nombre, r.Descripcion
                  ORDER BY r.Nombre";
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
                    "Descripcion" => $Descripcion,
                    "CantidadUsuarios" => (int)$CantidadUsuarios
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

            // 0. Actualizar usuarios que tienen este rol en la columna legacy IdRolSistema (si existe)
            try {
                // Verificar si la columna IdRolSistema existe en Usuario
                $query = "SHOW COLUMNS FROM Usuario LIKE 'IdRolSistema'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    // La columna existe, poner NULL en usuarios con este rol
                    $query = "UPDATE Usuario SET IdRolSistema = NULL WHERE IdRolSistema = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $data->Id);
                    $stmt->execute();
                }
            } catch(Exception $e) {
                // Continuar si hay error
            }

            // 1. Eliminar todas las asignaciones de usuarios con este rol (UsuarioRol) - si la tabla existe
            try {
                $query = "DELETE FROM UsuarioRol WHERE IdRolSistema = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data->Id);
                $stmt->execute();
            } catch(Exception $e) {
                // Tabla no existe, continuar
            }

            // 2. Eliminar todos los permisos asociados al rol (RolPermiso) - si la tabla existe
            try {
                $query = "DELETE FROM RolPermiso WHERE IdRolSistema = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data->Id);
                $stmt->execute();
            } catch(Exception $e) {
                // Tabla no existe, continuar
            }

            // 3. Eliminar todas las relaciones de jerarquía donde el rol participa - si la tabla existe
            try {
                $query = "DELETE FROM JerarquiaRol WHERE IdRolSuperior = :id OR IdRolSubordinado = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data->Id);
                $stmt->execute();
            } catch(Exception $e) {
                // Tabla no existe, continuar
            }

            // 4. Eliminar el rol
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