<?php
/**
 * Verificar y arreglar estructura de directorios de uploads
 */

$baseUploadPath = $_SERVER['DOCUMENT_ROOT'] . '/SISEE/uploads';

echo "🔍 Verificando estructura de directorios...\n\n";

// Verificar directorio base
if (!file_exists($baseUploadPath)) {
    echo "❌ Directorio base no existe: $baseUploadPath\n";
    mkdir($baseUploadPath, 0755, true);
    echo "✅ Directorio base creado\n\n";
} else {
    echo "✅ Directorio base existe: $baseUploadPath\n\n";
}

// Verificar estructura esperada
$expectedDirs = [
    $baseUploadPath . '/peticion',
    $baseUploadPath . '/peticion/2026',
    $baseUploadPath . '/peticion/2026/02',
    $baseUploadPath . '/historial',
];

foreach ($expectedDirs as $dir) {
    if (!file_exists($dir)) {
        echo "❌ Directorio no existe: $dir\n";
        mkdir($dir, 0755, true);
        echo "✅ Directorio creado: $dir\n";
    } else {
        echo "✅ Directorio existe: $dir\n";
    }
}

echo "\n📂 Listando contenido de uploads/peticion/2026/02/:\n";
$targetDir = $baseUploadPath . '/peticion/2026/02';
if (is_dir($targetDir)) {
    $files = scandir($targetDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filepath = $targetDir . '/' . $file;
            $size = filesize($filepath);
            echo "  📄 $file (" . round($size/1024, 1) . " KB)\n";
        }
    }
    if (count($files) <= 2) {
        echo "  📭 Directorio vacío\n";
    }
} else {
    echo "  ❌ Directorio no accesible\n";
}

echo "\n🔍 Verificando archivo específico esperado:\n";
$expectedFile = $baseUploadPath . '/peticion/2026/02/peticion_1_20260205230003_55a74d90.png';
if (file_exists($expectedFile)) {
    echo "✅ Archivo existe: " . basename($expectedFile) . " (" . round(filesize($expectedFile)/1024, 1) . " KB)\n";
} else {
    echo "❌ Archivo NO existe: " . basename($expectedFile) . "\n";
}

echo "\n📋 Reporte de permisos:\n";
echo "Permisos uploads: " . substr(sprintf('%o', fileperms($baseUploadPath)), -4) . "\n";
if (file_exists($targetDir)) {
    echo "Permisos peticion/2026/02: " . substr(sprintf('%o', fileperms($targetDir)), -4) . "\n";
}

echo "\n🔧 Verificando .htaccess:\n";
$htaccessFile = $baseUploadPath . '/.htaccess';
if (file_exists($htaccessFile)) {
    echo "✅ .htaccess existe\n";
    echo "Contenido:\n";
    echo file_get_contents($htaccessFile);
} else {
    echo "❌ .htaccess no existe\n";
    echo "Creando .htaccess de seguridad...\n";
    file_put_contents($htaccessFile, "# Prevenir ejecución de PHP\n<Files *.php>\n    Order Deny,Allow\n    Deny from all\n</Files>\n\n# Permitir imágenes\n<FilesMatch \"\\.(jpg|jpeg|png|gif|bmp|webp)$\">\n    Order Allow,Deny\n    Allow from all\n</FilesMatch>");
    echo "✅ .htaccess creado\n";
}

echo "\n✅ Verificación completada\n";
?>