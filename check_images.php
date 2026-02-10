<?php
// Verificar imágenes en la base de datos
try {
    $pdo = new PDO('mysql:host=nice-dubinsky.192-99-212-154.plesk.page;dbname=sisegestion;port=3306', 'siseg', 'NK!Igudh306ameu?');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ESTRUCTURA DE TABLA IMAGENES ===\n";
    $result = $pdo->query('DESCRIBE imagenes');
    if ($result) {
        foreach($result as $row) {
            printf("Field: %s, Type: %s, Null: %s, Key: %s, Default: %s, Extra: %s\n",
                $row['Field'], $row['Type'], $row['Null'], 
                $row['Key'], $row['Default'], $row['Extra']);
        }
    } else {
        echo "Error: No se pudo obtener la estructura de la tabla\n";
    }
    
    echo "\n=== TODAS LAS IMÁGENES ===\n";
    $result = $pdo->query('SELECT * FROM imagenes ORDER BY id DESC LIMIT 5');
    if ($result) {
        foreach($result as $row) {
            print_r($row);
            echo "---\n";
        }
    } else {
        echo "No hay imágenes\n";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
?>