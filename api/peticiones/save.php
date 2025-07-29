<?php
// api/peticiones/save.php

// Incluir el archivo CORS al principio
include_once '../cors.php';

// Permitir solicitudes desde cualquier origen
header("Content-Type: application/json; charset=UTF-8");

// Solo permitir el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Método no permitido"));
    exit();
}

// Iniciar sesión para verificar usuario autenticado
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['timestamp'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Usuario no autenticado"));
    exit();
}

// Verificar expiración de sesión
$expireTime = 24 * 60 * 60; // 24 horas
$currentTime = time();
if (($currentTime - $_SESSION['timestamp']) > $expireTime) {
    session_destroy();
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Sesión expirada"));
    exit();
}

// Incluir la clase de conexión a la base de datos
require_once $_SERVER['DOCUMENT_ROOT'] . '/SISE/config/database.php';

// Obtener los datos enviados
$data = json_decode(file_get_contents("php://input"));

// Verificar que todos los campos requeridos estén presentes
if (
    empty($data->nombre) ||
    empty($data->telefono) ||
    empty($data->direccion) ||
    empty($data->localidad) ||
    empty($data->descripcion) ||
    empty($data->nivel_importancia)
) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "No se pueden procesar datos incompletos. Por favor complete todos los campos obligatorios."));
    exit();
}

// Validar nivel de importancia
if (!is_numeric($data->nivel_importancia) || $data->nivel_importancia < 1 || $data->nivel_importancia > 5) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Nivel de importancia inválido. Debe ser un número entre 1 y 5."));
    exit();
}

try {
    // Crear una instancia de la clase Database
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener la información del usuario autenticado incluyendo su división administrativa
    $userQuery = "SELECT Id, Usuario, Nombre, ApellidoP, ApellidoM, IdDivisionAdm 
                  FROM Usuario 
                  WHERE Id = :user_id";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':user_id', $_SESSION['user_id']);
    $userStmt->execute();
    
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "Usuario no encontrado"));
        exit();
    }
    
    // Verificar que el usuario tenga una división administrativa asignada
    if (empty($userData['IdDivisionAdm'])) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "El usuario no tiene una división administrativa asignada"));
        exit();
    }
    
    // Generar un folio único
    $folio = generarFolio($db);
    
    // Preparar la consulta SQL para insertar los datos incluyendo la división administrativa y nivel de importancia
    $query = "INSERT INTO peticiones 
              (folio, nombre, telefono, direccion, localidad, descripcion, red_social, NivelImportancia, division_id, fecha_registro) 
              VALUES 
              (:folio, :nombre, :telefono, :direccion, :localidad, :descripcion, :red_social, :nivel_importancia, :division_id, NOW())";
    
    // Preparar la sentencia
    $stmt = $db->prepare($query);
    
    // Sanear y vincular los valores
    $nombre = htmlspecialchars(strip_tags($data->nombre));
    $telefono = htmlspecialchars(strip_tags($data->telefono));
    $direccion = htmlspecialchars(strip_tags($data->direccion));
    $localidad = htmlspecialchars(strip_tags($data->localidad));
    $descripcion = htmlspecialchars(strip_tags($data->descripcion));
    $red_social = !empty($data->red_social) ? htmlspecialchars(strip_tags($data->red_social)) : null;
    $nivel_importancia = intval($data->nivel_importancia);
    $division_id = $userData['IdDivisionAdm'];
    
    $stmt->bindParam(':folio', $folio);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':direccion', $direccion);
    $stmt->bindParam(':localidad', $localidad);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':red_social', $red_social);
    $stmt->bindParam(':nivel_importancia', $nivel_importancia);
    $stmt->bindParam(':division_id', $division_id);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Obtener información de la división para incluir en la respuesta
        $divisionQuery = "SELECT Nombre, Pais, Region, Ciudad FROM DivisionAdministrativa WHERE Id = :division_id";
        $divisionStmt = $db->prepare($divisionQuery);
        $divisionStmt->bindParam(':division_id', $division_id);
        $divisionStmt->execute();
        $divisionData = $divisionStmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener el nombre del nivel de importancia para la respuesta
        $nivelTexto = obtenerTextoNivelImportancia($nivel_importancia);
        
        http_response_code(201);
        echo json_encode(array(
            "success" => true, 
            "message" => "Petición registrada exitosamente",
            "folio" => $folio,
            "division" => $divisionData ? $divisionData['Nombre'] : 'División no especificada',
            "nivel_importancia" => $nivelTexto,
            "usuario_registrado" => $userData['Nombre'] . ' ' . $userData['ApellidoP'] . ' ' . $userData['ApellidoM']
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "success" => false, 
            "message" => "No fue posible registrar la petición"
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false, 
        "message" => "Error en el servidor: " . $e->getMessage()
    ));
}

/**
 * Genera un folio único para una nueva petición
 * 
 * @param PDO $db Conexión a la base de datos
 * @return string Folio generado en formato FOLIO-XXXXXX
 */
function generarFolio($db) {
    try {
        // Obtener el último folio
        $query = "SELECT MAX(CAST(SUBSTRING(folio, 7) AS UNSIGNED)) as ultimo_numero 
                  FROM peticiones 
                  WHERE folio LIKE 'FOLIO-%'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $ultimo_numero = $row['ultimo_numero'];
        
        // Si no hay registros previos o error, comenzar desde 1
        if ($ultimo_numero === null) {
            $ultimo_numero = 0;
        }
        
        // Incrementar el número y formatear el nuevo folio
        $nuevo_numero = $ultimo_numero + 1;
        $folio = 'FOLIO-' . str_pad($nuevo_numero, 6, '0', STR_PAD_LEFT);
        
        return $folio;
    } catch (Exception $e) {
        // En caso de error, generar un folio basado en timestamp
        $timestamp = time();
        return 'FOLIO-' . str_pad($timestamp % 1000000, 6, '0', STR_PAD_LEFT);
    }
}

/**
 * Obtiene el texto descriptivo del nivel de importancia
 * 
 * @param int $nivel Nivel de importancia (1-5)
 * @return string Texto descriptivo del nivel
 */
function obtenerTextoNivelImportancia($nivel) {
    $niveles = array(
        1 => 'Crítico - Requiere atención inmediata',
        2 => 'Alto - Problema urgente',
        3 => 'Medio - Problema importante',
        4 => 'Bajo - Problema menor',
        5 => 'Muy Bajo - Consulta o sugerencia'
    );
    
    return isset($niveles[$nivel]) ? $niveles[$nivel] : 'Nivel no especificado';
}
?>