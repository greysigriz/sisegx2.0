<?php
/**
 * API para gestión de notificaciones por Super Usuario
 * Permite ver y administrar las notificaciones de todos los usuarios de departamento
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
require_once 'cors.php';

session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autenticado'
    ]);
    exit;
}

// Verificar que el usuario sea Super Usuario (RolId = 1)
$esSuperUsuario = false;
if (isset($_SESSION['usuario']['RolesIds']) && is_array($_SESSION['usuario']['RolesIds'])) {
    $esSuperUsuario = in_array(1, $_SESSION['usuario']['RolesIds']);
}

if (!$esSuperUsuario) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para acceder a esta funcionalidad'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

try {
    if ($method === 'GET') {
        // Si se solicita el historial de un usuario específico
        if (isset($_GET['userId']) && isset($_GET['historial'])) {
            $userId = intval($_GET['userId']);
            
            $stmt = $pdo->prepare("
                SELECT 
                    Id,
                    FechaEnvio,
                    Estado,
                    Asunto,
                    CantidadPeticionesPendientes
                FROM NotificacionHistorial
                WHERE IdUsuario = :userId
                ORDER BY FechaEnvio DESC
                LIMIT 50
            ");
            
            $stmt->execute([':userId' => $userId]);
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $historial
            ]);
            exit;
        }
        
        // Obtener todos los usuarios con rol Departamento (RolId = 9)
        $stmt = $pdo->query("
            SELECT DISTINCT
                u.IdUsuario,
                u.Usuario,
                u.Email,
                u.IdUnidad,
                un.nombre AS nombre_unidad,
                nc.NotificacionesActivas,
                nc.UltimaNotificacion
            FROM Usuario u
            INNER JOIN UsuarioRol ur ON u.IdUsuario = ur.IdUsuario
            LEFT JOIN Unidad un ON u.IdUnidad = un.id
            LEFT JOIN NotificacionConfiguracion nc ON u.IdUsuario = nc.IdUsuario
            WHERE ur.IdRol = 9
            ORDER BY u.Usuario ASC
        ");
        
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir NotificacionesActivas a boolean
        foreach ($usuarios as &$usuario) {
            $usuario['NotificacionesActivas'] = (bool) $usuario['NotificacionesActivas'];
        }
        
        // Obtener lista de departamentos únicos
        $stmtDeptos = $pdo->query("
            SELECT DISTINCT
                un.id,
                un.nombre
            FROM Unidad un
            INNER JOIN Usuario u ON un.id = u.IdUnidad
            INNER JOIN UsuarioRol ur ON u.IdUsuario = ur.IdUsuario
            WHERE ur.IdRol = 9
            ORDER BY un.nombre ASC
        ");
        
        $departamentos = $stmtDeptos->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'usuarios' => $usuarios,
                'departamentos' => $departamentos
            ]
        ]);
        
    } elseif ($method === 'PUT') {
        // Activar o desactivar notificaciones para un usuario
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['userId']) || !isset($input['NotificacionesActivas'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan parámetros requeridos'
            ]);
            exit;
        }
        
        $userId = intval($input['userId']);
        $notificacionesActivas = $input['NotificacionesActivas'] ? 1 : 0;
        
        // Verificar que el usuario exista y tenga rol departamento
        $stmt = $pdo->prepare("
            SELECT u.IdUsuario, u.Email
            FROM Usuario u
            INNER JOIN UsuarioRol ur ON u.IdUsuario = ur.IdUsuario
            WHERE u.IdUsuario = :userId AND ur.IdRol = 9
        ");
        
        $stmt->execute([':userId' => $userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no encontrado o no es usuario de departamento'
            ]);
            exit;
        }
        
        // Si se está activando, verificar que tenga email configurado
        if ($notificacionesActivas && empty($usuario['Email'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'El usuario no tiene un email configurado'
            ]);
            exit;
        }
        
        // Verificar si existe configuración
        $stmt = $pdo->prepare("
            SELECT IdUsuario FROM NotificacionConfiguracion WHERE IdUsuario = :userId
        ");
        $stmt->execute([':userId' => $userId]);
        $existeConfig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existeConfig) {
            // Actualizar configuración existente
            $stmt = $pdo->prepare("
                UPDATE NotificacionConfiguracion
                SET NotificacionesActivas = :activas,
                    FechaActualizacion = NOW()
                WHERE IdUsuario = :userId
            ");
            
            $stmt->execute([
                ':activas' => $notificacionesActivas,
                ':userId' => $userId
            ]);
        } else {
            // Crear nueva configuración con valores por defecto
            $stmt = $pdo->prepare("
                INSERT INTO NotificacionConfiguracion (
                    IdUsuario,
                    NotificacionesActivas,
                    FrecuenciaNotificacion,
                    HoraEnvio,
                    UmbralPeticionesPendientes,
                    FiltrarPorMunicipio,
                    NotificarPeticionesNuevas,
                    NotificarPeticionesVencidas,
                    FechaCreacion,
                    FechaActualizacion
                ) VALUES (
                    :userId,
                    :activas,
                    'diaria',
                    '09:00:00',
                    1,
                    0,
                    1,
                    1,
                    NOW(),
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':userId' => $userId,
                ':activas' => $notificacionesActivas
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $notificacionesActivas 
                ? 'Notificaciones activadas correctamente' 
                : 'Notificaciones desactivadas correctamente'
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en gestion-notificaciones.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor',
        'error' => $e->getMessage()
    ]);
}
