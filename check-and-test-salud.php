<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "=== CONFIGURACIÓN DEL USUARIO SALUD ===\n\n";

// Ver usuario salud
$stmt = $db->query("SELECT u.Id, u.Usuario, u.IdUnidad, uni.nombre_unidad, nc.NotificacionesActivas, u.Email
                    FROM Usuario u 
                    LEFT JOIN unidades uni ON u.IdUnidad = uni.id 
                    LEFT JOIN NotificacionConfiguracion nc ON u.Id = nc.IdUsuario 
                    WHERE u.Usuario = 'salud'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "Usuario: " . $result['Usuario'] . "\n";
    echo "Email: " . ($result['Email'] ?? 'NULL') . "\n";
    echo "IdUnidad: " . ($result['IdUnidad'] ?? 'NULL') . "\n";
    echo "Nombre Unidad: " . ($result['nombre_unidad'] ?? 'NULL') . "\n";
    echo "NotificacionesActivas: " . ($result['NotificacionesActivas'] !== null ? $result['NotificacionesActivas'] : 'NO CONFIGURADO') . "\n\n";
    
    if ($result['NotificacionesActivas'] != 1) {
        echo "❌ PROBLEMA: NotificacionesActivas NO está en 1\n";
        echo "   Solución: Actualizar la configuración\n\n";
        
        // Activar notificaciones
        $updateQuery = "UPDATE NotificacionConfiguracion SET NotificacionesActivas = 1 WHERE IdUsuario = ?";
        $stmtUpdate = $db->prepare($updateQuery);
        if ($stmtUpdate->execute([$result['Id']])) {
            echo "✅ Notificaciones activadas para el usuario salud\n";
        }
    } else {
        echo "✅ Notificaciones activas\n";
    }
    
    // Ahora prueba enviar notificación con el departamento correcto
    if ($result['IdUnidad']) {
        echo "\n=== ENVIANDO NOTIFICACIÓN DE PRUEBA ===\n";
        echo "Departamento ID: " . $result['IdUnidad'] . "\n";
        
        require_once 'api/services/EmailService.php';
        
        // Usar petición más reciente
        $stmtPet = $db->query("SELECT id FROM peticiones ORDER BY id DESC LIMIT 1");
        $pet = $stmtPet->fetch(PDO::FETCH_ASSOC);
        
        if ($pet) {
            echo "Petición ID: " . $pet['id'] . "\n";
            
            $emailService = new EmailService();
            $resultado = $emailService->enviarNotificacionAsignacion($pet['id'], $result['IdUnidad'], $db);
            
            if ($resultado) {
                echo "\n✅ ¡Notificación enviada exitosamente!\n";
                echo "Revisa el email: " . $result['Email'] . "\n";
            } else {
                echo "\n❌ No se pudo enviar la notificación\n";
                echo "Revisa /var/www/sisee/api/logs/email.log\n";
            }
        }
    }
    
} else {
    echo "Usuario no encontrado\n";
}
