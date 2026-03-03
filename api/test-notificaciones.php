<?php
// C:\xampp\htdocs\SISEE\api\test-notificaciones.php
/**
 * Script de prueba para verificar la configuración del sistema de notificaciones
 * Uso: php api/test-notificaciones.php
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/services/EmailService.php';

echo "===========================================\n";
echo "SISTEMA DE NOTIFICACIONES - PRUEBA\n";
echo "===========================================\n\n";

// 1. Verificar variables de entorno
echo "1. VERIFICANDO VARIABLES DE ENTORNO...\n";
echo "   -----------------------------------\n";
$verificacion = EmailService::verificarConfiguracion();

if ($verificacion['configurado']) {
    echo "   ✅ Todas las variables están configuradas\n";
    echo "   - SMTP_HOST: " . getenv('SMTP_HOST') . "\n";
    echo "   - SMTP_PORT: " . getenv('SMTP_PORT') . "\n";
    echo "   - SMTP_USERNAME: " . getenv('SMTP_USERNAME') . "\n";
    echo "   - SMTP_PASSWORD: " . (getenv('SMTP_PASSWORD') ? '***configurada***' : 'NO configurada') . "\n";
} else {
    echo "   ❌ Faltan variables de entorno:\n";
    foreach ($verificacion['faltantes'] as $faltante) {
        echo "      - $faltante\n";
    }
    echo "\n   Por favor, configura el archivo .env\n";
    echo "   Usa .env.example como plantilla\n";
    exit(1);
}

// 2. Verificar conexión a base de datos
echo "\n2. VERIFICANDO CONEXIÓN A BASE DE DATOS...\n";
echo "   -----------------------------------\n";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "   ✅ Conexión a base de datos exitosa\n";
    
    // Verificar tablas necesarias
    $tablas = ['Usuario', 'NotificacionConfiguracion', 'NotificacionHistorial', 'peticion_departamento'];
    foreach ($tablas as $tabla) {
        $query = "SHOW TABLES LIKE '$tabla'";
        $stmt = $db->query($query);
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Tabla '$tabla' existe\n";
        } else {
            echo "   ❌ Tabla '$tabla' NO existe\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Verificar usuarios con rol 9
echo "\n3. VERIFICANDO USUARIOS CON ROL DE GESTOR (Rol 9)...\n";
echo "   -----------------------------------\n";
try {
    $query = "SELECT 
                u.Id,
                u.Usuario,
                u.Nombre,
                u.Email,
                u.IdUnidad,
                uni.nombre_unidad
              FROM Usuario u
              INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
              LEFT JOIN unidades uni ON u.IdUnidad = uni.id
              WHERE ur.IdRolSistema = 9";
    
    $stmt = $db->query($query);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Usuarios con Rol 9: " . count($usuarios) . "\n\n";
    
    if (count($usuarios) > 0) {
        foreach ($usuarios as $usuario) {
            echo "   Usuario: {$usuario['Usuario']}\n";
            echo "   - Nombre: {$usuario['Nombre']}\n";
            echo "   - Email: " . ($usuario['Email'] ? $usuario['Email'] : '❌ NO configurado') . "\n";
            echo "   - Unidad: " . ($usuario['nombre_unidad'] ? $usuario['nombre_unidad'] : '❌ NO asignada') . "\n";
            
            // Verificar si tiene configuración de notificaciones
            $queryConfig = "SELECT * FROM NotificacionConfiguracion WHERE IdUsuario = :userId";
            $stmtConfig = $db->prepare($queryConfig);
            $stmtConfig->execute([':userId' => $usuario['Id']]);
            $config = $stmtConfig->fetch(PDO::FETCH_ASSOC);
            
            if ($config) {
                echo "   - Notificaciones: " . ($config['NotificacionesActivas'] ? '✅ Activas' : '❌ Inactivas') . "\n";
            } else {
                echo "   - Notificaciones: ❌ Sin configurar\n";
            }
            echo "\n";
        }
    } else {
        echo "   ⚠️  No hay usuarios con Rol 9\n";
        echo "   Asigna el rol 9 a usuarios que gestionan departamentos\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 4. Verificar peticiones pendientes por departamento
echo "\n4. VERIFICANDO PETICIONES PENDIENTES...\n";
echo "   -----------------------------------\n";
try {
    $query = "SELECT 
                u.nombre_unidad,
                COUNT(pd.id) as total_pendientes
              FROM unidades u
              LEFT JOIN peticion_departamento pd ON u.id = pd.departamento_id
              WHERE pd.estado IN ('Esperando recepción', 'Aceptado en proceso', 'Devuelto a seguimiento')
              GROUP BY u.id, u.nombre_unidad
              HAVING total_pendientes > 0
              ORDER BY total_pendientes DESC
              LIMIT 10";
    
    $stmt = $db->query($query);
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($departamentos) > 0) {
        foreach ($departamentos as $dept) {
            echo "   - {$dept['nombre_unidad']}: {$dept['total_pendientes']} peticiones\n";
        }
    } else {
        echo "   ℹ️  No hay peticiones pendientes\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 5. Prueba de envío de email (solo si hay email configurado)
echo "\n5. PRUEBA DE ENVÍO DE EMAIL...\n";
echo "   -----------------------------------\n";
echo "   ¿Deseas enviar un correo de prueba? (s/n): ";
$handle = fopen("php://stdin", "r");
$respuesta = trim(fgets($handle));

if (strtolower($respuesta) === 's') {
    echo "   Ingresa el email de destino: ";
    $emailPrueba = trim(fgets($handle));
    
    if (filter_var($emailPrueba, FILTER_VALIDATE_EMAIL)) {
        try {
            $emailService = new EmailService();
            echo "   Enviando correo a $emailPrueba...\n";
            
            $resultado = $emailService->enviarCorreoPrueba($emailPrueba, 'Usuario de Prueba');
            
            if ($resultado) {
                echo "   ✅ Correo de prueba enviado correctamente\n";
                echo "   Revisa tu bandeja de entrada (y spam)\n";
            } else {
                echo "   ❌ No se pudo enviar el correo\n";
                echo "   Revisa los logs en: api/logs/email.log\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error al enviar: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ❌ Email inválido\n";
    }
}
fclose($handle);

// Resumen final
echo "\n===========================================\n";
echo "RESUMEN\n";
echo "===========================================\n";
echo "Variables de entorno: " . ($verificacion['configurado'] ? '✅' : '❌') . "\n";
echo "Conexión a BD: ✅\n";
echo "Usuarios con Rol 9: " . (count($usuarios ?? []) > 0 ? '✅' : '⚠️') . "\n";
echo "\nPróximos pasos:\n";
echo "1. Configura emails de los usuarios gestores\n";
echo "2. Activa notificaciones desde el frontend\n";
echo "3. Configura cron job para envío automático\n";
echo "\nDocumentación completa en:\n";
echo "documentation/Sistema_Notificaciones_Email.md\n";
echo "===========================================\n";
