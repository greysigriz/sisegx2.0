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
                <div class="detail-item">
                    <span class="detail-label">🔄 Devuelto a seguimiento</span>
                    <span class="detail-value">{$devueltas}</span>
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
- Devuelto a seguimiento: {$devueltas}

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
