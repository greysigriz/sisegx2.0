<?php
// C:\xampp\htdocs\SISEE\api\sincronizar_estados.php
// Sincroniza el estado de TODAS las peticiones existentes basándose en
// los estados actuales de sus departamentos asignados.
// Usa una sola consulta bulk para evitar N+1 queries.

require_once __DIR__ . '/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
set_time_limit(120); // 2 minutos máximo

require_once __DIR__ . '/../config/database.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit();
}

/**
 * Aplica las mismas reglas que EstadoService::determinarEstado()
 * para calcular el estado en base a conteos (sin hits a BD).
 */
function calcularNuevoEstado($total, $completados, $rechazados, $enProceso, $devueltos, $esperando) {
    if ($total === 0) {
        return ['estado' => 'Por asignar departamento', 'razon' => 'No hay departamentos asignados'];
    }
    if ($completados === $total) {
        return ['estado' => 'Completado', 'razon' => 'Todos los departamentos completaron la petición'];
    }
    if ($rechazados === $total) {
        return ['estado' => 'Rechazado por departamento', 'razon' => 'Todos los departamentos rechazaron la petición'];
    }
    if ($devueltos > 0) {
        return ['estado' => 'Devuelto', 'razon' => "$devueltos departamento(s) devolvieron la petición"];
    }
    if ($enProceso > 0) {
        return ['estado' => 'Aceptada en proceso', 'razon' => "$enProceso departamento(s) trabajando ($completados de $total completados)"];
    }
    if ($rechazados > 0 && $rechazados < $total) {
        return ['estado' => 'Aceptada en proceso', 'razon' => 'Algunos departamentos rechazaron, otros continúan'];
    }
    if ($esperando === $total) {
        return ['estado' => 'Esperando recepción', 'razon' => 'Esperando que los departamentos reciban la petición'];
    }
    return ['estado' => 'Aceptada en proceso', 'razon' => 'Estado mixto en departamentos'];
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    // ── 1. Una sola query: peticiones + conteos de estados de sus departamentos ──
    $query = "
        SELECT
            p.id,
            p.estado AS estado_actual,
            COUNT(pd.id)                                                           AS total_depts,
            SUM(CASE WHEN pd.estado = 'Completado'           THEN 1 ELSE 0 END)   AS completados,
            SUM(CASE WHEN pd.estado = 'Rechazado'            THEN 1 ELSE 0 END)   AS rechazados,
            SUM(CASE WHEN pd.estado = 'Aceptado en proceso'  THEN 1 ELSE 0 END)   AS en_proceso,
            SUM(CASE WHEN pd.estado = 'Devuelto a seguimiento' THEN 1 ELSE 0 END) AS devueltos,
            SUM(CASE WHEN pd.estado = 'Esperando recepción'  THEN 1 ELSE 0 END)   AS esperando
        FROM peticiones p
        LEFT JOIN peticion_departamento pd ON pd.peticion_id = p.id
        WHERE p.estado NOT IN ('Improcedente', 'Cancelada')
        GROUP BY p.id, p.estado
        ORDER BY p.id ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── 2. Calcular nuevo estado en PHP y acumular los que cambian ──
    $actualizadas = 0;
    $sin_cambios  = 0;
    $errores      = 0;
    $detalle      = [];
    $idsActualizar = []; // [id => nuevo_estado]

    foreach ($rows as $row) {
        $nuevo = calcularNuevoEstado(
            intval($row['total_depts']),
            intval($row['completados']),
            intval($row['rechazados']),
            intval($row['en_proceso']),
            intval($row['devueltos']),
            intval($row['esperando'])
        );

        if ($nuevo['estado'] !== $row['estado_actual']) {
            $idsActualizar[$row['id']] = [
                'nuevo'    => $nuevo['estado'],
                'anterior' => $row['estado_actual'],
                'razon'    => $nuevo['razon']
            ];
            $actualizadas++;
            $detalle[] = [
                'id'              => $row['id'],
                'result'          => 'actualizada',
                'estado_anterior' => $row['estado_actual'],
                'estado_nuevo'    => $nuevo['estado'],
                'razon'           => $nuevo['razon']
            ];
        } else {
            $sin_cambios++;
        }
    }

    // ── 3. Actualizar solo las peticiones que cambiaron (una query por petición
    //       que cambió, pero ese número es pequeño) ──────────────────────────────
    if (!empty($idsActualizar)) {
        $updateStmt = $db->prepare("UPDATE peticiones SET estado = ? WHERE id = ?");
        foreach ($idsActualizar as $pid => $info) {
            if (!$updateStmt->execute([$info['nuevo'], $pid])) {
                $errores++;
                error_log("sincronizar_estados: fallo UPDATE peticion $pid");
            }
        }
    }

    $total = count($rows);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Sincronización completada",
        "resumen" => [
            "total_procesadas" => $total,
            "actualizadas"     => $actualizadas,
            "sin_cambios"      => $sin_cambios,
            "errores"          => $errores
        ],
        "cambios" => $detalle
    ]);

} catch (Exception $e) {
    error_log("Error en sincronizar_estados.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error del servidor: " . $e->getMessage()
    ]);
}
?>
