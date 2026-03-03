<?php
header('Content-Type: application/json; charset=utf-8');

// Cargar variables del .env
require_once __DIR__ . '/config/env.php';

echo "🔧 Configuración SMTP Actual:\n\n";
echo "✅ SMTP_HOST: " . getenv('SMTP_HOST') . "\n";
echo "✅ SMTP_PORT: " . getenv('SMTP_PORT') . "\n";
echo "✅ SMTP_USERNAME: " . getenv('SMTP_USERNAME') . "\n";
echo "✅ SMTP_PASSWORD: " . (getenv('SMTP_PASSWORD') ? str_repeat('*', strlen(getenv('SMTP_PASSWORD'))) : 'NO CONFIGURADO') . "\n";
echo "✅ SMTP_FROM_EMAIL: " . getenv('SMTP_FROM_EMAIL') . "\n";
echo "✅ SMTP_FROM_NAME: " . getenv('SMTP_FROM_NAME') . "\n";
echo "✅ CRON_TOKEN: " . (getenv('CRON_TOKEN') ? substr(getenv('CRON_TOKEN'), 0, 8) . '...' : 'NO CONFIGURADO') . "\n";
echo "✅ APP_URL: " . getenv('APP_URL') . "\n\n";

// Prueba de envío real
echo "📧 Probando envío de email...\n\n";

require_once __DIR__ . '/api/services/EmailService.php';

$emailService = new EmailService();
$testEmail = getenv('SMTP_USERNAME'); // Enviar al mismo email de prueba

try {
    $result = $emailService->enviarCorreoPrueba($testEmail, 'Administrador SISEE');
    
    if ($result) {
        echo "✅ EMAIL ENVIADO EXITOSAMENTE\n";
        echo "📬 Revisa la bandeja de: " . $testEmail . "\n";
        echo "⚠️  Revisa también la carpeta de SPAM\n\n";
        echo "🎉 ¡Configuración SMTP funcionando correctamente!\n";
    } else {
        echo "❌ ERROR al enviar el email\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "📝 Detalles: " . $e->getTraceAsString() . "\n";
}
