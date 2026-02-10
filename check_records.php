<?php
// Verificar registros de ejemplo en peticion_departamento
try {
    $pdo = new PDO('mysql:host=nice-dubinsky.192-99-212-154.plesk.page;dbname=sisegestion;port=3306', 'siseg', 'NK!Igudh306ameu?');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== REGISTROS EN peticion_departamento ===\n";
    $result = $pdo->query('SELECT id, peticion_id, departamento_id, estado FROM peticion_departamento ORDER BY id DESC LIMIT 5');
    if ($result) {
        foreach($result as $row) {
            printf("ID: %s, Peticion ID: %s, Depto ID: %s, Estado: %s\n",
                $row['id'], $row['peticion_id'], $row['departamento_id'], $row['estado']);
        }
    } else {
        echo "No hay registros o error al consultar\n";
    }
    
    echo "\n=== ÚLTIMOS REGISTROS EN peticion_departamento_historial ===\n";
    $result = $pdo->query('SELECT id, peticion_departamento_id, estado_anterior, estado_nuevo, motivo, usuario_id, fecha_cambio FROM peticion_departamento_historial ORDER BY id DESC LIMIT 3');
    if ($result) {
        foreach($result as $row) {
            printf("ID: %s, PetDept ID: %s, De: %s -> %s, Motivo: %s, Usuario: %s, Fecha: %s\n",
                $row['id'], $row['peticion_departamento_id'], $row['estado_anterior'], 
                $row['estado_nuevo'], substr($row['motivo'], 0, 30), $row['usuario_id'], $row['fecha_cambio']);
        }
    } else {
        echo "No hay registros en el historial\n";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
?>