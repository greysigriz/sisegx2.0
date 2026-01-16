<?php
// C:\xampp\htdocs\SISEE\api\usuario-roles.php
// API para manejar roles de usuarios (tabla intermedia UsuarioRol)

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// GET - Múltiples acciones según el parámetro 'action'
if($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : 'getRolesByUser';
    
    // Acción 1: Obtener roles de un usuario específico
    if($action === 'getRolesByUser') {
        if(!isset($_GET['idUsuario'])) {
            http_response_code(400);
            echo json_encode(array("message" => "Se requiere idUsuario"));
            exit;
        }

        $idUsuario = $_GET['idUsuario'];

        try {
            $query = "SELECT 
                        ur.Id,
                        ur.IdUsuario,
                        ur.IdRolSistema,
                        r.Nombre as NombreRol,
                        r.Descripcion,
                        ur.FechaAsignacion
                      FROM UsuarioRol ur
                      JOIN RolSistema r ON ur.IdRolSistema = r.Id
                      WHERE ur.IdUsuario = :idUsuario
                      ORDER BY r.Nombre";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();

            $roles = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($roles, $row);
            }

            http_response_code(200);
            echo json_encode(array("records" => $roles));
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    }
    
    // Acción 2: Contar cuántos usuarios tienen un rol específico
    elseif($action === 'countByRole') {
        if(!isset($_GET['idRol'])) {
            http_response_code(400);
            echo json_encode(array("message" => "Se requiere idRol"));
            exit;
        }

        $idRol = $_GET['idRol'];

        try {
            $query = "SELECT COUNT(*) as count FROM UsuarioRol WHERE IdRolSistema = :idRol";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':idRol', $idRol, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(array("count" => (int)$row['count']));
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    }
    
    // Acción 3: Obtener usuarios que tienen un rol específico
    elseif($action === 'getUsersByRole') {
        if(!isset($_GET['idRol'])) {
            http_response_code(400);
            echo json_encode(array("message" => "Se requiere idRol"));
            exit;
        }

        $idRol = $_GET['idRol'];

        try {
            $query = "SELECT 
                        ur.IdUsuario,
                        u.Nombre,
                        u.ApellidoPaterno,
                        u.ApellidoMaterno,
                        CONCAT(u.Nombre, ' ', u.ApellidoPaterno, ' ', u.ApellidoMaterno) as NombreCompleto,
                        u.Email,
                        ur.FechaAsignacion
                      FROM UsuarioRol ur
                      JOIN Usuario u ON ur.IdUsuario = u.Id
                      WHERE ur.IdRolSistema = :idRol
                      ORDER BY u.Nombre, u.ApellidoPaterno";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':idRol', $idRol, PDO::PARAM_INT);
            $stmt->execute();

            $usuarios = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($usuarios, $row);
            }

            http_response_code(200);
            echo json_encode(array("usuarios" => $usuarios));
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    }
    
    // Acción 4: Obtener permisos de un rol específico
    elseif($action === 'getPermisosByRole') {
        if(!isset($_GET['idRol'])) {
            http_response_code(400);
            echo json_encode(array("message" => "Se requiere idRol"));
            exit;
        }

        $idRol = $_GET['idRol'];

        try {
            $query = "SELECT 
                        p.Id,
                        p.Codigo,
                        p.Nombre,
                        p.Descripcion,
                        p.Modulo
                      FROM RolPermiso rp
                      JOIN Permiso p ON rp.IdPermiso = p.Id
                      WHERE rp.IdRolSistema = :idRol
                      ORDER BY p.Modulo, p.Nombre";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':idRol', $idRol, PDO::PARAM_INT);
            $stmt->execute();

            $permisos = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($permisos, $row);
            }

            http_response_code(200);
            echo json_encode(array("permisos" => $permisos));
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error: " . $e->getMessage()));
        }
    }
    
    else {
        http_response_code(400);
        echo json_encode(array("message" => "Acción no reconocida"));
    }
}

// POST - Asignar roles a un usuario (reemplaza todos los roles existentes)
elseif($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if(!isset($data->idUsuario) || !isset($data->roles)) {
        http_response_code(400);
        echo json_encode(array("message" => "Se requieren idUsuario y roles"));
        exit;
    }

    try {
        // Iniciar transacción
        $db->beginTransaction();

        // 1. Eliminar todos los roles actuales del usuario
        $query = "DELETE FROM UsuarioRol WHERE IdUsuario = :idUsuario";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':idUsuario', $data->idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        // 2. Insertar los nuevos roles
        if(!empty($data->roles) && is_array($data->roles)) {
            $query = "INSERT INTO UsuarioRol (IdUsuario, IdRolSistema, AsignadoPor) 
                      VALUES (:idUsuario, :idRol, :asignadoPor)";
            $stmt = $db->prepare($query);

            // Obtener ID del usuario que está haciendo la asignación (de la sesión)
            session_start();
            $asignadoPor = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

            foreach($data->roles as $idRol) {
                $stmt->bindParam(':idUsuario', $data->idUsuario, PDO::PARAM_INT);
                $stmt->bindParam(':idRol', $idRol, PDO::PARAM_INT);
                $stmt->bindParam(':asignadoPor', $asignadoPor, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        // Confirmar transacción
        $db->commit();

        http_response_code(200);
        echo json_encode(array("message" => "Roles actualizados correctamente"));
    } catch(Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("message" => "Error: " . $e->getMessage()));
    }
}

// DELETE - Eliminar un rol específico de un usuario
elseif($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));

    if(!isset($data->id)) {
        http_response_code(400);
        echo json_encode(array("message" => "Se requiere id del registro UsuarioRol"));
        exit;
    }

    try {
        $query = "DELETE FROM UsuarioRol WHERE Id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);

        if($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "Rol removido correctamente"));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo remover el rol"));
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error: " . $e->getMessage()));
    }
}

else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido"));
}
?>
