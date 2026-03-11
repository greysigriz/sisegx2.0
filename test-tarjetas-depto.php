<?php
// Script de prueba para tarjetas-depto.php
session_start();
$_SESSION['user_id'] = 1; // Simular usuario admin

require_once 'config/database.php';

$db = (new Database())->getConnection();

// Query de prueba
$query = "SELECT 
            u.id as departamento_id,
            u.nombre_unidad as departamento_nombre,
            COUNT(pd.id) as total_peticiones,
            SUM(CASE 
                WHEN pd.estado IN ('Pendiente', 'Esperando recepción', 'Sin revisar') 
                THEN 1 ELSE 0 
            END) as pendientes,
            SUM(CASE 
                WHEN pd.estado IN ('Aceptada en proceso', 'En proceso', 'Asignada') 
                THEN 1 ELSE 0 
            END) as en_proceso,
            SUM(CASE 
                WHEN pd.estado IN ('Completado', 'Cerrado', 'Finalizado') 
                THEN 1 ELSE 0 
            END) as completadas,
            SUM(CASE 
                WHEN p.NivelImportancia = 1 
                AND pd.estado NOT IN ('Completado', 'Cerrado', 'Finalizado')
                THEN 1 ELSE 0 
            END) as criticas,
            MAX(pd.fecha_asignacion) as ultima_asignacion
          FROM unidades u
          LEFT JOIN peticion_departamento pd ON u.id = pd.departamento_id
          LEFT JOIN peticiones p ON pd.peticion_id = p.id
          GROUP BY u.id, u.nombre_unidad
          HAVING total_peticiones > 0
          ORDER BY total_peticiones DESC
          LIMIT 5";

$stmt = $db->query($query);
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📊 Test de tarjetas-depto.php\n";
echo "Total departamentos con peticiones: " . count($resultado) . "\n\n";

if (count($resultado) > 0) {
    echo "Top 5 departamentos:\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "⚠️  No hay departamentos con peticiones asignadas.\n";
    echo "Verificando tabla peticion_departamento...\n";
    
    $stmt = $db->query('SELECT COUNT(*) as total FROM peticion_departamento');
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total registros en peticion_departamento: {$count['total']}\n";
}
