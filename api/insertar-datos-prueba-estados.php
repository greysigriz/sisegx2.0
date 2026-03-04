<?php
/**
 * Script para insertar datos de prueba en peticion_departamento
 * para visualizar el gráfico de Tendencia Mensual
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🚀 Iniciando inserción de datos de prueba...\n\n";
    
    // Verificar que la tabla existe
    $checkTable = "SHOW TABLES LIKE 'peticion_departamento'";
    $stmt = $db->prepare($checkTable);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        die("❌ Error: La tabla 'peticion_departamento' no existe.\n");
    }
    
    // Obtener estructura de la tabla
    $columnsQuery = "SHOW COLUMNS FROM peticion_departamento";
    $columnsStmt = $db->prepare($columnsQuery);
    $columnsStmt->execute();
    $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Columnas disponibles en la tabla:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Estados posibles según el gráfico
    $estados = [
        'Esperando recepción',
        'Aceptado en proceso',
        'Devuelto a seguimiento',
        'Rechazado',
        'Completado'
    ];
    
    // Generar datos de prueba para los últimos 30 días
    $registrosInsertados = 0;
    $fechaInicio = date('Y-m-d', strtotime('-30 days'));
    $fechaFin = date('Y-m-d');
    
    echo "📅 Generando datos del $fechaInicio al $fechaFin\n\n";
    
    // Por cada día en el rango
    $fechaActual = strtotime($fechaInicio);
    $fechaFinTimestamp = strtotime($fechaFin);
    
    while ($fechaActual <= $fechaFinTimestamp) {
        $fecha = date('Y-m-d', $fechaActual);
        $hora = date('H:i:s', strtotime('09:00:00') + rand(0, 28800)); // Entre 9:00 y 17:00
        $fechaHora = "$fecha $hora";
        
        // Generar entre 1 y 5 registros por día
        $registrosPorDia = rand(1, 5);
        
        for ($i = 0; $i < $registrosPorDia; $i++) {
            // Seleccionar un estado aleatorio
            $estado = $estados[array_rand($estados)];
            
            // Datos básicos que probablemente estén en la tabla
            $datos = [
                'fecha_asignacion' => $fechaHora,
                'estado' => $estado
            ];
            
            // Intentar insertar con las columnas que probablemente existan
            // Ajustaremos según la estructura real de tu tabla
            try {
                // Query adaptable - solo usa columnas básicas
                $insertQuery = "INSERT INTO peticion_departamento 
                    (fecha_asignacion, estado) 
                    VALUES (:fecha_asignacion, :estado)";
                
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute($datos);
                
                $registrosInsertados++;
                
            } catch (PDOException $e) {
                // Si falla, intentar con más columnas comunes
                if (strpos($e->getMessage(), "doesn't have a default value") !== false) {
                    // Identificar qué columnas faltan
                    preg_match("/Field '(\w+)'/", $e->getMessage(), $matches);
                    if (isset($matches[1])) {
                        echo "⚠️  Columna requerida: {$matches[1]}\n";
                    }
                }
            }
        }
        
        // Avanzar al siguiente día
        $fechaActual = strtotime('+1 day', $fechaActual);
    }
    
    echo "\n✅ Proceso completado!\n";
    echo "📊 Total de registros insertados: $registrosInsertados\n\n";
    
    // Mostrar resumen de datos insertados
    $resumenQuery = "
        SELECT 
            estado,
            COUNT(*) as cantidad,
            MIN(fecha_asignacion) as primera_fecha,
            MAX(fecha_asignacion) as ultima_fecha
        FROM peticion_departamento
        WHERE fecha_asignacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY estado
        ORDER BY cantidad DESC
    ";
    
    $resumenStmt = $db->prepare($resumenQuery);
    $resumenStmt->execute();
    $resumen = $resumenStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📈 Resumen de datos insertados (últimos 30 días):\n";
    echo "================================================\n";
    foreach ($resumen as $row) {
        echo sprintf(
            "%-30s : %3d registros (del %s al %s)\n",
            $row['estado'],
            $row['cantidad'],
            date('d/m/Y', strtotime($row['primera_fecha'])),
            date('d/m/Y', strtotime($row['ultima_fecha']))
        );
    }
    
    echo "\n🎉 ¡Datos de prueba insertados exitosamente!\n";
    echo "💡 Ahora recarga el dashboard para ver el gráfico con datos.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error de base de datos:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n\n";
    
    echo "💡 Posibles soluciones:\n";
    echo "1. Verifica que la tabla 'peticion_departamento' existe\n";
    echo "2. Revisa los permisos de la base de datos\n";
    echo "3. Asegúrate de que las columnas 'fecha_asignacion' y 'estado' existen\n";
    
} catch (Exception $e) {
    echo "\n❌ Error general:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
}
