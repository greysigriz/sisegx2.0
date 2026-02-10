<?php
/**
 * Re-procesar la imagen que está en la base de datos pero no en el filesystem
 */

require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "🔍 Re-procesando imagen faltante...\n\n";

// Obtener la imagen de la base de datos
$stmt = $pdo->prepare("SELECT * FROM imagenes WHERE id = 1");
$stmt->execute();
$imagen = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$imagen) {
    echo "❌ No se encontró imagen con ID 1 en la base de datos\n";
    exit;
}

echo "📋 Datos de la imagen:\n";
echo "  - ID: {$imagen['id']}\n";
echo "  - Nombre original: {$imagen['filename_original']}\n";
echo "  - Nombre almacenado: {$imagen['filename_storage']}\n";
echo "  - Ruta relativa: {$imagen['path_relativo']}\n";
echo "  - URL acceso: {$imagen['url_acceso']}\n";
echo "  - Tamaño esperado: {$imagen['file_size']} bytes\n\n";

// Construir rutas
$baseUploadPath = $_SERVER['DOCUMENT_ROOT'] . '/SISEE/uploads';
$fullPath = $baseUploadPath . '/' . $imagen['path_relativo'] . '/' . $imagen['filename_storage'];
$dir = dirname($fullPath);

echo "🗂️ Rutas calculadas:\n";
echo "  - Base: $baseUploadPath\n";
echo "  - Directorio: $dir\n";
echo "  - Archivo completo: $fullPath\n\n";

// Verificar y crear directorio
if (!file_exists($dir)) {
    echo "📁 Creando directorio: $dir\n";
    if (mkdir($dir, 0755, true)) {
        echo "✅ Directorio creado exitosamente\n";
    } else {
        echo "❌ Error creando directorio\n";
        exit;
    }
} else {
    echo "✅ Directorio ya existe: $dir\n";
}

// Verificar si el archivo existe
if (file_exists($fullPath)) {
    echo "✅ Archivo ya existe: " . basename($fullPath) . "\n";
    $actualSize = filesize($fullPath);
    echo "  - Tamaño actual: $actualSize bytes\n";
    echo "  - Tamaño esperado: {$imagen['file_size']} bytes\n";
    
    if ($actualSize == $imagen['file_size']) {
        echo "✅ Tamaños coinciden - archivo correcto\n";
    } else {
        echo "⚠️ Tamaños no coinciden - archivo podría estar corrupto\n";
    }
} else {
    echo "❌ Archivo NO existe: " . basename($fullPath) . "\n";
    echo "🔧 Intentando recuperar/crear archivo...\n";
    
    // Buscar si existe en otras ubicaciones posibles
    $possiblePaths = [
        $_SERVER['DOCUMENT_ROOT'] . '/SISEE/' . $imagen['filename_storage'],
        $_SERVER['DOCUMENT_ROOT'] . '/SISEE/uploads/' . $imagen['filename_storage'],
        $_SERVER['DOCUMENT_ROOT'] . '/SISEE/api/uploads/' . $imagen['filename_storage'],
        './uploads/' . $imagen['filename_storage'],
        './uploads/' . $imagen['path_relativo'] . '/' . $imagen['filename_storage'],
    ];
    
    $found = false;
    foreach ($possiblePaths as $possiblePath) {
        if (file_exists($possiblePath)) {
            echo "✅ Archivo encontrado en ubicación alternativa: $possiblePath\n";
            echo "🔄 Copiando a ubicación correcta...\n";
            
            if (copy($possiblePath, $fullPath)) {
                echo "✅ Archivo copiado exitosamente\n";
                $found = true;
                break;
            } else {
                echo "❌ Error copiando archivo\n";
            }
        }
    }
    
    if (!$found) {
        echo "❌ Archivo no encontrado en ninguna ubicación\n";
        echo "💡 Sugerencias:\n";
        echo "  1. El archivo se perdió durante el upload\n";
        echo "  2. Hay un error en la ruta de almacenamiento\n";
        echo "  3. Problemas de permisos impidieron la escritura\n\n";
        
        // Crear un archivo de placeholder para testing
        echo "🔧 Creando archivo de placeholder para testing...\n";
        $placeholderContent = "Esta es una imagen de placeholder para testing - ID: {$imagen['id']}";
        file_put_contents($fullPath . '.txt', $placeholderContent);
        echo "✅ Placeholder creado: " . basename($fullPath) . ".txt\n";
    }
}

echo "\n🌐 Verificando acceso URL:\n";
$testURL = "http://localhost" . $imagen['url_acceso'];
echo "  - URL: $testURL\n";

// Test de acceso HTTP
$headers = @get_headers($testURL);
if ($headers) {
    echo "  - Status HTTP: " . $headers[0] . "\n";
    if (strpos($headers[0], '200') !== false) {
        echo "✅ URL accesible\n";
    } else {
        echo "❌ URL no accesible\n";
    }
} else {
    echo "❌ No se pudo verificar URL\n";
}

echo "\n✅ Diagnóstico completado\n";
?>