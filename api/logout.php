<?php
// C:\xampp\htdocs\SISE\api\logout.php

// Configurar headers de seguridad
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include CORS headers
require_once __DIR__ . '/cors.php';

// Configurar manejo de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Función para log de errores
function logError($message, $context = []) {
    $logFile = __DIR__ . '/logs/logout_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    
    $logMessage .= PHP_EOL;
    
    // Crear directorio de logs si no existe
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Función para enviar respuesta JSON
function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Solo permitir métodos POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse([
            'success' => false,
            'message' => 'Método no permitido. Use POST.',
            'code' => 'METHOD_NOT_ALLOWED'
        ], 405);
    }

    // Iniciar sesión de manera segura
    if (session_status() === PHP_SESSION_NONE) {
        // Configurar parámetros de sesión segura
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
        ini_set('session.use_strict_mode', 1);
        
        session_start();
    }

    // Verificar si hay una sesión activa
    $sessionExists = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $userId = $sessionExists ? $_SESSION['user_id'] : null;
    
    if (!$sessionExists) {
        logError('Intento de logout sin sesión activa', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        sendJsonResponse([
            'success' => false,
            'message' => 'No hay sesión activa para cerrar',
            'code' => 'NO_ACTIVE_SESSION'
        ], 400);
    }

    // Log del logout para auditoría
    logError('Logout exitoso', [
        'user_id' => $userId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    // Guardar información de la sesión antes de destruirla
    $sessionData = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'login_time' => $_SESSION['login_time'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null
    ];

    // Limpiar todas las variables de sesión
    $_SESSION = [];

    // Destruir la sesión
    if (session_destroy()) {
        // Limpiar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        // Limpiar cualquier otra cookie relacionada con la aplicación
        $cookiesToClear = ['remember_token', 'user_preferences', 'app_session'];
        foreach ($cookiesToClear as $cookie) {
            if (isset($_COOKIE[$cookie])) {
                setcookie($cookie, '', time() - 3600, '/');
            }
        }

        // Opcional: Registrar el logout en la base de datos
        // Esta parte es opcional y depende de si tienes una tabla de logs
        try {
            // require_once __DIR__ . '/config/database.php';
            // $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, ip_address, created_at) VALUES (?, 'logout', ?, NOW())");
            // $stmt->execute([$sessionData['user_id'], $_SERVER['REMOTE_ADDR']]);
        } catch (Exception $dbError) {
            logError('Error al registrar logout en base de datos: ' . $dbError->getMessage());
            // No fallar el logout por este error
        }

        sendJsonResponse([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
            'code' => 'LOGOUT_SUCCESS',
            'timestamp' => date('c')
        ], 200);

    } else {
        throw new Exception('No se pudo destruir la sesión');
    }

} catch (Exception $e) {
    logError('Error crítico en logout: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    // Intentar limpiar la sesión de manera forzada
    try {
        $_SESSION = [];
        @session_destroy();
        
        // Limpiar cookies de manera forzada
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"]);
        }
        
    } catch (Exception $cleanupError) {
        logError('Error en limpieza forzada: ' . $cleanupError->getMessage());
    }

    sendJsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor durante el logout',
        'code' => 'INTERNAL_ERROR',
        'debug' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null
    ], 500);
}
?>