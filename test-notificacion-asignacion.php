<?php
/**
 * Script de prueba para verificar la configuración de notificaciones inmediatas
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/api/services/EmailService.php';

$database = new Database();
$db = $database->getConnection();

echo "=== PRUEBA DE NOTIFICACIÓN DE ASIGNACIÓN ===\n\n";

// 1. Verificar usuarios con notificaciones activas por departamento
echo "1. Usuarios con notificaciones activas (Rol Departamento):\n";
echo str_repeat("-", 80) . "\n";

$queryUsuarios = "SELECT 
                    u.Id,
                    u.Usuario,
                    u.Nombre,
                    u.ApellidoP,
                    u.Email,
                    u.IdUnidad,
                    uni.nombre_unidad,
                   nc.NotificacionesActivas
                  FROM Usuario u
                  INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
                  LEFT JOIN NotificacionConfiguracion nc ON u.Id = nc.IdUsuario
                  LEFT JOIN unidades uni ON u.IdUnidad = uni.id
                  WHERE ur.IdRolSistema = 9
                    AND u.Email IS NOT NULL
                    AND u.Email != ''
                  ORDER BY u.IdUnidad";

$stmtUsuarios = $db->prepare($queryUsuarios);
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

if (empty($usuarios)) {
    echo "❌ No hay usuarios con rol Departamento que tengan email configurado\n";
} else {
    foreach ($usuarios as $usuario) {
        $activo = $usuario['NotificacionesActivas'] == 1 ? '✅' : '❌';
        $configStatus = $usuario['NotificacionesActivas'] !== null ? 'Configurado' : '⚠️ Sin configurar';
        echo sprintf(
            "%s Usuario: %-15s | Email: %-30s | Unidad: %-30s | Notif: %s\n",
            $activo,
            $usuario['Usuario'],
            $usuario['Email'] ?: 'Sin email',
            $usuario['nombre_unidad'] ?: 'Sin unidad (ID: ' . $usuario['IdUnidad'] . ')',
            $configStatus
        );
    }
}

// 2. Verificar configuración de email
echo "\n2. Configuración de envío de emails:\n";
echo str_repeat("-", 80) . "\n";

$verificacion = EmailService::verificarConfiguracion();
if ($verificacion['configurado']) {
    echo "✅ Configuración de email completa\n";
    echo "   - SMTP Host: " . (getenv('SMTP_HOST') ?: 'No configurado') . "\n";
    echo "   - SMTP User: " . (getenv('SMTP_USERNAME') ?: 'No configurado') . "\n";
} else {
    echo "❌ Configuración de email incompleta\n";
    echo "   Faltantes: " . implode(', ', $verificacion['faltantes']) . "\n";
}

// 3. Ver últimas peticiones asignadas
echo "\n3. Últimas 5 asignaciones de peticiones:\n";
echo str_repeat("-", 80) . "\n";

$queryAsignaciones = "SELECT 
                        pd.id,
                        pd.peticion_id,
                        pd.departamento_id,
                        pd.fecha_asignacion,
                        pd.estado,
                        uni.nombre_unidad,
                        p.Titulo
                      FROM peticion_departamento pd
                      LEFT JOIN unidades uni ON pd.departamento_id = uni.id
                      LEFT JOIN peticiones p ON pd.peticion_id = p.id
                      ORDER BY pd.fecha_asignacion DESC
                      LIMIT 5";

$stmtAsignaciones = $db->prepare($queryAsignaciones);
$stmtAsignaciones->execute();
$asignaciones = $stmtAsignaciones->fetchAll(PDO::FETCH_ASSOC);

if (empty($asignaciones)) {
    echo "No hay asignaciones recientes\n";
} else {
    foreach ($asignaciones as $asig) {
        echo sprintf(
            "ID: %-4d | Petición: %-4d | Depto: %-30s | Fecha: %s | Estado: %s\n",
            $asig['id'],
            $asig['peticion_id'],
            $asig['nombre_unidad'] ?: 'ID: ' . $asig['departamento_id'],
            $asig['fecha_asignacion'],
            $asig['estado']
        );
        if ($asig['Titulo']) {
            echo "         Título: " . substr($asig['Titulo'], 0, 60) . "\n";
        }
    }
}

// 4. Instrucciones para prueba
echo "\n4. Para probar la notificación inmediata:\n";
echo str_repeat("-", 80) . "\n";
echo "- Asigna una petición a un departamento que tenga un usuario con:\n";
echo "  1. Rol de Departamento (RolId = 9)\n";
echo "  2. Email configurado\n";
echo "  3. Notificaciones activas en NotificacionConfiguracion\n";
echo "  4. IdUnidad coincidente con el departamento asignado\n";
echo "\n- El email debería enviarse inmediatamente después de la asignación\n";
echo "- Revisa /var/www/sisee/api/logs/email.log para ver el registro\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
