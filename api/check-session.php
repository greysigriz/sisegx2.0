<?php
// C:\xampp\htdocs\SISE\api\check-session.php

// Headers de seguridad y JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// CORS
require_once __DIR__ . '/cors.php';

// Mostrar errores (desactiva en producción)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Función de respuesta JSON
function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Log de errores
function logError($message, $context = []) {
    $logFile = __DIR__ . '/logs/session_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";

    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }

    $logMessage .= PHP_EOL;

    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }

    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendJsonResponse([
            'success' => false,
            'message' => 'Método no permitido. Use GET.',
            'code' => 'METHOD_NOT_ALLOWED'
        ], 405);
    }

    // Seguridad en cookies
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'None');
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'No hay sesión activa',
            'code' => 'NO_SESSION'
        ], 401);
    }

    $currentTime = time();
    $loginTime = $_SESSION['login_time'] ?? $currentTime;
    $lastActivity = $_SESSION['last_activity'] ?? $currentTime;
    $sessionTimeout = 8 * 60 * 60; // 8 horas
    $inactivityTimeout = 30 * 60;  // 30 minutos

    if (($currentTime - $loginTime) > $sessionTimeout) {
        session_destroy();
        sendJsonResponse([
            'success' => false,
            'message' => 'Sesión expirada por tiempo',
            'code' => 'SESSION_EXPIRED_TIME'
        ], 401);
    }

    if (($currentTime - $lastActivity) > $inactivityTimeout) {
        session_destroy();
        sendJsonResponse([
            'success' => false,
            'message' => 'Sesión expirada por inactividad',
            'code' => 'SESSION_EXPIRED_INACTIVITY'
        ], 401);
    }

    // Actualizar actividad
    $_SESSION['last_activity'] = $currentTime;

    // Obtener datos desde la base de datos
    require_once __DIR__ . '/../config/database.php';

    try {
        $database = new Database();
        $db = $database->getConnection();
    } catch (Exception $dbException) {
        logError('Error de conexión a BD: ' . $dbException->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Error de conexión a la base de datos',
            'code' => 'DB_CONNECTION_ERROR'
        ], 500);
    }

    $query = "SELECT u.Id, u.Usuario, u.Nombre, u.ApellidoP, u.ApellidoM,
                     u.Puesto, u.Estatus, u.IdDivisionAdm, u.IdUnidad,
                     u.IdRolSistema, d.Municipio as NombreDivision, d.Estado, d.Pais
              FROM Usuario u
              LEFT JOIN DivisionAdministrativa d ON u.IdDivisionAdm = d.Id
              WHERE u.Id = :user_id AND u.Estatus = 'ACTIVO'";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        sendJsonResponse([
            'success' => false,
            'message' => 'Usuario no encontrado o inactivo',
            'code' => 'USER_NOT_FOUND'
        ], 401);
    }

    // Obtener todos los roles del usuario desde la tabla intermedia
    $queryRoles = "SELECT 
                    r.Id, 
                    r.Nombre, 
                    r.Descripcion
                   FROM UsuarioRol ur
                   JOIN RolSistema r ON ur.IdRolSistema = r.Id
                   WHERE ur.IdUsuario = :user_id
                   ORDER BY r.Nombre";
    
    $stmtRoles = $db->prepare($queryRoles);
    $stmtRoles->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmtRoles->execute();
    
    $roles = [];
    $rolesIds = [];
    $rolesNombres = [];
    
    while ($rol = $stmtRoles->fetch(PDO::FETCH_ASSOC)) {
        $roles[] = $rol;
        $rolesIds[] = $rol['Id'];
        $rolesNombres[] = $rol['Nombre'];
    }

    // Obtener todos los permisos del usuario basados en sus roles
    $queryPermisos = "SELECT DISTINCT p.Codigo
                      FROM UsuarioRol ur
                      JOIN RolPermiso rp ON ur.IdRolSistema = rp.IdRolSistema
                      JOIN Permiso p ON rp.IdPermiso = p.Id
                      WHERE ur.IdUsuario = :user_id";
    
    $stmtPermisos = $db->prepare($queryPermisos);
    $stmtPermisos->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmtPermisos->execute();
    
    $permisos = [];
    while ($permiso = $stmtPermisos->fetch(PDO::FETCH_ASSOC)) {
        $permisos[] = $permiso['Codigo'];
    }

    // Respuesta exitosa - incluir roles múltiples y permisos
    sendJsonResponse([
        'success' => true,
        'message' => 'Sesión válida',
        'user' => [
            'Id' => $user['Id'],
            'Usuario' => $user['Usuario'],
            'Nombre' => $user['Nombre'],
            'ApellidoP' => $user['ApellidoP'],
            'ApellidoM' => $user['ApellidoM'],
            'Puesto' => $user['Puesto'],
            'Estatus' => $user['Estatus'],
            'IdDivisionAdm' => $user['IdDivisionAdm'],
            'IdUnidad' => $user['IdUnidad'],
            'IdRolSistema' => $user['IdRolSistema'], // Mantener por compatibilidad
            'Roles' => $roles, // Array de objetos con todos los roles
            'RolesIds' => $rolesIds, // Array de IDs de roles
            'RolesNombres' => $rolesNombres, // Array de nombres de roles
            'Permisos' => $permisos, // NUEVO: Array de códigos de permisos
            'NombreDivision' => $user['NombreDivision'],
            'Estado' => $user['Estado'] ?? 'Yucatán',
            'Pais' => $user['Pais'] ?? 'México'
        ],
        'session_info' => [
            'login_time' => $loginTime,
            'last_activity' => $_SESSION['last_activity'],
            'expires_at' => $loginTime + $sessionTimeout,
            'time_remaining' => ($loginTime + $sessionTimeout) - $currentTime
        ],
        'code' => 'SESSION_VALID'
    ], 200);

} catch (Exception $e) {
    logError('Error en check-session: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'user_id' => $_SESSION['user_id'] ?? 'unknown'
    ]);

    sendJsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor',
        'code' => 'INTERNAL_ERROR'
    ], 500);
}
