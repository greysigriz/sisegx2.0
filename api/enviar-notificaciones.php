<?php
// C:\xampp\htdocs\SISEE\api\enviar-notificaciones.php
/**
 * API para enviar notificaciones por correo electrónico
 * Endpoint para envío manual y automatizado de notificaciones
 */

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/services/EmailService.php';

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Verificar autenticación (para llamadas manuales)
// Para cron jobs, se puede usar un token de autenticación
session_start();
$esLlamadaManual = isset($_SESSION['user_id']);
$esCronJob = isset($_GET['cron_token']) && $_GET['cron_token'] === getenv('CRON_TOKEN');

if (!$esLlamadaManual && !$esCronJob) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado', 'message' => 'Autenticación requerida']);
    exit();
}

// Si es llamada manual, verificar que tenga rol Departamento (RolId = 9)
if ($esLlamadaManual) {
    $userId = $_SESSION['user_id'];
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
}

/**
 * POST - Enviar notificaciones
 * Parámetros opcionales:
 * - userId: Enviar solo a un usuario específico (manual)
 * - testEmail: Enviar correo de prueba
 */
if ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Verificar configuración de email
        $verificacion = EmailService::verificarConfiguracion();
        if (!$verificacion['configurado']) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Configuración incompleta',
                'message' => 'El servidor de correo no está configurado correctamente',
                'faltantes' => $verificacion['faltantes']
            ]);
            exit();
        }
        
        $emailService = new EmailService();
        
        // Caso 1: Enviar correo de prueba
        if (isset($data['testEmail']) && $data['testEmail']) {
            if (!$esLlamadaManual) {
                http_response_code(403);
                echo json_encode(['error' => 'Solo usuarios autenticados pueden enviar correos de prueba']);
                exit();
            }
            
            $userId = $_SESSION['user_id'];
            $queryUser = "SELECT Email, Nombre, ApellidoP FROM Usuario WHERE Id = :userId";
            $stmtUser = $db->prepare($queryUser);
            $stmtUser->bindParam(':userId', $userId);
            $stmtUser->execute();
            $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario || !$usuario['Email']) {
                http_response_code(400);
                echo json_encode(['error' => 'Usuario no tiene email configurado']);
                exit();
            }
            
            $nombreCompleto = trim($usuario['Nombre'] . ' ' . ($usuario['ApellidoP'] ?? ''));
            $resultado = $emailService->enviarCorreoPrueba($usuario['Email'], $nombreCompleto);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Correo de prueba enviado correctamente',
                    'email' => $usuario['Email']
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo enviar el correo de prueba']);
            }
            exit();
        }
        
        // Caso 2: Enviar notificaciones a usuarios configurados
        $targetUserId = isset($data['userId']) ? intval($data['userId']) : null;
        
        // Obtener usuarios que deben recibir notificaciones
        $query = "SELECT 
                    u.Id AS usuario_id,
                    u.Usuario,
                    u.Nombre,
                    u.ApellidoP,
                    u.ApellidoM,
                    u.Email,
                    u.IdUnidad,
                    u.IdDivisionAdm AS municipio_id,
                    nc.NotificacionesActivas,
                    nc.FiltrarPorMunicipio,
                    nc.UmbralPeticionesPendientes,
                    nc.NotificarPeticionesNuevas,
                    nc.NotificarPeticionesVencidas,
                    uni.nombre_unidad
                  FROM Usuario u
                  INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
                  INNER JOIN NotificacionConfiguracion nc ON u.Id = nc.IdUsuario
                  INNER JOIN unidades uni ON u.IdUnidad = uni.id
                  WHERE ur.IdRolSistema = 9
                    AND nc.NotificacionesActivas = 1
                    AND u.Email IS NOT NULL
                    AND u.Email != ''
                    AND u.IdUnidad IS NOT NULL";
        
        if ($targetUserId) {
            $query .= " AND u.Id = :targetUserId";
        }
        
        $stmt = $db->prepare($query);
        if ($targetUserId) {
            $stmt->bindParam(':targetUserId', $targetUserId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($usuarios)) {
            echo json_encode([
                'success' => true,
                'message' => 'No hay usuarios configurados para recibir notificaciones',
                'enviados' => 0
            ]);
            exit();
        }
        
        $resultados = [
            'enviados' => 0,
            'fallidos' => 0,
            'omitidos' => 0,
            'detalles' => []
        ];
        
        // Procesar cada usuario
        foreach ($usuarios as $usuario) {
            try {
                // Obtener estadísticas de peticiones pendientes
                $queryStats = "SELECT 
                                COUNT(*) AS total_pendientes,
                                SUM(CASE WHEN pd.estado = 'Esperando recepción' THEN 1 ELSE 0 END) AS esperando_recepcion,
                                SUM(CASE WHEN pd.estado = 'Aceptado en proceso' THEN 1 ELSE 0 END) AS en_proceso,
                                MIN(pd.fecha_asignacion) AS peticion_mas_antigua,
                                MAX(pd.fecha_asignacion) AS peticion_mas_reciente
                              FROM peticion_departamento pd
                              INNER JOIN peticiones p ON pd.peticion_id = p.id
                              WHERE pd.departamento_id = :departamentoId
                                AND pd.estado IN ('Esperando recepción', 'Aceptado en proceso')";
                
                // Filtrar por municipio si está configurado
                if ($usuario['FiltrarPorMunicipio'] && $usuario['municipio_id']) {
                    $queryStats .= " AND p.division_id = :municipioId";
                }
                
                $stmtStats = $db->prepare($queryStats);
                $stmtStats->bindParam(':departamentoId', $usuario['IdUnidad'], PDO::PARAM_INT);
                
                if ($usuario['FiltrarPorMunicipio'] && $usuario['municipio_id']) {
                    $stmtStats->bindParam(':municipioId', $usuario['municipio_id'], PDO::PARAM_INT);
                }
                
                $stmtStats->execute();
                $estadisticas = $stmtStats->fetch(PDO::FETCH_ASSOC);
                
                // Verificar si cumple con el umbral mínimo
                $totalPendientes = intval($estadisticas['total_pendientes'] ?? 0);
                $umbral = intval($usuario['UmbralPeticionesPendientes'] ?? 0);
                
                if ($totalPendientes < $umbral) {
                    $resultados['omitidos']++;
                    $resultados['detalles'][] = [
                        'usuario' => $usuario['Usuario'],
                        'email' => $usuario['Email'],
                        'estado' => 'omitido',
                        'razon' => "Peticiones pendientes ($totalPendientes) menor al umbral ($umbral)"
                    ];
                    continue;
                }
                
                // Enviar notificación
                $enviado = $emailService->enviarNotificacionPeticionesPendientes(
                    $usuario,
                    $estadisticas,
                    $usuario['nombre_unidad'],
                    $usuario['FiltrarPorMunicipio']
                );
                
                // Registrar en historial
                $estado = $enviado ? 'enviado' : 'fallido';
                $queryHistorial = "INSERT INTO NotificacionHistorial 
                                  (IdUsuario, IdUnidad, Email, TipoNotificacion, CantidadPeticionesPendientes,
                                   CantidadPeticionesNuevas, Asunto, Estado)
                                   VALUES 
                                  (:idUsuario, :idUnidad, :email, 'peticiones_pendientes', :cantidadPendientes,
                                   :cantidadNuevas, :asunto, :estado)";
                
                $stmtHistorial = $db->prepare($queryHistorial);
                $asunto = "Peticiones Pendientes: {$totalPendientes} de {$usuario['nombre_unidad']}";
                
                $stmtHistorial->execute([
                    ':idUsuario' => $usuario['usuario_id'],
                    ':idUnidad' => $usuario['IdUnidad'],
                    ':email' => $usuario['Email'],
                    ':cantidadPendientes' => $totalPendientes,
                    ':cantidadNuevas' => intval($estadisticas['esperando_recepcion'] ?? 0),
                    ':asunto' => $asunto,
                    ':estado' => $estado
                ]);
                
                if ($enviado) {
                    $resultados['enviados']++;
                    $resultados['detalles'][] = [
                        'usuario' => $usuario['Usuario'],
                        'email' => $usuario['Email'],
                        'estado' => 'enviado',
                        'peticiones_pendientes' => $totalPendientes
                    ];
                } else {
                    $resultados['fallidos']++;
                    $resultados['detalles'][] = [
                        'usuario' => $usuario['Usuario'],
                        'email' => $usuario['Email'],
                        'estado' => 'fallido',
                        'razon' => 'Error al enviar correo'
                    ];
                }
                
            } catch (Exception $e) {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'usuario' => $usuario['Usuario'],
                    'email' => $usuario['Email'],
                    'estado' => 'error',
                    'razon' => $e->getMessage()
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Proceso completado: {$resultados['enviados']} enviados, {$resultados['fallidos']} fallidos, {$resultados['omitidos']} omitidos",
            'resultados' => $resultados
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error al procesar notificaciones',
            'message' => $e->getMessage()
        ]);
    }
} else if ($method === 'GET') {
    /**
     * GET - Obtener historial de notificaciones
     * Parámetros opcionales:
     * - limit: Número de registros (default: 50)
     */
    try {
        if (!$esLlamadaManual) {
            http_response_code(403);
            echo json_encode(['error' => 'Solo usuarios autenticados pueden ver el historial']);
            exit();
        }
        
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $userId = $_SESSION['user_id'];
        
        // Verificar si el usuario tiene permisos (puede ver su propio historial o ser admin)
        $query = "SELECT 
                    nh.*,
                    u.Usuario,
                    u.Nombre,
                    u.ApellidoP,
                    uni.nombre_unidad
                  FROM NotificacionHistorial nh
                  INNER JOIN Usuario u ON nh.IdUsuario = u.Id
                  LEFT JOIN unidades uni ON nh.IdUnidad = uni.id
                  WHERE nh.IdUsuario = :userId
                  ORDER BY nh.FechaEnvio DESC
                  LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $historial,
            'total' => count($historial)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener historial', 'message' => $e->getMessage()]);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
