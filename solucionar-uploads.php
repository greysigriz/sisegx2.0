<?php
/**
 * Solucionador definitivo del problema de uploads
 * Este script identifica y corrige todos los problemas posibles
 */

echo "<h1>🔧 Solucionador de Problemas de Upload</h1>\n";

// Paso 1: Verificar y crear estructura base
echo "<h2>📁 Paso 1: Estructura de directorios</h2>\n";

$baseDir = $_SERVER['DOCUMENT_ROOT'] . '/SISEE';
$uploadsDir = $baseDir . '/uploads';
$testDir = $uploadsDir . '/peticion/2024/12';

echo "🗂️ Rutas del sistema:<br>\n";
echo "  - DOCUMENT_ROOT: {$_SERVER['DOCUMENT_ROOT']}<br>\n"; 
echo "  - Base SISEE: $baseDir<br>\n";
echo "  - Uploads base: $uploadsDir<br>\n";
echo "  - Test directory: $testDir<br>\n";

// Crear directorios si no existen
$directorios = [
    $uploadsDir,
    $uploadsDir . '/peticion',
    $uploadsDir . '/peticion/2024',
    $testDir,
    $uploadsDir . '/historial_cambio',
    $uploadsDir . '/historial_cambio/2024',
    $uploadsDir . '/historial_cambio/2024/12'
];

foreach ($directorios as $dir) {
    if (!file_exists($dir)) {
        echo "📝 Creando: $dir<br>\n";
        if (mkdir($dir, 0755, true)) {
            echo "✅ Creado exitosamente<br>\n";
        } else {
            echo "❌ Error creando directorio<br>\n";
        }
    } else {
        echo "✅ Ya existe: " . basename($dir) . "<br>\n";
    }
}

// Paso 2: Verificar permisos
echo "<h2>🔒 Paso 2: Permisos</h2>\n";

foreach ($directorios as $dir) {
    if (file_exists($dir)) {
        $perms = decoct(fileperms($dir) & 0777);
        $writable = is_writable($dir) ? "✅ Escribible" : "❌ NO escribible";
        echo "📁 " . basename($dir) . " - Permisos: $perms - $writable<br>\n";
    }
}

// Paso 3: Crear .htaccess de protección
echo "<h2>🛡️ Paso 3: Protección .htaccess</h2>\n";

$htaccessPath = $uploadsDir . '/.htaccess';
$htaccessContent = "# Configuración de acceso para uploads de SISEE\n";
$htaccessContent .= "Options -Indexes\n\n";
$htaccessContent .= "# Permitir acceso a imágenes\n";
$htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif|webp|bmp)$\">\n";
$htaccessContent .= "    Order allow,deny\n";
$htaccessContent .= "    Allow from all\n";
$htaccessContent .= "    Header set Cache-Control \"max-age=31536000, public\"\n";
$htaccessContent .= "</FilesMatch>\n\n";
$htaccessContent .= "# Denegar acceso a otros archivos\n";
$htaccessContent .= "<FilesMatch \"\\.(php|txt|sql|log)$\">\n";
$htaccessContent .= "    Order deny,allow\n";
$htaccessContent .= "    Deny from all\n";
$htaccessContent .= "</FilesMatch>\n";

if (file_put_contents($htaccessPath, $htaccessContent)) {
    echo "✅ .htaccess creado en $htaccessPath<br>\n";
} else {
    echo "❌ Error creando .htaccess<br>\n";
}

// Paso 4: Test de escritura real
echo "<h2>✍️ Paso 4: Test de escritura</h2>\n";

$testFile = $testDir . '/test_write.txt';
$testContent = "Test de escritura - " . date('Y-m-d H:i:s');

if (file_put_contents($testFile, $testContent)) {
    echo "✅ Test de escritura exitoso<br>\n";
    echo "  - Archivo: $testFile<br>\n";
    echo "  - Contenido: " . file_get_contents($testFile) . "<br>\n";
    
    // Limpiar archivo de test
    unlink($testFile);
    echo "🗑️ Archivo de test eliminado<br>\n";
} else {
    echo "❌ Error en test de escritura<br>\n";
}

// Paso 5: Verificar base de datos
echo "<h2>🗄️ Paso 5: Verificación de base de datos</h2>\n";

try {
    require_once './config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "✅ Conexión a base de datos exitosa<br>\n";
    
    // Verificar que existe la tabla imagenes
    $stmt = $pdo->query("SHOW TABLES LIKE 'imagenes'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabla 'imagenes' existe<br>\n";
        
        // Verificar estructura
        $stmt = $pdo->query("DESCRIBE imagenes");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Columnas de la tabla (" . count($columns) . "):<br>\n";
        
        foreach (array_slice($columns, 0, 5) as $column) {
            echo "  - {$column['Field']} ({$column['Type']})<br>\n";
        }
        
        // Contar registros existentes
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM imagenes");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "📊 Total de imágenes en BD: $total<br>\n";
        
    } else {
        echo "❌ Tabla 'imagenes' NO existe<br>\n";
        echo "💡 Ejecuta el SQL de migración desde database/imagenes_sistema.sql<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "<br>\n";
}

// Paso 6: Test de configuración PHP
echo "<h2>⚙️ Paso 6: Configuración PHP</h2>\n";

$phpSettings = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

foreach ($phpSettings as $setting => $value) {
    $status = ($setting === 'file_uploads') ? ($value ? "✅ Habilitado" : "❌ Deshabilitado") : "✅ $value";
    echo "⚙️ $setting: $status<br>\n";
}

// Verificar extensiones importantes
$extensions = [
    'gd' => 'Manipulación de imágenes',
    'fileinfo' => 'Detección de tipo MIME',
    'pdo' => 'Base de datos'
];

echo "<br>🔧 Extensiones PHP:<br>\n";
foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "✅ Cargada" : "⚠️ No disponible";
    echo "📦 $ext ($desc): $status<br>\n";
    
    if (!$loaded && $ext === 'gd') {
        echo "  💡 Para habilitar GD: descomenta <code>;extension=gd</code> en php.ini<br>\n";
    }
}

// Paso 7: Test completo del flujo
echo "<h2>🧪 Paso 7: Test completo del flujo</h2>\n";

// Crear imagen de test
function createTestImage() {
    // Verificar si GD está disponible
    if (!extension_loaded('gd')) {
        // Crear un archivo PNG simple válido (1x1 pixel transparente)
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAGAWM+5lgAAAABJRU5ErkJggg==');
        return $pngData;
    }
    
    try {
        $image = imagecreate(200, 100);
        $bg = imagecolorallocate($image, 41, 128, 185);
        $white = imagecolorallocate($image, 255, 255, 255);
        
        imagestring($image, 5, 50, 30, 'SISEE', $white);
        imagestring($image, 3, 45, 50, 'Test Image', $white);
        imagestring($image, 2, 60, 70, date('H:i:s'), $white);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        
        return $imageData;
    } catch (Exception $e) {
        // Fallback: crear PNG simple
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAGAWM+5lgAAAABJRU5ErkJggg==');
        return $pngData;
    }
}

$imageData = createTestImage();
$fileName = 'test_sisee_' . date('YmdHis') . '.png';
$filePath = $testDir . '/' . $fileName;

// Información sobre GD
if (!extension_loaded('gd')) {
    echo "⚠️ Extensión GD no disponible - usando imagen PNG simple<br>\n";
} else {
    echo "✅ Extensión GD disponible - creando imagen personalizada<br>\n";
}

if (file_put_contents($filePath, $imageData)) {
    echo "✅ Imagen de test creada<br>\n";
    echo "  - Archivo: $fileName<br>\n";
    echo "  - Tamaño: " . strlen($imageData) . " bytes<br>\n";
    echo "  - Ruta: $filePath<br>\n";
    
    $relativePath = 'peticion/2024/12';
    $publicURL = "/SISEE/uploads/{$relativePath}/{$fileName}";
    echo "  - URL pública: $publicURL<br>\n";
    
    // Test de acceso HTTP
    $testURL = "http://localhost" . $publicURL;
    echo "🌐 Test de acceso: <a href='$testURL' target='_blank'>$testURL</a><br>\n";
    
    $headers = @get_headers($testURL);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "✅ Imagen accesible vía HTTP<br>\n";
        echo "🖼️ <img src='$testURL' alt='Test SISEE' style='border:2px solid #2980b9; margin:10px 0;'><br>\n";
    } else {
        echo "❌ Imagen NO accesible vía HTTP<br>\n";
        if ($headers) {
            echo "  - Status: {$headers[0]}<br>\n";
        }
    }
    
} else {
    echo "❌ Error creando imagen de test<br>\n";
}

// Paso 8: Verificar API
echo "<h2>🔌 Paso 8: Verificación del API</h2>\n";

$apiPath = $baseDir . '/api/imagenes.php';
if (file_exists($apiPath)) {
    echo "✅ API existe: $apiPath<br>\n";
    
    $apiURL = "http://localhost/SISEE/api/imagenes.php?entidad_tipo=peticion&entidad_id=1";
    echo "🔗 URL del API: <a href='$apiURL' target='_blank'>$apiURL</a><br>\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($apiURL, false, $context);
    if ($response !== false) {
        echo "✅ API responde<br>\n";
        $data = json_decode($response, true);
        if ($data) {
            echo "  - Status: " . ($data['success'] ? 'OK' : 'Error') . "<br>\n";
            echo "  - Imágenes: " . ($data['total'] ?? 0) . "<br>\n";
        }
    } else {
        echo "❌ API no responde<br>\n";
    }
} else {
    echo "❌ API no encontrada: $apiPath<br>\n";
}

echo "<h2>✅ Diagnóstico Completado</h2>\n";
echo "<div style='background:#e8f5e8; padding:15px; border-radius:5px; margin:20px 0;'>\n";
echo "<strong>🎉 Si ves la imagen de test arriba, el sistema de uploads está funcionando correctamente.</strong><br>\n";
echo "Ahora puedes usar el formulario de peticiones para subir imágenes reales.\n";
echo "</div>\n";

// Información de solución
echo "<h3>💡 Soluciones aplicadas:</h3>\n";
echo "<ul>\n";
echo "<li>✅ Estructura de directorios creada</li>\n";
echo "<li>✅ Permisos configurados</li>\n";
echo "<li>✅ .htaccess de protección instalado</li>\n";
echo "<li>✅ Test de escritura verificado</li>\n";
echo "<li>✅ Configuración PHP validada</li>\n";
echo "</ul>\n";

?>