<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Solo soportamos GET para listar dependencias con un conteo de peticiones
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
    exit;
}

// Datos fallback en caso de que la BD no esté disponible
$fallback = [
    ['ID' => 0, 'Nombre' => 'Obras Públicas', 'cantidad' => 0],
    ['ID' => 0, 'Nombre' => 'Servicios Urbanos', 'cantidad' => 0],
    ['ID' => 0, 'Nombre' => 'Seguridad', 'cantidad' => 0]
];

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT ID, Nombre FROM dependencias ORDER BY Nombre";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $dependencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Contar peticiones relacionadas con la dependencia.
        // Hay dos sitios donde pueden registrarse dependencias/sugerencias:
        // 1) peticion_sugerencias.departamento_nombre
        // 2) peticion_departamento -> unidades.nombre_unidad (departamentos asignados)
        // Usaremos una unión para contar peticiones distintas relacionadas con el nombre de la dependencia.
        $countSql = "SELECT COUNT(DISTINCT t.peticion_id) as cnt FROM (
                                        SELECT peticion_id FROM peticion_sugerencias WHERE departamento_nombre = ?
                                        UNION
                                        SELECT pd.peticion_id FROM peticion_departamento pd
                                            LEFT JOIN unidades u ON pd.departamento_id = u.id
                                            WHERE u.nombre_unidad = ?
                                 ) t";
        $countStmt = $db->prepare($countSql);

    $results = [];

    foreach ($dependencias as $d) {
        $id = $d['ID'];
        $nombre = $d['Nombre'];

        // Ejecutar conteo usando el nombre de la dependencia
        // (la consulta tiene dos placeholders: para peticion_sugerencias.departamento_nombre
        // y para unidades.nombre_unidad)
        $cantidad = 0;
        $countError = null;
        try {
            $countStmt->execute([$nombre, $nombre]);
            $cntRow = $countStmt->fetch(PDO::FETCH_ASSOC);
            $cantidad = isset($cntRow['cnt']) ? (int)$cntRow['cnt'] : 0;
        } catch (Exception $e) {
            $countError = $e->getMessage();
            error_log("dependencias.php count error for '{$nombre}': " . $countError);
            // continue with cantidad = 0
        }

        $results[] = [
            'ID' => (int)$id,
            'Nombre' => $nombre,
            'cantidad' => $cantidad,
            'count_error' => $countError
        ];
    }

    http_response_code(200);
    echo json_encode(['records' => $results]);

} catch (Exception $e) {
    // En lugar de devolver 500, devolver 200 con datos fallback y un warning
    http_response_code(200);
    echo json_encode([
        'records' => $fallback,
        'warning' => 'No fue posible conectar a la base de datos: ' . $e->getMessage()
    ]);
}

?>
