<?php
/**
 * Script de diagnóstico para el sistema de imágenes
 * Verifica que todo esté configurado correctamente
 */

header('Content-Type: text/html; charset=UTF-8');
echo "<html><head><title>🔍 Diagnóstico del Sistema de Imágenes</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.ok{color:green;}.error{color:red;}.warning{color:orange;}.section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:8px;}.code{background:#f5f5f5;padding:10px;border-radius:4px;font-family:monospace;}</style></head><body>";

echo "<h1>🔍 Diagnóstico del Sistema de Imágenes - SISEE</h1>";

// 1. Verificar conexión a base de datos
echo "<div class='section'>";
echo "<h2>1. 🗄️ Conexión a Base de Datos</h2>";

try {
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p class='ok'>✅ Conexión a base de datos exitosa</p>";
    
    // 2. Verificar tabla imagenes
    echo "<h2>2. 📋 Verificación de Tabla 'imagenes'</h2>";
    
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'imagenes'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p class='ok'>✅ Tabla 'imagenes' existe</p>";
        
        // Verificar estructura
        $structure = $pdo->query("DESCRIBE imagenes");
        $fields = $structure->fetchAll(PDO::FETCH_ASSOC);
        echo "<p class='ok'>✅ Estructura de la tabla:</p>";
        echo "<div class='code'>";
        foreach ($fields as $field) {
            echo "{$field['Field']} - {$field['Type']}<br>";
        }
        echo "</div>";
        
        // Verificar datos
        $count = $pdo->query("SELECT COUNT(*) as total FROM imagenes")->fetch(PDO::FETCH_ASSOC);
        echo "<p>📊 Total de imágenes en BD: <strong>{$count['total']}</strong></p>";
        
        if ($count['total'] > 0) {
            $recent = $pdo->query("SELECT * FROM imagenes ORDER BY fecha_subida DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            echo "<p class='ok'>📸 Últimas imágenes subidas:</p>";
            echo "<div class='code'>";
            foreach ($recent as $img) {
                echo "ID: {$img['id']} - {$img['filename_original']} - {$img['entidad_tipo']} #{$img['entidad_id']} - {$img['fecha_subida']}<br>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<p class='error'>❌ Tabla 'imagenes' NO existe - ejecutar migración</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

echo "</div>";

// 3. Verificar carpetas de uploads
echo "<div class='section'>";
echo "<h2>3. 📁 Verificación de Carpetas</h2>";

$uploadBasePath = $_SERVER['DOCUMENT_ROOT'] . '/SISEE/uploads';
$requiredDirs = [
    'uploads' => $uploadBasePath,
    'uploads/peticiones' => $uploadBasePath . '/peticiones',
    'uploads/historial' => $uploadBasePath . '/historial'
];

foreach ($requiredDirs as $name => $path) {
    if (file_exists($path) && is_dir($path)) {
        $writable = is_writable($path) ? " (escribible)" : " (NO escribible)";
        $class = is_writable($path) ? "ok" : "error";
        echo "<p class='{$class}'>✅ {$name}: {$path} {$writable}</p>";
        
        // Contar archivos
        $files = glob($path . '/*');
        $fileCount = count($files);
        echo "<p>📄 Archivos en {$name}: {$fileCount}</p>";
        
    } else {
        echo "<p class='error'>❌ {$name}: {$path} NO existe</p>";
        echo "<p class='warning'>💡 Crear con: mkdir -p {$path}</p>";
    }
}

echo "</div>";

// 4. Verificar API
echo "<div class='section'>";
echo "<h2>4. 🔌 Verificación de API</h2>";

$apiPath = $_SERVER['DOCUMENT_ROOT'] . '/SISEE/api/imagenes.php';
if (file_exists($apiPath)) {
    echo "<p class='ok'>✅ API imagenes.php existe: {$apiPath}</p>";
    
    // Verificar que sea accesible vía HTTP
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/SISEE/api/imagenes.php';
    echo "<p>🌐 URL de API: <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";
    
    // Test GET request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "?entidad_tipo=peticion&entidad_id=999");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "<p class='ok'>✅ API responde correctamente (HTTP {$httpCode})</p>";
        $json = json_decode($response, true);
        if ($json && isset($json['success'])) {
            echo "<p class='ok'>✅ Respuesta JSON válida</p>";
        } else {
            echo "<p class='error'>❌ Respuesta JSON inválida</p>";
            echo "<div class='code'>" . htmlspecialchars($response) . "</div>";
        }
    } else {
        echo "<p class='error'>❌ API no responde (HTTP {$httpCode})</p>";
    }
    
} else {
    echo "<p class='error'>❌ API imagenes.php NO existe: {$apiPath}</p>";
}

echo "</div>";

// 5. Verificar configuraciones PHP
echo "<div class='section'>";
echo "<h2>5. ⚙️ Configuración PHP</h2>";

$uploadMaxSize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxFileUploads = ini_get('max_file_uploads');
$memoryLimit = ini_get('memory_limit');

echo "<div class='code'>";
echo "upload_max_filesize: {$uploadMaxSize}<br>";
echo "post_max_size: {$postMaxSize}<br>";
echo "max_file_uploads: {$maxFileUploads}<br>";
echo "memory_limit: {$memoryLimit}<br>";
echo "</div>";

$uploadSizeBytes = (int)$uploadMaxSize * 1024 * 1024;
if ($uploadSizeBytes >= 10 * 1024 * 1024) {
    echo "<p class='ok'>✅ Configuración PHP permite archivos de 10MB</p>";
} else {
    echo "<p class='warning'>⚠️ upload_max_filesize ({$uploadMaxSize}) puede ser insuficiente para archivos de 10MB</p>";
}

echo "</div>";

// 6. Test de conectividad frontend
echo "<div class='section'>";
echo "<h2>6. 🌐 Información para Frontend</h2>";

echo "<div class='code'>";
echo "Servidor: {$_SERVER['HTTP_HOST']}<br>";
echo "Ruta base: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "API URL: http://{$_SERVER['HTTP_HOST']}/SISEE/api/imagenes.php<br>";
echo "Uploads URL: http://{$_SERVER['HTTP_HOST']}/SISEE/uploads/<br>";
echo "</div>";

// Verificar CORS
echo "<p>🔒 Headers CORS desde API:</p>";
$headers = get_headers($apiUrl . "?entidad_tipo=peticion&entidad_id=1");
echo "<div class='code'>";
foreach ($headers as $header) {
    if (stripos($header, 'access-control') !== false) {
        echo htmlspecialchars($header) . "<br>";
    }
}
echo "</div>";

echo "</div>";

// 7. Script de prueba rápida
echo "<div class='section'>";
echo "<h2>7. 🧪 Prueba Rápida</h2>";
echo "<p>Para probar la subida de imágenes:</p>";
echo "<ol>";
echo "<li>Ir a: <a href='/SISEE/test-imagenes.html' target='_blank'>http://{$_SERVER['HTTP_HOST']}/SISEE/test-imagenes.html</a></li>";
echo "<li>Seleccionar tipo 'Petición' e ID '1'</li>";
echo "<li>Subir una imagen pequeña (menos de 1MB)</li>";
echo "<li>Verificar que aparezca en la lista</li>";
echo "<li>Comprobar en BD: <code>SELECT * FROM imagenes ORDER BY fecha_subida DESC LIMIT 1;</code></li>";
echo "</ol>";

echo "</div>";

// 8. Registro de errores
echo "<div class='section'>";
echo "<h2>8. 📝 Posibles Problemas</h2>";

echo "<h3>Si no se suben imágenes:</h3>";
echo "<ol>";
echo "<li><strong>Verificar JavaScript Console</strong> - Abrir Developer Tools (F12) y buscar errores</li>";
echo "<li><strong>Verificar Network Tab</strong> - Ver si las peticiones HTTP llegan al servidor</li>";
echo "<li><strong>Verificar logs de Apache</strong> - C:\\xampp\\apache\\logs\\error.log</li>";
echo "<li><strong>Verificar permisos</strong> - La carpeta uploads debe ser escribible</li>";
echo "<li><strong>Verificar ruta de API</strong> - Debe estar en /SISEE/api/imagenes.php</li>";
echo "</ol>";

echo "<h3>Comandos útiles:</h3>";
echo "<div class='code'>";
echo "# Ver logs de PHP:<br>";
echo "tail -f C:\\xampp\\apache\\logs\\error.log<br><br>";

echo "# Verificar BD:<br>";
echo "SELECT * FROM imagenes ORDER BY fecha_subida DESC LIMIT 5;<br><br>";

echo "# Crear carpetas manualmente:<br>";
echo "mkdir C:\\xampp\\htdocs\\SISEE\\uploads\\peticiones<br>";
echo "mkdir C:\\xampp\\htdocs\\SISEE\\uploads\\historial<br>";
echo "</div>";

echo "</div>";

echo "<div style='margin-top: 30px; padding: 20px; background: #e8f4fd; border-radius: 8px;'>";
echo "<h2>🔍 Diagnóstico Completado</h2>";
echo "<p>Revisa cada sección marcada con ❌ para solucionar problemas.</p>";
echo "<p>Si todo está ✅ pero aún no funcionan las imágenes, verifica la consola del navegador (F12) para errores de JavaScript.</p>";
echo "</div>";

echo "</body></html>";
?>