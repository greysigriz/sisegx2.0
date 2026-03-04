<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

// Solo soportamos GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
    exit;
}

try {
    // Intentar conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que la conexión sea exitosa
    if (!$db) {
        throw new Exception("No se pudo establecer conexión con la base de datos");
    }

    // Obtener parámetro de días (por defecto 7 días)
    $dias = isset($_GET['dias']) ? intval($_GET['dias']) : 7;
    
    // DEBUG: Verificar que la tabla existe
    try {
        $tableCheckQuery = "SHOW TABLES LIKE 'peticion_departamento'";
        $tableCheckStmt = $db->prepare($tableCheckQuery);
        $tableCheckStmt->execute();
        $tableExists = $tableCheckStmt->fetch();
        
        if (!$tableExists) {
            throw new Exception("La tabla 'peticion_departamento' no existe en la base de datos");
        }
    } catch (PDOException $e) {
        throw new Exception("Error verificando la tabla: " . $e->getMessage());
    }
    
    // DEBUG: Verificar qué datos existen en la tabla
    $debugQuery = "SELECT 
        COUNT(*) as total, 
        MIN(fecha_asignacion) as min_fecha, 
        MAX(fecha_asignacion) as max_fecha 
    FROM peticion_departamento";
    
    $debugStmt = $db->prepare($debugQuery);
    $debugStmt->execute();
    $debugInfo = $debugStmt->fetch(PDO::FETCH_ASSOC);
    
    // DEBUG: Obtener estados únicos que existen en la tabla
    $estadosQuery = "SELECT DISTINCT estado, COUNT(*) as count 
                     FROM peticion_departamento 
                     GROUP BY estado";
    $estadosStmt = $db->prepare($estadosQuery);
    $estadosStmt->execute();
    $estadosExistentes = $estadosStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DEBUG: Verificar estructura de la tabla
    $columnasQuery = "SHOW COLUMNS FROM peticion_departamento";
    $columnasStmt = $db->prepare($columnasQuery);
    $columnasStmt->execute();
    $columnas = $columnasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar que existan las columnas necesarias
    $columnasNombres = array_column($columnas, 'Field');
    if (!in_array('fecha_asignacion', $columnasNombres)) {
        throw new Exception("La columna 'fecha_asignacion' no existe en la tabla");
    }
    if (!in_array('estado', $columnasNombres)) {
        throw new Exception("La columna 'estado' no existe en la tabla");
    }
    
    // Query para obtener el conteo de estados por día
    $query = "
        SELECT 
            DATE(fecha_asignacion) as fecha,
            DAYNAME(fecha_asignacion) as dia_nombre,
            estado,
            COUNT(*) as cantidad
        FROM peticion_departamento
        WHERE fecha_asignacion >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
        GROUP BY DATE(fecha_asignacion), estado
        ORDER BY fecha ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':dias', $dias, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapear nombres de días en español
    $diasSemana = [
        'Monday' => 'Lun',
        'Tuesday' => 'Mar',
        'Wednesday' => 'Mié',
        'Thursday' => 'Jue',
        'Friday' => 'Vie',
        'Saturday' => 'Sáb',
        'Sunday' => 'Dom'
    ];

    // Estados posibles
    $estadosPosibles = [
        'Completado',
        'Esperando recepción',
        'Aceptado en proceso',
        'Devuelto a seguimiento',
        'Rechazado'
    ];

    // Estructura de datos para el gráfico
    $datos = [];
    $fechas = [];
    
    // Inicializar estructura de datos agrupada por fecha
    foreach ($resultados as $row) {
        $fecha = $row['fecha'];
        $diaSpanish = isset($diasSemana[$row['dia_nombre']]) ? $diasSemana[$row['dia_nombre']] : $row['dia_nombre'];
        
        if (!isset($datos[$fecha])) {
            $datos[$fecha] = [
                'fecha' => $fecha,
                'dia' => $diaSpanish,
                'estados' => []
            ];
            $fechas[] = $fecha;
        }
        
        $datos[$fecha]['estados'][$row['estado']] = intval($row['cantidad']);
    }

    // Asegurar que todos los estados existan en todas las fechas (rellenar con 0)
    foreach ($datos as $fecha => &$item) {
        foreach ($estadosPosibles as $estado) {
            if (!isset($item['estados'][$estado])) {
                $item['estados'][$estado] = 0;
            }
        }
    }

    // Si no hay datos suficientes, completar con días vacíos
    if (count($fechas) < $dias) {
        $fechasCompletas = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $fechasCompletas[] = $fecha;
            
            if (!isset($datos[$fecha])) {
                $diaSemana = date('l', strtotime($fecha));
                $diaSpanish = isset($diasSemana[$diaSemana]) ? $diasSemana[$diaSemana] : substr($diaSemana, 0, 3);
                
                $datos[$fecha] = [
                    'fecha' => $fecha,
                    'dia' => $diaSpanish,
                    'estados' => []
                ];
                
                foreach ($estadosPosibles as $estado) {
                    $datos[$fecha]['estados'][$estado] = 0;
                }
            }
        }
        
        // Reordenar datos por fechas completas
        $datosOrdenados = [];
        foreach ($fechasCompletas as $fecha) {
            if (isset($datos[$fecha])) {
                $datosOrdenados[$fecha] = $datos[$fecha];
            }
        }
        $datos = $datosOrdenados;
    }

    // Convertir a array indexado
    $datosFinales = array_values($datos);

    echo json_encode([
        'success' => true,
        'data' => $datosFinales,
        'estados' => $estadosPosibles,
        'debug' => [
            'tabla_existe' => true,
            'total_registros' => $debugInfo['total'],
            'fecha_min' => $debugInfo['min_fecha'],
            'fecha_max' => $debugInfo['max_fecha'],
            'estados_existentes' => $estadosExistentes,
            'columnas_tabla' => $columnasNombres,
            'dias_solicitados' => $dias,
            'fecha_desde' => date('Y-m-d', strtotime("-$dias days")),
            'fecha_actual' => date('Y-m-d'),
            'resultados_query' => count($resultados),
            'datos_procesados' => count($datosFinales)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}