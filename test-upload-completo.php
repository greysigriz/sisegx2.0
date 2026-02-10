<?php
/**
 * Test completo del proceso de upload de imágenes
 * Simula una subida real para encontrar dónde falla
 */

require_once 'config/database.php';

echo "<h2>🧪 Test Completo del Sistema de Upload</h2>\n";

// Función para crear una imagen de prueba
function createTestImage($filename) {
    // Crear una imagen simple de 100x100 píxeles
    $image = imagecreate(100, 100);
    $backgroundColor = imagecolorallocate($image, 0, 100, 200);
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    // Agregar texto
    imagestring($image, 5, 20, 40, 'TEST', $textColor);
    
    // Guardar como PNG en memoria
    ob_start();
    imagepng($image);
    $imageData = ob_get_contents();
    ob_end_clean();
    
    imagedestroy($image);
    return $imageData;
}

echo "<h3>📋 Paso 1: Configuración inicial</h3>\n";

// Configurar datos de prueba
$testData = [
    'entidad_tipo' => 'peticion',
    'entidad_id' => 1, // ID de petición existente
    'filename_original' => 'test_image_manual.png',
    'description' => 'Imagen de prueba manual creada por script'
];

echo "✅ Datos de prueba configurados<br>\n";
echo "  - Entidad: {$testData['entidad_tipo']} #{$testData['entidad_id']}<br>\n";
echo "  - Nombre: {$testData['filename_original']}<br>\n";

echo "<h3>🗂️ Paso 2: Verificar estructura de directorios</h3>\n";

$baseUploadPath = $_SERVER['DOCUMENT_ROOT'] . '/SISEE/uploads';
$year = date('Y');
$month = date('m');
$relativePath = "{$testData['entidad_tipo']}/{$year}/{$month}";
$fullDir = $baseUploadPath . '/' . $relativePath;

echo "📁 Rutas calculadas:<br>\n";
echo "  - DOCUMENT_ROOT: {$_SERVER['DOCUMENT_ROOT']}<br>\n";
echo "  - Base uploads: $baseUploadPath<br>\n";
echo "  - Directorio target: $fullDir<br>\n";

// Verificar/crear estructura
if (!file_exists($baseUploadPath)) {
    echo "📁 Creando directorio base: $baseUploadPath<br>\n";
    if (mkdir($baseUploadPath, 0755, true)) {
        echo "✅ Directorio base creado<br>\n";
    } else {
        echo "❌ Error creando directorio base<br>\n";
        exit;
    }
} else {
    echo "✅ Directorio base existe<br>\n";
}

if (!file_exists($fullDir)) {
    echo "📁 Creando directorio completo: $fullDir<br>\n";
    if (mkdir($fullDir, 0755, true)) {
        echo "✅ Directorio completo creado<br>\n";
    } else {
        echo "❌ Error creando directorio completo<br>\n";
        echo "  - Permisos del padre: " . decoct(fileperms(dirname($fullDir)) & 0777) . "<br>\n";
        exit;
    }
} else {
    echo "✅ Directorio completo existe<br>\n";
}

// Verificar permisos
$permissions = decoct(fileperms($fullDir) & 0777);
echo "🔒 Permisos del directorio: $permissions<br>\n";

if (is_writable($fullDir)) {
    echo "✅ Directorio escribible<br>\n";
} else {
    echo "❌ Directorio NO escribible<br>\n";
    exit;
}

echo "<h3>🖼️ Paso 3: Generar imagen de prueba</h3>\n";

$imageData = createTestImage($testData['filename_original']);
$imageSize = strlen($imageData);

echo "✅ Imagen generada en memoria<br>\n";
echo "  - Tamaño: $imageSize bytes<br>\n";
echo "  - Tipo: PNG<br>\n";

echo "<h3>💾 Paso 4: Guardar archivo en filesystem</h3>\n";

// Generar nombre único para almacenamiento
$extension = pathinfo($testData['filename_original'], PATHINFO_EXTENSION);
$uniqueFilename = uniqid('img_', true) . '.' . $extension;
$fullFilePath = $fullDir . '/' . $uniqueFilename;

echo "📝 Generando archivo:<br>\n";
echo "  - Nombre único: $uniqueFilename<br>\n";
echo "  - Ruta completa: $fullFilePath<br>\n";

// Intentar escribir archivo
$bytesWritten = file_put_contents($fullFilePath, $imageData);

if ($bytesWritten !== false) {
    echo "✅ Archivo escrito exitosamente<br>\n";
    echo "  - Bytes escritos: $bytesWritten<br>\n";
    echo "  - Bytes esperados: $imageSize<br>\n";
    
    if ($bytesWritten === $imageSize) {
        echo "✅ Tamaños coinciden<br>\n";
    } else {
        echo "⚠️ Tamaños no coinciden<br>\n";
    }
} else {
    echo "❌ Error escribiendo archivo<br>\n";
    exit;
}

// Verificar que el archivo existe y es legible
if (file_exists($fullFilePath)) {
    echo "✅ Archivo existe en filesystem<br>\n";
    $actualSize = filesize($fullFilePath);
    echo "  - Tamaño verificado: $actualSize bytes<br>\n";
} else {
    echo "❌ Archivo NO existe después de escritura<br>\n";
    exit;
}

echo "<h3>🗄️ Paso 5: Guardar en base de datos</h3>\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // URL de acceso público
    $publicURL = "/SISEE/uploads/{$relativePath}/{$uniqueFilename}";
    
    $sql = "INSERT INTO imagenes (
        entidad_tipo, entidad_id, filename_original, filename_storage,
        path_relativo, url_acceso, mime_type, file_size, description,
        usuario_subida, fecha_subida
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $testData['entidad_tipo'],
        $testData['entidad_id'],
        $testData['filename_original'],
        $uniqueFilename,
        $relativePath,
        $publicURL,
        'image/png',
        $imageSize,
        $testData['description']
    ]);
    
    if ($result) {
        $newId = $pdo->lastInsertId();
        echo "✅ Registro guardado en base de datos<br>\n";
        echo "  - ID asignado: $newId<br>\n";
        echo "  - URL pública: $publicURL<br>\n";
    } else {
        echo "❌ Error guardando en base de datos<br>\n";
        $errorInfo = $stmt->errorInfo();
        echo "  - Error: {$errorInfo[2]}<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Excepción en base de datos: " . $e->getMessage() . "<br>\n";
}

echo "<h3>🌐 Paso 6: Verificar acceso público</h3>\n";

$testURL = "http://localhost" . $publicURL;
echo "🔗 URL de prueba: <a href='$testURL' target='_blank'>$testURL</a><br>\n";

// Test HTTP
$headers = @get_headers($testURL);
if ($headers) {
    echo "📡 Headers HTTP:<br>\n";
    foreach (array_slice($headers, 0, 3) as $header) {
        echo "  - $header<br>\n";
    }
    
    if (strpos($headers[0], '200') !== false) {
        echo "✅ Imagen accesible vía HTTP<br>\n";
        echo "🖼️ <img src='$testURL' alt='Test image' style='max-width:200px; border:1px solid #ccc; margin:10px 0;'><br>\n";
    } else {
        echo "❌ Imagen NO accesible vía HTTP<br>\n";
    }
} else {
    echo "❌ No se pudo obtener headers HTTP<br>\n";
}

echo "<h3>✅ Test Completado</h3>\n";
echo "<p><strong>Este test ha simulado todo el proceso de upload paso a paso. Si ves la imagen arriba, el sistema funciona correctamente.</strong></p>\n";

// Crear un .htaccess si no existe
$htaccessPath = $baseUploadPath . '/.htaccess';
if (!file_exists($htaccessPath)) {
    echo "<h3>🔧 Creando .htaccess de protección</h3>\n";
    $htaccessContent = "# Permitir acceso a imágenes\n";
    $htaccessContent .= "<Files ~ \"\\.(jpg|jpeg|png|gif|webp)$\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Allow from all\n";
    $htaccessContent .= "</Files>\n\n";
    $htaccessContent .= "# Denegar acceso a otros archivos\n";
    $htaccessContent .= "<Files ~ \"^(?!.*\\.(jpg|jpeg|png|gif|webp)$).*$\">\n";
    $htaccessContent .= "    Order deny,allow\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "✅ .htaccess creado en $htaccessPath<br>\n";
    } else {
        echo "❌ Error creando .htaccess<br>\n";
    }
}

?>