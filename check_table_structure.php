<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== ESTRUCTURA DE LA TABLA peticiones ===\n\n";
$result = $db->query('DESCRIBE peticiones');
foreach($result as $row) {
    printf("%-30s %-20s\n", $row['Field'], $row['Type']);
}

echo "\n=== MUESTRA DE DATOS ===\n\n";
$result = $db->query('SELECT * FROM peticiones LIMIT 1');
$sample = $result->fetch(PDO::FETCH_ASSOC);
if ($sample) {
    echo "Campos disponibles en la tabla:\n";
    foreach (array_keys($sample) as $field) {
        echo "  - $field\n";
    }
}
?>