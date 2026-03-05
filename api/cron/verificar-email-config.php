<?php
/**
 * Script para verificar la configuración de email
 * Verifica que todas las variables necesarias estén configuradas
 */

require_once dirname(__DIR__, 2) . '/config/env.php';
require_once dirname(__DIR__) . '/services/EmailService.php';

echo "=== Verificando Configuración de Email ===\n\n";

$verificacion = EmailService::verificarConfiguracion();

if ($verificacion['configurado']) {
    echo "✓ Configuración de email COMPLETA\n";
    echo "  - SMTP Host: " . (getenv('SMTP_HOST') ?: 'No configurado') . "\n";
    echo "  - SMTP Port: " . (getenv('SMTP_PORT') ?: 'No configurado') . "\n";
    echo "  - SMTP User: " . (getenv('SMTP_USERNAME') ?: 'No configurado') . "\n";
    echo "  - SMTP Pass: " . (getenv('SMTP_PASSWORD') ? '***configurado***' : 'No configurado') . "\n";
    echo "  - Email From: " . (getenv('SMTP_FROM_EMAIL') ?: 'No configurado') . "\n";
    echo "  - Email Name: " . (getenv('SMTP_FROM_NAME') ?: 'No configurado') . "\n";
    echo "\n✓ Sistema listo para enviar notificaciones automáticas\n";
} else {
    echo "✗ Configuración de email INCOMPLETA\n";
    echo "  Faltantes:\n";
    foreach ($verificacion['faltantes'] as $faltante) {
        echo "    - $faltante\n";
    }
    echo "\nDebes configurar estas variables en el archivo .env del servidor\n";
    exit(1);
}

// Verificar CRON_TOKEN
echo "\n=== Verificando Token de Cron ===\n";
$cronToken = getenv('CRON_TOKEN');
if ($cronToken) {
    echo "✓ CRON_TOKEN configurado: " . substr($cronToken, 0, 10) . "...\n";
} else {
    echo "✗ CRON_TOKEN no configurado\n";
    echo "  Genera uno con: openssl rand -hex 32\n";
    echo "  Y agrégalo al archivo .env\n";
}

echo "\n=== Verificación completada ===\n";
