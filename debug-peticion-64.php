<?php
/**
 * Debug específico para petición #64
 */

require_once 'config/database.php';

echo "<h2>🔍 Debug Petición #64 - Imágenes</h2>\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h3>📋 1. Verificar petición #64 existe</h3>\n";
    
    $stmt = $pdo->prepare("SELECT id, folio, descripcion FROM peticiones WHERE id = 64");
    $stmt->execute();
    $peticion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($peticion) {
        echo "✅ Petición encontrada:<br>\n";
        echo "  - ID: {$peticion['id']}<br>\n";
        echo "  - Folio: {$peticion['folio']}<br>\n";
        echo "  - Descripción: " . substr($peticion['descripcion'], 0, 100) . "...<br>\n";
    } else {
        echo "❌ Petición #64 NO encontrada<br>\n";
        
        // Buscar peticiones con FOLIO-000041
        echo "<h3>🔍 Buscando por folio FOLIO-000041</h3>\n";
        $stmt = $pdo->prepare("SELECT id, folio, descripcion FROM peticiones WHERE folio LIKE '%FOLIO-000041%'");
        $stmt->execute();
        $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($peticiones) {
            echo "📋 Peticiones encontradas con ese folio:<br>\n";
            foreach ($peticiones as $p) {
                echo "  - ID: {$p['id']}, Folio: {$p['folio']}<br>\n";
            }
        } else {
            echo "❌ No se encontraron peticiones con ese folio<br>\n";
        }
        
        exit;
    }
    
    echo "<h3>🖼️ 2. Verificar imágenes en tabla</h3>\n";
    
    $stmt = $pdo->prepare("
        SELECT * FROM imagenes 
        WHERE entidad_tipo = 'peticion' AND entidad_id = 64
        ORDER BY fecha_subida DESC
    ");
    $stmt->execute();
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($imagenes) {
        echo "✅ Imágenes encontradas (" . count($imagenes) . "):<br>\n";
        foreach ($imagenes as $img) {
            echo "<div style='border:1px solid #ccc; margin:10px 0; padding:10px;'>\n";
            echo "  <strong>ID:</strong> {$img['id']}<br>\n";
            echo "  <strong>Archivo original:</strong> {$img['filename_original']}<br>\n";
            echo "  <strong>Archivo almacenado:</strong> {$img['filename_storage']}<br>\n";
            echo "  <strong>Ruta relativa:</strong> {$img['path_relativo']}<br>\n";
            echo "  <strong>URL:</strong> {$img['url_acceso']}<br>\n";
            echo "  <strong>Activa:</strong> " . ($img['activa'] ? 'Sí' : 'No') . "<br>\n";
            echo "  <strong>Fecha:</strong> {$img['fecha_subida']}<br>\n";
            
            // Verificar si el archivo físico existe
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/SISEE/uploads/' . $img['path_relativo'] . '/' . $img['filename_storage'];
            $exists = file_exists($fullPath);
            echo "  <strong>Archivo físico:</strong> " . ($exists ? "✅ Existe" : "❌ No existe") . "<br>\n";
            echo "  <strong>Ruta completa:</strong> $fullPath<br>\n";
            
            if ($exists) {
                $size = filesize($fullPath);
                echo "  <strong>Tamaño físico:</strong> $size bytes<br>\n";
                echo "  <strong>Tamaño BD:</strong> {$img['file_size']} bytes<br>\n";
                
                $testURL = "http://localhost" . $img['url_acceso'];
                echo "  <strong>Test URL:</strong> <a href='$testURL' target='_blank'>$testURL</a><br>\n";
                echo "  <img src='$testURL' style='max-width:200px; max-height:150px; border:1px solid #ddd;' alt='Test'><br>\n";
            }
            echo "</div>\n";
        }
    } else {
        echo "❌ No se encontraron imágenes para petición #64<br>\n";
        
        echo "<h3>🔍 3. Buscar imágenes en toda la tabla</h3>\n";
        
        $stmt = $pdo->prepare("
            SELECT entidad_tipo, entidad_id, COUNT(*) as total 
            FROM imagenes 
            WHERE activa = 1
            GROUP BY entidad_tipo, entidad_id 
            ORDER BY entidad_tipo, entidad_id
        ");
        $stmt->execute();
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($stats) {
            echo "📊 Imágenes por entidad:<br>\n";
            foreach ($stats as $stat) {
                echo "  - {$stat['entidad_tipo']} #{$stat['entidad_id']}: {$stat['total']} imágenes<br>\n";
            }
        } else {
            echo "❌ No hay imágenes en toda la tabla<br>\n";
        }
    }
    
    echo "<h3>🧪 4. Test del API</h3>\n";
    
    $apiURL = "http://localhost/SISEE/api/imagenes.php?entidad_tipo=peticion&entidad_id=64";
    echo "📡 Probando: <a href='$apiURL' target='_blank'>$apiURL</a><br>\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($apiURL, false, $context);
    if ($response !== false) {
        echo "✅ API responde:<br>\n";
        echo "<pre>$response</pre>\n";
    } else {
        echo "❌ API no responde<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

?>