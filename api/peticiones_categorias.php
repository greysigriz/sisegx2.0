<?php
// Handle CORS first so preflight/requests include proper headers
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$mapping = [
    'Baches' => ['bache', 'baches', 'bacheo'],
    'Alumbrado Público' => ['alumbrado', 'luz', 'poste', 'foco', 'farol'],
    'Basura' => ['basura', 'recoleccion', 'recolecta', 'residuo', 'basurero'],
    'Robos' => ['robo', 'robos', 'hurto', 'hurtado'],
    'Incendios' => ['incendi', 'fuego', 'humo', 'quemad'],
    'Falta de Agua' => ['agua', 'sin agua', 'abastecimiento', 'corte de agua'],
    'Otros' => []
];

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $stmt = $pdo->query("SELECT descripcion FROM peticiones WHERE descripcion IS NOT NULL AND descripcion != ''");
    $counts = array_fill_keys(array_keys($mapping), 0);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $text = mb_strtolower($row['descripcion'], 'UTF-8');
        $matched = false;

        foreach ($mapping as $category => $keywords) {
            if ($category === 'Otros') continue;
            foreach ($keywords as $kw) {
                if (mb_stripos($text, $kw, 0, 'UTF-8') !== false) {
                    $counts[$category]++;
                    $matched = true;
                    break 2;
                }
            }
        }

        if (!$matched) {
            $counts['Otros']++;
        }
    }

    // Convert to records array and sort by value desc
    $records = [];
    foreach ($counts as $name => $value) {
        $records[] = ['name' => $name, 'value' => $value];
    }

    usort($records, function($a, $b) { return $b['value'] <=> $a['value']; });

    echo json_encode(['records' => $records], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    error_log('peticiones_categorias error: ' . $e->getMessage());
    echo json_encode(['message' => 'Error procesando categorías: ' . $e->getMessage()]);
}

?>
