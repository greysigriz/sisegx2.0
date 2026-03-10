<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Coordenadas de municipios de Yucatán (extendido)
    $coordenadasMunicipios = [
        // Zona Norte
        'Mérida' => ['lat' => 20.9674, 'lng' => -89.5926],
        'Progreso' => ['lat' => 21.2817, 'lng' => -89.6650],
        'Motul' => ['lat' => 21.0949, 'lng' => -89.2881],
        'Izamal' => ['lat' => 20.9306, 'lng' => -89.0172],
        'Conkal' => ['lat' => 21.0833, 'lng' => -89.5167],
        'Chicxulub Puerto' => ['lat' => 21.2667, 'lng' => -89.5667],
        'Telchac Puerto' => ['lat' => 21.3333, 'lng' => -89.2667],
        
        // Zona Centro
        'Kanasín' => ['lat' => 20.9337, 'lng' => -89.5533],
        'Umán' => ['lat' => 20.8867, 'lng' => -89.7517],
        'Hunucmá' => ['lat' => 21.0159, 'lng' => -89.8761],
        'Maxcanú' => ['lat' => 20.5872, 'lng' => -90.1686],
        'Acanceh' => ['lat' => 20.8167, 'lng' => -89.4500],
        'Tecoh' => ['lat' => 20.7333, 'lng' => -89.4667],
        'Tekit' => ['lat' => 20.5833, 'lng' => -89.3667],
        
        // Zona Oriente
        'Valladolid' => ['lat' => 20.6896, 'lng' => -88.2018],
        'Tizimín' => ['lat' => 21.1450, 'lng' => -88.1653],
        'Temozón' => ['lat' => 20.9333, 'lng' => -88.2833],
        'Dzitás' => ['lat' => 20.8333, 'lng' => -88.3000],
        'Espita' => ['lat' => 21.0167, 'lng' => -88.3000],
        'Río Lagartos' => ['lat' => 21.5667, 'lng' => -88.1500],
        'San Felipe' => ['lat' => 21.5667, 'lng' => -88.2333],
        
        // Zona Sur
        'Ticul' => ['lat' => 20.3992, 'lng' => -89.5342],
        'Oxkutzcab' => ['lat' => 20.3042, 'lng' => -89.4208],
        'Tekax' => ['lat' => 20.2053, 'lng' => -89.2828],
        'Peto' => ['lat' => 20.1294, 'lng' => -88.9228],
        'Tzucacab' => ['lat' => 20.0833, 'lng' => -89.0500],
        'Akil' => ['lat' => 20.2667, 'lng' => -89.3333],
        'Maní' => ['lat' => 20.3833, 'lng' => -89.4667],
        'Teabo' => ['lat' => 20.4333, 'lng' => -89.2833],
        
        // Zona Poniente
        'Celestún' => ['lat' => 20.8667, 'lng' => -90.4000],
        'Kinchil' => ['lat' => 20.9000, 'lng' => -90.0500],
        'Kopomá' => ['lat' => 20.9667, 'lng' => -89.9833],
        'Tetiz' => ['lat' => 20.9833, 'lng' => -89.8667],
        'Halachó' => ['lat' => 20.4833, 'lng' => -90.0833],
        
        // Otros municipios importantes
        'Chemax' => ['lat' => 20.6500, 'lng' => -87.9333],
        'Buctzotz' => ['lat' => 21.2000, 'lng' => -88.3667],
        'Calotmul' => ['lat' => 21.1333, 'lng' => -88.2333],
        'Panabá' => ['lat' => 21.2833, 'lng' => -88.2833],
        'Sucilá' => ['lat' => 21.1667, 'lng' => -88.5000],
        'Sotuta' => ['lat' => 20.6333, 'lng' => -89.0167],
        'Cansahcab' => ['lat' => 21.1667, 'lng' => -89.6333],
        'Yaxcabá' => ['lat' => 20.5833, 'lng' => -88.8000]
    ];

    // Mapeo de estados de la BD a categorías del mapa
    $mapeoEstados = [
        'Sin revisar' => 'Pendiente',
        'Rechazado por departamento' => 'Rechazado',
        'Por asignar departamento' => 'Pendiente',
        'Completado' => 'Atendido',
        'Aceptada en proceso' => 'En Proceso',
        'Devuelto' => 'En Proceso',
        'Improcedente' => 'Rechazado',
        'Cancelada' => 'Rechazado',
        'Esperando recepción' => 'Pendiente'
    ];

    // Consulta para obtener peticiones agrupadas por municipio usando DivisionAdministrativa
    $query = "
        SELECT 
            da.Municipio as municipio,
            da.Estado as estado_division,
            p.estado,
            COUNT(*) as cantidad
        FROM peticiones p
        INNER JOIN DivisionAdministrativa da ON p.division_id = da.Id
        WHERE da.Municipio IS NOT NULL 
        AND da.Municipio != ''
        GROUP BY da.Municipio, da.Estado, p.estado
        ORDER BY da.Municipio, cantidad DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar datos por municipio
    $municipiosData = [];

    foreach ($results as $row) {
        $municipio = $row['municipio'];
        $estadoDivision = $row['estado_division'] ?? 'Yucatán';
        $estadoOriginal = $row['estado'];
        $estadoMapeado = $mapeoEstados[$estadoOriginal] ?? 'Pendiente';
        $cantidad = intval($row['cantidad']);

        if (!isset($municipiosData[$municipio])) {
            // Buscar coordenadas (búsqueda más inteligente)
            $coords = null;
            
            // 1. Búsqueda exacta (ignorando mayúsculas/minúsculas)
            $municipioNormalizado = mb_strtolower(trim($municipio));
            foreach ($coordenadasMunicipios as $nombre => $coord) {
                if (mb_strtolower($nombre) === $municipioNormalizado) {
                    $coords = $coord;
                    break;
                }
            }
            
            // 2. Búsqueda parcial si no se encontró exacta
            if (!$coords) {
                foreach ($coordenadasMunicipios as $nombre => $coord) {
                    if (stripos($municipio, $nombre) !== false || stripos($nombre, $municipio) !== false) {
                        $coords = $coord;
                        break;
                    }
                }
            }

            // 3. Si no se encuentran coordenadas, determinar ubicación por defecto según estado
            if (!$coords) {
                // Si es de Yucatán, usar centro de Yucatán
                if (stripos($estadoDivision, 'Yucatán') !== false || stripos($estadoDivision, 'Yucatan') !== false) {
                    $coords = ['lat' => 20.7099, 'lng' => -89.0943]; // Centro de Yucatán
                } else {
                    // Para otros estados, usar una ubicación fuera de Yucatán (requiere actualización manual)
                    $coords = ['lat' => 19.4326, 'lng' => -99.1332]; // CDMX como ejemplo
                }
            }

            $municipiosData[$municipio] = [
                'municipio' => $municipio,
                'estado' => $estadoDivision,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'total' => 0,
                'rechazado' => 0,
                'atendido' => 0,
                'pendiente' => 0,
                'enProceso' => 0,
                'problemas' => []
            ];
        }

        // Incrementar contadores
        $municipiosData[$municipio]['total'] += $cantidad;
        
        switch ($estadoMapeado) {
            case 'Rechazado':
                $municipiosData[$municipio]['rechazado'] += $cantidad;
                break;
            case 'Atendido':
                $municipiosData[$municipio]['atendido'] += $cantidad;
                break;
            case 'Pendiente':
                $municipiosData[$municipio]['pendiente'] += $cantidad;
                break;
            case 'En Proceso':
                $municipiosData[$municipio]['enProceso'] += $cantidad;
                break;
        }
    }

    // Obtener problemas principales por municipio
    $queryProblemas = "
        SELECT 
            da.Municipio as municipio,
            SUBSTRING_INDEX(p.descripcion, ' ', 3) as problema_corto,
            COUNT(*) as cantidad
        FROM peticiones p
        INNER JOIN DivisionAdministrativa da ON p.division_id = da.Id
        WHERE da.Municipio IS NOT NULL 
        AND da.Municipio != ''
        AND p.descripcion IS NOT NULL
        GROUP BY da.Municipio, problema_corto
        ORDER BY da.Municipio, cantidad DESC
    ";

    $stmtProblemas = $db->prepare($queryProblemas);
    $stmtProblemas->execute();
    $problemas = $stmtProblemas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($problemas as $problema) {
        $municipio = $problema['municipio'];
        $problemaDesc = $problema['problema_corto'];
        $cantidad = intval($problema['cantidad']);

        if (isset($municipiosData[$municipio])) {
            if (!isset($municipiosData[$municipio]['problemas'][$problemaDesc])) {
                $municipiosData[$municipio]['problemas'][$problemaDesc] = 0;
            }
            $municipiosData[$municipio]['problemas'][$problemaDesc] += $cantidad;
        }
    }

    // Limpiar y limitar problemas a top 5 por municipio
    foreach ($municipiosData as &$mun) {
        // Convertir contadores de estados a objeto "estados"
        $mun['estados'] = [];
        if ($mun['rechazado'] > 0) {
            $mun['estados']['Rechazado'] = $mun['rechazado'];
        }
        if ($mun['atendido'] > 0) {
            $mun['estados']['Atendido'] = $mun['atendido'];
        }
        if ($mun['pendiente'] > 0) {
            $mun['estados']['Pendiente'] = $mun['pendiente'];
        }
        if ($mun['enProceso'] > 0) {
            $mun['estados']['En Proceso'] = $mun['enProceso'];
        }

        // Remover campos individuales (opcional, pero limpia la respuesta)
        unset($mun['rechazado']);
        unset($mun['atendido']);
        unset($mun['pendiente']);
        unset($mun['enProceso']);

        // Convertir problemas a array con formato [tipo, cantidad]
        $problemasArray = [];
        arsort($mun['problemas']);
        $topProblemas = array_slice($mun['problemas'], 0, 5, true);
        foreach ($topProblemas as $tipo => $cantidad) {
            $problemasArray[] = [
                'tipo' => $tipo,
                'cantidad' => $cantidad
            ];
        }
        $mun['problemas'] = $problemasArray;
    }
    unset($mun); // Liberar referencia

    // Convertir a array indexado
    $resultado = array_values($municipiosData);

    // Estadísticas adicionales
    $totalReportes = array_sum(array_column($resultado, 'total'));
    $municipiosYucatan = array_filter($resultado, function($m) {
        return stripos($m['estado'] ?? 'Yucatán', 'Yucatán') !== false;
    });
    $municipiosFuera = array_filter($resultado, function($m) {
        return stripos($m['estado'] ?? 'Yucatán', 'Yucatán') === false;
    });

    echo json_encode([
        'success' => true,
        'municipios' => $resultado,
        'total' => count($resultado),
        'estadisticas' => [
            'totalReportes' => $totalReportes,
            'municipiosYucatan' => count($municipiosYucatan),
            'municipiosFueraYucatan' => count($municipiosFuera),
            'reportesYucatan' => array_sum(array_column($municipiosYucatan, 'total')),
            'reportesFuera' => array_sum(array_column($municipiosFuera, 'total'))
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener datos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
