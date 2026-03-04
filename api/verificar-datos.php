<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔍 Verificación de datos en peticion_departamento</h2>";
    
    // Contar registros totales
    $countQuery = "SELECT COUNT(*) as total FROM peticion_departamento";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Total de registros:</strong> $total</p>";
    
    if ($total > 0) {
        // Mostrar distribución por estado
        $estadosQuery = "SELECT estado, COUNT(*) as cantidad FROM peticion_departamento GROUP BY estado";
        $estadosStmt = $db->prepare($estadosQuery);
        $estadosStmt->execute();
        $estados = $estadosStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📊 Distribución por estado:</h3>";
        echo "<ul>";
        foreach ($estados as $e) {
            echo "<li>{$e['estado']}: {$e['cantidad']} registros</li>";
        }
        echo "</ul>";
        
        // Mostrar rango de fechas
        $rangoQuery = "SELECT MIN(fecha_asignacion) as min, MAX(fecha_asignacion) as max FROM peticion_departamento";
        $rangoStmt = $db->prepare($rangoQuery);
        $rangoStmt->execute();
        $rango = $rangoStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Rango de fechas:</strong> {$rango['min']} hasta {$rango['max']}</p>";
        
        // Registros de los últimos 7 días
        $recientesQuery = "SELECT COUNT(*) as total FROM peticion_departamento WHERE fecha_asignacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $recientesStmt = $db->prepare($recientesQuery);
        $recientesStmt->execute();
        $recientes = $recientesStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "<p><strong>Registros de la última semana:</strong> $recientes</p>";
        
        if ($recientes === 0) {
            echo "<p style='color: orange;'>⚠️ No hay datos en los últimos 7 días. El gráfico aparecerá vacío.</p>";
            echo "<p><a href='generar-datos-grafico.php' style='padding: 10px 20px; background: #3B82F6; color: white; text-decoration: none; border-radius: 5px;'>Insertar datos de prueba</a></p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No hay datos en la tabla.</p>";
        echo "<p><a href='generar-datos-grafico.php' style='padding: 10px 20px; background: #3B82F6; color: white; text-decoration: none; border-radius: 5px;'>Insertar datos de prueba ahora</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
