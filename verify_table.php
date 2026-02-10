<?php
// Verificar estructura de tabla peticion_departamento_historial
try {
    $pdo = new PDO('mysql:host=nice-dubinsky.192-99-212-154.plesk.page;dbname=sisegestion;port=3306', 'siseg', 'NK!Igudh306ameu?');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ESTRUCTURA DE TABLA peticion_departamento_historial ===\n";
    $result = $pdo->query('DESCRIBE peticion_departamento_historial');
    if ($result) {
        foreach($result as $row) {
            printf("Field: %s, Type: %s, Null: %s, Key: %s, Default: %s, Extra: %s\n",
                $row['Field'], $row['Type'], $row['Null'], 
                $row['Key'], $row['Default'], $row['Extra']);
        }
    } else {
        echo "Error: No se pudo obtener la estructura de la tabla\n";
    }
    
    echo "\n=== VERIFICAR SI LA TABLA EXISTE ===\n";
    $result = $pdo->query("SHOW TABLES LIKE 'peticion_departamento_historial'");
    if ($result && $result->rowCount() > 0) {
        echo "✓ La tabla peticion_departamento_historial SÍ existe\n";
    } else {
        echo "✗ La tabla peticion_departamento_historial NO existe\n";
    }
    
    echo "\n=== PROBAR INSERT Y lastInsertId ===\n";
    $stmt = $pdo->prepare("INSERT INTO peticion_departamento_historial (peticion_departamento_id, estado_anterior, estado_nuevo, motivo, usuario_id, fecha_cambio) VALUES (?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([1, 'pendiente', 'en_proceso', 'Prueba debug', 1]);
    
    if ($result) {
        $lastId = $pdo->lastInsertId();
        echo "✓ INSERT exitoso. Last Insert ID: " . $lastId . "\n";
        echo "Affected rows: " . $stmt->rowCount() . "\n";
        
        // Eliminar el registro de prueba
        if ($lastId > 0) {
            $pdo->exec("DELETE FROM peticion_departamento_historial WHERE id = " . $lastId);
            echo "✓ Registro de prueba eliminado\n";
        }
    } else {
        echo "✗ Error en INSERT\n";
        print_r($stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
?>