<?php
/**
 * Prueba rápida de notificación de asignación
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/api/services/EmailService.php';

$database = new Database();
$db = $database->getConnection();

echo "=== PRUEBA DE NOTIFICACIÓN DE ASIGNACIÓN ===\n\n";

// Usa estos valores para la prueba:
// Cambia los IDs según tu petición y departamento de prueba
$peticionId = 1;  // Cambia esto
$departamentoId = 2; // ID del departamento (unidades.id) - usa el que tenga el usuario "salud"

echo "Pet ición ID: $peticionId\n";
echo "Departamento ID: $departamentoId\n\n";

try {
    $emailService = new EmailService();
    $resultado = $emailService->enviarNotificacionAsignacion($peticionId, $departamentoId, $db);
    
    if ($resultado) {
        echo "✅ Notificación enviada exitosamente!\n";
    } else {
        echo "❌ No se envió la notificación\n";
        echo "   Revisa /var/www/sisee/api/logs/email.log para más detalles\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
