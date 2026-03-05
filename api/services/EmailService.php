<?php
// C:\xampp\htdocs\SISEE\api\services\EmailService.php
/**
 * Servicio de envío de correos electrónicos usando PHPMailer
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $fromEmail;
    private $fromName;
    
    /**
     * Constructor - Configura PHPMailer con credenciales SMTP
     */
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Configuración del servidor SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com'; // Servidor SMTP
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = getenv('SMTP_USERNAME') ?: ''; // Tu email SMTP
            $this->mailer->Password   = getenv('SMTP_PASSWORD') ?: ''; // Tu contraseña SMTP o App Password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = getenv('SMTP_PORT') ?: 587;
            $this->mailer->CharSet    = 'UTF-8';
            
            // Configuración del remitente
            $this->fromEmail = getenv('SMTP_FROM_EMAIL') ?: getenv('SMTP_USERNAME');
            $this->fromName  = getenv('SMTP_FROM_NAME') ?: 'Sistema de Gestión SISEE';
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            
            // Configuraciones adicionales
            $this->mailer->isHTML(true);
            
        } catch (Exception $e) {
            error_log("Error configurando EmailService: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Enviar notificación de peticiones pendientes a un usuario
     * 
     * @param array $usuario Datos del usuario (debe incluir: Email, Nombre, ApellidoP)
     * @param array $estadisticas Estadísticas de peticiones (total_pendientes, esperando_recepcion, etc.)
     * @param string $nombreUnidad Nombre de la unidad/departamento
     * @param bool $filtradoPorMunicipio Si se filtraron las peticiones por municipio
     * @return bool True si se envió correctamente, false en caso contrario
     */
    public function enviarNotificacionPeticionesPendientes($usuario, $estadisticas, $nombreUnidad, $filtradoPorMunicipio = false) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Destinatario
            $nombreCompleto = trim($usuario['Nombre'] . ' ' . ($usuario['ApellidoP'] ?? ''));
            $this->mailer->addAddress($usuario['Email'], $nombreCompleto);
            
            // Asunto
            $totalPendientes = $estadisticas['total_pendientes'] ?? 0;
            $this->mailer->Subject = "📋 Peticiones Pendientes: {$totalPendientes} de {$nombreUnidad}";
            
            // Cuerpo del correo en HTML
            $body = $this->generarHTMLNotificacionPendientes($usuario, $estadisticas, $nombreUnidad, $filtradoPorMunicipio);
            $this->mailer->Body = $body;
            
            // Alternativa en texto plano
            $this->mailer->AltBody = $this->generarTextoPlanoNotificacionPendientes($usuario, $estadisticas, $nombreUnidad, $filtradoPorMunicipio);
            
            // Enviar
            $resultado = $this->mailer->send();
            
            if ($resultado) {
                $this->logEmail("Notificación enviada a: {$usuario['Email']} - {$totalPendientes} peticiones pendientes");
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            $this->logEmail("Error enviando notificación a {$usuario['Email']}: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Generar HTML del email para notificación de peticiones pendientes
     */
    private function generarHTMLNotificacionPendientes($usuario, $estadisticas, $nombreUnidad, $filtradoPorMunicipio) {
        $nombreCompleto = trim($usuario['Nombre'] . ' ' . ($usuario['ApellidoP'] ?? ''));
        $totalPendientes = $estadisticas['total_pendientes'] ?? 0;
        $esperandoRecepcion = $estadisticas['esperando_recepcion'] ?? 0;
        $enProceso = $estadisticas['en_proceso'] ?? 0;
        $devueltas = $estadisticas['devueltas'] ?? 0;
        $peticionMasAntigua = $estadisticas['peticion_mas_antigua'] ?? null;
        $municipioFiltro = $filtradoPorMunicipio ? ' (filtrado por tu municipio)' : ' (todas las peticiones)';
        
        // Calcular días desde la petición más antigua
        $diasPendiente = '';
        if ($peticionMasAntigua) {
            $fechaAntigua = new DateTime($peticionMasAntigua);
            $hoy = new DateTime();
            $diferencia = $hoy->diff($fechaAntigua);
            $diasPendiente = $diferencia->days;
        }
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Peticiones Pendientes</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #0074D9, #0056b3);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }
        .stats-container {
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            border-left: 4px solid #0074D9;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 4px;
        }
        .stat-card.warning {
            border-left-color: #FFA500;
            background: #fff8f0;
        }
        .stat-card.alert {
            border-left-color: #dc3545;
            background: #fff0f0;
        }
        .stat-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #0074D9;
        }
        .stat-card.warning .stat-value {
            color: #FFA500;
        }
        .stat-card.alert .stat-value {
            color: #dc3545;
        }
        .detail-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #666;
            font-size: 14px;
        }
        .detail-value {
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background: #0074D9;
            color: white;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .footer a {
            color: #0074D9;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Resumen de Peticiones Pendientes</h1>
            <p>{$nombreUnidad}{$municipioFiltro}</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hola <strong>{$nombreCompleto}</strong>,
            </div>
            
            <p>Te informamos sobre el estado actual de las peticiones asignadas a tu departamento:</p>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-title">Total de Peticiones Pendientes</div>
                    <div class="stat-value">{$totalPendientes}</div>
                </div>
            </div>
            
            <div class="detail-list">
                <h3 style="margin-top: 0; color: #333;">Desglose por Estado</h3>
                <div class="detail-item">
                    <span class="detail-label">⏳ Esperando recepción</span>
                    <span class="detail-value">{$esperandoRecepcion}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">⚙️ Aceptado en proceso</span>
                    <span class="detail-value">{$enProceso}</span>
                </div>
HTML;

        if ($diasPendiente !== '') {
            $colorClase = $diasPendiente > 30 ? 'alert' : ($diasPendiente > 15 ? 'warning' : '');
            if ($colorClase) {
                $colorHex = $colorClase === 'alert' ? '#dc3545' : '#FFA500';
                $html .= <<<HTML
                <div class="detail-item">
                    <span class="detail-label">⚠️ Petición más antigua</span>
                    <span class="detail-value" style="color: {$colorHex};">{$diasPendiente} días</span>
                </div>
HTML;
            }
        }

        $html .= <<<HTML
            </div>
            
            <div style="text-align: center;">
                <a href="https://tu-dominio.com/peticiones" class="cta-button">
                    Ver Peticiones en el Sistema
                </a>
            </div>
            
            <p style="font-size: 14px; color: #666; margin-top: 20px;">
                <strong>Nota:</strong> Esta notificación se genera automáticamente según tu configuración de notificaciones. 
                Puedes modificar la frecuencia de envío en la configuración de tu perfil.
            </p>
        </div>
        
        <div class="footer">
            <p>Sistema de Gestión de Peticiones SISEE</p>
            <p>
                <a href="#">Configurar notificaciones</a> | 
                <a href="#">Soporte</a>
            </p>
            <p style="margin-top: 10px; color: #999;">
                Este es un correo automático, por favor no responder.
            </p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }
    
    /**
     * Generar texto plano del email para clientes que no soportan HTML
     */
    private function generarTextoPlanoNotificacionPendientes($usuario, $estadisticas, $nombreUnidad, $filtradoPorMunicipio) {
        $nombreCompleto = trim($usuario['Nombre'] . ' ' . ($usuario['ApellidoP'] ?? ''));
        $totalPendientes = $estadisticas['total_pendientes'] ?? 0;
        $esperandoRecepcion = $estadisticas['esperando_recepcion'] ?? 0;
        $enProceso = $estadisticas['en_proceso'] ?? 0;
        $devueltas = $estadisticas['devueltas'] ?? 0;
        $municipioFiltro = $filtradoPorMunicipio ? ' (filtrado por tu municipio)' : ' (todas las peticiones)';
        
        $texto = <<<TEXT
RESUMEN DE PETICIONES PENDIENTES
{$nombreUnidad}{$municipioFiltro}

Hola {$nombreCompleto},

Te informamos sobre el estado actual de las peticiones asignadas a tu departamento:

TOTAL DE PETICIONES PENDIENTES: {$totalPendientes}

DESGLOSE POR ESTADO:
- Esperando recepción: {$esperandoRecepcion}
- Aceptado en proceso: {$enProceso}

Para ver más detalles, accede al sistema de gestión de peticiones.

---
Sistema de Gestión de Peticiones SISEE
Este es un correo automático, por favor no responder.
TEXT;

        return $texto;
    }
    
    /**
     * Enviar notificación de prueba
     */
    public function enviarCorreoPrueba($email, $nombreDestinatario = 'Usuario') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $nombreDestinatario);
            
            $this->mailer->Subject = 'Prueba de Configuración de Notificaciones - SISEE';
            
            $this->mailer->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .header { background: #0074D9; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✅ Correo de Prueba</h2>
        </div>
        <div class="content">
            <p>Hola <strong>{$nombreDestinatario}</strong>,</p>
            <p>Este es un correo de prueba para verificar que tu configuración de notificaciones está funcionando correctamente.</p>
            <p>Si recibiste este correo, significa que el sistema está configurado correctamente y recibirás notificaciones sobre las peticiones de tu departamento.</p>
            <hr>
            <p style="font-size: 12px; color: #666;">Sistema de Gestión de Peticiones SISEE</p>
        </div>
    </div>
</body>
</html>
HTML;
            
            $this->mailer->AltBody = "Hola {$nombreDestinatario},\n\nEste es un correo de prueba para verificar tu configuración de notificaciones.\n\nSistema de Gestión SISEE";
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            $this->logEmail("Error enviando correo de prueba: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Enviar notificación inmediata cuando se asigna una petición a un departamento
     * 
     * @param int $peticionId ID de la petición asignada
     * @param int $departamentoId ID del departamento al que se asigna
     * @param PDO $db Conexión a la base de datos
     * @return bool True si se envió correctamente, false en caso contrario
     */
    public function enviarNotificacionAsignacion($peticionId, $departamentoId, $db) {
        try {
            // Obtener usuarios del departamento con notificaciones activas
            $queryUsuarios = "SELECT 
                                u.Id,
                                u.Email,
                                u.Nombre,
                                u.ApellidoP,
                                uni.nombre_unidad
                              FROM Usuario u
                              INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
                              INNER JOIN NotificacionConfiguracion nc ON u.Id = nc.IdUsuario
                              INNER JOIN unidades uni ON u.IdUnidad = uni.id
                              WHERE u.IdUnidad = :departamentoId
                                AND ur.IdRolSistema = 9
                                AND nc.NotificacionesActivas = 1
                                AND u.Email IS NOT NULL
                                AND u.Email != ''";
            
            $stmtUsuarios = $db->prepare($queryUsuarios);
            $stmtUsuarios->bindParam(':departamentoId', $departamentoId, PDO::PARAM_INT);
            $stmtUsuarios->execute();
            $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($usuarios)) {
                $this->logEmail("No hay usuarios con notificaciones activas en departamento $departamentoId");
                return false;
            }
            
            // Obtener información de la petición
            $queryPeticion = "SELECT 
                                p.id,
                                p.Titulo,
                                p.Descripcion,
                                p.Ciudadano,
                                p.NivelImportancia,
                                p.FechaCreacion,
                                d.NombreDivision as municipio
                              FROM peticiones p
                              LEFT JOIN divisiones d ON p.IdMunicipio = d.Id
                              WHERE p.id = :peticionId";
            
            $stmtPeticion = $db->prepare($queryPeticion);
            $stmtPeticion->bindParam(':peticionId', $peticionId, PDO::PARAM_INT);
            $stmtPeticion->execute();
            $peticion = $stmtPeticion->fetch(PDO::FETCH_ASSOC);
            
            if (!$peticion) {
                $this->logEmail("Petición $peticionId no encontrada", 'ERROR');
                return false;
            }
            
            // Enviar notificación a cada usuario del departamento
            $enviados = 0;
            foreach ($usuarios as $usuario) {
                if ($this->enviarCorreoAsignacion($usuario, $peticion)) {
                    $enviados++;
                }
            }
            
            $this->logEmail("Notificación de asignación enviada a $enviados usuarios del departamento $departamentoId");
            return $enviados > 0;
            
        } catch (Exception $e) {
            $this->logEmail("Error enviando notificación de asignación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Enviar correo individual de asignación
     */
    private function enviarCorreoAsignacion($usuario, $peticion) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $nombreCompleto = trim($usuario['Nombre'] . ' ' . ($usuario['ApellidoP'] ?? ''));
            $this->mailer->addAddress($usuario['Email'], $nombreCompleto);
            
            $nivelUrgencia = $peticion['NivelImportancia'] == 1 ? '🔴 ALTA' : ($peticion['NivelImportancia'] == 2 ? '🟡 MEDIA' : '🟢 BAJA');
            
            $this->mailer->Subject = "🆕 Nueva Petición Asignada: {$peticion['Titulo']}";
            
            $this->mailer->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 650px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info-row { display: flex; margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .info-label { font-weight: bold; min-width: 140px; color: #495057; }
        .info-value { color: #212529; }
        .urgency-high { color: #dc3545; font-weight: bold; }
        .urgency-medium { color: #ffc107; font-weight: bold; }
        .urgency-low { color: #28a745; font-weight: bold; }
        .description-box { background: #e9ecef; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
        .cta-button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🆕 Nueva Petición Asignada</h1>
            <p style="margin: 10px 0 0 0; font-size: 14px;">Se ha asignado una nueva petición a {$usuario['nombre_unidad']}</p>
        </div>
        <div class="content">
            <div class="alert-box">
                <strong>⚠️ Acción Requerida:</strong> Una nueva petición ha sido asignada a tu departamento y requiere tu atención.
            </div>
            
            <div class="info-row">
                <span class="info-label">📋 Petición:</span>
                <span class="info-value">#{$peticion['id']}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">📌 Título:</span>
                <span class="info-value"><strong>{$peticion['Titulo']}</strong></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">👤 Ciudadano:</span>
                <span class="info-value">{$peticion['Ciudadano']}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">📍 Municipio:</span>
                <span class="info-value">{$peticion['municipio']}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">⚡ Urgencia:</span>
                <span class="info-value">{$nivelUrgencia}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">📅 Fecha:</span>
                <span class="info-value">{$peticion['FechaCreacion']}</span>
            </div>
            
            <div class="description-box">
                <strong>📄 Descripción:</strong>
                <p style="margin: 10px 0 0 0;">{$peticion['Descripcion']}</p>
            </div>
            
            <center>
                <a href="http://50.21.181.205" class="cta-button">Ver Petición en el Sistema</a>
            </center>
            
            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 13px; color: #6c757d;">
                <strong>Nota:</strong> Esta es una notificación automática. Por favor, revisa la petición en el sistema y toma las acciones necesarias.
            </p>
        </div>
        <div class="footer">
            <p><strong>Sistema de Gestión de Peticiones SISEE</strong></p>
            <p>Esta notificación fue enviada automáticamente al asignar la petición a tu departamento.</p>
        </div>
    </div>
</body>
</html>
HTML;
            
            $this->mailer->AltBody = "Nueva Petición Asignada\n\n" .
                "Petición: #{$peticion['id']}\n" .
                "Título: {$peticion['Titulo']}\n" .
                "Ciudadano: {$peticion['Ciudadano']}\n" .
                "Municipio: {$peticion['municipio']}\n" .
                "Urgencia: {$nivelUrgencia}\n" .
                "Fecha: {$peticion['FechaCreacion']}\n\n" .
                "Descripción:\n{$peticion['Descripcion']}\n\n" .
                "Departamento: {$usuario['nombre_unidad']}\n\n" .
                "Por favor, revisa la petición en el sistema.";
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            $this->logEmail("Error enviando correo de asignación a {$usuario['Email']}: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Log de actividad de emails
     */
    private function logEmail($message, $level = 'INFO') {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/email.log';
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[$timestamp] [$level] $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Verificar si las credenciales SMTP están configuradas
     */
    public static function verificarConfiguracion() {
        $requeridos = ['SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD'];
        $faltantes = [];
        
        foreach ($requeridos as $var) {
            if (!getenv($var)) {
                $faltantes[] = $var;
            }
        }
        
        return [
            'configurado' => empty($faltantes),
            'faltantes' => $faltantes
        ];
    }
}
