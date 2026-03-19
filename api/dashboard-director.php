<?php
// C:\xampp\htdocs\SISEE\api\dashboard-director.php
// API unificada para el dashboard del director - datos dinámicos con filtros
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 8 * 60 * 60);
    ini_set('session.cookie_lifetime', 8 * 60 * 60);
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // ===================== FILTROS =====================
    $filtroMunicipio = isset($_GET['municipio_id']) ? intval($_GET['municipio_id']) : null;
    $filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : null;
    $filtroImportancia = isset($_GET['importancia']) ? intval($_GET['importancia']) : null;
    $filtroDias = isset($_GET['dias']) ? intval($_GET['dias']) : 365;
    $filtroFechaInicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : null;
    $filtroFechaFin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : null;

    // Construir cláusulas WHERE dinámicas
    $whereConditions = [];
    $params = [];

    if ($filtroMunicipio) {
        $whereConditions[] = "p.division_id = :municipio_id";
        $params[':municipio_id'] = $filtroMunicipio;
    }
    if ($filtroEstado) {
        $whereConditions[] = "p.estado = :estado";
        $params[':estado'] = $filtroEstado;
    }
    if ($filtroImportancia) {
        $whereConditions[] = "p.NivelImportancia = :importancia";
        $params[':importancia'] = $filtroImportancia;
    }
    if ($filtroFechaInicio) {
        $whereConditions[] = "p.fecha_registro >= :fecha_inicio";
        $params[':fecha_inicio'] = $filtroFechaInicio . ' 00:00:00';
    }
    if ($filtroFechaFin) {
        $whereConditions[] = "p.fecha_registro <= :fecha_fin";
        $params[':fecha_fin'] = $filtroFechaFin . ' 23:59:59';
    }
    if (!$filtroFechaInicio && !$filtroFechaFin) {
        $whereConditions[] = "p.fecha_registro >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)";
        $params[':dias'] = $filtroDias;
    }

    $whereClause = count($whereConditions) > 0 ? " WHERE " . implode(" AND ", $whereConditions) : "";

    // ===================== 1. KPIs PRINCIPALES =====================
    $queryKPIs = "SELECT
        COUNT(*) as total_peticiones,
        SUM(CASE WHEN p.estado = 'Sin revisar' THEN 1 ELSE 0 END) as sin_revisar,
        SUM(CASE WHEN p.estado = 'Por asignar departamento' THEN 1 ELSE 0 END) as por_asignar,
        SUM(CASE WHEN p.estado = 'Esperando recepción' THEN 1 ELSE 0 END) as esperando_recepcion,
        SUM(CASE WHEN p.estado = 'Aceptada en proceso' THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN p.estado = 'Devuelto' THEN 1 ELSE 0 END) as devueltas,
        SUM(CASE WHEN p.estado = 'Rechazado por departamento' THEN 1 ELSE 0 END) as rechazadas,
        SUM(CASE WHEN p.estado = 'Improcedente' THEN 1 ELSE 0 END) as improcedentes,
        SUM(CASE WHEN p.estado = 'Cancelada' THEN 1 ELSE 0 END) as canceladas,
        SUM(CASE WHEN p.NivelImportancia = 1 THEN 1 ELSE 0 END) as criticas,
        SUM(CASE WHEN p.NivelImportancia = 2 THEN 1 ELSE 0 END) as altas,
        SUM(CASE WHEN p.estado NOT IN ('Completado', 'Cancelada', 'Improcedente')
            AND DATEDIFF(CURDATE(), p.fecha_registro) > 30 THEN 1 ELSE 0 END) as retrasadas
    FROM peticiones p $whereClause";

    $stmtKPIs = $db->prepare($queryKPIs);
    foreach ($params as $key => $value) {
        $stmtKPIs->bindValue($key, $value);
    }
    $stmtKPIs->execute();
    $kpis = $stmtKPIs->fetch(PDO::FETCH_ASSOC);

    // Promedio dias resolucion (query separada, mas eficiente)
    $qProm = "SELECT ROUND(AVG(DATEDIFF(pdh.fecha_cambio, p.fecha_registro)), 1) as promedio
        FROM peticion_departamento_historial pdh
        INNER JOIN peticion_departamento pd ON pdh.peticion_departamento_id = pd.id
        INNER JOIN peticiones p ON pd.peticion_id = p.id
        WHERE pdh.estado_nuevo = 'Completado'";
    $stmtProm = $db->query($qProm);
    $kpis['promedio_dias_resolucion'] = $stmtProm->fetch(PDO::FETCH_ASSOC)['promedio'];

    // KPIs del periodo anterior (periodo equivalente previo al seleccionado)
    $prevWhere = [];
    $prevParams = [];
    if ($filtroMunicipio) {
        $prevWhere[] = "p.division_id = :prev_muni";
        $prevParams[':prev_muni'] = $filtroMunicipio;
    }
    if ($filtroFechaInicio && $filtroFechaFin) {
        // Periodo custom: calcular diferencia y obtener periodo anterior equivalente
        $diff = (strtotime($filtroFechaFin) - strtotime($filtroFechaInicio));
        $prevEnd = date('Y-m-d', strtotime($filtroFechaInicio) - 1);
        $prevStart = date('Y-m-d', strtotime($prevEnd) - $diff);
        $prevWhere[] = "p.fecha_registro BETWEEN :prev_fi AND :prev_ff";
        $prevParams[':prev_fi'] = $prevStart . ' 00:00:00';
        $prevParams[':prev_ff'] = $prevEnd . ' 23:59:59';
    } else {
        // Basado en dias: periodo anterior de misma longitud
        $prevWhere[] = "p.fecha_registro BETWEEN DATE_SUB(CURDATE(), INTERVAL :prev_dias2 DAY) AND DATE_SUB(CURDATE(), INTERVAL :prev_dias1 DAY)";
        $prevParams[':prev_dias2'] = $filtroDias * 2;
        $prevParams[':prev_dias1'] = $filtroDias;
    }
    $prevWhereSQL = count($prevWhere) > 0 ? " WHERE " . implode(" AND ", $prevWhere) : "";

    $qPrev = "SELECT
        COUNT(*) as total_peticiones,
        SUM(CASE WHEN p.estado = 'Aceptada en proceso' THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN p.NivelImportancia = 1 THEN 1 ELSE 0 END) as criticas,
        SUM(CASE WHEN p.estado NOT IN ('Completado','Cancelada','Improcedente')
            AND DATEDIFF(CURDATE(), p.fecha_registro) > 30 THEN 1 ELSE 0 END) as retrasadas
    FROM peticiones p $prevWhereSQL";
    $stmtPrev = $db->prepare($qPrev);
    foreach ($prevParams as $k => $v) $stmtPrev->bindValue($k, $v);
    $stmtPrev->execute();
    $kpisPrev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

    // ===================== 2. DISTRIBUCIÓN POR ESTADO =====================
    $queryEstados = "SELECT p.estado, COUNT(*) as cantidad
        FROM peticiones p $whereClause
        GROUP BY p.estado
        ORDER BY cantidad DESC";
    $stmtEstados = $db->prepare($queryEstados);
    foreach ($params as $key => $value) {
        $stmtEstados->bindValue($key, $value);
    }
    $stmtEstados->execute();
    $porEstado = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

    // ===================== 3. DISTRIBUCIÓN POR IMPORTANCIA =====================
    $queryImportancia = "SELECT p.NivelImportancia, COUNT(*) as cantidad
        FROM peticiones p $whereClause
        GROUP BY p.NivelImportancia
        ORDER BY p.NivelImportancia";
    $stmtImp = $db->prepare($queryImportancia);
    foreach ($params as $key => $value) {
        $stmtImp->bindValue($key, $value);
    }
    $stmtImp->execute();
    $porImportancia = $stmtImp->fetchAll(PDO::FETCH_ASSOC);

    // ===================== 4. TIMELINE (TENDENCIA) =====================
    // Agrupar por día si <= 90 días, por semana si <= 365, por mes si > 365
    if ($filtroDias <= 90) {
        $groupBy = "DATE(p.fecha_registro)";
        $selectFecha = "DATE(p.fecha_registro) as fecha";
    } elseif ($filtroDias <= 365) {
        $groupBy = "YEARWEEK(p.fecha_registro, 1)";
        $selectFecha = "MIN(DATE(p.fecha_registro)) as fecha";
    } else {
        $groupBy = "DATE_FORMAT(p.fecha_registro, '%Y-%m')";
        $selectFecha = "DATE_FORMAT(p.fecha_registro, '%Y-%m-01') as fecha";
    }

    $queryTimeline = "SELECT
        $selectFecha,
        COUNT(*) as total,
        SUM(CASE WHEN p.estado IN ('Sin revisar', 'Esperando recepción', 'Por asignar departamento') THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN p.estado = 'Aceptada en proceso' THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN p.estado IN ('Devuelto', 'Rechazado por departamento') THEN 1 ELSE 0 END) as devueltas,
        SUM(CASE WHEN p.estado IN ('Improcedente', 'Cancelada') THEN 1 ELSE 0 END) as cerradas
    FROM peticiones p $whereClause
    GROUP BY $groupBy
    ORDER BY fecha ASC";

    $stmtTimeline = $db->prepare($queryTimeline);
    foreach ($params as $key => $value) {
        $stmtTimeline->bindValue($key, $value);
    }
    $stmtTimeline->execute();
    $timeline = $stmtTimeline->fetchAll(PDO::FETCH_ASSOC);

    // ===================== 5. TOP MUNICIPIOS =====================
    $queryMunicipios = "SELECT
        d.Id as municipio_id,
        d.Municipio as municipio,
        COUNT(p.id) as cantidad,
        SUM(CASE WHEN p.estado NOT IN ('Completado', 'Cancelada', 'Improcedente') THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN p.NivelImportancia <= 2 THEN 1 ELSE 0 END) as urgentes
    FROM peticiones p
    INNER JOIN DivisionAdministrativa d ON p.division_id = d.Id
    " . (count($whereConditions) > 0 ? " WHERE " . implode(" AND ", $whereConditions) : "") . "
    GROUP BY d.Id, d.Municipio
    ORDER BY cantidad DESC
    LIMIT 15";
    $stmtMuni = $db->prepare($queryMunicipios);
    foreach ($params as $key => $value) {
        $stmtMuni->bindValue($key, $value);
    }
    $stmtMuni->execute();
    $topMunicipios = $stmtMuni->fetchAll(PDO::FETCH_ASSOC);

    // ===================== 6. TOP DEPARTAMENTOS (UNIDADES) =====================
    $deptWhere = [];
    $deptParams = [];

    if ($filtroMunicipio) {
        $deptWhere[] = "p.division_id = :dept_municipio_id";
        $deptParams[':dept_municipio_id'] = $filtroMunicipio;
    }
    if ($filtroEstado) {
        $deptWhere[] = "p.estado = :dept_estado";
        $deptParams[':dept_estado'] = $filtroEstado;
    }
    if ($filtroImportancia) {
        $deptWhere[] = "p.NivelImportancia = :dept_importancia";
        $deptParams[':dept_importancia'] = $filtroImportancia;
    }
    if ($filtroFechaInicio) {
        $deptWhere[] = "pd.fecha_asignacion >= :dept_fecha_inicio";
        $deptParams[':dept_fecha_inicio'] = $filtroFechaInicio . ' 00:00:00';
    }
    if ($filtroFechaFin) {
        $deptWhere[] = "pd.fecha_asignacion <= :dept_fecha_fin";
        $deptParams[':dept_fecha_fin'] = $filtroFechaFin . ' 23:59:59';
    }
    if (!$filtroFechaInicio && !$filtroFechaFin) {
        $deptWhere[] = "pd.fecha_asignacion >= DATE_SUB(CURDATE(), INTERVAL :dept_dias DAY)";
        $deptParams[':dept_dias'] = $filtroDias;
    }

    $deptWhereClause = count($deptWhere) > 0 ? " WHERE " . implode(" AND ", $deptWhere) : "";

    $queryDepartamentos = "SELECT
        u.id as departamento_id,
        u.nombre_unidad as departamento,
        COUNT(pd.id) as total_asignaciones,
        SUM(CASE WHEN pd.estado = 'Esperando recepción' THEN 1 ELSE 0 END) as esperando,
        SUM(CASE WHEN pd.estado = 'Aceptado en proceso' THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN pd.estado IN ('Devuelto a seguimiento', 'Rechazado') THEN 1 ELSE 0 END) as devueltas,
        ROUND(SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(pd.id), 0), 1) as tasa_resolucion
    FROM unidades u
    LEFT JOIN peticion_departamento pd ON u.id = pd.departamento_id
    LEFT JOIN peticiones p ON pd.peticion_id = p.id
    $deptWhereClause
    GROUP BY u.id, u.nombre_unidad
    HAVING total_asignaciones > 0
    ORDER BY total_asignaciones DESC";

    $stmtDept = $db->prepare($queryDepartamentos);
    foreach ($deptParams as $key => $value) {
        $stmtDept->bindValue($key, $value);
    }
    $stmtDept->execute();
    $topDepartamentos = $stmtDept->fetchAll(PDO::FETCH_ASSOC);

    // ===================== 7. PETICIONES RECIENTES =====================
    $queryRecientes = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
        p.NivelImportancia, p.fecha_registro, p.localidad,
        d.Municipio
    FROM peticiones p
    LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
    " . (count($whereConditions) > 0 ? " WHERE " . implode(" AND ", $whereConditions) : "") . "
    ORDER BY p.fecha_registro DESC
    LIMIT 15";
    $stmtRecientes = $db->prepare($queryRecientes);
    foreach ($params as $key => $value) {
        $stmtRecientes->bindValue($key, $value);
    }
    $stmtRecientes->execute();
    $recientes = $stmtRecientes->fetchAll(PDO::FETCH_ASSOC);

    // ===================== 8. ALERTAS (respetan filtros de municipio y fecha) =====================
    $alerts = [];
    $alertFilter = "";
    $alertParams = [];
    $alertDeptFilter = "";
    $alertDeptParams = [];

    if ($filtroMunicipio) {
        $alertFilter .= " AND p.division_id = :alert_muni";
        $alertParams[':alert_muni'] = $filtroMunicipio;
    }
    if ($filtroFechaInicio) {
        $alertFilter .= " AND p.fecha_registro >= :alert_fi";
        $alertParams[':alert_fi'] = $filtroFechaInicio . ' 00:00:00';
    }
    if ($filtroFechaFin) {
        $alertFilter .= " AND p.fecha_registro <= :alert_ff";
        $alertParams[':alert_ff'] = $filtroFechaFin . ' 23:59:59';
    }
    if (!$filtroFechaInicio && !$filtroFechaFin) {
        $alertFilter .= " AND p.fecha_registro >= DATE_SUB(CURDATE(), INTERVAL :alert_dias DAY)";
        $alertParams[':alert_dias'] = $filtroDias;
    }

    // Dept alerts: join con peticiones para filtrar por fecha
    if ($filtroMunicipio) {
        $alertDeptFilter .= " AND pd.peticion_id IN (SELECT id FROM peticiones WHERE division_id = :alert_dept_muni)";
        $alertDeptParams[':alert_dept_muni'] = $filtroMunicipio;
    }
    if ($filtroFechaInicio) {
        $alertDeptFilter .= " AND pd.fecha_asignacion >= :alert_dept_fi";
        $alertDeptParams[':alert_dept_fi'] = $filtroFechaInicio . ' 00:00:00';
    }
    if ($filtroFechaFin) {
        $alertDeptFilter .= " AND pd.fecha_asignacion <= :alert_dept_ff";
        $alertDeptParams[':alert_dept_ff'] = $filtroFechaFin . ' 23:59:59';
    }
    if (!$filtroFechaInicio && !$filtroFechaFin) {
        $alertDeptFilter .= " AND pd.fecha_asignacion >= DATE_SUB(CURDATE(), INTERVAL :alert_dept_dias DAY)";
        $alertDeptParams[':alert_dept_dias'] = $filtroDias;
    }

    // Peticiones criticas sin atender
    $qCriticas = "SELECT COUNT(*) as c FROM peticiones p WHERE p.NivelImportancia = 1 AND p.estado IN ('Sin revisar', 'Esperando recepción', 'Por asignar departamento')" . $alertFilter;
    $stmtCriticas = $db->prepare($qCriticas);
    foreach ($alertParams as $k => $v) $stmtCriticas->bindValue($k, $v);
    $stmtCriticas->execute();
    $criticasCount = $stmtCriticas->fetch(PDO::FETCH_ASSOC)['c'];
    if ($criticasCount > 0) {
        $alerts[] = ['type' => 'critical', 'message' => "criticas pendientes", 'count' => intval($criticasCount)];
    }

    // Peticiones retrasadas (+30 dias)
    $qRetrasadas = "SELECT COUNT(*) as c FROM peticiones p WHERE p.estado NOT IN ('Completado', 'Cancelada', 'Improcedente') AND DATEDIFF(CURDATE(), p.fecha_registro) > 30" . $alertFilter;
    $stmtRetrasadas = $db->prepare($qRetrasadas);
    foreach ($alertParams as $k => $v) $stmtRetrasadas->bindValue($k, $v);
    $stmtRetrasadas->execute();
    $retrasadasCount = $stmtRetrasadas->fetch(PDO::FETCH_ASSOC)['c'];
    if ($retrasadasCount > 0) {
        $alerts[] = ['type' => 'warning', 'message' => "retrasadas (+30d)", 'count' => intval($retrasadasCount)];
    }

    // Departamentos sin responder (+15 dias)
    $qDeptSinResp = "SELECT COUNT(*) as c FROM peticion_departamento pd WHERE pd.estado = 'Esperando recepción' AND DATEDIFF(CURDATE(), pd.fecha_asignacion) > 15" . $alertDeptFilter;
    $stmtDeptSinResp = $db->prepare($qDeptSinResp);
    foreach ($alertDeptParams as $k => $v) $stmtDeptSinResp->bindValue($k, $v);
    $stmtDeptSinResp->execute();
    $deptSinRespCount = $stmtDeptSinResp->fetch(PDO::FETCH_ASSOC)['c'];
    if ($deptSinRespCount > 0) {
        $alerts[] = ['type' => 'warning', 'message' => "deptos sin respuesta (+15d)", 'count' => intval($deptSinRespCount)];
    }

    // ===================== 9-10. LISTAS ESTATICAS (solo si no se pidio skip) =====================
    $skipLists = isset($_GET['skip_lists']) && $_GET['skip_lists'] == '1';
    $municipiosList = null;
    $departamentosList = null;

    if (!$skipLists) {
        $stmtMuniList = $db->query("SELECT Id, Municipio FROM DivisionAdministrativa ORDER BY Municipio");
        $municipiosList = $stmtMuniList->fetchAll(PDO::FETCH_ASSOC);

        $stmtDeptList = $db->query("SELECT id, nombre_unidad FROM unidades ORDER BY nombre_unidad");
        $departamentosList = $stmtDeptList->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===================== 11. DETALLE POR DEPARTAMENTO =====================
    $detalleDepartamento = null;
    $filtroDeptId = isset($_GET['departamento_id']) ? intval($_GET['departamento_id']) : null;

    if ($filtroDeptId) {
        // Info del departamento
        $stmtDeptInfo = $db->prepare("SELECT id, nombre_unidad FROM unidades WHERE id = :id");
        $stmtDeptInfo->bindValue(':id', $filtroDeptId);
        $stmtDeptInfo->execute();
        $deptInfo = $stmtDeptInfo->fetch(PDO::FETCH_ASSOC);

        if ($deptInfo) {
            // Municipios donde ha trabajado este departamento
            $qDeptMunis = "SELECT d.Municipio as municipio, d.Id as municipio_id,
                COUNT(pd.id) as total,
                SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN pd.estado = 'Esperando recepción' THEN 1 ELSE 0 END) as esperando,
                SUM(CASE WHEN pd.estado = 'Aceptado en proceso' THEN 1 ELSE 0 END) as en_proceso
            FROM peticion_departamento pd
            INNER JOIN peticiones p ON pd.peticion_id = p.id
            INNER JOIN DivisionAdministrativa d ON p.division_id = d.Id
            WHERE pd.departamento_id = :dept_id
            GROUP BY d.Id, d.Municipio
            ORDER BY total DESC";
            $stmtDM = $db->prepare($qDeptMunis);
            $stmtDM->bindValue(':dept_id', $filtroDeptId);
            $stmtDM->execute();
            $deptMunicipios = $stmtDM->fetchAll(PDO::FETCH_ASSOC);

            // Resumen de estados para este departamento
            $qDeptEstados = "SELECT pd.estado, COUNT(*) as cantidad
                FROM peticion_departamento pd
                WHERE pd.departamento_id = :dept_id
                GROUP BY pd.estado";
            $stmtDE = $db->prepare($qDeptEstados);
            $stmtDE->bindValue(':dept_id', $filtroDeptId);
            $stmtDE->execute();
            $deptEstados = $stmtDE->fetchAll(PDO::FETCH_ASSOC);

            // Timeline: asignaciones vs completaciones por mes
            $qDeptTimeline = "SELECT
                DATE_FORMAT(pd.fecha_asignacion, '%Y-%m-01') as fecha,
                COUNT(*) as nuevas,
                SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas
            FROM peticion_departamento pd
            WHERE pd.departamento_id = :dept_id
            GROUP BY DATE_FORMAT(pd.fecha_asignacion, '%Y-%m')
            ORDER BY fecha ASC";
            $stmtDT = $db->prepare($qDeptTimeline);
            $stmtDT->bindValue(':dept_id', $filtroDeptId);
            $stmtDT->execute();
            $deptTimeline = $stmtDT->fetchAll(PDO::FETCH_ASSOC);

            // Totales
            $qDeptTotals = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN pd.estado NOT IN ('Completado','Rechazado') THEN 1 ELSE 0 END) as pendientes,
                ROUND(SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END)*100.0/NULLIF(COUNT(*),0),1) as tasa
            FROM peticion_departamento pd
            WHERE pd.departamento_id = :dept_id";
            $stmtDTot = $db->prepare($qDeptTotals);
            $stmtDTot->bindValue(':dept_id', $filtroDeptId);
            $stmtDTot->execute();
            $deptTotals = $stmtDTot->fetch(PDO::FETCH_ASSOC);

            $detalleDepartamento = [
                'info' => $deptInfo,
                'totales' => $deptTotals,
                'municipios' => $deptMunicipios,
                'por_estado' => $deptEstados,
                'timeline' => $deptTimeline
            ];
        }
    }

    // ===================== 12. DETALLE POR MUNICIPIO =====================
    $detalleMunicipio = null;
    $filtroMuniDetalle = isset($_GET['municipio_detalle_id']) ? intval($_GET['municipio_detalle_id']) : null;

    if ($filtroMuniDetalle) {
        $stmtMuniInfo = $db->prepare("SELECT Id, Municipio FROM DivisionAdministrativa WHERE Id = :id");
        $stmtMuniInfo->bindValue(':id', $filtroMuniDetalle);
        $stmtMuniInfo->execute();
        $muniInfo = $stmtMuniInfo->fetch(PDO::FETCH_ASSOC);

        if ($muniInfo) {
            // Departamentos que intervienen en este municipio
            $qMuniDepts = "SELECT u.id as departamento_id, u.nombre_unidad as departamento,
                COUNT(pd.id) as total,
                SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN pd.estado = 'Esperando recepción' THEN 1 ELSE 0 END) as esperando,
                SUM(CASE WHEN pd.estado = 'Aceptado en proceso' THEN 1 ELSE 0 END) as en_proceso
            FROM peticion_departamento pd
            INNER JOIN peticiones p ON pd.peticion_id = p.id
            INNER JOIN unidades u ON pd.departamento_id = u.id
            WHERE p.division_id = :muni_id
            GROUP BY u.id, u.nombre_unidad
            ORDER BY total DESC";
            $stmtMD = $db->prepare($qMuniDepts);
            $stmtMD->bindValue(':muni_id', $filtroMuniDetalle);
            $stmtMD->execute();
            $muniDepartamentos = $stmtMD->fetchAll(PDO::FETCH_ASSOC);

            // Estados de peticiones en este municipio
            $qMuniEstados = "SELECT p.estado, COUNT(*) as cantidad
                FROM peticiones p WHERE p.division_id = :muni_id
                GROUP BY p.estado ORDER BY cantidad DESC";
            $stmtME = $db->prepare($qMuniEstados);
            $stmtME->bindValue(':muni_id', $filtroMuniDetalle);
            $stmtME->execute();
            $muniEstados = $stmtME->fetchAll(PDO::FETCH_ASSOC);

            // Timeline: peticiones nuevas vs completadas por mes
            $qMuniTimeline = "SELECT
                DATE_FORMAT(p.fecha_registro, '%Y-%m-01') as fecha,
                COUNT(*) as nuevas,
                SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END) as completadas
            FROM peticiones p WHERE p.division_id = :muni_id
            GROUP BY DATE_FORMAT(p.fecha_registro, '%Y-%m')
            ORDER BY fecha ASC";
            $stmtMT = $db->prepare($qMuniTimeline);
            $stmtMT->bindValue(':muni_id', $filtroMuniDetalle);
            $stmtMT->execute();
            $muniTimeline = $stmtMT->fetchAll(PDO::FETCH_ASSOC);

            // Totales del municipio
            $qMuniTotals = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN p.estado NOT IN ('Completado','Cancelada','Improcedente') THEN 1 ELSE 0 END) as activas,
                SUM(CASE WHEN p.NivelImportancia <= 2 THEN 1 ELSE 0 END) as urgentes
            FROM peticiones p WHERE p.division_id = :muni_id";
            $stmtMTot = $db->prepare($qMuniTotals);
            $stmtMTot->bindValue(':muni_id', $filtroMuniDetalle);
            $stmtMTot->execute();
            $muniTotals = $stmtMTot->fetch(PDO::FETCH_ASSOC);

            $detalleMunicipio = [
                'info' => $muniInfo,
                'totales' => $muniTotals,
                'departamentos' => $muniDepartamentos,
                'por_estado' => $muniEstados,
                'timeline' => $muniTimeline
            ];
        }
    }

    // ===================== 13. DETALLE POR TARJETA (DRILL-DOWN KPIs) =====================
    $cardDetalle = null;
    $cardTipo = isset($_GET['card_detalle']) ? trim($_GET['card_detalle']) : null;

    if ($cardTipo) {
        $cardWhere = [];
        $cardParams = [];

        // Filtros base (municipio, fecha)
        if ($filtroMunicipio) {
            $cardWhere[] = "p.division_id = :cd_muni";
            $cardParams[':cd_muni'] = $filtroMunicipio;
        }
        if ($filtroFechaInicio) {
            $cardWhere[] = "p.fecha_registro >= :cd_fi";
            $cardParams[':cd_fi'] = $filtroFechaInicio . ' 00:00:00';
        }
        if ($filtroFechaFin) {
            $cardWhere[] = "p.fecha_registro <= :cd_ff";
            $cardParams[':cd_ff'] = $filtroFechaFin . ' 23:59:59';
        }
        if (!$filtroFechaInicio && !$filtroFechaFin) {
            $cardWhere[] = "p.fecha_registro >= DATE_SUB(CURDATE(), INTERVAL :cd_dias DAY)";
            $cardParams[':cd_dias'] = $filtroDias;
        }

        // Condiciones específicas por tipo de tarjeta
        switch ($cardTipo) {
            case 'total':
                // Todas las peticiones del periodo
                break;
            case 'criticas':
                $cardWhere[] = "p.NivelImportancia = 1";
                break;
            case 'proceso':
                $cardWhere[] = "p.estado = 'Aceptada en proceso'";
                break;
            case 'completadas':
                $cardWhere[] = "p.estado = 'Completado'";
                break;
            case 'retrasadas':
                $cardWhere[] = "p.estado NOT IN ('Completado', 'Cancelada', 'Improcedente')";
                $cardWhere[] = "DATEDIFF(CURDATE(), p.fecha_registro) > 30";
                break;
            case 'importancia_1': case 'importancia_2': case 'importancia_3':
            case 'importancia_4': case 'importancia_5':
                $nivel = intval(str_replace('importancia_', '', $cardTipo));
                $cardWhere[] = "p.NivelImportancia = :cd_nivel";
                $cardParams[':cd_nivel'] = $nivel;
                break;
            default:
                $cardTipo = null; // tipo no válido, ignorar
        }

        if ($cardTipo) {
            $cardWhereSQL = count($cardWhere) > 0 ? " WHERE " . implode(" AND ", $cardWhere) : "";

            $qCard = "SELECT p.id, p.folio, p.nombre, p.descripcion, p.estado,
                p.NivelImportancia, p.fecha_registro, p.localidad,
                d.Municipio,
                DATEDIFF(CURDATE(), p.fecha_registro) as dias_transcurridos
            FROM peticiones p
            LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
            $cardWhereSQL
            ORDER BY p.fecha_registro DESC";

            $stmtCard = $db->prepare($qCard);
            foreach ($cardParams as $k => $v) {
                $stmtCard->bindValue($k, $v);
            }
            $stmtCard->execute();
            $cardDetalle = [
                'tipo' => $cardTipo,
                'peticiones' => $stmtCard->fetchAll(PDO::FETCH_ASSOC)
            ];
        }
    }

    // ===================== 14. DATOS PARA MAPA CHOROPLETH =====================
    // Construir filtro de fecha para mapa
    $mapWhere = [];
    $mapParams = [];
    if ($filtroFechaInicio) {
        $mapWhere[] = "p.fecha_registro >= :map_fi";
        $mapParams[':map_fi'] = $filtroFechaInicio . ' 00:00:00';
    }
    if ($filtroFechaFin) {
        $mapWhere[] = "p.fecha_registro <= :map_ff";
        $mapParams[':map_ff'] = $filtroFechaFin . ' 23:59:59';
    }
    if (!$filtroFechaInicio && !$filtroFechaFin) {
        $mapWhere[] = "p.fecha_registro >= DATE_SUB(CURDATE(), INTERVAL :map_dias DAY)";
        $mapParams[':map_dias'] = $filtroDias;
    }
    $mapWhereSQL = count($mapWhere) > 0 ? " WHERE " . implode(" AND ", $mapWhere) : "";

    $qMapData = "SELECT d.Id as municipio_id, d.Municipio as municipio,
        COUNT(p.id) as total,
        SUM(CASE WHEN p.estado NOT IN ('Completado','Cancelada','Improcedente') THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN p.NivelImportancia <= 2 THEN 1 ELSE 0 END) as urgentes,
        SUM(CASE WHEN p.estado = 'Sin revisar' THEN 1 ELSE 0 END) as sin_revisar,
        SUM(CASE WHEN p.estado = 'Por asignar departamento' THEN 1 ELSE 0 END) as por_asignar,
        SUM(CASE WHEN p.estado = 'Aceptada en proceso' THEN 1 ELSE 0 END) as en_proceso,
        ROUND(SUM(CASE WHEN p.estado = 'Completado' THEN 1 ELSE 0 END)*100.0/NULLIF(COUNT(p.id),0),1) as tasa_resolucion
    FROM peticiones p
    INNER JOIN DivisionAdministrativa d ON p.division_id = d.Id
    $mapWhereSQL
    GROUP BY d.Id, d.Municipio
    ORDER BY total DESC";
    $stmtMap = $db->prepare($qMapData);
    foreach ($mapParams as $k => $v) $stmtMap->bindValue($k, $v);
    $stmtMap->execute();
    $mapData = $stmtMap->fetchAll(PDO::FETCH_ASSOC);

    // Top 3 departamentos por municipio en UNA sola query (elimina N+1)
    $qAllTopDepts = "SELECT ranked.* FROM (
        SELECT p.division_id as muni_id, u.id as departamento_id, u.nombre_unidad as departamento, COUNT(pd.id) as total,
            ROW_NUMBER() OVER (PARTITION BY p.division_id ORDER BY COUNT(pd.id) DESC) as rn
        FROM peticion_departamento pd
        INNER JOIN peticiones p ON pd.peticion_id = p.id
        INNER JOIN unidades u ON pd.departamento_id = u.id
        $mapWhereSQL
        GROUP BY p.division_id, u.id, u.nombre_unidad
    ) ranked WHERE ranked.rn <= 3";
    $stmtAllTD = $db->prepare($qAllTopDepts);
    foreach ($mapParams as $k => $v) $stmtAllTD->bindValue($k, $v);
    $stmtAllTD->execute();
    $allTopDepts = $stmtAllTD->fetchAll(PDO::FETCH_ASSOC);

    // Indexar por municipio_id
    $deptsByMuni = [];
    foreach ($allTopDepts as $row) {
        $deptsByMuni[$row['muni_id']][] = ['departamento_id' => $row['departamento_id'], 'departamento' => $row['departamento'], 'total' => $row['total']];
    }
    foreach ($mapData as &$muni) {
        $muni['top_departamentos'] = $deptsByMuni[$muni['municipio_id']] ?? [];
    }
    unset($muni);

    // ===================== RESPUESTA =====================
    sendJson([
        'success' => true,
        'kpis' => $kpis,
        'kpis_prev' => $kpisPrev,
        'por_estado' => $porEstado,
        'por_importancia' => $porImportancia,
        'timeline' => $timeline,
        'top_municipios' => $topMunicipios,
        'top_departamentos' => $topDepartamentos,
        'recientes' => $recientes,
        'alerts' => $alerts,
        'municipios_list' => $municipiosList,
        'departamentos_list' => $departamentosList,
        'detalle_departamento' => $detalleDepartamento,
        'detalle_municipio' => $detalleMunicipio,
        'card_detalle' => $cardDetalle,
        'map_data' => $mapData,
        'filtros_aplicados' => [
            'municipio_id' => $filtroMunicipio,
            'estado' => $filtroEstado,
            'importancia' => $filtroImportancia,
            'dias' => $filtroDias,
            'fecha_inicio' => $filtroFechaInicio,
            'fecha_fin' => $filtroFechaFin,
            'departamento_id' => $filtroDeptId,
            'municipio_detalle_id' => $filtroMuniDetalle
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Error en dashboard-director.php: " . $e->getMessage());
    sendJson([
        'success' => false,
        'message' => 'Error al obtener datos del dashboard',
        'error' => $e->getMessage()
    ], 500);
}
?>
