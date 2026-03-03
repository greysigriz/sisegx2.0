<?php
// C:\xampp\htdocs\SISEE\api\cron\notificaciones-diarias.php
/**
 * Script de cron job para enviar notificaciones diarias
 * 
 * Configuración recomendada en crontab:
 * # Ejecutar diariamente a las 8:00 AM
 * 0 8 * * * /usr/bin/php /ruta/a/SISEE/api/cron/notificaciones-diarias.php
 * 
 * NOTA: Este script también puede ejecutarse vía HTTP con autenticación:
 * curl -X POST "https://tu-dominio.com/api/enviar-notificaciones.php?cron_token=TU_TOKEN_SEGURO"
 */

// Asegurar que el script solo se ejecuta desde CLI o con token válido
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde la línea de comandos (CLI)');
}

// Establecer zona horaria
date_default_timezone_set('America/Mexico_City');

// Cargar configuración
require_once dirname(__DIR__, 2) . '/config/env.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/services/EmailService.php';

echo "[" . date('Y-m-d H:i:s') . "] Iniciando proceso de notificaciones diarias\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar configuración de email
    $verificacion = EmailService::verificarConfiguracion();
    if (!$verificacion['configurado']) {
        echo "[ERROR] Configuración de email incompleta. Faltantes: " . implode(', ', $verificacion['faltantes']) . "\n";
        exit(1);
    }
    
    $emailService = new EmailService();
    
    // Obtener hora actual
    $horaActual = date('H:i:s');
    
    // Obtener usuarios que deben recibir notificaciones
    // Solo los que tienen frecuencia 'diaria' o 'inmediata' y su hora de envío coincide
    $query = "SELECT 
                u.Id AS usuario_id,
                u.Usuario,
                u.Nombre,
                u.ApellidoP,
                u.Email,
                u.IdUnidad,
                u.IdDivisionAdm AS municipio_id,
                nc.FiltrarPorMunicipio,
                nc.UmbralPeticionesPendientes,
                nc.HoraEnvio,
                nc.FrecuenciaNotificacion,
                uni.nombre_unidad
              FROM Usuario u
              INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
              INNER JOIN NotificacionConfiguracion nc ON u.Id = nc.IdUsuario
              INNER JOIN unidades uni ON u.IdUnidad = uni.id
              WHERE ur.IdRolSistema = 9
                AND nc.NotificacionesActivas = 1
                AND nc.FrecuenciaNotificacion = 'diaria'
                AND TIME(nc.HoraEnvio) <= :horaActual
                AND u.Email IS NOT NULL
                AND u.Email != ''
                AND u.IdUnidad IS NOT NULL";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':horaActual', $horaActual);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Usuarios a notificar: " . count($usuarios) . "\n";
    
    if (empty($usuarios)) {
        echo "No hay usuarios configurados para recibir notificaciones en este momento\n";
        exit(0);
    }
    
    $contadores = [
        'enviados' => 0,
        'fallidos' => 0,
        'omitidos' => 0
    ];
    
    // Procesar cada usuario
    foreach ($usuarios as $usuario) {
        echo "\nProcesando usuario: {$usuario['Usuario']} ({$usuario['Email']})...\n";
        
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
            
            // Verificar umbral
            $totalPendientes = intval($estadisticas['total_pendientes'] ?? 0);
            $umbral = intval($usuario['UmbralPeticionesPendientes'] ?? 0);
            
            echo "  Peticiones pendientes: {$totalPendientes}, Umbral: {$umbral}\n";
            
            if ($totalPendientes < $umbral) {
                echo "  [OMITIDO] No cumple con el umbral mínimo\n";
                $contadores['omitidos']++;
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
                echo "  [ENVIADO] Notificación enviada correctamente\n";
                $contadores['enviados']++;
            } else {
                echo "  [FALLIDO] Error al enviar notificación\n";
                $contadores['fallidos']++;
            }
            
        } catch (Exception $e) {
            echo "  [ERROR] " . $e->getMessage() . "\n";
            $contadores['fallidos']++;
        }
    }
    
    echo "\n=================================\n";
    echo "RESUMEN DEL PROCESO:\n";
    echo "  Enviados: {$contadores['enviados']}\n";
    echo "  Fallidos: {$contadores['fallidos']}\n";
    echo "  Omitidos: {$contadores['omitidos']}\n";
    echo "=================================\n";
    echo "[" . date('Y-m-d H:i:s') . "] Proceso finalizado\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "[FATAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
