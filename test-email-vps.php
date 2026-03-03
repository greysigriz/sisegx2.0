<?php
/**
 * Prueba de envío de correo desde el VPS
 * Ejecutar: php test-email-vps.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PRUEBA DE EMAIL EN VPS ===\n\n";

// Verificar que existe el autoloader
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("❌ ERROR: No se encontró el autoloader de Composer en: $autoloadPath\n");
}
echo "✅ Autoloader encontrado\n";

// Cargar autoloader
require_once $autoloadPath;
echo "✅ Autoloader cargado\n";

// Verificar que existe config/env.php
$envConfigPath = __DIR__ . '/config/env.php';
if (!file_exists($envConfigPath)) {
    die("❌ ERROR: No se encontró config/env.php en: $envConfigPath\n");
}
echo "✅ config/env.php encontrado\n";

// Cargar configuración de entorno
require_once $envConfigPath;
echo "✅ Configuración de entorno cargada\n\n";

// Mostrar configuración SMTP (sin mostrar password)
echo "📧 Configuración SMTP:\n";
echo "   SMTP_HOST: " . (getenv('SMTP_HOST') ?: 'NO CONFIGURADO') . "\n";
echo "   SMTP_PORT: " . (getenv('SMTP_PORT') ?: 'NO CONFIGURADO') . "\n";
echo "   SMTP_USERNAME: " . (getenv('SMTP_USERNAME') ?: 'NO CONFIGURADO') . "\n";
echo "   SMTP_PASSWORD: " . (getenv('SMTP_PASSWORD') ? '********' : 'NO CONFIGURADO') . "\n";
echo "   SMTP_FROM_EMAIL: " . (getenv('SMTP_FROM_EMAIL') ?: 'NO CONFIGURADO') . "\n";
echo "   SMTP_FROM_NAME: " . (getenv('SMTP_FROM_NAME') ?: 'NO CONFIGURADO') . "\n\n";

// Verificar que existe EmailService
$emailServicePath = __DIR__ . '/api/services/EmailService.php';
if (!file_exists($emailServicePath)) {
    die("❌ ERROR: No se encontró EmailService.php en: $emailServicePath\n");
}
echo "✅ EmailService.php encontrado\n";

// Cargar EmailService
try {
    require_once $emailServicePath;
    echo "✅ EmailService cargado\n\n";
} catch (Exception $e) {
    die("❌ ERROR cargando EmailService: " . $e->getMessage() . "\n");
}

// Intentar crear instancia de EmailService
try {
    $emailService = new EmailService();
    echo "✅ EmailService instanciado correctamente\n\n";
} catch (Exception $e) {
    die("❌ ERROR creando instancia de EmailService: " . $e->getMessage() . "\n");
}

// Intentar enviar email de prueba
echo "📨 Intentando enviar email de prueba...\n";
$emailDestinatario = getenv('SMTP_FROM_EMAIL') ?: 'test@example.com';

try {
    $resultado = $emailService->enviarCorreoPrueba($emailDestinatario, 'Prueba VPS');
    
    if ($resultado) {
        echo "✅ ¡Email enviado exitosamente a $emailDestinatario!\n";
        echo "   Revisa la bandeja de entrada (y spam)\n";
    } else {
        echo "❌ El envío del email falló\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR enviando email: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
