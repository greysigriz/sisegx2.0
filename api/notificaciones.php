<?php
// C:\xampp\htdocs\SISEE\api\notificaciones.php
/**
 * API para gestión de configuración de notificaciones por correo electrónico
 * Permite a usuarios con rol de Departamento configurar sus preferencias de notificación
 */

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado', 'message' => 'Debe iniciar sesión']);
    exit();
}

$userId = $_SESSION['user_id'];

// Verificar que el usuario tiene rol de Departamento (RolId = 9)
$queryCheckRole = "SELECT COUNT(*) as count 
                   FROM UsuarioRol 
                   WHERE IdUsuario = :userId AND IdRolSistema = 9";
$stmtCheckRole = $db->prepare($queryCheckRole);
$stmtCheckRole->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmtCheckRole->execute();
$roleCheck = $stmtCheckRole->fetch(PDO::FETCH_ASSOC);

if ($roleCheck['count'] == 0) {
    http_response_code(403);
    echo json_encode([
        'error' => 'Acceso denegado', 
        'message' => 'Esta funcionalidad es solo para usuarios con rol de Departamento'
    ]);
    exit();
}

/**
 * GET - Obtener configuración de notificaciones del usuario
 */
if ($method === 'GET') {
    try {
        $query = "SELECT 
                    nc.*,
                    u.Usuario,
                    u.Email,
                    u.IdUnidad,
                    uni.nombre_unidad,
                    (SELECT COUNT(*) 
                     FROM peticion_departamento pd 
                     WHERE pd.departamento_id = u.IdUnidad 
                         AND pd.estado IN ('Esperando recepción', 'Aceptado en proceso')
                    ) AS peticiones_pendientes_actuales
                  FROM NotificacionConfiguracion nc
                  INNER JOIN Usuario u ON nc.IdUsuario = u.Id
                  LEFT JOIN unidades uni ON nc.IdUnidad = uni.id
                  WHERE nc.IdUsuario = :userId";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            // Si no existe configuración, obtener datos del usuario (debe tener rol Departamento y unidad asignada)
            $queryUsuario = "SELECT u.IdUnidad, u.Email FROM Usuario u WHERE u.Id = :userId";
            $stmtUsuario = $db->prepare($queryUsuario);
            $stmtUsuario->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmtUsuario->execute();
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && $usuario['IdUnidad']) {
                // Crear configuración por defecto para el gestor de departamento
                $idUnidad = $usuario['IdUnidad'];
                
                $insertQuery = "INSERT INTO NotificacionConfiguracion 
                               (IdUsuario, IdUnidad, NotificacionesActivas, FiltrarPorMunicipio)
                               VALUES (:userId, :idUnidad, 0, 0)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                $insertStmt->bindParam(':idUnidad', $idUnidad, PDO::PARAM_INT);
                $insertStmt->execute();
                
                // Obtener la configuración recién creada
                $stmt->execute();
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Sin configuración',
                    'message' => 'Usuario no tiene unidad/departamento asignado. Contacte al administrador.'
                ]);
                exit();
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $config
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener configuración', 'message' => $e->getMessage()]);
    }
}

/**
 * PUT - Actualizar configuración de notificaciones
 */
elseif ($method === 'PUT') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validar datos requeridos
        $camposPermitidos = [
            'NotificacionesActivas',
            'FiltrarPorMunicipio',
            'FrecuenciaNotificacion',
            'HoraEnvio',
            'UmbralPeticionesPendientes',
            'NotificarPeticionesNuevas',
            'NotificarPeticionesVencidas'
        ];
        
        $updates = [];
        $params = [':userId' => $userId];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($data[$campo])) {
                $updates[] = "$campo = :$campo";
                $params[":$campo"] = $data[$campo];
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'Sin datos para actualizar']);
            exit();
        }
        
        $query = "UPDATE NotificacionConfiguracion 
                  SET " . implode(', ', $updates) . "
                  WHERE IdUsuario = :userId";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada correctamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'No se encontró configuración para actualizar']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar configuración', 'message' => $e->getMessage()]);
    }
}

/**
 * POST - Actualizar email del usuario y enviar prueba
 */
elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['email']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email requerido']);
            exit();
        }
        
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Email inválido']);
            exit();
        }
        
        // Verificar si el email ya está en uso por otro usuario
        $queryCheck = "SELECT Id FROM Usuario WHERE Email = :email AND Id != :userId";
        $stmtCheck = $db->prepare($queryCheck);
        $stmtCheck->bindParam(':email', $email);
        $stmtCheck->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmtCheck->execute();
        
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email ya registrado', 'message' => 'Este email ya está en uso por otro usuario']);
            exit();
        }
        
        // Actualizar email del usuario
        $query = "UPDATE Usuario SET Email = :email WHERE Id = :userId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Activar notificaciones automáticamente con configuración fija
        $queryUpdateConfig = "UPDATE NotificacionConfiguracion 
                             SET NotificacionesActivas = 1,
                                 FrecuenciaNotificacion = 'diaria',
                                 HoraEnvio = '09:00:00',
                                 UmbralPeticionesPendientes = 1,
                                 FiltrarPorMunicipio = 0,
                                 NotificarPeticionesNuevas = 1,
                                 NotificarPeticionesVencidas = 1
                             WHERE IdUsuario = :userId";
        $stmtUpdateConfig = $db->prepare($queryUpdateConfig);
        $stmtUpdateConfig->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmtUpdateConfig->execute();
        
        // Enviar correo de prueba inmediatamente
        require_once __DIR__ . '/services/EmailService.php';
        $emailService = new EmailService();
        
        try {
            $emailEnviado = $emailService->enviarCorreoPrueba($email, 'Usuario de Departamento');
            
            if ($emailEnviado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Email actualizado y correo de prueba enviado correctamente. Revisa tu bandeja de entrada.',
                    'email' => $email,
                    'testEmailSent' => true
                ]);
            } else {
                // Si falla el envío, desactivar notificaciones
                $queryDeactivate = "UPDATE NotificacionConfiguracion 
                                   SET NotificacionesActivas = 0 
                                   WHERE IdUsuario = :userId";
                $stmtDeactivate = $db->prepare($queryDeactivate);
                $stmtDeactivate->bindParam(':userId', $userId, PDO::PARAM_INT);
                $stmtDeactivate->execute();
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Error al enviar correo de prueba',
                    'message' => 'El email fue guardado pero no se pudo enviar el correo de prueba. Por favor verifica que el email sea correcto e inténtalo nuevamente.',
                    'email' => $email,
                    'testEmailSent' => false
                ]);
            }
        } catch (Exception $e) {
            // Si falla el envío, desactivar notificaciones
            $queryDeactivate = "UPDATE NotificacionConfiguracion 
                               SET NotificacionesActivas = 0 
                               WHERE IdUsuario = :userId";
            $stmtDeactivate = $db->prepare($queryDeactivate);
            $stmtDeactivate->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmtDeactivate->execute();
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al enviar correo de prueba',
                'message' => 'El email fue guardado pero no se pudo enviar el correo de prueba: ' . $e->getMessage(),
                'email' => $email,
                'testEmailSent' => false
            ]);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar email', 'message' => $e->getMessage()]);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
