<?php
// Test completo de tarjetas-depto.php con todas las unidades
session_start();
$_SESSION['user_id'] = 1; // Simular usuario admin

require_once 'config/database.php';
$db = (new Database())->getConnection();

// Query completo (igual que en tarjetas-depto.php)
$query = "SELECT 
            u.id as departamento_id,
            u.nombre_unidad as departamento_nombre,
            COALESCE(COUNT(pd.id), 0) as total_peticiones,
            COALESCE(SUM(CASE 
                WHEN pd.estado IN ('Esperando recepción') 
                THEN 1 ELSE 0 
            END), 0) as pendientes,
            COALESCE(SUM(CASE 
                WHEN pd.estado IN ('Aceptado en proceso') 
                THEN 1 ELSE 0 
            END), 0) as en_proceso,
            COALESCE(SUM(CASE 
                WHEN pd.estado IN ('Completado') 
                THEN 1 ELSE 0 
            END), 0) as completadas,
            COALESCE(SUM(CASE 
                WHEN pd.estado IN ('Devuelto a seguimiento', 'Rechazado')
                THEN 1 ELSE 0
            END), 0) as devueltas,
            COALESCE(SUM(CASE 
                WHEN p.NivelImportancia = 1 
                AND pd.estado NOT IN ('Completado', 'Rechazado')
                THEN 1 ELSE 0 
            END), 0) as criticas,
            MAX(pd.fecha_asignacion) as ultima_asignacion
          FROM unidades u
          LEFT JOIN peticion_departamento pd ON u.id = pd.departamento_id
          LEFT JOIN peticiones p ON pd.peticion_id = p.id
          GROUP BY u.id, u.nombre_unidad
          ORDER BY total_peticiones DESC, u.nombre_unidad ASC";

$stmt = $db->query($query);
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📊 Test tarjetas-depto.php - TODAS las unidades\n";
echo "Total unidades en resultado: " . count($resultado) . "\n\n";
echo "Top 5 con más peticiones:\n";
echo json_encode(array_slice($resultado, 0, 5), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n5 unidades sin peticiones:\n";
$sinPeticiones = array_filter($resultado, function($u) {
    return $u['total_peticiones'] == 0;
});
echo json_encode(array_slice($sinPeticiones, 0, 5), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
