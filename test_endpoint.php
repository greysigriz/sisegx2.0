<?php
// Simular la misma operación que hace el frontend
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Error de conexión a la base de datos");
}

echo "=== SIMULACIÓN DEL ENDPOINT PUT ===\n";

// Datos simulados (basados en los registros existentes)
$asignacion_id = 21; // ID que vimos en la tabla
$estado_nuevo = "En revisión"; // Nuevo estado
$motivo = "Probando subida de imagen desde frontend";
$usuario_id = 18; // Usuario que vimos en los registros existentes

echo "📝 Datos de la simulación:\n";
echo "  - asignacion_id: $asignacion_id\n";
echo "  - estado_nuevo: $estado_nuevo\n";
echo "  - motivo: $motivo\n";
echo "  - usuario_id: $usuario_id\n";
echo "\n";

try {
    $db->beginTransaction();

    // 1. Obtener estado anterior
    $getQuery = "SELECT estado FROM peticion_departamento WHERE id = ?";
    $getStmt = $db->prepare($getQuery);
    $getStmt->execute([$asignacion_id]);
    $current = $getStmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        throw new Exception("Asignación no encontrada");
    }

    echo "🔍 Estado anterior encontrado: " . $current['estado'] . "\n";

    // 2. Actualizar estado
    $updateQuery = "UPDATE peticion_departamento SET estado = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateResult = $updateStmt->execute([$estado_nuevo, $asignacion_id]);
    
    echo "✏️ Actualización de estado: " . ($updateResult ? "EXITOSA" : "FALLIDA") . "\n";

    // 3. Registrar en historial
    $histQuery = "INSERT INTO peticion_departamento_historial 
                 (peticion_departamento_id, estado_anterior, estado_nuevo, motivo, usuario_id, fecha_cambio) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
    $histStmt = $db->prepare($histQuery);
    
    echo "🏗️ Query del historial preparada\n";
    
    $histResult = $histStmt->execute([
        $asignacion_id,
        $current['estado'],
        $estado_nuevo,
        $motivo,
        $usuario_id
    ]);

    echo "📊 Resultado del INSERT historial: " . ($histResult ? "EXITOSO" : "FALLIDO") . "\n";
    echo "📊 Filas afectadas: " . $histStmt->rowCount() . "\n";

    $historial_id = $db->lastInsertId();
    echo "🆔 lastInsertId(): $historial_id\n";

    if (!$histResult || $historial_id == 0) {
        echo "❌ ERROR: El INSERT del historial falló\n";
        echo "🔍 Información del error:\n";
        print_r($histStmt->errorInfo());
        $db->rollback();
        exit(1);
    }

    echo "✅ Todo exitoso, confirmando transacción...\n";
    $db->commit();

    echo "🎉 PROCESO COMPLETADO EXITOSAMENTE\n";
    echo "🆔 ID del historial creado: $historial_id\n";

} catch (Exception $e) {
    echo "❌ ERROR durante el proceso: " . $e->getMessage() . "\n";
    $db->rollback();
    exit(1);
}
?>