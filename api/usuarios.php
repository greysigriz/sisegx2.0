<?php
require_once __DIR__ . '/cors.php';

//C:\xampp\htdocs\SISE\api\usuarios.php

// Incluir archivos de conexión y modelo
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';


// Instanciar base de datos y objeto usuario
$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

// Obtener método de solicitud
$method = $_SERVER['REQUEST_METHOD'];

// Procesar según el método HTTP
switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            // Obtener un usuario específico
            $usuario->Id = $_GET['id'];
            $usuario_data = $usuario->readOne();
            
            if($usuario_data) {
                http_response_code(200);
                echo json_encode($usuario_data);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Usuario no encontrado."));
            }
        } else {
            // Obtener todos los usuarios
            $stmt = $usuario->read();
            $num = $stmt->rowCount();
            
            if($num > 0) {
                $usuarios_arr = array();
                $usuarios_arr["records"] = array();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    
                    $usuario_item = array(
                        "Id" => $Id,
                        "Usuario" => $Usuario,
                        "Nombre" => $Nombre,
                        "ApellidoP" => $ApellidoP,
                        "ApellidoM" => $ApellidoM,
                        "Puesto" => $Puesto,
                        "Estatus" => $Estatus,
                        "IdDivisionAdm" => $IdDivisionAdm,
                        "IdUnidad" => $IdUnidad,
                        "IdRolSistema" => $IdRolSistema,
                        "NombreRol" => $NombreRol,
                        "NombreDivision" => $NombreDivision
                    );
                    
                    array_push($usuarios_arr["records"], $usuario_item);
                }
                
                http_response_code(200);
                echo json_encode($usuarios_arr);
            } else {
                http_response_code(200);
                echo json_encode(array("records" => array()));
            }
        }
        break;
        
    case 'POST':
        // Crear un usuario
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->Usuario) && !empty($data->Nombre)) {
            // Convertir valores posiblemente nulos
            $usuario->Usuario = $data->Usuario;
            $usuario->Nombre = $data->Nombre;
            $usuario->ApellidoP = $data->ApellidoP ?? '';
            $usuario->ApellidoM = $data->ApellidoM ?? '';
            $usuario->Puesto = $data->Puesto ?? '';
            $usuario->Estatus = $data->Estatus ?? 'ACTIVO';
            $usuario->IdDivisionAdm = $data->IdDivisionAdm ?? null;
            $usuario->IdUnidad = $data->IdUnidad ?? null;
            $usuario->IdRolSistema = $data->IdRolSistema;
            $usuario->Password = password_hash($data->Password, PASSWORD_DEFAULT);
            
            if($usuario->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Usuario creado."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el usuario."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No se pudo crear el usuario. Datos incompletos."));
        }
        break;
        
    case 'PUT':
        // Actualizar un usuario
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->Id)) {
            $usuario->Id = $data->Id;
            $usuario->Usuario = $data->Usuario;
            $usuario->Nombre = $data->Nombre;
            $usuario->ApellidoP = $data->ApellidoP ?? '';
            $usuario->ApellidoM = $data->ApellidoM ?? '';
            $usuario->Puesto = $data->Puesto ?? '';
            $usuario->Estatus = $data->Estatus ?? 'ACTIVO';
            $usuario->IdDivisionAdm = $data->IdDivisionAdm;
            $usuario->IdUnidad = $data->IdUnidad;
            $usuario->IdRolSistema = $data->IdRolSistema;
            
            // Solo actualizar contraseña si se proporciona
            if(!empty($data->Password)) {
                $usuario->Password = password_hash($data->Password, PASSWORD_DEFAULT);
            }
            
            if($usuario->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Usuario actualizado."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el usuario."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No se pudo actualizar el usuario. Falta ID."));
        }
        break;
        
    case 'DELETE':
        // Eliminar un usuario
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->Id)) {
            $usuario->Id = $data->Id;
            
            if($usuario->delete()) {
                http_response_code(200);
                echo json_encode(array("message" => "Usuario eliminado."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar el usuario."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No se pudo eliminar el usuario. Falta ID."));
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido."));
        break;
}
?>